<?php

/**
 * Manage schema.xml definitions
 */
class WpSolrSchema {

	// Field queried by default. Necessary to get highlighting right.
	const _FIELD_NAME_DEFAULT_QUERY = 'text';

	/*
	 * Solr document field names
	 */
	const _FIELD_NAME_ID = 'id';
	const _FIELD_NAME_PID = 'PID';
	const _FIELD_NAME_TITLE = 'title';
	const _FIELD_NAME_CONTENT = 'content';
	const _FIELD_NAME_AUTHOR = 'author';
	const _FIELD_NAME_AUTHOR_S = 'author_s';
	const _FIELD_NAME_TYPE = 'type';
	const _FIELD_NAME_DATE = 'date';
	const _FIELD_NAME_MODIFIED = 'modified';
	const _FIELD_NAME_DISPLAY_DATE = 'displaydate';
	const _FIELD_NAME_DISPLAY_MODIFIED = 'displaymodified';
	const _FIELD_NAME_PERMALINK = 'permalink';
	const _FIELD_NAME_COMMENTS = 'comments';
	const _FIELD_NAME_NUMBER_OF_COMMENTS = 'numcomments';
	const _FIELD_NAME_CATEGORIES = 'categories';
	const _FIELD_NAME_CATEGORIES_STR = 'categories_str';
	const _FIELD_NAME_TAGS = 'tags';
	const _FIELD_NAME_CUSTOM_FIELDS = 'categories';
	const _FIELD_NAME_FLAT_HIERARCHY = 'flat_hierarchy_%s'; // field contains hierarchy as a string with separator
	const _FIELD_NAME_NON_FLAT_HIERARCHY = 'non_flat_hierarchy_%s'; // filed contains hierarchy as an array
	const _FIELD_NAME_BLOG_NAME_STR = 'blog_name_str';
	const _FIELD_NAME_POST_THUMBNAIL_HREF_STR = 'post_thumbnail_href_str';
	const _FIELD_NAME_POST_HREF_STR = 'post_href_str';

	// Separator of a flatten hierarchy
	const FACET_HIERARCHY_SEPARATOR = '->';

	/*
		 * Dynamic types
		 */
	// Solr dynamic type postfix for text
	const _DYNAMIC_TYPE_POSTFIX_TEXT = '_t';


	// Definition translated fields when multi-languages plugins are activated
	public static $multi_language_fields = array(
		array(
			'field_name'      => WpSolrSchema::_FIELD_NAME_TITLE,
			'field_extension' => WpSolrSchema::_DYNAMIC_TYPE_POSTFIX_TEXT,
		),
		array(
			'field_name'      => WpSolrSchema::_FIELD_NAME_CONTENT,
			'field_extension' => WpSolrSchema::_DYNAMIC_TYPE_POSTFIX_TEXT,
		),
	);

}