<?php

// Load WPML class for inheritance
WpSolrExtensions::require_once_wpsolr_extension( WpSolrExtensions::EXTENSION_WPML, true );

/**
 * Class PluginqTranslateX
 *
 * Manage qTranslateX plugin
 * @link https://qtranslatexteam.wordpress.com/interface/
 */
class PluginQTranslateX extends PluginWpml {

	const _PLUGIN_NAME_IN_MESSAGES = 'qTranslateX';

	/*
	 * qTranslateX database constants
	 */
	const TABLE_TERM_RELATION_SHIPS = "term_relationships";


	// qTranslateX options
	const _OPTIONS_NAME = 'wdm_solr_extension_qtranslatex_data';

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

		parent::__construct();

		/*
		add_filter( WpSolrFilters::WPSOLR_FILTER_SEARCH_PAGE_SLUG, array(
			$this,
			'get_search_page_slug',
		), 10, 1 );
		*/


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

		// Get the languages
		$languages = $this->get_languages();

		// Retrieve the term_id used for this language code
		if ( ! isset( $languages[ $language ]['term_id'] ) ) {
			throw new ErrorException( sprintf( "The language '%s' is undefined in %s (not in the taxonomy terms).", $language, static::_PLUGIN_NAME_IN_MESSAGES ) );
		}
		$language_term_id = $languages[ $language ]['term_id'];

		if ( isset( $language ) ) {

			// Join statement
			$sql_joint_statement = ' JOIN ';
			$sql_joint_statement .= $wpdb->prefix . self::TABLE_TERM_RELATION_SHIPS . ' AS ' . 'wp_term_relationships';
			$sql_joint_statement .= " ON posts.ID = wp_term_relationships.object_id AND wp_term_relationships.term_taxonomy_id = '%s' ";

			$sql_statements['JOIN'] = sprintf( $sql_joint_statement, $language_term_id );
		}

		return $sql_statements;
	}

	/**
	 * Get current language code
	 *
	 * @return string Current language code
	 */
	function get_current_language_code() {

		return qtranxf_getLanguage();

	}

	/**
	 * Get default language code
	 *
	 * @return string Default language code
	 */
	function get_default_language_code() {

		return qtranxf_getLanguageDefault();

	}

	/**
	 * Get the language of a post
	 *
	 * @return string Post language code
	 */
	function filter_get_post_language( $language_code, $post ) {

		$post_language = isset( $post ) ? qtranpo( $post->ID, 'slug' ) : null;

		return $post_language;
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

		// Retrieve qTranslateX active languages
		$languages = qtranxf_getSortedLanguages();

		// Fill the result
		if ( ! empty( $languages ) ) {
			foreach ( $languages as $language ) {

				$result[ $language ] = array(
					'language_code' => $language,
					'active'        => true
				);

			}
		}


		return $result;
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
		global $qtranslatex;

		$current_language_code = $this->get_current_language_code();

		// Get search page in current language
		$default_search_page_id_translated = pll_get_post( $default_search_page_id, $current_language_code );

		if ( ! $default_search_page_id_translated ) {

			// Create a new search page for the translation
			$default_search_page = WPSolrSearchSolrClient::create_default_search_page();

			// Retrieve current search page translations
			$translations = $qtranslatex->model->get_translations( 'post', $default_search_page_id );

			// Add current translation to translations
			$translations[ $current_language_code ] = $default_search_page->ID;

			// Save translations
			pll_save_post_translations( $translations );

		}

		$result = ( $default_search_page_id === $default_search_page_id_translated ) ? $default_search_page_url : get_permalink( $default_search_page_id_translated );

		return $result;
	}

	function get_search_page_slug( $slug = null ) {

		// POLYLANG cannot accept 2 pages with the same slug.
		// So, add the language to the slug.
		return WPSolrSearchSolrClient::_SEARCH_PAGE_SLUG . "-" . $this->get_current_language_code();
	}

}