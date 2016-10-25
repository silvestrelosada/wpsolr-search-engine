<?php

/**
 * Included file to display admin options
 */


WpSolrExtensions::require_once_wpsolr_extension( WpSolrExtensions::EXTENSION_WPML, true );
WpSolrExtensions::require_once_wpsolr_extension( WpSolrExtensions::OPTION_INDEXES, true );

$extension_options_name = 'wdm_solr_extension_wpml_data';
$settings_fields_name   = 'solr_extension_wpml_options';

$options          = get_option( $extension_options_name, array(
	'is_extension_active' => '0',
) );
$is_plugin_active = WpSolrExtensions::is_plugin_active( WpSolrExtensions::EXTENSION_WPML );

$plugin_name    = "WPML";
$plugin_link    = "https://wpml.org/";
$plugin_version = "(WPML Multilingual CMS > 3.1.6)";

$ml_plugin = PluginWpml::create();

$package_name = OptionLicenses::LICENSE_PACKAGE_WPML;
?>

<?php
include_once( 'template.inc.php' );