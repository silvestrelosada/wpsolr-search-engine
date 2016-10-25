<?php

/**
 * Facets data
 *
 * Class WPSOLR_Data_Facets
 */
class WPSOLR_Data_Facets {

	// Labels for field values
	protected static $fields_items_labels;

	/**
	 * @param $facets_selected
	 * @param $facets_to_display
	 * @param $facets_in_results
	 *
	 * @return array    [
	 *                  {"items":[{"name":"post","count":5,"selected":true}],"id":"type","name":"Type"},
	 *                  {"items":[{"name":"admin","count":6,"selected":false}],"id":"author","name":"Author"},
	 *                  {"items":[{"name":"Blog","count":13,"selected":true}],"id":"categories","name":"Categories"}
	 *                  ]
	 */
	public static function get_data( $facets_selected, $facets_to_display, $facets_in_results ) {

		$results = array();

		if ( count( $facets_in_results ) && count( $facets_to_display ) ) {

			$facets_labels = WPSOLR_Global::getOption()->get_facets_labels();

			foreach ( $facets_to_display as $facet_to_display_id ) {

				if ( isset( $facets_in_results[ $facet_to_display_id ] ) && count( $facets_in_results[ $facet_to_display_id ] ) > 0 ) {

					$facet_with_no_blank_id = strtolower( str_replace( ' ', '_', $facet_to_display_id ) );

					// Remove the ending "_str"
					$facet_to_display_id_without_str = preg_replace( '/_str$/', '', $facet_to_display_id );

					// Give plugins a chance to change the facet name (ACF).
					$facet_to_display_name = apply_filters( WpSolrFilters::WPSOLR_FILTER_SEARCH_PAGE_FACET_NAME, $facet_to_display_id_without_str );

					if ( $facet_to_display_name === $facet_to_display_id_without_str ) {
						// Facet label not changed by filter

						if ( ! empty( $facets_labels[ $facet_to_display_id ] ) ) {
							// Facet label is defined in options
							$facet_to_display_name = $facets_labels[ $facet_to_display_id ];

							// Give plugins a chance to change the facet name (WPML, POLYLANG).
							$facet_to_display_name = apply_filters( WpSolrFilters::WPSOLR_FILTER_TRANSLATION_STRING, $facet_to_display_name,
								array(
									'domain' => WPSOLR_Option::TRANSLATION_DOMAIN_FACET_LABEL,
									'name'   => $facet_to_display_id,
									'text'   => $facet_to_display_name
								) );

						} else {
							// Try to make a decent label from the facet raw id
							$facet_to_display_name = str_replace( '_', ' ', $facet_to_display_name );
							$facet_to_display_name = ucfirst( $facet_to_display_name );
						}
					}

					$facet          = array();
					$facet['items'] = array();
					$facet['id']    = $facet_to_display_id;
					$facet['name']  = $facet_to_display_name;

					$items_hierachy = array();
					self::buildHierarchies( $items_hierachy, $facet_to_display_id, $facets_in_results[ $facet_to_display_id ],
						! empty( $facets_selected[ $facet_with_no_blank_id ] ) ? $facets_selected[ $facet_with_no_blank_id ] : array() );

					foreach ( $items_hierachy as $facet_in_results ) {

						array_push( $facet['items'], array(
							'value'           => $facet_in_results['value'],
							'value_localized' => ! empty( $facet_in_results['value_localized'] ) ? $facet_in_results['value_localized'] : $facet_in_results['value'],
							'count'           => $facet_in_results['count'],
							'items'           => $facet_in_results['items'],
							'selected'        => $facet_in_results['selected']
						) );

					}

					// Add current facet to results
					array_push( $results, $facet );
				}

			}

		}

		return $results;
	}


	/**
	 * Build a hierachy of facets when facet name contains WpSolrSchema::FACET_HIERARCHY_SEPARATOR
	 * Recursive
	 *
	 * @param $results
	 * @param $items
	 */
	public static function buildHierarchies( &$results, $facet_to_display_id, $items, $facets_selected ) {

		$result = array();
		foreach ( $items as $item ) {

			$item_hierarcy_item_names = explode( WpSolrSchema::FACET_HIERARCHY_SEPARATOR, $item['value'] );
			$item_top_level_name      = array_shift( $item_hierarcy_item_names );

			if ( empty( $result[ $item_top_level_name ] ) ) {
				$result[ $item_top_level_name ]          = array(
					'value'           => $item_top_level_name,
					'value_localized' => ( WpSolrSchema::_FIELD_NAME_TYPE === $facet_to_display_id ) ? self::get_field_value_localization( $facet_to_display_id, $item_top_level_name, null ) : $item_top_level_name,
					'count'           => $item['count'],
					'selected'        => isset( $facets_selected ) && ( in_array( $item_top_level_name, $facets_selected, true ) )
				);
				$result[ $item_top_level_name ]['items'] = array();
			}

			if ( ! empty( $item_hierarcy_item_names ) ) {

				array_push( $result[ $item_top_level_name ]['items'],
					array(
						'value'    => implode( WpSolrSchema::FACET_HIERARCHY_SEPARATOR, $item_hierarcy_item_names ),
						'count'    => $item['count'],
						'selected' => isset( $facets_selected ) && ( in_array( $item_top_level_name, $facets_selected, true ) )
					)
				);
			}
		}


		foreach ( $result as $top_name => $sub_items ) {

			$level = array(
				'value'           => $sub_items['value'],
				'value_localized' => ! empty( $sub_items['value_localized'] ) ? $sub_items['value_localized'] : $sub_items['value'],
				'count'           => $sub_items['count'],
				'selected'        => $sub_items['selected'],
				'items'           => array()
			);

			if ( ! empty( $sub_items['items'] ) ) {

				self::buildHierarchies( $level['items'], $facet_to_display_id, $sub_items['items'], $facets_selected );
			}

			// Calculate the count by summing children count
			if ( ! empty( $level['items'] ) ) {

				$count = 0;
				foreach ( $level['items'] as $item ) {

					$count += $item['count'];
				}
				$level['count'] = $count;
			}

			array_push( $results, $level );
		}

	}

	/**
	 * Replace a field value by it's localization.
	 *
	 * @param $field_name
	 * @param $field_value
	 *
	 * @return mixed
	 */
	public static function get_field_value_localization( $field_name, $field_value, $language ) {

		$value = $field_value;

		if ( ( WpSolrSchema::_FIELD_NAME_TYPE === $field_name ) && ( null === self::$fields_items_labels[ $field_name ] ) ) {

			// Init the items labels once, only for field WpSolrSchema::_FIELD_NAME_TYPE
			self::$fields_items_labels = WPSOLR_Global::getOption()->get_facets_items_labels();
		}

		if ( ( WpSolrSchema::_FIELD_NAME_TYPE === $field_name ) && ( ! empty( self::$fields_items_labels[ $field_name ] ) ) ) {

			if ( ! empty( self::$fields_items_labels[ $field_name ][ $field_value ] ) ) {

				$value = apply_filters( WpSolrFilters::WPSOLR_FILTER_TRANSLATION_STRING, $field_value,
					array(
						'domain'   => WPSOLR_Option::TRANSLATION_DOMAIN_FACET_LABEL,
						'name'     => $field_value,
						'text'     => $field_value,
						'language' => $language
					) );
			}

			if ( $value === $field_value ) {
				// No translation for this value, try to get the localization instead.
				$value = ! empty( self::$fields_items_labels[ $field_name ][ $field_value ] ) ? self::$fields_items_labels[ $field_name ][ $field_value ] : $field_value;
			}

		}

		return $value;
	}

}
