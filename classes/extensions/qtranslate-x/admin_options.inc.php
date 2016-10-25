<?php

/**
 * Included file to display admin options
 */

WpSolrExtensions::require_once_wpsolr_extension( WpSolrExtensions::EXTENSION_QTRANSLATEX, true );
WpSolrExtensions::require_once_wpsolr_extension( WpSolrExtensions::OPTION_INDEXES, true );

$extension_options_name = 'wdm_solr_extension_qtranslatex_data';
$settings_fields_name   = 'solr_extension_qtranslatex_options';

$options          = get_option( $extension_options_name, array(
	'is_extension_active' => '0',
) );
$is_plugin_active = WpSolrExtensions::is_plugin_active( WpSolrExtensions::EXTENSION_QTRANSLATEX );

$plugin_name    = "qTranslate X";
$plugin_link    = "https://wordpress.org/plugins/qtranslate-x/";
$plugin_version = "";

if ( $is_plugin_active ) {
	$ml_plugin = PluginQTranslateX::create();
}
?>

<?php
include_once( WpSolrExtensions::get_option_file( WpSolrExtensions::EXTENSION_WPML, 'template.inc.php' ) );
