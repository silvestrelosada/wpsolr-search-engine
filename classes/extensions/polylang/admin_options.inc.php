<?php

/**
 * Included file to display admin options
 */

WpSolrExtensions::require_once_wpsolr_extension( WpSolrExtensions::EXTENSION_POLYLANG, true );
WpSolrExtensions::require_once_wpsolr_extension( WpSolrExtensions::OPTION_INDEXES, true );

$extension_options_name = 'wdm_solr_extension_polylang_data';
$settings_fields_name   = 'solr_extension_polylang_options';

$options          = get_option( $extension_options_name, array(
	'is_extension_active' => '0',
) );
$is_plugin_active = WpSolrExtensions::is_plugin_active( WpSolrExtensions::EXTENSION_POLYLANG );

$plugin_name    = "Polylang";
$plugin_link    = "https://polylang.wordpress.com/documentation/";
$plugin_version = "(>= 1.7.11)";

if ( $is_plugin_active ) {
	$ml_plugin = PluginPolylang::create();
}

$package_name = OptionLicenses::LICENSE_PACKAGE_POLYLANG;
?>

<?php
include_once( WpSolrExtensions::get_option_file( WpSolrExtensions::EXTENSION_WPML, 'template.inc.php' ) );
