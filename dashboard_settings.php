<?php
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

	register_setting( 'solr_form_options', 'wdm_solr_form_data' );
	register_setting( 'solr_res_options', 'wdm_solr_res_data' );
	register_setting( 'solr_facet_options', 'wdm_solr_facet_data' );
	register_setting( 'solr_sort_options', 'wdm_solr_sortby_data' );
	register_setting( 'solr_localization_options', 'wdm_solr_localization_data' );
	register_setting( 'solr_extension_groups_options', 'wdm_solr_extension_groups_data' );
	register_setting( 'solr_extension_s2member_options', 'wdm_solr_extension_s2member_data' );
	register_setting( 'solr_extension_wpml_options', 'wdm_solr_extension_wpml_data' );
	register_setting( 'solr_operations_options', 'wdm_solr_operations_data' );
}

function fun_add_solr_settings() {
	$img_url = plugins_url( 'images/WPSOLRDashicon.png', __FILE__ );
	add_menu_page( 'WPSOLR', 'WPSOLR', 'manage_options', 'solr_settings', 'fun_set_solr_options', $img_url );
	wp_enqueue_style( 'dashboard_style', plugins_url( 'css/dashboard_css.css', __FILE__ ) );
	wp_enqueue_script( 'jquery-ui-sortable' );
	wp_enqueue_script( 'dashboard_js1', plugins_url( 'js/dashboard.js', __FILE__ ), array(
		'jquery',
		'jquery-ui-sortable'
	) );

	$plugin_vals = array( 'plugin_url' => plugins_url( 'images/', __FILE__ ) );
	wp_localize_script( 'dashboard_js1', 'plugin_data', $plugin_vals );

	// Google api recaptcha - Used for temporary indexes creation
	wp_enqueue_script( 'google-api-recaptcha', '//www.google.com/recaptcha/api.js', array() );

}

function fun_set_solr_options() {

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
	<div class="page_title"><h1>WPSOLR Settings </h1></div>

	<?php
	if ( isset ( $_GET['tab'] ) ) {
		wpsolr_admin_tabs( $_GET['tab'] );
	} else {
		wpsolr_admin_tabs( 'solr_indexes' );
	}

	if ( isset ( $_GET['tab'] ) ) {
		$tab = $_GET['tab'];
	} else {
		$tab = 'solr_indexes';
	}

	switch ( $tab ) {
	case 'solr_indexes' :
		WpSolrExtensions::require_once_wpsolr_extension_admin_options( WpSolrExtensions::OPTION_INDEXES );
		break;

	case 'solr_option':
		?>
		<div id="solr-option-tab">

			<?php

			$subtabs = array(
				'result_opt'           => 'Result Options',
				'index_opt'            => 'Indexing Options',
				'facet_opt'            => 'Facets Options',
				'sort_opt'             => 'Sort Options',
				'localization_options' => 'Localization Options',
			);

			$subtab = wpsolr_admin_sub_tabs( $subtabs );

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
										Replace WordPress Default Search<br/>
										Warning: permalinks must be activated.
									</div>
									<div class='col_right'>
										<input type='checkbox' name='wdm_solr_res_data[default_search]'
										       value='1'
											<?php checked( '1', isset( $solr_res_options['default_search'] ) ? $solr_res_options['default_search'] : '0' ); ?>>
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
									<div class='col_left'>Display suggestions (Did you mean?)</div>
									<div class='col_right'>
										<input type='checkbox'
										       name='wdm_solr_res_data[<?php echo 'spellchecker' ?>]'
										       value='spellchecker'
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
									<div class='col_left'>No. of values to be displayed by facets</div>
									<div class='col_right'>
										<input type='text' id='number_of_fac' name='wdm_solr_res_data[no_fac]'
										       placeholder="Enter a Number"
										       value="<?php echo empty( $solr_res_options['no_fac'] ) ? '20' : $solr_res_options['no_fac']; ?>"><span
											class='fac_err'></span> <br>
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


					$posts          = get_post_types();
					$args       = array(
						'public'   => true,
						'_builtin' => false

					);
					$output     = 'names'; // or objects
					$operator   = 'and'; // 'and' or 'or'
					$taxonomies = get_taxonomies( $args, $output, $operator );
					global $wpdb;
					$limit      = (int) apply_filters( 'postmeta_form_limit', 30 );
					$keys       = $wpdb->get_col( "
                                                                    SELECT meta_key
                                                                    FROM $wpdb->postmeta
                                                                    WHERE meta_key!='bwps_enable_ssl' 
                                                                    GROUP BY meta_key
                                                                    HAVING meta_key NOT LIKE '\_%'
                                                                    ORDER BY meta_key" );
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
										Index post excerpt.<br/>
										Excerpt will be added to the post content, and be searchable, highlighted, and
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
									<div class='col_left'>Post types to be indexed</div>
									<div class='col_right'>
										<input type='hidden' name='wdm_solr_form_data[p_types]' id='p_types'>
										<?php
										$post_types_opt = $solr_options['p_types'];
										foreach ( $post_types as $type ) {
											?>
											<input type='checkbox' name='post_tys' value='<?php echo $type ?>'
												<?php if ( strpos( $post_types_opt, $type ) !== false ) { ?> checked <?php } ?>> <?php echo $type ?>
											<br>
											<?php
										}
										?>

									</div>
									<div class="clear"></div>
								</div>

								<div class="wdm_row">
									<div class='col_left'>Attachment types to be indexed</div>
									<div class='col_right'>
										<input type='hidden' name='wdm_solr_form_data[attachment_types]'
										       id='attachment_types'>
										<?php
										$attachment_types_opt = $solr_options['attachment_types'];
										foreach ( $allowed_attachments_types as $type ) {
											?>
											<input type='checkbox' name='attachment_types'
											       value='<?php echo $type ?>'
												<?php if ( strpos( $attachment_types_opt, $type ) !== false ) { ?> checked <?php } ?>> <?php echo $type ?>
											<br>
											<?php
										}
										?>
									</div>
									<div class="clear"></div>
								</div>

								<div class="wdm_row">
									<div class='col_left'>Custom taxonomies to be indexed</div>
									<div class='col_right'>
										<div class='cust_tax'><!--new div class given-->
											<input type='hidden' name='wdm_solr_form_data[taxonomies]'
											       id='tax_types'>
											<?php
											$tax_types_opt = $solr_options['taxonomies'];
											if ( count( $taxonomies ) > 0 ) {
												foreach ( $taxonomies as $type ) {
													?>

													<input type='checkbox' name='taxon'
													       value='<?php echo $type . "_str" ?>'
														<?php if ( strpos( $tax_types_opt, $type . "_str" ) !== false ) { ?> checked <?php } ?>
														> <?php echo $type ?> <br>
													<?php
												}

											} else {
												echo 'None';
											} ?>
										</div>
									</div>
									<div class="clear"></div>
								</div>

								<div class="wdm_row">
									<div class='col_left'>Custom Fields to be indexed</div>

									<div class='col_right'>
										<input type='hidden' name='wdm_solr_form_data[cust_fields]'
										       id='field_types'>

										<div class='cust_fields'><!--new div class given-->
											<?php
											$field_types_opt = $solr_options['cust_fields'];
											if ( count( $keys ) > 0 ) {
												foreach ( $keys as $key ) {
													?>

													<input type='checkbox' name='cust_fields'
													       value='<?php echo $key . "_str" ?>'
														<?php if ( strpos( $field_types_opt, $key . "_str" ) !== false ) { ?> checked <?php } ?>> <?php echo $key ?>
													<br>
													<?php
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

				case 'facet_opt':
					$solr_options   = get_option( 'wdm_solr_form_data' );
					$checked_fls = $solr_options['cust_fields'] . ',' . $solr_options['taxonomies'];

					$checked_fields = array();
					$checked_fields = explode( ',', $checked_fls );
					$img_path       = plugins_url( 'images/plus.png', __FILE__ );
					$minus_path     = plugins_url( 'images/minus.png', __FILE__ );
					$built_in       = array( 'Type', 'Author', 'Categories', 'Tags' );
					$built_in       = array_merge( $built_in, $checked_fields );
					?>
					<div id="solr-facets-options" class="wdm-vertical-tabs-content">
						<form action="options.php" method="POST" id='fac_settings_form'>
							<?php
							settings_fields( 'solr_facet_options' );
							$solr_fac_options      = get_option( 'wdm_solr_facet_data' );
							$selected_facets_value = $solr_fac_options['facets'];
							if ( $selected_facets_value != '' ) {
								$selected_array = explode( ',', $selected_facets_value );
							} else {
								$selected_array = array();
							}
							?>
							<div class='wrapper'>
								<h4 class='head_div'>Facets Options</h4>

								<div class="wdm_note">

									In this section, you will choose which data you want to display as facets in
									your search results. Facets are extra filters usually seen in the left hand
									side of the results, displayed as a list of links. You can add facets only
									to data you've selected to be indexed.

								</div>
								<div class="wdm_note">
									<h4>Instructions</h4>
									<ul class="wdm_ul wdm-instructions">
										<li>Click on the 'Plus' icon to add the facets</li>
										<li>Click on the 'Minus' icon to remove the facets</li>
										<li>Sort the items in the order you want to display them by dragging and
											dropping them at the desired plcae
										</li>
									</ul>
								</div>

								<div class="wdm_row">
									<div class='avail_fac'>
										<h4>Available items for facets</h4>
										<input type='hidden' id='select_fac' name='wdm_solr_facet_data[facets]'
										       value='<?php echo $selected_facets_value ?>'>


										<ul id="sortable1" class="wdm_ul connectedSortable">
											<?php
											if ( $selected_facets_value != '' ) {
												foreach ( $selected_array as $selected_val ) {
													if ( $selected_val != '' ) {
														if ( substr( $selected_val, ( strlen( $selected_val ) - 4 ), strlen( $selected_val ) ) == "_str" ) {
															$dis_text = substr( $selected_val, 0, ( strlen( $selected_val ) - 4 ) );
														} else {
															$dis_text = $selected_val;
														}


														echo "<li id='$selected_val' class='ui-state-default facets facet_selected'>$dis_text
                                                                                                    <img src='$img_path'  class='plus_icon' style='display:none'>
                                                                                                <img src='$minus_path' class='minus_icon' style='display:inline' title='Click to Remove the Facet'></li>";
													}
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
					$img_path    = plugins_url( 'images/plus.png', __FILE__ );
					$minus_path = plugins_url( 'images/minus.png', __FILE__ );

					$checked_fls = array();
					$built_in    = WPSolrSearchSolrClient::get_sort_options();

					?>
					<div id="solr-sort-options" class="wdm-vertical-tabs-content">
						<form action="options.php" method="POST" id='sort_settings_form'>
							<?php
							settings_fields( 'solr_sort_options' );
							$solr_sort_options   = get_option( 'wdm_solr_sortby_data' );
							$selected_sort_value = $solr_sort_options['sort'];
							if ( $selected_sort_value != '' ) {
								$selected_array = explode( ',', $selected_sort_value );
							} else {
								$selected_array = array();
							}
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
												$selected = $solr_sort_options['sort_default'] == $sort['code'] ? 'selected' : '';
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
			'extension_wpml_opt'     => 'WPML',
			'extension_groups_opt'   => 'Groups',
			'extension_s2member_opt' => 's2Member',
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
							<div class='col_left'>Display debug infos during indexing</div>
							<div class='col_right'>

								<input type='checkbox'
								       id='is_debug_indexing'
								       name='wdm_solr_operations_data[is_debug_indexing][<?php echo $current_index_indice ?>]'
								       value='is_debug_indexing'
									<?php checked( 'is_debug_indexing', isset( $solr_operations_options['is_debug_indexing'][ $current_index_indice ] ) ? $solr_operations_options['is_debug_indexing'][ $current_index_indice ] : '' ); ?>>
								<span class='res_err'></span><br>
							</div>
							<div class="clear"></div>
							<div class='col_left'>
								Re-index all the data in place.<br/>
								If you check this option, it will restart the indexing from start, without deleting the
								data already in the Solr index.
							</div>
							<div class='col_right'>

								<input type='checkbox'
								       id='is_reindexing_all_posts'
								       name='is_reindexing_all_posts'
								       value='is_reindexing_all_posts'
									<?php checked( true, false ); ?>>
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


	$tabs = array(
		'solr_indexes'    => 'Solr Indexes',
		'solr_option'     => sprintf( 'Solr Options %s', ! isset( $default_search_solr_index )
			? count( $option_indexes->get_indexes() ) > 0 ? "<span class='text_error'>No index selected</span>" : ''
			: $option_indexes->get_index_name( $default_search_solr_index )
		),
		'solr_plugins'    => 'Plugins Integration',
		'solr_operations' => 'Solr Indexing Batch'
	);
	echo '<div id="icon-themes" class="icon32"><br></div>';
	echo '<h2 class="nav-tab-wrapper">';
	foreach ( $tabs as $tab => $name ) {
		$class = ( $tab == $current ) ? ' nav-tab-active' : '';
		echo "<a class='nav-tab$class' href='admin.php?page=solr_settings&tab=$tab'>$name</a>";

	}
	echo '</h2>';
}


function wpsolr_admin_sub_tabs( $subtabs, $before = null ) {

	// Tab selected by the user
	$tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'solr_indexes';

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
		echo "<a class='nav-tab$class' href='admin.php?page=solr_settings&tab=$tab&subtab=$subtab'>$name</a>";

	}

	echo '</h2>';

	return $current_subtab;
}
