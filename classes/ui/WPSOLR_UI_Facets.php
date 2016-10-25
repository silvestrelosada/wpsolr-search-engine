<?php

/**
 * Display facets
 *
 * Class WPSOLR_UI_Facets
 */
class WPSOLR_UI_Facets {

	const WPSOLR_FACET_CHECKBOX_CLASS = 'wpsolr_facet_checkbox';

	/**
	 * Build facets UI
	 *
	 * @param array $facets
	 * @param $localization_options array
	 *
	 * @return string
	 */
	public static function Build( $facets, $localization_options ) {

		$html = '';

		if ( ! empty( $facets ) ) {

			$facet_element = OptionLocalization::get_term( $localization_options, 'facets_element' );
			$facet_title   = OptionLocalization::get_term( $localization_options, 'facets_title' );

			foreach ( $facets as $facet ) {

				$html .= "<div class='wpsolr_facet_title'>" . sprintf( $facet_title, $facet['name'] ) . "</div>";

				self::displayFacetHierarchy( $facet_element, $html, $facet, ! empty( $facet['items'] ) ? $facet['items'] : array() );
			}

			$is_facet_selected = false;
			$html              = sprintf( "<div><label class='wdm_label'>%s</label>
                                    <input type='hidden' name='sel_fac_field' id='sel_fac_field' data-wpsolr-facets-selected=''>
                                    <div class='wdm_ul' id='wpsolr_section_facets'><div class='select_opt %s' id='wpsolr_remove_facets'>%s</div>",
					OptionLocalization::get_term( $localization_options, 'facets_header' ),
					self::WPSOLR_FACET_CHECKBOX_CLASS . ( ! $is_facet_selected ? ' checked' : '' ),
					OptionLocalization::get_term( $localization_options, 'facets_element_all_results' )
			                     )
			                     . $html;

			$html .= '</div></div>';
		}

		return $html;
	}


	public static function displayFacetHierarchy( $facet_element, &$html, $facet, $items ) {

		if ( empty( $items ) ) {
			return;
		}

		$html .= '<ul>';

		$is_facet_selected = false;

		$facet_id = strtolower( str_replace( ' ', '_', $facet['id'] ) );
		foreach ( $items as $item ) {

			$item_name           = $item['value'];
			$item_localized_name = ! empty( $item['value_localized'] ) ? $item['value_localized'] : $item['value'];
			$item_count          = $item['count'];
			$item_selected       = isset( $item['selected'] ) ? $item['selected'] : false;

			// Check if one facet item is selected (once only).
			if ( $item_selected && ! $is_facet_selected ) {
				$is_facet_selected = true;
			}

			$facet_class = self::WPSOLR_FACET_CHECKBOX_CLASS . ( $item_selected ? ' checked' : '' );

			$html .= '<li>';
			$html .= "<div class='select_opt $facet_class' id='$facet_id:$item_name'>"
			         . ( empty( $item['items'] ) ? sprintf( $facet_element, $item_localized_name, $item_count ) : $item_localized_name ) // only show count on leaf items (else count is false)
			         . "</div>";

			if ( ! empty( $item['items'] ) ) {

				self::displayFacetHierarchy( $facet_element, $html, $facet, $item['items'] );
			}

			$html .= '</li>';

		}

		$html .= '</ul>';

	}

}
