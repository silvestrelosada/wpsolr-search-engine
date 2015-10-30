// Timout handler on the indexing process
var timeoutHandler;
var timeoutHandlerIsCleared = false;

jQuery(document).ready(function () {

    jQuery(".radio_type").change(function () {

        if (jQuery("#self_host").attr("checked")) {
            jQuery('#div_self_hosted').slideDown("slow");
            jQuery('#hosted_on_other').css('display', 'none');
        }
        else if (jQuery("#other_host").attr("checked")) {
            jQuery('#hosted_on_other').slideDown("slow");
            jQuery('#div_self_hosted').css('display', 'none');


        }
    });

    // Clean the Solr index
    jQuery('#solr_delete_index').click(function () {

        jQuery('.status_del_message').addClass('loading');
        jQuery('#solr_delete_index').attr('value', 'Deleting ... please wait');

        path = jQuery('#adm_path').val();
        solr_index_indice = jQuery('#solr_index_indice').val();

        var request = jQuery.ajax({
            url: path + 'admin-ajax.php',
            type: "post",
            dataType: "json",
            timeout: 1000 * 3600 * 24,
            data: {
                action: 'return_solr_delete_index',
                solr_index_indice: solr_index_indice
            }
        });

        request.done(function (data) {

            // Errors
            if (data && (data.status != 0 || data.message)) {
                jQuery('.status_index_message').html('<br><br>An error occured: <br><br>' + data.message);

                // Block submit
                alert('An error occured.');
            }
        });

        request.fail(function (req, status, error) {

            if (error) {

                jQuery('.status_index_message').html('<br><br>An error or timeout occured. <br><br>' + '<b>Error code:</b> ' + status + '<br><br>' + '<b>Error message:</b> ' + error + '<br><br>');
            }

        });

    });

    // Stop the current Solr index process
    jQuery('#solr_stop_index_data').click(function () {

        jQuery('#solr_stop_index_data').attr('value', 'Stopping ... please wait');

        clearTimeout(timeoutHandler);

        timeoutHandlerIsCleared = true;
    });

    // Fill the Solr index
    jQuery('#solr_start_index_data').click(function () {

        jQuery('.status_index_icon').addClass('loading');

        jQuery('#solr_stop_index_data').css('visibility', 'visible');
        jQuery('#solr_start_index_data').hide();
        jQuery('#solr_delete_index').hide();

        solr_index_indice = jQuery('#solr_index_indice').val();
        batch_size = jQuery('#batch_size').val();
        is_debug_indexing = jQuery('#is_debug_indexing').prop('checked');
        is_reindexing_all_posts = jQuery('#is_reindexing_all_posts').prop('checked');

        err = 1;

        if (isNaN(batch_size) || (batch_size < 1)) {
            jQuery('.res_err').text("Please enter a number > 0");
            err = 0;
        }
        else {
            jQuery('.res_err').text();
        }

        if (err == 0) {
            return false;
        } else {

            call_solr_index_data(solr_index_indice, batch_size, 0, is_debug_indexing, is_reindexing_all_posts);

            // Block submit
            return false;
        }

    });


    // Promise to the Ajax call
    function call_solr_index_data(solr_index_indice, batch_size, nb_results, is_debug_indexing, is_reindexing_all_posts) {

        var nb_results_message = nb_results + ' documents indexed so far'

        jQuery('.status_index_message').html(nb_results_message);

        path = jQuery('#adm_path').val();

        var request = jQuery.ajax({
            url: path + 'admin-ajax.php',
            type: "post",
            data: {
                action: 'return_solr_index_data',
                solr_index_indice: solr_index_indice,
                batch_size: batch_size,
                nb_results: nb_results,
                is_debug_indexing: is_debug_indexing,
                is_reindexing_all_posts: is_reindexing_all_posts
            },
            dataType: "json",
            timeout: 1000 * 3600 * 24
        });

        request.done(function (data) {

            if (data.debug_text) {
                // Debug

                jQuery('.status_debug_message').append('<br><br>' + data.debug_text);

                if (data.indexing_complete) {
                    // Freeze the screen to have time to read debug infos
                    return false;
                }

            }

            if (data.status != 0 || data.message) {
                // Errors

                jQuery('.status_index_message').html('<br><br>An error occured: <br><br>' + data.message);

            }
            else if (!data.indexing_complete) {

                // If indexing completed, stop. Else, call once more.
                // Do not re-index all, again !
                is_reindexing_all_posts = false;
                timeoutHandler = setTimeout(call_solr_index_data(solr_index_indice, batch_size, data.nb_results, is_debug_indexing, is_reindexing_all_posts), 100);


            } else {

                jQuery('#solr_stop_index_data').click();

            }
        });


        request.fail(function (req, status, error) {

            if (error) {

                message = '';
                if (batch_size > 100) {
                    message = '<br> You could try to decrease your batch size to prevent errors or timeouts.';
                }
                jQuery('.status_index_message').html('<br><br>An error or timeout occured. <br><br>' + '<b>Error code:</b> ' + status + '<br><br>' + '<b>Error message:</b> ' + escapeHtml(error) + '<br><br>' + escapeHtml(req.responseText) + '<br><br>' + message);
            }

        });

    }

    /*
     Escape html for javascript error messages to be displayed correctly.
     */
    var entityMap = {
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': '&quot;',
        "'": '&#39;',
        "/": '&#x2F;'
    };

    function escapeHtml(string) {
        return String(string).replace(/[&<>"'\/]/g, function (s) {
            return entityMap[s];
        });
    }

    jQuery('#save_selected_index_options_form').click(function () {
        ps_types = '';
        tax = '';
        fields = '';
        attachment_types = '';

        jQuery("input:checkbox[name=post_tys]:checked").each(function () {
            ps_types += jQuery(this).val() + ',';
        });
        pt_tp = ps_types.substring(0, ps_types.length - 1);
        jQuery('#p_types').val(pt_tp);

        jQuery("input:checkbox[name=attachment_types]:checked").each(function () {
            attachment_types += jQuery(this).val() + ',';
        });
        attachment_types = attachment_types.substring(0, attachment_types.length - 1);
        jQuery('#attachment_types').val(attachment_types);

        jQuery("input:checkbox[name=taxon]:checked").each(function () {
            tax += jQuery(this).val() + ',';
        });
        tx = tax.substring(0, tax.length - 1);
        jQuery('#tax_types').val(tx);

        jQuery("input:checkbox[name=cust_fields]:checked").each(function () {
            fields += jQuery(this).val() + ',';
        });
        fl = fields.substring(0, fields.length - 1);
        jQuery('#field_types').val(fl);


    });

    jQuery('#save_facets_options_form').click(function () {

        result = '';
        jQuery(".facet_selected").each(function () {
            result += jQuery(this).attr('id') + ",";
        });
        result = result.substring(0, result.length - 1);

        jQuery("#select_fac").val(result);
    })

    jQuery('#save_sort_options_form').click(function () {

        result = '';
        jQuery(".sort_selected").each(function () {
            result += jQuery(this).attr('id') + ",";
        });
        result = result.substring(0, result.length - 1);

        jQuery("#select_sort").val(result);

    })

    jQuery('#save_selected_res_options_form').click(function () {
        num_of_res = jQuery('#number_of_res').val();
        num_of_fac = jQuery('#number_of_fac').val();
        highlighting_fragsize = jQuery('#highlighting_fragsize').val();
        err = 1;
        if (isNaN(num_of_res)) {
            jQuery('.res_err').text("Please enter valid number of results");
            err = 0;
        }
        else if (num_of_res < 1 || num_of_res > 100) {
            jQuery('.res_err').text("Number of results must be between 1 and 100");
            err = 0;
        }
        else {
            jQuery('.res_err').text();
        }

        if (isNaN(num_of_fac)) {
            jQuery('.fac_err').text("Please enter valid number of facets");
            err = 0;
        }
        else if (num_of_fac < 1 || num_of_fac > 100) {
            jQuery('.fac_err').text("Number of facets must be between 1 and 100");
            err = 0;
        }
        else {
            jQuery('.fac_err').text();

        }

        if (isNaN(highlighting_fragsize)) {
            jQuery('.highlighting_fragsize_err').text("Please enter a valid Highlighting fragment size");
            err = 0;
        }
        else if (highlighting_fragsize < 1) {
            jQuery('.highlighting_fragsize_err').text("Highlighting fragment size must be > 0");
            err = 0;
        }
        else {
            jQuery('.highlighting_fragsize_err').text();

        }

        if (err == 0)
            return false;
    })
    jQuery('#save_selected_extension_groups_form').click(function () {
        err = 1;
        if (err == 0)
            return false;
    })

    /*
     Create a temporary managed index
     */
    jQuery("input[name='submit_button_form_temporary_index']").click(function () {

        // Display the loading icon
        jQuery(this).hide();
        jQuery('.solr_error').hide();
        jQuery('.wdm_note').hide();
        jQuery(this).after("<h2>Please wait a few seconds. We are configuring your test Solr index ...</h2>");
        jQuery(this).after("<div class='loading'>");

        // Let the submit execute by doing nothing
        return true;
    });

    /*
     Remove an index configuration
     */
    jQuery('#delete_index_configuration').click(function () {

        // Remove the current configuration to delete from the DOM
        jQuery('#current_index_configuration_edited_id').remove();

        // Autosubmit
        jQuery('#settings_conf_form').submit();
    });


    jQuery('#check_index_status').click(function () {
        path = jQuery('#adm_path').val();

        name = jQuery('#index_name').val();
        host = jQuery('#index_host').val();
        port = jQuery('#index_port').val();
        spath = jQuery('#index_path').val();
        pwd = jQuery('#index_secret').val();
        user = jQuery('#index_key').val();
        protocol = jQuery('#index_protocol').val();

        error = 0;

        if (name == '') {
            jQuery('.name_err').text('Please enter an index name');
            error = 1;
        }
        else {
            jQuery('.name_err').text('');
        }

        if (spath == '') {
            jQuery('.path_err').text('Please enter a path for your index');
            error = 1;
        }
        else {
            // Remove last '/'
            if (spath.substr(spath.length - 1, 1) == '/') {
                spath = spath.substr(0, spath.length - 1);
                jQuery('#index_path').val(spath);
            }

            jQuery('.path_err').text('');
        }

        if (host == '') {
            jQuery('.host_err').text('Please enter an index host');
            error = 1;
        }
        else {
            jQuery('.host_err').text('');
        }

        if (isNaN(port) || port.length < 2) {
            jQuery('.port_err').text('Please enter a valid port');
            error = 1;
        }
        else
            jQuery('.port_err').text('');


        if (error == 1)
            return false;
        else {
            jQuery('.img-succ').css('display', 'none');
            jQuery('.img-err').css('display', 'none');
            jQuery('.img-load').css('display', 'inline');
            jQuery.ajax({
                url: path + 'admin-ajax.php',
                type: "post",
                data: {
                    action: 'return_solr_instance',
                    'sproto': protocol,
                    'shost': host,
                    'sport': port,
                    'spath': spath,
                    'spwd': pwd,
                    'skey': user
                },
                timeout: 10000,
                success: function (data1) {

                    jQuery('.img-load').css('display', 'none');
                    if (data1 == 0) {
                        jQuery('.solr_error').html('');
                        jQuery('.img-succ').css('display', 'inline');
                        jQuery('#settings_conf_form').submit();
                    }
                    else if (data1 == 1)
                        jQuery('.solr_error').text('Error in detecting solr instance');
                    else
                        jQuery('.solr_error').html(data1);

                },
                error: function (req, status, error) {

                    jQuery('.img-load').css('display', 'none');

                    jQuery('.solr_error').text('Timeout: we had no response from your Solr server in less than 10 seconds. It\'s probably because port ' + port + ' is blocked. Please try another port, for instance 443, or contact your hosting provider to unblock port ' + port + '.');
                }
            });

        }


    })


    jQuery('.plus_icon').click(function () {
        jQuery(this).parent().addClass('facet_selected');
        jQuery(this).hide();
        jQuery(this).siblings().css('display', 'inline');
    })

    jQuery('.minus_icon').click(function () {
        jQuery(this).parent().removeClass('facet_selected');
        jQuery(this).hide();
        jQuery(this).siblings().css('display', 'inline');
    })

    jQuery("#sortable1").sortable(
        {
            connectWith: ".connectedSortable",
            stop: function (event, ui) {
                jQuery('.connectedSortable').each(function () {
                    result = "";
                    jQuery(this).find(".facet_selected").each(function () {
                        result += jQuery(this).attr('id') + ",";
                    });
                    result = result.substring(0, result.length - 1);

                    jQuery("#select_fac").val(result);
                });
            }
        });


    jQuery('.plus_icon_sort').click(function () {
        jQuery(this).parent().addClass('sort_selected');
        jQuery(this).hide();
        jQuery(this).siblings().css('display', 'inline');
    })

    jQuery('.minus_icon_sort').click(function () {
        jQuery(this).parent().removeClass('sort_selected');
        jQuery(this).hide();
        jQuery(this).siblings().css('display', 'inline');
    })

    jQuery("#sortable_sort").sortable(
        {
            connectWith: ".connectedSortable_sort",
            stop: function (event, ui) {
                jQuery('.connectedSortable_sort').each(function () {
                    result = "";
                    jQuery(this).find(".sort_selected").each(function () {
                        result += jQuery(this).attr('id') + ",";
                    });
                    result = result.substring(0, result.length - 1);

                    jQuery("#select_sort").val(result);

                });
            }
        });


})
;
