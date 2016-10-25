<?php

/**
 * Class PluginTypes
 *
 * Manage "Types" plugin (Custom fields)
 * @link https://wordpress.org/plugins/types/
 */
class PluginTypes extends WpSolrExtensions {

	// Prefix of TYPES custom fields
	const CONST_TYPES_FIELD_PREFIX = 'wpcf-';

	// Polylang options
	const _OPTIONS_NAME = 'wdm_solr_extension_types_data';

	// Options
	private $_options;


	/**
	 * Factory
	 *
	 * @return PluginTypes
	 */
	static function create() {

		return new self();
	}

	/*
	 * Constructor
	 * Subscribe to actions
	 */

	function __construct() {

		$this->_options = self::get_option_data( self::EXTENSION_TYPES );

		add_filter( WpSolrFilters::WPSOLR_FILTER_SEARCH_PAGE_FACET_NAME, array(
			$this,
			'get_field_label'
		), 10, 1 );


	}


	/**
	 * Get the TYPES field label from the custom field name.
	 *
	 * @param $custom_field_name
	 *
	 * @return mixed
	 */
	public
	function get_field_label(
		$custom_field_name
	) {

		$result = $custom_field_name;

		if ( ! isset( $this->_options['display_types_label_on_facet'] ) || ! ( self::CONST_TYPES_FIELD_PREFIX === substr( $custom_field_name, 0, strlen( self::CONST_TYPES_FIELD_PREFIX ) ) ) ) {
			// No need to replace custom field name by types field label
			return $result;
		}


		$custom_field_name_without_prefix = substr( $custom_field_name, strlen( self::CONST_TYPES_FIELD_PREFIX ) );
		$field                            = wpcf_fields_get_field_by_slug( $custom_field_name_without_prefix );

		// Retrieve field among TYPES fields
		if ( isset( $field ) ) {
			$result = $field['name'];
		}

		return $result;
	}

}