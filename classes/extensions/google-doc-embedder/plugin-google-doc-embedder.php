<?php

// Load class for inheritance
WpSolrExtensions::require_once_wpsolr_extension( WpSolrExtensions::EXTENSION_EMBED_ANY_DOCUMENT, true );

/**
 * Class PluginGoogleDocEmbedder
 *
 * Manage Google Doc Embedder plugin
 * @link https://wordpress.org/plugins/google-document-embedder/
 */
class PluginGoogleDocEmbedder extends PluginEmbedAnyDocument {

	const EMBEDDOC_SHORTCODE = 'gview';
	const EMBEDDOC_SHORTCODE_ATTRIBUTE_URL = 'file';

	protected function set_is_do_embed_documents() {
		$this->is_do_embed_documents = WPSOLR_Global::getOption()->get_google_doc_embedder_is_do_embed_documents();
	}


}