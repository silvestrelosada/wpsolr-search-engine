var info = {
    curentpage: "1",
    numberofelements: jQuery('#pagination-flickr li').size(),
    ajaxurl: wp_localize_script_infinitescroll.ajax_url,
    loadimage: wp_localize_script_infinitescroll.loadimage,
    loadingtext: wp_localize_script_infinitescroll.loadingtext,
    inprogress: 'no'
};

jQuery("#pagination-flickr").hide();
//jQuery(".res_info").hide();

jQuery.urlParam = function (name) {
    var results = new RegExp('[\?&]' + name + '=([^&#]*)').exec(window.location.href);
    return results ? results[1] || 0 : 0;
}

// ajax data
var datajax = {
    'query': jQuery.urlParam(wp_localize_script_autocomplete.SEARCH_PARAMETER_Q),
    'action': 'return_solr_results',
    'page_no': 1,
    'opts': '',
    'sort_opt': wp_localize_script_autocomplete.SORT_CODE_BY_RELEVANCY_DESC
};

// Facets
jQuery(document).on('click', '.select_opt', function () {

    datajax.opts = jQuery(this).attr("id");
    info.numberofelements = 1;
    info.curentpage = 1;
    //console.log('curent data opts ' + datajax.opts);

    setTimeout(function () {
        info.numberofelements = jQuery('#pagination-flickr li').size();
    }, 2000);

    return;
});

function showloading() {
    jQuery('#loadingtext').remove();
    //console.log('showloading');
    jQuery("body").prepend('<div id="loadingtext" style="position: fixed; top: 100px; text-align: center; font-size: 15px; z-index: 999; margin: 0px auto; background: #CCC; padding: 30px; left: 40%;">' + info.loadingtext + '<br /><img src="' + info.loadimage + '" alt="loading" /></div>');

    setTimeout(function () {
        jQuery('#loadingtext').remove();
    }, 1000);


    return;
}


jQuery(document).scroll(function () {

    jQuery("#pagination-flickr").hide();

    info.numberofelements = jQuery('#pagination-flickr li').size();

    jQuery(document).ready(function () {

        if (info.numberofelements > 1) {

            var scrollposition = jQuery('body').scrollTop();
            var resultsHeight = jQuery(".results-by-facets").outerHeight();
            var resultsPosition = jQuery(".results-by-facets").offset().top;

            //console.log('scrollposition:' + scrollposition);
            //console.log('resultsHeight:' + resultsHeight);
            //console.log('resultsPosition:' + resultsPosition);

            if ((resultsHeight - resultsPosition < scrollposition) && info.curentpage <= info.numberofelements && info.inprogress == 'no') {

                info.inprogress = 'yes';
                showloading(); // show loading bar
                info.curentpage++;
                setTimeout(function () {
                    info.inprogress = 'no';
                }, 1800); // execute function after 2 sec

                datajax.sort_opt = jQuery(".select_field").val();
                //console.log('curent select_field is ' + datajax.sort_opt);

                datajax.page_no = info.curentpage;
                //console.log('curent data page no ' + datajax.page_no);
                //console.log(info.curentpage + 'pagenumber');


                // Ajax call on the current selection
                // Merge default parameters with active parameters
                var parameters = get_ui_selection();
                var selection_parameters = {};
                selection_parameters[wp_localize_script_autocomplete.SEARCH_PARAMETER_PAGE] = datajax.page_no;
                jQuery.extend(parameters, selection_parameters);

                // Generate url parameters
                var url_parameters = generateUrlParameters(window.location.href, parameters, true);
                //console.log('parameters' + url_parameters);

                // Generate Ajax data object
                var data = {action: 'return_solr_results', url_parameters: url_parameters};

                // since wp 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
                jQuery.post(info.ajaxurl, data, function (response) {

                    var obj = jQuery.parseJSON(response)

                    if (jQuery.isArray(obj) && obj.length > 0) {

                        // Loop on result rows
                        jQuery.each(obj[0], function (index, value) {
                            //console.log(value);

                            jQuery(".results-by-facets").append(value);
                        });
                    }

                });


            } else {

                //console.log('fade out postion: ' + position);
                //console.log('fade out scrollpositionnew: ' + scrollpositionnew);
                //console.log('numberelements: ' + info.numberofelements);
                //console.log('info.curentpage: ' + info.curentpage);
                //console.log('info.inprogress: ' + info.inprogress);
                //console.log(' ----- ');


                // do noting
            }


        }

    });
});