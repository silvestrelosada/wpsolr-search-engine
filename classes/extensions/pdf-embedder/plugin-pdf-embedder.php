<?php

// Load class for inheritance
WpSolrExtensions::require_once_wpsolr_extension( WpSolrExtensions::EXTENSION_EMBED_ANY_DOCUMENT, true );

/**
 * Class PluginPdfEmbedder
 *
 * Manage Pdf Embedder plugin
 * @link https://wordpress.org/plugins/pdf-embedder/
 */
class PluginPdfEmbedder extends PluginEmbedAnyDocument {


	const EMBEDDOC_SHORTCODE = 'pdf-embedder';

	protected function set_is_do_embed_documents() {
		$this->is_do_embed_documents = WPSOLR_Global::getOption()->get_pdf_embedder_is_do_embed_documents();
	}


}