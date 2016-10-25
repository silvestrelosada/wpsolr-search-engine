<?php


/**
 * Class OptionLicenses
 *
 * Manage licenses options
 */
class OptionLicenses extends WpSolrExtensions {

	// Ajax methods
	const AJAX_ACTIVATE_LICENCE = 'ajax_activate_licence';
	const AJAX_DEACTIVATE_LICENCE = 'ajax_deactivate_licence';
	const AJAX_VERIFY_LICENCE = 'ajax_verify_licence';

	// License types
	const LICENSE_PACKAGE_CORE = 'LICENSE_PACKAGE_CORE';
	const LICENSE_PACKAGE_WOOCOMMERCE = 'LICENSE_PACKAGE_WOOCOMMERCE';
	const LICENSE_PACKAGE_ACF = 'LICENSE_PACKAGE_ACF';
	const LICENSE_PACKAGE_TYPES = 'LICENSE_PACKAGE_TYPES';
	const LICENSE_PACKAGE_WPML = 'LICENSE_PACKAGE_WPML';
	const LICENSE_PACKAGE_POLYLANG = 'LICENSE_PACKAGE_POLYLANG';
	const LICENSE_PACKAGE_GROUPS = 'LICENSE_PACKAGE_GROUPS';
	const LICENSE_PACKAGE_S2MEMBER = 'LICENSE_PACKAGE_S2MEMBER';
	const LICENSE_PACKAGE_BBPRESS = 'LICENSE_PACKAGE_BBPRESS';
	const LICENSE_PACKAGE_EMBED_ANY_DOCUMENT = 'LICENSE_PACKAGE_EMBED_ANY_DOCUMENT';
	const LICENSE_PACKAGE_PDF_EMBEDDER = 'LICENSE_PACKAGE_PDF_EMBEDDER';
	const LICENSE_PACKAGE_GOOGLE_DOC_EMBEDDER = 'LICENSE_PACKAGE_GOOGLE_DOC_EMBEDDER';

	// License type fields
	const FIELD_LICENSE_SUBSCRIPTION_NUMBER = 'license_subscription_number';
	const FIELD_LICENSE_PACKAGE = 'license_package';
	const FIELD_DESCRIPTION = 'description';
	const FIELD_IS_ACTIVATED = 'is_activated';
	const FIELD_ORDERS_URLS = 'orders_urls';
	const FIELD_ORDER_URL_BUTTON_LABEL = 'order_url_button_label';
	const FIELD_ORDER_URL_TEXT = 'order_url_text';
	const FIELD_ORDER_URL_LINK = 'order_url_link';
	const FIELD_ORDER_URL_BUTTON_LABEL_DEFAULT = '7 days free trial';
	const FIELD_FEATURES = 'features';
	const FIELD_LICENSE_TITLE = 'LICENSE_TITLE';
	const FIELD_LICENSE_MATCHING_REFERENCE = 'matching_license_reference';
	const FIELD_NEEDS_VERIFICATION = 'needs_verification';
	const FIELD_LICENSE_ACTIVATION_UUID = 'activation_uuid';

	// Texts
	const TEXT_LICENSE_ACTIVATED = 'License is activated';
	const TEXT_LICENSE_DEACTIVATED = 'License is not activated. Click to activate.';

	public $is_installed;
	private $_options;

	// Order link
	const ORDER_LINK_URL = 'https://secure.avangate.com/order/trial.php?PRODS=4687291&QTY=1&PRICES4687291[EUR]=0&TPERIOD=7&PHASH=af1373521d3efd46f8db12dfde45c91d';

	// Features
	const FEATURE_ZENDESK_SUPPORT = 'Get support via Zendesk <br/>(Apache Solr setup/installation not supported)';
	const FEATURE_FREE_UPGRADE_ONE_YEAR = 'Get free upgrades during one year';

	/**
	 * Constructor.
	 */
	function __construct() {
		$this->_options     = self::get_option_data( self::OPTION_LICENSES, array() );
		$this->is_installed = WPSOLR_Global::getOption()->get_option_is_installed();
	}


	/**
	 * Return all activated licenses
	 */
	function get_licenses() {
		$results = $this->_options;

		return $results;
	}


	/**
	 * Upgrade all licenses
	 */
	static function upgrade_licenses() {

		// Upgrade licenses
		$licenses = self::get_option_data( self::OPTION_LICENSES, array() );

		if ( ! empty( $licenses ) ) {

			foreach ( $licenses as $license_package => $license ) {

				$licenses[ $license_package ][ self::FIELD_NEEDS_VERIFICATION ] = true;
			}

			self::set_option_data( self::OPTION_LICENSES, $licenses );

		} else {

			// Installation
			WPSOLR_Global::getOption()->get_option_installation();
		}

	}

	/**
	 * Is a license activated ?
	 */
	function get_license_is_activated( $license_type ) {
		$licenses = $this->get_licenses();

		return isset( $licenses[ $license_type ] )
		       && isset( $licenses[ $license_type ][ self::FIELD_IS_ACTIVATED ] )
		       && ! isset( $licenses[ $license_type ][ self::FIELD_NEEDS_VERIFICATION ] );
	}

	/**
	 * Is a license need to be verified ?
	 */
	function get_license_is_need_verification( $license_type ) {
		$licenses = $this->get_licenses();

		return isset( $licenses[ $license_type ] )
		       && isset( $licenses[ $license_type ][ self::FIELD_IS_ACTIVATED ] )
		       && isset( $licenses[ $license_type ][ self::FIELD_NEEDS_VERIFICATION ] );
	}

	/**
	 * Is a license can be deactivated ?
	 */
	function get_license_is_can_be_deactivated( $license_type ) {
		$licenses = $this->get_licenses();

		return isset( $licenses[ $license_type ] )
		       && isset( $licenses[ $license_type ][ self::FIELD_IS_ACTIVATED ] );
	}


	/**
	 * Get licanse activation api url
	 */
	static function get_license_api_url() {

		if ( false ) {
			//	if ( isset( $_SERVER['HTTP_HOST'] ) ? false : $_SERVER['HTTP_HOST'] === 'dev-wpsolr-search-engine.dev' ) {

			return 'http://10.0.2.2:8082/v1/providers/d26a384b-fa62-4bdb-a1dd-27d714a3f519/accounts/2c93bcdc-e6cd-4251-b4f7-8130e398dc36/addons/87dd6f18-3d36-4339-8974-45b80831f8fe/license-manager/30866139-e56a-426b-a7fe-500523cbcce5/licenses';

		} else {

			return 'https://api.gotosolr.com/v1/providers/8c25d2d6-54ae-4ff6-a478-e2c03f1e08a4/accounts/24b7729e-02dc-47d1-9c15-f1310098f93f/addons/b553e78c-3af8-4c97-9157-db77bfa6d909/license-manager/83e214e6-54f8-4f59-ba95-889de756ebee/licenses';
		}
	}

	/**
	 * Return all license types
	 */
	function get_license_types() {

		return array(
			self::LICENSE_PACKAGE_CORE                => array(
				self::FIELD_LICENSE_MATCHING_REFERENCE => 'wpsolr_package_premium',
				self::FIELD_LICENSE_TITLE              => 'Premium',
				self::FIELD_DESCRIPTION                => '',
				self::FIELD_ORDERS_URLS                => array(
					array(
						self::FIELD_ORDER_URL_BUTTON_LABEL => self::FIELD_ORDER_URL_BUTTON_LABEL_DEFAULT,
						self::FIELD_ORDER_URL_TEXT         => 'Order a pack now',
						self::FIELD_ORDER_URL_LINK         => self::ORDER_LINK_URL
					),
				),
				self::FIELD_FEATURES                   => array(
					self::FEATURE_ZENDESK_SUPPORT,
					self::FEATURE_FREE_UPGRADE_ONE_YEAR,
					'Create a test Solr index, valid 2 hours',
					'Configure several Solr indexes',
					'Select your theme search page',
					'Select Infinite Scroll navigation in Ajax search',
					'Display suggestions (Did you mean?)',
					'Index custom post types',
					'Index attachments',
					'Index custom taxonomies',
					'Index custom fields',
					'Show facets hierarchies',
					'Localize (translate) the front search page with your .po files',
					'Display debug infos during indexing',
					'Reindex all your data in-place',
					'Deactivate real-time indexing to load huge external datafeeds'
				)
			),
			self::LICENSE_PACKAGE_WOOCOMMERCE         => array(
				self::FIELD_LICENSE_MATCHING_REFERENCE => 'wpsolr_package_woocommerce',
				self::FIELD_LICENSE_TITLE              => 'WooCommerce',
				self::FIELD_DESCRIPTION                => 'WooCommerce Extension description',
				self::FIELD_ORDERS_URLS                => array(
					array(
						self::FIELD_ORDER_URL_BUTTON_LABEL => self::FIELD_ORDER_URL_BUTTON_LABEL_DEFAULT,
						self::FIELD_ORDER_URL_TEXT         => 'Order a pack now',
						self::FIELD_ORDER_URL_LINK         => self::ORDER_LINK_URL
					),
				),
				self::FIELD_FEATURES                   => array(
					self::FEATURE_ZENDESK_SUPPORT,
					self::FEATURE_FREE_UPGRADE_ONE_YEAR,
					'Index product attributes/variations',
					'Search in product attributes/variations',
					'Create facets on product attributes/variations'
				)
			),
			self::LICENSE_PACKAGE_ACF                 => array(
				self::FIELD_LICENSE_MATCHING_REFERENCE => 'wpsolr_package_acf',
				self::FIELD_LICENSE_TITLE              => 'ACF',
				self::FIELD_DESCRIPTION                => 'ACF Extension description',
				self::FIELD_ORDERS_URLS                => array(
					array(
						self::FIELD_ORDER_URL_BUTTON_LABEL => self::FIELD_ORDER_URL_BUTTON_LABEL_DEFAULT,
						self::FIELD_ORDER_URL_TEXT         => 'Order a pack now',
						self::FIELD_ORDER_URL_LINK         => self::ORDER_LINK_URL
					),
				),
				self::FIELD_FEATURES                   => array(
					self::FEATURE_ZENDESK_SUPPORT,
					self::FEATURE_FREE_UPGRADE_ONE_YEAR,
					'Replace facet names with their ACF label',
					'Decode ACF field values before indexing a post',
					'Index ACF field files content inside the post',
					'Group ACF repeater rows under one single facet field (requires ACF Pro 5.0.0)'
				)
			),
			self::LICENSE_PACKAGE_TYPES               => array(
				self::FIELD_LICENSE_MATCHING_REFERENCE => 'wpsolr_package_types',
				self::FIELD_LICENSE_TITLE              => 'Types',
				self::FIELD_DESCRIPTION                => 'Types Extension description',
				self::FIELD_ORDERS_URLS                => array(
					array(
						self::FIELD_ORDER_URL_BUTTON_LABEL => self::FIELD_ORDER_URL_BUTTON_LABEL_DEFAULT,
						self::FIELD_ORDER_URL_TEXT         => 'Order a pack now',
						self::FIELD_ORDER_URL_LINK         => self::ORDER_LINK_URL
					),
				),
				self::FIELD_FEATURES                   => array(
					self::FEATURE_ZENDESK_SUPPORT,
					self::FEATURE_FREE_UPGRADE_ONE_YEAR,
					'Replace facet names with their Types label'
				)
			),
			self::LICENSE_PACKAGE_WPML                => array(
				self::FIELD_LICENSE_MATCHING_REFERENCE => 'wpsolr_package_wpml',
				self::FIELD_LICENSE_TITLE              => 'WPML',
				self::FIELD_DESCRIPTION                => 'WPML Extension description',
				self::FIELD_ORDERS_URLS                => array(
					array(
						self::FIELD_ORDER_URL_BUTTON_LABEL => self::FIELD_ORDER_URL_BUTTON_LABEL_DEFAULT,
						self::FIELD_ORDER_URL_TEXT         => 'Order a pack now',
						self::FIELD_ORDER_URL_LINK         => self::ORDER_LINK_URL
					),
				),
				self::FIELD_FEATURES                   => array(
					self::FEATURE_ZENDESK_SUPPORT,
					self::FEATURE_FREE_UPGRADE_ONE_YEAR,
					'User can associate WPML languages to their own Solr index',
					'Indexing process send each data to it\'s language related Solr index',
					'Search results are displayed in each WPML languages'
				)
			),
			self::LICENSE_PACKAGE_POLYLANG            => array(
				self::FIELD_LICENSE_MATCHING_REFERENCE => 'wpsolr_package_polylang',
				self::FIELD_LICENSE_TITLE              => 'Polylang',
				self::FIELD_DESCRIPTION                => 'Polylang Extension description',
				self::FIELD_ORDERS_URLS                => array(
					array(
						self::FIELD_ORDER_URL_BUTTON_LABEL => self::FIELD_ORDER_URL_BUTTON_LABEL_DEFAULT,
						self::FIELD_ORDER_URL_TEXT         => 'Order a pack now',
						self::FIELD_ORDER_URL_LINK         => self::ORDER_LINK_URL
					),
				),
				self::FIELD_FEATURES                   => array(
					self::FEATURE_ZENDESK_SUPPORT,
					self::FEATURE_FREE_UPGRADE_ONE_YEAR,
					'User can associate Polylang languages to their own Solr index',
					'Indexing process send each data to it\'s language related Solr index',
					'Search results are displayed in each Polylang languages'
				)
			),
			self::LICENSE_PACKAGE_GROUPS              => array(
				self::FIELD_LICENSE_MATCHING_REFERENCE => 'wpsolr_package_groups',
				self::FIELD_LICENSE_TITLE              => 'Groups',
				self::FIELD_DESCRIPTION                => 'Groups Extension description',
				self::FIELD_ORDERS_URLS                => array(
					array(
						self::FIELD_ORDER_URL_BUTTON_LABEL => self::FIELD_ORDER_URL_BUTTON_LABEL_DEFAULT,
						self::FIELD_ORDER_URL_TEXT         => 'Order a pack now',
						self::FIELD_ORDER_URL_LINK         => self::ORDER_LINK_URL
					),
				),
				self::FIELD_FEATURES                   => array(
					self::FEATURE_ZENDESK_SUPPORT,
					self::FEATURE_FREE_UPGRADE_ONE_YEAR,
					'Results are indexed and filtered with Groups user\'s groups/capabilities',
				)
			),
			self::LICENSE_PACKAGE_S2MEMBER            => array(
				self::FIELD_LICENSE_MATCHING_REFERENCE => 'wpsolr_package_s2member',
				self::FIELD_LICENSE_TITLE              => 's2Member',
				self::FIELD_DESCRIPTION                => 's2Member Extension description',
				self::FIELD_ORDERS_URLS                => array(
					array(
						self::FIELD_ORDER_URL_BUTTON_LABEL => self::FIELD_ORDER_URL_BUTTON_LABEL_DEFAULT,
						self::FIELD_ORDER_URL_TEXT         => 'Order a pack now',
						self::FIELD_ORDER_URL_LINK         => self::ORDER_LINK_URL
					),
				),
				self::FIELD_FEATURES                   => array(
					self::FEATURE_ZENDESK_SUPPORT,
					self::FEATURE_FREE_UPGRADE_ONE_YEAR,
					'Results are indexed and filtered with s2Member user\'s levels/capabilities capabilities',
				)
			),
			self::LICENSE_PACKAGE_BBPRESS             => array(
				self::FIELD_LICENSE_MATCHING_REFERENCE => 'wpsolr_package_bbpress',
				self::FIELD_LICENSE_TITLE              => 'bbPress',
				self::FIELD_DESCRIPTION                => 'bbPress Extension description',
				self::FIELD_ORDERS_URLS                => array(
					array(
						self::FIELD_ORDER_URL_BUTTON_LABEL => self::FIELD_ORDER_URL_BUTTON_LABEL_DEFAULT,
						self::FIELD_ORDER_URL_TEXT         => 'Order a pack now',
						self::FIELD_ORDER_URL_LINK         => self::ORDER_LINK_URL
					),
				),
				self::FIELD_FEATURES                   => array(
					self::FEATURE_ZENDESK_SUPPORT,
					self::FEATURE_FREE_UPGRADE_ONE_YEAR,
					'Benefit from the Solr search features (speed, relevancy, partial match, fuzzy match ...), while keeping your current bbPress theme.',
				)
			),
			self::LICENSE_PACKAGE_EMBED_ANY_DOCUMENT  => array(
				self::FIELD_LICENSE_MATCHING_REFERENCE => 'wpsolr_package_embed_any_document',
				self::FIELD_LICENSE_TITLE              => 'Embed Any Document',
				self::FIELD_DESCRIPTION                => 'Embed Any Document Extension description',
				self::FIELD_ORDERS_URLS                => array(
					array(
						self::FIELD_ORDER_URL_BUTTON_LABEL => self::FIELD_ORDER_URL_BUTTON_LABEL_DEFAULT,
						self::FIELD_ORDER_URL_TEXT         => 'Order a pack now',
						self::FIELD_ORDER_URL_LINK         => self::ORDER_LINK_URL
					),
				),
				self::FIELD_FEATURES                   => array(
					self::FEATURE_ZENDESK_SUPPORT,
					self::FEATURE_FREE_UPGRADE_ONE_YEAR,
					'Automatically index and search embedded documents with the plugin shortcode.',
				)
			),
			self::LICENSE_PACKAGE_PDF_EMBEDDER        => array(
				self::FIELD_LICENSE_MATCHING_REFERENCE => 'wpsolr_package_pdf_embedder',
				self::FIELD_LICENSE_TITLE              => 'Pdf Embedder',
				self::FIELD_DESCRIPTION                => 'Pdf Embedder Extension description',
				self::FIELD_ORDERS_URLS                => array(
					array(
						self::FIELD_ORDER_URL_BUTTON_LABEL => self::FIELD_ORDER_URL_BUTTON_LABEL_DEFAULT,
						self::FIELD_ORDER_URL_TEXT         => 'Order a pack now',
						self::FIELD_ORDER_URL_LINK         => self::ORDER_LINK_URL
					),
				),
				self::FIELD_FEATURES                   => array(
					self::FEATURE_ZENDESK_SUPPORT,
					self::FEATURE_FREE_UPGRADE_ONE_YEAR,
					'Automatically index and search embedded pdfs with the plugin shortcode.',
				)
			),
			self::LICENSE_PACKAGE_GOOGLE_DOC_EMBEDDER => array(
				self::FIELD_LICENSE_MATCHING_REFERENCE => 'wpsolr_package_google_doc_embedder',
				self::FIELD_LICENSE_TITLE              => 'Google Doc Embedder',
				self::FIELD_DESCRIPTION                => 'Google Doc Embedder Extension description',
				self::FIELD_ORDERS_URLS                => array(
					array(
						self::FIELD_ORDER_URL_BUTTON_LABEL => self::FIELD_ORDER_URL_BUTTON_LABEL_DEFAULT,
						self::FIELD_ORDER_URL_TEXT         => 'Order a pack now',
						self::FIELD_ORDER_URL_LINK         => self::ORDER_LINK_URL
					),
				),
				self::FIELD_FEATURES                   => array(
					self::FEATURE_ZENDESK_SUPPORT,
					self::FEATURE_FREE_UPGRADE_ONE_YEAR,
					'Automatically index and search embedded documents with the plugin shortcode.',
				)
			)
		);

	}


	/**
	 * Show premium link in place of a text if not licensed
	 *
	 * @param $license_type
	 * @param $text_to_show
	 * @param $is_show_link
	 *
	 * @return string
	 */
	function show_premium_link( $license_type, $text_to_show, $is_show_link, $is_new_feature = false ) {

		if ( ( ! $this->is_installed && ! $is_new_feature ) || $this->get_license_is_activated( $license_type ) ) {

			if ( ( ! $is_show_link ) || ( ! $this->is_installed && ! $is_new_feature ) ) {
				return ( self::TEXT_LICENSE_ACTIVATED === $text_to_show ) ? '' : $text_to_show;
			}

			$img_url = plugins_url( 'images/success.png', WPSOLR_PLUGIN_FILE );

		} else {

			$img_url = plugins_url( 'images/warning.png', WPSOLR_PLUGIN_FILE );

		}

		$result = sprintf( '<a href="#TB_inline?width=800&height=700&inlineId=%s" class="thickbox wpsolr_premium_class" >' .
		                   '<img src="%s" class="wpsolr_premium_text_class" style="display:inline"><span>%s</span></a>',
			$license_type, $img_url, $text_to_show );

		return $result;
	}

	/**
	 * Output a disable html code if not licensed
	 *
	 * @param $license_type
	 *
	 * @return string
	 */
	function get_license_enable_html_code( $license_type, $is_new_feature = false ) {

		return ( ( ! $this->is_installed && ! $is_new_feature ) || $this->get_license_is_activated( $license_type ) ) ? '' : 'disabled';
	}


	/**
	 * Get a license type order urls
	 * @return mixed
	 */
	public
	function get_license_orders_urls(
		$license_type
	) {
		$license_types = $this->get_license_types();

		return $license_types[ $license_type ][ self::FIELD_ORDERS_URLS ];
	}

	/**
	 * Get a license matching reference
	 * @return mixed
	 */
	public
	function get_license_matching_reference(
		$license_type
	) {
		$license_types = $this->get_license_types();

		return $license_types[ $license_type ][ self::FIELD_LICENSE_MATCHING_REFERENCE ];
	}

	/**
	 * Get a license activation uuid
	 * @return string
	 */
	public
	function get_license_activation_uuid(
		$license_type
	) {
		$licenses = $this->get_licenses();

		return isset( $licenses[ $license_type ][ self::FIELD_LICENSE_ACTIVATION_UUID ] ) ? $licenses[ $license_type ][ self::FIELD_LICENSE_ACTIVATION_UUID ] : '';
	}

	/**
	 * Get a license subscription number
	 * @return string
	 */
	public
	function get_license_subscription_number(
		$license_type
	) {
		$licenses = $this->get_licenses();

		return isset( $licenses[ $license_type ][ self::FIELD_LICENSE_SUBSCRIPTION_NUMBER ] ) ? $licenses[ $license_type ][ self::FIELD_LICENSE_SUBSCRIPTION_NUMBER ] : '';
	}

	/**
	 * Get a license type features
	 * @return mixed
	 */
	public
	function get_license_features(
		$license_type
	) {
		$license_types = $this->get_license_types();

		return $license_types[ $license_type ][ self::FIELD_FEATURES ];
	}


	/**
	 * Ajax call to activate a license
	 */
	public
	static function ajax_activate_licence() {

		$subscription_number        = isset( $_POST['data'] ) && isset( $_POST['data'][ self::FIELD_LICENSE_SUBSCRIPTION_NUMBER ] ) ? $_POST['data'][ self::FIELD_LICENSE_SUBSCRIPTION_NUMBER ] : null;
		$license_package            = isset( $_POST['data'] ) && isset( $_POST['data'][ self::FIELD_LICENSE_PACKAGE ] ) ? $_POST['data'][ self::FIELD_LICENSE_PACKAGE ] : null;
		$license_matching_reference = isset( $_POST['data'] ) && isset( $_POST['data'][ self::FIELD_LICENSE_MATCHING_REFERENCE ] ) ? $_POST['data'][ self::FIELD_LICENSE_MATCHING_REFERENCE ] : null;

		$managed_solr_server = new OptionManagedSolrServer();
		$response_object     = $managed_solr_server->call_rest_activate_license( self::get_license_api_url(), $license_matching_reference, $subscription_number );

		if ( isset( $response_object ) && OptionManagedSolrServer::is_response_ok( $response_object ) ) {

			// Save the license type activation
			$licenses                     = self::get_option_data( self::OPTION_LICENSES, array() );
			$licenses[ $license_package ] = array(
				self::FIELD_IS_ACTIVATED                => true,
				self::FIELD_LICENSE_SUBSCRIPTION_NUMBER => $subscription_number,
				self::FIELD_LICENSE_ACTIVATION_UUID     => OptionManagedSolrServer::get_response_result( $response_object, 'uuid' ),
			);
			self::set_option_data( self::OPTION_LICENSES, $licenses );

		}


		// Return the whole object
		echo json_encode( $response_object );

		die();
	}

	/**
	 * Ajax call to deactivate a license
	 */
	public
	static function ajax_deactivate_licence() {

		$option_licenses = new OptionLicenses();
		$licenses        = $option_licenses->get_licenses();

		$license_package         = isset( $_POST['data'] ) && isset( $_POST['data'][ self::FIELD_LICENSE_PACKAGE ] ) ? $_POST['data'][ self::FIELD_LICENSE_PACKAGE ] : null;
		$license_activation_uuid = $option_licenses->get_license_activation_uuid( $license_package );

		if ( empty( $license_activation_uuid ) ) {

			$licenses[ $license_package ] = array(
				self::FIELD_LICENSE_SUBSCRIPTION_NUMBER => $licenses[ $license_package ][ self::FIELD_LICENSE_SUBSCRIPTION_NUMBER ],
			);
			self::set_option_data( self::OPTION_LICENSES, $licenses );

			echo json_encode( (object) array(
				'status' => (object) array(
					'state'   => 'ERROR',
					'message' => 'This license activation code is missing. Try to unactivate manually, by signin to your subscription account.'
				)
			) );

			die();
		}

		$managed_solr_server = new OptionManagedSolrServer();
		$response_object     = $managed_solr_server->call_rest_deactivate_license( self::get_license_api_url(), $license_activation_uuid );

		if ( isset( $response_object ) && OptionManagedSolrServer::is_response_ok( $response_object ) ) {

		}

		// Alway remove the activation, else we're stuck forever
		if ( isset( $licenses[ $license_package ] ) ) {

			// Remove the license type activation
			$licenses                     = self::get_option_data( self::OPTION_LICENSES, array() );
			$licenses[ $license_package ] = array(
				self::FIELD_LICENSE_SUBSCRIPTION_NUMBER => $licenses[ $license_package ][ self::FIELD_LICENSE_SUBSCRIPTION_NUMBER ],
			);
			self::set_option_data( self::OPTION_LICENSES, $licenses );
		}

		// Return the whole object
		echo json_encode( $response_object );

		die();

	}

	/**
	 * Ajax call to verify a license
	 */
	public
	static function ajax_verify_licence() {

		$option_licenses = new OptionLicenses();
		$licenses        = $option_licenses->get_licenses();

		$license_package         = isset( $_POST['data'] ) && isset( $_POST['data'][ self::FIELD_LICENSE_PACKAGE ] ) ? $_POST['data'][ self::FIELD_LICENSE_PACKAGE ] : null;
		$license_activation_uuid = $option_licenses->get_license_activation_uuid( $license_package );

		if ( empty( $license_activation_uuid ) ) {

			$licenses[ $license_package ] = array(
				self::FIELD_LICENSE_SUBSCRIPTION_NUMBER => $licenses[ $license_package ][ self::FIELD_LICENSE_SUBSCRIPTION_NUMBER ],
			);
			self::set_option_data( self::OPTION_LICENSES, $licenses );

			echo json_encode( (object) array(
				'status' => (object) array(
					'state'   => 'ERROR',
					'message' => 'This license activation code is missing. Try to unactivate manually, by signin to your subscription account.'
				)
			) );

			die();
		}

		$managed_solr_server = new OptionManagedSolrServer();
		$response_object     = $managed_solr_server->call_rest_verify_license( self::get_license_api_url(), $license_activation_uuid );

		if ( isset( $response_object ) && OptionManagedSolrServer::is_response_ok( $response_object ) ) {

			if ( isset( $licenses[ $license_package ] ) ) {

				// Remove the license type activation
				$licenses = self::get_option_data( self::OPTION_LICENSES, array() );
				unset( $licenses[ $license_package ][ self::FIELD_NEEDS_VERIFICATION ] );
				self::set_option_data( self::OPTION_LICENSES, $licenses );
			}

		}

		// Return the whole object
		echo json_encode( $response_object );

		die();
	}

	/**
	 * Get all activated licenses
	 *
	 * @return array
	 */
	public static function get_activated_licenses_titles() {

		$results = array();

		$option_licenses = new OptionLicenses();
		$licenses        = $option_licenses->get_licenses();

		foreach ( $licenses as $license_code => $license ) {

			if ( $option_licenses->get_license_is_activated( $license_code ) ) {
				array_push( $results, $option_licenses->get_license_title( $license_code ) );
			}
		}

		return $results;
	}


	/**
	 * Get a license title
	 *
	 * @param $license_code
	 *
	 * @return array
	 */
	public function get_license_title(
		$license_code
	) {

		$license_defs = self::get_license_types();

		return ! empty( $license_defs[ $license_code ] ) && ! empty( $license_defs[ $license_code ][ self::FIELD_LICENSE_TITLE ] ) ? $license_defs[ $license_code ][ self::FIELD_LICENSE_TITLE ] : $license_code;
	}

}

// Register Ajax events
add_action( 'wp_ajax_' . OptionLicenses::AJAX_ACTIVATE_LICENCE, array(
	'OptionLicenses',
	OptionLicenses::AJAX_ACTIVATE_LICENCE
) );

add_action( 'wp_ajax_' . OptionLicenses::AJAX_DEACTIVATE_LICENCE, array(
	'OptionLicenses',
	OptionLicenses::AJAX_DEACTIVATE_LICENCE
) );

add_action( 'wp_ajax_' . OptionLicenses::AJAX_VERIFY_LICENCE, array(
	'OptionLicenses',
	OptionLicenses::AJAX_VERIFY_LICENCE
) );
