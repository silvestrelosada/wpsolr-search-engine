<?php
/**
 * Plugin Name: WPSOLR
 * Description: Search for WordPress, WooCommerce, bbPress that never gets stuck - WPSOLR
 * Version: 13.3
 * Author: wpsolr
 * Plugin URI: http://www.wpsolr.com
 * License: GPL2
 */

// Constants
define( 'WPSOLR_PLUGIN_DIR', dirname( __FILE__ ) );
define( 'WPSOLR_PLUGIN_FILE', __FILE__ );
define( 'WPSOLR_PLUGIN_VERSION', '13.2' );

// Composer autoloader
require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

require_once 'ajax_solr_services.php';
require_once 'dashboard_settings.php';
require_once 'autocomplete.php';

/* Include Solr clients */
require_once 'classes/solr/wpsolr-index-solr-client.php';
require_once 'classes/solr/wpsolr-search-solr-client.php';

/* WPSOLR global factory */
require_once 'classes/utilities/WPSOLR_Global.php';
/* UI Facets */
require_once 'classes/ui/WPSOLR_UI_Facets.php';

/* Register Solr settings from dashboard
 * Add menu page in dashboard - Solr settings
 * Add solr settings- solr host, post and path
 *
 */
add_action( 'wp_head', 'check_default_options_and_function' );
add_action( 'admin_menu', 'fun_add_solr_settings' );
add_action( 'admin_init', 'wpsolr_admin_init' );
add_action( 'wp_enqueue_scripts', 'my_enqueue' );

// Register WpSolr widgets when current theme's search is used.
if ( WPSOLR_Global::getOption()->get_search_is_use_current_theme_search_template() ) {
	require_once 'classes/ui/widget/WPSOLR_Widget.php';
	WPSOLR_Widget::Autoload();
}

if ( is_admin() ) {
	/*
	 * Register metabox
	 */
	require_once 'classes/metabox/wpsolr-metabox.php';
	WPSOLR_Metabox::register();
}

/*
 * Display Solr errors in admin when a save on a post can't index to Solr
 */
function solr_post_save_admin_notice() {
	if ( $out = get_transient( get_current_user_id() . 'error_solr_post_save_admin_notice' ) ) {
		delete_transient( get_current_user_id() . 'error_solr_post_save_admin_notice' );
		echo "<div class=\"error wpsolr_admin_notice_error\"><p>(WPSOLR) Error while indexing this post/page in Solr:<br><br>$out</p></div>";
	}

	if ( $out = get_transient( get_current_user_id() . 'updated_solr_post_save_admin_notice' ) ) {
		delete_transient( get_current_user_id() . 'updated_solr_post_save_admin_notice' );
		echo "<div class=\"updated wpsolr_admin_notice_updated\"><p>(WPSOLR) $out</p></div>";
	}

	if ( $out = get_transient( get_current_user_id() . 'wpsolr_some_languages_have_no_solr_index_admin_notice' ) ) {
		delete_transient( get_current_user_id() . 'wpsolr_some_languages_have_no_solr_index_admin_notice' );
		echo "<div class=\"error wpsolr_admin_notice_error\"><p>(WPSOLR) $out</p></div>";
	}

}

add_action( 'admin_notices', "solr_post_save_admin_notice" );

if ( WPSOLR_Global::getOption()->get_index_is_real_time() ) {
	// Index as soon as a save is performed.
	add_action( 'save_post', 'add_remove_document_to_solr_index', 11, 3 );
	add_action( 'add_attachment', 'add_attachment_to_solr_index', 10, 3 );
	add_action( 'edit_attachment', 'add_attachment_to_solr_index', 10, 3 );
	add_action( 'delete_attachment', 'delete_attachment_to_solr_index', 10, 3 );


	if ( WPSOLR_Global::getOption()->get_index_are_comments_indexed() ) {
		// new comment
		add_action( 'comment_post', 'add_remove_comment_to_solr_index', 11, 1 );

		// approved, unaproved, trashed, untrashed, spammed, unspammed
		add_action( 'wp_set_comment_status', 'add_remove_comment_to_solr_index', 11, 1 );
	}
}

/**
 * Reindex a post when one of it's comment is updated.
 *
 * @param $comment_id
 */
function add_remove_comment_to_solr_index( $comment_id ) {

	$comment = get_comment( $comment_id );

	if ( ! empty( $comment ) ) {

		add_remove_document_to_solr_index( $comment->comment_post_ID, get_post( $comment->comment_post_ID ) );
	}
}

/**
 * Add/remove document to/from Solr index when status changes to/from published
 * We have to use action 'save_post', as it is used by other plugins to trigger meta boxes save
 *
 * @param $post_id
 * @param $post
 */
function add_remove_document_to_solr_index( $post_id, $post ) {

	// If this is just a revision, don't go on.
	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}

	// If this is just a new post opened in editor, don't go on.
	if ( 'auto-draft' === $post->post_status ) {
		return;
	}

	// Delete previous message first
	delete_transient( get_current_user_id() . 'updated_solr_post_save_admin_notice' );

	try {
		if ( 'publish' === $post->post_status ) {
			// post published, add/update it from Solr index

			$solr = WPSolrIndexSolrClient::create_from_post( $post );

			$results = $solr->index_data( 1, $post );

			// Display confirmation in admin, if one doc at least has been indexed
			if ( ! empty( $results ) && ! empty( $results['nb_results'] ) ) {

				set_transient( get_current_user_id() . 'updated_solr_post_save_admin_notice', sprintf( '%s updated in index \'%s\'', ucfirst( $post->post_type ), $solr->index['index_name'] ) );
			}

		} else {

			// post unpublished, remove it from Solr index
			$solr = WPSolrIndexSolrClient::create_from_post( $post );

			$solr->delete_document( $post );

			// Display confirmation in admin
			set_transient( get_current_user_id() . 'updated_solr_post_save_admin_notice', sprintf( '%s removed from index \'%s\'', ucfirst( $post->post_type ), $solr->index['index_name'] ) );
		}

	} catch ( Exception $e ) {
		set_transient( get_current_user_id() . 'error_solr_post_save_admin_notice', htmlentities( $e->getMessage() ) );
	}

}

/*
 * Add an attachment to Solr
 */
function add_attachment_to_solr_index( $attachment_id ) {

	// Index the new attachment
	try {
		$solr = WPSolrIndexSolrClient::create();

		$results = $solr->index_data( 1, get_post( $attachment_id ) );

		// Display confirmation in admin, if one doc at least has been indexed
		if ( ! empty( $results ) && ! empty( $results['nb_results'] ) ) {

			set_transient( get_current_user_id() . 'updated_solr_post_save_admin_notice', 'Media file uploaded to Solr' );
		}

	} catch ( Exception $e ) {

		set_transient( get_current_user_id() . 'error_solr_post_save_admin_notice', htmlentities( $e->getMessage() ) );
	}

}

/*
 * Delete an attachment from Solr
 */
function delete_attachment_to_solr_index( $attachment_id ) {

	// Remove the attachment from Solr index
	try {
		$solr = WPSolrIndexSolrClient::create();

		$solr->delete_document( get_post( $attachment_id ) );

		set_transient( get_current_user_id() . 'updated_solr_post_save_admin_notice', 'Attachment deleted from Solr' );

	} catch ( Exception $e ) {

		set_transient( get_current_user_id() . 'error_solr_post_save_admin_notice', htmlentities( $e->getMessage() ) );
	}

}


/* Replace WordPress search
 * Default WordPress will be replaced with Solr search
 */


function check_default_options_and_function() {

	if ( WPSOLR_Global::getOption()->get_search_is_replace_default_wp_search() && ! WPSOLR_Global::getOption()->get_search_is_use_current_theme_search_template() ) {

		add_filter( 'get_search_form', 'solr_search_form' );

	}
}

add_filter( 'template_include', 'portfolio_page_template', 99 );
function portfolio_page_template( $template ) {

	if ( is_page( WPSolrSearchSolrClient::_SEARCH_PAGE_SLUG ) ) {
		$new_template = locate_template( WPSolrSearchSolrClient::_SEARCH_PAGE_TEMPLATE );
		if ( '' != $new_template ) {
			return $new_template;
		}
	}

	return $template;
}

/* Create default page template for search results
*/
add_shortcode( 'solr_search_shortcode', 'fun_search_indexed_data' );
add_shortcode( 'solr_form', 'fun_dis_search' );
function fun_dis_search() {
	echo solr_search_form();
}


register_activation_hook( __FILE__, 'my_register_activation_hook' );
function my_register_activation_hook() {

	/*
	 * Migrate old data on plugin update
	 */
	WpSolrExtensions::require_once_wpsolr_extension( WpSolrExtensions::OPTION_INDEXES, true );
	$option_object = new OptionIndexes();
	$option_object->migrate_data_from_v4_9();
}


add_action( 'admin_notices', 'curl_dependency_check' );
function curl_dependency_check() {
	if ( ! in_array( 'curl', get_loaded_extensions() ) ) {

		echo "<div class='updated'><p><b>cURL</b> is not installed on your server. In order to make <b>'Solr for WordPress'</b> plugin work, you need to install <b>cURL</b> on your server </p></div>";
	}


}


function solr_search_form() {

	ob_start();

	// Load current theme's wpsolr search form if it exists
	$search_form_template = locate_template( 'wpsolr-search-engine/searchform.php' );
	if ( '' != $search_form_template ) {

		require( $search_form_template );
		$form = ob_get_clean();

	} else {

		$ad_url = admin_url();

		if ( isset( $_GET[ WPSOLR_Query_Parameters::SEARCH_PARAMETER_Q ] ) ) {
			$search_que = $_GET[ WPSOLR_Query_Parameters::SEARCH_PARAMETER_Q ];
		} else if ( isset( $_GET[ WPSOLR_Query_Parameters::SEARCH_PARAMETER_SEARCH ] ) ) {
			$search_que = $_GET[ WPSOLR_Query_Parameters::SEARCH_PARAMETER_SEARCH ];
		} else {
			$search_que = '';
		}

		// Get localization options
		$localization_options = OptionLocalization::get_options();

		$wdm_typehead_request_handler = !empty( $_GET['nofacet'] ) ?
			'wdm_return_solr_rows' : 'wdm_return_facet_solr_rows';

		$get_page_info = WPSolrSearchSolrClient::get_search_page();
		$ajax_nonce    = wp_create_nonce( "nonce_for_autocomplete" );


		$url = get_permalink( $get_page_info->ID );
		// Filter the search page url. Used for multi-language search forms.
		$url = apply_filters( WpSolrFilters::WPSOLR_FILTER_SEARCH_PAGE_URL, $url, $get_page_info->ID );

		$form = "<div class='cls_search' style='width:100%'><form action='$url' method='get'  class='search-frm2' >";
		$form .= '<input type="hidden" value="' . $wdm_typehead_request_handler . '" id="path_to_fold">';
		$form .= '<input type="hidden"  id="ajax_nonce" value="' . $ajax_nonce . '">';

		$form .= '<input type="hidden" value="' . $ad_url . '" id="path_to_admin">';
		$form .= '<input type="hidden" value="' . $search_que . '" id="search_opt">';
		$form .= '
       <div class="ui-widget search-box">
 	<input type="hidden"  id="ajax_nonce" value="' . $ajax_nonce . '">
        <input type="text" placeholder="' . OptionLocalization::get_term( $localization_options, 'search_form_edit_placeholder' ) . '" value="' . $search_que . '" name="' . WPSOLR_Query_Parameters::SEARCH_PARAMETER_Q . '" id="search_que" class="' . WPSOLR_Option::OPTION_SEARCH_SUGGEST_CLASS_DEFAULT . ' sfl1" autocomplete="off"/>
	<input type="submit" value="' . OptionLocalization::get_term( $localization_options, 'search_form_button_label' ) . '" id="searchsubmit" style="position:relative;width:auto">
        <div style="clear:both"></div>
        </div>
	</div>
       </form>';
	}

	return $form;


}

add_action( 'after_setup_theme', 'wpsolr_after_setup_theme' ); // Some plugins are loaded with the theme, like ACF. We need to wait till then.
function wpsolr_after_setup_theme() {

	// Load active extensions
	WpSolrExtensions::load();

	/*
	 * Load WPSOLR text domain to the Wordpress languages plugin directory (WP_LANG_DIR/plugins)
	 * Copy your .mo files there
	 * Example: /htdocs/wp-includes/languages/plugins/wpsolr-fr_FR.mo or /htdocs/wp-content/languages/plugins/wpsolr-fr_FR.mo
	 * You can find our template file in this plugin's /languages/wpsolr.pot file
	 */
	load_plugin_textdomain( 'wpsolr', false, false );

	/**
	 * Load dynamic string translations
	 */
	if ( is_admin() ) {

		// Load all string translations for all data managed by all extensions
		WpSolrExtensions::extract_strings_to_translate_for_all_extensions();
	}

}

function my_enqueue() {

	if ( ! WPSOLR_Global::getOption()->get_search_is_prevent_loading_front_end_css() ) {
		wp_enqueue_style( 'solr_auto_css', plugins_url( 'css/bootstrap.min.css', __FILE__ ), array(), WPSOLR_PLUGIN_VERSION );
		wp_enqueue_style( 'solr_frontend', plugins_url( 'css/style.css', __FILE__ ), array(), WPSOLR_PLUGIN_VERSION );
	}

	if ( ! WPSOLR_Global::getOption()->get_search_is_galaxy_slave() ) {
		// In this mode, suggestions do not work, as suggestions cannot be filtered by site.
		wp_enqueue_script( 'solr_auto_js1', plugins_url( 'js/bootstrap-typeahead.js', __FILE__ ), array( 'jquery' ), WPSOLR_PLUGIN_VERSION, true );
	}

	// Url utilities to manipulate the url parameters
	wp_enqueue_script( 'urljs', plugins_url( 'bower_components/jsurl/url.min.js', __FILE__ ), array( 'jquery' ), WPSOLR_PLUGIN_VERSION, true );
	wp_enqueue_script( 'autocomplete', plugins_url( 'js/autocomplete_solr.js', __FILE__ ), array(
		'solr_auto_js1',
		'urljs'
	), WPSOLR_PLUGIN_VERSION, true );
	wp_localize_script( 'autocomplete', 'wp_localize_script_autocomplete',
		array(
			'ajax_url'                     => admin_url( 'admin-ajax.php' ),
			'is_show_url_parameters'       => WPSOLR_Global::getOption()->get_search_is_ajax_with_url_parameters(),
			'is_url_redirect'              => WPSOLR_Global::getOption()->get_search_is_use_current_theme_search_template(),
			'SEARCH_PARAMETER_SEARCH'      => WPSOLR_Query_Parameters::SEARCH_PARAMETER_SEARCH,
			'SEARCH_PARAMETER_Q'           => WPSOLR_Query_Parameters::SEARCH_PARAMETER_Q,
			'SEARCH_PARAMETER_FQ'          => WPSOLR_Query_Parameters::SEARCH_PARAMETER_FQ,
			'SEARCH_PARAMETER_SORT'        => WPSOLR_Query_Parameters::SEARCH_PARAMETER_SORT,
			'SEARCH_PARAMETER_PAGE'        => WPSOLR_Query_Parameters::SEARCH_PARAMETER_PAGE,
			'SORT_CODE_BY_RELEVANCY_DESC'  => WPSolrSearchSolrClient::SORT_CODE_BY_RELEVANCY_DESC,
			'wpsolr_autocomplete_selector' => WPSOLR_Global::getOption()->get_search_suggest_jquery_selector()
		),
		WPSOLR_PLUGIN_VERSION
	);

	/*
	 * Infinite scroll: load javascript if option is set.
	 */
	if ( WPSOLR_Global::getOption()->get_search_is_infinitescroll() ) {
		// Get localization options
		$localization_options = OptionLocalization::get_options();

		wp_register_script( 'infinitescroll', plugins_url( '/js/jquery.infinitescroll.js', __FILE__ ), array( 'jquery' ), WPSOLR_PLUGIN_VERSION, true );

		wp_enqueue_script( 'infinitescroll' );

		// loadingtext for translation
		// loadimage custom loading image url
		wp_localize_script( 'infinitescroll', 'wp_localize_script_infinitescroll',
			array(
				'ajax_url'           => admin_url( 'admin-ajax.php' ),
				'loadimage'          => plugins_url( '/images/infinitescroll.gif', __FILE__ ),
				'loadingtext'        => OptionLocalization::get_term( $localization_options, 'infinitescroll_loading' ),
				'SEARCH_PARAMETER_Q' => WPSOLR_Query_Parameters::SEARCH_PARAMETER_Q,
			),
			WPSOLR_PLUGIN_VERSION
		);
	}
}

function wpsolr_activate() {

	if ( ! is_multisite() ) {
		/**
		 * Mark licenses
		 */
		WpSolrExtensions::require_once_wpsolr_extension( WpSolrExtensions::OPTION_LICENSES, true );
		OptionLicenses::upgrade_licenses();
	}
}

register_activation_hook( __FILE__, 'wpsolr_activate' );

