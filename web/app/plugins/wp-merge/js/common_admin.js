/**
 * WPMerge
 * Copyright (c) 2018 Revmakx LLC
 * revmakx.com
 */

var wpmerge_lang = {};
wpmerge_lang['unexpected_response_txt_see_browser_console'] = 'Unexpected HTTP response.\n\nFor response text see browser console log.';

function wpmerge__lang(slug){
	if(wpmerge_lang.hasOwnProperty(slug)){
		return wpmerge_lang[slug];
	}
	return slug;
}

async function wpmerge_do_http_call(request, response){
	if(!jQuery.isPlainObject(response)){
		return false;
	}

	var _url = request.url;
	var _method = 'GET';
	var _data;
	if(request.hasOwnProperty('method') && request.method == 'POST'){
		_method = request.method;
	}
	if(request.hasOwnProperty('data')){
		_data = request.data;
	}

	try{
		await jQuery.ajax({
			url: _url,	
			data: _data,
			method: _method
		})
		.done(function(data, textStatus, jqXHR){
			response.http_is_success = true;
			response.http_data = data;
			response.http_textStatus = textStatus;
			response.http_jqXHR = jqXHR;
		})
		.fail(function(jqXHR, textStatus, errorThrown) {
			//console.log(jqXHR, textStatus, errorThrown);
			console.log('HTTP call failed - Response text:\n', jqXHR.responseText);
			response.http_is_success = false;
			response.http_errorThrown = errorThrown;
			response.http_textStatus = textStatus;
			response.http_jqXHR = jqXHR;		
		});
			
	}
	catch(e){//unexpected "Uncaught (in promise)", error handling already done, but to avoid console error this catching is done
	}
}

function wpmerge_show_result(result_elem, status, msg){
	//status should success or error
	var result_class;
	if(status == 'success'){
		result_class = 'success-box'
		if(!msg){
			msg = 'Well done! You successfully did this.';
		}
	}
	else if(status == 'error'){
		result_class = 'error-box'
		if(!msg){
			msg = 'Oh snap! Error.';
		}
	}

	var result_html = '<div class="'+result_class+'">'+msg+'</div>';
	jQuery(result_elem).html(result_html);
}

function wpmerge_clean_response(response){
    //return substring closed by <wpmerge_response> and </wpmerge_response>
    return response.split('<wpmerge_response>').pop().split('</wpmerge_response>').shift();
}

function wpmerge_clean_and_parse_json_response(response){
	response = wpmerge_clean_response(response);
	try{
		return jQuery.parseJSON(response);
	}
	catch(e){
		console.log('Unexpected HTTP response(JSON Parse Error) - Response text:\n', response);
		return 'JSON_PARSE_ERROR';
	}
}

function wpmerge_get_http_error_details(response){
	var error_details = 'HTTP call failed.';
	if( response.hasOwnProperty('http_errorThrown') && response.hasOwnProperty('http_textStatus') && response.hasOwnProperty('http_jqXHR') ){
		error_details += '\nStatus: ' + response.http_jqXHR.status + ' - ' + response.http_jqXHRstatusText;
		error_details += '\n\nFor response text see browser console log.';
	}
	return error_details;
}