<?php

require_once 'WPSOLR_Option.php';
require_once __DIR__ . '/../ui/WPSOLR_Query_Parameters.php';
require_once __DIR__ . '/../ui/WPSOLR_Query.php';

/**
 * Replace class WP_Query by the child class WPSOLR_query
 * Action called at the end of wp-settings.php, before $wp_query is processed
 */
add_action( 'wp_loaded', array( 'WPSOLR_Global', 'action_wp_loaded' ) );


/**
 * Manage a list of singleton objects (global objects).
 */
class WPSOLR_Global {

	private static $objects = array();

	public static function action_wp_loaded() {

		if ( WPSOLR_Query_Parameters::is_wp_search() && ! is_admin() && is_main_query() && WPSOLR_Global::getOption()->get_search_is_replace_default_wp_search() && WPSOLR_Global::getOption()->get_search_is_use_current_theme_search_template() ) {

			// Override global $wp_query with wpsolr_query
			$GLOBALS['wp_the_query'] = WPSOLR_Global::getQuery();
			$GLOBALS['wp_query']     = $GLOBALS['wp_the_query'];
		}
	}

	/**
	 * Get/create a singleton object from it's class.
	 */
	public static function getObject( $class_name, $parameter = null ) {

		if ( ! isset( self::$objects[ $class_name ] ) ) {

			self::$objects[ $class_name ] = method_exists( $class_name, "global_object" )
				? isset( $parameter ) ? $class_name::global_object( $parameter ) : $class_name::global_object()
				: new $class_name();
		}

		return self::$objects[ $class_name ];
	}

	/**
	 * @return WPSOLR_Option
	 */
	public static function getOption() {

		return self::getObject( 'WPSOLR_Option' );
	}

	/**
	 * @return WPSOLR_Query
	 */
	public static function getQuery( WPSOLR_Query $wpsolr_query = null ) {

		return self::getObject( 'WPSOLR_Query', $wpsolr_query );
	}

	/**
	 * @return WPSolrSearchSolrClient
	 */
	public static function getSolrClient() {

		return self::getObject( 'WPSolrSearchSolrClient' );
	}

}

