<?php

/**
 * Base class for all WPSOLR extensions.
 * An extension is an encapsulation of a plugin that (if configured) might extend some features of WPSOLR.
 */

require_once plugin_dir_path( __FILE__ ) . '../wpsolr-schema.php';

class WpSolrExtensions {

	static $wpsolr_extensions;
	/*
    * Private constants
    */
	const _CONFIG_EXTENSION_DIRECTORY = 'config_extension_directory';
	const _CONFIG_EXTENSION_CLASS_NAME = 'config_extension_class_name';
	const _CONFIG_PLUGIN_CLASS_NAME = 'config_plugin_class_name';
	const _CONFIG_PLUGIN_FUNCTION_NAME = 'config_plugin_function_name';
	const _CONFIG_PLUGIN_CONSTANT_NAME = 'config_plugin_constant_name';
	const _CONFIG_EXTENSION_FILE_PATH = 'config_extension_file_path';
	const _CONFIG_EXTENSION_ADMIN_OPTIONS_FILE_PATH = 'config_extension_admin_options_file_path';
	const _CONFIG_OPTIONS = 'config_extension_options';
	const _CONFIG_OPTIONS_DATA = 'data';
	const _CONFIG_OPTIONS_IS_ACTIVE_FIELD_NAME = 'is_active_field';

	const _SOLR_OR_OPERATOR = ' OR ';
	const _SOLR_AND_OPERATOR = ' AND ';

	const _METHOD_CUSTOM_QUERY = 'set_custom_query';

	/*
	 * Public constants
	 */

	// Option: localization
	const OPTION_INDEXES = 'Indexes';

	// Option: localization
	const OPTION_LOCALIZATION = 'Localization';

	// Extension: Groups
	const EXTENSION_GROUPS = 'Groups';

	// Extension: s2member
	const EXTENSION_S2MEMBER = 'S2Member';

	// Extension: WPML
	const EXTENSION_WPML = 'WPML';

	// Extension: POLYLANG
	const EXTENSION_POLYLANG = 'Polylang';

	// Extension: qTranslate X
	const EXTENSION_QTRANSLATEX = 'qTranslate X';

	// Extension: WooCommerce
	const EXTENSION_WOOCOMMERCE = 'WooCommerce';

	// Extension: Advanced Custom Fields
	const EXTENSION_ACF = 'ACF';

	// Extension: Types
	const EXTENSION_TYPES = 'Types';

	// Extension: Gotosolr hosting
	const OPTION_MANAGED_SOLR_SERVERS = 'Managed Solr Servers';

	// Option: licenses
	const OPTION_LICENSES = 'Licenses';

	// Extension: bbpress
	const EXTENSION_BBPRESS = 'bbpress';

	// Extension: Embed Any Document
	const EXTENSION_EMBED_ANY_DOCUMENT = 'embed any document';

	// Extension: Pdf Embedder
	const EXTENSION_PDF_EMBEDDER = 'pdf embedder';

	// Extension: Google Doc Embedder
	const EXTENSION_GOOGLE_DOC_EMBEDDER = 'google doc embedder';

	/*
	 * Extensions configuration
	 */
	private static $extensions_array = array(
		self::OPTION_INDEXES                =>
			array(
				self::_CONFIG_EXTENSION_CLASS_NAME              => 'OptionIndexes',
				self::_CONFIG_PLUGIN_CLASS_NAME                 => 'OptionIndexes',
				self::_CONFIG_EXTENSION_DIRECTORY               => 'indexes/',
				self::_CONFIG_EXTENSION_FILE_PATH               => 'indexes/option-indexes.php',
				self::_CONFIG_EXTENSION_ADMIN_OPTIONS_FILE_PATH => 'indexes/admin_options.inc.php',
				self::_CONFIG_OPTIONS                           => array(
					self::_CONFIG_OPTIONS_DATA                 => 'wpsolr_solr_indexes',
					self::_CONFIG_OPTIONS_IS_ACTIVE_FIELD_NAME => 'is_extension_active'
				)
			),
		self::OPTION_LOCALIZATION           =>
			array(
				self::_CONFIG_EXTENSION_CLASS_NAME              => 'OptionLocalization',
				self::_CONFIG_PLUGIN_CLASS_NAME                 => 'OptionLocalization',
				self::_CONFIG_EXTENSION_DIRECTORY               => 'localization/',
				self::_CONFIG_EXTENSION_FILE_PATH               => 'localization/option-localization.php',
				self::_CONFIG_EXTENSION_ADMIN_OPTIONS_FILE_PATH => 'localization/admin_options.inc.php',
				self::_CONFIG_OPTIONS                           => array(
					self::_CONFIG_OPTIONS_DATA                 => 'wdm_solr_localization_data',
					self::_CONFIG_OPTIONS_IS_ACTIVE_FIELD_NAME => 'is_extension_active'
				)
			),
		self::EXTENSION_GROUPS              =>
			array(
				self::_CONFIG_EXTENSION_CLASS_NAME              => 'PluginGroups',
				self::_CONFIG_PLUGIN_CLASS_NAME                 => 'Groups_WordPress',
				self::_CONFIG_EXTENSION_DIRECTORY               => 'groups/',
				self::_CONFIG_EXTENSION_FILE_PATH               => 'groups/plugin-groups.php',
				self::_CONFIG_EXTENSION_ADMIN_OPTIONS_FILE_PATH => 'groups/admin_options.inc.php',
				self::_CONFIG_OPTIONS                           => array(
					self::_CONFIG_OPTIONS_DATA                 => 'wdm_solr_extension_groups_data',
					self::_CONFIG_OPTIONS_IS_ACTIVE_FIELD_NAME => 'is_extension_active'
				)
			),
		self::EXTENSION_S2MEMBER            =>
			array(
				self::_CONFIG_EXTENSION_CLASS_NAME              => 'PluginS2Member',
				self::_CONFIG_PLUGIN_CLASS_NAME                 => 'c_ws_plugin__s2member_utils_s2o',
				self::_CONFIG_EXTENSION_DIRECTORY               => 's2member/',
				self::_CONFIG_EXTENSION_FILE_PATH               => 's2member/plugin-s2member.php',
				self::_CONFIG_EXTENSION_ADMIN_OPTIONS_FILE_PATH => 's2member/admin_options.inc.php',
				self::_CONFIG_OPTIONS                           => array(
					self::_CONFIG_OPTIONS_DATA                 => 'wdm_solr_extension_s2member_data',
					self::_CONFIG_OPTIONS_IS_ACTIVE_FIELD_NAME => 'is_extension_active'
				)
			),
		self::EXTENSION_WPML                =>
			array(
				self::_CONFIG_EXTENSION_CLASS_NAME              => 'PluginWpml',
				self::_CONFIG_PLUGIN_CLASS_NAME                 => 'SitePress',
				self::_CONFIG_EXTENSION_DIRECTORY               => 'wpml/',
				self::_CONFIG_EXTENSION_FILE_PATH               => 'wpml/plugin-wpml.php',
				self::_CONFIG_EXTENSION_ADMIN_OPTIONS_FILE_PATH => 'wpml/admin_options.inc.php',
				self::_CONFIG_OPTIONS                           => array(
					self::_CONFIG_OPTIONS_DATA                 => 'wdm_solr_extension_wpml_data',
					self::_CONFIG_OPTIONS_IS_ACTIVE_FIELD_NAME => 'is_extension_active'
				)
			),
		self::EXTENSION_POLYLANG            =>
			array(
				self::_CONFIG_EXTENSION_CLASS_NAME              => 'PluginPolylang',
				self::_CONFIG_PLUGIN_FUNCTION_NAME              => 'pll_get_post',
				self::_CONFIG_EXTENSION_DIRECTORY               => 'polylang/',
				self::_CONFIG_EXTENSION_FILE_PATH               => 'polylang/plugin-polylang.php',
				self::_CONFIG_EXTENSION_ADMIN_OPTIONS_FILE_PATH => 'polylang/admin_options.inc.php',
				self::_CONFIG_OPTIONS                           => array(
					self::_CONFIG_OPTIONS_DATA                 => 'wdm_solr_extension_polylang_data',
					self::_CONFIG_OPTIONS_IS_ACTIVE_FIELD_NAME => 'is_extension_active'
				)
			),
		self::EXTENSION_QTRANSLATEX         =>
			array(
				self::_CONFIG_EXTENSION_CLASS_NAME              => 'PluginQTranslateX',
				self::_CONFIG_PLUGIN_CONSTANT_NAME              => 'QTRANSLATE_FILE',
				self::_CONFIG_EXTENSION_DIRECTORY               => 'qtranslate-x/',
				self::_CONFIG_EXTENSION_FILE_PATH               => 'qtranslate-x/plugin-qtranslatex.php',
				self::_CONFIG_EXTENSION_ADMIN_OPTIONS_FILE_PATH => 'qtranslate-x/admin_options.inc.php',
				self::_CONFIG_OPTIONS                           => array(
					self::_CONFIG_OPTIONS_DATA                 => 'wdm_solr_extension_qtranslatex_data',
					self::_CONFIG_OPTIONS_IS_ACTIVE_FIELD_NAME => 'is_extension_active'
				)
			),
		self::OPTION_MANAGED_SOLR_SERVERS   =>
			array(
				self::_CONFIG_EXTENSION_CLASS_NAME              => 'OptionManagedSolrServers',
				self::_CONFIG_PLUGIN_FUNCTION_NAME              => 'OptionManagedSolrServers',
				self::_CONFIG_EXTENSION_DIRECTORY               => 'managed-solr-servers/',
				self::_CONFIG_EXTENSION_FILE_PATH               => 'managed-solr-servers/option-managed-solr-servers.php',
				self::_CONFIG_EXTENSION_ADMIN_OPTIONS_FILE_PATH => 'managed-solr-servers/admin_options.inc.php',
				self::_CONFIG_OPTIONS                           => array(
					self::_CONFIG_OPTIONS_DATA                 => 'wdm_solr_extension_managed_solr_servers_data',
					self::_CONFIG_OPTIONS_IS_ACTIVE_FIELD_NAME => 'is_extension_active'
				)
			),
		self::EXTENSION_WOOCOMMERCE         =>
			array(
				self::_CONFIG_EXTENSION_CLASS_NAME              => 'PluginWooCommerce',
				self::_CONFIG_PLUGIN_CLASS_NAME                 => 'WooCommerce',
				self::_CONFIG_EXTENSION_DIRECTORY               => 'woocommerce/',
				self::_CONFIG_EXTENSION_FILE_PATH               => 'woocommerce/plugin-woocommerce.php',
				self::_CONFIG_EXTENSION_ADMIN_OPTIONS_FILE_PATH => 'woocommerce/admin_options.inc.php',
				self::_CONFIG_OPTIONS                           => array(
					self::_CONFIG_OPTIONS_DATA                 => 'wdm_solr_extension_woocommerce_data',
					self::_CONFIG_OPTIONS_IS_ACTIVE_FIELD_NAME => 'is_extension_active'
				)
			),
		self::EXTENSION_ACF                 =>
			array(
				self::_CONFIG_EXTENSION_CLASS_NAME              => 'PluginAcf',
				self::_CONFIG_PLUGIN_CLASS_NAME                 => 'acf',
				self::_CONFIG_EXTENSION_DIRECTORY               => 'acf/',
				self::_CONFIG_EXTENSION_FILE_PATH               => 'acf/plugin-acf.php',
				self::_CONFIG_EXTENSION_ADMIN_OPTIONS_FILE_PATH => 'acf/admin_options.inc.php',
				self::_CONFIG_OPTIONS                           => array(
					self::_CONFIG_OPTIONS_DATA                 => 'wdm_solr_extension_acf_data',
					self::_CONFIG_OPTIONS_IS_ACTIVE_FIELD_NAME => 'is_extension_active'
				)
			),
		self::EXTENSION_TYPES               =>
			array(
				self::_CONFIG_EXTENSION_CLASS_NAME              => 'PluginTypes',
				self::_CONFIG_PLUGIN_CLASS_NAME                 => 'WPCF_Field',
				self::_CONFIG_EXTENSION_DIRECTORY               => 'types/',
				self::_CONFIG_EXTENSION_FILE_PATH               => 'types/plugin-types.php',
				self::_CONFIG_EXTENSION_ADMIN_OPTIONS_FILE_PATH => 'types/admin_options.inc.php',
				self::_CONFIG_OPTIONS                           => array(
					self::_CONFIG_OPTIONS_DATA                 => 'wdm_solr_extension_types_data',
					self::_CONFIG_OPTIONS_IS_ACTIVE_FIELD_NAME => 'is_extension_active'
				)
			),
		self::OPTION_LICENSES               =>
			array(
				self::_CONFIG_EXTENSION_CLASS_NAME              => 'OptionLicenses',
				self::_CONFIG_PLUGIN_CLASS_NAME                 => 'OptionLicenses',
				self::_CONFIG_EXTENSION_DIRECTORY               => 'licenses/',
				self::_CONFIG_EXTENSION_FILE_PATH               => 'licenses/option-licenses.php',
				self::_CONFIG_EXTENSION_ADMIN_OPTIONS_FILE_PATH => 'licenses/admin_options.inc.php',
				self::_CONFIG_OPTIONS                           => array(
					self::_CONFIG_OPTIONS_DATA                 => 'wpsolr_licenses',
					self::_CONFIG_OPTIONS_IS_ACTIVE_FIELD_NAME => 'is_extension_active'
				)
			),
		self::EXTENSION_BBPRESS             =>
			array(
				self::_CONFIG_EXTENSION_CLASS_NAME              => 'PluginBbPress',
				self::_CONFIG_PLUGIN_CLASS_NAME                 => 'bbPress',
				self::_CONFIG_EXTENSION_DIRECTORY               => 'bbpress/',
				self::_CONFIG_EXTENSION_FILE_PATH               => 'bbpress/plugin-bbpress.php',
				self::_CONFIG_EXTENSION_ADMIN_OPTIONS_FILE_PATH => 'bbpress/admin_options.inc.php',
				self::_CONFIG_OPTIONS                           => array(
					self::_CONFIG_OPTIONS_DATA                 => 'wdm_solr_extension_bbpress_data',
					self::_CONFIG_OPTIONS_IS_ACTIVE_FIELD_NAME => 'is_extension_active'
				)
			),
		self::EXTENSION_EMBED_ANY_DOCUMENT  =>
			array(
				self::_CONFIG_EXTENSION_CLASS_NAME              => 'PluginEmbedAnyDocument',
				self::_CONFIG_PLUGIN_CLASS_NAME                 => 'Awsm_embed',
				self::_CONFIG_EXTENSION_DIRECTORY               => 'embed-any-document/',
				self::_CONFIG_EXTENSION_FILE_PATH               => 'embed-any-document/plugin-embed-any-document.php',
				self::_CONFIG_EXTENSION_ADMIN_OPTIONS_FILE_PATH => 'embed-any-document/admin_options.inc.php',
				self::_CONFIG_OPTIONS                           => array(
					self::_CONFIG_OPTIONS_DATA                 => 'wdm_solr_extension_embed_any_document_data',
					self::_CONFIG_OPTIONS_IS_ACTIVE_FIELD_NAME => 'is_extension_active'
				)
			),
		self::EXTENSION_PDF_EMBEDDER        =>
			array(
				self::_CONFIG_EXTENSION_CLASS_NAME              => 'PluginPdfEmbedder',
				self::_CONFIG_PLUGIN_CLASS_NAME                 => 'pdfemb_basic_pdf_embedder',
				self::_CONFIG_EXTENSION_DIRECTORY               => 'pdf-embedder/',
				self::_CONFIG_EXTENSION_FILE_PATH               => 'pdf-embedder/plugin-pdf-embedder.php',
				self::_CONFIG_EXTENSION_ADMIN_OPTIONS_FILE_PATH => 'pdf-embedder/admin_options.inc.php',
				self::_CONFIG_OPTIONS                           => array(
					self::_CONFIG_OPTIONS_DATA                 => 'wdm_solr_extension_pdf_embedder_data',
					self::_CONFIG_OPTIONS_IS_ACTIVE_FIELD_NAME => 'is_extension_active'
				)
			),
		self::EXTENSION_GOOGLE_DOC_EMBEDDER =>
			array(
				self::_CONFIG_EXTENSION_CLASS_NAME              => 'PluginGoogleDocEmbedder',
				self::_CONFIG_PLUGIN_CONSTANT_NAME              => 'GDE_PLUGIN_DIR',
				self::_CONFIG_EXTENSION_DIRECTORY               => 'google-doc-embedder/',
				self::_CONFIG_EXTENSION_FILE_PATH               => 'google-doc-embedder/plugin-google-doc-embedder.php',
				self::_CONFIG_EXTENSION_ADMIN_OPTIONS_FILE_PATH => 'google-doc-embedder/admin_options.inc.php',
				self::_CONFIG_OPTIONS                           => array(
					self::_CONFIG_OPTIONS_DATA                 => 'wdm_solr_extension_google_doc_embedder_data',
					self::_CONFIG_OPTIONS_IS_ACTIVE_FIELD_NAME => 'is_extension_active'
				)
			)
	);

	/*
	 * Array of active extension objects
	 */
	private $extension_objects = array();

	/**
	 * Factory to load extensions
	 * @return WpSolrExtensions
	 */
	static function load() {

		if ( ! isset( static::$wpsolr_extensions ) ) {

			static::$wpsolr_extensions = new self();
		}
	}

	/**
	 * Constructor.
	 */
	function __construct() {

		// Instantiate active extensions.
		$this->extension_objects = $this->instantiate_active_extension_objects();

	}

	/**
	 * Include a file with a set of parameters.
	 * All other parameters are not passed, because they are out of the function scope.
	 *
	 * @param $pg File to include
	 * @param $vars Parameters to pass to the file
	 */
	public static function require_with( $pg, $vars = null ) {

		if ( isset( $vars ) ) {
			extract( $vars );
		}

		require $pg;
	}

	/**
	 * Instantiate all active extension classes
	 *
	 * @return array extension objects instantiated
	 */
	private function instantiate_active_extension_objects() {

		$extension_objects = array();

		foreach ( $this->get_extensions_active() as $extension_class_name ) {

			$extension_objects[] = new $extension_class_name();
		}

		return $extension_objects;
	}

	/**
	 * Returns all extension class names which plugins are active. And load them.
	 *
	 * @return array[string]
	 */
	public function get_extensions_active() {
		$results = array();

		foreach ( self::$extensions_array as $key => $class ) {

			if ( $this->require_once_wpsolr_extension( $key, false ) ) {

				$results[] = $class[ self::_CONFIG_EXTENSION_CLASS_NAME ];
			}
		}

		return $results;
	}

	/**
	 * Include the admin options extension file.
	 *
	 * @param string $extension
	 *
	 * @return bool
	 */
	public static function require_once_wpsolr_extension_admin_options( $extension ) {

		// Configuration array of $extension
		$extension_config_array = self::$extensions_array[ $extension ];

		// Called from admin: we active the extension, whatever.
		require_once plugin_dir_path( __FILE__ ) . $extension_config_array[ self::_CONFIG_EXTENSION_ADMIN_OPTIONS_FILE_PATH ];

	}

	/**
	 * Is the extension's plugin active ?
	 *
	 * @param $extension
	 *
	 * @return bool
	 */
	public static function is_plugin_active( $extension ) {

		// Configuration array of $extension
		$extension_config_array = self::$extensions_array[ $extension ];

		// Is extension's plugin installed and activated ?
		if ( isset( $extension_config_array[ self::_CONFIG_PLUGIN_CLASS_NAME ] ) ) {

			return class_exists( $extension_config_array[ self::_CONFIG_PLUGIN_CLASS_NAME ] );

		} else if ( isset( $extension_config_array[ self::_CONFIG_PLUGIN_FUNCTION_NAME ] ) ) {

			return function_exists( $extension_config_array[ self::_CONFIG_PLUGIN_FUNCTION_NAME ] );

		} else if ( isset( $extension_config_array[ self::_CONFIG_PLUGIN_CONSTANT_NAME ] ) ) {

			return defined( $extension_config_array[ self::_CONFIG_PLUGIN_CONSTANT_NAME ] );
		}

		return false;
	}

	public static function update_custom_field_capabilities( $custom_field_name ) {

		// Get options contening custom fields
		$array_wdm_solr_form_data = get_option( 'wdm_solr_form_data' );

		// is extension active checked in options ?
		$extension_is_active = self::is_extension_option_activate( self::EXTENSION_GROUPS );


		if ( $extension_is_active
		     && ! self::get_custom_field_capabilities( $custom_field_name )
		     && isset( $array_wdm_solr_form_data )
		     && isset( $array_wdm_solr_form_data['cust_fields'] )
		) {

			$custom_fields = explode( ',', $array_wdm_solr_form_data['cust_fields'] );

			if ( ! isset( $custom_fields[ $custom_field_name ] ) ) {

				$custom_fields[ $custom_field_name ] = $custom_field_name;

				$custom_fields_str = implode( ',', $custom_fields );

				$array_wdm_solr_form_data['cust_fields'] = $custom_fields_str;

				update_option( 'wdm_solr_form_data', $array_wdm_solr_form_data );
			}
		}
	}

	/**
	 * Is the extension activated ?
	 *
	 * @param string $extension
	 *
	 * @return bool
	 */
	public static function is_extension_option_activate( $extension ) {

		// Configuration array of $extension
		$extension_config_array = self::$extensions_array[ $extension ];

		// Configuration not set, return
		if ( ! is_array( $extension_config_array ) ) {
			return false;
		}

		// Configuration options array: setup in extension options tab admin
		$extension_options_array = get_option( $extension_config_array[ self::_CONFIG_OPTIONS ][ self::_CONFIG_OPTIONS_DATA ] );

		// Configuration option says that user did not choose to active this extension: return
		if ( isset( $extension_options_array ) && isset( $extension_options_array[ $extension_config_array[ self::_CONFIG_OPTIONS ][ self::_CONFIG_OPTIONS_IS_ACTIVE_FIELD_NAME ] ] ) ) {
			return true;
		}

		return false;
	}

	public
	static function get_custom_field_capabilities(
		$custom_field_name
	) {

		// Get custom fields selected for indexing
		$array_options     = get_option( 'wdm_solr_form_data' );
		$array_cust_fields = explode( ',', $array_options['cust_fields'] );

		if ( ! is_array( $array_cust_fields ) ) {
			return false;
		}

		return false !== array_search( $custom_field_name, $array_cust_fields );
	}


	/*
	 * If extension is active, check its custom field in indexing options
	 */

	/**
	 * Include the extension file.
	 * If called from admin, always do.
	 * Else, do it if the extension options say so, and the extension's plugin is activated.
	 *
	 * @param string $extension
	 * @param bool $is_admin
	 *
	 * @return bool
	 */
	public static function require_once_wpsolr_extension( $extension, $is_admin = false ) {

		// Configuration array of $extension
		$extension_config_array = self::$extensions_array[ $extension ];

		if ( $is_admin ) {
			// Called from admin: we active the extension, whatever.
			require_once plugin_dir_path( __FILE__ ) . $extension_config_array[ self::_CONFIG_EXTENSION_FILE_PATH ];

			return true;
		}

		// Configuration not set, return
		if ( ! is_array( $extension_config_array ) ) {
			return false;
		}

		// Configuration options array: setup in extension options tab admin
		$extension_options_array = get_option( $extension_config_array[ self::_CONFIG_OPTIONS ][ self::_CONFIG_OPTIONS_DATA ] );

		// Configuration option says that user did not choose to active this extension: return
		if ( ! isset( $extension_options_array ) || ! isset( $extension_options_array[ $extension_config_array[ self::_CONFIG_OPTIONS ][ self::_CONFIG_OPTIONS_IS_ACTIVE_FIELD_NAME ] ] ) ) {
			return false;
		}

		// Is extension's plugin installed and activated ?
		$result = self::is_plugin_active( $extension );

		if ( $result ) {
			// Load extension's plugin
			require_once plugin_dir_path( __FILE__ ) . $extension_config_array[ self::_CONFIG_EXTENSION_FILE_PATH ];
		}

		return $result;
	}

	/**
	 * Get the option data of an extension
	 *
	 * @param $extension
	 *
	 * @return mixed
	 */
	public static function get_option_data( $extension, $default = false ) {

		return get_option( self::get_option_name( $extension ), $default );
	}


	/**
	 * Get the option name of an extension
	 *
	 * @param $extension
	 *
	 * @return mixed
	 */
	public static function get_option_name( $extension ) {

		return self::$extensions_array[ $extension ][ self::_CONFIG_OPTIONS ][ self::_CONFIG_OPTIONS_DATA ];
	}

	/**
	 * Set the option value of an extension
	 *
	 * @param $extension
	 * @param $option_value
	 *
	 * @return mixed
	 */
	public static function set_option_data( $extension, $option_value ) {

		return update_option( self::$extensions_array[ $extension ][ self::_CONFIG_OPTIONS ][ self::_CONFIG_OPTIONS_DATA ], $option_value );
	}

	/**
	 * Get the extension template path
	 *
	 * @param $extension
	 *
	 * @param $template_file_name
	 *
	 * @return string Template file path
	 *
	 */
	public static function get_option_template_file( $extension, $template_file_name ) {

		return plugin_dir_path( __FILE__ ) . self::$extensions_array[ $extension ][ self::_CONFIG_EXTENSION_DIRECTORY ] . 'templates/' . $template_file_name;
	}

	/**
	 * Get the extension file
	 *
	 * @param $extension
	 *
	 * @param $file_name
	 *
	 * @return string File path
	 *
	 */
	public static function get_option_file( $extension, $file_name ) {

		return plugin_dir_path( __FILE__ ) . self::$extensions_array[ $extension ][ self::_CONFIG_EXTENSION_DIRECTORY ] . $file_name;
	}

	/*
	 * Templates methods
	 */

	public static function extract_form_data( $is_submit, $fields ) {

		$form_data = array();

		$is_error = false;

		foreach ( $fields as $key => $field ) {

			$value = isset( $_POST[ $key ] ) ? $_POST[ $key ] : $field['default_value'];
			$error = '';

			// Check format errors id it is a form post (submit)
			if ( $is_submit ) {

				$error = '';

				if ( isset( $field['can_be_empty'] ) && ! $field['can_be_empty'] ) {
					$error = empty( $value ) ? 'This field cannot be empty.' : '';
				}

				if ( isset( $field['is_email'] ) ) {
					$error = is_email( $value ) ? '' : 'This does not look like an email address.';
				}
			}

			$is_error = $is_error || ( '' != $error );

			$form_data[ $key ] = array( 'value' => $value, 'error' => $error );
		}

		// Is there an error in any field ?
		$form_data['is_error'] = $is_error;

		return $form_data;
	}

	/**
	 * Get the dynamic strings to translate among the group data of all extensions translatable.
	 *
	 * @return array Translations
	 */
	public static function extract_strings_to_translate_for_all_extensions() {

		$translations = array();

		// Translate facet labels
		$labels = WPSOLR_Global::getOption()->get_facets_labels();
		if ( is_array( $labels ) && ! empty( $labels ) ) {
			foreach ( $labels as $facet_name => $facet_label ) {
				if ( ! empty( $facet_label ) ) {
					$translation           = array();
					$translation['domain'] = WPSOLR_Option::TRANSLATION_DOMAIN_FACET_LABEL;
					$translation['name']   = $facet_name;
					$translation['text']   = $facet_label;

					array_push( $translations, $translation );
				}
			}
		}

		// Translate facet items labels
		$labels = WPSOLR_Global::getOption()->get_facets_items_labels();
		if ( is_array( $labels ) && ! empty( $labels ) ) {
			foreach ( $labels as $facet_name => $facet_items_labels ) {
				foreach ( $facet_items_labels as $facet_item_name => $facet_item_label ) {
					if ( ! empty( $facet_item_label ) ) {
						$translation           = array();
						$translation['domain'] = WPSOLR_Option::TRANSLATION_DOMAIN_FACET_LABEL;
						$translation['name']   = $facet_item_name;
						$translation['text']   = $facet_item_label;

						array_push( $translations, $translation );
					}
				}
			}
		}

		if ( count( $translations ) > 0 ) {

			// Translate
			do_action( WpSolrFilters::ACTION_TRANSLATION_REGISTER_STRINGS,
				array(
					'translations' => $translations
				)
			);
		}

	}

}