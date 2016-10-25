=== Search for WordPress, WooCommerce, bbPress, that never gets stuck - WPSOLR ===

Contributors: wpsolr

Current Version: 13.3

Author: wpsolr

Author URI: http://www.wpsolr.com/

Tags: search, Solr in WordPress, wordpress search, bbPress search, WooCommerce search, ACF search, coupon search, affiliate feed search, relevance, Solr search, fast search, wpsolr, apache solr, better search, site search, category search, search bar, comment search, filtering, relevant search, custom search, filters, page search, autocomplete, post search, online search, search, spell checking, search integration, did you mean, typeahead, search replacement, suggestions, search results, search by category, multi language, seo, lucene, solr, suggest, apache lucene

Requires at least: 3.7.1

Tested up to: 4.6

Stable tag: 13.3

Search faster. When your Wordpress search fails, when your WooCommerce search or bbPress search gets stuck, you need a change of search technology.

== Description ==

You definitely need wpsolr search if you agree with one of:

- My current search page, my instant (live) product suggestions, are so slow that my visitors are leaving without buying anything, without subscribing to anything

- I have too many posts, products, visitors, comments, and I cannot afford hundred of dollars on external search hosted services

- Most of my data is stored in pdf files, word files, excel files. I need to search these formats too.

- My customers are international, they speak different languages. My search should be multilingual also.

- I want a modern search with tons of features. Ajax, facets, partial match search, fuzzy match search.

- I want to filter my woocommerce search results with any taxonomies, custom fields, attributes, or variations.

- I have several sites, unrelated, but I want to give my visitors a single search page combining all their content

- My bbPress search cannot handle thousands, hundreds of thousands of topics and replies.


If not, there are plenty of great search plugins out there to help you.

But, if you're really ready to unleash the beast, visit <a href='http://www.wpsolr.com?camp=2'>wpsolr.com</a>, ask us any question, or just download wpsolr search to give it a try.


== Installation ==

1. Upload the WPSOLR-Search-Engine folder to the /wp-content/plugins/ directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to the 'WPSOLR' settings page and configure the plugin.
4. Please refer the Installation and User Guide for further reference.

Installation procedure for Apache Solr: see FAQ section.

== Screenshots ==

1. 1) Admin: Download the Solr files solrconfig.xml and schema.xml
2. 2) Admin: Configure your local Solr instance
3. 3) Admin: Configure your cloud Solr instance
4. 4) Admin: Indexing option (part 1)
5. 5) Admin: Indexing option (part 2)
6. 6) Admin: Options to display results
7. 7) Admin: Add filters and control their order
8. 8) Admin: Integration with the plugin 'groups'
9. 9) Admin: Integration with the plugin 's2member'
10. 10) Admin: Solr indexation
11. 11) Front end: Auto suggestions while typing in search bar
12. 12) Front end: filters are displayed
13. 13) Front end: Did you mean ?
14. 14) Admin: Select attachment types to index
15. 15) Admin: The "Sort by" items list is configurable
16. 16) Admin: Change all front-end texts in admin
17. 17) Admin: WPML setup
18. 18) Admin: POLYLANG setup
19. 19) Admin: WooCommerce integration activation.
20. 20) Admin: WooCommerce Product attribute selected for indexing
21. 21) Admin: WooCommerce Product attribute selected for filtering
22. 22) Admin: Advanced Custom Fields (ACF) integration activation.
23. 23) Admin: Types plugin integration activation.
24. 24) Front end: multi-selection on filters.
25. 25) Admin: get a free instant cloud Solr index ready for testing.
26. 26) Admin: create one or several Solr indexes.
27. 27) Admin: create local or cloud Solr indexes.
28. 28) Admin: show categories and custom taxonomies hierarchy.
29. 29) Admin: stop real-time indexing.
30. 30) Admin: Add boosts to any searched field.
31. 31) Admin: Change facets labels.
32. 32) Admin: Translate facets labels with WPML or POLYLANG string modules.
33. 33) Admin: Metabox.
34. 34) Admin: Metabox selection to index and search embedded files defined with an ACF field of type file ID or file object.
35. 35) Admin: Search in Embed Any Document post content.
36. 36) Admin: Search in Pdf Embedder post content.
37. 37) Admin: Search in Google Doc Embedder post content.
38. 38) Admin: Ajax product suggestions.
39. 39) ACF: Create ACF repeater fields.
40. 40) Admin: Index ACF repeater fields.
41. 41) Admin: Select ACF repeater fields as facets.
42. 42) Admin: Create a post with ACF repeater fields.
43. 43) Front: Search in ACF repeater fields, and filter ACF repeater fields with facets.

== Changelog ==

= 13.3 =
* Removed wpml-config.xml from plugin directory. It provoked an error with the strict XML parser introduced by Polylang 2 versions.

= 13.2 =
* Fix empty results when filtering with a facet containing a double quote
* Fix empty results when searching with a keyword containing a double quote
* Fix ajax search box not showing double quotes

= 13.1 =
* Deliver new schema.xml files to fix comments/replies not indexed/searchable. Download from http://wpsolr.com/releases/#1.0, install on your Solr index, and reload the Solr index (or restart Solr).
* Comments/Replies are indexed in real-time.
* Comments/Replies are searchable, autocompleted, and spellchecked.
* Comments/Replies fields and terms can be boosted.
* Reorder selected/Unselected options in screen "indexed data".
* WARNING: if you want to activate comments/replies indexing/searching, this will require you to re-index all your documents containing comments/replies. It can take a while if you have a large amount of documents in your WP database.

= 13.0 =
* (ACF Pack) Index and search, with facets, ACF repeater fields. Do not index empty ACF fields.
* [Screenshot: ACF: Create ACF repeater fields](https://s.w.org/plugins/wpsolr-search-engine/screenshot-39.png "ACF: Create ACF repeater fields")
* [Screenshot: Admin: Index ACF repeater fields](https://s.w.org/plugins/wpsolr-search-engine/screenshot-40.png "Admin: Index ACF repeater fields")
* [Screenshot: Admin: Select ACF repeater fields as facets](https://s.w.org/plugins/wpsolr-search-engine/screenshot-41.png "Admin: Select ACF repeater fields as facets")
* [Screenshot: Admin: Create a post with ACF repeater fields](https://s.w.org/plugins/wpsolr-search-engine/screenshot-42.png "Admin: Create a post with ACF repeater fields")
* [Screenshot: Front: Search in ACF repeater fields, and filter ACF repeater fields with facets](https://s.w.org/plugins/wpsolr-search-engine/screenshot-43.png "Front: Search in ACF repeater fields, and filter ACF repeater fields with facets")
* Do not show keywords highlighting with empty keywords.
* Extensions now detect plugins loaded by the active theme (for instance, ACF can do that).

= 12.9 =
* Compatibility with Wordpress 4.6.
* You need to upgrade to this version before upgrading to Wordpress 4.6
* Remove dependency to http library http://requests.ryanmccue.info/ in conflict with the version newly delivered with Wordpress 4.6.

= 12.8 =
* Fix pages content not indexed
* Fix metabox warning when a page is saved and indexed in real-time

= 12.7 =
* You can now attach suggestions list to any search form in your own theme, by setting a jQuery selector.

= 12.6 =
* Add Ajax product suggestions to search form. You now have the choice between products or keywords suggestions.
* [Screenshot: Ajax product suggestions](https://s.w.org/plugins/wpsolr-search-engine/screenshot-38.png "Ajax product suggestions")

= 12.5 =
* Fix Ajax InfiniteScroll pagination javascript.

= 12.4 =
* Add a video explainer.

= 12.3 =
* (Google Doc Embedder Pack) New pack integrating with plugin Google Doc Embedder. Documents embedded with the plugin will be indexed and searched, within their post content (the post is returned by the search, not the embedded pdf).
* [Screenshot: Search in Google Doc Embedder post content](https://s.w.org/plugins/wpsolr-search-engine/screenshot-37.png "Search in Google Doc Embedder post content")

= 12.2 =
* (Pdf Embedder Pack) New pack integrating with plugin Pdf Embedder. Pdfs embedded with the plugin will be indexed and searched, within their post content (the post is returned by the search, not the embedded pdf).
* [Screenshot: Search in Pdf Embedder post content](https://s.w.org/plugins/wpsolr-search-engine/screenshot-36.png "Search in Pdf Embedder post content")

= 12.1 =
* (Embed Any Document Pack) New pack integrating with plugin Embed Any Document. Documents embedded with the plugin will be indexed and searched, within their post content (the post is returned by the search, not the embedded document).
* [Screenshot: Search in Embed Any Document post content](https://s.w.org/plugins/wpsolr-search-engine/screenshot-35.png "Search in Embed Any Document post content")

= 12.0 =
* (ACF Pack) Add checkbox to wpsolr metabox. When a post contains an ACF field of type "file" (File Object, File ID, File URL), the file content is added to the post body (indexed and searched).
* [Screenshot: Metabox selection to index and search embedded files defined with an ACF field of type file](https://s.w.org/plugins/wpsolr-search-engine/screenshot-34.png "Metabox selection to index and search embedded files defined with an ACF field of type file ")

= 11.9 =
* Add a metabox to all post types.
* [Screenshot: Add checkbox indexing/not indexing in the metabox](https://s.w.org/plugins/wpsolr-search-engine/screenshot-33.png "Enable/disable indexing in the metabox")

= 11.8 =
* Show index name in admin notice when a post is saved/deleted. Usefull to check that the current post is indexed in it's language related Solr index, with WPML or Polylang.

= 11.7 =
* (ACF Pack) Decode multi-valued ACF fields before sending to Solr index.
* WARNING: this will require you to re-index all your documents. It can take a while if you have a large amount of documents in your WP database.

= 11.6 =
* Add a feedback link in admin pages footer. Tell us what you do not like, what is missing, or why not what you love.

= 11.5 =
* (bbPress pack) The new bbPress integration replaces the bbPress search in forums / topics / replies, with the Solr search, while keeping your bbPress theme.
You can now easily and quickly search in millions of topics and replies.

= 11.4 =
* Index attachments when they are updated.

= 11.3 =
* Remove a php warning message on admin pages.

= 11.2 =
* (WooCommerce pack) Fix error when indexing also non-product types.

= 11.1 =
* (Premium pack) Manage post type facets labels ('post', 'page', 'product' ...), including their translations with WPML/POLYLANG string modules.

= 11.0 =
* Add a 7 days trial for all packs (Premium, Woocommerce, WPML, Polylang, S2member, Groups, Types, ACF).

= 10.9 =
* (Premium pack) Manage facets labels, including their translations with WPML/POLYLANG string modules.
[Screenshot: add facets labels](https://s.w.org/plugins/wpsolr-search-engine/screenshot-31.png "Add facets labels")
[Screenshot: translate facets labels with WPML/POLYLANG string modules](https://s.w.org/plugins/wpsolr-search-engine/screenshot-32.png "translate facets labels with WPML/POLYLANG string modules")

= 10.8 =
* (Premium pack) Add boost values to certain fields to favor results matching certain values. [Screenshot](https://s.w.org/plugins/wpsolr-search-engine/screenshot-30.png?r=1453376 "Add boost query to any searched field")

= 10.7 =
* (Premium pack) Add boost (weights) to any searched fields. You can now add more weight to titles in a search, or to contents, or to a custom field, or to prices. [Screenshot](https://s.w.org/plugins/wpsolr-search-engine/screenshot-30.png?r=1453376 "Add boosts to any searched field")

= 10.6 =
* Add fuzzy search option.

= 10.5 =
* Fix ajax page search form: 'undefined' was selected when a user pressed ENTER while the suggestion list was displayed, and the search widget was also displayed on the search page.

= 10.4 =
* Fix widget search form: 'undefined' was selected when a user pressed ENTER while the suggestion list was displayed.

= 10.3 =
* (Premium pack) Do not display facets count on top levels hierarchies anymore (too confusing).

= 10.2 =
* Fix custom fields not indexed immediately on a new post with plugin the Toolset plugin.
* Improve license UI.

= 10.1 =
* (Premium pack) Add a multi-site search: a site with wpsolr can search in (thousands) other sites with wpsolr

= 10.0 =
* Fix activation on Firefox.

= 9.9 =
* Fix warning: Illegal offset type in isset or empty in WPSOLR_Option.php on line 86

= 9.8 =
* Fix the 'Empty index' action, that was effectless on Windows7/Firefox.

= 9.7 =
* WPSOLR Groups plugin Pack: fix issue 'A filterquery must have a unique key value within a query'.

= 9.6 =
* Partial matching now returns also results with the exact keywords. For instance, SKUs can now be used with the partial matching option.

= 9.5 =
* Better Solr connection management: automatic retry (twice) before throwing errors. Prevent indexing/search errors due to minor network disconnections.

= 9.4 =
* Add an indexing option: custom fields and categories can be indexed with post content, and appear in autocomplete and highlighted results.
* WARNING: this will require you to re-index all your documents. It can take a while if you have a large amount of documents in your WP database.
* Fix some HTML syntax elements (remove hl, remove labels, fix ul inside ul) in the Ajax search page.
* WARNING: The HTML fix can require you to update your own CSS.

= 9.3 =
* WPSOLR Polylang Pack: Fix Polylang extension not activating.

= 9.2 =
* Fix a potential blank admin page.

= 9.1 =
* WPSOLR Premium Pack: speed up the load of huge external datafeeds (affiliate, coupons ...) by momentarily deactivating real-time indexing. [Screenshot](https://s.w.org/plugins/wpsolr-search-engine/screenshot-29.png "Stop real-time indexing")

= 9.0 =
* WooCommerce premium pack: add variations index/search/filters.

= 8.9 =
* Fix some results showing the full post contents rather than extracts.
* Add a filter on sort elements.

= 8.8 =
* Introduce Premium Packs activation in WPSOLR. More Packs will come very soon.

= 8.7 =
* Fix a potential security issue.

= 8.6 =
* Improve the sort on your current theme search template. Now, you can set your sort order by on your Solr search handler, it will be used on your search page.

= 8.5 =
* Fix custom taxonomies to be searchable (they used to be displayed in filters only).
* WARNING: this fix will require you to re-index all your documents. It can take a while if you have a large amount of documents in your WP database.

= 8.4 =
* Authorize unlimited number of filters items (by using 0)
* Add localized text for infinitescroll header

= 8.3 =
* Add an option to display partial keyword matches in results. For instance, 'search apache' will return results containing 'searching apachesolr'.
* Add css class to admin notice messages, so they can be hidden: 'wpsolr_admin_notice_error' and 'wpsolr_admin_notice_updated'.
* Prevent wpsolr admin css to interfere with other wordpress/plugins css.

= 8.2 =
* [Screenshot](https://s.w.org/plugins/wpsolr-search-engine/screenshot-28.png "Show categories and custom taxonomies filters hierarchy"): Show categories and custom taxonomies filters hierarchy, by selecting an option on filters.
* WARNING: this will require you to re-index all your documents. It can take a while if you have a large amount of documents in your WP database.

= 8.1 =
* Fix blanks in custom taxonomy field names

= 8.0 =
* Fix a redirect loop in safari

= 7.9 =
* Attachements are now showing in results

= 7.8 =
* Separate custom taxonomies filters content from categories filters content

= 7.7 =
* Fix bug with custom taxonomies filters
* Remove the '*' when search box is empty

= 7.6 =
* Extra option (default) to use your current theme search templates to display Solr results. Advantage: search results are fully controlled by your theme's standard loop. Drawback: advanced Solr features are not available: keyword autocompletion, did you mean, sort, filters.
* New Widget 'WPSOLR filters' to display filters wherever your theme can support it.

= 7.5 =
* Extra option to prevent WPSOLR loading it's own css files. It will then be easier to apply your own theme styles.

= 7.4 =
* Fix POLYLANG sql returning no documents to index.
* Update of nl_NL translation files.

= 7.3 =
* [Screenshot](https://s.w.org/plugins/wpsolr-search-engine/screenshot-24.png "filters multi-selection"): Add multi-selection to filters.

= 7.2 =
* Option to display Ajax search parameters in url. Back/Forward buttons is now compatible with Ajax search.

= 7.1 =
* When no highlighting is returned by Solr, display the excerpt or the content instead, with expanded shortcodes if required, and html/tags stripped.

= 7.0 =
* Fix a javascript error with infinite scroll.

= 6.9 =
* [Screenshot](https://s.w.org/plugins/wpsolr-search-engine/screenshot-23.png "Types plugin integration"): Types plugin integration: display custom fields label, rather than name, in filters.
Just activate the Types integration, and select your options.
* Plugins integrations minimum compatible version is indicated.

= 6.8 =
* Easier way to get a test Solr index.
* [Screenshot](https://s.w.org/plugins/wpsolr-search-engine/screenshot-22.png "Add Advanced Custom Fields (ACF) plugin integration"): Add Advanced Custom Fields (ACF) plugin integration: display custom fields label, rather than name, in filters.
Just activate the Advanced Custom Fields (ACF) integration, and select your options.
* Fix an error when WooCommerce plugin is activated but not configured in WPSOLR integration.

= 6.7 =
* [Screenshot](https://s.w.org/plugins/wpsolr-search-engine/screenshot-19.png "WooCommerce integration"), [Screenshot](https://s.w.org/plugins/wpsolr-search-engine/screenshot-20.png "WooCommerce integration"), [Screenshot](https://s.w.org/plugins/wpsolr-search-engine/screenshot-21.png "WooCommerce integration"): WooCommerce integration, product attributes are now in search, filters, autocomplete and suggestions (did you mean).
Just activate the WooCommerce integration, and select your products attributes in the indexed custom fields and filters options.
* WARNING: this will require you to re-index all your documents. It can take a while if you have a large amount of documents in your WP database.


= 6.6 =
* Fix a bug on filter categories containing a white space.

= 6.5 =
* Add Infinite Scroll pagination: this optional feature loads the next page of results automatically when visitors approach the bottom of search page.

= 6.4 =
* Add French and Deutch translations (check in /languages).

= 6.3 =
* Reduce network traffic With the Solr server by not retrieving the content. Can be dramatic with heavy attachment files.

= 6.2 =
* Let users change the temporary index port to 443, if a firewall blocks the default Solr port 8983.

= 6.1 =
* Custom fields beginning with "_" can be indexed/searched/autocompleted.

= 6.0 =
* [Screenshot](https://s.w.org/plugins/wpsolr-search-engine/screenshot-18.png "POLYLANG integration"): fully support multilingual search form and search results with the plugin POLYLANG, by mapping one Solr index by language.

= 5.9 =
* Fix a bug when configuring several Solr indexes.
* The temporary Solr index created for testing, can now be extended to a yearly or monthly paid plan.

= 5.8 =
* Front-end search page Ajax: replace deprecated JQuery .live() by .on()

= 5.7 =
* Add an indexing option: post excerpt can be indexed with post content, and appear in autocomplete and highlighted results.
* WARNING: this will require you to re-index all your documents. It can take a while if you have a large amount of documents in your WP database.

= 5.6 =
* Fix bug "Headers already sent" when activating the plugin.

= 5.5 =
* One-click generation and setup of a fully working online Solr index. More than enough to fully test WPSOLR in a few minutes.
* Fix bug in Solr when emptying Solr indexes.

= 5.4 =
* Improve search speed by 2-3 times.
* Fix bug in category filter.
* WARNING: this will require you to re-index all your documents. It can take a while if you have a large amount of documents in your WP database.

= 5.3 =
* Update documentation.

= 5.2 =
* New admin option to expand shortcodes found in posts content before indexing in Solr, rather than stripping them.
* WARNING: this will require you to re-index all your documents. It can take a while if you have a large amount of documents in your WP database.
* Remove HTML and php tags from custom fields before indexing in Solr.
* WARNING: this will require you to re-index all your documents. It can take a while if you have a large amount of documents in your WP database.
* New admin option to control the size of the results snippets (highlighting fragment size).
* New admin option to re-index all the posts, without deleting the index.

= 5.1 =
* Use custom fields also in search, autocomplete and suggestions (did you mean). Until now, custom fields where only displayed as filters.
* WARNING: this will require you to re-index all your documents. It can take a while if you have a large amount of documents in your WP database.

= 5.0 =
* Fix error while updating the Solr index when post/page are published or trashed.

= 4.9 =
* [Screenshot](https://s.w.org/plugins/wpsolr-search-engine/screenshot-17.png "WPML integration"): Fully support multilingual search form and search results with the plugin WPML (tested for WPML Multilingual CMS > 3.1.6).
* Use .mo files to translate the search form and search results front-end texts.
* Manage several Solr indexes.
* The search page is now /search-wpsolr (to be sure it does not exist yet). Migrate your /search-results page content if you customized it.

= 4.8 =
* Index the shortcodes content when stripping shortcodes tags.
* WARNING: this will require you to re-index all your documents. It can take a while if you have a large amount of documents in your WP database.

= 4.7 =
* (Screenshot 6) A new option can prevent/enforce submitting the search form after selecting a value from the autocomplete list.

= 4.6 =
* Remove shortcodes from results by stripping shortcodes from documents indexed.
* WARNING: this will require you to re-index all your documents. It can take a while if you have a large amount of documents in your WP database.

= 4.5 =
* [Screenshot](https://s.w.org/plugins/wpsolr-search-engine/screenshot-16.png "Texts localization"): All front-end texts can be changed, with the dedicated admin screen (screenshot 16), or:
- With gettext() standard .po/.mo files
- With WPML string translation module
* Translation files are not delivered, but /lang/wpsolr.pot can be used to generate the .po and .mo files, or WPSOLR sources can be parsed to generate a .pot file (with poedit free tool for instance).
* Multi-language is not supported in Solr search, yet. Only the front-end texts can be multilingual.

= 4.4 =
* Fix several admin and front-end php notices

= 4.3 =
* [Screenshot](https://s.w.org/plugins/wpsolr-search-engine/screenshot-15.png "Sort by"): The "Sort by" items list is configurable. You can choose not to diplay it at all, which elements it contains and in which order, which element is applied by default.
* WARNING: Your front-end sort list will not be displayed, until you configure it.

= 4.2 =
* [Screenshot](https://s.w.org/plugins/wpsolr-search-engine/screenshot-14.png "Attachement types"): You can now select which attachment type(s) you want to index (see screenshot 14).
* WARNING: If you already indexed attachments, you MUST now select which types you want, or the next time you start the indexing process, no attachments will be indexed.

= 4.1 =
* Attachments added and deleted are now synchronized with Solr in real-time (no need to sart the Solr indexing process).
* Fix message "Undefined variable: res_final".
* Fix message "Notice: ob_flush(): failed to flush buffer. No buffer to flush" in Solr operations ajax calls.

= 4.0 =
* Fix constant error DEFAULT_SOLR_TIMEOUT_IN_SECOND.

= 3.9 =
* Optional Cloud Solr hosting plans can now be chosen by those who are not familiar with Solr installation and configuration in a production environment.

= 3.8 =
* Categories are now indexed even when no custom taxonomy is selected in indexing option.

= 3.7 =
* Fix random error "undefined index: skey" when setting local Solr hosting.

= 3.6 =
* Fix JQuery issues on button emptying the index (not working on Safari, false errors displayed elsewhere).

= 3.5 =
* Add a debug checkbox on the indexing admin screen. By activating the debug mode, many details are displayed during the indexing process, to help solve difficult issues with Solr.

= 3.4 =
* Display errors occurring while deleting the Solr index data.
* Increase Solr timeout from 5 seconds to 30 seconds.

= 3.3 =
* Fix curl CA verification error when calling a Solr index protected with https.

= 3.2 =
* WPSOLR is now compatible with the latest Solr 5.x versions. Tested up to Solr 5.2.

= 3.1 =
* Fix bug on filters which prevented custom fields to be indexed.

= 3.0 =
* Prevent new posts/pages in status 'auto-draft' from calling Solr.

= 2.9 =
* Fix bug on Windows installations: "Warning: session_start(): Cannot send session cache limiter - headers already sent ".

= 2.8 =
* Fix bug which prevented some keywords to be highlighted in search results snippets.

= 2.7 =
* Fix bug which prevented partial search "tem1 term3" to match results, while "tem1 term2 term3" did.
* "Did you mean" now displays multiple terms suggestions. For instance "salr serch" can now suggest "solr search".

= 2.6 =
* WARNING: this version will require you to re-index all your documents. It can take a while if you have a large amount of documents in your WP database.
* Introduce a new filter for developpers to tweak custom fields sent to Solr

= 2.5 =
* Compatible with Solr 5.x: you'll need to use the new schema.xml

= 2.4 =
* WARNING: this version will require you to re-index all your documents. It can take a while if you have a large amount of documents in your WP database.
* Improved indexing process for large amount of data: the default batch size can be changed, timeouts are caught.

= 2.3 =
* Integration with <a href="https://wordpress.org/plugins/s2member/" target="_blank">s2member plugin</a>: filter Solr results with user levels and custom capabilities.

= 2.2 =
* Fix custom taxonomies to be searchable (they used to be displayed in filters only). As a side effect, <a href="https://wordpress.org/plugins/woocommerce/" target="_blank">WooCommerce</a> product taxonomies (product_cat , product_tag) are now searchable.

= 2.1 =
* Installation failed with PHP <= 5.3: fixed.

= 2.0 =
* Integration with <a href="https://wordpress.org/plugins/groups/" target="_blank">Groups plugin</a>: filter Solr results with user groups and posts capabilities.
* Stop the indexing process when attacements fail, and display the attachment name in error. Can be related to php security.

= 1.9 =
* Display thumbnail on page result lines.

= 1.8 =
* Do not open a new page when clicking on a page result line.

= 1.7 =
* Restart indexing at last document indexed (wether it fell in error, or timeout occured)
* Prevent index deletion when indexing starts
* Index post attachements
* Add attachements checkbox in menu Solr Options -> Indexing Options -> Post types to be indexed.
* Improve Solr error messages in Solr hosting tab, and Solr operations tab, including timeout messages.

= 1.6 =
* Can now index tens of thousands of documents without freezing or timeout

= 1.5 =
* Fixed an issue with older php versions. Should activate and work from PHP 5.2.4 at least.

= 1.4 =
* Fixed warning on search page for self hosted Solr
* Requires to reload yor index with the new config files (solrconfig.xml, schema.xml). Fixed error on autocomplete, and search page with "did you mean" activated, for self hosted Solr

= 1.3 =
* Speed up search results display.

= 1.2 =
* Speed up autocompletion by 3 times.

= 1.1 =
* Improved error message when Solr port is blocked by hosting provider.
* Bug fix: Solr port used to be 4 digits. Can now be 2 digits and more.

= 1.0 =
* First version.


== Frequently Asked Questions ==

= Is there a trial for the extra packs ? =

Yes, we added a 7 days trial for all packs (Premium, bbPress, Woocommerce, WPML, Polylang, S2member, Groups, Types, ACF). Download wpsolr, then the trial instructions.

= What is the installation procedure for Solr on Windows ? =

!!! Important: always reload the index in your Solr admin UI after each install/change of file schema.xml

A tutorial at WPSOLR: [Solr 4.x](http://wpsolr.com/installation-guide/ "Apache Solr installation, Solr 4.x")

A tutorial at Wordpress support: [Windows, Solr 5.x/6.x](https://wordpress.org/support/topic/great-software-but-needs-some-documentation "Apache Solr installation, Windows, Solr 5.x/6.x")

= What is the installation procedure for Solr on linux ? =

!!! Important: always reload the index in your Solr admin UI after each install/change of file schema.xml

A tutorial at Wordpress support: [Linux, Solr 4.x](https://wordpress.org/support/topic/no-support-for-self-hosted-solr-and-not-working-for-self-hosted "Apache Solr installation, Linux, Solr 4.x")

A tutorial at Linode: [Linux, Solr 4.x](https://www.linode.com/docs/websites/cms/turbocharge-wordpress-search-with-solr "Apache Solr installation, Linux, Solr 4.x")

For Linux, Solr 6.1.0 (tested). Replace 6.1.0 with your current Solr version.
`
wget http://archive.apache.org/dist/lucene/solr/6.1.0/solr-6.1.0.tgz
tar xvf solr-6.1.0.tgz
solr-6.1.0/bin/solr start
solr-6.1.0/bin/solr create -c wpsolr-6.1.0
(download solr 5.xx config files from http://wpsolr.com/releases/#1.0)
cp solrconfig.xml schema.xml solr-6.1.0/server/solr/wpsolr-6.1.0/conf/
(reload index with solr admin UI)
(configure a new index in wpsolr admin UI:
Index name: wpsolr - local 6.1.0
Solr Protocol: http
Solr host: localhost
Solr port: 8983
Solr path: /solr/wpsolr-6.1.0
)
(index posts on wpsolr admin UI, including a pdf file)
(search in posts, retrieve the pdf)
`

= What WPSOLR can do to help my search ? =
Relevanssi, Better Search, Search Everything, are really great because they do not need other external softwares or services to work.

WPSOLR, on the other hand, requires Apache Solr, the worlds's most popular search engine on the planet, to index and search your data.

If you can manage to install Solr (or to buy a hosting Solr service), WPSOLR can really help you to:

* Search in many sites for aggregated searches

* Search in thousands or millions of posts/products

* Search in attached files (pdf, word, excel....)

* Filter results with dynamic facets

* Tweak your search in many many ways with Solr solrconfig.cml and schema.xml files (language analysers, stopwords, synonyms, stemmers ...)

= Do you offer a premium version ? =
Yes. Check out our <a href="http://wpsolr.com/pricing">Premium Packs</a>.

= Can you search in several sites and show results on one site ? =
Yes, there is a (Premium) multisites option in wpsolr.

You configure the sites belonging to the network search as "local", and one or several "global" sites to show results from "local" sites consolidated, while "Local" sites continue to search their own data.

As Solr manages the whole network search, there is almost no limits to the number of "local" sites, and number of posts indexed.
Contact us for more information on this multisites feature.

= Can you manage millions of products/attributes/variations ? =
Yes (Premium for attributes/variations). WPSOLR is based on the mighty Apache Solr search engine. It can easily manage millions of posts, and fast.

= Why the search page does not show up ? =
You have to select the admin option "Replace standard WP search", and verify that your urls permalinks are activated.

= Which PHP version is required ? =

WPSOLR uses a Solr client library, Solarium, which requires namespaces.

Namespaces are supported by PHP >= 5.3.0

= Which Apache Solr version is supported ? =

Solr 4.x, Solr 5.x, Solr 6.x

WPSOLR was tested till Solr 6.1.0

= Can I have my Apache Solr server hosted ? =

Yes, on <a href='http://gotosolr.com/en/'>Gotosolr Solr hosting</a>.

[Gotosolr Solr hosting tutorial](http://www.gotosolr.com/en/solr-tutorial-for-wordpress/ "Gotosolr Solr hosting tutorial")

[sitepoint tutorial on Gotosolr Solr hosting with WPSOLR](https://www.sitepoint.com/enterprise-search-with-apache-solr-and-wordpress/ "sitepoint tutorial on Gotosolr Solr hosting with WPSOLR")

= How do I install and configure my own Apache Solr server ? =

Please refer to our detailed <a href='http://wpsolr.com/installation-guide/'>Installation Guide</a>.


= What version of Solr does the WPSOLR Search Engine plugin need? =

WPSOLR Search Engine plugin is <a href="http://wpsolr.com/releases/#1.0"> compatible with the following Solr versions</a>. But if you were going with a new installation, we would recommend installing Solr version 3.6.x or above.


= Does WPSOLR Search Engine Plugin work with any version of WordPress? =

As of now, the WPSOLR Search Engine Plugin works with WordPress version 3.8 or above.


= Can custom post type, custom taxonomies and custom fields be added filtered search? =

Yes (Premium feature). The WPSOLR Search Engine plugin provides option in dashboard, to select custom post types, custom taxonomies and custom fields, to be added in filtered search.


= Do you offer support? =

You can raise a support question for our plugin from wordpress.org.
Premium users can use our zendesk support.
