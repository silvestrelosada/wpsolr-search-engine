<?php

/**
 * Included file to display admin options
 */


WpSolrExtensions::require_once_wpsolr_extension( WpSolrExtensions::OPTION_LICENSES, true );

// Options name
$option_name = OptionLicenses::get_option_name( WpSolrExtensions::OPTION_LICENSES );

// Options object
$license_manager = new OptionLicenses();

?>

	<script>
		jQuery(document).on("click", "#<?php echo OptionLicenses::AJAX_VERIFY_LICENCE; ?>, #<?php echo OptionLicenses::AJAX_ACTIVATE_LICENCE; ?>, #<?php echo OptionLicenses::AJAX_DEACTIVATE_LICENCE; ?>", function (e) {

			// Remember this for ajax
			var current = this;

			// Show progress
			var button_clicked = jQuery(this);
			var button_form = button_clicked.parents('.wpsolr_form_license');
			var buttonText = button_clicked.val(); // Remmember button text
			button_clicked.val('Operation in progress ... Please wait.');
			button_clicked.prop('disabled', true);
			var error_message_element = jQuery('.wpsolr_form_license').find(".error-message");
			error_message_element.css("display", "none");
			error_message_element.html("");


			// Extract form data
			var subscription_number = button_form.find("input[name=<?php echo OptionLicenses::FIELD_LICENSE_SUBSCRIPTION_NUMBER; ?>]").val()

			if (subscription_number.length == 0) {
				// End progress
				button_clicked.val(buttonText);
				button_clicked.prop('disabled', false);

				error_message_element.css("display", "inline-block");
				error_message_element.html('Please enter a license#.');
				return;
			}

			var license_package = button_form.find("input[name=<?php echo OptionLicenses::FIELD_LICENSE_PACKAGE; ?>]").val()
			var license_matching_reference = button_form.find("input[name=<?php echo OptionLicenses::FIELD_LICENSE_MATCHING_REFERENCE; ?>]").val()
			var data = {
				action: button_clicked.attr('id'),
				data: {
			<?php echo OptionLicenses::FIELD_LICENSE_PACKAGE; ?>:
			license_package,
			<?php echo OptionLicenses::FIELD_LICENSE_MATCHING_REFERENCE; ?>:
			license_matching_reference,
			<?php echo OptionLicenses::FIELD_LICENSE_SUBSCRIPTION_NUMBER; ?>:
			subscription_number
		}
		}
			;

			//alert(button_clicked.attr('id'));

			// Pass parameters to Ajax
			jQuery.ajax({
				url: "<?php echo admin_url( 'admin-ajax.php' ); ?>",
				type: "post",
				data: data,
				success: function (data1) {

					data1 = JSON.parse(data1);

					// Error message
					if ("OK" != data1.status.state) {

						// End progress
						button_clicked.val(buttonText);
						button_clicked.prop('disabled', false);

						error_message_element.css("display", "inline-block");
						error_message_element.html(data1.status.message);

					} else {

						// Continue the submit
						button_form.submit();
					}

				},
				error: function () {

					// End progress
					jQuery(current).val(buttonText);
					jQuery(current).prop('disabled', false);

					/*
					 // Post Ajax UI display
					 jQuery('.loading_res').css('display', 'none');
					 jQuery('.results-by-facets').css('display', 'block');
					 */

				},
				always: function () {
					// Not called.
				}
			});


			return false;
		})
		;
	</script>

<?php foreach ( $license_manager->get_license_types() as $license_type => $license ) { ?>

	<div id="<?php echo $license_type; ?>" style="display:none;" class="wdm-vertical-tabs-content">

		<form method="POST" id="form_<?php echo $license_type; ?>" class="wpsolr_form_license">

			<input type="hidden" name="<?php echo OptionLicenses::FIELD_LICENSE_PACKAGE; ?>"
			       value="<?php echo $license_type; ?>"/>

			<input type="hidden" name="<?php echo OptionLicenses::FIELD_LICENSE_MATCHING_REFERENCE; ?>"
			       value="<?php echo $license[ OptionLicenses::FIELD_LICENSE_MATCHING_REFERENCE ]; ?>"/>

			<div class='wrapper wpsolr_license_popup'><h4
					class='head_div'><?php echo $license[ OptionLicenses::FIELD_LICENSE_TITLE ]; ?></h4>
				<div class="wdm_note">
					<?php echo $license_manager->get_license_is_activated( $license_type ) ?
						sprintf( 'This feature is already activated with the %s Extension', $license[ OptionLicenses::FIELD_LICENSE_TITLE ] )
						: sprintf( 'This feature requires the %s Extension', $license[ OptionLicenses::FIELD_LICENSE_TITLE ] );
					?>
					<br/>
				</div>

				<hr/>
				<div class="wdm_row">
					<div class='col_left'>
						Your
						license# <?php echo $license_manager->get_license_is_activated( $license_type ) ? 'is already activated' : 'is not yet activated.'; ?>
						<p>
							<a href="http://www.gotosolr.com/en/solr-documentation/license-activations" target="__new1">
								Where is my license# ?
							</a>
						</p>
					</div>
					<div class='col_right'>

						<?php
						$subscription_number = $license_manager->get_license_subscription_number( $license_type );
						?>
						<input type="text" placeholder="Your license #"
						       name="<?php echo OptionLicenses::FIELD_LICENSE_SUBSCRIPTION_NUMBER; ?>"
						       value="<?php echo $subscription_number; ?>"
							<?php disabled( $license_manager->get_license_is_need_verification( $license_type ) || $license_manager->get_license_is_can_be_deactivated( $license_type ) ); ?>

						>

						<p>
							<?php if ( $license_manager->get_license_is_need_verification( $license_type ) ) { ?>

								<input id="<?php echo OptionLicenses::AJAX_VERIFY_LICENCE; ?>" type="button"
								       class="button-primary wdm-save wpsolr_license_submit"
								       value="Verify"/>

							<?php } ?>

							<?php if ( $license_manager->get_license_is_can_be_deactivated( $license_type ) ) { ?>

								<input id="<?php echo OptionLicenses::AJAX_DEACTIVATE_LICENCE; ?>" type="button"
								       class="button-primary wdm-save wpsolr_license_submit"
								       value="Deactivate"/>

							<?php } ?>

							<?php if ( ! $license_manager->get_license_is_can_be_deactivated( $license_type ) ) { ?>

								<input id="<?php echo OptionLicenses::AJAX_ACTIVATE_LICENCE; ?>" type="button"
								       class="button-primary wdm-save wpsolr_license_submit"
								       value="Activate"/>

							<?php } ?>
						</p>

						<span class="error-message"></span>


					</div>
					<div class="clear"></div>
				</div>

				<?php if ( !$license_manager->get_license_is_activated( $license_type ) ) { ?>
					<hr/>
					<div class="wdm_row">
						<div class='col_left'>
							No pack yet ?
						</div>
						<div class='col_right'>

							<?php foreach ( $license_manager->get_license_orders_urls( $license_type ) as $license_orders_url ) { ?>

								<input name="gotosolr_plan_yearly_trial"
								       type="button" class="button-primary"
								       value="<?php echo sprintf( $license_orders_url[ OptionLicenses::FIELD_ORDER_URL_BUTTON_LABEL ], $license[ OptionLicenses::FIELD_LICENSE_TITLE ] ); ?>"
								       onclick="window.open('<?php echo $license_orders_url[ OptionLicenses::FIELD_ORDER_URL_LINK ]; ?>', '__blank');"
								/>

								<h4 class="solr_error" style="font-size: 12px">
									See our packs <a
										href="http://www.wpsolr.com/pricing"
										target="__new1">pricing and features</a>.<br/>
									This will give you 7 days to test the 'Premium Pack'.
									<br/>
									Contact us if you need other packs to test with your trial. We will add them to your
									trial subscription.
								</h4>

								<h3>With your pack, you will be able to:</h3>
								<ol>
									<?php foreach ( $license_manager->get_license_features( $license_type ) as $feature ) { ?>
										<li>
											<?php echo $feature; ?>
										</li>
									<?php } ?>
								</ol>

								<h3>Instructions:</h3>
								Click on the button to be redirected to your order page.
								After completion of your order, you will receive an email with a link to your account.
								Signin, and copy the license activation code (Licence # column of the subscription) above to activate your pack.
								See documentation here: <a
									href="http://www.gotosolr.com/en/solr-documentation/license-activations"
									target="__new1">http://www.gotosolr.com/en/solr-documentation/license-activations</a>

								<h3>Chat</h3>
								If you are quite, but not completely, convinced, let's have a chat at <a
									href="http://www.wpsolr.com"
									target="__new1">wpsolr.com chat box</a>.
								<br/> We also deliver custom developments, if your project needs extra care.

							<?php } ?>

						</div>
						<div class="clear"></div>
					</div>
				<?php } ?>

			</div>

		</form>

	</div>

<?php } ?>