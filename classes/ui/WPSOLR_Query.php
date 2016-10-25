<?php

/**
 * Manage Solr query parameters.
 */
class WPSOLR_Query extends WP_Query {

	protected $solr_client;

	protected $wp_query;

	//protected $query;
	protected $wpsolr_query;
	protected $wpsolr_filter_query;
	protected $wpsolr_paged;
	protected $wpsolr_sort;


	/**
	 * Constructor used by factory WPSOLR_Global
	 *
	 * @return WPSOLR_Query
	 */
	static function global_object( WPSOLR_Query $wpsolr_query = null ) {

		// Create/Update query from parameters
		return WPSOLR_Query_Parameters::CreateQuery( $wpsolr_query );
	}


	/**
	 * @param WP_Query $wp_query
	 *
	 * @return WPSOLR_Query
	 */
	public static function Create() {

		$wpsolr_query = new WPSOLR_Query();

		$wpsolr_query->set_defaults();

		return $wpsolr_query;
	}

	public function set_defaults() {

		$this->set_wpsolr_query( '' );
		$this->set_filter_query_fields( array() );
		$this->set_wpsolr_paged( '0' );
		$this->set_wpsolr_sort( WPSOLR_Global::getOption()->get_sortby_default() );
	}

	/**
	 * @return string
	 */
	public function get_wpsolr_query( $default = '' ) {

		// Prevent Solr error by replacing empty query by default value
		return empty( $this->wpsolr_query ) ? $default : $this->wpsolr_query;
	}

	/**
	 * @param string $query
	 */
	public function set_wpsolr_query( $query ) {
		$this->wpsolr_query = $query;
	}

	/**
	 * @return array
	 */
	public function get_filter_query_fields() {
		return ! empty( $this->wpsolr_filter_query ) ? $this->wpsolr_filter_query : array();
	}

	/**
	 * @param array $fq
	 */
	public function set_filter_query_fields( $fq ) {
		// Ensure fq is always an array
		$this->wpsolr_filter_query = empty( $fq ) ? array() : ( is_array( $fq ) ? $fq : array( $fq ) );
	}

	/**
	 * @return string
	 */
	public function get_wpsolr_paged() {
		return $this->wpsolr_paged;
	}

	/**
	 * Calculate the start of pagination
	 * @return integer
	 */
	public function get_start() {
		return ( $this->get_wpsolr_paged() == 0 || $this->get_wpsolr_paged() == 1 ) ? 0 : ( ( $this->get_wpsolr_paged() - 1 ) * $this->get_nb_results_by_page() );
	}


	/**
	 * Get the nb of results by page
	 * @return integer
	 */
	public function get_nb_results_by_page() {
		return WPSOLR_Global::getOption()->get_search_max_nb_results_by_page();
	}

	/**
	 * @param string $wpsolr_paged
	 */
	public function set_wpsolr_paged( $wpsolr_paged ) {
		$this->wpsolr_paged = $wpsolr_paged;
	}

	/**
	 * @return string
	 */
	public function get_wpsolr_sort() {
		return $this->wpsolr_sort;
	}

	/**
	 * @param string $wpsolr_sort
	 */
	public function set_wpsolr_sort( $wpsolr_sort ) {
		$this->wpsolr_sort = $wpsolr_sort;
	}


	/**************************************************************************
	 *
	 * Override WP_Query methods
	 *
	 *************************************************************************/

	function get_posts() {

		//return parent::get_posts();

		// Let WP extract parameters
		$this->parse_query();
		$q = &$this->query_vars;
		$this->parse_search($q);

		// Copy WP standard query to WPSOLR query
		$this->set_wpsolr_query( $this->query[ WPSOLR_Query_Parameters::SEARCH_PARAMETER_S ] );

		// Copy WP standard paged to WPSOLR paged
		$this->set_wpsolr_paged( isset( $this->query_vars['paged'] ) ? $this->query_vars['paged'] : 1 );

		// $_GET['s'] is used internally by some themes
		//$_GET['s'] = $query;

		// Set variable 's', so that get_search_query() and other standard WP_Query methods still work with our own search parameter
		//$this->set( 's', $query );

		$this->solr_client = WPSOLR_Global::getSolrClient();
		$this->resultSet   = $this->solr_client->execute_wpsolr_query( $this );

		// Create posts from Solr PIDs
		$posts_in_results = $this->solr_client->get_posts_from_pids();

		foreach ( $posts_in_results as $post ) {
			$this->set_the_title( $post );
			$this->set_the_excerpt( $post );
		}

		$this->posts       = $posts_in_results;
		$this->post_count  = count( $this->posts );
		$this->found_posts = $this->resultSet->getNumFound();

		$this->posts_per_page = $this->get_nb_results_by_page();
		$this->set( "posts_per_page", $this->posts_per_page );
		$this->max_num_pages = ceil( $this->found_posts / $this->posts_per_page );

		if ( ! isset( $this->query_vars['name'] ) ) {
			// Prevent error later in WP code
			$this->query_vars['name'] = '';
		}

		return $this->posts;
	}

	protected function get_highlighting_of_field( $field_name, $post_id ) {

		$highlighting = $this->resultSet->getHighlighting();

		$highlightedDoc = $highlighting ? $highlighting->getResult( $post_id ) : null;
		if ( $highlightedDoc ) {

			$highlighted_field = $highlightedDoc->getField( $field_name );

			return empty( $highlighted_field ) ? '' : implode( ' (...) ', $highlighted_field );
		}


		return '';
	}

	protected function set_the_title( WP_Post $post ) {

		$result = $this->get_highlighting_of_field( WpSolrSchema::_FIELD_NAME_TITLE, $post->ID );

		if ( ! empty( $result ) ) {

			$post->post_title = $result;
		}
	}


	protected function set_the_excerpt( WP_Post $post ) {

		$result = $this->get_highlighting_of_field( WpSolrSchema::_FIELD_NAME_CONTENT, $post->ID );

		if ( ! empty( $result ) ) {

			$post->post_excerpt = $result;
		}
	}

	/**
	 * Regroup filter query fields by field
	 * ['type:post', 'type:page', 'category:cat1'] => ['type' => ['post', 'page'], 'category' => ['cat1']]
	 * @return array
	 */
	public function get_filter_query_fields_group_by_name() {

		$results = array();

		foreach ( $this->get_filter_query_fields() as $field_encoded ) {

			// Convert 'type:post' in ['type', 'post']
			$field = explode( ':', $field_encoded );

			if ( count( $field ) == 2 ) {

				if ( ! isset( $results[ $field[0] ] ) ) {

					$results[ $field[0] ] = array( $field[1] );

				} else {

					$results[ $field[0] ][] .= $field[1];
				}
			}
		}

		return $results;
	}


}