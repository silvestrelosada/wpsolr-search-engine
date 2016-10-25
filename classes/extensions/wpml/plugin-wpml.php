<?php

/**
 * Class PluginWpml
 *
 * Manage WPML plugin
 * @link http://www.wpml.org/
 */
class PluginWpml extends WpSolrExtensions {

	const _PLUGIN_NAME_IN_MESSAGES = 'WPML';

	/*
	 * WPML database constants
	 */
	const TABLE_ICL_TRANSLATIONS = "icl_translations";

	// WPML languages
	private $languages;

	// WPML options
	const _OPTIONS_NAME = 'wdm_solr_extension_wpml_data';
	const _OPTIONS_INDEX_INDICE = 'solr_index_indice';
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
		$this->options = get_option( static::_OPTIONS_NAME );

		// Retrieve the active languages
		$this->languages = $this->get_languages();

		/*
		 * Filters and actions
		 */

		add_filter( WpSolrFilters::WPSOLR_FILTER_SQL_QUERY_STATEMENT, array(
			$this,
			'set_sql_query_statement',
		), 10, 2 );

		add_filter( WpSolrFilters::WPSOLR_FILTER_SEARCH_GET_DEFAULT_SOLR_INDEX_INDICE, array(
			$this,
			'get_default_solr_index_indice',
		), 10, 2 );


		add_filter( WpSolrFilters::WPSOLR_FILTER_SEARCH_PAGE_URL, array(
			$this,
			'set_search_page_url',
		), 10, 2 );


		add_filter( WpSolrFilters::WPSOLR_FILTER_POST_LANGUAGE, array(
			$this,
			'filter_get_post_language',
		), 10, 2 );


		add_action( WpSolrFilters::ACTION_TRANSLATION_REGISTER_STRINGS, array(
			$this,
			'register_translation_strings',
		), 10, 1 );

		add_filter( WpSolrFilters::WPSOLR_FILTER_TRANSLATION_STRING, array(
			$this,
			'get_translation_string',
		), 10, 2 );

	}


	/**
	 * Set admin notice when some languages are not configured with a Solr index
	 */
	static function set_admin_notice() {

		if ( ! self::each_language_has_a_one_solr_index_search() ) {
			set_transient( get_current_user_id() . 'wpsolr_some_languages_have_no_solr_index_admin_notice',
				sprintf( "Each %s language should have it's own unique Solr index. Search results will return mixed content from the languages with the same Solr index.",
					static::_PLUGIN_NAME_IN_MESSAGES )
			);
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

		// Retrieve current document language code
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

		return isset( $this->options[ self::_OPTIONS_INDEX_INDICE ] ) ? $this->options[ self::_OPTIONS_INDEX_INDICE ] : null;;
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
	 * Get the language of a post
	 *
	 * @return string Post language code
	 */
	function filter_get_post_language( $language_code, $post ) {

		$post_language_details = isset( $post ) ? apply_filters( 'wpml_post_language_details', null, $post->ID ) : null;

		return ( isset( $post_language_details ) && isset( $post_language_details['language_code'] ) ) ? $post_language_details['language_code'] : null;
	}

	/**
	 * Get the Solr index search for the language / current language
	 *
	 * @param $language_code
	 *
	 * @return string Solr index indice
	 * @throws Exception
	 */
	function get_default_solr_index_indice( $solr_index_indice, $language_code ) {

		$current_language_code = isset( $language_code ) ? $language_code : $this->get_current_language_code();
		$solr_indexes          = $this->get_solr_index_indices();
		if ( ! isset( $solr_indexes ) ) {
			// Languages not yet related to any Solr index search.
			throw new Exception( sprintf( 'WPSOLR %s extension is activated, but not configured to match languages and Solr indexes.', static::_PLUGIN_NAME_IN_MESSAGES ) );
		}

		foreach ( $solr_indexes as $solr_index_indice => $solr_index ) {

			if ( isset( $solr_index['is_default_search'] ) && isset( $solr_index['indexing_language_code'] ) && ( $solr_index['indexing_language_code'] === $current_language_code ) ) {

				// Is language a valid one ?
				if ( ! $this->is_language_code( $solr_index['indexing_language_code'] ) ) {
					throw new Exception( sprintf( "WPSOLR %s extension is activated, but current language '%s' is not an active language.", static::_PLUGIN_NAME_IN_MESSAGES, $current_language_code ) );
				}

				// The winner: valid index indice which is default search for current language
				return $solr_index_indice;

			}
		}

		throw new Exception( sprintf( "WPSOLR %s extension is activated, but current language '%s' has no search Solr index.", static::_PLUGIN_NAME_IN_MESSAGES, $current_language_code ) );
	}

	/**
	 * Register translation strings to translatable strings
	 *
	 * @param $parameters ["translations" => [ ["domain" => "wpsolr facel label", "name" => "categories", "text" => "my categories"]
	 */
	function register_translation_strings( $parameters ) {

		foreach ( $parameters['translations'] as $text_to_add ) {

			do_action( 'wpml_register_single_string', $text_to_add['domain'], $text_to_add['name'], $text_to_add['text'] );
		}

		return;
	}

	/**
	 * Add translation strings to translatable strings
	 *
	 * @param array $parameter ["domain" => "wpsolr facel label", "name" => "categories", "text" => "my categories"]
	 */
	function get_translation_string( $string, $parameter ) {

		$result = apply_filters( 'wpml_translate_single_string', $parameter['text'], $parameter['domain'], $parameter['name'], ! empty( $parameter['language'] ) ? $parameter['language'] : null );

		return $result;
	}

}