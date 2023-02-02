/**
 * WPMerge
 * Copyright (c) 2018 Revmakx LLC
 * revmakx.com
 */

var wpmerge_exim_unexpected_response_count = 0;
var wpmerge_show_progress_container = '#wpmerge_dev_exim_progress';
var wpmerge_show_progress_first_task_running = true;

async function wpmerge_dev_exim_initiate_overall_task_js(overall_task, options = {}){
	var request = {};
	var response = {};
	request.url = wpmerge_dev_ajax.ajax_url;
	request.method = 'POST';
	request.data = {
		action: 'wpmerge_exim_initiate_overall_task',
		overall_task: overall_task
	};

	if(options.request_data && typeof options.request_data === 'object'){
		request.data = Object.assign(request.data, options.request_data);
	}

	if(wpmerge_show_progress_container == '#wpmerge_dev_exim_progress' ){
		jQuery(wpmerge_show_progress_container).html('<div class="loader" style="margin: 10px;"></div>');
	}
	
	wpmerge_is_progressive_task_running = true;

	jQuery(wpmerge_this_element).addClass('loading');
	await wpmerge_do_http_call(request, response);
	jQuery(wpmerge_this_element).removeClass('loading');

	if(response.http_is_success){
		response_obj = wpmerge_clean_and_parse_json_response(response.http_data);
		if(response_obj === 'JSON_PARSE_ERROR'){
			wpmerge_is_progressive_task_running = false;
			alert(wpmerge__lang('unexpected_response_txt_see_browser_console'));
			return false;
		}
		if(response_obj.hasOwnProperty('overall_status')){
			if( response_obj.overall_status == 'pending'
				|| response_obj.overall_status == 'paused'
				|| response_obj.overall_status == 'retry' ){
				wpmerge_exim_show_progress(response_obj);
				if(options.on_initiate_success){
					options.on_initiate_success(response_obj);
				}
				setTimeout(function() {  try{ wpmerge_dev_exim_continue_overall_task_js(overall_task, options); } catch(e){} }, 200);
				return true;
			}
			else{
				wpmerge_is_progressive_task_running = false;
				wpmerge_exim_show_progress(response_obj);
				return true;
			}
		}
		else{
			wpmerge_is_progressive_task_running = false;
			//alert('Unexpected response.');
			return false;
		}
	}
	else{
		wpmerge_is_progressive_task_running = false;
		alert(wpmerge_get_http_error_details(response));
		return false;
	}
	//something wrong
	alert('Something went wrong while initiating.');//improve later
}

async function wpmerge_dev_exim_continue_overall_task_js(overall_task, options){
	var request = {};
	var response = {};
	request.url = wpmerge_dev_ajax.ajax_url;
	request.method = 'POST';
	request.data = {
		action: 'wpmerge_exim_continue_overall_task',
		overall_task: overall_task
	};

	if(options.request_data && typeof options.request_data === 'object'){
		request.data = Object.assign(request.data, options.request_data);
	}

	wpmerge_is_progressive_task_running = true;
	await wpmerge_do_http_call(request, response)

	var response_obj;
	if(response.http_is_success){
		response_obj = wpmerge_clean_and_parse_json_response(response.http_data);
		if(response_obj === 'JSON_PARSE_ERROR'){
			wpemerge_exim_retry(overall_task, options, wpmerge__lang('unexpected_response_txt_see_browser_console'));
			return false;
		}
		if(response_obj.hasOwnProperty('overall_status')){
			wpmerge_exim_unexpected_response_count = 0;//reset
			if( response_obj.overall_status == 'pending'
				|| response_obj.overall_status == 'paused'
				|| response_obj.overall_status == 'retry' ){
				// var str = JSON.stringify(response_obj, null, 4);
				// jQuery('#wpmerge_debug_exim_progress').html('<pre>' + str + '</pre>');

				wpmerge_exim_show_progress(response_obj);

				var timeout_milliseconds = 200;

				if( response_obj.overall_status == 'retry'
					&& typeof response_obj.overall_retry_interval != 'undefined' ){
					timeout_milliseconds = response_obj.overall_retry_interval;
				}

				setTimeout(function() { try{ wpmerge_dev_exim_continue_overall_task_js(overall_task, options); } catch(e){} }, timeout_milliseconds);

				if(options.on_continue){
					options.on_continue(response_obj);
				}

				return true;
			}
			else if(response_obj.overall_status == 'completed'){
				wpmerge_is_progressive_task_running = false;

				// var str = JSON.stringify(response_obj, null, 4);
				// jQuery('#wpmerge_debug_exim_progress').html('<pre>' + str + '</pre>');

				wpmerge_exim_show_progress(response_obj);
				//alert("Completed");

				if(options.on_complete){
					options.on_complete(response_obj);
				}

				// if(overall_task == 'apply_changes_for_dev_in_dev' || overall_task == 'apply_changes_for_prod_in_dev'){
				// 	//redirect
				// 	setTimeout(function() { location.assign(wpmerge_admin_url + 'admin.php?page=wpmerge_dev_options'); }, 1000);
				// }

				if(overall_task == 'export_changed_files_in_dev'){
					//redirect
					setTimeout(function() { location.assign(wpmerge_dev_ajax.admin_url + 'admin.php?page=wpmerge_dev_options&download_changed_file=1'); }, 1000);
				}

				return true;
			}
			else if(response_obj.overall_status == 'error'){
				wpmerge_is_progressive_task_running = false;

				// var str = JSON.stringify(response_obj, null, 4);
				// jQuery('#wpmerge_debug_exim_progress').html('<pre>' + str + '</pre>');

				wpmerge_exim_show_progress(response_obj);
				//alert("Error");
				if(options.on_error){
					options.on_error(response_obj);
				}
				return true;
			}
			else{
				wpmerge_is_progressive_task_running = false;
				// var str = JSON.stringify(response_obj, null, 4);
				// jQuery('#wpmerge_debug_exim_progress').html('<pre>' + str + '</pre>');

				wpmerge_exim_show_progress(response_obj);
				return true;
			}
		}
		else{
			wpemerge_exim_retry(overall_task, options, 'Unexpected response.');
			return false;
		}
	}
	else{
		var err_msg = wpmerge_get_http_error_details(response);
		wpemerge_exim_retry(overall_task, options, err_msg);
		return false;
	}
	//something wrong
	alert('Something went wrong with progressing.');//improve later
}

function wpemerge_exim_retry(overall_task, options, retry_failed_err_msg){
	wpmerge_exim_unexpected_response_count++;
	if(wpmerge_exim_unexpected_response_count <= 5){
		setTimeout(function() {  try{ wpmerge_dev_exim_continue_overall_task_js(overall_task, options); } catch(e){}  }, 200);//ignore http error, lets retry
	}
	else{
		wpmerge_remove_processing_animation_and_show_error_on_failure();
		wpmerge_is_progressive_task_running = false;
		alert(retry_failed_err_msg);//improve later
	}
	return false;
}

function wpmerge_exim_show_progress(exim_response){
	var overall_task_title = exim_response.overall_task_title;
	var overall_task_status = exim_response.overall_status;
	var tasks = exim_response.tasks;
	var task_status_html = '';
	var overall_task_result_html = '';

	var class_status;
	var previous_task_status = '';
	var loop_i = 0;
	for(var task in tasks){
		var progress_percent_str = '';
		var task_title = tasks[task]['task_title'];
		if(tasks[task]['status'] == 'pending'){
			if( wpmerge_show_progress_first_task_running && (previous_task_status == 'completed' || loop_i == 0) ){
				class_status = 'processing';//hack to show next one is started
			}
			else{
				class_status = 'waiting';
			}
		}
		else if( tasks[task]['status'] == 'running'
				 || tasks[task]['status'] == 'paused'
				 || tasks[task]['status'] == 'retry' ){
			class_status = 'processing';
			if( tasks[task].hasOwnProperty('progress_status') && 
			tasks[task]['progress_status'] && 
			tasks[task]['progress_status'].hasOwnProperty('percent') &&
			tasks[task]['progress_status']['percent'] > 0 && //lets show if greater than zero
			tasks[task]['progress_status']['percent'] < 100 //lets show if lesser than 100
			){
				progress_percent_str = '<span>' + tasks[task]['progress_status']['percent'] + '%</span>';
			}
		}
		else if(tasks[task]['status'] == 'error'){
			class_status = 'error';
		}
		else if(tasks[task]['status'] == 'completed'){
			class_status = 'done';
		}
		else{
			class_status = 'waiting';
		}
		previous_task_status = tasks[task]['status'];
		task_status_html += '<p class="'+class_status+'">'+task_title+'... ' + progress_percent_str + '</p>';
		loop_i++;
	}

	if(overall_task_status == 'completed'){
		overall_task_result_html = '<p class="result success">'+overall_task_title+' successfully!</p>';
	}
	else if(overall_task_status == 'error'){
		var overall_error_msg = exim_response.overall_error_msg;
		overall_task_result_html = '<p class="result oops">Oops... '+overall_error_msg+'</p>';
	}

	var progress_html = '<div class="process-steps-progress" style="padding: 10px 0 0 30px;">' +
	'<h3>'+overall_task_title+'</h3>' +
	task_status_html +
	overall_task_result_html +
	'</div>';
	jQuery(wpmerge_show_progress_container).html(progress_html);
}

function wpmerge_remove_processing_animation_and_show_error_on_failure(){
	jQuery('.wpmerge_b .process-steps-progress .processing').removeClass('processing').addClass('error');
}

async function wpmerge_check_recorded_queries_count_and_do_apply_changes(task){//this will be called from wp-admin(not bridge)
	if(task != 'apply_changes_for_dev_in_dev' && task != 'apply_changes_for_prod_in_dev'){
		return false;
	}

	var recorded_queries_count = null;

	var request = {};
	var response = {};
	request.url = wpmerge_dev_ajax.ajax_url;
	request.method = 'POST';
	request.data = {
		action: 'wpmerge_dev_process_ajax_request',
		wpmerge_action : 'get_recorded_queries_count'
	};

	jQuery(wpmerge_this_element).addClass('loading');
	await wpmerge_do_http_call(request, response);
	jQuery(wpmerge_this_element).removeClass('loading');
	
	if(response.http_is_success){
		response_obj = wpmerge_clean_and_parse_json_response(response.http_data);
		if(response_obj === 'JSON_PARSE_ERROR'){
			alert(wpmerge__lang('unexpected_response_txt_see_browser_console'));
			return false;
		}
		if(response_obj.hasOwnProperty('status')){
			if(response_obj.status === 'success'){
				if(response_obj.hasOwnProperty('recorded_queries_count')){
					recorded_queries_count = response_obj.recorded_queries_count;
				}
			}
		}
	}
	else{
		//wpmerge_show_result(result_element, 'error', 'HTTP call failed.');
		alert(wpmerge_get_http_error_details(response));
		return;
	}

	wpmerge_dev_show_recorded_queries_count(recorded_queries_count);//lets show them updated data

	//lets take alternate option only if we get zero queries, other wise >0 or null or false just do as usual 
	if(recorded_queries_count === 0 || recorded_queries_count === '0'){
		if(task == 'apply_changes_for_dev_in_dev'){
			var is_confirm = confirm('There are no queries recorded that can be applied. If you still want to clone the prod DB alone, click Ok. After cloning, a DB modification will be done to continue recording of changes.');
			if(!is_confirm){
				return false;
			}
			wpmerge_prepare_bridge('do_prod_db_import_and_db_mod');
		}
		else if(task == 'apply_changes_for_prod_in_dev'){
			alert('There are no queries recorded that can be applied.');
		}		
	}
	else{//initiate prepare bridge - it will take care
		wpmerge_prepare_bridge(task);
	}
}
async function wpmerge_dev_check_and_do_apply_changes(main_task){//this mostly will be called from bridge
	//-------------diabled checking is_changes_applied_in_dev, now 2 task run by default
	//this function will check if prod db clone is necessary before apply changes. Then it will do prod db clone first and then it apply changes

	// var is_changes_applied_in_dev;

	// //step - 1 check prod db clone is necessary
	// var request = {};
	// var response = {};
	// request.url = wpmerge_dev_ajax.ajax_url;
	// request.method = 'POST';
	// request.data = {
	// 	action: 'wpmerge_dev_process_ajax_request',
	// 	wpmerge_action : 'is_changes_applied_in_dev'
	// };

	// await wpmerge_do_http_call(request, response);

	// if(response.http_is_success){
	// 	response_obj = wpmerge_clean_and_parse_json_response(response.http_data);
	// 	if(response_obj === 'JSON_PARSE_ERROR'){
	// 		alert(wpmerge__lang('unexpected_response_txt_see_browser_console'));
	// 		return false;
	// 	}
	// 	if(response_obj.hasOwnProperty('status')){
	// 		if(response_obj.status === 'success'){
	// 			if(response_obj.hasOwnProperty('is_changes_applied_in_dev')){
	// 				is_changes_applied_in_dev = response_obj.is_changes_applied_in_dev;
	// 			}
	// 		}
	// 	}
	// }
	// else{
	// 	//wpmerge_show_result(result_element, 'error', 'HTTP call failed.');
	// 	alert(wpmerge_get_http_error_details(response));
	// 	return;
	// }

	// //step - 2
	var main_task_options = {
		on_complete : function(task_response_obj){
			wpmerge_dev_bridge_task_on_complete();
		}
	}

	// //step - 2a if prod db clone not required
	// if(!is_changes_applied_in_dev){
	// 	wpmerge_dev_exim_initiate_overall_task_js(main_task, main_task_options);
	// 	return;
	// }

	// //step - 2b if prod db clone required
	var options = {
		on_complete : function(task_response_obj){
			wpmerge_show_progress_container = '#wpmerge_dev_exim_progress2';
			var offset = jQuery(wpmerge_show_progress_container).offset();
			jQuery(window).scrollTop( offset.top );
			wpmerge_dev_exim_initiate_overall_task_js(main_task, main_task_options);
		},
		on_initiate_success : function(task_response_obj){
			wpmerge_dev_show_next_overall_task_details(main_task);
		},
	}
	wpmerge_dev_exim_initiate_overall_task_js('prod_db_import', options);
}

async function wpmerge_do_prod_db_import_and_db_mod_and_record(options){
	if(typeof options !== 'object'){
		options = {};
	}

	var db_mod_task = 'do_db_modification_in_dev';
	var db_mod_task_options = {
		on_complete : async function(task_response_obj){
			if(options.hasOwnProperty('record') && options.record == 'on'){
				await wpmerge_dev_record_switch_js('on');
			}
			await wpmerge_dev_bridge_task_on_complete();
		}
	}

	var prod_clone_task_options = {
		on_complete : function(task_response_obj){
			wpmerge_show_progress_container = '#wpmerge_dev_exim_progress2';
			var offset = jQuery(wpmerge_show_progress_container).offset();
			jQuery(window).scrollTop( offset.top );
			wpmerge_dev_exim_initiate_overall_task_js(db_mod_task, db_mod_task_options);
		},
		on_initiate_success : function(task_response_obj){
			wpmerge_dev_show_next_overall_task_details(db_mod_task);
		},
	}
	wpmerge_dev_exim_initiate_overall_task_js('prod_db_import', prod_clone_task_options);
}

async function wpmerge_do_prod_db_import_and_db_mod_then_record_on(){
	var options = {};
	options.record = 'on';
	wpmerge_do_prod_db_import_and_db_mod_and_record(options);
}

async function wpmerge_do_prod_db_import_and_db_mod(){
	var options = {};
	wpmerge_do_prod_db_import_and_db_mod_and_record(options);
}

async function wpmerge_dev_show_next_overall_task_details(next_task){

	var request = {};
	var response = {};
	request.url = wpmerge_dev_ajax.ajax_url;
	request.method = 'POST';
	request.data = {
		action: 'wpmerge_exim_get_default_state_for_dummy',
		overall_task : next_task
	};

	await wpmerge_do_http_call(request, response);

	if(response.http_is_success){
		response_obj = wpmerge_clean_and_parse_json_response(response.http_data);
		if(response_obj === 'JSON_PARSE_ERROR'){
			alert(wpmerge__lang('unexpected_response_txt_see_browser_console'));
			return false;
		}
		if(response_obj.hasOwnProperty('overall_status')){
			wpmerge_show_progress_container = '#wpmerge_dev_exim_progress2';
			wpmerge_show_progress_first_task_running = false;
			wpmerge_exim_show_progress(response_obj);
			wpmerge_show_progress_first_task_running = true;
			wpmerge_show_progress_container = '#wpmerge_dev_exim_progress';
		}
	}
}

async function wpmerge_dev_bridge_task_on_complete(){
	jQuery('#wpmerge_dev_exim_progress2').after('<div>Redirecting...</div>');
	await wpmerge_delete_bridge();
	setTimeout(function() { location.assign(wpmerge_admin_url + 'admin.php?page=wpmerge_dev_options&wpmerge_completed_bridge_action=' + wpmerge_bridge_action); }, 1000);
}

async function wpmerge_dev_check_and_do_export_dev_db_delta_2_prod(){

	var is_confirm = confirm('It is strongly recommended that you take a backup of prod DB. You can Cancel the operation to take the backup first or click Ok if you want to continue with the operation anyway.\n\n' +  
	'Maintenance mode will be enabled for the Prod site and it will be disabled at the end of process.');
	if(!is_confirm){
		return false;
	}

	//step - 1 check prod db clone is necessary
	var request = {};
	var response = {};
	request.url = wpmerge_dev_ajax.ajax_url;
	request.method = 'POST';
	request.data = {
		action: 'wpmerge_dev_process_ajax_request',
		wpmerge_action : 'check_old_export_dev_db_delta_2_prod'
	};

	var confirm_message;

	jQuery(wpmerge_this_element).addClass('loading');	
	await wpmerge_do_http_call(request, response);
	jQuery(wpmerge_this_element).removeClass('loading');

	if(response.http_is_success){
		response_obj = wpmerge_clean_and_parse_json_response(response.http_data);
		if(response_obj === 'JSON_PARSE_ERROR'){
			alert(wpmerge__lang('unexpected_response_txt_see_browser_console'));
			return false;
		}
		if(response_obj.hasOwnProperty('status')){
			if(response_obj.status === 'success'){
				/*if(response_obj.hasOwnProperty('prod_delta_import_is_atleast_one_query_is_successful')){
					//show confirmation
					confirm_message = 'The last time you applied changes, it looks like some error occurred before we could finish applying all changes. To avoid possible duplication, please restore the DB backup in prod (which you should have taken before applying changes) and click on the OK button to reapply changes.';
				}
				else*/ if(response_obj.hasOwnProperty('is_export_dev_db_delta_2_prod_already_done')){
					//show confirmation
					confirm_message = 'It looks like you have already applied changes to prod. If you want to do it again, please restore the DB backup (which you should have taken before applying changes) and click on the OK button to reapply changes.';
				}
			}
		}
	}
	else{
		//wpmerge_show_result(result_element, 'error', 'HTTP call failed.');
		alert(wpmerge_get_http_error_details(response));
		return;
	}

	if(confirm_message){
		var is_confirm = confirm(confirm_message);
		if(!is_confirm){
			return false;
		}
	}

	var overall_task_options = { 'on_complete' : function(){
			wpmerge_this_element = '';
			wpmerge_dev_wp_purge_cache_in_prod();
			wpmerge_dev_show_clear_cache_notification('prod');
		}
	};
	wpmerge_dev_exim_initiate_overall_task_js('export_dev_db_delta_2_prod', overall_task_options);
}