<?php
/**
 * Plugin Name: Apache Solr search by WPSOLR
 * Description: Replace your sluggish and rigid SQL search with the world open source leader Apache Solr wich powers the leading internet websites
 * Version: 3.4
 * Author: WPSOLR.COM
 * Plugin URI: http://www.wpsolr.com
 * License: GPL2
 */

require_once 'ajax_solr_services.php';
require_once 'dashboard_settings.php';
require_once 'class-wp-solr.php';
require_once 'autocomplete.php';

global $solr;

/* Register Solr settings from dashboard
 * Add menu page in dashboard - Solr settings
 * Add solr settings- solr host, post and path
 *
 */
add_action( 'wp_head', 'check_default_options_and_function' );
add_action( 'admin_menu', 'fun_add_solr_settings' );
add_action( 'admin_init', 'func_reg_solr_form_setting' );

/*
 * Display Solr errors in admin when a save on a post can't index to Solr
 */
function solr_post_save_admin_notice() {
	if ( $out = get_transient( get_current_user_id() . 'error_solr_post_save_admin_notice' ) ) {
		delete_transient( get_current_user_id() . 'error_solr_post_save_admin_notice' );
		echo "<div class=\"error\"><p>(WPSOLR) Error while indexing this post/page in Solr:<br><br>$out</p></div>";
	}

	if ( $out = get_transient( get_current_user_id() . 'updated_solr_post_save_admin_notice' ) ) {
		delete_transient( get_current_user_id() . 'updated_solr_post_save_admin_notice' );
		echo "<div class=\"updated\"><p>(WPSOLR) $out</p></div>";
	}
}

add_action( 'admin_notices', "solr_post_save_admin_notice" );

/*
 * Add/remove document to/from Solr index when status changes to/from published
 * We have to use action 'save_post', as it is used by other plugins to trigger meta boxes save
 */
function add_remove_document_to_solr_index( $post_id, $post, $update ) {

	// If this is just a revision, don't go on.
	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}

	// If this is just a new post opened in editor, don't go on.
	if ( 'auto-draft' == $post->post_status ) {
		return;
	}

	try {
		if ( 'publish' == $post->post_status ) {
			// post published, add/update it from Solr index

			$solr = new wp_Solr();

			$solr->index_data( 1, $post );

			// Display confirmation in admin
			set_transient( get_current_user_id() . 'updated_solr_post_save_admin_notice', 'Post/page indexed in Solr' );

		} else {
			// post unpublished, remove it from Solr index
			$solr = new wp_Solr();

			$solr->delete_document( $post );

			// Display confirmation in admin
			set_transient( get_current_user_id() . 'updated_solr_post_save_admin_notice', 'Post/Page deleted from Solr' );
		}

	} catch ( Exception $e ) {
		set_transient( get_current_user_id() . 'error_solr_post_save_admin_notice', htmlentities( $e->getMessage() ) );
	}

}

add_action( 'save_post', 'add_remove_document_to_solr_index', 10, 3 );

/* Replace WordPress search
 * Default WordPress will be replaced with Solr search
 */


function check_default_options_and_function() {
	$solr_options = get_option( 'wdm_solr_res_data' );
	if ( $solr_options['default_search'] == 1 ) {

		add_filter( 'get_search_form', 'solr_search_form' );

	}
}


/* Create default page template for search results
*/
add_shortcode( 'solr_search_shortcode', 'fun_search_indexed_data' );
add_shortcode( 'solr_form', 'fun_dis_search' );
function fun_dis_search() {
	echo solr_search_form();
}

register_activation_hook( __FILE__, 'fun_add_result_page' );
function fun_add_result_page() {
	$the_page = get_page_by_title( 'Search Results', 'OBJECT', 'page' );
	if ( ! $the_page ) {

		$_p = array(
			'post_type'      => 'page',
			'post_title'     => 'Search Results',
			'post_content'   => '[solr_search_shortcode]',
			'post_status'    => 'publish',
			'post_author'    => 1,
			'comment_status' => 'closed',
			'post_name'      => 'Search Results'
		);

		$the_page_id = wp_insert_post( $_p );

		update_post_meta( $the_page_id, 'bwps_enable_ssl', '1' );

	} else {

		if ( $the_page->post_status != 'publish' ) {


			$the_page->post_status = 'publish';

			$the_page_id = wp_update_post( $the_page );
		} else {
			$the_page_id = $the_page->ID;
		}
	}


}

add_action( 'admin_notices', 'curl_dependency_check' );
function curl_dependency_check() {
	if ( ! in_array( 'curl', get_loaded_extensions() ) ) {

		echo "<div class='updated'><p><b>cURL</b> is not installed on your server. In order to make <b>'Solr for WordPress'</b> plugin work, you need to install <b>cURL</b> on your server </p></div>";
	}


}


function solr_search_form() {

	ob_start();
	$form   = ob_get_clean();
	$ad_url = admin_url();

	if ( isset( $_GET['search'] ) ) {
		$search_que = $_GET['search'];
	} else {
		$search_que = '';
	}
	$solr_options = get_option( 'wdm_solr_conf_data' );

	if ( $solr_options['host_type'] == 'self_hosted' ) {
		$_SESSION['wdm-host'] = $solr_options['solr_host'];
		$_SESSION['wdm-port'] = $solr_options['solr_port'];
		$_SESSION['wdm-path'] = $solr_options['solr_path'];

	} else {
		//$wdm_typehead_request_handler = 'wdm_return_goto_solr_rows';
		$_SESSION['wdm-ghost']  = $solr_options['solr_host_goto'];
		$_SESSION['wdm-gport']  = $solr_options['solr_port_goto'];
		$_SESSION['wdm-gpath']  = $solr_options['solr_path_goto'];
		$_SESSION['wdm-guser']  = $solr_options['solr_key_goto'];
		$_SESSION['wdm-gpwd']   = $solr_options['solr_secret_goto'];
		$_SESSION['wdm-gproto'] = $solr_options['solr_protocol_goto'];

	}

	$wdm_typehead_request_handler = 'wdm_return_solr_rows';

	$get_page_info = get_page_by_title( 'Search Results' );
	$ajax_nonce    = wp_create_nonce( "nonce_for_autocomplete" );


	$url  = get_permalink( $get_page_info->ID );
	$form = "<div class='cls_search' style='width:100%'><form action='$url' method='get'  class='search-frm2' >
   ";
	$form .= '<input type="hidden" value="' . $wdm_typehead_request_handler . '" id="path_to_fold">';
	$form .= '<input type="hidden"  id="ajax_nonce" value="' . $ajax_nonce . '">';

	$form .= '<input type="hidden" value="' . $ad_url . '" id="path_to_admin">';
	$form .= '<input type="hidden" value="' . $search_que . '" id="search_opt">';


	$form .= '
       <div class="ui-widget search-box">
        <input type="hidden" name="page_id" value="' . $get_page_info->ID . '" />
	<input type="hidden"  id="ajax_nonce" value="' . $ajax_nonce . '">
        <input type="text" placeholder="Search ..." value="' . $search_que . '" name="search" id="search_que" class="search-field sfl1" autocomplete="off"/>
	<input type="submit" value="Search" id="searchsubmit" style="position:relative;width:auto">
        <div style="clear:both"></div>
        </div>
	</div>
       </form>';

	return $form;


}

