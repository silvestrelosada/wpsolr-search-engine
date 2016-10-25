<?php

/**
 * Class PluginBbPress
 *
 * Manage PluginBbPress plugin
 */
class PluginBbPress extends WpSolrExtensions {

	/*
	 * Constructor
	 * Subscribe to actions
	 */

	function __construct() {

		add_filter( 'bbp_after_has_search_results_parse_args', array(
			$this,
			'bbp_after_has_search_results_parse_args',
		), 10, 1 );

	}

	/**
	 * Filter bbPress arguments
	 *
	 * @param $bbp_args
	 *
	 * @return mixed
	 */
	public function bbp_after_has_search_results_parse_args( $bbp_args ) {

		$bbp = bbpress();

		$bbp->search_query = new WPSOLR_Query( $bbp_args );

		// Remove the 's' parameter, to prevent bbPress executing it's own wp_query
		if ( ! empty( $bbp_args['s'] ) ) {
			unset( $bbp_args['s'] );
		}

		return $bbp_args;
	}

}