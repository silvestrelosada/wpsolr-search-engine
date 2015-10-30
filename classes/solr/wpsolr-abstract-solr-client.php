<?php

require_once plugin_dir_path( __FILE__ ) . '../extensions/wpsolr-extensions.php';
require_once plugin_dir_path( __FILE__ ) . '../wpsolr-filters.php';
require_once plugin_dir_path( __FILE__ ) . '../wpsolr-schema.php';

class WPSolrAbstractSolrClient {

	// Timeout in seconds when calling Solr
	const DEFAULT_SOLR_TIMEOUT_IN_SECOND = 30;

	public $client;
	protected $config;

	// Indice of the Solr index configuration in admin options
	protected $index_indice;


	// Array of active extension objects
	protected $wpsolr_extensions;

}
