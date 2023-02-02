/**
 * WPMerge
 * Copyright (c) 2018 Revmakx LLC
 * revmakx.com
 */

//utils start here
const wpmerge_sleep = ms => new Promise(res => setTimeout(res, ms));


//utils ends here

var wpmerge_is_progressive_task_running = false;
var wpmerge_record_switch_avoid_recursive_cb = false;
var wpmerge_bridge_action = '';
var wpmerge_dev_is_bridge_call = false;
var wpmerge_this_element;

jQuery(document).ready(function($){
	//console.log('jQuery version: ' + jQuery.fn.jquery);
	jQuery( document ).on( "click change", "#wpmerge_dev_record_switch, #wpmerge_dev_record_switch_in_page", function(e) {
		if(jQuery(this).attr('id') == 'wpmerge_dev_record_switch' && e.type === "click"){
			btn_element = '#wpmerge_dev_record_switch';
			e.preventDefault();
		}
		else if(jQuery(this).attr('id') == 'wpmerge_dev_record_switch_in_page' && e.type === "change"){
			btn_element = '#wpmerge_dev_record_switch_in_page';
		}
		else{
			return;
		}

		var btn_element;
		var do_record_switch;
		if(jQuery(this).attr('id') == 'wpmerge_dev_record_switch'){
			if(jQuery(this).hasClass('wpmerge_dev_record_on')){
				do_record_switch = 'off';
			}
			else if(jQuery(this).hasClass('wpmerge_dev_record_off')){
				do_record_switch = 'on';
			}
		}
		else if(jQuery(this).attr('id') == 'wpmerge_dev_record_switch_in_page'){
			if(jQuery(this).prop("checked")){
				do_record_switch = 'on';
			}
			else {
				do_record_switch = 'off';
			}
		}

		var data = {
			'action': 'wpmerge_dev_record_switch',
			'wpmerge_dev_record_switch': do_record_switch
		};

		jQuery('#wpmerge_dev_recording_state_in_page .record-status').html('<div class="loader"></div>');

		jQuery.post(wpmerge_dev_ajax.ajax_url, data, function(response) {
			//console.log(response);
			response = wpmerge_clean_and_parse_json_response(response);
			if(response === 'JSON_PARSE_ERROR'){
				return false;
			}
			//console.log(response);
			if(response.status === 'success'){//means basic communication is ok
				if(response.get_recording_state.status_slug == 'on'){
					// commented the following to disable admin bar
					// jQuery('#wpmerge_dev_record_switch').removeClass('wpmerge_dev_record_off wpmerge_dev_record_on');
					// jQuery('#wpmerge_dev_record_switch').addClass('wpmerge_dev_record_on');
					// jQuery('#wpmerge_dev_record_switch span').html('R on');

					jQuery('#wpmerge_dev_record_switch_in_page').prop("checked", true);

				}
				else if(response.get_recording_state.status_slug == 'off'){
					// commented the following to disable admin bar
					// jQuery('#wpmerge_dev_record_switch').removeClass('wpmerge_dev_record_off wpmerge_dev_record_on');
					// jQuery('#wpmerge_dev_record_switch').addClass('wpmerge_dev_record_off');
					// jQuery('#wpmerge_dev_record_switch span').html('R off');

					jQuery('#wpmerge_dev_record_switch_in_page').prop("checked", false);

				}
				wpmerge_dev_show_recording_state_in_page(response.get_recording_state);
				wpmerge_dev_recording_switch_handle_failure(do_record_switch, response.get_recording_state)
			}
		});
	});

	jQuery( "#wpmerge_toggle_adv_opts" ).on( "click", function(e) {
		if(jQuery( "#wpmerge_adv_opts_cont" ).is(":visible")){
			jQuery( "#wpmerge_adv_opts_cont" ).hide();
			jQuery( "#wpmerge_toggle_adv_opts" ).html('+ Show advanced options');
		}
		else{
			jQuery( "#wpmerge_adv_opts_cont" ).show();
			jQuery( "#wpmerge_toggle_adv_opts" ).html('- Hide advanced options');
		}
	});

	jQuery( "#wbdbsync_run_test_func" ).on( "click", function(e) {
		wbdbsync_run_test_func();
	});

	jQuery( "#wbdbsync_initiate_prod_db_import_btn" ).on( "click", function(e) {
		//wpmerge_dev_exim_initiate_overall_task_js('prod_db_import');
		//jQuery(window).scrollTop( 0 );
		wpmerge_this_element = this;
		wpmerge_prepare_bridge('prod_db_import');
	});

	jQuery( "#wbdbsync_continue_prod_db_import" ).on( "click", function(e) {
		//wpmerge_continue_prod_db_import_js();
		wpmerge_dev_exim_continue_overall_task_js('prod_db_import');
	});

	jQuery( "#wpmerge_initiate_export_dev_db_delta_2_prod_btn" ).on( "click", function(e) {
		jQuery(window).scrollTop( 0 );
		wpmerge_this_element = this;
		wpmerge_dev_check_and_do_export_dev_db_delta_2_prod();
	});

	jQuery( "#wpmerge_continue_export_dev_db_delta_2_prod" ).on( "click", function(e) {
		//wpmerge_continue_export_dev_db_delta_2_prod_js();
		wpmerge_dev_exim_continue_overall_task_js('export_dev_db_delta_2_prod');
		
	});

	jQuery( "#wpmerge_exim_initiate_export_changed_files_in_dev_btn" ).on( "click", function(e) {
		jQuery(window).scrollTop( 0 );
		wpmerge_this_element = this;
		wpmerge_dev_exim_initiate_overall_task_js('export_changed_files_in_dev');
	});

	jQuery( "#wpmerge_continue_export_changed_files_in_dev" ).on( "click", function(e) {
		wpmerge_dev_exim_continue_overall_task_js('export_changed_files_in_dev');
	});

	jQuery( "#wpmerge_apply_changes_for_dev_in_dev_btn" ).on( "click", function(e) {
		//wpmerge_dev_exim_initiate_overall_task_js('apply_changes_for_dev_in_dev');
		//wpmerge_dev_check_and_do_apply_changes('apply_changes_for_dev_in_dev');
		wpmerge_this_element = this;
		//wpmerge_prepare_bridge('apply_changes_for_dev_in_dev');
		wpmerge_check_recorded_queries_count_and_do_apply_changes('apply_changes_for_dev_in_dev');
	});

	jQuery( "#wpmerge_apply_changes_for_prod_in_dev_btn" ).on( "click", function(e) {
		//wpmerge_dev_exim_initiate_overall_task_js('apply_changes_for_prod_in_dev');
		wpmerge_this_element = this;
		//wpmerge_prepare_bridge('apply_changes_for_prod_in_dev');
		wpmerge_check_recorded_queries_count_and_do_apply_changes('apply_changes_for_prod_in_dev');
	});

	jQuery( "#wpmerge_dev_connect_prod_btn" ).on( "click", function(e) {
		wpmerge_dev_connect_prod();
	});

	jQuery( "#wpmerge_dev_discard_changes_btn" ).on( "click", function(e) {
		wpmerge_dev_discard_changes();
	});

	jQuery( "#wpmerge_dev_reset_plugin_btn" ).on( "click", function(e) {
		wpmerge_dev_reset_plugin();
	});

	jQuery( "#wpmerge_do_db_mod_btn" ).on( "click", function(e) {
		jQuery(window).scrollTop( 0 );
		wpmerge_this_element = this;
		wpmerge_dev_exim_initiate_overall_task_js('do_db_modification_in_dev');
	});

	jQuery( "#wpmerge_initiate_dev_fix_db_serialization" ).on( "click", function(e) {
		var is_confirm = confirm('Are you sure you want to fix serialization in this DEV site?');
		if(!is_confirm){
			return false;
		}	
		jQuery(window).scrollTop( 0 );
		wpmerge_this_element = this;
		wpmerge_dev_exim_initiate_overall_task_js('fix_db_serialization_in_dev');
	});

	jQuery( "#wpmerge_initiate_prod_fix_db_serialization" ).on( "click", function(e) {
		var is_confirm = confirm('Are you sure you want to fix serialization in the PROD site?');
		if(!is_confirm){
			return false;
		}	
		jQuery(window).scrollTop( 0 );
		wpmerge_this_element = this;
		wpmerge_dev_exim_initiate_overall_task_js('fix_db_serialization_in_prod');
	});

	jQuery( "#wpmerge_save_filter_contents" ).on( "click", function(e) {
		wpmerge_save_filter_contents();
	});

	jQuery( "#wbdbsync_initiate_decode_encoded_log_queries" ).on( "click", function(e) {
		jQuery(window).scrollTop( 0 );
		wpmerge_this_element = this;

		var overall_task_options = {};

		var form_data_which = jQuery("input[name='decode_queries_which']:checked").val();
		overall_task_options.which = form_data_which;

		var form_data_range_min = jQuery("input[name='decode_queries_range_min']").val();
		var form_data_range_max = jQuery("input[name='decode_queries_range_max']").val();
		overall_task_options.range_min = form_data_range_min;
		overall_task_options.range_max = form_data_range_max;

		var options = {}
		options.request_data = {};
		options.request_data.overall_task_options = overall_task_options;

		wpmerge_dev_exim_initiate_overall_task_js('decode_encoded_logged_queries', options);
	});

	
	jQuery( "#wbdbsync_remove_decoded_log_queries" ).on( "click", function(e) {
		jQuery(window).scrollTop( 0 );
		wpmerge_this_element = this;
		
		wpmerge_dev_exim_initiate_overall_task_js('remove_decoded_logged_queries',);
	});

	jQuery( "#wpmerge_service_login_btn" ).on( "click", function(e) {
		wpmerge_service_login();
	});

	jQuery( "#wpmerge_wp_purge_cache_btn" ).on( "click", function(e) {
		wpmerge_this_element = this;
		wpmerge_dev_wp_purge_cache_in_dev();
	});

	$('#wpmerge_service_email,#wpmerge_service_password').keypress(function (e) {
		if (e.which == 13) {
			jQuery( "#wpmerge_service_login_btn" ).click();
			return false;
		}
	});

	//assuming if page is not navigated for more than 10 secs, then this will trigger - need to improve this this trigger condition is not reliable
	setTimeout(function() { wpmerge_dev_background_works(); }, 5000);

	$(window).bind('beforeunload', function(){
		if(wpmerge_is_progressive_task_running){
			return 'WPMerge Task is running.';//custom message won't be shown in most of the browsers https://stackoverflow.com/a/38880926/188371
		}
	});

	jQuery("#wpmerge_init_toggle_files").on("click", function(e){
		e.stopImmediatePropagation();
		e.preventDefault();

		var id             = '#wpmerge_exc_files';
		jQuery(id).toggle();

		if (jQuery(id).css('display') === 'block') {
			wpmerge_fancy_tree_init_exc_files(id);
		}

		return false;
	});
	
	

	jQuery("#wpmerge_init_toggle_tables").on("click", function(e){
		e.stopImmediatePropagation();
		e.preventDefault();
		wpmerge_init_toggle_table();
	});

	async function wpmerge_init_toggle_table(){
		var id             = '#wpmerge_exc_tables';
		jQuery(id).toggle();

		var table_list_with_inc_exc = {};

		var request = {};
		var response = {};
		request.url = wpmerge_ajax.ajax_url;
		request.method = 'POST';
		request.data = {
			action: 'wpmerge_dev_process_ajax_request',
			wpmerge_action: 'wpmerge_get_tables_with_inc_exc'
		};
		
		await wpmerge_do_http_call(request, response);
		if(response.http_is_success){
			response_data = wpmerge_clean_and_parse_json_response(response.http_data);
			if(response_data === 'JSON_PARSE_ERROR'){
				//wpmerge_show_result(result_element, 'error', 'Invalid response received.');
				return false;
			}
			if(response_data.hasOwnProperty('status')){
				if(response_data.status === 'success'){
					//wpmerge_show_result(result_element, 'success', '');
					//alert('success');
					table_list_with_inc_exc = response_data.tables;
					//return true;
				}
				if(response_data.status === 'error'){
					//wpmerge_show_result(result_element, 'error', response_data.error_msg);
					//alert('Error:' + response_data.error_msg);
					return true;
				}
			}
		}
		else{
			//wpmerge_show_result(result_element, 'error', 'HTTP call failed.');
			//alert('HTTP call failed.');
		}

		if (jQuery(id).css('display') === 'block') {
			wpmerge_fancy_tree_init_exc_tables(id, table_list_with_inc_exc);
		}

		return false;
	}
	

	jQuery("#wpmerge_help_collapsed .wpmerge_toggle_help").on("click", function(){
		jQuery("#wpmerge_help_collapsed").hide();
		jQuery("#wpmerge_help_cont").show();
		var help_toggles_state = {'dev_main_help_show' : '1'};
		wpmerge_save_help_toggle_save(help_toggles_state);
	});
	jQuery("#wpmerge_help_cont .wpmerge_toggle_help").on("click", function(){
		jQuery("#wpmerge_help_cont").hide();
		jQuery("#wpmerge_help_collapsed").show();
		var help_toggles_state = {'dev_main_help_show' : '0'};
		wpmerge_save_help_toggle_save(help_toggles_state);
	});

	jQuery("#wpmerge_selected_queries_info .notice-dismiss").on("click", function(){
		var help_toggles_state = {'dev_selected_queries_info' : '0'};
		wpmerge_save_help_toggle_save(help_toggles_state);
	});


	//query browser starts here

	
	jQuery('#wpmerge_query_group_cont .row_main_content').on("click", function(){
		jQuery(this).closest('.row').find('.row_details').toggle();
	});

	//if group cb is changed and change the children
	jQuery('#wpmerge_query_group_cont .query_group_cb').on("click", function(){
		var is_checked = this.checked;
		jQuery(this).closest('tr').find('.query_cb').prop('checked', is_checked);
		var queries_state = {};
		jQuery(this).closest('tr').find('.query_cb').each(function(){
			queries_state[jQuery(this).val()] = Number(jQuery(this).prop('checked'));
		});
		wpmerge_select_and_unselect_queries(queries_state);
	});

	//if children cb is changed and change the group cb
	jQuery('#wpmerge_query_group_cont .query_cb').on("click", function(){
		var is_any_one_unchecked = jQuery(this).closest('.row').find('.query_cb').not(':checked').length > 0;
		var checked = !is_any_one_unchecked;
		jQuery(this).closest('.wp-list-table-row').find('.query_group_cb').prop('checked', checked);

		var queries_state = {};
		queries_state[jQuery(this).val()] = Number(jQuery(this).prop('checked'));
		wpmerge_select_and_unselect_queries(queries_state);
	});

	//if page cb which is parent of group cb
	//'.column-cb.check-column #cb-select-all-1' '.column-cb.check-column #cb-select-all-2' half of work done by WP js
	jQuery('#wpmerge_query_group_cont .column-cb.check-column #cb-select-all-1, #wpmerge_query_group_cont .column-cb.check-column #cb-select-all-2').on("click", function(){
		var is_checked = this.checked;
		var check_txt = "UNCHECK";
		if(is_checked){
			check_txt = "CHECK";
		}
		var is_confirm = confirm('Are you sure you want to '+ check_txt +' all the queries in this page?');
		if(!is_confirm){
			return false;
		}		
		//jQuery(this).closest('.wp-list-table').find('.query_group_cb').prop('checked', is_checked);//.query_group_cb checking/unchecking this will done by WP code
		jQuery(this).closest('.wp-list-table').find('.query_cb').prop('checked', is_checked);
		var queries_state = {};
		jQuery(this).closest('.wp-list-table').find('.query_cb').each(function(){
			queries_state[jQuery(this).val()] = Number(jQuery(this).prop('checked'));
		});
		wpmerge_select_and_unselect_queries(queries_state);
	});

	jQuery('#wpmerge_query_group_cont #show_queries, #wpmerge_query_group_cont #items_per_page').on("change", function(){
		var url = jQuery('option:selected', this).data('onselect-url');
		location.assign(url);
	});

	//for query browser page WP takes care top/bottom page checkbox, but it doesn't get checked when all the items in the page gets checked. Therefore doing it here
	if(jQuery('#wpmerge_query_group_cont .column-cb.check-column #cb-select-all-1, #wpmerge_query_group_cont .column-cb.check-column #cb-select-all-2').length > 1){
		if(jQuery('#wpmerge_query_group_cont .query_cb').length < 1){
			return;
		}
		var is_any_one_unchecked = jQuery('#wpmerge_query_group_cont .query_cb').not(':checked').length > 0;
		var checked = !is_any_one_unchecked;
		jQuery('#wpmerge_query_group_cont .column-cb.check-column #cb-select-all-1, #wpmerge_query_group_cont .column-cb.check-column #cb-select-all-2').prop('checked', checked);
	}
	

	//query browser ends here

});

var wpmerge_select_and_unselect_queries_timeout_obj;
async function wpmerge_select_and_unselect_queries(queries_selection_state){
	var request = {};
	var response = {};
	request.url = wpmerge_ajax.ajax_url;
	request.method = 'POST';
	var queries_selection_state_json = JSON.stringify(queries_selection_state);
	request.data = {
        action: 'wpmerge_dev_process_ajax_request',
		wpmerge_action: 'save_queries_selection_state',
		queries_selection_state_json: queries_selection_state_json
	};

	clearTimeout(wpmerge_select_and_unselect_queries_timeout_obj);
	jQuery('#wpmerge_query_group_cont  #queries_selected_cont').html('<span style="color:green;">Saving...</span>');
	await wpmerge_do_http_call(request, response);
	if(response.http_is_success){
		response_data = wpmerge_clean_and_parse_json_response(response.http_data);
		if(response_data === 'JSON_PARSE_ERROR'){
			//alert('Invalid response received.');
			return false;
		}
		if(response_data.hasOwnProperty('status')){
			if(response_data.status === 'success'){
				if(response_data.hasOwnProperty('total_selected_queries')){
					var queries_count = response_data.total_selected_queries;
					var queries_txt = ' queries selected for merging';
					if(queries_count == 1){
						queries_txt = ' query selected for merging';
					}
					jQuery('#wpmerge_query_group_cont  #queries_selected_cont').html(queries_count + queries_txt + ' <span style="color:green;">Saved!</span>');
					wpmerge_select_and_unselect_queries_timeout_obj = setTimeout(function() {
						jQuery('#wpmerge_query_group_cont  #queries_selected_cont').html(queries_count + queries_txt); }, 1000);
				}
				//alert('success');
				return true;
			}
			if(response_data.status === 'error'){
				//alert('Error:' + response_data.error_msg);
				return false;
			}
		}
	}
	else{
		//alert('HTTP call failed.');
	}
	jQuery('#wpmerge_query_group_cont  #queries_selected_cont').html('<span style="color:darkred;font-weight:normal;">Saving error. Try again.</span>');

}

function wpmerge_fancy_tree_init_exc_files(id){

	jQuery(id).fancytree({
		checkbox: false,
		selectMode: 3,
		clickFolderMode: 3,
		debugLevel:0,
		source: {
			url: wpmerge_ajax.ajax_url,
			data: {
				action: 'wpmerge_dev_process_ajax_request',
				wpmerge_action : 'wpmerge_get_root_files',
			}
		},
		postProcess: function(event, data) {
			data.result = data.response;
		},
		init: function (event, data) {
			data.tree.getRootNode().visit(function (node) {
				if (node.data.preselected) node.setSelected(true);
				if (node.data.partial) node.addClass('fancytree-partsel');
			});
		},
		lazyLoad: function(event, ctx) {
			var key = ctx.node.key;
			ctx.result = {
				url: wpmerge_ajax.ajax_url,
				data:{
					action: 'wpmerge_dev_process_ajax_request',
					wpmerge_action : 'wpmerge_get_files_by_key',
					key : key,
				}
			};
		},
		renderNode: function(event, data){ // called for every toggle
			if (!data.node.getChildren())
				return false;
			if(data.node.expanded === false){
				data.node.resetLazy();
			}
			jQuery.each( data.node.getChildren(), function( key, value ) {
				if (value.data.preselected){
					value.setSelected(true);
				} else {
					value.setSelected(false);
				}
			});
		},
		loadChildren: function(event, data) {
			data.node.fixSelection3AfterClick();
			data.node.fixSelection3FromEndNodes();
			last_lazy_load_call = jQuery.now();
		},
		dblclick: function(event, data) {
			return false;
			// data.node.toggleSelected();
		},
		keydown: function(event, data) {
			if( event.which === 32 ) {
				data.node.toggleSelected();
				return false;
			}
		},
		cookieId: "fancytree-Cb3",
		idPrefix: "fancytree-Cb3-"
	}).on("mouseenter", '.fancytree-node', function(event){
		wpmerge_mouse_enter_files(event);
	}).on("mouseleave", '.fancytree-node' ,function(event){
		wpmerge_mouse_leave_files(event);
	}).on("click", '.fancytree-file-exclude-key' ,function(event){
		wpmerge_mouse_click_files_exclude_key(event);
	}).on("click", '.fancytree-file-include-key' ,function(event){
		wpmerge_mouse_click_files_include_key(event);
	});

	return false;
}

function wpmerge_mouse_enter_files(event){
	// Add a hover handler to all node titles (using event delegation)
	var node = jQuery.ui.fancytree.getNode(event);
	if (	node &&
			typeof node.span != 'undefined'
			&& (!node.getParentList().length
					|| node.getParent().selected !== false
					|| node.getParent().partsel !== false
					|| (node.getParent()
						&& node.getParent()[0]
						&& node.getParent()[0].extraClasses
						&& node.getParent()[0].extraClasses.indexOf("fancytree-selected") !== false )
					|| (node.getParent()
						&& node.getParent()[0]
						&&node.getParent()[0].extraClasses
						&& node.getParent()[0].extraClasses.indexOf("fancytree-partsel") !== false )
						 )
			) {
		jQuery(node.span).addClass('fancytree-background-color');
		jQuery(node.span).find('.fancytree-size-key').hide();
		jQuery(node.span).find(".fancytree-file-include-key, .fancytree-file-exclude-key").remove();
		if(node.selected){
			jQuery(node.span).append("<span role='button' class='fancytree-file-exclude-key'><a>Exclude</a></span>");
		} else {
			jQuery(node.span).append("<span role='button' class='fancytree-file-include-key'><a>Include</a></span>");
		}
	}
}

function wpmerge_mouse_leave_files(event){
	// Add a hover handler to all node titles (using event delegation)
	var node = jQuery.ui.fancytree.getNode(event);
	if (node && typeof node.span != 'undefined') {
		jQuery(node.span).find('.fancytree-size-key').show();
		jQuery(node.span).find(".fancytree-file-include-key, .fancytree-file-exclude-key").remove();
		jQuery(node.span).removeClass('fancytree-background-color');
	}
}

function wpmerge_mouse_click_files_exclude_key(event){
	var node = jQuery.ui.fancytree.getNode(event);

	if (!node) {
		return ;
	}

	if (node!= undefined && node.getChildren() != undefined) {
		var children = node.getChildren();
		jQuery.each(children, function( index, value ) {
			value.selected = false;
			value.setSelected(false);
			value.removeClass('fancytree-partsel fancytree-selected')
		});
	}

	folder = (node.folder) ? 1 : 0;
	node.removeClass('fancytree-partsel fancytree-selected');
	node.selected = false;
	node.partsel = false;
	jQuery(node.span).find(".fancytree-file-include-key, .fancytree-file-exclude-key").remove();
	wpmerge_save_inc_exc_data('wpmerge_exclude_file_list', node.key, folder);
}

function wpmerge_mouse_click_files_include_key(event){
	var node = jQuery.ui.fancytree.getNode(event);

	if (!node) {
		return ;
	}

	if (node != undefined && node.getChildren() != undefined) {
		var children = node.getChildren();
		jQuery.each(children, function( index, value ) {
			value.selected = true;
			value.setSelected(true);
			value.addClass('fancytree-selected')
		});
	}

	folder = (node.folder) ? 1 : 0;
	node.addClass('fancytree-selected');
	node.selected = true;
	jQuery(node.span).find(".fancytree-file-include-key, .fancytree-file-exclude-key").remove();
	wpmerge_save_inc_exc_data('wpmerge_include_file_list', node.key, folder);
}

function wpmerge_save_inc_exc_data(request, file, isdir){
	jQuery.post(ajaxurl, {
		action: 'wpmerge_dev_process_ajax_request',
		wpmerge_action: request,
		data: {file : file, isdir : isdir},
	}, function(data) {
	});
}

function wpmerge_fancy_tree_init_exc_tables(id, source_data){
	jQuery(id).fancytree({
		checkbox: false,
		selectMode: 2,
		icon:false,
		debugLevel:0,
		source: {
			url: ajaxurl,
			data: {
				action: 'wpmerge_dev_process_ajax_request',
				wpmerge_action : 'wpmerge_get_tables_with_inc_exc',
			},
		},
		init: function (event, data) {
			data.tree.getRootNode().visit(function (node) {
				if (node.data.preselected){
					node.setSelected(true);
					if (node.data.content_excluded && node.data.content_excluded == 1) {
						node.addClass('fancytree-partial-selected');
					}
				}
			});
		},
		loadChildren: function(event, ctx) {
			// ctx.node.fixSelection3AfterClick();
			// ctx.node.fixSelection3FromEndNodes();
			last_lazy_load_call = jQuery.now();
		},
		dblclick: function(event, data) {
			return false;
		},
		keydown: function(event, data) {
			if( event.which === 32 ) {
				data.node.toggleSelected();
				return false;
			}
		},
		cookieId: "fancytree-Cb3",
		idPrefix: "fancytree-Cb3-"
	}).on("mouseenter", '.fancytree-node', function(event){
		wpmerge_mouse_enter_tables(event);
	}).on("mouseleave", '.fancytree-node' ,function(event){
		wpmerge_mouse_leave_tables(event);
	}).on("click", '.fancytree-table-exclude-key' ,function(event){
		wpmerge_mouse_click_table_exclude_key(event);
	}).on("click", '.fancytree-table-include-key' ,function(event){
		wpmerge_mouse_click_table_include_key(event);
	}).on("click", '.fancytree-table-exclude-content' ,function(event){
		wpmerge_mouse_click_table_exclude_content(event);
	});
}


function wpmerge_mouse_enter_tables(event){
	// Add a hover handler to all node titles (using event delegation)
	var node = jQuery.ui.fancytree.getNode(event);
	jQuery(node.span).addClass('fancytree-background-color');
	jQuery(node.span).find('.fancytree-size-key').hide();
	jQuery(node.span).find(".fancytree-table-include-key, .fancytree-table-exclude-key, .fancytree-table-exclude-content").remove();
	if(node.selected || (node.extraClasses  && node.extraClasses.indexOf('fancytree-selected')!== -1 ) ){
		if (!node.extraClasses || node.extraClasses.indexOf('fancytree-partial-selected') === -1) {
			//jQuery(node.span).append("<span role='button' class='fancytree-table-exclude-key' style='margin-left: 10px;position: absolute;right: 120px;'><a>Exclude Table</a></span>");
			jQuery(node.span).append("<span role='button' class='fancytree-table-exclude-content' style='position: absolute;right: 4px;'><a>Exclude Content</a></span>");
		}  else {
			//jQuery(node.span).append("<span role='button' class='fancytree-table-exclude-key'><a>Exclude Table</a></span>");
			jQuery(node.span).append("<span role='button' class='fancytree-table-include-key'><a>Include Table</a></span>");
		}
	} else {
		jQuery(node.span).append("<span role='button' class='fancytree-table-include-key'><a>Include Table</a></span>");
	}
}

function wpmerge_mouse_leave_tables(event){
	// Add a hover handler to all node titles (using event delegation)
	var node = jQuery.ui.fancytree.getNode(event);
	if (node && typeof node.span != 'undefined') {
		jQuery(node.span).find('.fancytree-size-key').show();
		jQuery(node.span).find(".fancytree-table-include-key, .fancytree-table-exclude-key, .fancytree-table-exclude-content").remove();
		jQuery(node.span).removeClass('fancytree-background-color');
		jQuery(node.span).removeClass('fancytree-background-color');
	}
}

// function wpmerge_mouse_click_table_exclude_key(event){
// 	event.stopImmediatePropagation();
// 	event.preventDefault();
// 	var node = jQuery.ui.fancytree.getNode(event);
// 	node.removeClass('fancytree-partsel fancytree-selected fancytree-partial-selected');
// 	node.partsel = node.selected = false;
// 	jQuery(node.span).find(".fancytree-table-include-key, .fancytree-table-exclude-key, .fancytree-table-exclude-content").remove();
// 	wpmerge_save_inc_exc_data('wpmerge_exclude_table_list', node.key, false);
// }

function wpmerge_mouse_click_table_include_key(event){
	event.stopImmediatePropagation();
	event.preventDefault();
	var node = jQuery.ui.fancytree.getNode(event);
	node.removeClass('fancytree-partial-selected');
	node.addClass('fancytree-selected ');
	node.selected = true;
	jQuery(node.span).find(".fancytree-table-include-key, .fancytree-table-exclude-key, .fancytree-table-exclude-content").remove();
	wpmerge_save_inc_exc_data('wpmerge_include_table_list', node.key, false);
}

function wpmerge_mouse_click_table_exclude_content(event){
	event.stopImmediatePropagation();
	event.preventDefault();
	var node = jQuery.ui.fancytree.getNode(event);
	node.addClass('fancytree-partial-selected ');
	node.selected = true;
	jQuery(node.span).find(".fancytree-table-include-key, .fancytree-table-exclude-key, .fancytree-table-exclude-content").remove();
	wpmerge_save_inc_exc_data('wpmerge_include_table_structure_only', node.key, false);
}


function wpmerge_populate_iframe(html){
	jQuery('#wpmerge_iframe_show_response').remove();	
	var iframe = document.createElement('iframe');
	iframe.setAttribute('id', 'wpmerge_iframe_show_response');
	iframe.setAttribute('width', '80%');
	iframe.setAttribute('height', '300px');
	iframe.setAttribute('style', 'margin:0 0 0 175px;border:2px solid black;');
	iframe.setAttribute('sandbox', 'allow-same-origin');

	//var html = '<body>Foo</body>';
	iframe_debug = document.getElementById('wpmerge_iframe_debug');
	iframe_debug.appendChild(iframe);
	//document.body.appendChild(iframe);
	iframe.contentWindow.document.open();
	iframe.contentWindow.document.write(html);
	iframe.contentWindow.document.close();
}

async function wbdbsync_run_test_func(){
	var data = {
		'action': 'wpmerge_dev_process_ajax_request',
		'wpmerge_action' : 'test_func',
		//'http_request_id': http_request_id,
		//'apply_changes_group': jQuery('#wpmerge_apply_changes_group').val()
	};
	await jQuery.post(wpmerge_ajax.ajax_url, data)
	.done(function(response){
		console.log(response);
		response = wpmerge_clean_and_parse_json_response(response);
		if(response === 'JSON_PARSE_ERROR'){
			return false;
		}
		if(response.status === 'success'){
			console.log(response);
		}
	});
}

async function wpmerge_prepare_bridge(for_action){

	var is_confirm = confirm('It is recommended that you take a backup of dev DB. You can Cancel the operation to take the backup first or click Ok if you want to continue with the operation anyway.');
	if(!is_confirm){
		return false;
	}


	var request = {};
	var response = {};
	request.url = wpmerge_ajax.ajax_url;
	request.method = 'POST';
	request.data = {
		'action': 'wpmerge_dev_process_ajax_request',
		'wpmerge_action' : 'prepare_bridge',
	};	
	
	jQuery(wpmerge_this_element).addClass('loading');
	await wpmerge_do_http_call(request, response);
	jQuery(wpmerge_this_element).removeClass('loading');

	if(response.http_is_success){
		response_data = wpmerge_clean_and_parse_json_response(response.http_data);
		if(response_data === 'JSON_PARSE_ERROR'){
			alert('Cannot create the bridge.');
			return false;
		}

		if(typeof response_data.error != 'undefined'){
			alert(response_data.error);
		} else if(typeof response_data.bridge_path != 'undefined'){
			jQuery(wpmerge_this_element).addClass('loading');
			location.assign(response_data.bridge_path+'/?action='+for_action);
		} else {
			alert('Cannot create the bridge.');
		}
	}
	else {
		alert('Cannot create the bridge.');
	}
}

async function wpmerge_delete_bridge(){
	var request = {};
	var response = {};
	request.url = wpmerge_ajax.ajax_url;
	request.method = 'POST';
	request.data = {
		'action': 'wpmerge_dev_process_ajax_request',
		'wpmerge_action' : 'delete_bridge',
	};

	await wpmerge_do_http_call(request, response);

	if(response.http_is_success){
		response_data = wpmerge_clean_and_parse_json_response(response.http_data);
		if(response_data === 'JSON_PARSE_ERROR'){
			alert('cannot delete the bridge');
			return false;
		}

		if(typeof response_data.error != 'undefined'){
			alert(response_data.error);
		}
	}
	else {
		alert('HTTP error.');
	}
}

async function wpmerge_dev_connect_prod(){
	var this_element = '#wpmerge_dev_connect_prod_btn';
	var result_element = '#wpmerge_dev_connect_prod_btn_result';
	var connect_str = jQuery('#wpmerge_dev_connect_str').val();
	connect_str = connect_str.trim();
	if(typeof connect_str === 'undefined' || connect_str == ''){
		wpmerge_show_result(result_element, 'error', 'Invalid input.');
		return false;
	}

	var request = {};
	var response = {};
	request.url = wpmerge_ajax.ajax_url;
	request.method = 'POST';
	request.data = {
        action: 'wpmerge_dev_process_ajax_request',
		wpmerge_action: 'connect_to_prod',
		connect_str: connect_str
	};
	jQuery(this_element).addClass('loading');
	await wpmerge_do_http_call(request, response);
	jQuery(this_element).removeClass('loading');
	if(response.http_is_success){
		response_data = wpmerge_clean_and_parse_json_response(response.http_data);
		if(response_data === 'JSON_PARSE_ERROR'){
			wpmerge_show_result(result_element, 'error', 'Invalid response received.');
			return false;
		}
		if(response_data.hasOwnProperty('status')){
			if(response_data.status === 'success'){
				var result_html = '<div class="info-box" style="word-break: break-word;">Connected to production site at <strong><a href="'+response_data.prod_site_url+'" target="_black">'+response_data.prod_site_url+'</a></strong>. Redirecting to the main page...</div>';
				jQuery(result_element).html(result_html);

				//redirect to main page
				setTimeout(function() { location.assign(wpmerge_dev_ajax.admin_url + 'admin.php?page=wpmerge_dev_options&wpmerge_do=show_prod_clone_dialog'); }, 1500);
				//wpmerge_show_result(result_element, 'success', '');
				//alert('success');
				return true;
			}
			if(response_data.status === 'error'){
				wpmerge_show_result(result_element, 'error', response_data.error_msg);
				//alert('Error:' + response_data.error_msg);
				return true;
			}
		}
	}
	else{
		wpmerge_show_result(result_element, 'error', 'HTTP call failed.');
		//alert('HTTP call failed.');
	}
}

async function wpmerge_dev_do_db_modification(){//this is single call
	var this_element = '#wpmerge_do_db_mod_btn';
	var result_element = '#wpmerge_do_db_mod_btn_result';

	var request = {};
	var response = {};
	request.url = wpmerge_ajax.ajax_url;
	request.method = 'POST';
	request.data = {
        action: 'wpmerge_dev_process_ajax_request',
		wpmerge_action: 'do_db_modification'
	};
	jQuery(this_element).addClass('loading');
	await wpmerge_do_http_call(request, response);
	jQuery(this_element).removeClass('loading');
	if(response.http_is_success){
		response_data = wpmerge_clean_and_parse_json_response(response.http_data);
		if(response_data === 'JSON_PARSE_ERROR'){
			wpmerge_show_result(result_element, 'error', 'Invalid response received.');
			return false;
		}
		if(response_data.hasOwnProperty('status')){
			if(response_data.status === 'success'){
				wpmerge_show_result(result_element, 'success', '');
				//alert('success');
				return true;
			}
			if(response_data.status === 'error'){
				wpmerge_show_result(result_element, 'error', response_data.error_msg);
				//alert('Error:' + response_data.error_msg);
				return true;
			}
		}
	}
	else{
		wpmerge_show_result(result_element, 'error', 'HTTP call failed.');
		//alert('HTTP call failed.');
	}
}

async function wpmerge_dev_discard_changes(){
	var this_element = '#wpmerge_dev_discard_changes_btn';
	var result_element = '#wpmerge_dev_discard_changes_btn_result';
	var is_confirm = confirm('Are you sure you want to discard the changes recorded so far? This cannot be undone.');
	if(!is_confirm){
		return false;
	}
	var request = {};
	var response = {};
	request.url = wpmerge_ajax.ajax_url;
	request.method = 'POST';
	request.data = {
        action: 'wpmerge_dev_process_ajax_request',
		wpmerge_action: 'discard_changes',
		discard_changes_confirm: 'confirm'
	};
	jQuery(this_element).addClass('loading');
	await wpmerge_do_http_call(request, response);
	jQuery(this_element).removeClass('loading');
	if(response.http_is_success){
		response_data = wpmerge_clean_and_parse_json_response(response.http_data);
		if(response_data === 'JSON_PARSE_ERROR'){
			wpmerge_show_result(result_element, 'error', 'Invalid response received.');
			return false;
		}
		if(response_data.hasOwnProperty('status')){
			if(response_data.status === 'success'){
				wpmerge_show_result(result_element, 'success', '');
				wpmerge_dev_show_recorded_queries_count(response_data['recorded_queries_count']);
				//alert('success');
				return true;
			}
			if(response_data.status === 'error'){+
				wpmerge_show_result(result_element, 'error', response_data.error_msg);
				//alert('Error:' + response_data.error_msg);
				return true;
			}
		}
	}
	else{
		wpmerge_show_result(result_element, 'error', 'HTTP call failed.');
		//alert('HTTP call failed.');
	}
}

async function wpmerge_dev_reset_plugin(){
	var this_element = '#wpmerge_dev_reset_plugin_btn';
	var result_element = '#wpmerge_dev_reset_plugin_btn_result';
	var is_confirm = confirm('Are you sure you want to reset the plugin? The recorded changes and plugin settings will be deleted. But DB modifications will not be removed.');
	if(!is_confirm){
		return false;
	}
	var request = {};
	var response = {};
	request.url = wpmerge_ajax.ajax_url;
	request.method = 'POST';
	request.data = {
        action: 'wpmerge_dev_process_ajax_request',
		wpmerge_action: 'reset_plugin',
		reset_plugin_confirm: 'confirm'
	};
	jQuery(this_element).addClass('loading');
	await wpmerge_do_http_call(request, response);
	jQuery(this_element).removeClass('loading');
	if(response.http_is_success){
		response_data = wpmerge_clean_and_parse_json_response(response.http_data);
		if(response_data === 'JSON_PARSE_ERROR'){
			wpmerge_show_result(result_element, 'error', 'Invalid response received.');
			return false;
		}
		if(response_data.hasOwnProperty('status')){
			if(response_data.status === 'success'){
				wpmerge_show_result(result_element, 'success', '');

				//redirect
				setTimeout(function() { location.assign(wpmerge_dev_ajax.admin_url + 'admin.php?page=wpmerge_setup_env'); }, 1000);

				return true;
			}
			if(response_data.status === 'error'){
				wpmerge_show_result(result_element, 'error', response_data.error_msg);
				return true;
			}
		}
	}
	else{
		wpmerge_show_result(result_element, 'error', 'HTTP call failed.');
	}
}

async function wpmerge_dev_get_recording_state(){
	var request = {};
	var response = {};
	request.url = wpmerge_ajax.ajax_url;
	request.method = 'POST';
	request.data = {
        action: 'wpmerge_dev_process_ajax_request',
		wpmerge_action: 'get_recording_state'
	};

	await wpmerge_do_http_call(request, response);
	if(response.http_is_success){
		response_data = wpmerge_clean_and_parse_json_response(response.http_data);
		if(response_data === 'JSON_PARSE_ERROR'){
			alert('Invalid response received.');
			return false;
		}
		if(response_data.hasOwnProperty('status')){
			if(response_data.status === 'success'){
				wpmerge_dev_show_recording_state_in_page(response_data['get_recording_state']);
				wpmerge_dev_show_recorded_queries_count(response_data['recorded_queries_count']);
				//alert('success');
				return true;
			}
			if(response_data.status === 'error'){
				//alert('Error:' + response_data.error_msg);
				return true;
			}
		}
	}
	else{
		//alert('HTTP call failed.');
	}
}

async function wpmerge_dev_background_works(){
	if(wpmerge_is_progressive_task_running || wpmerge_dev_is_bridge_call){
		return false;//lets not check this
	}

	//this is background functionality, lets maintain silence
	var request = {};
	var response = {};
	request.url = wpmerge_ajax.ajax_url;
	request.method = 'POST';
	request.data = {
        action: 'wpmerge_dev_process_ajax_request',
		wpmerge_action: 'dev_background_works'
	};

	await wpmerge_do_http_call(request, response);
	if(response.http_is_success){
		response_data = wpmerge_clean_and_parse_json_response(response.http_data);
		if(response_data === 'JSON_PARSE_ERROR'){
			alert('Invalid response received.');
			return false;
		}
		if(response_data.hasOwnProperty('status')){
			if(response_data.status === 'success'){
				//alert('success');
				if(response_data.hasOwnProperty('is_fresh_db_modifications_required')){
					if(response_data.is_fresh_db_modifications_required === true){
						wpmerge_dev_toggle_db_modification_required_notice('show');
						wpmerge_dev_db_mod_required_popup();
					}
					else{
						wpmerge_dev_toggle_db_modification_required_notice('hide');
					}
				}
				return true;
			}
			if(response_data.status === 'error'){
				//alert('Error:' + response_data.error_msg);
				return true;
			}
		}
	}
	else{
		//alert('HTTP call failed.');
	}
}

function wpmerge_dev_show_recording_state_in_page(recording_state){
	var record_class = 'off';
	var record_status_descr = 'Changes are not being recorded.';
	var record_switch_checked = '';
	if(recording_state['status'] == true){
		record_class = 'on';
		record_status_descr = 'Changes are being recorded.';
		record_switch_checked = 'checked="checked"';
	}
	var record_status_detailed_descr_slug = recording_state['status_decr_slug'];
	var record_status_detailed_descr = recording_state['status_decr'];
	if(
		record_status_detailed_descr_slug == 'db_modification_not_present' ||
		record_status_detailed_descr_slug == 'db_modification_required' ||
		record_status_detailed_descr_slug == 'recording_set_to_off' ||
		record_status_detailed_descr_slug == 'all_ok'
	){
		record_status_detailed_descr = '';// for 'db_modification_not_present' don't display as we promt to do db modification
	}

	var recording_state_html = 	'<div class="record-status '+record_class+'">'+
								'<strong>'+record_status_descr+'</strong><div>'
								+'<div style="font-size: 12px;">'+record_status_detailed_descr+'<!--All changes to the Dev DB are being recorded if dev mod is present.--></div>'
								;

	jQuery('#wpmerge_dev_recording_state_in_page').html(recording_state_html);

	var recording_switch_in_page_html = '<input type="checkbox" id="wpmerge_dev_record_switch_in_page" name="wpmerge_dev_record_radio" value="1" '+ record_switch_checked +' class="slide_cb"><label for="wpmerge_dev_record_switch_in_page" class="slide_cb_label">On </label>';

	if(jQuery('#wpmerge_dev_record_switch_in_page').length == 0) {//just to bring effect, when toggle is not successful, already checkbox is toggled
		jQuery('#wpmerge_dev_record_switch_in_page_cont').html(recording_switch_in_page_html);
	}
}

function wpmerge_dev_show_recorded_queries_count(recorded_queries_count){
	recorded_queries_count_str = recorded_queries_count + ' ';
	recorded_queries_count_str += recorded_queries_count == 1 ? ' Query' : 'Queries';
	jQuery('#wpmerge_recorded_queries_count_str_heading_cont').html(recorded_queries_count_str);
	recorded_queries_count_str = recorded_queries_count_str.toLowerCase();
	jQuery('#wpmerge_recorded_queries_count_str_desc_cont').html(recorded_queries_count_str);
	
}

function wpmerge_dev_recording_switch_handle_failure(do_record_switch, result_record_state){
	if(wpmerge_record_switch_avoid_recursive_cb){
		wpmerge_record_switch_avoid_recursive_cb = false;
		return;
	}

	if(do_record_switch == 'on' && result_record_state.status_slug == 'off'){
		if(result_record_state.status_decr_slug == 'db_modification_not_present' || result_record_state.status_decr_slug == 'db_modification_required'){
			var confirm_msg;
			confirm_msg = 'To start recording changes, we need to modify a few aspects of the DB. Do you want to continue?';
			if(result_record_state.status_decr_slug == 'db_modification_required'){
				confirm_msg = 'WPMerge: We\'ve detected a change in the DB structure. To continue recording changes, we need to modify a few aspects of the DB.'+"\n"+'DB Mod now?';
			}

			var is_confirm = confirm(confirm_msg);
			if(is_confirm){
				var options = {
					on_complete : function(){
						//try turning on the recording
						wpmerge_record_switch_avoid_recursive_cb = true;
						jQuery('#wpmerge_dev_record_switch_in_page').prop("checked", true);
						jQuery("#wpmerge_dev_record_switch_in_page").trigger("change");
					},
				}
				wpmerge_dev_exim_initiate_overall_task_js('do_db_modification_in_dev', options);

			}
		}
		// else if(result_record_state.status_decr_slug == 'changes_applied_for_prod_do_prod_db_clone_and_apply_changes_for_dev'){
		// 	var is_confirm = confirm('1. Please import prod DB. \n 2. Then apply changes for dev. Do you want to Please import prod DB now?');

		// }
		// else if(result_record_state.status_decr_slug == 'changes_for_dev_in_dev_not_applied'){
		// 	var is_confirm = confirm('Please apply changes in dev?');

		// }
	}
}

function wpmerge_dev_toggle_db_modification_required_notice(action){
	if(action == 'show'){
		if(jQuery('.wpmerge_dev_db_modification_required_notice').length > 0){
			//already present no need to add again
			return;
		}

		var db_mod_notify_html = 'WPMerge: We\'ve paused recording changes since we detected a change in the DB structure. To continue recording, we need to modify a few aspects of the DB. <a href="admin.php?page=wpmerge_dev_options&wpmerge_do=db_mod&show_adv=1" target="_blank">Modify DB now <span class="dashicons dashicons-external" style="font-size: 14px; margin-left: -3px;"></span></a>';//this content also in php
		var notice_html = '<div class="notice error wpmerge_dev_db_modification_required_notice"><p>'+db_mod_notify_html+'</p></div>';

		if(jQuery('#wpbody-content .wrap .wp-heading-inline').length){
			jQuery('#wpbody-content .wrap .wp-heading-inline').after(notice_html);
			return;
		}
		if(jQuery('#wpbody-content .wrap').length){
			jQuery('#wpbody-content .wrap').prepend(notice_html);
			return;
		}
		jQuery('#wpbody-content').prepend(notice_html);		
	}
	else if(action == 'hide'){
		jQuery('.wpmerge_dev_db_modification_required_notice').remove();
	}
}

async function wpmerge_save_filter_contents(){
	var this_element = '#wpmerge_save_filter_contents';
	var size = jQuery('#wpmerge_exclude_by_size').val();

	if (!size) {
		size = 0;
	}

	var data = {
		action: 'wpmerge_dev_process_ajax_request',
		wpmerge_action : 'wpmerge_save_filter_contents',
		data: {
			user_excluded_extenstions: jQuery("#wpmerge_exclude_extensions").val(),
			user_excluded_files_more_than_size_settings: {
				size: size
			},
		}
	};
	jQuery(this_element).addClass('loading');
	await jQuery.post(wpmerge_ajax.ajax_url, data)
	.done(function(response){

		console.log(response);

		response = wpmerge_clean_and_parse_json_response(response);

		if(response === 'JSON_PARSE_ERROR'){
			wpmerge_show_result('#wpmerge_save_filter_contents_result', 'error', 'cannot save changes!');
			return false;
		}

		if(typeof response.error != 'undefined'){
			wpmerge_show_result('#wpmerge_save_filter_contents_result', 'error', response.error);
		} else if(typeof response.success != 'undefined'){
			wpmerge_show_result('#wpmerge_save_filter_contents_result', 'success', 'Changes saved successfully!.');
		} else {
			wpmerge_show_result('#wpmerge_save_filter_contents_result', 'error', 'cannot save changes!');
		}

	})
	.always(function() {
		jQuery(this_element).removeClass('loading');
	});
}

async function wpmerge_save_help_toggle_save(help_toggles_state){//can be used save each toggle state and also works in bulk
	//this is background functionality, lets maintain silence
	var request = {};
	var response = {};
	request.url = wpmerge_ajax.ajax_url;
	request.method = 'POST';
	request.data = {
        action: 'wpmerge_dev_process_ajax_request',
		wpmerge_action: 'save_help_toggles_state',
		help_toggles_state: help_toggles_state
	};

	await wpmerge_do_http_call(request, response);
	if(response.http_is_success){
		response_data = wpmerge_clean_and_parse_json_response(response.http_data);
		if(response_data === 'JSON_PARSE_ERROR'){
			//alert('Invalid response received.');
			return false;
		}
		if(response_data.hasOwnProperty('status')){
			if(response_data.status === 'success'){
				//alert('success');
				return true;
			}
			if(response_data.status === 'error'){
				//alert('Error:' + response_data.error_msg);
				return true;
			}
		}
	}
	else{
		//alert('HTTP call failed.');
	}
}


async function wpmerge_dev_record_switch_js(switch_to){
	//this is background functionality, lets maintain silence

	if(switch_to !== 'on' && switch_to !== 'off'){
		return false;
	}
	var request = {};
	var response = {};
	request.url = wpmerge_ajax.ajax_url;
	request.method = 'POST';
	request.data = {
        action: 'wpmerge_dev_record_switch',
		wpmerge_dev_record_switch: switch_to
	};

	await wpmerge_do_http_call(request, response);
	if(response.http_is_success){
		response_data = wpmerge_clean_and_parse_json_response(response.http_data);
		if(response_data === 'JSON_PARSE_ERROR'){
			return false;
		}
		if(response_data.status === 'success'){
			return true;
		}
		else if(response_data.status === 'error'){
			return false;
		}

	}
	else{
		return false;
	}
}

async function wpmerge_service_login(){
	var this_element = '#wpmerge_service_login_btn';
	var result_element = '#wpmerge_service_login_btn_result';

	var email = jQuery('#wpmerge_service_email').val();
	var password = jQuery('#wpmerge_service_password').val();
	//connect_str = connect_str.trim();

	// if(typeof connect_str === 'undefined' || connect_str == ''){
	// 	wpmerge_show_result(result_element, 'error', 'Invalid input.');
	// 	return false;
	// }

	var request = {};
	var response = {};
	request.url = wpmerge_ajax.ajax_url;
	request.method = 'POST';
	request.data = {
        action: 'wpmerge_dev_process_ajax_request',
		wpmerge_action: 'service_login',
		email: email,
		password: password
	};

	jQuery(this_element).addClass('loading');
	await wpmerge_do_http_call(request, response);
	jQuery(this_element).removeClass('loading');

	if(response.http_is_success){
		response_data = wpmerge_clean_and_parse_json_response(response.http_data);
		if(response_data === 'JSON_PARSE_ERROR'){
			wpmerge_show_result(result_element, 'error', 'Invalid response received.');
			return false;
		}
		if(response_data.hasOwnProperty('status')){
			if(response_data.status === 'success'){

				var result_html = '<div class="success-box">Sucess! Redirecting...</div>';
				jQuery(result_element).html(result_html);

				//redirect to main page
				if(typeof redirect_after_login != 'undefined'){
					setTimeout(function() { location.assign(redirect_after_login); }, 10);
				}
				return true;
			}
			if(response_data.status === 'error'){
				wpmerge_show_result(result_element, 'error', response_data.error_msg);
				//alert('Error:' + response_data.error_msg);
				return true;
			}
		}
	}
	else{
		wpmerge_show_result(result_element, 'error', 'HTTP call failed.');
		//alert('HTTP call failed.');
	}
}

var wpmerge_dev_db_mod_required_popup_called = false;
async function wpmerge_dev_db_mod_required_popup(){
	if(typeof wpmerge_dev_db_mod_required_popup_ignore != 'undefined' && wpmerge_dev_db_mod_required_popup_ignore){
		return;
	}
	if(wpmerge_dev_db_mod_required_popup_called){
		return;
	}
	wpmerge_dev_db_mod_required_popup_called = true;

	var is_confirm = confirm('WPMerge: We\'ve paused recording changes since we detected a change in the DB structure. To continue recording, we need to modify a few aspects of the DB.'+"\n"+'Modify DB now?');
	
	if(is_confirm){
		location.assign(wpmerge_dev_ajax.admin_url + 'admin.php?page=wpmerge_dev_options&wpmerge_do=db_mod&show_adv=1');
		return;
	}
	else{
		wpmerge_dev_record_switch_js('off');
		return false;
	}
}

function wpmerge_dev_wp_purge_cache_in_dev(){
	var do_wpmerge_action = 'purge_cache_for_dev_from_dev';
	wpmerge_dev_wp_purge_cache_in(do_wpmerge_action);
}

function wpmerge_dev_wp_purge_cache_in_prod(){
	var do_wpmerge_action = 'purge_cache_for_prod_from_dev';
	wpmerge_dev_wp_purge_cache_in(do_wpmerge_action);
}

async function wpmerge_dev_wp_purge_cache_in(do_wpmerge_action){
	var request = {};
	var response = {};
	request.url = wpmerge_ajax.ajax_url;
	request.method = 'POST';
	request.data = {
        action: 'wpmerge_dev_process_ajax_request',
		wpmerge_action: do_wpmerge_action
	};

	//run in background
	jQuery(wpmerge_this_element).addClass('loading');
	await wpmerge_do_http_call(request, response);
	jQuery(wpmerge_this_element).removeClass('loading');

	if(response.http_is_success){
		response_data = wpmerge_clean_and_parse_json_response(response.http_data);
		if(response_data === 'JSON_PARSE_ERROR'){
			alert('Invalid response received.');
			return false;
		}
		if(response_data.hasOwnProperty('status')){
			if( wpmerge_this_element ){
				alert(response_data.status);
			}
			if(response_data.status === 'success'){
				//alert('success');
				return true;
			}
			if(response_data.status === 'error'){
				//alert('Error:' + response_data.error_msg);
				return true;
			}
		}
	}
	else{
		//alert('HTTP call failed.');
	}
}

function wpmerge_dev_show_clear_cache_notification(site){
	var site_desc = site == 'prod' ? 'the PROD' : 'this DEV';
	alert('Please clear '+ site_desc +' site cache of the following to see the merged site.\n'+
	'    * Page Builders cache(Example: Elementor, Beaver Builder)\n'+
	'    * Cache plugins\n'+
	'    * Server level cache\n'+
	'    * Cloudflare or Sucuri cache\n'+
	'    * Browser cache.');
}