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