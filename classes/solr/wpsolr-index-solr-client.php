<?php

require_once plugin_dir_path( __FILE__ ) . 'wpsolr-abstract-solr-client.php';

class WPSolrIndexSolrClient extends WPSolrAbstractSolrClient {


	// Posts table name
	const TABLE_POSTS = 'posts';

	protected $solr_indexing_options;

	static function create( $solr_index_indice = null ) {

		return new self( $solr_index_indice );
	}

	public function __construct( $solr_index_indice = null ) {

		// Load active extensions
		$this->wpsolr_extensions = new WpSolrExtensions();


		$path = plugin_dir_path( __FILE__ ) . '../../vendor/autoload.php';
		require_once $path;

		// Load options
		$this->solr_indexing_options = get_option( 'wdm_solr_form_data' );

		// Build Solarium config from the default indexing Solr index
		WpSolrExtensions::require_once_wpsolr_extension( WpSolrExtensions::OPTION_INDEXES, true );
		$options_indexes = new OptionIndexes();
		$config          = $options_indexes->build_solarium_config( $solr_index_indice, self::DEFAULT_SOLR_TIMEOUT_IN_SECOND );


		$this->index_indice = $solr_index_indice;
		$this->client       = new Solarium\Client( $config );

	}

	public function delete_documents() {

		// Reset docs first
		$this->reset_documents();

		// Execute delete query
		$client      = $this->client;
		$deleteQuery = $client->createUpdate();
		$deleteQuery->addDeleteQuery( 'id:*' );
		$deleteQuery->addCommit();
		$client->execute( $deleteQuery );


	}

	public function reset_documents() {

		// Store 0 in # of index documents
		self::set_index_indice_option_value( 'solr_docs', 0 );

		// Reset last indexed post date
		self::set_index_indice_option_value( 'solr_last_post_date_indexed', '1000-01-01 00:00:00' );

		// Update nb of documents updated/added
		self::set_index_indice_option_value( 'solr_docs_added_or_updated_last_operation', - 1 );

	}

	public function get_hosting_postfixed_option( $option ) {

		$result = $option;

		$solr_options = get_option( 'wdm_solr_conf_data' );

		switch ( $solr_options['host_type'] ) {
			case 'self_hosted':
				$postfix = '_in_self_index';
				break;

			default:
				$postfix = '_in_cloud_index';
				break;
		}

		return $result . $postfix;
	}

	/*
	 * How many documents were updated/added during last indexing operation
	 */

	public function get_count_documents() {
		$solr_options = get_option( 'wdm_solr_conf_data' );

		$client = $this->client;

		$query = $client->createSelect();
		$query->setQuery( '*:*' );
		$query->setRows( 0 );
		$resultset = $client->execute( $query );

		// Store 0 in # of index documents
		self::set_index_indice_option_value( 'solr_docs', $resultset->getNumFound() );

		return $resultset->getNumFound();

	}

	public function delete_document( $post ) {

		$client = $this->client;

		$deleteQuery = $client->createUpdate();
		$deleteQuery->addDeleteQuery( 'id:' . $post->ID );
		$deleteQuery->addCommit();

		$result = $client->execute( $deleteQuery );


		return $result->getStatus();

	}


	public function get_count_documents_indexed_last_operation( $default_value = - 1 ) {

		return $this->get_index_indice_option_value( 'solr_docs_added_or_updated_last_operation', $default_value );

	}

	public function get_last_post_date_indexed() {

		return $this->get_index_indice_option_value( 'solr_last_post_date_indexed', '1000-01-01 00:00:00' );

	}

	public function reset_last_post_date_indexed() {

		return $this->set_index_indice_option_value( 'solr_last_post_date_indexed', '1000-01-01 00:00:00' );

	}

	public function set_last_post_date_indexed( $option_value ) {

		return $this->set_index_indice_option_value( 'solr_last_post_date_indexed', $option_value );

	}

	public function get_index_indice_option_value( $option_name, $option_value ) {

		// Get option value. Replace by default value if undefined.
		$option = get_option( $option_name, null );

		$result = ( isset( $option ) && isset( $option[ $this->index_indice ] ) )
			? $option[ $this->index_indice ]
			: $option_value;

		return $result;
	}

	public function set_index_indice_option_value( $option_name, $option_value ) {

		$option = get_option( $option_name, null );

		if ( ! isset( $option ) ) {
			$option = array();
		}

		$option[ $this->index_indice ] = $option_value;

		update_option( $option_name, $option );

		return $option_value;
	}

	/**
	 * Count nb documents remaining to index for a solr index
	 *
	 * @return integer Nb documents remaining to index
	 */
	public function count_nb_documents_to_be_indexed() {

		return $this->index_data( 0, null );

	}

	/**
	 * @param int $batch_size
	 * @param null $post
	 *
	 * @return array
	 * @throws Exception
	 */
	public function index_data( $batch_size = 100, $post = null, $is_debug_indexing = false ) {

		global $wpdb;

		// Debug variable containing debug text
		$debug_text = '';

		// Last post date set in previous call. We begin with posts published after.
		// Reset the last post date is reindexing is required.
		$lastPostDate = $this->get_last_post_date_indexed();

		$query_from       = $wpdb->prefix . self::TABLE_POSTS . ' AS ' . self::TABLE_POSTS;
		$query_join_stmt  = '';
		$query_where_stmt = '';

		$client      = $this->client;
		$updateQuery = $client->createUpdate();
		// Get body of attachment
		$solarium_extract_query = $client->createExtract();

		$post_types = str_replace( ",", "','", $this->solr_indexing_options['p_types'] );
		$exclude_id = $this->solr_indexing_options['exclude_ids'];
		$ex_ids     = array();
		$ex_ids     = explode( ',', $exclude_id );

		// Build the WHERE clause

		// Where clause for post types
		$where_p = " post_type in ('$post_types') ";

		// Build the attachment types clause
		$attachment_types = str_replace( ",", "','", $this->solr_indexing_options['attachment_types'] );
		if ( isset( $attachment_types ) && ( $attachment_types != '' ) ) {
			$where_a = " ( post_status='publish' OR post_status='inherit' ) AND post_type='attachment' AND post_mime_type in ('$attachment_types') ";
		}


		if ( isset( $where_p ) ) {
			$query_where_stmt = "post_status='publish' AND ( $where_p )";
			if ( isset( $where_a ) ) {
				$query_where_stmt = "( $query_where_stmt ) OR ( $where_a )";
			}
		} elseif ( isset( $where_a ) ) {
			$query_where_stmt = $where_a;
		}

		if ( $batch_size == 0 ) {
			// count only
			$query_select_stmt = "count(ID) as TOTAL";
		} else {
			$query_select_stmt = "ID, post_modified, post_parent, post_type";
		}

		if ( isset( $post ) ) {
			// Add condition on the $post
			$query_where_stmt = " ID = %d " . " AND ( $query_where_stmt ) ";
		} else {
			// Condition on the date only for the batch, not for individual posts
			$query_where_stmt = " post_modified > %s " . " AND ( $query_where_stmt ) ";
		}

		$query_order_by_stmt = "post_modified ASC";

		// Filter the query
		$query_statements = apply_filters( WpSolrFilters::WPSOLR_FILTER_SQL_QUERY_STATEMENT,
			array(
				'SELECT' => $query_select_stmt,
				'FROM'   => $query_from,
				'JOIN'   => $query_join_stmt,
				'WHERE'  => $query_where_stmt,
				'ORDER'  => $query_order_by_stmt,
				'LIMIT'  => $batch_size,
			),
			array(
				'index_indice' => $this->index_indice,
			)
		);


		// Generate query string from the query statements
		$query = sprintf( 'SELECT %s FROM %s %s WHERE %s ORDER BY %s LIMIT %s',
			$query_statements['SELECT'], $query_statements['FROM'], $query_statements['JOIN'], $query_statements['WHERE'], $query_statements['ORDER'], $query_statements['LIMIT'] === 0 ? 1 : $query_statements['LIMIT'] );


		$documents     = array();
		$doc_count     = 0;
		$no_more_posts = false;
		while ( true ) {

			if ( $is_debug_indexing ) {
				$this->add_debug_line( $debug_text, 'Beginning of new loop (batch size)' );
			}

			// Execute query (retrieve posts IDs, parents and types)
			if ( isset( $post ) ) {

				if ( $is_debug_indexing ) {
					$this->add_debug_line( $debug_text, 'Query document with post->ID', Array(
						'Query'   => $query,
						'Post ID' => $post->ID
					) );
				}

				$ids_array = $wpdb->get_results( $wpdb->prepare( $query, $post->ID ), ARRAY_A );

			} else {

				if ( $is_debug_indexing ) {
					$this->add_debug_line( $debug_text, 'Query documents from last post date', Array(
						'Query'          => $query,
						'Last post date' => $lastPostDate
					) );
				}

				$ids_array = $wpdb->get_results( $wpdb->prepare( $query, $lastPostDate ), ARRAY_A );
			}

			if ( $batch_size == 0 ) {

				$nb_docs = $ids_array[0]['TOTAL'];

				if ( $is_debug_indexing ) {
					$this->add_debug_line( $debug_text, 'End of loop', Array(
						'Number of documents in database to be indexed' => $nb_docs
					) );
				}

				// Just return the count
				return $nb_docs;
			}


			// Aggregate current batch IDs in one Solr update statement
			$postcount = count( $ids_array );

			if ( $postcount == 0 ) {
				// No more documents to index, stop now by exiting the loop

				if ( $is_debug_indexing ) {
					$this->add_debug_line( $debug_text, 'No more documents, end of document loop' );
				}

				$no_more_posts = true;
				break;
			}

			// For the batch, update the last post date with current post's date
			if ( ! isset( $post ) ) {
				// In 2 steps to be valid in PHP 5.3
				$lastPost     = end( $ids_array );
				$lastPostDate = $lastPost['post_modified'];
			}

			for ( $idx = 0; $idx < $postcount; $idx ++ ) {
				$postid = $ids_array[ $idx ]['ID'];

				// If post is not on blacklist
				if ( ! in_array( $postid, $ex_ids ) ) {
					// If post is not an attachment
					if ( $ids_array[ $idx ]['post_type'] != 'attachment' ) {

						// Count this post
						$doc_count ++;

						// Get the posts data
						$document = self::create_solr_document_from_post_or_attachment( $updateQuery, get_post( $postid ) );

						if ( $is_debug_indexing ) {
							$this->add_debug_line( $debug_text, null, Array(
								'Post to be sent' => json_encode( $document->getFields(), JSON_PRETTY_PRINT )
							) );
						}

						$documents[] = $document;

					} else {
						// Post is of type "attachment"

						if ( $is_debug_indexing ) {
							$this->add_debug_line( $debug_text, null, Array(
								'Post ID to be indexed (attachment)' => $postid
							) );
						}

						// Count this post
						$doc_count ++;

						// Get the attachments body with a Solr Tika extract query
						$attachment_body = self::extract_attachment_text_by_calling_solr_tika( $solarium_extract_query, get_post( $postid ) );

						// Get the posts data
						$document = self::create_solr_document_from_post_or_attachment( $updateQuery, get_post( $postid ), $attachment_body );

						if ( $is_debug_indexing ) {
							$this->add_debug_line( $debug_text, null, Array(
								'Attachment to be sent' => json_encode( $document->getFields(), JSON_PRETTY_PRINT )
							) );
						}

						$documents[] = $document;

					}
				}
			}

			if ( empty( $documents ) || ! isset( $documents ) ) {
				// No more documents to index, stop now by exiting the loop

				if ( $is_debug_indexing ) {
					$this->add_debug_line( $debug_text, 'End of loop, no more documents' );
				}

				break;
			}

			// Send batch documents to Solr
			$res_final = self::send_posts_or_attachments_to_solr_index( $updateQuery, $documents );

			// Solr error, or only $post to index: exit loop
			if ( ( ! $res_final ) OR isset( $post ) ) {
				break;
			}

			if ( ! isset( $post ) ) {
				// Store last post date sent to Solr (for batch only)
				$this->set_last_post_date_indexed( $lastPostDate );
			}

			// AJAX: one loop by ajax call
			break;
		}

		$status = ! isset( $res_final ) ? 0 : $res_final->getStatus();

		return $res_final = array(
			'nb_results'        => $doc_count,
			'status'            => $status,
			'indexing_complete' => $no_more_posts,
			'debug_text'        => $is_debug_indexing ? $debug_text : null
		);

	}

	/*
	 * Fetch posts and attachments,
	 * Transform them in Solr documents,
	 * Send them in packs to Solr
	 */

	/**
	 * Add a debug line to the current debug text
	 *
	 * @param $is_debug_indexing
	 * @param $debug_text
	 * @param $debug_text_header
	 * @param $debug_text_content
	 */
	public function add_debug_line( &$debug_text, $debug_line_header, $debug_text_header_content = null ) {

		if ( isset( $debug_line_header ) ) {
			$debug_text .= '******** DEBUG ACTIVATED - ' . $debug_line_header . ' *******' . '<br><br>';
		}

		if ( isset( $debug_text_header_content ) ) {

			foreach ( $debug_text_header_content as $key => $value ) {
				$debug_text .= $key . ':' . '<br>' . '<b>' . $value . '</b>' . '<br><br>';
			}
		}
	}

	/**
	 * @param $solarium_update_query
	 * @param $post_to_index
	 * @param null $attachment_body
	 *
	 * @return mixed
	 * @internal param $solr_indexing_options
	 */
	public
	function create_solr_document_from_post_or_attachment(
		$solarium_update_query, $post_to_index, $attachment_body = null
	) {

		$pid    = $post_to_index->ID;
		$ptitle = $post_to_index->post_title;
		if ( isset( $attachment_body ) ) {
			// Post is an attachment: we get the document body from the function call
			$pcontent = $attachment_body;
		} else {
			// Post is NOT an attachment: we get the document body from the post object
			$pcontent = $post_to_index->post_content;
		}

		$pexcerpt         = $post_to_index->post_excerpt;
		$pauth_info       = get_userdata( $post_to_index->post_author );
		$pauthor          = isset( $pauth_info ) ? $pauth_info->display_name : '';
		$pauthor_s        = isset( $pauth_info ) ? get_author_posts_url( $pauth_info->ID, $pauth_info->user_nicename ) : '';
		$ptype            = $post_to_index->post_type;
		$pdate            = solr_format_date( $post_to_index->post_date_gmt );
		$pmodified        = solr_format_date( $post_to_index->post_modified_gmt );
		$pdisplaydate     = $post_to_index->post_date;
		$pdisplaymodified = $post_to_index->post_modified;
		$purl             = get_permalink( $pid );
		$comments_con     = array();
		$comm             = isset( $this->solr_indexing_options[ WpSolrSchema::_FIELD_NAME_COMMENTS ] ) ? $this->solr_indexing_options[ WpSolrSchema::_FIELD_NAME_COMMENTS ] : '';

		$numcomments = 0;
		if ( $comm ) {
			$comments_con = array();

			$comments = get_comments( "status=approve&post_id={$post_to_index->ID}" );
			foreach ( $comments as $comment ) {
				array_push( $comments_con, $comment->comment_content );
				$numcomments += 1;
			}

		}
		$pcomments    = $comments_con;
		$pnumcomments = $numcomments;


		/*
			Get all custom categories selected for indexing, including 'category'
		*/
		$cats   = array();
		$taxo   = $this->solr_indexing_options['taxonomies'];
		$aTaxo  = explode( ',', $taxo );
		$newTax = array( 'category' ); // Add categories by default
		if ( is_array( $aTaxo ) && count( $aTaxo ) ) {
		}
		foreach ( $aTaxo as $a ) {

			if ( substr( $a, ( strlen( $a ) - 4 ), strlen( $a ) ) == "_str" ) {
				$a = substr( $a, 0, ( strlen( $a ) - 4 ) );
			}

			// Add only non empty categories
			if ( strlen( trim( $a ) ) > 0 ) {
				array_push( $newTax, $a );
			}
		}


		// Get all taxonomy terms ot this post
		$term_names = wp_get_post_terms( $post_to_index->ID, $newTax, array( "fields" => "names" ) );
		if ( $term_names && ! is_wp_error( $term_names ) ) {
			foreach ( $term_names as $term_name ) {
				array_push( $cats, $term_name );
			}
		}

		// Get all tags of this port
		$tag_array = array();
		$tags      = get_the_tags( $post_to_index->ID );
		if ( ! $tags == null ) {
			foreach ( $tags as $tag ) {
				array_push( $tag_array, $tag->name );

			}
		}


		$solarium_document_for_update = $solarium_update_query->createDocument();

		$solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_ID ]    = $pid;
		$solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_PID ]   = $pid;
		$solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_TITLE ] = $ptitle;

		if ( isset( $this->solr_indexing_options['p_excerpt'] ) && ( ! empty( $pexcerpt ) ) ) {

			// Index post excerpt, by adding it to the post content.
			// Excerpt can therefore be: searched, autocompleted, highlighted.
			$pcontent .= ' ' . $pexcerpt;
		}

		$content_with_shortcodes_expanded_or_stripped = $pcontent;
		if ( isset( $this->solr_indexing_options['is_shortcode_expanded'] ) && ( strpos( $pcontent, '[solr_search_shortcode]' ) === false ) ) {

			// Expand shortcodes which have a plugin active, and are not the search form shortcode (else pb).
			global $post;
			$post                                         = $post_to_index;
			$content_with_shortcodes_expanded_or_stripped = do_shortcode( $pcontent );
		}

		// Remove shortcodes tags remaining, but not their content.
		// strip_shortcodes() does nothing, probably because shortcodes from themes are not loaded in admin.
		// Credit: https://wordpress.org/support/topic/stripping-shortcodes-keeping-the-content.
		// Modified to enable "/" in attributes
		$content_with_shortcodes_expanded_or_stripped = preg_replace( "~(?:\[/?)[^\]]+/?\]~s", '', $content_with_shortcodes_expanded_or_stripped );  # strip shortcodes, keep shortcode content;

		// Remove HTML tags
		$solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_CONTENT ] = strip_tags( $content_with_shortcodes_expanded_or_stripped );

		$solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_AUTHOR ]             = $pauthor;
		$solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_AUTHOR_S ]           = $pauthor_s;
		$solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_TYPE ]               = $ptype;
		$solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_DATE ]               = $pdate;
		$solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_MODIFIED ]           = $pmodified;
		$solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_DISPLAY_DATE ]       = $pdisplaydate;
		$solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_DISPLAY_MODIFIED ]   = $pdisplaymodified;
		$solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_PERMALINK ]          = $purl;
		$solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_COMMENTS ]           = $pcomments;
		$solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_NUMBER_OF_COMMENTS ] = $pnumcomments;
		$solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_CATEGORIES ]         = $cats;
		$solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_CATEGORIES_STR ]     = $cats;
		$solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_TAGS ]               = $tag_array;

		$taxonomies = (array) get_taxonomies( array( '_builtin' => false ), 'names' );
		foreach ( $taxonomies as $parent ) {
			if ( in_array( $parent, $newTax ) ) {
				$terms = get_the_terms( $post_to_index->ID, $parent );
				if ( (array) $terms === $terms ) {
					$parent = strtolower( str_replace( ' ', '_', $parent ) );
					foreach ( $terms as $term ) {
						$nm1                                = $parent . '_str';
						$nm2                                = $parent . '_srch';
						$solarium_document_for_update->$nm1 = $term->name;
						$solarium_document_for_update->$nm2 = $term->name;
					}
				}
			}
		}

		// Add custom fields to the document
		$this->set_custom_fields( $solarium_document_for_update, $post_to_index );

		// Last chance to customize the solarium update document
		$solarium_document_for_update = apply_filters( WpSolrFilters::WPSOLR_FILTER_SOLARIUM_DOCUMENT_FOR_UPDATE, $solarium_document_for_update, $this->solr_indexing_options, $post_to_index, $attachment_body );

		return $solarium_document_for_update;

	}


	/**
	 * Set custom fields to the update document.
	 * HTML and php tags are removed.
	 *
	 * @param $solarium_document_for_update
	 * @param $post
	 */
	function set_custom_fields( $solarium_document_for_update, $post ) {

		$custom                    = $this->solr_indexing_options['cust_fields'];
		$custom_fields_values_list = array();
		$aCustom                   = explode( ',', $custom );
		if ( count( $aCustom ) > 0 ) {
			if ( count( $custom_fields = get_post_custom( $post->ID ) ) ) {

				// Apply filters on custom fields
				$custom_fields = apply_filters( WpSolrFilters::WPSOLR_FILTER_POST_CUSTOM_FIELDS, $custom_fields, $post->ID );

				$existing_custom_fields = isset( $solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_CUSTOM_FIELDS ] )
					? $solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_CUSTOM_FIELDS ]
					: array();
				foreach ( (array) $aCustom as $field_name ) {
					if ( substr( $field_name, ( strlen( $field_name ) - 4 ), strlen( $field_name ) ) == "_str" ) {
						$field_name = substr( $field_name, 0, ( strlen( $field_name ) - 4 ) );
					}
					if ( isset( $custom_fields[ $field_name ] ) ) {
						$field = (array) $custom_fields[ $field_name ];

						$field_name = strtolower( str_replace( ' ', '_', $field_name ) );

						// Add custom field array of values
						$nm1       = $field_name . '_str';
						$nm2       = $field_name . '_srch';
						$array_nm1 = array();
						$array_nm2 = array();
						foreach ( $field as $field_value ) {
							$field_value_stripped = strip_tags( $field_value );

							array_push( $array_nm1, $field_value_stripped );
							array_push( $array_nm2, $field_value_stripped );

							// Add current custom field values to custom fields search field
							// $field being an array, we add each of it's element
							array_push( $existing_custom_fields, $field_value_stripped );
						}

						$solarium_document_for_update->$nm1 = $array_nm1;
						$solarium_document_for_update->$nm2 = $array_nm2;

					}
				}

				if ( count( $existing_custom_fields ) > 0 ) {
					$solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_CUSTOM_FIELDS ] = $existing_custom_fields;
				}

			}

		}

	}

	/**
	 * @param $solarium_extract_query
	 * @param $post
	 *
	 * @return string
	 * @throws Exception
	 */
	public
	function extract_attachment_text_by_calling_solr_tika(
		$solarium_extract_query, $post
	) {

		try {
			// Set URL to attachment
			$solarium_extract_query->setFile( get_attached_file( $post->ID ) );
			$doc1 = $solarium_extract_query->createDocument();
			$solarium_extract_query->setDocument( $doc1 );
			// We don't want to add the document to the solr index now
			$solarium_extract_query->addParam( 'extractOnly', 'true' );
			// Try to extract the document body
			$client                              = $this->client;
			$result                              = $client->execute( $solarium_extract_query );
			$response                            = $result->getResponse()->getBody();
			$attachment_text_extracted_from_tika = preg_replace( '/^.*?\<body\>(.*?)\<\/body\>.*$/i', '\1', $response );
			$attachment_text_extracted_from_tika = str_replace( '\n', ' ', $attachment_text_extracted_from_tika );
		} catch ( Exception $e ) {
			throw new Exception( 'Error on attached file ' . $post->post_title . ' (ID: ' . $post->ID . ')' . ': ' . $e->getMessage(), $e->getCode() );
		}

		// Last chance to customize the tika extracted attachment body
		$attachment_text_extracted_from_tika = apply_filters( WpSolrFilters::WPSOLR_FILTER_ATTACHMENT_TEXT_EXTRACTED_BY_APACHE_TIKA, $attachment_text_extracted_from_tika, $solarium_extract_query, $post );

		return $attachment_text_extracted_from_tika;
	}

	/**
	 * @param $solarium_update_query
	 * @param $documents
	 *
	 * @return mixed
	 */
	public
	function send_posts_or_attachments_to_solr_index(
		$solarium_update_query, $documents
	) {

		$client = $this->client;
		$solarium_update_query->addDocuments( $documents );
		$solarium_update_query->addCommit();
		$result = $client->execute( $solarium_update_query );

		return $result;

	}


}
