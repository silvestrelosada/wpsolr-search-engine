<?php

/**
 * Class PluginWpml
 *
 * Manage WPML plugin
 * @link http://www.wpml.org/
 */
class PluginWpml extends WpSolrExtensions {

	/*
	 * WPML database constants
	 */
	const TABLE_ICL_TRANSLATIONS = "icl_translations";

	// WPML languages
	private $languages;

	// WPML options
	const WPML_OPTIONS_NAME = 'wdm_solr_extension_wpml_data';
	const WPML_OPTIONS_INDEX_INDICE = 'solr_index_indice';
	private $options;

	/**
	 * Factory
	 *
	 * @return PluginWpml
	 */
	static function create() {

		return new self();
	}

	/*
	 * Constructor
	 * Subscribe to actions
	 */

	function __construct() {

		// Load the options
		$this->options = get_option( self::WPML_OPTIONS_NAME );

		// Retrieve the active languages
		$this->languages = $this->get_languages();

		/*
		 * Filters and actions
		 */

		/*

		add_filter( WpSolrFilters::WPSOLR_FILTER_SOLARIUM_DOCUMENT_FOR_UPDATE, array(
			$this,
			'add_language_fields_to_document_for_update',
		), 10, 4 );


		add_action( WpSolrFilters::WPSOLR_ACTION_SOLARIUM_QUERY, array( $this, 'set_query_keywords' ), 10, 1 );

		*/

		add_filter( WpSolrFilters::WPSOLR_FILTER_SQL_QUERY_STATEMENT, array(
			$this,
			'set_sql_query_statement',
		), 10, 2 );

		add_filter( WpSolrFilters::WPSOLR_FILTER_SEARCH_GET_DEFAULT_SOLR_INDEX_INDICE, array(
			$this,
			'get_default_solr_index_indice',
		), 10, 1 );


		add_filter( WpSolrFilters::WPSOLR_FILTER_SEARCH_PAGE_URL, array(
			$this,
			'set_search_page_url',
		), 10, 2 );

		// Display admin notice in admin
		//self::set_admin_notice();
	}


	/**
	 * Set admin notice when some languages are not configured with a Solr index
	 */
	static function set_admin_notice() {

		if ( ! self::each_language_has_a_one_solr_index_search() ) {
			set_transient( get_current_user_id() . 'wpml_some_languages_have_no_solr_index_admin_notice', "Each WPML language should have it's own unique Solr index. Search results will return mixed content from the languages with the same Solr index." );
		}

	}


	/**
	 * Customize the sql query statements.
	 * Add a join with the current indexing language
	 *
	 * @param $sql_statements
	 *
	 * @return mixed
	 */
	function set_sql_query_statement( $sql_statements, $parameters ) {
		global $wpdb;

		// Get the index indexing language
		$language = $this->get_solr_index_indexing_language( $parameters['index_indice'] );

		if ( isset( $language ) ) {

			// Join statement
			$sql_joint_statement = ' JOIN ';
			$sql_joint_statement .= $wpdb->prefix . self::TABLE_ICL_TRANSLATIONS . ' AS ' . 'icl_translations';
			$sql_joint_statement .= " ON posts.ID = icl_translations.element_id AND icl_translations.element_type = CONCAT('post_', posts.post_type) AND icl_translations.language_code = '%s' ";

			$sql_statements['JOIN'] = sprintf( $sql_joint_statement, $language );
		}

		return $sql_statements;
	}

	/**
	 * Add multi-language fields to a Solarium document
	 *
	 * @param $solarium_document_for_update
	 * @param $solr_indexing_options
	 * @param $post
	 * @param $attachment_body
	 *
	 * @return object Solarium document updated with multi-language fields
	 */
	function add_language_fields_to_document_for_update( $solarium_document_for_update, $solr_indexing_options, $post, $attachment_body ) {

		// Retrieve current document language code from WPML
		$args               = array(
			'element_id'   => $solarium_document_for_update->id,
			'element_type' => $solarium_document_for_update->type,
		);
		$post_language_code = apply_filters( 'wpml_element_language_code', null, $args );

		if ( ! is_null( $post_language_code ) ) {
			// Now, just add the language fields

			// Language field
			$solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_LANGUAGE_CODE ] = $post_language_code;

			// Add fields for the language code
			foreach ( WpSolrSchema::$multi_language_fields as $field_definitions ) {
				// Create the dynamic field name
				$field_name = self::create_field_name_for_language_code( $field_definitions['field_name'], $post_language_code, $field_definitions['field_extension'] );
				// Add the dynamic field name to the Solarium document
				$solarium_document_for_update->$field_name = $solarium_document_for_update[ $field_definitions['field_name'] ];
			}

		}

		return $solarium_document_for_update;
	}


	/**
	 * Get current language code
	 *
	 * @return string Current language code
	 */
	function get_current_language_code() {

		return apply_filters( 'wpml_current_language', null );

	}

	/**
	 * Get default language code
	 *
	 * @return string Default language code
	 */
	function get_default_language_code() {

		return apply_filters( 'wpml_default_language', null );

	}

	/**
	 * Is the language code part of the languages ?
	 *
	 * @param $language_code
	 *
	 * @return bool
	 */
	function is_language_code( $language_code ) {

		$result = array_key_exists( $language_code, $this->get_languages() );

		return $result;
	}

	/**
	 * Get active language codes
	 *
	 * @return array Language codes
	 */
	function get_languages() {

		/*
		if ( isset( $this->languages ) ) {
			// Use value
			return $this->languages;
		}*/

		$result = array();

		// Retrieve WPML active languages
		$languages = apply_filters( 'wpml_active_languages', null, 'orderby=id&order=desc' );

		// Fill the result
		if ( ! empty( $languages ) ) {
			foreach ( $languages as $language ) {

				$result[ $language['code'] ] = array(
					'language_code' => $language['code'],
					'active'        => $language['active'],
				);

			}
		}


		return $result;
	}

	/**
	 * Retrieve index indices
	 *
	 * @return mixed
	 */
	function get_solr_index_indices() {

		return $this->options[ self::WPML_OPTIONS_INDEX_INDICE ];
	}

	function get_solr_index_indexing_language( $solr_index_indice ) {

		$solr_indexes = $this->get_solr_index_indices();

		if ( ! isset( $solr_indexes ) || ! isset( $solr_indexes[ $solr_index_indice ] ) || ! isset( $solr_indexes[ $solr_index_indice ]['indexing_language_code'] ) || '' === $solr_indexes[ $solr_index_indice ]['indexing_language_code'] ) {
			return null;
		}

		return $solr_indexes[ $solr_index_indice ]['indexing_language_code'];

	}

	/**
	 * Verify that all languages are related to a unique Solr index for search.
	 *
	 * @return bool
	 */
	public function each_language_has_a_one_solr_index_search() {

		$solr_indexes = $this->get_solr_index_indices();
		if ( ! isset( $solr_indexes ) ) {
			// Languages not yet related to any Solr index search.
			return false;
		}

		$default_search_languages_already_found = array();
		foreach ( $solr_indexes as $solr_index_indice => $solr_index ) {

			if ( isset( $solr_index['is_default_search'] ) && isset( $solr_index['indexing_language_code'] ) ) {

				// Is language a valid one ?
				if ( ! $this->is_language_code( $solr_index['indexing_language_code'] ) ) {
					return false;
				}

				if ( $solr_index['indexing_language_code'] ) {
					if ( array_key_exists( $solr_index['indexing_language_code'], $default_search_languages_already_found ) ) {
						// We found this language as default search twice
						return false;
					}
				}

				// Add language to already found ones
				$default_search_languages_already_found[ $solr_index['indexing_language_code'] ] = '';
			}
		}

		return true;
	}

	/**
	 * Create a field name for a language code
	 * Example: title_en_t from title
	 *
	 * @param $field_name Field name
	 * @param $language_code Language code
	 * @param $solr_dynamic_type_post_fix Solr postfix dynamic type of the field name (_t, _s, _i, ...)
	 *
	 * @return string New field name
	 */
	function create_field_name_for_language_code( $field_name, $language_code, $solr_dynamic_type_post_fix ) {

		return $field_name . '_' . $language_code . $solr_dynamic_type_post_fix;
	}


	/**
	 *
	 * Replace default field by language specific fields in query
	 *
	 * @param $parameters array
	 *
	 */
	public function set_query_keywords( $parameters ) {

		$current_language_code = self::get_current_language_code();

		$query        = $parameters[ WpSolrFilters::WPSOLR_ACTION_SOLARIUM_QUERY__PARAM_SOLARIUM_QUERY ];
		$search_terms = $parameters[ WpSolrFilters::WPSOLR_ACTION_SOLARIUM_QUERY__PARAM_SEARCH_TERMS ];

		// Add multi-language fields to the query
		$query_string = '';
		foreach ( WpSolrSchema::$multi_language_fields as $field_definitions ) {
			// Create the dynamic field name
			$field_name = self::create_field_name_for_language_code( $field_definitions['field_name'], $current_language_code, $field_definitions['field_extension'] );
			// Add the dynamic field name to the query
			$query_string .= ( $query_string === '' ? '' : ' OR ' ) . $field_name . ':' . $search_terms;
		}

		$query->setQuery( $query_string === '' ? ( WpSolrSchema::_FIELD_NAME_DEFAULT_QUERY . ':' . $search_terms ) : $query_string );

	}


	/**
	 * Define the sarch page url for the current language
	 *
	 * @param $default_search_page_id
	 * @param $default_search_page_url
	 *
	 * @return string
	 */
	function set_search_page_url( $default_search_page_url, $default_search_page_id ) {

		$translated_search_page_url = apply_filters( 'wpml_permalink', $default_search_page_url, null );

		if ( is_null( apply_filters( 'wpml_object_id', $default_search_page_id, 'page', false ) ) ) {

			// Need to create the translated search page. Once only.
			do_action( 'wpml_make_post_duplicates', $default_search_page_id );

		}

		return $translated_search_page_url;
	}


	/**
	 * Get the Solr index search for the current language
	 *
	 * @return string Solr index indice
	 */
	function get_default_solr_index_indice() {

		$current_language_code = self::get_current_language_code();
		$solr_indexes          = $this->get_solr_index_indices();
		if ( ! isset( $solr_indexes ) ) {
			// Languages not yet related to any Solr index search.
			throw new Exception( sprintf( "WPSOLR WPML extension is activated, but not configured to match languages and Solr indexes.", $current_language_code ) );
		}

		$default_search_languages_already_found = array();
		foreach ( $solr_indexes as $solr_index_indice => $solr_index ) {

			if ( isset( $solr_index['is_default_search'] ) && isset( $solr_index['indexing_language_code'] ) && ( $solr_index['indexing_language_code'] === $current_language_code ) ) {

				// Is language a valid one ?
				if ( ! $this->is_language_code( $solr_index['indexing_language_code'] ) ) {
					throw new Exception( sprintf( "WPSOLR WPML extension is activated, but current language '%s' is not an active WPML language.", $current_language_code ) );
				}

				// The winner: valid index indice which is default search for current language
				return $solr_index_indice;

			}
		}

		throw new Exception( sprintf( "WPSOLR WPML extension is activated, but current language '%s' has no search Solr index.", $current_language_code ) );
	}
}