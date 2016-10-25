<?php

/**
 * Class PluginWooCommerce
 *
 * Manage WooCommerce plugin
 */
class PluginWooCommerce extends WpSolrExtensions {

	// Polylang options
	const _OPTIONS_NAME = 'wdm_solr_extension_woocommerce_data';

	// Product types
	const PRODUCT_TYPE_VARIABLE = 'variable';

	/**
	 * Factory
	 *
	 * @return PluginWooCommerce
	 */
	static function create() {

		return new self();
	}

	/*
	 * Constructor
	 * Subscribe to actions
	 */

	function __construct() {

		add_filter( WpSolrFilters::WPSOLR_FILTER_POST_CUSTOM_FIELDS, array(
			$this,
			'filter_custom_fields'
		), 10, 2 );

	}

	/**
	 * Add woo attributes to a custom field with the same name
	 *
	 * @param $custom_fields
	 * @param $post_id
	 *
	 * @return mixed
	 */
	public function filter_custom_fields( $custom_fields, $post_id ) {

		if ( ! isset( $custom_fields ) ) {
			$custom_fields = array();
		}

		// Get the product correponding to this post
		$product = wc_get_product( $post_id );
		
		if ( false === $product ) {
			// Not a product
			return $custom_fields;
		}

		switch ( $product->get_type() ) {

			case self::PRODUCT_TYPE_VARIABLE:

				$product_variable = new WC_Product_Variable( $product );
				foreach ( $product_variable->get_available_variations() as $variation_array ) {

					foreach ( $variation_array['attributes'] as $attribute_name => $attribute_value ) {

						if ( ! isset( $custom_fields[ $attribute_name ] ) ) {
							$custom_fields[ $attribute_name ] = array();
						}

						if ( ! in_array( $attribute_value, $custom_fields[ $attribute_name ], true ) ) {

							array_push( $custom_fields[ $attribute_name ], $attribute_value );
						}
					}
				}


				break;

			default:

				foreach ( $product->get_attributes() as $attribute ) {

					//$terms = wc_get_product_terms( $product->id, $attribute['name'], array( 'fields' => 'names' ) );

					// Remove the eventual 'pa_' prefix from the global attribute name
					$attribute_name = $attribute['name'];
					if ( substr( $attribute_name, 0, 3 ) === 'pa_' ) {
						$attribute_name = substr( $attribute_name, 3, strlen( $attribute_name ) );
					}

					$custom_fields[ $attribute_name ] = explode( ',', $product->get_attribute( $attribute['name'] ) );
				}

				break;
		}


		return $custom_fields;
	}


	/**
	 * Return all woo commerce attributes
	 * @return array
	 */
	static function get_attribute_taxonomies() {

		// Standard woo function
		return wc_get_attribute_taxonomies();
	}

	/**
	 * Return all woo commerce attributes names (slugs)
	 * @return array
	 */
	static function get_attribute_taxonomy_names() {

		$results = array();

		foreach ( self::get_attribute_taxonomies() as $woo_attribute ) {

			// Add woo attribute terms to custom fields
			array_push( $results, $woo_attribute->attribute_name );
		}

		return $results;
	}

}