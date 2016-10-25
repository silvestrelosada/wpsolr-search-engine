<?php

use Solarium\Core\Query\Result\ResultInterface;
use Solarium\QueryType\Select\Query\Query;

require_once plugin_dir_path( __FILE__ ) . 'wpsolr-abstract-solr-client.php';

/**
 * Class WPSolrSearchSolrClient
 *
 * @property \Solarium\QueryType\Select\Result\Result $solarium_results
 */
class WPSolrSearchSolrClient extends WPSolrAbstractSolrClient {

	protected $is_query_wildcard;

	protected $solarium_results;

	protected $solarium_query;

	protected $solarium_config;

	// Array of active extension objects
	protected $wpsolr_extensions;

	// Search template
	const _SEARCH_PAGE_TEMPLATE = 'wpsolr-search-engine/search.php';

	// Search page slug
	const _SEARCH_PAGE_SLUG = 'search-wpsolr';

	// Do not change - Sort by most relevant
	const SORT_CODE_BY_RELEVANCY_DESC = 'sort_by_relevancy_desc';

	// Do not change - Sort by newest
	const SORT_CODE_BY_DATE_DESC = 'sort_by_date_desc';

	// Do not change - Sort by oldest
	const SORT_CODE_BY_DATE_ASC = 'sort_by_date_asc';

	// Do not change - Sort by least comments
	const SORT_CODE_BY_NUMBER_COMMENTS_ASC = 'sort_by_number_comments_asc';

	// Do not change - Sort by most comments
	const SORT_CODE_BY_NUMBER_COMMENTS_DESC = 'sort_by_number_comments_desc';

	// Default maximum number of items returned by facet
	const DEFAULT_MAX_NB_ITEMS_BY_FACET = 10;

	// Defaut minimum count for a facet to be returned
	const DEFAULT_MIN_COUNT_BY_FACET = 1;

	// Default maximum size of highliting fragments
	const DEFAULT_HIGHLIGHTING_FRAGMENT_SIZE = 100;

	// Default highlighting prefix
	const DEFAULT_HIGHLIGHTING_PREFIX = '<b>';

	// Default highlighting postfix
	const DEFAULT_HIGHLIGHTING_POSFIX = '</b>';

	const PARAMETER_HIGHLIGHTING_FIELD_NAMES = 'field_names';
	const PARAMETER_HIGHLIGHTING_FRAGMENT_SIZE = 'fragment_size';
	const PARAMETER_HIGHLIGHTING_PREFIX = 'prefix';
	const PARAMETER_HIGHLIGHTING_POSTFIX = 'postfix';

	const PARAMETER_FACET_FIELD_NAMES = 'field_names';
	const PARAMETER_FACET_LIMIT = 'limit';
	const PARAMETER_FACET_MIN_COUNT = 'min_count';


	// Create using a configuration
	static function create_from_solarium_config( $solarium_config ) {

		return new self( $solarium_config );
	}


	/**
	 * Constructor used by factory WPSOLR_Global
	 * Create using the default index configuration
	 *
	 * @return WPSolrSearchSolrClient
	 */
	static function global_object() {

		return self::create_from_index_indice( null );
	}

	// Create using an index configuration
	static function create_from_index_indice( $index_indice ) {

		// Build Solarium config from the default indexing Solr index
		WpSolrExtensions::require_once_wpsolr_extension( WpSolrExtensions::OPTION_INDEXES, true );
		$options_indexes = new OptionIndexes();
		$solarium_config = $options_indexes->build_solarium_config( $index_indice, null, self::DEFAULT_SOLR_TIMEOUT_IN_SECOND );

		return new self( $solarium_config );
	}

	public function __construct( $solarium_config ) {

		$this->init_galaxy();

		$path = plugin_dir_path( __FILE__ ) . '../../vendor/autoload.php';
		require_once $path;
		$this->solarium_client = new Solarium\Client( $solarium_config );

	}


	/**
	 * Get suggestions from Solr (keywords or posts).
	 *
	 * @param string $query Keywords to suggest from
	 *
	 * @return array
	 */
	public function get_suggestions( $query ) {

		$results = array();

		switch ( WPSOLR_Global::getOption()->get_search_suggest_content_type() ) {

			case WPSOLR_Option::OPTION_SEARCH_SUGGEST_CONTENT_TYPE_POSTS:
				$results = $this->get_suggestions_posts( $query );
				break;

			case WPSOLR_Option::OPTION_SEARCH_SUGGEST_CONTENT_TYPE_KEYWORDS:
				$results = $this->get_suggestions_keywords( $query );
				break;

			default:
				break;
		}

		return $results;
	}


	/**
	 * Get suggestions from Solr search.
	 *
	 * @param string $query Keywords to suggest from
	 *
	 * @return array
	 */
	public function get_suggestions_posts( $query ) {

		$wpsolr_query = WPSOLR_Global::getQuery();
		$wpsolr_query->set_wpsolr_query( $query );

		$results = WPSOLR_Global::getSolrClient()->display_results( $wpsolr_query );

		return array_slice( $results[3], 0, 5 );
	}


	/**
	 * Get suggestions from Solr suggester.
	 *
	 * @param string $query Keywords to suggest from
	 *
	 * @return array
	 */
	public function get_suggestions_keywords( $query ) {

		$results = array();

		$client = $this->solarium_client;


		$suggestqry = $client->createSuggester();
		$suggestqry->setHandler( 'suggest' );
		$suggestqry->setDictionary( 'suggest' );
		$suggestqry->setQuery( $query );
		$suggestqry->setCount( 5 );
		$suggestqry->setCollate( true );
		$suggestqry->setOnlyMorePopular( true );

		$resultset = $this->execute( $client, $suggestqry );

		foreach ( $resultset as $term => $termResult ) {

			foreach ( $termResult as $result ) {

				if ( !empty ( $response['suggest'] ) ) {
					$terms = reset( array_values ( $response['suggest'] ) );
					if ( !empty ( $terms[$input] ) ) {
						$suggestions = $terms[$input]['suggestions'];
						if ( count( $suggestions ) ) {
							foreach ( $suggestions as $suggestion ) {
								$results[] = $suggestion['term'];
							}
						}
					}
				}
			}
		}

		return $results;
	}

	/*
	 * Manage options by hosting mode
	 * Use a dedicated postfix added to the option name.
	 */
	public function get_facet_suggestions( $input ) {
		$client = $this->solarium_client;
		$query = $client->createQuery( $client::QUERY_SELECT )
			->setOmitHeader( null )
			->setRows( 0 );
		$query->getFacetSet()
			->createFacetField( 'suggest' )
			->setField( 'suggest' )
			->setPrefix( $input )
			->setLimit( 20 );
		$result = $client->execute( $query );
		$facets = $result->getFacetSet();
		if ($facets != null)
		{
			$suggest = $facets->getFacet( 'suggest' )->getValues();
			if (count($suggest) > 1)
			{
				foreach ($suggest as $suggestion => $count)
				{
					if (strtolower( $suggestion ) == $input)
					{
						continue;
					}
					$results[] = $suggestion;
				}
			}
		}
		return $results;
	}

	/**
	 * Retrieve or create the search page
	 */
	static function get_search_page() {

		// Let other plugins (POLYLANG, ...) modify the search page slug
		$search_page_slug = apply_filters( WpSolrFilters::WPSOLR_FILTER_SEARCH_PAGE_SLUG, self::_SEARCH_PAGE_SLUG );

		// Search page is found by it's path (hard-coded).
		$search_page = get_page_by_path( $search_page_slug );

		if ( ! $search_page ) {

			$search_page = self::create_default_search_page();

		} else {

			if ( $search_page->post_status != 'publish' ) {

				$search_page->post_status = 'publish';

				wp_update_post( $search_page );
			}
		}


		return $search_page;
	}


	/**
	 * Create a default search page
	 *
	 * @return WP_Post The search page
	 */
	static function create_default_search_page() {

		// Let other plugins (POLYLANG, ...) modify the search page slug
		$search_page_slug = apply_filters( WpSolrFilters::WPSOLR_FILTER_SEARCH_PAGE_SLUG, self::_SEARCH_PAGE_SLUG );

		$_search_page = array(
			'post_type'      => 'page',
			'post_title'     => 'Search Results',
			'post_content'   => '[solr_search_shortcode]',
			'post_status'    => 'publish',
			'post_author'    => 1,
			'comment_status' => 'closed',
			'post_name'      => $search_page_slug
		);

		// Let other plugins (POLYLANG, ...) modify the search page
		$_search_page = apply_filters( WpSolrFilters::WPSOLR_FILTER_BEFORE_CREATE_SEARCH_PAGE, $_search_page );

		$search_page_id = wp_insert_post( $_search_page );

		update_post_meta( $search_page_id, 'bwps_enable_ssl', '1' );

		return get_post( $search_page_id );
	}

	/**
	 * Get all sort by options available
	 *
	 * @param string $sort_code_to_retrieve
	 *
	 * @return array
	 */
	public
	static function get_sort_options() {

		$results = array(

			array(
				'code'  => self::SORT_CODE_BY_RELEVANCY_DESC,
				'label' => 'Most relevant',
			),
			array(
				'code'  => self::SORT_CODE_BY_DATE_DESC,
				'label' => 'Newest',
			),
			array(
				'code'  => self::SORT_CODE_BY_DATE_ASC,
				'label' => 'Oldest',
			),
			array(
				'code'  => self::SORT_CODE_BY_NUMBER_COMMENTS_DESC,
				'label' => 'More comments',
			),
			array(
				'code'  => self::SORT_CODE_BY_NUMBER_COMMENTS_ASC,
				'label' => 'Less comments',
			),
		);

		return $results;
	}

	/**
	 * Get all sort by options available
	 *
	 * @param string $sort_code_to_retrieve
	 *
	 * @return array
	 */
	public static function get_sort_option_from_code( $sort_code_to_retrieve, $sort_options = null ) {

		if ( $sort_options == null ) {
			$sort_options = self::get_sort_options();
		}

		if ( $sort_code_to_retrieve != null ) {
			foreach ( $sort_options as $sort ) {

				if ( $sort['code'] === $sort_code_to_retrieve ) {
					return $sort;
				}
			}
		}


		return null;
	}

	/**
	 * Convert a $wpsolr_query in a Solarium select query
	 *
	 * @param WPSOLR_Query $wpsolr_query
	 *
	 * @return Query
	 */
	public function set_solarium_query( WPSOLR_Query $wpsolr_query ) {

		// Create the solarium query
		$solarium_query = $this->solarium_client->createSelect();

		// Set the query keywords.
		$this->set_keywords( $solarium_query, $wpsolr_query->get_wpsolr_query() );

		// Set default operator
		$solarium_query->setQueryDefaultOperator( 'AND' );

		// Limit nb of results
		$solarium_query->setStart( $wpsolr_query->get_start() )->setRows( WPSOLR_Global::getOption()->get_search_max_nb_results_by_page() );

		/*
		* Add sort field(s)
		*/
		$this->add_sort_field( $solarium_query, $wpsolr_query->get_wpsolr_sort() );

		/*
		* Add facet fields
		*/
		$this->add_filter_query_fields( $solarium_query, $wpsolr_query->get_filter_query_fields() );

		/*
		* Add highlighting fields
		*/
		$this->add_highlighting_fields( $solarium_query,
			array(
				self::PARAMETER_HIGHLIGHTING_FIELD_NAMES   => array(
					WpSolrSchema::_FIELD_NAME_TITLE,
					WpSolrSchema::_FIELD_NAME_CONTENT,
					WpSolrSchema::_FIELD_NAME_COMMENTS
				),
				self::PARAMETER_HIGHLIGHTING_FRAGMENT_SIZE => WPSOLR_Global::getOption()->get_search_max_length_highlighting(),
				self::PARAMETER_HIGHLIGHTING_PREFIX        => self::DEFAULT_HIGHLIGHTING_PREFIX,
				self::PARAMETER_HIGHLIGHTING_POSTFIX       => self::DEFAULT_HIGHLIGHTING_POSFIX
			)
		);

		/*
		 * Add facet fields
		 */
		$this->add_facet_fields( $solarium_query,
			array(
				self::PARAMETER_FACET_FIELD_NAMES => WPSOLR_Global::getOption()->get_facets_to_display(),
				self::PARAMETER_FACET_LIMIT       => WPSOLR_Global::getOption()->get_search_max_nb_items_by_facet(),
				self::PARAMETER_FACET_MIN_COUNT   => self::DEFAULT_MIN_COUNT_BY_FACET
			)
		);

		/*
		 * Add fields
		 */
		$this->add_fields( $solarium_query );


		// Filter to change the solarium query
		do_action( WpSolrFilters::WPSOLR_ACTION_SOLARIUM_QUERY,
			array(
				WpSolrFilters::WPSOLR_ACTION_SOLARIUM_QUERY__PARAM_SOLARIUM_QUERY => $solarium_query,
				WpSolrFilters::WPSOLR_ACTION_SOLARIUM_QUERY__PARAM_SEARCH_TERMS   => $wpsolr_query->get_wpsolr_query(),
				WpSolrFilters::WPSOLR_ACTION_SOLARIUM_QUERY__PARAM_SEARCH_USER    => wp_get_current_user(),
			)
		);

		// Done
		return $this->solarium_query = $solarium_query;
	}

	/**
	 * Execute a WPSOLR query.
	 *
	 * @param WPSOLR_Query $wpsolr_query
	 *
	 * @return ResultInterface
	 */
	public function execute_wpsolr_query( WPSOLR_Query $wpsolr_query ) {

		if ( isset( $this->solarium_results ) ) {
			// Return results already in cache
			return $this->solarium_results;
		}

		// Create the solarium query from the wpsolr query
		$this->set_solarium_query( $wpsolr_query );

		// Perform the query, return the Solarium result set
		return $this->execute_solarium_query();

	}

	/**
	 * Execute a Solarium query.
	 * Used internally, or when fine tuned solarium select query is better than using a WPSOLR query.
	 *
	 * @param Query $solarium_query
	 *
	 * @return ResultInterface
	 */
	public function execute_solarium_query( Query $solarium_query = null ) {

		// Perform the query, return the Solarium result set
		return $this->solarium_results = $this->execute( $this->solarium_client, isset( $solarium_query ) ? $solarium_query : $this->solarium_query );
	}

	/**
	 *
	 * @param WPSOLR_Query $wpsolr_query
	 *
	 * @return array Array of html
	 */
	public function display_results( WPSOLR_Query $wpsolr_query ) {

		$output        = array();
		$search_result = array();

		// Load options
		$localization_options = OptionLocalization::get_options();

		$resultset = $this->execute_wpsolr_query( $wpsolr_query );

		$found = $resultset->getNumFound();

		// No results: try a new query if did you mean is activated
		if ( ( $found === 0 ) && ( WPSOLR_Global::getOption()->get_search_is_did_you_mean() ) ) {

			// Add spellcheck to current solarium query
			$spell_check = $this->solarium_query->getSpellcheck();
			$spell_check->setCount( 10 );
			$spell_check->setCollate( true );
			$spell_check->setExtendedResults( true );
			$spell_check->setCollateExtendedResults( true );

			// Excecute the query modified
			$resultset = $this->execute_solarium_query();

			// Parse spell check results
			$spell_check_results = $resultset->getSpellcheck();
			if ( $spell_check_results && ! $spell_check_results->getCorrectlySpelled() ) {
				$collations          = $spell_check_results->getCollations();
				$queryTermsCorrected = $wpsolr_query->get_wpsolr_query(); // original query
				foreach ( $collations as $collation ) {
					foreach ( $collation->getCorrections() as $input => $correction ) {
						$queryTermsCorrected = str_replace( $input, is_array( $correction ) ? $correction[0] : $correction, $queryTermsCorrected );
					}

				}

				if ( $queryTermsCorrected != $wpsolr_query->get_wpsolr_query() ) {

					$err_msg         = sprintf( OptionLocalization::get_term( $localization_options, 'results_header_did_you_mean' ), $queryTermsCorrected ) . '<br/>';
					$search_result[] = $err_msg;

					// Execute query with spelled terms
					$this->solarium_query->setQuery( $queryTermsCorrected );
					try {
						$resultset = $this->execute_solarium_query();
						$found     = $resultset->getNumFound();

					} catch ( Exception $e ) {
						// Sometimes, the spelling query returns errors
						// java.lang.StringIndexOutOfBoundsException: String index out of range: 15\n\tat java.lang.AbstractStringBuilder.charAt(AbstractStringBuilder.java:203)\n\tat
						// java.lang.StringBuilder.charAt(StringBuilder.java:72)\n\tat org.apache.solr.spelling.SpellCheckCollator.getCollation(SpellCheckCollator.java:164)\n\tat

						$found = 0;
					}

				} else {
					$search_result[] = 0;
				}

			} else {
				$search_result[] = 0;
			}

		} else {
			$search_result[] = 0;
		}

		// Retrieve facets from resultset
		$facets_to_display = WPSOLR_Global::getOption()->get_facets_to_display();
		if ( count( $facets_to_display ) ) {
			foreach ( $facets_to_display as $facet ) {

				$fact      = $this->get_facet_hierarchy_name( WpSolrSchema::_FIELD_NAME_FLAT_HIERARCHY, $facet );
				$facet_res = $resultset->getFacetSet()->getFacet( "$fact" );

				foreach ( $facet_res as $value => $count ) {
					$output[ $facet ][] = array( 'value' => $value, 'count' => $count );
				}


			}
			$search_result[] = $output;

		} else {
			$search_result[] = 0;
		}

		$search_result[] = $found;

		$results      = array();
		$highlighting = $resultset->getHighlighting();

		$i                    = 1;
		$cat_arr              = array();
		$are_comments_indexed = WPSOLR_Global::getOption()->get_index_are_comments_indexed();
		foreach ( $resultset as $document ) {

			$post_id = $document->PID;
			$title   = mb_convert_encoding($document->title, 'UTF-8', 'auto');
			$content = '';

			$image_url = $this->get_post_thumbnail( $document, $post_id );

			$no_comments = $document->numcomments;
			if ( $are_comments_indexed ) {
				$comments = $document->comments;
			}
			$date = date( 'm/d/Y', strtotime( $document->displaydate ) );

			if ( property_exists( $document, 'categories_str' ) ) {
				$cat_arr = $document->categories_str;
			}


			$cat  = implode( ',', $cat_arr );
			$auth = $document->author;

			$url = $this->get_post_url( $document, $post_id );

			$comm_no        = 0;
			$highlightedDoc = $highlighting ? $highlighting->getResult( $document->id ) : null;
			if ( $highlightedDoc ) {

				foreach ( $highlightedDoc as $field => $highlight ) {

					if ( $field == WpSolrSchema::_FIELD_NAME_TITLE ) {

						$title = implode( ' (...) ', $highlight );

					} else if ( $field == WpSolrSchema::_FIELD_NAME_CONTENT ) {

						$content = implode( ' (...) ', $highlight );

					} else if ( $field == WpSolrSchema::_FIELD_NAME_COMMENTS ) {

						$comments = implode( ' (...) ', $highlight );
						$comm_no  = 1;

					}

				}

			}

			$msg = '';
			$msg .= "<div id='res$i'><div class='p_title'><a href='$url'>$title</a></div>";

			$image_fragment = '';
			// Display first image
			if ( ! empty( $image_url ) ) {
				$image_fragment .= "<img class='wdm_result_list_thumb' src='$image_url' />";
			}

			if ( empty( $content ) ) {
				// Set a default value for content if no highlighting returned.
				$post_to_show = get_post( $post_id );
				if ( isset( $post_to_show ) ) {
					// Excerpt first, or content.
					$content = ( ! empty( $post_to_show->post_excerpt ) ) ? $post_to_show->post_excerpt : $post_to_show->post_content;

					if ( isset( $ind_opt['is_shortcode_expanded'] ) && ( strpos( $content, '[solr_search_shortcode]' ) === false ) ) {

						// Expand shortcodes which have a plugin active, and are not the search form shortcode (else pb).
						global $post;
						$post    = $post_to_show;
						$content = do_shortcode( $content );
					}

					// Remove shortcodes tags remaining, but not their content.
					// strip_shortcodes() does nothing, probably because shortcodes from themes are not loaded in admin.
					// Credit: https://wordpress.org/support/topic/stripping-shortcodes-keeping-the-content.
					// Modified to enable "/" in attributes
					$content = preg_replace( "~(?:\[/?)[^\]]+/?\]~s", '', $content );  # strip shortcodes, keep shortcode content;


					// Strip HTML and PHP tags
					$content = strip_tags( $content );

					$solr_res_options = get_option( 'wdm_solr_res_data', array() );
					if ( isset( $solr_res_options['highlighting_fragsize'] ) && is_numeric( $solr_res_options['highlighting_fragsize'] ) ) {
						// Cut content at the max length defined in options.
						$content = substr( $content, 0, $solr_res_options['highlighting_fragsize'] );
					}
				}
			}

			$content = mb_convert_encoding($content, 'UTF-8', 'auto');
			// Format content text a little bit
			$content = str_replace( '&nbsp;', '', $content );
			$content = str_replace( '  ', ' ', $content );
			$content = ucfirst( trim( $content ) );
			$content .= '...';

			$msg .= "<div class='p_content'>$image_fragment $content</div>";
			if ( $comm_no === 1 ) {
				$comment_link_title = OptionLocalization::get_term( $localization_options, 'results_row_comment_link_title' );
				$msg .= "<div class='p_comment'>$comments<a href='$url'>$comment_link_title</a></div>";
			}

			// Groups bloc - Bottom right
			$wpsolr_groups_message = apply_filters( WpSolrFilters::WPSOLR_FILTER_SOLR_RESULTS_DOCUMENT_GROUPS_INFOS, get_current_user_id(), $document );
			if ( isset( $wpsolr_groups_message ) ) {

				// Display groups of this user which owns at least one the document capability
				$message = $wpsolr_groups_message['message'];
				$msg .= "<div class='p_misc'>$message";
				$msg .= "</div>";
				$msg .= '<br/>';

			}

			// Informative bloc - Bottom right
			$msg .= "<div class='p_misc'>";
			$msg .= "<span class='pauthor'>" . sprintf( OptionLocalization::get_term( $localization_options, 'results_row_by_author' ), $auth ) . "</span>";
			$msg .= empty( $cat ) ? "" : "<span class='pcat'>" . sprintf( OptionLocalization::get_term( $localization_options, 'results_row_in_category' ), $cat ) . "</span>";
			$msg .= "<span class='pdate'>" . sprintf( OptionLocalization::get_term( $localization_options, 'results_row_on_date' ), $date ) . "</span>";
			$msg .= empty( $no_comments ) ? "" : "<span class='pcat'>" . sprintf( OptionLocalization::get_term( $localization_options, 'results_row_number_comments' ), $no_comments ) . "</span>";
			$msg .= "</div>";

			// End of snippet bloc
			$msg .= "</div><hr>";

			array_push( $results, $msg );
			$i = $i + 1;
		}
		//  $msg.='</div>';


		if ( count( $results ) < 0 ) {
			$search_result[] = 0;
		} else {
			$search_result[] = $results;
		}

		$fir = $wpsolr_query->get_start() + 1;

		$last = $wpsolr_query->get_start() + $wpsolr_query->get_nb_results_by_page();
		if ( $last > $found ) {
			$last = $found;
		}

		if ( WPSOLR_Global::getOption()->get_search_is_infinitescroll() ) {

			$information_header = sprintf( OptionLocalization::get_term( $localization_options, 'infinitescroll_results_header_pagination_numbers' ), $found );

		} else {

			$information_header = sprintf( OptionLocalization::get_term( $localization_options, 'results_header_pagination_numbers' ), $fir, $last, $found );
		}

		$search_result[] = "<span class='infor'>" . $information_header . "</span>";


		return $search_result;
	}


	/**
	 * Add facet fields to the solarium query
	 *
	 * @param Query $solarium_query
	 * @param array $field_names
	 * @param int $max_nb_items_by_facet Maximum items by facet
	 * @param int $min_count_by_facet Do not return facet elements with less than this minimum count
	 */
	public function add_facet_fields(
		Query $solarium_query,
		$facets_parameters
	) {

		// Field names
		$field_names = isset( $facets_parameters[ self::PARAMETER_FACET_FIELD_NAMES ] )
			? $facets_parameters[ self::PARAMETER_FACET_FIELD_NAMES ]
			: array();

		// Limit
		$limit = isset( $facets_parameters[ self::PARAMETER_FACET_LIMIT ] )
			? $facets_parameters[ self::PARAMETER_FACET_LIMIT ]
			: self::DEFAULT_MAX_NB_ITEMS_BY_FACET;

		// Min count
		$min_count = isset( $facets_parameters[ self::PARAMETER_FACET_MIN_COUNT ] )
			? $facets_parameters[ self::PARAMETER_FACET_MIN_COUNT ]
			: self::DEFAULT_MIN_COUNT_BY_FACET;


		if ( count( $field_names ) ) {

			$facetSet = $solarium_query->getFacetSet();

			// Only display facets that contain data
			$facetSet->setMinCount( $min_count );

			foreach ( $field_names as $facet ) {

				$fact = $this->get_facet_hierarchy_name( WpSolrSchema::_FIELD_NAME_FLAT_HIERARCHY, $facet );

				// Add the facet
				$facetSet->createFacetField( array(
					'exclude' => $facet,
					'key' => "$fact"
				) )->setField( "$fact" );

				if ( ! empty( $limit ) ) {

					$facetSet->setLimit( $limit );
				}
			}
		}

	}

	/**
	 * Add highlighting fields to the solarium query
	 *
	 * @param Query $solarium_query
	 * @param array $highlighting_parameters
	 */
	public
	function add_highlighting_fields(
		Query $solarium_query,
		$highlighting_parameters
	) {

		if ( $this->is_query_wildcard ) {
			// Wilcard queries does not need highlighting.
			return;
		}

		// Field names
		$field_names = isset( $highlighting_parameters[ self::PARAMETER_HIGHLIGHTING_FIELD_NAMES ] )
			? $highlighting_parameters[ self::PARAMETER_HIGHLIGHTING_FIELD_NAMES ]
			: array(
				WpSolrSchema::_FIELD_NAME_TITLE,
				WpSolrSchema::_FIELD_NAME_CONTENT,
				WpSolrSchema::_FIELD_NAME_COMMENTS
			);

		// Fragment size
		$fragment_size = isset( $highlighting_parameters[ self::PARAMETER_HIGHLIGHTING_FRAGMENT_SIZE ] )
			? $highlighting_parameters[ self::PARAMETER_HIGHLIGHTING_FRAGMENT_SIZE ]
			: self::DEFAULT_HIGHLIGHTING_FRAGMENT_SIZE;

		// Prefix
		$prefix = isset( $highlighting_parameters[ self::PARAMETER_HIGHLIGHTING_PREFIX ] )
			? $highlighting_parameters[ self::PARAMETER_HIGHLIGHTING_PREFIX ]
			: self::DEFAULT_HIGHLIGHTING_PREFIX;

		// Postfix
		$postfix = isset( $highlighting_parameters[ self::PARAMETER_HIGHLIGHTING_POSTFIX ] )
			? $highlighting_parameters[ self::PARAMETER_HIGHLIGHTING_POSTFIX ]
			: self::DEFAULT_HIGHLIGHTING_POSFIX;

		$highlighting = $solarium_query->getHighlighting();

		foreach ( $field_names as $field_name ) {

			$highlighting->getField( $field_name )->setSimplePrefix( $prefix )->setSimplePostfix( $postfix );

			// Max size of each highlighting fragment for post content
			$highlighting->getField( $field_name )->setFragSize( $fragment_size );
		}

	}

	/**
	 * Ping the Solr index
	 */
	public
	function ping() {

		return $this->solarium_client->ping( $this->solarium_client->createPing() );
	}

	/**
	 * Add filter query fields to the solarium query
	 *
	 * @param Query $solarium_query
	 * @param array $filter_query_fields
	 */
	private
	function add_filter_query_fields(
		Query $solarium_query, $filter_query_fields = array()
	) {

		if ( $this->is_galaxy_slave ) {
			// Filter results by the slave filter
			array_push( $filter_query_fields, sprintf( '%s:%s', WpSolrSchema::_FIELD_NAME_BLOG_NAME_STR, $this->galaxy_slave_filter_value ) );
		}

		if ( $filter_query_fields != null ) {

			$query_fields = array();

			foreach ($filter_query_fields as $fq ) {

				$fq = explode( ':', $fq );
				$name  = strtolower( $fq[0] );
				$value = isset( $fq[1] ) ?
					$this->escape_solr_special_catacters( $fq[1] ) : '';

				if ( trim( $name ) === '' | trim($value) === '' ) {
					continue;
				}

				$this->get_facet_hierarchy_name(
					WpSolrSchema::_FIELD_NAME_NON_FLAT_HIERARCHY, $name );

				$query_fields[$name][] = '"' . $value . '"';

			}

			if ($query_fields != null) {

				foreach ( $query_fields as $field => $values ) {

					$solarium_query->addFilterQuery( array(
						'tag'   => $field,
						'key'   => $field,
						'query' => $field . ':(' . implode(' OR ', $values) . ')'
					) );

				}

			}

		}

	}

	/**
	 * Escape Solr special caracters
	 *
	 * @param string $string_to_escape String to escape
	 *
	 * @return mixed
	 */
	function escape_solr_special_catacters( $string_to_escape ) {

		$result = $string_to_escape;

		// Special characters and their escape characters. Add more in the array if necessary.
		$special_characters = array(
			'"' => '\"', // The double quote sends a nasty syntax error in Solr 5/6
		);

		// Caracters never found in any string to escape
		$unique_caracter = 'WPSOLR_MARK_THIS_CARACTERS';

		foreach ( $special_characters as $special_character => $special_character_escaped ) {

			$result = str_replace( $special_character_escaped, $unique_caracter, $string_to_escape ); // do not escape already escaped characters: replace them by a unique character
			$result = str_replace( $special_character, $special_character_escaped, $result ); // Here it is: escape special character
			$result = str_replace( $unique_caracter, $special_character_escaped, $result ); // Replace back already escaped characters
		}

		return $result;
	}

	/**
	 * Add sort field to the solarium query
	 *
	 * @param Query $solarium_query
	 * @param string $sort_field_name
	 */
	private
	function add_sort_field(
		Query $solarium_query, $sort_field_name = self::SORT_CODE_BY_RELEVANCY_DESC
	) {

		switch ( $sort_field_name ) {

			case self::SORT_CODE_BY_DATE_DESC:
				$solarium_query->addSort( WpSolrSchema::_FIELD_NAME_DATE, $solarium_query::SORT_DESC );
				break;

			case self::SORT_CODE_BY_DATE_ASC:
				$solarium_query->addSort( WpSolrSchema::_FIELD_NAME_DATE, $solarium_query::SORT_ASC );
				break;

			case self::SORT_CODE_BY_NUMBER_COMMENTS_DESC:
				$solarium_query->addSort( WpSolrSchema::_FIELD_NAME_NUMBER_OF_COMMENTS, $solarium_query::SORT_DESC );
				break;

			case self::SORT_CODE_BY_NUMBER_COMMENTS_ASC:
				$solarium_query->addSort( WpSolrSchema::_FIELD_NAME_NUMBER_OF_COMMENTS, $solarium_query::SORT_ASC );
				break;

			case self::SORT_CODE_BY_RELEVANCY_DESC:
			default:
				// None is relevancy by default
				break;

		}

		// Let a chance to add custom sort options
		$solarium_query = apply_filters( WpSolrFilters::WPSOLR_FILTER_SORT, $solarium_query, $sort_field_name );
	}

	/**
	 * Set fields returned by the query.
	 * We do not ask for 'content', because it can be huge for attachments, and is anyway replaced by highlighting.
	 *
	 * @param Query $solarium_query
	 * @param array $field_names
	 */
	private
	function add_fields(
		Query $solarium_query
	) {

		// We add '*' to dynamic fields, else they are not returned by Solr (Solr bug ?)
		$solarium_query->setFields(
			array(
				WpSolrSchema::_FIELD_NAME_ID,
				WpSolrSchema::_FIELD_NAME_PID,
				WpSolrSchema::_FIELD_NAME_TITLE,
				WpSolrSchema::_FIELD_NAME_NUMBER_OF_COMMENTS,
				WpSolrSchema::_FIELD_NAME_COMMENTS,
				WpSolrSchema::_FIELD_NAME_DISPLAY_DATE,
				WpSolrSchema::_FIELD_NAME_DISPLAY_MODIFIED,
				'*' . WpSolrSchema::_FIELD_NAME_CATEGORIES_STR,
				WpSolrSchema::_FIELD_NAME_AUTHOR,
				'*' . WpSolrSchema::_FIELD_NAME_POST_THUMBNAIL_HREF_STR,
				'*' . WpSolrSchema::_FIELD_NAME_POST_HREF_STR,
			)
		);
	}

	/**
	 * Set the query keywords.
	 *
	 * @param Query $solarium_query
	 * @param string $keywords
	 */
	private
	function set_keywords(
		Query $solarium_query, $keywords
	) {

		$query_field_name = '';

		$keywords = trim( $keywords );

		if ( ! WPSOLR_Global::getOption()->get_search_fields_is_active() ) {

			// No search fields selected, use the default search field
			$query_field_name = WpSolrSchema::_FIELD_NAME_DEFAULT_QUERY . ':';

		} else {

			/// Use search fields with their boost defined in qf instead of default field 'text'
			$query_fields_str = $this->get_query_fields();
			if ( ! empty( $query_fields_str ) ) {

				$solarium_query->getEDisMax()->setQueryFields( $query_fields_str );
			}

			/// Add boosts on field values
			$query_boosts_fields_str = $this->get_query_boosts_fields();
			if ( ! empty( $query_boosts_fields_str ) ) {

				$solarium_query->getEDisMax()->setBoostQuery( $query_boosts_fields_str );
			}

		}


		if ( WPSOLR_Global::getOption()->get_search_is_partial_matches() ) {

			// Add '*' to each world of the query string.
			// 'word1  word2 ' => 'word1*  word2* '
			$keywords1 = preg_replace( '/(\S+)/i', '$1*', $keywords );

			if ( $keywords1 === ( $keywords . '*' ) ) {
				// then use 'OR' to ensure results include the exact keywords also (not only beginning with keywords) if there is one word only

				$keywords = $keywords . ' OR ' . $keywords1;

			} else {

				$keywords = $keywords1;
			}

			$solarium_query->setQuery( $query_field_name . ! empty( $keywords ) ? $keywords : '*' );

		} elseif ( WPSOLR_Global::getOption()->get_search_is_fuzzy_matches() ) {

			$keywords = preg_replace( '/(\S+)/i', '$1~', $keywords );

		}

		$this->is_query_wildcard = ( empty( $keywords ) || ( '*' === $keywords ) );

		// Escape Solr special caracters
		$keywords = $this->escape_solr_special_catacters( $keywords );

		$solarium_query->setQuery( $query_field_name . ( ! $this->is_query_wildcard ? $keywords : '*' ) );
	}


	/**
	 * Build a query fields with boosts
	 *
	 * @return string
	 */
	private function get_query_fields() {

		$option_search_fields_boosts = WPSOLR_Global::getOption()->get_search_fields_boosts();


		// Build a query fields with boosts
		$query_fields_str = '';
		foreach ( $option_search_fields_boosts as $search_field_name => $search_field_boost ) {

			if ( WpSolrSchema::_FIELD_NAME_CATEGORIES === $search_field_name ) {

				// Field 'categories' are now treated as other fields (dynamic string type)
				$search_field_name = WpSolrSchema::_FIELD_NAME_CATEGORIES_STR;
			}

			if ( '1' === $search_field_boost ) {

				// Boost of '1' is a default value. No need to add it with it's field.
				$query_fields_str .= sprintf( ' %s ', $search_field_name );

			} else {

				// Add field and it's (non default) boost value.
				$query_fields_str .= sprintf( ' %s^%s ', $search_field_name, $search_field_boost );
			}
		}

		$query_fields_str = trim( $query_fields_str );

		return $query_fields_str;
	}

	/**
	 * Build a query with boosts values
	 *
	 * @return string
	 */
	private function get_query_boosts_fields() {

		$option_search_fields_terms_boosts = WPSOLR_Global::getOption()->get_search_fields_terms_boosts();


		$query_boost_str = '';
		foreach ( $option_search_fields_terms_boosts as $search_field_name => $search_field_term_boost_lines ) {

			$search_field_term_boost_lines = trim( $search_field_term_boost_lines );

			if ( ! empty( $search_field_term_boost_lines ) ) {

				if ( WpSolrSchema::_FIELD_NAME_CATEGORIES === $search_field_name ) {

					// Field 'categories' are now treated as other fields (dynamic string type)
					$search_field_name = WpSolrSchema::_FIELD_NAME_CATEGORIES_STR;
				}

				foreach ( preg_split( "/(\r\n|\n|\r)/", $search_field_term_boost_lines ) as $search_field_term_boost_line ) {

					// Transform apache solr^2 in "apache solr"^2
					$search_field_term_boost_line = preg_replace( "/(.*)\^(.*)/", '"$1"^$2', $search_field_term_boost_line );

					// Add field and it's boost term value.
					$query_boost_str .= sprintf( ' %s:%s ', $search_field_name, $search_field_term_boost_line );
				}

			}
		}

		$query_boost_str = trim( $query_boost_str );

		return $query_boost_str;
	}

	/**
	 * Does a facet has to be shown as a hierarchy
	 *
	 * @param $facet_name
	 *
	 * @return bool
	 */
	private function is_facet_to_show_as_a_hierarchy( $facet_name ) {

		$facets_to_show_as_a_hierarchy = WPSOLR_Global::getOption()->get_facets_to_show_as_hierarchy();

		return ! empty( $facets_to_show_as_a_hierarchy ) && ! empty( $facets_to_show_as_a_hierarchy[ $facet_name ] );
	}

	/**
	 * Get a facet name if it's hierarchy (or not)
	 *
	 * @param $facet_name
	 *
	 * @return string Facet name with hierarch or not
	 */
	private function get_facet_hierarchy_name( $hierarchy_field_name, $facet_name ) {

		$facet_name   = strtolower( str_replace( ' ', '_', $facet_name ) );
		$is_hierarchy = $this->is_facet_to_show_as_a_hierarchy( $facet_name );

		if ( WpSolrSchema::_FIELD_NAME_CATEGORIES === $facet_name ) {

			// Field 'categories' are now treated as other fields (dynamic string type)
			$facet_name = WpSolrSchema::_FIELD_NAME_CATEGORIES_STR;
		}

		$result = $is_hierarchy ? sprintf( $hierarchy_field_name, $facet_name ) : $facet_name;

		return $result;
	}

	/**
	 * Retrieve a post thumbnail, from local database, or from the index content.
	 *
	 * @param \Solarium\QueryType\Select\Result\Document $document Solarium document
	 * @param $post_id
	 *
	 * @return array|false
	 */
	private function get_post_thumbnail( $document, $post_id ) {

		if ( $this->is_galaxy_master ) {

			// Master sites must get thumbnails from the index, as the $post_id is not in local database
			$results = $document->post_thumbnail_href_str;

		} else {

			// $post_id is in local database, use the standard way
			$results = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ) );
		}

		return ! empty( $results ) ? $results[0] : null;
	}

	/**
	 * Retrieve a post url, from local database, or from the index content.
	 *
	 * @param \Solarium\QueryType\Select\Result\Document $document Solarium document
	 * @param $post_id
	 *
	 * @return string
	 */
	private function get_post_url( $document, $post_id ) {

		if ( $this->is_galaxy_master ) {

			// Master sites must get thumbnails from the index, as the $post_id is not in local database
			$result = ! empty( $document->post_href_str ) ? $document->post_href_str[0] : null;

		} else {

			// $post_id is in local database, use the standard way
			$result = get_permalink( $post_id );
		}

		return $result;
	}

	/**
	 * Return posts from Solr results post PIDs
	 *
	 * @param $posts_ids
	 *
	 * @return WP_Post[]
	 */
	public function get_posts_from_pids() {

		if ( $this->solarium_results->getNumFound() === 0 ) {
			return array();
		}

		// Fetch all posts from the documents ids, in ONE call.
		if ( ! $this->is_galaxy_master ) {
			// Local search: return posts from local database

			$posts_ids = array();
			foreach ( $this->solarium_results as $document ) {
				array_push( $posts_ids, $document->PID );
			}

			if ( empty( $posts_ids ) ) {
				return array();
			}

			return get_posts( array(
				'numberposts' => count( $posts_ids ),
				'post_type'   => WPSOLR_Global::getOption()->get_option_index_post_types(),
				'post_status' => 'any',
				'post__in'    => $posts_ids,
				'orderby'     => 'post__in',
				// Get posts in same order as documents in Solr results.
			) );

		}

		// Create pseudo posts from Solr results
		$results = array();
		foreach ( $this->solarium_results as $document ) {

			unset( $current_post );
			$current_post         = new stdClass();
			$current_post->ID     = $document->id;
			$current_post->filter = 'raw';

			$wp_post = new WP_Post( $current_post );

			array_push( $results, $wp_post );
		}

		return $results;
	}

}
