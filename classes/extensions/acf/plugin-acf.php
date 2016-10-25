<?php

/**
 * Class PluginAcf
 *
 * Manage Advanced Custom Fields (ACF) plugin
 * @link https://wordpress.org/plugins/advanced-custom-fields/
 */
class PluginAcf extends WpSolrExtensions {

	// Prefix of ACF fields
	const FIELD_PREFIX = '_';

	// Polylang options
	const _OPTIONS_NAME = 'wdm_solr_extension_acf_data';

	// acf fields indexed by name.
	private $_fields;

	// Options
	private $_options;

	// ACF types
	const ACF_TYPE_REPEATER = 'repeater';
	const ACF_TYPE_FILE = 'file';
	const ACF_TYPE_FILE_ID = 'id';
	const ACF_TYPE_FILE_OBJECT = 'object';
	const ACF_TYPE_FILE_URL = 'url';

	/**
	 * Factory
	 *
	 * @return PluginAcf
	 */
	static function create() {

		return new self();
	}

	/*
	 * Constructor
	 * Subscribe to actions
	 */

	/**
	 * PluginAcf constructor.
	 */
	function __construct() {

		$this->_options = self::get_option_data( self::EXTENSION_ACF );

		add_filter( WpSolrFilters::WPSOLR_FILTER_INDEX_CUSTOM_FIELDS, array(
			$this,
			'get_index_custom_fields'
		), 10, 1 );

		add_filter( WpSolrFilters::WPSOLR_FILTER_SEARCH_PAGE_FACET_NAME, array(
			$this,
			'get_field_label'
		), 10, 1 );

		add_filter( WpSolrFilters::WPSOLR_FILTER_POST_CUSTOM_FIELDS, array(
			$this,
			'filter_custom_fields'
		), 10, 2 );

		add_filter( WpSolrFilters::WPSOLR_FILTER_GET_POST_ATTACHMENTS, array(
			$this,
			'filter_get_post_attachments'
		), 10, 2 );

	}

	/**
	 * Retrieve all field keys of all ACF fields.
	 *
	 * @return array
	 */
	function get_acf_fields() {
		global $wpdb;

		// Uue cached fields if exist
		if ( isset( $this->_fields ) ) {
			return $this->_fields;
		}

		$fields = array();

		// Else create the cached fields
		$results = $wpdb->get_results( "SELECT distinct meta_key, meta_value
                                        FROM $wpdb->postmeta
                                        WHERE meta_key like '_%'
                                        AND   meta_value like 'field_%'" );

		$nb_results = count( $results );
		for ( $loop = 0; $loop < $nb_results; $loop ++ ) {
			$fields[ $results[ $loop ]->meta_key ] = $results[ $loop ]->meta_value;

		}

		// Save the cache
		$this->_fields = $fields;

		return $this->_fields;
	}


	/**
	 * Update custom fields list to be indexed
	 * Replace _groupRepeater_0_repeatedFieldName by repeatedFieldName
	 *
	 * @param string[] $custom_fields
	 *
	 * @return string[]
	 */
	function get_index_custom_fields( $custom_fields ) {

		if ( ! function_exists( 'acf_get_field' ) ) {
			throw new Exception( 'Your ACF plugin does not include the function \'acf_get_field\', we cannot format here the repeater fields. Perhaps an old ACF version ? (ACF Pro from 5.0.0 should be fine)' );
		}

		if ( ! isset( $custom_fields ) ) {
			$custom_fields = array();
		}

		$results = array();

		$fields = $this->get_acf_fields();

		foreach ( $custom_fields as $custom_field_name ) {

			$is_field_discarded = false;

			if ( isset( $fields[ self::FIELD_PREFIX . $custom_field_name ] ) || isset( $fields[ $custom_field_name ] ) ) {

				$field_key = isset( $fields[ self::FIELD_PREFIX . $custom_field_name ] ) ? $fields[ self::FIELD_PREFIX . $custom_field_name ] : $fields[ $custom_field_name ];
				$field     = get_field_object( $field_key, false, false, false );

				if ( $field ) {

					if ( self::ACF_TYPE_REPEATER === $field['type'] ) {

						// This field is a repeater container: do not keep it.
						$is_field_discarded = true;

					} else {

						$parent_field = acf_get_field( $field['parent'] );

						if ( $parent_field ) {

							if ( self::ACF_TYPE_REPEATER === $parent_field['type'] ) {

								$is_field_discarded = true;

								if ( ( self::ACF_TYPE_REPEATER === $parent_field['type'] ) && ! in_array( $field['name'], $results, true ) ) {
									array_push( $results, $field['name'] );
								}

							}
						}
					}

				}
			}

			if ( ! $is_field_discarded ) {
				array_push( $results, $custom_field_name );
			}


		}

		return $results;
	}

	/**
	 * Get the ACF field label from the custom field name.
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

		if ( ! isset( $this->_options['display_acf_label_on_facet'] ) ) {
			// No need to replace custom field name by acf field label
			return $result;
		}

		// Retrieve field among ACF fields
		$fields = $this->get_acf_fields();
		if ( isset( $fields[ self::FIELD_PREFIX . $custom_field_name ] ) ) {
			$field_key = $fields[ self::FIELD_PREFIX . $custom_field_name ];
			$field     = get_field_object( $field_key );
			$result    = isset( $field['label'] ) ? $field['label'] : $custom_field_name;
		}

		return $result;
	}


	/**
	 * Decode acf multi-values before indexing
	 *
	 * @param $custom_fields
	 * @param $post_id
	 *
	 * @return mixed
	 */
	public
	function filter_custom_fields(
		$custom_fields, $post_id
	) {

		if ( ! isset( $custom_fields ) ) {
			$custom_fields = array();
		}

		$fields = $this->get_acf_fields();

		foreach ( $custom_fields as $custom_field_name => $custom_field_value ) {

			if ( isset( $fields[ self::FIELD_PREFIX . $custom_field_name ] ) ) {

				$field_key = $fields[ self::FIELD_PREFIX . $custom_field_name ];
				$field     = get_field_object( $field_key, $post_id );

				// Index only non-empty fields
				if ( ! empty( $field ) && ! empty( $field['value'] ) ) {

					switch ( $field['type'] ) {

						case self::ACF_TYPE_REPEATER:
							foreach ( $field['value'] as $values ) {

								foreach ( $values as $repeated_field_name => $repeated_field_value ) {

									if ( empty( $custom_fields[ $repeated_field_name ] ) ) {
										$custom_fields[ $repeated_field_name ] = array();
									}

									array_push( $custom_fields[ $repeated_field_name ], $repeated_field_value );
								}
							}
							break;

						default:
							$custom_fields[ $custom_field_name ] = $field['value'];
							break;
					}


				}
			}
		}

		return $custom_fields;
	}

	/**
	 * Retrieve attachments in the fields of type file of the post
	 *
	 * @param array $attachments
	 * @param string $post
	 *
	 */
	public
	function filter_get_post_attachments(
		$attachments, $post_id
	) {

		if ( ! WPSOLR_Metabox::get_metabox_is_do_index_acf_field_files( $post_id ) ) {
			// Do nothing
			return $attachments;
		}

		// Get post ACF field objects
		$fields = get_field_objects( $post_id );

		if ( $fields ) {

			foreach ( $fields as $field_name => $field ) {

				// Retrieve the post_id of the file
				if ( ! empty( $field['value'] ) && ( self::ACF_TYPE_FILE === $field['type'] ) ) {

					switch ( $field['save_format'] ) {
						case self::ACF_TYPE_FILE_ID:
							array_push( $attachments, array( 'post_id' => $field['value'] ) );
							break;

						case self::ACF_TYPE_FILE_OBJECT:
							array_push( $attachments, array( 'post_id' => $field['value']['id'] ) );
							break;

						case self::ACF_TYPE_FILE_URL:
							array_push( $attachments, array( 'url' => $field['value'] ) );
							break;

						default:
							// Do nothing
							break;
					}
				}
			}

		}

		return $attachments;
	}
}