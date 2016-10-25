<?php

/**
 * Included file to display admin options
 */
global $license_manager;

WpSolrExtensions::require_once_wpsolr_extension( WpSolrExtensions::EXTENSION_WOOCOMMERCE, true );

$extension_options_name = 'wdm_solr_extension_woocommerce_data';
$settings_fields_name   = 'solr_extension_woocommerce_options';

$options          = get_option( $extension_options_name, array(
	'is_extension_active' => '0',
) );
$is_plugin_active = WpSolrExtensions::is_plugin_active( WpSolrExtensions::EXTENSION_WOOCOMMERCE );

$plugin_name    = "WooCommerce";
$plugin_link    = "https://wordpress.org/plugins/woocommerce/";
$plugin_version = "(>= 2.4.10)";

if ( $is_plugin_active ) {
	$ml_plugin = PluginWooCommerce::create();
}
?>

<div id="extension_groups-options" class="wdm-vertical-tabs-content">
	<form action="options.php" method="POST" id='extension_groups_settings_form'>
		<?php
		settings_fields( 'solr_extension_woocommerce_options' );
		$extension_options = get_option( $extension_options_name, array(
				'is_extension_active' => '0'
			)
		);
		?>

		<div class='wrapper'>
			<h4 class='head_div'><?php echo $plugin_name; ?> plugin Options</h4>

			<div class="wdm_note">

				In this section, you will configure WPSOLR to work with <?php echo $plugin_name; ?>.<br/>

				<?php if ( ! $is_plugin_active ): ?>
					<p>
						Status: <a href="<?php echo $plugin_link; ?>"
						           target="_blank"><?php echo $plugin_name; ?>
							plugin</a> is not activated. First, you need to install and
						activate it to configure WPSOLR.
					</p>
					<p>
						You will also need to re-index all your data if you activated
						<a href="<?php echo $plugin_link; ?>" target="_blank"><?php echo $plugin_name; ?>
							plugin</a>
						after you activated WPSOLR.
					</p>
				<?php else : ?>
					<p>
						Status: <a href="<?php echo $plugin_link; ?>"
						           target="_blank"><?php echo $plugin_name; ?>
							plugin</a>
						is activated. You can now configure WPSOLR to use it.
					</p>
				<?php endif; ?>
			</div>
			<div class="wdm_row">
				<div class='col_left'>Use the <a
						href="<?php echo $plugin_link; ?>"
						target="_blank"><?php echo $plugin_name; ?> <?php echo $plugin_version; ?>
						plugin</a>
					to filter search results.
					<br/>Think of re-indexing all your data if <a
						href="<?php echo $plugin_link; ?>" target="_blank"><?php echo $plugin_name; ?>
						plugin</a> was installed after WPSOLR.
				</div>
				<div class='col_right'>
					<input type='checkbox' <?php echo $is_plugin_active ? '' : 'readonly' ?>
					       name='<?php echo $extension_options_name; ?>[is_extension_active]'
					       value='is_extension_active'
						<?php checked( 'is_extension_active', isset( $extension_options['is_extension_active'] ) ? $extension_options['is_extension_active'] : '' ); ?>>
				</div>
				<div class="clear"></div>
			</div>

			<div class='wdm_row'>
				<div class="submit">
					<?php if ( ! $license_manager->is_installed || $license_manager->get_license_is_activated( OptionLicenses::LICENSE_PACKAGE_WOOCOMMERCE ) ) { ?>
						<div class="wpsolr_premium_block_class"><?php echo $license_manager->show_premium_link( OptionLicenses::LICENSE_PACKAGE_WOOCOMMERCE, OptionLicenses::TEXT_LICENSE_ACTIVATED, true ); ?></div>
						<input <?php echo $is_plugin_active ? '' : 'disabled' ?>
							name="save_selected_options_res_form"
							id="save_selected_extension_groups_form" type="submit"
							class="button-primary wdm-save"
							value="<?php echo $is_plugin_active ? 'Save Options' : sprintf( 'Install and activate the plugin %s first.', $plugin_name ); ?>"/>
					<?php } else { ?>
						<?php echo $license_manager->show_premium_link( OptionLicenses::LICENSE_PACKAGE_WOOCOMMERCE, 'Save Options', true ); ?>
						<br/>
					<?php } ?>
				</div>
			</div>

		</div>

	</form>
</div>