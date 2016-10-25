/**
 * Remove an element from an array
 */
Array.prototype.remove = function (value) {
    if (this.indexOf(value) !== -1) {
        this.splice(this.indexOf(value), 1);
        return true;
    } else {
        return false;
    }
}

/**
 * Change the value of a url parameter, without reloading the page.
 */
function generateUrlParameters(url, current_parameters, is_remove_unused_parameters) {

    //alert(JSON.stringify(current_parameters));

    // jsurl library to manipulate parameters (https://github.com/Mikhus/jsurl)
    var url1 = new Url(url);

    /**
     * Extract parameter query
     */
    var query = current_parameters[wp_localize_script_autocomplete.SEARCH_PARAMETER_Q] || '';
    if (query !== '') {
        url1.query[wp_localize_script_autocomplete.SEARCH_PARAMETER_Q] = query;
    } else if (is_remove_unused_parameters) {
        delete url1.query[wp_localize_script_autocomplete.SEARCH_PARAMETER_Q];
    }


    /**
     *    Extract parameter fq (query field)
     *    We follow Wordpress convention for url parameters with multiple occurence: xxx[0..n]=
     *    (php is xxx[]=)
     */
    // First, remove all fq parameters
    for (var index = 0; ; index++) {
        if (undefined === url1.query[wp_localize_script_autocomplete.SEARCH_PARAMETER_FQ + '[' + index + ']']) {
            break;
        } else {
            delete url1.query[wp_localize_script_autocomplete.SEARCH_PARAMETER_FQ + '[' + index + ']'];
        }
    }
    // 2nd, add parameters
    var query_fields = current_parameters[wp_localize_script_autocomplete.SEARCH_PARAMETER_FQ] || [];
    for (var index in query_fields) {
        url1.query[wp_localize_script_autocomplete.SEARCH_PARAMETER_FQ + '[' + index + ']'] = query_fields[index];
    }

    /**
     * Extract parameter sort
     */
    var sort = current_parameters[wp_localize_script_autocomplete.SEARCH_PARAMETER_SORT] || '';
    if (sort !== '') {
        url1.query[wp_localize_script_autocomplete.SEARCH_PARAMETER_SORT] = sort;
    } else if (is_remove_unused_parameters) {
        delete url1.query[wp_localize_script_autocomplete.SEARCH_PARAMETER_SORT];
    }

    /**
     * Extract parameter page number
     */
    var paged = current_parameters[wp_localize_script_autocomplete.SEARCH_PARAMETER_PAGE] || '';
    if (paged !== '') {
        url1.query[wp_localize_script_autocomplete.SEARCH_PARAMETER_PAGE] = paged;
    } else if (is_remove_unused_parameters) {
        delete url1.query[wp_localize_script_autocomplete.SEARCH_PARAMETER_PAGE];
    }


    // Remove old search parameter
    delete url1.query[wp_localize_script_autocomplete.SEARCH_PARAMETER_SEARCH];

    return '?' + url1.query.toString();
}

/**
 * History back/forward buttons
 */
window.addEventListener("popstate", function (e) {

    // Test to fix Safari trigger ob page load
    if (e.state) {
        call_ajax_search(window.location.search, false);
    }
});

/**
 * Get the facets state (checked)
 * @returns {Array}
 */
function get_ui_facets_state() {

    // Add all selected facets to the state
    state = [];
    jQuery('.select_opt.checked').each(function () {
        // Retrieve current selection
        opts = jQuery(this).attr('id');

        if (opts !== 'wpsolr_remove_facets') {
            // Do not add the remove facets facet to url parameters
            state.push(opts);
        }

    });

    return state;
}

/**
 * Return current stored values
 * @returns {{query: *, fq: *, sort: *, start: *}}
 */
function get_ui_selection() {

    var result = {};

    result[wp_localize_script_autocomplete.SEARCH_PARAMETER_Q] = jQuery('#search_que').val() || '';
    result[wp_localize_script_autocomplete.SEARCH_PARAMETER_FQ] = get_ui_facets_state();
    result[wp_localize_script_autocomplete.SEARCH_PARAMETER_SORT] = jQuery('.select_field').val() || '';
    result[wp_localize_script_autocomplete.SEARCH_PARAMETER_PAGE] = jQuery('#paginate').val() || '';

    //alert(JSON.stringify(result));

    return result;
}

function call_ajax_search(selection_parameters, is_change_url) {

    // Pre-ajax UI display
    jQuery('.loading_res').css('display', 'block');
    jQuery('.results-by-facets').css('display', 'none');

    var url_parameters = selection_parameters;
    if (selection_parameters instanceof Object) {
        // Merge default parameters with active parameters
        var parameters = get_ui_selection();
        jQuery.extend(parameters, selection_parameters);

        //alert(JSON.stringify(parameters));

        // Update url with the current selection
        url_parameters = generateUrlParameters(window.location.href, parameters, true);
    }

    // Not an ajax, redirect to url
    if (wp_localize_script_autocomplete.is_url_redirect) {

        // Remove the pagination from the url, to start from page 1
        // xxx/2/ => xxx/
        var url_base = window.location.href.split("?")[0];
        var url = url_base.replace(/\/page\/\d+/, '');

        // Redirect to url
        window.location.href = url + url_parameters;
        return;
    }


    // Update url with the current selection, if required, and authorized by admin option
    if (is_change_url && wp_localize_script_autocomplete.is_show_url_parameters) {
        // Option to show parameters in url no selected: do nothing

        // Create state from url parameters
        var state = {url: url_parameters};

        //alert('before pushState: ' + url1.toString());

        // Create state and change url
        window.history.pushState(state, '', state.url);
    }

    // Generate Ajax data object
    var data = {action: 'return_solr_results', url_parameters: url_parameters};

    //alert(JSON.stringify(data));

    // Pass parameters to Ajax
    jQuery.ajax({
        url: wp_localize_script_autocomplete.ajax_url,
        type: "post",
        data: data,
        success: function (data1) {

            // Post Ajax UI display
            jQuery('.loading_res').css('display', 'none');
            jQuery('.results-by-facets').css('display', 'block');

            data = JSON.parse(data1);

            // Display result rows
            jQuery('.results-by-facets').html(data[0]);

            // Display pagination
            jQuery('.paginate_div').html(data[1]);

            // Display number of results
            jQuery('.res_info').html(data[2]);

            // Display number of results
            jQuery('#res_facets').html(data[3]);

        },
        error: function () {

            // Post Ajax UI display
            jQuery('.loading_res').css('display', 'none');
            jQuery('.results-by-facets').css('display', 'block');

        },
        always: function () {
            // Not called.
        }
    });
}

/**
 * JQuery UI events
 */
jQuery(document).ready(function () {

    /**
     * Search form is triggered
     */
    jQuery(document).on('focus', wp_localize_script_autocomplete.wpsolr_autocomplete_selector, function () {
        var wdm_action = jQuery('#path_to_fold').val();

        var mythis = this;

        jQuery(this).typeahead({
            ajax: {
                matcher: function (item) {
                    return true;
                },
                url: wp_localize_script_autocomplete.ajax_url,
                triggerLength: 1,
                method: "post",
                loadingClass: "loading-circle",
                preDispatch: function (query) {

                    jQuery(mythis).addClass('loading_sugg');

                    return {
                        action: wdm_action,
                        word: query,
                        security: jQuery('#ajax_nonce').val()
                    }
                },
                preProcess: function (data) {
                    jQuery(mythis).removeClass('loading_sugg');
                    return data;
                }
            }
        });
    })


    /**
     * A facet is selected/unselected
     */
    jQuery(document).on('click', '.select_opt', function () {

        // Reset pagination
        jQuery('#paginate').val('');

        var state = [];

        if (jQuery(this).attr('id') === 'wpsolr_remove_facets') {

            // Unselect all facets
            jQuery('.select_opt').removeClass('checked');
            jQuery(this).addClass('checked');

        } else {

            // Select/Unselect the element
            is_already_selected = jQuery(this).hasClass('checked');
            var facet_name = jQuery(this).attr('id').split(":")[0];
            if (is_already_selected) {
                // Unselect current selection
                jQuery(this).removeClass('checked');
                jQuery(this).closest("ul").children().find("[id^=" + facet_name + "]").removeClass('checked');
            } else {
                // Select current selection
                jQuery(this).parents("li").children(".select_opt").addClass('checked');
            }

            // Get facets state
            state = get_ui_facets_state();
        }

        //alert(JSON.stringify(state));

        // Ajax call on the current selection
        var parameter = {};
        parameter[wp_localize_script_autocomplete.SEARCH_PARAMETER_FQ] = state;
        call_ajax_search(parameter, true);

    });

    /**
     * Sort is selected
     */
    jQuery(document).on('change', '.select_field', function () {

        // Reset pagination
        jQuery('#paginate').val('');

        // Retrieve current selection
        sort_value = jQuery(this).val();

        // Ajax call on the current selection
        var parameter = {};
        parameter[wp_localize_script_autocomplete.SEARCH_PARAMETER_SORT] = sort_value;
        call_ajax_search(parameter, true);
    });


    /**
     * Pagination is selected
     */
    jQuery(document).on('click', '.paginate', function () {

        // Retrieve current selection
        page_number = jQuery(this).attr('id');

        // Store the current selection
        jQuery('#paginate').val(page_number);

        // Ajax call on the current selection
        var parameter = {};
        parameter[wp_localize_script_autocomplete.SEARCH_PARAMETER_PAGE] = page_number;
        call_ajax_search(parameter, true);

    });


})
;
