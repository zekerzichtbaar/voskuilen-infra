/**
 * WPMerge
 * Copyright (c) 2018 Revmakx LLC
 * revmakx.com
 */

jQuery(document).ready(function($) {
	$( "#wpmerge_prod_regenerate_key_btn" ).on( "click", function(e) {
		wpmerge_prod_regenerate_key();
	});	

	$( "#wpmerge_prod_copy_key" ).on( "click", function(e) {
		wpmerge_prod_copy_connect_str();
	});	

	$( "#wpmerge_prod_connect_str" ).on( "click", function(e) {
		$(this).select();
	});	
});

async function wpmerge_prod_regenerate_key(){
	var this_element = '#wpmerge_prod_regenerate_key_btn';
	var result_element = '#wpmerge_prod_regenerate_key_btn_result';
    request = {};
	response = {};
	request.url = wpmerge_ajax.ajax_url;
	request.method = 'POST';
	request.data = {
        action: 'wpmerge_prod_process_ajax_request',
		wpmerge_action: 'prod_regenerate_key'
	};

	jQuery(this_element).addClass('loading');
	await wpmerge_do_http_call(request, response);
	jQuery(this_element).removeClass('loading');
	if(response.http_is_success){
		response_obj = wpmerge_clean_and_parse_json_response(response.http_data);
		if(response_obj === 'JSON_PARSE_ERROR'){
			alert(wpmerge__lang('unexpected_response_txt_see_browser_console'));	
			return false;
		}
		if(response_obj.hasOwnProperty('status')){
			if(response_obj.status == true){
				wpmerge_show_result(result_element, 'success', '');
				jQuery('#wpmerge_prod_connect_str').val(response_obj.connect_str);
			}
		}
	}
	else{
		wpmerge_show_result(result_element, 'error', 'HTTP call failed.');
	}
}

function wpmerge_prod_copy_connect_str() {
	var copyText = document.getElementById("wpmerge_prod_connect_str");
	copyText.select();
	document.execCommand("copy");
	
	//var tooltip = document.getElementById("wpmerge_prod_copy_status");
	//tooltip.innerHTML = "Copied!";
  }