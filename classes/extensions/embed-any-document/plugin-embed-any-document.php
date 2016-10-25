<?php

/**
 * Class PluginEmbedAnyDocument
 *
 * Manage Embed Any Document plugin
 * @link https://wordpress.org/plugins/embed-any-document/
 */
class PluginEmbedAnyDocument extends WpSolrExtensions {

	// Options
	const _OPTIONS_NAME = 'wdm_solr_extension_embed_any_document_data';

	protected $is_do_embed_documents;
	protected $pattern;

	// Options
	private $_options;

	// Overide in child classes
	const EMBEDDOC_SHORTCODE = 'embeddoc';
	const EMBEDDOC_SHORTCODE_ATTRIBUTE_URL = 'url';


	/**
	 * Factory
	 *
	 * @return PluginEmbedAnyDocument
	 */
	static function create() {

		return new self();
	}

	/*
	 * Constructor
	 * Subscribe to actions
	 */

	function __construct() {

		$this->set_is_do_embed_documents();
		$this->pattern = get_shortcode_regex( array( static::EMBEDDOC_SHORTCODE ) );

		add_filter( WpSolrFilters::WPSOLR_FILTER_GET_POST_ATTACHMENTS, array(
			$this,
			'filter_get_post_attachments'
		), 10, 2 );

	}

	protected function set_is_do_embed_documents() {

		$this->is_do_embed_documents = WPSOLR_Global::getOption()->get_embed_any_document_is_do_embed_documents();
	}

	/**
	 * Retrieve embedded urls in the post shortcodes
	 *
	 * @param array $attachments
	 * @param string $post
	 *
	 * @return array
	 */
	public function filter_get_post_attachments( $attachments, $post_id ) {

		if ( ! $this->is_do_embed_documents ) {
			// Do nothing
			return $attachments;
		}

		$post = get_post( $post_id );

		// Extract shortcodes
		$pattern = $this->pattern;
		preg_match_all( "/$pattern/", $post->post_content, $matches );

		if ( ! empty( $matches ) && ! empty( $matches[3] ) ) {

			foreach ( $matches[3] as $match ) {

				// Extract shortcode attributes
				$attributes = shortcode_parse_atts( $match );

				if ( ! empty( $attributes ) && ! empty( $attributes[ static::EMBEDDOC_SHORTCODE_ATTRIBUTE_URL ] ) ) {

					array_push( $attachments, array( 'url' => $attributes[ static::EMBEDDOC_SHORTCODE_ATTRIBUTE_URL ] ) );
				}
			}
		}

		return $attachments;
	}
}