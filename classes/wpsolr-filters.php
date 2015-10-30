<?php

/**
 * Interface for filters definitions.
 *
 * Developers: try to use these constants in your filters.
 */
class WpSolrFilters {

	// Add 'groups' plugin infos to a Solr results document
	const WPSOLR_FILTER_SOLR_RESULTS_DOCUMENT_GROUPS_INFOS = 'wpsolr_filter_solr_results_document_groups_infos';

	// Customize a post custom fields before they are processed in a Solarium update document
	const WPSOLR_FILTER_POST_CUSTOM_FIELDS = 'wpsolr_filter_post_custom_fields';

	// Customize a fully processed Solarium update document before sending to Solr for indexing
	const WPSOLR_FILTER_SOLARIUM_DOCUMENT_FOR_UPDATE = 'wpsolr_filter_solarium_document_for_update';

	// Customize a fully processed attachment content before sending to Solr for indexing
	const WPSOLR_FILTER_ATTACHMENT_TEXT_EXTRACTED_BY_APACHE_TIKA = 'wpsolr_filter_attachment_text_extracted_by_apache_tika';

	// Customize the Solarium query before a search is performed
	const WPSOLR_ACTION_SOLARIUM_QUERY = 'wpsolr_action_solarium_query';
	const WPSOLR_ACTION_SOLARIUM_QUERY__PARAM_SOLARIUM_QUERY = 'solarium_query_object';
	const WPSOLR_ACTION_SOLARIUM_QUERY__PARAM_SEARCH_TERMS = 'keywords';
	const WPSOLR_ACTION_SOLARIUM_QUERY__PARAM_SEARCH_USER = 'user';

	// Action to add custom query fields to a Solr select query
	const WPSOLR_ACTION_SOLARIUM_ADD_QUERY_FIELDS = 'wpsolr_action_solr_add_query_fields';

	// Customize the search page url
	const WPSOLR_FILTER_SEARCH_PAGE_URL = 'wpsolr_filter_search_page_url';

	// Action before a solr index configuration is deleted
	const WPSOLR_ACTION_BEFORE_A_SOLR_INDEX_CONFIGURATION_DELETION = 'wpsolr_action_before_a_solr_index_configuration_deletion';

	// Filter the sql query statement used to retrieve the posts not yet indexed
	const WPSOLR_FILTER_SQL_QUERY_STATEMENT = 'wpsolr_filter_sql_query_statement';

	// Filter to get the default search index indice
	const WPSOLR_FILTER_SEARCH_GET_DEFAULT_SOLR_INDEX_INDICE = 'wpsolr_filter_get_default_search_solr_index_indice';

	// Filter to get the indexing index indice for a post
	const WPSOLR_FILTER_INDEXING_GET_SOLR_INDEX_INDICE_FOR_A_POST = 'wpsolr_filter_get_default_indexing_solr_index_indice_for_a_post';
}