<?php

/**
 * Included file to display admin options
 */
global $license_manager;

WpSolrExtensions::require_once_wpsolr_extension( WpSolrExtensions::OPTION_INDEXES, true );

// Options name
$option_name = OptionIndexes::get_option_name( WpSolrExtensions::OPTION_INDEXES );

// Options object
$option_object = new OptionIndexes();

?>

<?php
global $response_object, $google_recaptcha_site_key, $google_recaptcha_token;
$is_submit_button_form_temporary_index = isset( $_POST['submit_button_form_temporary_index'] );
$form_data                             = WpSolrExtensions::extract_form_data( $is_submit_button_form_temporary_index, array(
		'managed_solr_service_id' => array( 'default_value' => '', 'can_be_empty' => false )
	)
);

?>

<div id="solr-hosting-tab">

	<?php

	// Options data. Loaded after the POST, to be sure it contains the posted data.
	$option_data = OptionIndexes::get_option_data( WpSolrExtensions::OPTION_INDEXES );

	$subtabs = array();

	// Create the tabs from the Solr indexes already configured
	foreach ( $option_object->get_indexes() as $index_indice => $index ) {
		$subtabs[ $index_indice ] = isset( $index['index_name'] ) ? $index['index_name'] : 'Index with no name';
	}

	$subtabs['new_index'] = count( $option_object->get_indexes() ) > 0 ? $license_manager->show_premium_link( OptionLicenses::LICENSE_PACKAGE_CORE, 'Configure another index', false ) : 'Configure your first index';

	// Create subtabs on the left side
	$subtab = wpsolr_admin_sub_tabs( $subtabs );

	?>

	<div id="solr-results-options" class="wdm-vertical-tabs-content">

		<?php
		$is_new_index = false;
		if ( 'new_index' === $subtab ) {
			$is_new_index                           = true;
			$subtab                                 = $option_object->generate_uuid();
			$option_data['solr_indexes'][ $subtab ] = array();

			if ( ! $option_object->has_index_type_temporary() ) {
				// No temporary index yet: display the form to create one.
				WpSolrExtensions::require_with( WpSolrExtensions::get_option_template_file( WpSolrExtensions::OPTION_MANAGED_SOLR_SERVERS, 'template-temporary-account-form.php' ),
					array(
						'managed_solr_service_id'   => $form_data['managed_solr_service_id']['value'],
						'response_error'            => ( isset( $response_object ) && ! OptionManagedSolrServer::is_response_ok( $response_object ) ) ? OptionManagedSolrServer::get_response_error_message( $response_object ) : '',
						'google_recaptcha_site_key' => isset( $google_recaptcha_site_key ) ? $google_recaptcha_site_key : '',
						'google_recaptcha_token'    => isset( $google_recaptcha_token ) ? $google_recaptcha_token : '',
						'total_nb_indexes'          => $option_object->get_nb_indexes()
					) );
			}

		} else {
			// Verify that current subtab is a Solr index indice.
			if ( ! $option_object->has_index( $subtab ) ) {
				// Use the first subtab element
				$subtab = key( $subtabs );
			}

		}

		?>


		<form action="options.php" method="POST" id='settings_conf_form'>

			<?php
			settings_fields( $option_name );
			?>

			<input type='hidden' id='adm_path' value='<?php echo admin_url(); ?>'>

			<?php
			foreach ( ( isset( $option_data['solr_indexes'] ) ? $option_data['solr_indexes'] : array() ) as $index_indice => $index ) {

				$is_index_type_temporary = false;
				$is_index_type_managed   = false;
				$is_index_readonly       = false;

				if ( $subtab === $index_indice ) {
					$is_index_type_temporary = $option_object->is_index_type_temporary( $option_data['solr_indexes'][ $index_indice ] );
					$is_index_type_managed   = $option_object->is_index_type_managed( $option_data['solr_indexes'][ $index_indice ] );
					$is_index_readonly       = $is_index_type_temporary;

					if ( $is_index_type_temporary ) {
						// Check that the temporary index is still temporary on the server.
						$managed_solr_server = new OptionManagedSolrServer( $option_object->get_index_managed_solr_service_id( $index ) );
						$response_object     = $managed_solr_server->call_rest_get_temporary_solr_index_status( $index_indice );

						if ( OptionManagedSolrServer::is_response_ok( $response_object ) ) {

							$is_index_unknown_on_server = OptionManagedSolrServer::get_response_result( $response_object, 'isUnknown' );

							if ( $is_index_unknown_on_server ) {

								// Change the solr index type to managed
								$option_object->update_index_property( $index_indice, OptionIndexes::INDEX_TYPE, OptionIndexes::STORED_INDEX_TYPE_UNMANAGED );

								// Display message
								$response_error = 'This temporary solr core has expired and was therefore deleted. You can remove it from your configuration';

								// No more readonly therefore
								$is_index_type_temporary = false;
								$is_index_readonly       = false;

							} else {

								$is_index_type_temporary_on_server = OptionManagedSolrServer::get_response_result( $response_object, 'isTemporary' );
								if ( ! $is_index_type_temporary_on_server ) {

									// Change the solr index type to managed
									$option_object->update_index_property( $index_indice, OptionIndexes::INDEX_TYPE, OptionIndexes::STORED_INDEX_TYPE_MANAGED );

									// No more readonly therefore
									$is_index_type_temporary = false;
									$is_index_readonly       = false;
								}
							}

						} else {

							$response_error = ( isset( $response_object ) && ! OptionManagedSolrServer::is_response_ok( $response_object ) ) ? OptionManagedSolrServer::get_response_error_message( $response_object ) : '';
						}
					}
				}

				?>

				<div
					id="<?php echo $subtab != $index_indice ? $index_indice : "current_index_configuration_edited_id" ?>"
					class="wrapper" <?php echo ( $subtab != $index_indice ) ? "style='display:none'" : "" ?> >

					<input type='hidden'
					       name="<?php echo $option_name ?>[solr_indexes][<?php echo $index_indice ?>][managed_solr_service_id]"
						<?php echo $subtab === $index_indice ? "id='managed_solr_service_id'" : "" ?>
						   value="<?php echo empty( $option_data['solr_indexes'][ $index_indice ]['managed_solr_service_id'] ) ? '' : $option_data['solr_indexes'][ $index_indice ]['managed_solr_service_id']; ?>">
					<input type='hidden'
					       name="<?php echo $option_name ?>[solr_indexes][<?php echo $index_indice ?>][index_type]"
						<?php echo $subtab === $index_indice ? "id='index_type'" : "" ?>
						   value="<?php echo empty( $option_data['solr_indexes'][ $index_indice ]['index_type'] ) ? '' : $option_data['solr_indexes'][ $index_indice ]['index_type']; ?>">

					<h4 class='head_div'>
						<?php echo $is_index_type_temporary
							? 'This is your temporary (2 hours) Solr Index configuration for testing'
							: ( $is_index_type_managed
								? sprintf( 'This is your Index configuration managed by %s', $option_object->get_index_managed_solr_service_id( $option_data['solr_indexes'][ $index_indice ] ) )
								: sprintf( 'Manually configure your existing Solr index. %s', '<a href="http://www.gotosolr.com/en" target="_wpsolr">Sorry, no free support by chat to setup your own local index</a>' ) );
						?>
					</h4>

					<?php
					if ( $is_new_index ) {
						?>
						<div class="wdm_note">

							WPSOLR is compatible with the Solr versions listed at the following page: <a
								href="http://www.wpsolr.com/releases#1.0" target="__wpsolr">Compatible Solr versions</a>.

							Your first action must be to download the two configuration files (schema.xml,
							solrconfig.xml) listed in the online release section, and upload them to your Solr instance.
							Everything is described online.

						</div>
						<?php
					}
					?>

					<div class="wdm_row">
						<h4 class="solr_error" <?php echo $subtab != $index_indice ? "style='display:none'" : "" ?> >
							<?php
							if ( ! empty( $response_error ) ) {
								echo $response_error;
							}
							?>
						</h4>
					</div>

					<div class="wdm_row">
						<div class='col_left'>Index name</div>

						<div class='col_right'><input type='text' <?php echo $is_index_readonly ? 'readonly' : ''; ?>
						                              placeholder="Give a name to your index"
						                              name="<?php echo $option_name ?>[solr_indexes][<?php echo $index_indice ?>][index_name]"
								<?php echo $subtab === $index_indice ? "id='index_name'" : "" ?>
								                      value="<?php echo empty( $option_data['solr_indexes'][ $index_indice ]['index_name'] ) ? '' : $option_data['solr_indexes'][ $index_indice ]['index_name']; ?>">

							<div class="clear"></div>
							<span class='name_err'></span>
						</div>
						<div class="clear"></div>
					</div>

					<div class="wdm_row">
						<div class='col_left'>Solr Protocol</div>

						<div class='col_right'>
							<?php if ( ! $is_index_readonly ) { ?>
								<select
									name="<?php echo $option_name ?>[solr_indexes][<?php echo $index_indice ?>][index_protocol]"
									<?php echo $subtab === $index_indice ? "id='index_protocol'" : "" ?>
								>
									<option
										value='http' <?php selected( 'http', empty( $option_data['solr_indexes'][ $index_indice ]['index_protocol'] ) ? 'http' : $option_data['solr_indexes'][ $index_indice ]['index_protocol'] ) ?>>
										http
									</option>
									<option
										value='https' <?php selected( 'https', empty( $option_data['solr_indexes'][ $index_indice ]['index_protocol'] ) ? 'http' : $option_data['solr_indexes'][ $index_indice ]['index_protocol'] ) ?>>
										https
									</option>
								</select>
							<?php } else { ?>
								<input type='text' type='text' readonly
								       name="<?php echo $option_name ?>[solr_indexes][<?php echo $index_indice ?>][index_protocol]"
									<?php echo $subtab === $index_indice ? "id='index_protocol'" : "" ?>
									   value="<?php echo empty( $option_data['solr_indexes'][ $index_indice ]['index_protocol'] ) ? '' : $option_data['solr_indexes'][ $index_indice ]['index_protocol']; ?>">
							<?php } ?>

							<div class="clear"></div>
							<span class='protocol_err'></span>
						</div>
						<div class="clear"></div>
					</div>
					<div class="wdm_row">
						<div class='col_left'>Solr Host</div>

						<div class='col_right'>
							<input type='text' type='text' <?php echo $is_index_readonly ? 'readonly' : ''; ?>
							       placeholder="localhost or ip adress or hostname. No 'http', no '/', no ':'"
							       name="<?php echo $option_name ?>[solr_indexes][<?php echo $index_indice ?>][index_host]"
								<?php echo $subtab === $index_indice ? "id='index_host'" : "" ?>
								   value="<?php echo empty( $option_data['solr_indexes'][ $index_indice ]['index_host'] ) ? '' : $option_data['solr_indexes'][ $index_indice ]['index_host']; ?>">

							<div class="clear"></div>
							<span class='host_err'></span>
						</div>
						<div class="clear"></div>
					</div>
					<div class="wdm_row">
						<div class='col_left'>Solr Port</div>
						<div class='col_right'>
							<input type="text" type='text'
							       placeholder="8983 or 443 or any other port"
							       name="<?php echo $option_name ?>[solr_indexes][<?php echo $index_indice ?>][index_port]"
								<?php echo $subtab === $index_indice ? "id='index_port'" : "" ?>
								   value="<?php echo empty( $option_data['solr_indexes'][ $index_indice ]['index_port'] ) ? '' : $option_data['solr_indexes'][ $index_indice ]['index_port']; ?>">

							<div class="clear"></div>
							<span class='port_err'></span>
						</div>
						<div class="clear"></div>
					</div>
					<div class="wdm_row">
						<div class='col_left'>Solr Path</div>
						<div class='col_right'>
							<input type='text' type='text' <?php echo $is_index_readonly ? 'readonly' : ''; ?>
							       placeholder="For instance /solr/index_name. Begins with '/', no '/' at the end"
							       name="<?php echo $option_name ?>[solr_indexes][<?php echo $index_indice ?>][index_path]"
								<?php echo $subtab === $index_indice ? "id='index_path'" : "" ?>
								   value="<?php echo empty( $option_data['solr_indexes'][ $index_indice ]['index_path'] ) ? '' : $option_data['solr_indexes'][ $index_indice ]['index_path']; ?>">

							<div class="clear"></div>
							<span class='path_err'></span>
						</div>
						<div class="clear"></div>
					</div>
					<div class="wdm_row">
						<div class='col_left'>Key</div>
						<div class='col_right'>
							<input type='text' type='text' <?php echo $is_index_readonly ? 'readonly' : ''; ?>
							       placeholder="Optional security user if the index is protected with Http Basic Authentication"
							       name="<?php echo $option_name ?>[solr_indexes][<?php echo $index_indice ?>][index_key]"
								<?php echo $subtab === $index_indice ? "id='index_key'" : "" ?>
								   value="<?php echo empty( $option_data['solr_indexes'][ $index_indice ]['index_key'] ) ? '' : $option_data['solr_indexes'][ $index_indice ]['index_key']; ?>">

							<div class="clear"></div>
							<span class='key_err'></span>
						</div>
						<div class="clear"></div>
					</div>
					<div class="wdm_row">
						<div class='col_left'>Secret</div>
						<div class='col_right'>
							<input type='text' type='text' <?php echo $is_index_readonly ? 'readonly' : ''; ?>
							       placeholder="Optional security password if the index is protected with Http Basic Authentication"
							       name="<?php echo $option_name ?>[solr_indexes][<?php echo $index_indice ?>][index_secret]"
								<?php echo $subtab === $index_indice ? "id='index_secret'" : "" ?>
								   value="<?php echo empty( $option_data['solr_indexes'][ $index_indice ]['index_secret'] ) ? '' : $option_data['solr_indexes'][ $index_indice ]['index_secret']; ?>">

							<div class="clear"></div>
							<span class='sec_err'></span>
						</div>
						<div class="clear"></div>
					</div>

					<?php
					// Display managed offers links
					if ( $is_index_type_temporary ) {
						?>

						<div class='col_right' style='width:90%'>

							<?php
							$managed_solr_service_id = $option_object->get_index_managed_solr_service_id( $option_data['solr_indexes'][ $index_indice ] );

							$OptionManagedSolrServer = new OptionManagedSolrServer( $managed_solr_service_id );
							foreach ( $OptionManagedSolrServer->generate_convert_orders_urls( $index_indice ) as $managed_solr_service_orders_url ) {
								?>

								<input name="gotosolr_plan_yearly_trial"
								       type="button" class="button-primary"
								       value="<?php echo $managed_solr_service_orders_url[ OptionManagedSolrServer::MANAGED_SOLR_SERVICE_ORDER_URL_BUTTON_LABEL ]; ?>"
								       onclick="window.open('<?php echo $managed_solr_service_orders_url[ OptionManagedSolrServer::MANAGED_SOLR_SERVICE_ORDER_URL_LINK ]; ?>', '__blank');"
								/>

								<?php


								//echo $managed_solr_service_orders_url[ OptionManagedSolrServer::MANAGED_SOLR_SERVICE_ORDER_URL_TEXT ];

							}
							?>
							<a href="http://www.gotosolr.com/en" target="_wpsolr">Free support by chat to setup your
								Gotosolr index</a>
						</div>
						<div class="clear"></div>

						<?php
					}
					?>


				</div>
			<?php } // end foreach ?>

			<div class="wdm_row">
				<div class="submit">
					<input name="check_solr_status" id='check_index_status' type="button"
					       class="button-primary wdm-save"
					       value="Check Solr Status, then Save this configuration"/> <span><img
							src='<?php echo plugins_url( '../../../images/gif-load_cir.gif', __FILE__ ) ?>'
							style='height:18px;width:18px;margin-top: 10px;display: none'
							class='img-load'>

                                             <img
	                                             src='<?php echo plugins_url( '../../../images/success.png', __FILE__ ) ?>'
	                                             style='height:18px;width:18px;margin-top: 10px;display: none'
	                                             class='img-succ'/>
                                                <img
	                                                src='<?php echo plugins_url( '../../../images/warning.png', __FILE__ ) ?>'
	                                                style='height:18px;width:18px;margin-top: 10px;display: none'
	                                                class='img-err'/></span>
				</div>

				<?php if ( ! $is_new_index ) { ?>
					<input name="delete_index_configuration" id='delete_index_configuration' type="button"
					       class="button-secondary wdm-delete"
					       value="Delete this configuration"/>
				<?php } // end if ?>

			</div>
			<div class="clear"></div>

		</form>
	</div>

</div>
