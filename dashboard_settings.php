<?php
/**
 * Action to replace the admin footer text
 * @return string
 */
function wpsolr_admin_footer_text( $footer_text ) {
	$current_screen = get_current_screen();

	// Display wpsolr footer only on wpsolr admin pages
	if ( 'solr_settings' === $current_screen->parent_file ) {
		$footer_text = 'If you like WPSOLR, thank you for letting others know with a <a href="https://wordpress.org/support/view/plugin-reviews/wpsolr-search-engine" target="__new">***** review</a>.';
		$footer_text .= ' Else, we\'d like very much your feedbacks throught our <a href="http://www.wpsolr.com" target="__new">chat box</a> to improve the plugin.';

		$footer_version = 'You are using the free plugin.';
		$licenses       = OptionLicenses::get_activated_licenses_titles();
		if ( is_array( $licenses ) && ! empty( $licenses ) ) {

			$footer_version = sprintf( 'Activated packs: %s.', implode( ', ', $licenses ) );
		}
		$footer_version .= ' See <a href="http://www.wpsolr.com/pricing" target="__new">other packs</a>.';

		$footer_text = $footer_version . '<br/>' . $footer_text;
	}

	return $footer_text;
}

add_filter( 'admin_footer_text', 'wpsolr_admin_footer_text' );

/**
 * Action to replace the admin footer version
 * @return string
 */
function wpsolr_update_footer( $footer_version ) {

	$current_screen = get_current_screen();

	// Display wpsolr footer version only on wpsolr admin pages
	if ( 'solr_settings' === $current_screen->parent_file ) {
		$footer_version = 'WPSOLR ' . WPSOLR_PLUGIN_VERSION;
	}

	return $footer_version;
}

add_filter( 'update_footer', 'wpsolr_update_footer', 11 );


/*
 *  Route to controllers
 */
WpSolrExtensions::require_once_wpsolr_extension( WpSolrExtensions::OPTION_MANAGED_SOLR_SERVERS, true );
WpSolrExtensions::require_once_wpsolr_extension( WpSolrExtensions::OPTION_INDEXES, true );

switch ( isset( $_POST['wpsolr_action'] ) ? $_POST['wpsolr_action'] : '' ) {
	case 'wpsolr_admin_action_form_temporary_index':
		unset( $response_object );

		if ( isset( $_POST['submit_button_form_temporary_index'] ) ) {
			wpsolr_admin_action_form_temporary_index( $response_object );
		}

		if ( isset( $_POST['submit_button_form_temporary_index_select_managed_solr_service_id'] ) ) {

			$form_data = WpSolrExtensions::extract_form_data( true, array(
					'managed_solr_service_id' => array( 'default_value' => '', 'can_be_empty' => false )
				)
			);

			$managed_solr_server = new OptionManagedSolrServer( $form_data['managed_solr_service_id']['value'] );
			$response_object     = $managed_solr_server->call_rest_create_google_recaptcha_token();

			if ( isset( $response_object ) && OptionManagedSolrServer::is_response_ok( $response_object ) ) {
				$google_recaptcha_site_key = OptionManagedSolrServer::get_response_result( $response_object, 'siteKey' );
				$google_recaptcha_token    = OptionManagedSolrServer::get_response_result( $response_object, 'token' );
			}

		}

		break;

}

function wpsolr_admin_action_form_temporary_index( &$response_object ) {


	// recaptcha response
	$g_recaptcha_response = isset( $_POST['g-recaptcha-response'] ) ? $_POST['g-recaptcha-response'] : '';

	// A recaptcha response must be set
	if ( empty( $g_recaptcha_response ) ) {

		return;
	}

	$form_data = WpSolrExtensions::extract_form_data( true, array(
			'managed_solr_service_id' => array( 'default_value' => '', 'can_be_empty' => false )
		)
	);

	$managed_solr_server = new OptionManagedSolrServer( $form_data['managed_solr_service_id']['value'] );
	$response_object     = $managed_solr_server->call_rest_create_solr_index( $g_recaptcha_response );

	if ( isset( $response_object ) && OptionManagedSolrServer::is_response_ok( $response_object ) ) {

		$option_indexes_object = new OptionIndexes();

		$option_indexes_object->create_index(
			$managed_solr_server->get_id(),
			OptionIndexes::STORED_INDEX_TYPE_MANAGED_TEMPORARY,
			OptionManagedSolrServer::get_response_result( $response_object, 'urlCore' ),
			'Test index from ' . $managed_solr_server->get_label(),
			OptionManagedSolrServer::get_response_result( $response_object, 'urlScheme' ),
			OptionManagedSolrServer::get_response_result( $response_object, 'urlDomain' ),
			OptionManagedSolrServer::get_response_result( $response_object, 'urlPort' ),
			'/' . OptionManagedSolrServer::get_response_result( $response_object, 'urlPath' ) . '/' . OptionManagedSolrServer::get_response_result( $response_object, 'urlCore' ),
			OptionManagedSolrServer::get_response_result( $response_object, 'key' ),
			OptionManagedSolrServer::get_response_result( $response_object, 'secret' )
		);

		// Redirect automatically to Solr options if it is the first solr index created
		if ( count( $option_indexes_object->get_indexes() ) === 1 ) {
			$redirect_location = '?page=solr_settings&tab=solr_option';
			header( "Location: $redirect_location", true, 302 ); // wp_redirect() is not found
			exit;
		}
	}

}

function wpsolr_admin_init() {

	WpSolrExtensions::require_once_wpsolr_extension( WpSolrExtensions::OPTION_INDEXES, true );
	register_setting( OptionIndexes::get_option_name( WpSolrExtensions::OPTION_INDEXES ), OptionIndexes::get_option_name( WpSolrExtensions::OPTION_INDEXES ) );

	WpSolrExtensions::require_once_wpsolr_extension( WpSolrExtensions::OPTION_LICENSES, true );
	register_setting( OptionIndexes::get_option_name( WpSolrExtensions::OPTION_LICENSES ), OptionLicenses::get_option_name( WpSolrExtensions::OPTION_LICENSES ) );

	register_setting( 'solr_form_options', 'wdm_solr_form_data' );
	register_setting( 'solr_res_options', 'wdm_solr_res_data' );
	register_setting( 'solr_facet_options', 'wdm_solr_facet_data' );
	register_setting( 'solr_search_field_options', WPSOLR_Option::OPTION_SEARCH_FIELDS );
	register_setting( 'solr_sort_options', WPSOLR_Option::OPTION_SORTBY );
	register_setting( 'solr_localization_options', 'wdm_solr_localization_data' );
	register_setting( 'solr_extension_groups_options', 'wdm_solr_extension_groups_data' );
	register_setting( 'solr_extension_s2member_options', 'wdm_solr_extension_s2member_data' );
	register_setting( 'solr_extension_wpml_options', 'wdm_solr_extension_wpml_data' );
	register_setting( 'solr_extension_polylang_options', 'wdm_solr_extension_polylang_data' );
	register_setting( 'solr_extension_qtranslatex_options', 'wdm_solr_extension_qtranslatex_data' );
	register_setting( 'solr_operations_options', 'wdm_solr_operations_data' );
	register_setting( 'solr_extension_woocommerce_options', 'wdm_solr_extension_woocommerce_data' );
	register_setting( 'solr_extension_acf_options', 'wdm_solr_extension_acf_data' );
	register_setting( 'solr_extension_types_options', 'wdm_solr_extension_types_data' );
	register_setting( 'solr_extension_bbpress_options', 'wdm_solr_extension_bbpress_data' );
	register_setting( 'extension_embed_any_document_opt', WPSOLR_Option::OPTION_EMBED_ANY_DOCUMENT );
	register_setting( 'extension_pdf_embedder_opt', WPSOLR_Option::OPTION_PDF_EMBEDDER );
	register_setting( 'extension_google_doc_embedder_opt', WPSOLR_Option::OPTION_GOOGLE_DOC_EMBEDDER );

}

function fun_add_solr_settings() {
	$img_url = plugins_url( 'images/WPSOLRDashicon.png', __FILE__ );
	add_menu_page( 'WPSOLR', 'WPSOLR', 'manage_options', 'solr_settings', 'fun_set_solr_options', $img_url );
	wp_enqueue_style( 'dashboard_style', plugins_url( 'css/dashboard_css.css', __FILE__ ), array(), WPSOLR_PLUGIN_VERSION );
	wp_enqueue_script( 'jquery-ui-sortable' );
	wp_enqueue_script( 'dashboard_js1', plugins_url( 'js/dashboard.js', __FILE__ ),
		array(
			'jquery',
			'jquery-ui-sortable'
		),
		WPSOLR_PLUGIN_VERSION );

	$plugin_vals = array( 'plugin_url' => plugins_url( 'images/', __FILE__ ) );
	wp_localize_script( 'dashboard_js1', 'plugin_data', $plugin_vals );

	// Google api recaptcha - Used for temporary indexes creation
	wp_enqueue_script( 'google-api-recaptcha', '//www.google.com/recaptcha/api.js', array(), WPSOLR_PLUGIN_VERSION );

	// Bootstrap tour
	/*
	wp_enqueue_style( 'bootstrap_tour_css', plugins_url( 'css/bootstrap-tour-standalone.css', __FILE__ ), array(), 'v0.10.3' );
	wp_enqueue_script( 'bootstrap_tour_js', plugins_url( 'js/bootstrap-tour-standalone.js', __FILE__ ), array( 'jquery' ), 'v0.10.3' );
	*/
}

function fun_set_solr_options() {
	global $license_manager;

	// Include license activation popup boxes in all admin tabs
	add_thickbox();
	if ( ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
		// Do not load in Ajax
		require_once 'classes/extensions/licenses/admin_options.inc.php';
	}

	// Button Index
	if ( isset( $_POST['solr_index_data'] ) ) {

		$solr = WPSolrIndexSolrClient::create();

		try {
			$res = $solr->get_solr_status();

			$val = $solr->index_data();

			if ( count( $val ) == 1 || $val == 1 ) {
				echo "<script type='text/javascript'>
                jQuery(document).ready(function(){
                jQuery('.status_index_message').removeClass('loading');
                jQuery('.status_index_message').addClass('success');
                });
            </script>";
			} else {
				echo "<script type='text/javascript'>
            jQuery(document).ready(function(){
                jQuery('.status_index_message').removeClass('loading');
                jQuery('.status_index_message').addClass('warning');
                });
            </script>";
			}

		} catch ( Exception $e ) {

			$errorMessage = $e->getMessage();

			echo "<script type='text/javascript'>
            jQuery(document).ready(function(){
               jQuery('.status_index_message').removeClass('loading');
               jQuery('.status_index_message').addClass('warning');
               jQuery('.wdm_note').html('<b>Error: <p>{$errorMessage}</p></b>');
            });
            </script>";

		}

	}

	// Button delete
	if ( isset( $_POST['solr_delete_index'] ) ) {
		$solr = WPSolrIndexSolrClient::create();

		try {
			$res = $solr->get_solr_status();

			$val = $solr->delete_documents();

			if ( $val == 0 ) {
				echo "<script type='text/javascript'>
            jQuery(document).ready(function(){
               jQuery('.status_del_message').removeClass('loading');
               jQuery('.status_del_message').addClass('success');
            });
            </script>";
			} else {
				echo "<script type='text/javascript'>
            jQuery(document).ready(function(){
               jQuery('.status_del_message').removeClass('loading');
                              jQuery('.status_del_message').addClass('warning');
            });
            </script>";
			}

		} catch ( Exception $e ) {

			$errorMessage = $e->getMessage();

			echo "<script type='text/javascript'>
            jQuery(document).ready(function(){
               jQuery('.status_del_message').removeClass('loading');
               jQuery('.status_del_message').addClass('warning');
               jQuery('.wdm_note').html('<b>Error: <p>{$errorMessage}</p></b>');
            })
            </script>";
		}
	}


	?>
	<div class="wdm-wrap" xmlns="http://www.w3.org/1999/html">
	<div class="page_title">
		<h1>
			<!--<input id="wpsolr_tour_button_start" type="button" class="button-secondary" value="Resume the Tour" style="align:right">-->
			Power your search with <a href="http://lucene.apache.org/solr/" target="_blank">Apache Solr</a>, the world's
			leading search engine
		</h1>
	</div>

	<?php
	if ( isset ( $_GET['tab'] ) ) {
		wpsolr_admin_tabs( $_GET['tab'] );
	} else {
		wpsolr_admin_tabs( 'solr_presentation' );
	}

	if ( isset ( $_GET['tab'] ) ) {
		$tab = $_GET['tab'];
	} else {
		$tab = 'solr_presentation';
	}

	switch ( $tab ) {
	case 'solr_presentation' :
		?>
		<h2>See our little <a href="http://www.gotosolr.com/en/search-wpsolr/" target="_blank">demo (WPSOLR, Ajax,
				Infinite scroll pagination, WPML, Products suggestions, attachments)</a>. Try "solr", "cluster",
			....
		</h2>
		<h2>Walkthrough of the different steps to configure a search with wpsolr</h2>

		<ul>
			<li>1. Download <a href="http://lucene.apache.org/solr/" target="_blank">Apache Solr</a>. WPSOLR
				replaces
				the slow WP SQL search by the mighty Solr search.
			</li>
			<li>2. Install <a href="http://wpsolr.com/installation-guide/" target="_blank">Apache Solr</a> (if you
				want
				to host it yourself).
			</li>
			<li>3. <a href="http://www.wpsolr.com/installation-guide/" target="_blank">Create a Solr index</a>, or
				<a
					href="http://www.gotosolr.com/en" target="_blank">host a Gotosolr index</a> to store your data.
			</li>
			<li>4. <a href="http://wpsolr.com/user-guide/" target="_blank">Configure WPSOLR with your own Solr
					index</a>,
				<a href="http://www.gotosolr.com/en/solr-tutorial-for-wordpress/" target="_blank">configure WPSOLR
					with
					Gotosolr</a></li>
		</ul>

		<h2>Quick video to watch the setup steps</h2>
		<iframe width="1020" height="630" src="https://www.youtube.com/embed/Di2QExcliCo" frameborder="0"
		        allowfullscreen>
		</iframe>
		<?php

		break;

	case 'solr_indexes' :
		WpSolrExtensions::require_once_wpsolr_extension_admin_options( WpSolrExtensions::OPTION_INDEXES );
		break;

	case 'solr_option':
		?>
		<div id="solr-option-tab">

			<?php

			$subtabs = array(
				'result_opt'           => '2.1 Settings',
				'index_opt'            => '2.2 Indexed data',
				'field_opt'            => '2.3 Search fields boosts',
				'facet_opt'            => '2.4 Results facets',
				'sort_opt'             => '2.5 Results sort',
				'localization_options' => '2.6 Localization',
			);

			$subtab              = wpsolr_admin_sub_tabs( $subtabs );

			switch ( $subtab ) {
				case 'result_opt':

					WpSolrExtensions::require_once_wpsolr_extension( WpSolrExtensions::OPTION_INDEXES, true );
					$option_indexes = new OptionIndexes();
					$solr_indexes   = $option_indexes->get_indexes();

					?>
					<div id="solr-results-options" class="wdm-vertical-tabs-content">
						<form action="options.php" method="POST" id='res_settings_form'>
							<?php
							settings_fields( 'solr_res_options' );
							$solr_res_options = get_option( 'wdm_solr_res_data', array(
								'default_search'                     => 0,
								'res_info'                           => '0',
								'spellchecker'                       => '0',
								'is_after_autocomplete_block_submit' => '1',
							) );

							?>

							<div class='wrapper'>
								<h4 class='head_div'>Result Options</h4>

								<div class="wdm_note">

									In this section, you will choose how to display the results returned by a
									query to your Solr instance.

								</div>
								<div class="wdm_row">
									<div class='col_left'>
										Replace WordPress default search by WPSOLR's.<br/><br/>
									</div>
									<div class='col_right'>
										<input type='checkbox' name='wdm_solr_res_data[default_search]'
										       value='1'
											<?php checked( '1', isset( $solr_res_options['default_search'] ) ? $solr_res_options['default_search'] : '0' ); ?>>
										If your website is already in production, check this option after tabs
										1-4 are completed. <br/><br/>
										Warning: permalinks must be activated.
									</div>
									<div class="clear"></div>
								</div>
								<div class="wdm_row">
									<div class='col_left'>Search with this Solr index<br/>

									</div>
									<div class='col_right'>
										<select name='wdm_solr_res_data[default_solr_index_for_search]'>
											<?php
											// Empty option
											echo sprintf( "<option value='%s' %s>%s</option>",
												'',
												'',
												'Your search is not managed by Solr. Please select a Solr index.'
											);

											foreach (
												$solr_indexes as $solr_index_indice => $solr_index
											) {

												echo sprintf( "
											<option value='%s' %s>%s</option>
											",
													$solr_index_indice,
													selected( $solr_index_indice, isset( $solr_res_options['default_solr_index_for_search'] ) ?
														$solr_res_options['default_solr_index_for_search'] : '' ),
													isset( $solr_index['index_name'] ) ? $solr_index['index_name'] : 'Unnamed
											Solr index' );

											}
											?>
										</select>

									</div>
									<div class="clear"></div>
								</div>
								<div class="wdm_row">
									<div class='col_left'>
										<?php echo $license_manager->show_premium_link( OptionLicenses::LICENSE_PACKAGE_CORE, 'This search is part of a network search', true, true ); ?>
									</div>
									<div class='col_right'>
										<select
											name="wdm_solr_res_data[<?php echo WPSOLR_Option::OPTION_SEARCH_ITEM_GALAXY_MODE; ?>]">
											<?php
											$options = array(
												array(
													'code'  => '',
													'label' => 'No, this is a standalone search'
												),
												array(
													'code'     => WPSOLR_Option::OPTION_SEARCH_ITEM_IS_GALAXY_SLAVE,
													'label'    => 'Yes, as one of local searches (suggestions will not work)',
													'disabled' => $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_CORE, true ),
												),
												array(
													'code'     => WPSOLR_Option::OPTION_SEARCH_ITEM_IS_GALAXY_MASTER,
													'label'    => 'Yes, as the global search (only with ajax)',
													'disabled' => $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_CORE, true ),
												),
											);
											foreach ( $options as $option ) {
												$selected = $solr_res_options[ WPSOLR_Option::OPTION_SEARCH_ITEM_GALAXY_MODE ] == $option['code'] ? 'selected' : '';
												$disabled = isset( $option['disabled'] ) ? $option['disabled'] : '';
												?>
												<option
													value="<?php echo $option['code'] ?>"
													<?php echo $selected ?>
													<?php echo $disabled ?>>
													<?php echo $option['label'] ?>
												</option>
											<?php } ?>

										</select>
										<ul>
											<li>- The global site searches in all local sites data</li>
											<li>- Each local site searches in it's own data</li>
										</ul>
									</div>
									<div class="clear"></div>
								</div>
								<div class="wdm_row">
									<div class='col_left'>
										<?php echo $license_manager->show_premium_link( OptionLicenses::LICENSE_PACKAGE_CORE, 'Search theme', true ); ?>
									</div>
									<div class='col_right'>
										<select name="wdm_solr_res_data[search_method]">
											<?php
											$options = array(
												array(
													'code'     => 'use_current_theme_search_template',
													'label'    => 'Use my current theme search templates (no keyword autocompletion, no \'Did you mean\', no filters, no sort)',
													'disabled' => $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_CORE ),
												),
												array(
													'code'  => 'ajax',
													'label' => 'Use WPSOLR custom search templates with Ajax (full WPSOLR features)'
												),
												array(
													'code'  => 'ajax_with_parameters',
													'label' => 'Use WPSOLR custom search templates with Ajax and show parameters in url (full WPSOLR features)'
												)
											);
											foreach ( $options as $option ) {
												$selected = $solr_res_options['search_method'] == $option['code'] ? 'selected' : '';
												$disabled = isset( $option['disabled'] ) ? $option['disabled'] : '';
												?>
												<option
													value="<?php echo $option['code'] ?>"
													<?php echo $selected ?>
													<?php echo $disabled ?>>
													<?php echo $option['label'] ?>
												</option>
											<?php } ?>

										</select>

										<br/><br/>
										To display your search results, you can choose among:<br/>
										<b>- Full integration to your theme, but less Solr features:</b> <br/>
										Use your own theme's search templates customized with Widget 'WPSOLR
										filters'.<br/>
										<b>- Full Solr features, but less integration to your theme:</b><br/>
										Use WPSOLR's custom search templates with your own css.<br/>
									</div>
									<div class="clear"></div>
								</div>
								<div class="wdm_row">
									<div class='col_left'>Do not load WPSOLR front-end css.<br/>You can then use
										your
										own theme css.
									</div>
									<div class='col_right'>
										<?php $is_prevent_loading_front_end_css = isset( $solr_res_options['is_prevent_loading_front_end_css'] ) ? '1' : '0'; ?>
										<input type='checkbox'
										       name='wdm_solr_res_data[is_prevent_loading_front_end_css]'
										       value='1'
											<?php checked( '1', $is_prevent_loading_front_end_css ); ?>>
									</div>
									<div class="clear"></div>
								</div>
								<div class="wdm_row">
									<div class='col_left'>
										<?php echo $license_manager->show_premium_link( OptionLicenses::LICENSE_PACKAGE_CORE, 'Activate the "Infinite scroll" pagination', true ); ?>
									</div>
									<div class='col_right'>
										<input type='checkbox'
										       name='wdm_solr_res_data[<?php echo 'infinitescroll' ?>]'
										       value='infinitescroll'
											<?php echo $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_CORE ); ?>
											<?php checked( 'infinitescroll', isset( $solr_res_options['infinitescroll'] ) ? $solr_res_options['infinitescroll'] : '?' ); ?>>

										This feature loads the next page of results automatically when visitors
										approach
										the bottom of search page.
									</div>
									<div class="clear"></div>
								</div>
								<div class="wdm_row">
									<div class='col_left'>
										<?php echo $license_manager->show_premium_link( OptionLicenses::LICENSE_PACKAGE_CORE, 'Show suggestions in the search box', true, true ); ?>
									</div>
									<div class='col_right'>
										<select
											name="wdm_solr_res_data[<?php echo WPSOLR_Option::OPTION_SEARCH_SUGGEST_CONTENT_TYPE; ?>]">
											<?php
											$options = array(
												array(
													'code'     => WPSOLR_Option::OPTION_SEARCH_SUGGEST_CONTENT_TYPE_NONE,
													'label'    => 'No suggestions',
													'disabled' => $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_CORE, true ),
												),
												array(
													'code'  => WPSOLR_Option::OPTION_SEARCH_SUGGEST_CONTENT_TYPE_KEYWORDS,
													'label' => 'Suggest Keywords',
												),
												array(
													'code'     => WPSOLR_Option::OPTION_SEARCH_SUGGEST_CONTENT_TYPE_POSTS,
													'label'    => 'Suggest Products',
													'disabled' => $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_CORE, true ),
												)
											);
											foreach ( $options as $option ) {
												$selected = ( $solr_res_options[ WPSOLR_Option::OPTION_SEARCH_SUGGEST_CONTENT_TYPE ] === $option['code'] ) || ( empty( $solr_res_options[ WPSOLR_Option::OPTION_SEARCH_SUGGEST_CONTENT_TYPE ] ) && WPSOLR_Option::OPTION_SEARCH_SUGGEST_CONTENT_TYPE_KEYWORDS === $option['code'] ) ? 'selected' : '';
												$disabled = isset( $option['disabled'] ) ? $option['disabled'] : '';
												?>
												<option
													value="<?php echo $option['code'] ?>"
													<?php echo $selected ?>
													<?php echo $disabled ?>>
													<?php echo $option['label'] ?>
												</option>
											<?php } ?>

										</select>

										By default, suggestions are shown only with the WPSOLR Ajax theme's search
										form.
										Use the jQuery selectors field below to show suggestions on your own theme's
										search forms.
									</div>
									<div class="clear"></div>
								</div>
								<div class="wdm_row">
									<div
										class='col_left'><?php echo $license_manager->show_premium_link( OptionLicenses::LICENSE_PACKAGE_CORE, 'Attach the suggestions list to your own search forms', true, true ); ?></div>
									<div class='col_right'>
										<input type='text'
										       name='wdm_solr_res_data[<?php echo WPSOLR_Option::OPTION_SEARCH_SUGGEST_JQUERY_SELECTOR; ?>]'
										       placeholder=".search_form1, #search_form2"
											<?php echo $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_CORE, true ); ?>
											   value="<?php echo( ! empty( $solr_res_options[ WPSOLR_Option::OPTION_SEARCH_SUGGEST_JQUERY_SELECTOR ] ) ? $solr_res_options[ WPSOLR_Option::OPTION_SEARCH_SUGGEST_JQUERY_SELECTOR ] : '' ); ?>">
										Enter a jQuery selector for your search forms.
									</div>
									<div class="clear"></div>
								</div>
								<div class="wdm_row">
									<div class='col_left'>Do not automatically trigger the search, when a user
										clicks on the
										autocomplete list
									</div>
									<div class='col_right'>
										<?php $is_after_autocomplete_block_submit = isset( $solr_res_options['is_after_autocomplete_block_submit'] ) ? '1' : '0'; ?>
										<input type='checkbox'
										       name='wdm_solr_res_data[is_after_autocomplete_block_submit]'
										       value='1'
											<?php checked( '1', $is_after_autocomplete_block_submit ); ?>>
									</div>
									<div class="clear"></div>
								</div>
								<div class="wdm_row">
									<div class='col_left'>
										<?php echo $license_manager->show_premium_link( OptionLicenses::LICENSE_PACKAGE_CORE, 'Display "Did you mean?" in search results header ?', true ); ?>
									</div>
									<div class='col_right'>
										<input type='checkbox'
										       name='wdm_solr_res_data[<?php echo 'spellchecker' ?>]'
										       value='spellchecker'
											<?php echo $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_CORE ); ?>
											<?php checked( 'spellchecker', isset( $solr_res_options['spellchecker'] ) ? $solr_res_options['spellchecker'] : '?' ); ?>>
									</div>
									<div class="clear"></div>
								</div>
								<div class="wdm_row">
									<div class='col_left'>Display number of results and current page</div>
									<div class='col_right'>
										<input type='checkbox' name='wdm_solr_res_data[res_info]'
										       value='res_info'
											<?php checked( 'res_info', isset( $solr_res_options['res_info'] ) ? $solr_res_options['res_info'] : '?' ); ?>>
									</div>
									<div class="clear"></div>
								</div>
								<div class="wdm_row">
									<div class='col_left'>No. of results per page</div>
									<div class='col_right'>
										<input type='text' id='number_of_res' name='wdm_solr_res_data[no_res]'
										       placeholder="Enter a Number"
										       value="<?php echo empty( $solr_res_options['no_res'] ) ? '20' : $solr_res_options['no_res']; ?>">
										<span class='res_err'></span><br>
									</div>
									<div class="clear"></div>
								</div>
								<div class="wdm_row">
									<div class='col_left'>No. of values to be displayed by filters</div>
									<div class='col_right'>
										<input type='text' id='number_of_fac' name='wdm_solr_res_data[no_fac]'
										       placeholder="Enter a Number"
										       value="<?php echo ( empty( $solr_res_options['no_fac'] ) && $solr_res_options['no_fac'] !== '0' ) ? '20' : $solr_res_options['no_fac']; ?>"><span
											class='fac_err'></span>
										0 for unlimited values
									</div>
									<div class="clear"></div>
								</div>
								<div class="wdm_row">
									<div class='col_left'>Maximum size of each snippet text in results</div>
									<div class='col_right'>
										<input type='text' id='highlighting_fragsize'
										       name='wdm_solr_res_data[highlighting_fragsize]'
										       placeholder="Enter a Number"
										       value="<?php echo empty( $solr_res_options['highlighting_fragsize'] ) ? '100' : $solr_res_options['highlighting_fragsize']; ?>"><span
											class='highlighting_fragsize_err'></span> <br>
									</div>
									<div class="clear"></div>
								</div>
								<div class="wdm_row">
									<div class='col_left'>Use partial keyword matches in results</div>
									<div class='col_right'>
										<input type='checkbox' class='wpsolr_checkbox_mono_wpsolr_is_partial'
										       name='wdm_solr_res_data[<?php echo WPSOLR_Option::OPTION_SEARCH_ITEM_IS_PARTIAL_MATCHES; ?>]'
										       value='1'
											<?php checked( isset( $solr_res_options[ WPSOLR_Option::OPTION_SEARCH_ITEM_IS_PARTIAL_MATCHES ] ) ); ?>>
										Warning: this will hurt both search performance and search accuracy !
										<p>This adds '*' to all keywords.
											For instance, 'search apache' will return results
											containing 'searching apachesolr'</p>
									</div>
									<div class="clear"></div>
								</div>
								<div class="wdm_row">
									<div class='col_left'>Use fuzzy keyword matches in results</div>
									<div class='col_right'>
										<input type='checkbox' class='wpsolr_checkbox_mono_wpsolr_is_partial other'
										       name='wdm_solr_res_data[<?php echo WPSOLR_Option::OPTION_SEARCH_ITEM_IS_FUZZY_MATCHES; ?>]'
										       value='1'
											<?php checked( isset( $solr_res_options[ WPSOLR_Option::OPTION_SEARCH_ITEM_IS_FUZZY_MATCHES ] ) ); ?>>
										See <a
											href="https://cwiki.apache.org/confluence/display/solr/The+Standard+Query+Parser#TheStandardQueryParser-FuzzySearches"
											target="_new">Fuzzy description at Solr wiki</a>
										<p>The search 'roam' will match terms like roams, foam, & foams. It will
											also
											match the word "roam" itself.</p>
									</div>
									<div class="clear"></div>
								</div>
								<div class='wdm_row'>
									<div class="submit">
										<input name="save_selected_options_res_form"
										       id="save_selected_res_options_form" type="submit"
										       class="button-primary wdm-save" value="Save Options"/>


									</div>
								</div>
							</div>

						</form>
					</div>
					<?php
					break;
				case 'index_opt':

					$custom_fields_error_message = '';

					$posts      = get_post_types();
					$args       = array(
						'public'   => true,
						'_builtin' => false

					);
					$output     = 'names'; // or objects
					$operator   = 'and'; // 'and' or 'or'
					$taxonomies = get_taxonomies( $args, $output, $operator );
					global $wpdb;
					$limit = (int) apply_filters( 'postmeta_form_limit', 30 );
					$keys  = $wpdb->get_col( "
                                                                    SELECT distinct meta_key
                                                                    FROM $wpdb->postmeta
                                                                    WHERE meta_key!='bwps_enable_ssl' 
                                                                    ORDER BY meta_key" );

					try {// Filter custom fields to be indexed.
						$keys = apply_filters( WpSolrFilters::WPSOLR_FILTER_INDEX_CUSTOM_FIELDS, $keys );
					} catch ( Exception $e ) {
						$custom_fields_error_message = $e->getMessage();
					}

					// WooCommerce attributes are added to custom fields
					if ( function_exists( 'wc_get_attribute_taxonomies' ) ) {
						WpSolrExtensions::require_once_wpsolr_extension( WpSolrExtensions::EXTENSION_WOOCOMMERCE, true );
						$woo_attribute_names = PluginWooCommerce::get_attribute_taxonomy_names();
						foreach ( $woo_attribute_names as $woo_attribute_name ) {
							// Add woo attribute slug to custom fields
							array_push( $keys, $woo_attribute_name );
						}
					}

					$post_types = array();
					foreach ( $posts as $ps ) {
						if ( $ps != 'attachment' && $ps != 'revision' && $ps != 'nav_menu_item' ) {
							array_push( $post_types, $ps );
						}
					}

					$allowed_attachments_types = get_allowed_mime_types();

					WpSolrExtensions::require_once_wpsolr_extension( WpSolrExtensions::OPTION_INDEXES, true );
					$option_indexes = new OptionIndexes();
					$solr_indexes   = $option_indexes->get_indexes();
					?>

					<div id="solr-indexing-options" class="wdm-vertical-tabs-content">
						<form action="options.php" method="POST" id='settings_form'>
							<?php
							settings_fields( 'solr_form_options' );
							$solr_options = get_option( 'wdm_solr_form_data', array(
								'comments'         => 0,
								'p_types'          => '',
								'taxonomies'       => '',
								'cust_fields'      => '',
								'attachment_types' => ''
							) );
							?>


							<div class='indexing_option wrapper'>
								<h4 class='head_div'>Indexing Options</h4>

								<div class="wdm_note">

									In this section, you will choose among all the data stored in your Wordpress
									site, which you want to load in your Solr index.

								</div>

								<div class="wdm_row">
									<div class='col_left'>
										<?php echo $license_manager->show_premium_link( OptionLicenses::LICENSE_PACKAGE_CORE, 'Stop real-time indexing', true ); ?>
									</div>
									<div class='col_right'>
										<input type='checkbox' name='wdm_solr_form_data[is_real_time]'
										       value='1'
											<?php checked( '1', isset( $solr_options['is_real_time'] ) ? $solr_options['is_real_time'] : '' ); ?>
											<?php echo $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_CORE ); ?>
										>
										<br/>The Solr index will no more be updated as soon as a post/comment/attachment
										is
										added/saved/deleted, but only when you launch the indexing bach.
										<br/> Useful to load a large number of posts, for instance coupons/products
										from
										affiliate datafeeds.

									</div>
									<div class="clear"></div>
								</div>
								<div class="wdm_row">
									<div class='col_left'>
										Index post excerpt.<br/>
										Excerpt will be added to the post content, and be searchable, highlighted,
										and
										autocompleted.
									</div>
									<div class='col_right'>
										<input type='checkbox' name='wdm_solr_form_data[p_excerpt]'
										       value='1' <?php checked( '1', isset( $solr_options['p_excerpt'] ) ? $solr_options['p_excerpt'] : '' ); ?>>

									</div>
									<div class="clear"></div>
								</div>
								<div class="wdm_row">
									<div class='col_left'>
										Index custom fields and categories.<br/>
										Custom fields and categories will be added to the post content, and be
										searchable, highlighted,
										and
										autocompleted.
									</div>
									<div class='col_right'>
										<input type='checkbox' name='wdm_solr_form_data[p_custom_fields]'
										       value='1' <?php checked( '1', isset( $solr_options['p_custom_fields'] ) ? $solr_options['p_custom_fields'] : '' ); ?>>

									</div>
									<div class="clear"></div>
								</div>
								<div class="wdm_row">
									<div class='col_left'>
										Expand shortcodes of post content before indexing.<br/>
										Else, shortcodes will simply be stripped.
									</div>
									<div class='col_right'>
										<input type='checkbox' name='wdm_solr_form_data[is_shortcode_expanded]'
										       value='1' <?php checked( '1', isset( $solr_options['is_shortcode_expanded'] ) ? $solr_options['is_shortcode_expanded'] : '' ); ?>>

									</div>
									<div class="clear"></div>
								</div>
								<div class="wdm_row">
									<div
										class='col_left'><?php echo $license_manager->show_premium_link( OptionLicenses::LICENSE_PACKAGE_CORE, 'Post types to be indexed', true ); ?></div>
									<div class='col_right'>
										<input type='hidden' name='wdm_solr_form_data[p_types]' id='p_types'>
										<?php
										$post_types_opt = $solr_options['p_types'];
										// Sort post types
										asort( $post_types );

										// Selected first
										foreach ( $post_types as $type ) {
											if ( strpos( $post_types_opt, $type ) !== false ) {
												$disabled = ( ( $type === 'post' ) || ( $type === 'page' ) || ( $type === 'product' ) ) ? '' : $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_CORE );

												?>
												<input type='checkbox' name='post_tys'
												       value='<?php echo $type ?>'
													<?php echo $disabled; ?>
													   checked> <?php echo $type ?>
												<br>
												<?php
											}
										}

										// Unselected 2nd
										foreach ( $post_types as $type ) {
											if ( strpos( $post_types_opt, $type ) === false ) {
												$disabled = ( ( $type === 'post' ) || ( $type === 'page' ) || ( $type === 'product' ) ) ? '' : $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_CORE );

												?>
												<input type='checkbox' name='post_tys'
												       value='<?php echo $type ?>'
													<?php echo $disabled; ?>
												> <?php echo $type ?>
												<br>
												<?php
											}
										}

										?>

									</div>
									<div class="clear"></div>
								</div>

								<div class="wdm_row">
									<div class='col_left'>
										<?php echo $license_manager->show_premium_link( OptionLicenses::LICENSE_PACKAGE_CORE, 'Attachment types to be indexed', true ); ?>
									</div>
									<div class='col_right'>
										<input type='hidden' name='wdm_solr_form_data[attachment_types]'
										       id='attachment_types'>
										<?php
										$attachment_types_opt = $solr_options['attachment_types'];
										$disabled             = $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_CORE );
										// sort attachments
										asort( $allowed_attachments_types );

										// Selected first
										foreach ( $allowed_attachments_types as $type ) {
											if ( strpos( $attachment_types_opt, $type ) !== false ) {
												?>
												<input type='checkbox' name='attachment_types'
												       value='<?php echo $type ?>'
													<?php echo $disabled; ?>
													   checked> <?php echo $type ?>
												<br>
												<?php
											}
										}

										// Unselected 2nd
										foreach ( $allowed_attachments_types as $type ) {
											if ( strpos( $attachment_types_opt, $type ) === false ) {
												?>
												<input type='checkbox' name='attachment_types'
												       value='<?php echo $type ?>'
													<?php echo $disabled; ?>
												> <?php echo $type ?>
												<br>
												<?php
											}
										}

										?>
									</div>
									<div class="clear"></div>
								</div>

								<div class="wdm_row">
									<div class='col_left'>
										<?php echo $license_manager->show_premium_link( OptionLicenses::LICENSE_PACKAGE_CORE, 'Custom taxonomies to be indexed', true ); ?>
									</div>
									<div class='col_right'>
										<div class='cust_tax'><!--new div class given-->
											<input type='hidden' name='wdm_solr_form_data[taxonomies]'
											       id='tax_types'>
											<?php
											$tax_types_opt = $solr_options['taxonomies'];
											$disabled      = $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_CORE );
											if ( count( $taxonomies ) > 0 ) {

												// Selected first
												foreach ( $taxonomies as $type ) {
													if ( strpos( $tax_types_opt, $type . "_str" ) !== false ) {
														?>

														<input type='checkbox' name='taxon'
														       value='<?php echo $type . "_str" ?>'
															<?php echo $disabled; ?>
															   checked
														> <?php echo $type ?> <br>
														<?php
													}
												}

												// Unselected 2nd
												foreach ( $taxonomies as $type ) {
													if ( strpos( $tax_types_opt, $type . "_str" ) === false ) {
														?>

														<input type='checkbox' name='taxon'
														       value='<?php echo $type . "_str" ?>'
															<?php echo $disabled; ?>
														> <?php echo $type ?> <br>
														<?php
													}
												}

											} else {
												echo 'None';
											} ?>
										</div>
									</div>
									<div class="clear"></div>
								</div>

								<div class="wdm_row">
									<div class='col_left'>
										<?php echo $license_manager->show_premium_link( OptionLicenses::LICENSE_PACKAGE_CORE, 'Custom Fields to be indexed', true ); ?>
									</div>

									<div class='col_right'>
										<?php
										if ( ! empty( $custom_fields_error_message ) ) {
											echo sprintf( '<div class="error-message">%s</div>', $custom_fields_error_message );
										}
										?>

										<input type='hidden' name='wdm_solr_form_data[cust_fields]'
										       id='field_types'>

										<div class='cust_fields'><!--new div class given-->
											<?php
											$field_types_opt = $solr_options['cust_fields'];
											$disabled        = $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_CORE );
											if ( count( $keys ) > 0 ) {
												// sort custom fields
												asort( $keys );

												// Show selected first
												foreach ( $keys as $key ) {
													if ( strpos( $field_types_opt, $key . "_str" ) !== false ) {
														?>

														<input type='checkbox' name='cust_fields'
														       value='<?php echo $key . "_str" ?>'
															<?php echo $disabled; ?>
															   checked> <?php echo $key ?>
														<br>
														<?php
													}
												}

												// Show unselected 2nd
												foreach ( $keys as $key ) {
													if ( strpos( $field_types_opt, $key . "_str" ) === false ) {
														?>

														<input type='checkbox' name='cust_fields'
														       value='<?php echo $key . "_str" ?>'
															<?php echo $disabled; ?>
														> <?php echo $key ?>
														<br>
														<?php
													}
												}

											} else {
												echo 'None';
											}
											?>
										</div>
									</div>
									<div class="clear"></div>
								</div>

								<div class="wdm_row">
									<div class='col_left'>Index Comments</div>
									<div class='col_right'>
										<input type='checkbox' name='wdm_solr_form_data[comments]'
										       value='1' <?php checked( '1', isset( $solr_options['comments'] ) ? $solr_options['comments'] : '' ); ?>>

									</div>
									<div class="clear"></div>
								</div>
								<div class="wdm_row">
									<div class='col_left'>Exclude items (Posts,Pages,...)</div>
									<div class='col_right'>
										<input type='text' name='wdm_solr_form_data[exclude_ids]'
										       placeholder="Comma separated ID's list"
										       value="<?php echo empty( $solr_options['exclude_ids'] ) ? '' : $solr_options['exclude_ids']; ?>">
										<br>
										(Comma separated ids list)
									</div>
									<div class="clear"></div>
								</div>
								<div class='wdm_row'>
									<div class="submit">
										<input name="save_selected_index_options_form"
										       id="save_selected_index_options_form" type="submit"
										       class="button-primary wdm-save" value="Save Options"/>


									</div>
								</div>

							</div>
						</form>
					</div>
					<?php
					break;

				case 'field_opt':
					$solr_options = get_option( 'wdm_solr_form_data' );
					$checked_fls = $solr_options['cust_fields'] . ',' . $solr_options['taxonomies'];

					$checked_fields = explode( ',', $checked_fls );
					$img_path       = plugins_url( 'images/plus.png', __FILE__ );
					$minus_path     = plugins_url( 'images/minus.png', __FILE__ );
					$built_in       = array(
						WpSolrSchema::_FIELD_NAME_CONTENT,
						WpSolrSchema::_FIELD_NAME_TITLE,
						WpSolrSchema::_FIELD_NAME_COMMENTS,
						WpSolrSchema::_FIELD_NAME_TYPE,
						WpSolrSchema::_FIELD_NAME_AUTHOR,
						WpSolrSchema::_FIELD_NAME_CATEGORIES,
						WpSolrSchema::_FIELD_NAME_TAGS
					);
					$built_in       = array_merge( $built_in, $checked_fields );

					?>
					<div id="solr-facets-options" class="wdm-vertical-tabs-content">
						<form action="options.php" method="POST" id='fac_settings_form'>
							<?php
							settings_fields( 'solr_search_field_options' );
							$solr_search_fields_is_active            = WPSOLR_Global::getOption()->get_search_fields_is_active();
							$solr_search_fields_boosts_options       = WPSOLR_Global::getOption()->get_search_fields_boosts();
							$solr_search_fields_terms_boosts_options = WPSOLR_Global::getOption()->get_search_fields_terms_boosts();
							$selected_values                         = WPSOLR_Global::getOption()->get_option_search_fields_str();
							$selected_array                          = WPSOLR_Global::getOption()->get_option_search_fields();
							?>
							<div class='wrapper'>
								<h4 class='head_div'>Search fields boosts Options</h4>

								<div class="wdm_row">
									<div class='col_left'>Activate the boosts</div>
									<div class='col_right'>
										<input type='checkbox'
										       name='<?php echo WPSOLR_Option::OPTION_SEARCH_FIELDS; ?>[<?php echo WPSOLR_Option::OPTION_SEARCH_FIELDS_IS_ACTIVE; ?>]'
										       value='1' <?php checked( $solr_search_fields_is_active ); ?>>

										First, select among the fields indexed (see below) those you want to search
										in,
										then define their boosts.
										Select none if you want to use the default search configuration.

									</div>
									<div class="clear"></div>
								</div>

								<div class="wdm_row">
									<div class='avail_fac' style="width:90%">
										<input type='hidden' id='select_fac'
										       name='<?php echo WPSOLR_Option::OPTION_SEARCH_FIELDS; ?>[<?php echo WPSOLR_Option::OPTION_SEARCH_FIELDS_FIELDS; ?>]'
										       value='<?php echo $selected_values ?>'>

										<ul id="sortable1" class="wdm_ul connectedSortable">
											<?php
											if ( $selected_values != '' ) {
												foreach ( $selected_array as $selected_val ) {
													if ( $selected_val != '' ) {
														if ( substr( $selected_val, ( strlen( $selected_val ) - 4 ), strlen( $selected_val ) ) == "_str" ) {
															$dis_text = substr( $selected_val, 0, ( strlen( $selected_val ) - 4 ) );
														} else {
															$dis_text = $selected_val;
														}
														?>
														<li id='<?php echo $selected_val; ?>'
														    class='ui-state-default facets facet_selected'>

															<img src='<?php echo $img_path; ?>'
															     class='plus_icon'
															     style='display:none'>
															<img src='<?php echo $minus_path ?>'
															     class='minus_icon'
															     style='display:inline'
															     title='Click to remove the field from the search'>
															<span style="float:left;width: 80%;">
																<?php echo $dis_text; ?>
															</span>

															<div>&nbsp;</div>

															<?php
															$search_field_boost = empty( $solr_search_fields_boosts_options[ $selected_val ] )
																? '' : $solr_search_fields_boosts_options[ $selected_val ];
															?>
															<div class="wdm_row" style="top-margin:5px;">
																<div class='col_left'>Boost field</div>
																<div class='col_right'>
																	<input type='input'
																		<?php echo empty( $search_field_boost ) ? 'style="border-color:red;"' : ''; ?>
																		   class='wpsolr_field_boost_factor_class'
																		   name='<?php echo WPSOLR_Option::OPTION_SEARCH_FIELDS; ?>[<?php echo WPSOLR_Option::OPTION_SEARCH_FIELDS_BOOST; ?>][<?php echo $selected_val; ?>]'
																		   value='<?php echo esc_attr( $search_field_boost ); ?>'
																	/>
																	<?php echo empty( $search_field_boost ) ? "<span class='res_err'>Please enter a number > 0. Examples: '0.5', '2', '3.1'</span>" : ''; ?>
																	<p>
																		Set a boost factor to increase or decrease
																		that
																		particular field's importance in the search.
																		Like '0.4', '2', '3.5'. Default value is
																		'1'.
																		<a target="__new"
																		   href="https://cwiki.apache.org/confluence/display/solr/The+DisMax+Query+Parser#TheDisMaxQueryParser-Theqf(QueryFields)Parameter">See
																			Solr boost</a>
																	</p>

																</div>
																<div class="clear"></div>
															</div>

															<?php
															$solr_search_fields_terms_boosts = empty( $solr_search_fields_terms_boosts_options[ $selected_val ] )
																? '' : $solr_search_fields_terms_boosts_options[ $selected_val ];
															?>
															<div class="wdm_row" style="top-margin:5px;">
																<div class='col_left'>Boost values</div>
																<div class='col_right'>
																	<textarea
																		class='wpsolr_field_boost_term_factor_class'
																		rows="5"
																		placeholder="solr^0.5&#10;apache solr^2.5&#10;apache solr search^3"
																		name="<?php echo WPSOLR_Option::OPTION_SEARCH_FIELDS; ?>[<?php echo WPSOLR_Option::OPTION_SEARCH_FIELDS_TERMS_BOOST; ?>][<?php echo $selected_val; ?>]"
																	><?php echo esc_attr( $solr_search_fields_terms_boosts ); ?></textarea>

																	<p>
																		Boost results that have
																		field '<?php echo $selected_val; ?>' that
																		matches
																		a specific value
																		<a target="__new"
																		   href="https://cwiki.apache.org/confluence/display/solr/The+DisMax+Query+Parser#TheDisMaxQueryParser-Thebq(BoostQuery)Parameter">See
																			Solr boost query</a>
																	</p>

																</div>
																<div class="clear"></div>
															</div>

														</li>

													<?php }
												}
											}
											foreach ( $built_in as $built_fac ) {
												if ( $built_fac != '' ) {
													$buil_fac = strtolower( $built_fac );
													if ( substr( $buil_fac, ( strlen( $buil_fac ) - 4 ), strlen( $buil_fac ) ) == "_str" ) {
														$dis_text = substr( $buil_fac, 0, ( strlen( $buil_fac ) - 4 ) );
													} else {
														$dis_text = $buil_fac;
													}

													if ( ! in_array( $buil_fac, $selected_array ) ) {

														echo "<li id='$buil_fac' class='ui-state-default facets'>$dis_text
                                                                                                    <img src='$img_path'  class='plus_icon' style='display:inline' title='Click to add the field from the search'>
                                                                                                <img src='$minus_path' class='minus_icon' style='display:none'></li>";
													}
												}
											}
											?>


										</ul>
									</div>

									<div class="clear"></div>
								</div>

								<div class='wdm_row'>
									<div class="submit">
										<?php if ( $license_manager->get_license_is_activated( OptionLicenses::LICENSE_PACKAGE_CORE ) ) { ?>
											<div
												class="wpsolr_premium_block_class"><?php echo $license_manager->show_premium_link( OptionLicenses::LICENSE_PACKAGE_CORE, OptionLicenses::TEXT_LICENSE_ACTIVATED, true, true ); ?></div>
											<input name="save_fields_options_form" id="save_fields_options_form"
											       type="submit" class="button-primary wdm-save"
											       value="Save Options"/>
										<?php } else { ?>
											<?php echo $license_manager->show_premium_link( OptionLicenses::LICENSE_PACKAGE_CORE, 'Save Options', true, true ); ?>
											<br/>
										<?php } ?>
									</div>
								</div>
							</div>
						</form>
					</div>
					<?php
					break;

				case 'facet_opt':
					$solr_options = get_option( 'wdm_solr_form_data' );
					$checked_fls = $solr_options['cust_fields'] . ',' . $solr_options['taxonomies'];

					$checked_fields = explode( ',', $checked_fls );
					$img_path       = plugins_url( 'images/plus.png', __FILE__ );
					$minus_path     = plugins_url( 'images/minus.png', __FILE__ );
					$built_in       = array( 'Type', 'Author', 'Categories', 'Tags' );
					$built_in       = array_merge( $built_in, $checked_fields );

					$built_in_can_show_hierarchy = explode( ',', 'Categories' . ',' . $solr_options['taxonomies'] );
					?>
					<div id="solr-facets-options" class="wdm-vertical-tabs-content">
						<form action="options.php" method="POST" id='fac_settings_form'>
							<?php
							settings_fields( 'solr_facet_options' );
							$solr_fac_options             = get_option( 'wdm_solr_facet_data' );
							$selected_facets_value        = $solr_fac_options['facets'];
							$selected_facets_is_hierarchy = ! empty( $solr_fac_options[ WPSOLR_Option::OPTION_FACET_FACETS_TO_SHOW_AS_HIERARCH ] ) ? $solr_fac_options[ WPSOLR_Option::OPTION_FACET_FACETS_TO_SHOW_AS_HIERARCH ] : array();
							$selected_facets_labels       = WPSOLR_Global::getOption()->get_facets_labels();
							$selected_facets_item_labels  = WPSOLR_Global::getOption()->get_facets_items_labels();
							if ( $selected_facets_value != '' ) {
								$selected_array = explode( ',', $selected_facets_value );
							} else {
								$selected_array = array();
							}
							?>
							<div class='wrapper'>
								<h4 class='head_div'>Filters Options</h4>

								<div class="wdm_note">

									In this section, you will choose which data you want to display as filters in
									your search results. filters are extra filters usually seen in the left hand
									side of the results, displayed as a list of links. You can add filters only
									to data you've selected to be indexed.

								</div>
								<div class="wdm_note">
									<h4>Instructions</h4>
									<ul class="wdm_ul wdm-instructions">
										<li>Click on the 'Plus' icon to add the filters</li>
										<li>Click on the 'Minus' icon to remove the filters</li>
										<li>Sort the items in the order you want to display them by dragging and
											dropping them at the desired place
										</li>
									</ul>
								</div>

								<div class="wdm_row">
									<div class='avail_fac' style="width:100%">
										<h4>Available items for filters</h4>
										<input type='hidden' id='select_fac' name='wdm_solr_facet_data[facets]'
										       value='<?php echo $selected_facets_value ?>'>

										<ul id="sortable1" class="wdm_ul connectedSortable">
											<?php
											if ( $selected_facets_value != '' ) {
												$disabled = $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_CORE );
												foreach ( $selected_array as $selected_val ) {
													if ( $selected_val != '' ) {
														if ( substr( $selected_val, ( strlen( $selected_val ) - 4 ), strlen( $selected_val ) ) == "_str" ) {
															$dis_text = substr( $selected_val, 0, ( strlen( $selected_val ) - 4 ) );
														} else {
															$dis_text = $selected_val;
														}
														$is_hierarchy       = isset( $selected_facets_is_hierarchy[ $selected_val ] );
														$can_show_hierarchy = in_array( $selected_val, array_map( 'strtolower', $built_in_can_show_hierarchy ) );
														?>
														<li id='<?php echo $selected_val; ?>'
														    class='ui-state-default facets facet_selected'>
															<span
																style="float:left;width: 300px;"><?php echo $dis_text; ?></span>
															<img src='<?php echo $img_path; ?>'
															     class='plus_icon'
															     style='display:none'>
															<img src='<?php echo $minus_path ?>'
															     class='minus_icon'
															     style='display:inline'
															     title='Click to Remove the filter'>
															<br/>

															<div class="wdm_row" style="top-margin:5px;">
																<div class='col_left'>
																	<?php echo $license_manager->show_premium_link( OptionLicenses::LICENSE_PACKAGE_CORE, sprintf( '%s label', ucfirst( $selected_val ) ), true ); ?>
																</div>
																<?php
																$facet_label = ! empty( $selected_facets_labels[ $selected_val ] ) ? $selected_facets_labels[ $selected_val ] : '';
																?>
																<div class='col_right'>
																	<input type='text'
																	       name='wdm_solr_facet_data[<?php echo WPSOLR_Option::OPTION_FACET_FACETS_LABEL; ?>][<?php echo $selected_val; ?>]'
																	       value='<?php echo esc_attr( $facet_label ); ?>'
																		<?php echo $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_CORE ); ?>
																	/>
																	<p>
																		Will be shown on the front-end (and
																		translated in WPML/POLYLANG string modules).
																		Leave empty if you wish to use the current
																		facet
																		name "<?php echo $dis_text; ?>".
																	</p>

																</div>
																<div class="clear"></div>
															</div>
															<?php
															if ( 'type' === $selected_val ) {
																$all_post_types = get_post_types();
																$post_types     = array( 'attachment' );
																foreach ( $all_post_types as $post_type ) {
																	if ( $post_type != 'attachment' && $post_type != 'revision' && $post_type != 'nav_menu_item' ) {
																		array_push( $post_types, $post_type );
																	}
																}

																foreach ( $post_types as $post_type ) {
																	?>
																	<div class="wdm_row" style="top-margin:5px;">
																		<div class='col_left'>
																			<?php echo $license_manager->show_premium_link( OptionLicenses::LICENSE_PACKAGE_CORE, sprintf( '%s label', ucfirst( $post_type ) ), true ); ?>
																		</div>
																		<?php
																		$facet_label = ( ! empty( $selected_facets_item_labels[ $selected_val ] ) && ! empty( $selected_facets_item_labels[ $selected_val ][ $post_type ] ) )
																			? $selected_facets_item_labels[ $selected_val ][ $post_type ] : '';
																		?>
																		<div class='col_right'>
																			<input type='text'
																			       name='wdm_solr_facet_data[<?php echo WPSOLR_Option::OPTION_FACET_FACETS_ITEMS_LABEL; ?>][<?php echo $selected_val; ?>][<?php echo $post_type; ?>]'
																			       value='<?php echo esc_attr( $facet_label ); ?>'
																				<?php echo $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_CORE ); ?>
																			/>
																			<p>
																				Will be shown on the front-end (and
																				translated in WPML/POLYLANG string
																				modules).
																				Leave empty if you wish to use the
																				current facet
																				name "<?php echo $post_type; ?>".
																			</p>

																		</div>
																		<div class="clear"></div>
																	</div>
																<?php }
															} ?>

															<?php if ( $can_show_hierarchy ) { ?>
																<div class="wdm_row" style="top-margin:5px;">
																	<div class='col_left'>
																		<?php echo $license_manager->show_premium_link( OptionLicenses::LICENSE_PACKAGE_CORE, 'Show the hierarchy', true ); ?>
																	</div>
																	<div class='col_right'>
																		<input type='checkbox'
																		       name='wdm_solr_facet_data[<?php echo WPSOLR_Option::OPTION_FACET_FACETS_TO_SHOW_AS_HIERARCH; ?>][<?php echo $selected_val; ?>]'
																		       value='1'
																			<?php echo checked( $is_hierarchy ); ?>
																			<?php echo ( empty( $disabled ) && $can_show_hierarchy ) ? '' : 'disabled'; ?>
																		/>
																		Select to display the facet as a tree

																	</div>
																	<div class="clear"></div>
																</div>
															<?php } ?>

														</li>

													<?php }
												}
											}
											foreach ( $built_in as $built_fac ) {
												if ( $built_fac != '' ) {
													$buil_fac = strtolower( $built_fac );
													if ( substr( $buil_fac, ( strlen( $buil_fac ) - 4 ), strlen( $buil_fac ) ) == "_str" ) {
														$dis_text = substr( $buil_fac, 0, ( strlen( $buil_fac ) - 4 ) );
													} else {
														$dis_text = $buil_fac;
													}

													if ( ! in_array( $buil_fac, $selected_array ) ) {

														echo "<li id='$buil_fac' class='ui-state-default facets'>$dis_text
                                                                                                    <img src='$img_path'  class='plus_icon' style='display:inline' title='Click to Add the Facet'>
                                                                                                <img src='$minus_path' class='minus_icon' style='display:none'></li>";
													}
												}
											}
											?>


										</ul>
									</div>

									<div class="clear"></div>
								</div>

								<div class='wdm_row'>
									<div class="submit">
										<input name="save_facets_options_form" id="save_facets_options_form"
										       type="submit" class="button-primary wdm-save"
										       value="Save Options"/>


									</div>
								</div>
							</div>
						</form>
					</div>
					<?php
					break;

				case 'sort_opt':
					$img_path = plugins_url( 'images/plus.png', __FILE__ );
					$minus_path  = plugins_url( 'images/minus.png', __FILE__ );

					$built_in = WPSolrSearchSolrClient::get_sort_options();

					?>
					<div id="solr-sort-options" class="wdm-vertical-tabs-content">
						<form action="options.php" method="POST" id='sort_settings_form'>
							<?php
							settings_fields( 'solr_sort_options' );
							$selected_sort_value = WPSOLR_Global::getOption()->get_sortby_items();
							$selected_array      = WPSOLR_Global::getOption()->get_sortby_items_as_array();
							?>
							<div class='wrapper'>
								<h4 class='head_div'>Sort Options</h4>

								<div class="wdm_note">

									In this section, you will choose which elements will be displayed as sort
									criteria for your search results, and in which order.

								</div>
								<div class="wdm_note">
									<h4>Instructions</h4>
									<ul class="wdm_ul wdm-instructions">
										<li>Click on the 'Plus' icon to add the sort</li>
										<li>Click on the 'Minus' icon to remove the sort</li>
										<li>Sort the items in the order you want to display them by dragging and
											dropping them at the desired place
										</li>
									</ul>
								</div>

								<div class="wdm_row">
									<div class='col_left'>Default when no sort is selected by the user</div>
									<div class='col_right'>
										<select name="wdm_solr_sortby_data[sort_default]">
											<?php foreach ( $built_in as $sort ) {
												$selected = WPSOLR_Global::getOption()->get_sortby_default() == $sort['code'] ? 'selected' : '';
												?>
												<option
													value="<?php echo $sort['code'] ?>" <?php echo $selected ?> ><?php echo $sort['label'] ?></option>
											<?php } ?>
										</select>
									</div>
								</div>

								<div class="wdm_row">
									<div class='avail_fac'>
										<h4>Activate/deactivate items in the sort list</h4>
										<input type='hidden' id='select_sort' name='wdm_solr_sortby_data[sort]'
										       value='<?php echo $selected_sort_value ?>'>


										<ul id="sortable_sort" class="wdm_ul connectedSortable_sort">
											<?php
											if ( $selected_sort_value != '' ) {
												foreach ( $selected_array as $sort_code ) {
													if ( $sort_code != '' ) {
														$sort     = WPSolrSearchSolrClient::get_sort_option_from_code( $sort_code, null );
														$dis_text = is_array( $sort ) ? $sort['label'] : $sort_code;

														echo "<li id='$sort_code' class='ui-state-default facets sort_selected'>$dis_text
                                                                                                    <img src='$img_path'  class='plus_icon_sort' style='display:none'>
                                                                                                <img src='$minus_path' class='minus_icon_sort' style='display:inline' title='Click to Remove the Sort'></li>";
													}
												}
											}
											foreach ( $built_in as $built ) {
												if ( $built != '' ) {
													$buil_fac = $built['code'];
													$dis_text = $built['label'];

													if ( ! in_array( $buil_fac, $selected_array ) ) {

														echo "<li id='$buil_fac' class='ui-state-default facets'>$dis_text
                                                                                                    <img src='$img_path'  class='plus_icon_sort' style='display:inline' title='Click to Add the Sort'>
                                                                                                <img src='$minus_path' class='minus_icon_sort' style='display:none'></li>";
													}
												}
											}
											?>


										</ul>
									</div>

									<div class="clear"></div>
								</div>

								<div class='wdm_row'>
									<div class="submit">
										<input name="save_sort_options_form" id="save_sort_options_form"
										       type="submit" class="button-primary wdm-save"
										       value="Save Options"/>


									</div>
								</div>
							</div>
						</form>
					</div>
					<?php
					break;

				case 'localization_options':
					WpSolrExtensions::require_once_wpsolr_extension_admin_options( WpSolrExtensions::OPTION_LOCALIZATION );
					break;

			}

			?>

		</div>
		<?php
		break;

	case 'solr_plugins':
	?>
	<div id="solr-option-tab">

		<?php

		$subtabs = array(
			'extension_woocommerce_opt'         => 'WooCommerce',
			'extension_acf_opt'                 => 'ACF',
			'extension_types_opt'               => 'Toolset Types',
			'extension_wpml_opt'                => 'WPML',
			'extension_polylang_opt'            => 'Polylang',
			// It seems impossible to map qTranslate X structure (1 post/many languages) in WPSOLR's (1 post/1 language)
			/* 'extension_qtranslatex_opt' => 'qTranslate X', */
			'extension_groups_opt'              => 'Groups',
			'extension_s2member_opt'            => 's2Member',
			'extension_bbpress_opt'             => 'bbPress',
			'extension_embed_any_document_opt'  => 'Embed Any Document',
			'extension_pdf_embedder_opt'        => 'PDF Embedder',
			'extension_google_doc_embedder_opt' => 'Google Doc Embedder'
		);

		$subtab = wpsolr_admin_sub_tabs( $subtabs );

		switch ( $subtab ) {
			case 'extension_groups_opt':
				WpSolrExtensions::require_once_wpsolr_extension_admin_options( WpSolrExtensions::EXTENSION_GROUPS );
				break;

			case 'extension_s2member_opt':
				WpSolrExtensions::require_once_wpsolr_extension_admin_options( WpSolrExtensions::EXTENSION_S2MEMBER );
				break;

			case 'extension_wpml_opt':
				WpSolrExtensions::require_once_wpsolr_extension_admin_options( WpSolrExtensions::EXTENSION_WPML );
				break;

			case 'extension_polylang_opt':
				WpSolrExtensions::require_once_wpsolr_extension_admin_options( WpSolrExtensions::EXTENSION_POLYLANG );
				break;

			case 'extension_qtranslatex_opt':
				WpSolrExtensions::require_once_wpsolr_extension_admin_options( WpSolrExtensions::EXTENSION_QTRANSLATEX );
				break;

			case 'extension_woocommerce_opt':
				WpSolrExtensions::require_once_wpsolr_extension_admin_options( WpSolrExtensions::EXTENSION_WOOCOMMERCE );
				break;

			case 'extension_acf_opt':
				WpSolrExtensions::require_once_wpsolr_extension_admin_options( WpSolrExtensions::EXTENSION_ACF );
				break;

			case 'extension_types_opt':
				WpSolrExtensions::require_once_wpsolr_extension_admin_options( WpSolrExtensions::EXTENSION_TYPES );
				break;

			case 'extension_bbpress_opt':
				WpSolrExtensions::require_once_wpsolr_extension_admin_options( WpSolrExtensions::EXTENSION_BBPRESS );
				break;

			case 'extension_embed_any_document_opt':
				WpSolrExtensions::require_once_wpsolr_extension_admin_options( WpSolrExtensions::EXTENSION_EMBED_ANY_DOCUMENT );
				break;

			case 'extension_pdf_embedder_opt':
				WpSolrExtensions::require_once_wpsolr_extension_admin_options( WpSolrExtensions::EXTENSION_PDF_EMBEDDER );
				break;

			case 'extension_google_doc_embedder_opt':
				WpSolrExtensions::require_once_wpsolr_extension_admin_options( WpSolrExtensions::EXTENSION_GOOGLE_DOC_EMBEDDER );
				break;
		}

		break;

		case 'solr_operations':

			WpSolrExtensions::require_once_wpsolr_extension( WpSolrExtensions::OPTION_INDEXES, true );
			$option_indexes_object = new OptionIndexes();

			// Create the tabs from the Solr indexes already configured
			$subtabs = array();
			foreach ( $option_indexes_object->get_indexes() as $index_indice => $index ) {
				$subtabs[ $index_indice ] = isset( $index['index_name'] ) ? $index['index_name'] : 'Index with no name';
			}

			if ( empty( $subtabs ) ) {
				echo "Please create a Solr index configuration first.";

				return;
			}

			// Create subtabs on the left side
			$current_index_indice = wpsolr_admin_sub_tabs( $subtabs );
			if ( ! $option_indexes_object->has_index( $current_index_indice ) ) {
				$current_index_indice = key( $subtabs );
			}
			$current_index_name = $subtabs[ $current_index_indice ];


			try {
				$solr                             = WPSolrIndexSolrClient::create( $current_index_indice );
				$count_nb_documents_to_be_indexed = $solr->count_nb_documents_to_be_indexed();
			} catch ( Exception $e ) {
				echo '<b>An error occured while trying to connect to the Solr server:</b> <br>' . htmlentities( $e->getMessage() );

				return;
			}

			?>

			<div id="solr-operations-tab"
			     class="wdm-vertical-tabs-content">
				<form action="options.php" method='post' id='solr_actions'>
					<input type='hidden' id='solr_index_indice' name='wdm_solr_operations_data[solr_index_indice]'
					       value="<?php echo $current_index_indice; ?>">
					<?php

					settings_fields( 'solr_operations_options' );

					$solr_operations_options = get_option( 'wdm_solr_operations_data' );

					$batch_size = empty( $solr_operations_options['batch_size'][ $current_index_indice ] ) ? '100' : $solr_operations_options['batch_size'][ $current_index_indice ];

					?>
					<input type='hidden' id='adm_path' value='<?php echo admin_url(); ?>'> <!-- for ajax -->
					<div class='wrapper'>
						<h4 class='head_div'>Content of the Solr index "<?php echo $current_index_name ?>"</h4>

						<div class="wdm_note">
							<div>
								<?php
								try {
									$nb_documents_in_index = $solr->get_count_documents();
									echo sprintf( "<b>A total of %s documents are currently in your index \"%s\"</b>", $nb_documents_in_index, $current_index_name );
								} catch ( Exception $e ) {
									echo '<b>Please check your Solr Hosting, an exception occured while calling your Solr server:</b> <br><br>' . htmlentities( $e->getMessage() );
								}
								?>
							</div>
							<?php if ( $count_nb_documents_to_be_indexed >= 0 ): ?>
								<div><b>
										<?php
										echo $count_nb_documents_to_be_indexed;

										// Reset value so it's not displayed next time this page is displayed.
										//$solr->update_count_documents_indexed_last_operation();
										?>
									</b> document(s) remain to be indexed
								</div>
							<?php endif ?>
						</div>
						<div class="wdm_row">
							<p>The indexing is <b>incremental</b>: only documents updated after the last operation
								are sent to the index.</p>

							<p>So, the first operation will index all documents, by batches of
								<b><?php echo $batch_size; ?></b> documents.</p>

							<p>If a <b>timeout</b> occurs, you just have to click on the button again: the process
								will restart from where it stopped.</p>

							<p>If you need to reindex all again, delete the index first.</p>
						</div>
						<div class="wdm_row">
							<div class='col_left'>Number of documents sent in Solr as a single commit.<br>
								You can change this number to control indexing's performance.
							</div>
							<div class='col_right'>
								<input type='text' id='batch_size'
								       name='wdm_solr_operations_data[batch_size][<?php echo $current_index_indice ?>]'
								       placeholder="Enter a Number"
								       value="<?php echo $batch_size; ?>">
								<span class='res_err'></span><br>
							</div>
							<div class="clear"></div>
							<div class='col_left'>
								<?php echo $license_manager->show_premium_link( OptionLicenses::LICENSE_PACKAGE_CORE, 'Display debug infos during indexing', true ); ?>
							</div>
							<div class='col_right'>

								<input type='checkbox'
								       id='is_debug_indexing'
								       name='wdm_solr_operations_data[is_debug_indexing][<?php echo $current_index_indice ?>]'
								       value='is_debug_indexing'
									<?php echo $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_CORE ); ?>
									<?php checked( 'is_debug_indexing', isset( $solr_operations_options['is_debug_indexing'][ $current_index_indice ] ) ? $solr_operations_options['is_debug_indexing'][ $current_index_indice ] : '' ); ?>>
								<span class='res_err'></span><br>
							</div>
							<div class="clear"></div>
							<div class='col_left'>
								<?php echo $license_manager->show_premium_link( OptionLicenses::LICENSE_PACKAGE_CORE, 'Re-index all the data in place.', true ); ?>
							</div>
							<div class='col_right'>

								<input type='checkbox'
								       id='is_reindexing_all_posts'
								       name='is_reindexing_all_posts'
								       value='is_reindexing_all_posts'
									<?php echo $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_CORE ); ?>
									<?php checked( true, false ); ?>>

								If you check this option, it will restart the indexing from start, without deleting the
								data already in the Solr index.
								<span class='res_err'></span><br>
							</div>
							<div class="clear"></div>
						</div>
						<div class="wdm_row">
							<div class="submit">
								<input name="solr_start_index_data" type="submit" class="button-primary wdm-save"
								       id='solr_start_index_data'
								       value="Synchronize Wordpress with '<?php echo $current_index_name ?>' "/>
								<input name="solr_stop_index_data" type="submit" class="button-primary wdm-save"
								       id='solr_stop_index_data' value="Stop current indexing"
								       style="visibility: hidden;"/>
								<span class='status_index_icon'></span>

								<input name="solr_delete_index" type="submit" class="button-primary wdm-save"
								       id="solr_delete_index"
								       value="Empty '<?php echo $current_index_name ?>' "/>


								<span class='status_index_message'></span>
								<span class='status_debug_message'></span>
								<span class='status_del_message'></span>
							</div>
						</div>
					</div>
				</form>
			</div>
			<?php
			break;

		case 'wpsolr_licenses' :
			WpSolrExtensions::require_once_wpsolr_extension_admin_options( WpSolrExtensions::OPTION_LICENSES );
			break;

		}
		?>


	</div>
	<?php


}

function wpsolr_admin_tabs( $current = 'solr_indexes' ) {

	// Get default search solr index indice
	WpSolrExtensions::require_once_wpsolr_extension( WpSolrExtensions::OPTION_INDEXES, true );
	$option_indexes            = new OptionIndexes();
	$default_search_solr_index = $option_indexes->get_default_search_solr_index();

	$nb_indexes        = count( $option_indexes->get_indexes() );
	$are_there_indexes = ( $nb_indexes >= 0 );

	$tabs                      = array();
	$tabs['solr_presentation'] = 'What is WPSOLR ?';
	$tabs['solr_indexes']      = $are_there_indexes ? '1. Define your Solr Indexes' : '1. Define your Solr Index';
	if ( $are_there_indexes ) {
		$tabs['solr_option']     = sprintf( "2. Define your search with '%s'",
			! isset( $default_search_solr_index )
				? $are_there_indexes ? "<span class='text_error'>No index selected</span>" : ''
				: $option_indexes->get_index_name( $default_search_solr_index ) );
		$tabs['solr_plugins']    = '3. Define which plugins to work with';
		$tabs['solr_operations'] = '4. Send your data';
		//$tabs['wpsolr_licenses'] = '5. AddOns';
	}

	echo '<div id="icon-themes" class="icon32"><br></div>';
	echo '<h2 class="nav-tab-wrapper wpsolr-tour-navigation-tabs">';
	foreach ( $tabs as $tab => $name ) {
		$class = ( $tab == $current ) ? ' nav-tab-active' : '';
		echo "<a class='nav-tab$class' href='admin.php?page=solr_settings&tab=$tab'>$name</a>";

	}
	echo '</h2>';
}


function wpsolr_admin_sub_tabs( $subtabs, $before = null ) {

	// Tab selected by the user
	$tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'solr_presentation';

	if ( isset ( $_GET['subtab'] ) ) {

		$current_subtab = $_GET['subtab'];

	} else {
		// No user selection: use the first subtab in the list
		$current_subtab = key( $subtabs );
	}

	echo '<div id="icon-themes" class="icon32"><br></div>';
	echo '<h2 class="nav-tab-wrapper wdm-vertical-tabs">';

	if ( isset( $before ) ) {
		echo "$before<div style='clear: both;margin-bottom: 10px;'></div>";
	}

	foreach ( $subtabs as $subtab => $name ) {
		$class = ( $subtab == $current_subtab ) ? ' nav-tab-active' : '';

		if ( false === strpos( $name, 'wpsolr_premium_class' ) ) {
			echo "<a class='nav-tab$class' href='admin.php?page=solr_settings&tab=$tab&subtab=$subtab'>$name</a>";
		} else {
			echo $name;
		}

	}

	echo '</h2>';

	return $current_subtab;
}
