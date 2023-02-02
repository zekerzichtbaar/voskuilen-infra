<?php
/**
 * WPMerge
 * Copyright (c) 2018 Revmakx LLC
 * revmakx.com
 */

if(!defined('ABSPATH')){ exit; }

function wpmerge_prod_add_settings_menu(){
	if(defined('MULTISITE') && MULTISITE == true){
        $parent_slug = 'settings.php';
    }
    else{
        $parent_slug = 'options-general.php';
    }
    $page_title = 'WPMerge Settings';
	$menu_title = 'WPMerge';
	$capability = 'activate_plugins';
	$menu_slug = 'wpmerge_prod_setting';
	$function = 'wpmerge_prod_setting_page';
	$icon_url = '';
	$position = 61;
    add_menu_page($page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position);
    //add_submenu_page($parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function);

	// $parent_slug = 'wpmerge';
	// add_submenu_page($parent_slug, $page_title='fdgdfg', $menu_title='hi', $capability, $menu_slug, $function='');
}

if ( is_multisite() ) {
    add_action('network_admin_menu', 'wpmerge_prod_add_settings_menu');
} else {
    add_action('admin_menu', 'wpmerge_prod_add_settings_menu');
}


add_action('admin_enqueue_scripts', 'wpmerge_prod_admin_enqueue_scripts');//need to bring on demand inside wpmerge_prod_setting_page improve later 
function wpmerge_prod_setting_page(){

    wpmerge_set_error_reporting();

    $connect_str = wpmerge_prod_get_connect_str();
    $is_current_user_is_super_admin = wpmerge_prod_is_current_user_is_super_admin();

    include(WPMERGE_PATH .'/templates/prod_settings.php');
}

function wpmerge_prod_get_connect_str(){
    if(defined('MULTISITE') && MULTISITE == true){	
        // global $blog_id;
        // $details = get_user_by( 'email',get_blog_option($blog_id, 'admin_email'));
        // //$details = get_userdata($user_id_from_email->ID);
        // $username = $details->user_login;

        $current_user = wp_get_current_user(); 
        $current_user_id = $current_user->ID;
        $username = null;

        if(is_super_admin($current_user_id)){
            $username = $current_user->data->user_login;
        }
    }
    else{
        $current_user = wp_get_current_user(); 
        $username = $current_user->data->user_login;
    }	
    
    $wpmerge_prod_api_key = wpmerge_get_option('prod_api_key');

    $admin_url = admin_url();

    if(empty($wpmerge_prod_api_key) ||  empty($username) || empty($admin_url) ){
        $connect_str = '';
    }
    else{
        $connect_array = array(
            'username' => $username,
            'prod_api_key' => $wpmerge_prod_api_key,
            'admin_url' => $admin_url        
        );
    
        $connect_str = json_encode($connect_array);
    }
    return $connect_str;
}

function wpmerge_prod_is_current_user_is_super_admin(){
    $current_user = wp_get_current_user(); 
    $current_user_id = $current_user->ID;
    return is_super_admin($current_user_id);
}


function wpmerge_prod_generate_api_key($force=''){
    $add_or_update_key = false;
    $return = false;
    if(empty($force)){
        if(!wpmerge_is_exists_option('prod_api_key')){
            $add_or_update_key = true;
        }
    }
    else{
        $add_or_update_key = true;
    }
    if($add_or_update_key){
        $key = sha1( rand(201, 99999). uniqid('WPMERGE', true) . get_option('siteurl') );
        wpmerge_update_option('prod_api_key', $key);
        $return = true;
    }
    return $return;
}

function wpmerge_prod_regenerate_api_key(){
    return wpmerge_prod_generate_api_key(true);
}

function wpmerge_prod_process_ajax_request(){
    if(isset($_POST['wpmerge_action']) && $_POST['wpmerge_action'] === 'prod_regenerate_key'){
        $result = wpmerge_prod_regenerate_api_key();
        $response = array();
        $response['status'] = $result;
        $response['connect_str'] = wpmerge_prod_get_connect_str();
    }
    if(!empty($response)){
        echo wpmerge_prepare_response($response);
        exit();
    }
}

add_action('admin_post_nopriv_wpmerge_prod_connect_site', 'wpmerge_prod_process_connect_site');

function wpmerge_prod_process_connect_site(){
    if(isset($_POST['action']) && $_POST['action'] === 'wpmerge_prod_connect_site'){

        wpmerge_prod_check_dev_compatibility_may_exit_w_msg(null, true);

        if(empty($_POST['prod_api_key'])){
            $response = array();
            $response['status'] = 'error';
            $response['error_msg'] = 'Invalid API Key.';
        }
        else{
            $wpmerge_prod_api_key = wpmerge_get_option('prod_api_key');
            if(
                strlen($_POST['prod_api_key']) > 20 && 
                is_string($_POST['prod_api_key']) &&
                !empty($wpmerge_prod_api_key ) && 
                $wpmerge_prod_api_key === $_POST['prod_api_key']
                ){
                $response = array();
                $response['status'] = 'success';
                $response['site_url'] = get_option('siteurl');
                //do note that this prod site is added in dev
            }
            else{
                $response = array();
                $response['status'] = 'error';
                $response['error_msg'] = 'API Key not matching.';
            }
        }
        echo wpmerge_prepare_response($response);
        exit();
    }
}

function wpmerge_prod_auth($prod_api_key){
    if(empty($prod_api_key) || !is_string($prod_api_key) || strlen($prod_api_key) < 20){
        //invalid prod api key
        return false;
    }
    $wpmerge_prod_api_key = wpmerge_get_option('prod_api_key');
    if($wpmerge_prod_api_key === $prod_api_key){
        return true;
    }
    return false;
}

function wpmerge_prod_check_auth_may_exit_w_msg(){
    $_post_prod_api_key = isset($_POST['prod_api_key']) ? $_POST['prod_api_key'] : '';
    if(!wpmerge_prod_auth($_post_prod_api_key)){
        $result = array();
        $result['status'] = 'error_auth_failed';
        $result['error'] = 'auth_failed';
        $result['error_msg'] = 'Auth Failed';
        $result = wpmerge_prepare_response($result);
        echo $result;
        exit();
    }
}

function wpmerge_prod_verify_plugin_version_compatability($dev_plugin_version, $show_simple_error){
    if(empty($dev_plugin_version)){
        throw new wpmerge_exception('invalid_dev_plugin_version');
    }

    if(!defined('WPMERGE_VERSION') || empty(WPMERGE_VERSION)){
        throw new wpmerge_exception('invalid_prod_plugin_version');
    }

    $help_txt = 'Dev(v'.$dev_plugin_version.') and Prod(v'.WPMERGE_VERSION.') WPMerge plugins should be in the same version.';
    if($show_simple_error){//show errors without version numbers, helpful for connect prod site for safety purpose.
        $help_txt = 'Dev and Prod WPMerge plugins should be in the same version.';
    }

    if($dev_plugin_version !== WPMERGE_VERSION){
        if (version_compare($dev_plugin_version, WPMERGE_VERSION, '<')) {
            throw new wpmerge_exception('prod_dev_plugins_incompatible', $help_txt . ' Please update the Dev WPMerge plugin.');
        }
        elseif (version_compare($dev_plugin_version, WPMERGE_VERSION, '>')) {
            throw new wpmerge_exception('prod_dev_plugins_incompatible', $help_txt . ' Please update the Prod WPMerge plugin.');
        }
        else{
            throw new wpmerge_exception('prod_dev_plugins_incompatible', $help_txt);
        }
    }
    return true;
}

function wpmerge_prod_check_dev_compatibility_may_exit_w_msg($dev_plugin_version=null, $show_simple_error=false){
    if( $dev_plugin_version === null ){
        $dev_plugin_version = isset($_POST['dev_plugin_version']) ? $_POST['dev_plugin_version'] : '';
    }

    try{
        wpmerge_prod_verify_plugin_version_compatability($dev_plugin_version, $show_simple_error);
    }
    catch(wpmerge_exception $e){

        $error = $e->getError();
        $error_msg = $e->getErrorMsg();

        $result = array();
        $result['status'] = 'error_incompatible_plugins_version';
        $result['error'] = $error;
        $result['error_msg'] = $error_msg;
        $result = wpmerge_prepare_response($result);
        echo $result;
        exit();
    }
}

function wpmerge_prod_check_auth_and_dev_compatability_may_exit_w_msg(){

    wpmerge_prod_check_auth_may_exit_w_msg();
    wpmerge_prod_check_dev_compatibility_may_exit_w_msg();
}

add_action('wp_ajax_wpmerge_prod_process_ajax_request', 'wpmerge_prod_process_ajax_request');

add_action('admin_post_nopriv_wpmerge_prod_process_dev_request', 'wpmerge_prod_process_dev_request');

function wpmerge_prod_process_dev_request(){
    $_action = isset($_POST['action']) ? $_POST['action'] : '';
    if($_action !== 'wpmerge_prod_process_dev_request'){
        return false;
    }

    wpmerge_prod_check_auth_and_dev_compatability_may_exit_w_msg();

    if(isset($_POST['wpmerge_action']) && $_POST['wpmerge_action'] === 'wpmerge_prod_purge_wp_cache'){
        $response = array();
        $result = wpmerge_purge_wp_cache();
        $response['status'] = $result ? 'success' : 'error';
        $response['error_msg'] = '';
    }

    if(!empty($response)){
		echo wpmerge_prepare_response($response);
		exit();
	}
}

//add_action('admin_post_wpmerge_prod_db_export', 'wpmerge_prod_db_export');
add_action('admin_post_nopriv_wpmerge_prod_db_export', 'wpmerge_prod_db_export');

function wpmerge_prod_db_export(){
    $_action = isset($_POST['action']) ? $_POST['action'] : '';
    if($_action !== 'wpmerge_prod_db_export'){
        return false;
    }
    // if(!wpmerge_prod_auth($_POST['prod_api_key'])){//commented because it will be handled in wpmerge_common_exim
    //     //echo error properly improve later
    //     return false;
    // }
    include_once(WPMERGE_PATH.'/includes/common_exim.php');

    $request = '';
    if( !empty($_POST['exim_request_json']) ){
        $request = json_decode(stripslashes($_POST['exim_request_json']), true);
    }
    elseif( !empty($_POST['exim_request']) ){//backward compatibility
        $request = $_POST['exim_request'];
    }

    if(!empty($request) && !empty($request['action'])){
        $wpmerge_common_exim_obj = new wpmerge_common_exim($request);
        $wpmerge_common_exim_obj->response();
        exit();
    }
}

function wpmerge_prod_admin_enqueue_scripts(){
	wp_register_script('wpmerge_prod_admin_script', plugin_dir_url( __FILE__ ) . 'js/prod_admin.js', array('jquery'), WPMERGE_VERSION, true);
	wp_enqueue_script('wpmerge_prod_admin_script');
}

add_action('admin_post_nopriv_wpmerge_prod_db_delta_import', 'wpmerge_prod_db_delta_import');
function wpmerge_prod_db_delta_import(){
    // include_once(WPMERGE_PATH.'/includes/common_exim.php');
    // $request = $_POST['exim_request'];
    // if(!empty($request) && !empty($request['action'])){
    //     $wpmerge_common_exim_obj = new wpmerge_common_exim($request);
    //     $wpmerge_common_exim_obj->response();
    // }
    $_post_action = isset($_POST['action']) ? $_POST['action'] : '';
    if($_post_action !== 'wpmerge_prod_db_delta_import'){
        return false;
    }

    wpmerge_prod_check_auth_and_dev_compatability_may_exit_w_msg();

    if(!isset($_POST['wpmerge_action'])){
        return false;
    }
    if($_POST['wpmerge_action'] === 'prepare_bridge'){
        $response = wpmerge_prepare_bridge();
        echo wpmerge_prepare_response($response);
        exit();
    }elseif($_POST['wpmerge_action'] === 'get_server_info'){
        $info = array();
        $info['php']['post_max_size']          = ini_get('post_max_size');
        $info['php']['upload_max_filesize']    = ini_get('upload_max_filesize');
        $info['php']['memory_limit']           = ini_get('memory_limit');
        $info['php']['is_gz_available']        = wpmerge_is_gz_available();
        $info['php']['is_gz_txt_available']    = wpmerge_is_gz_txt_available();
        $info['php']['PHP_EOL']                = PHP_EOL;
        $info['wp']['abspath']                 = ABSPATH;
        $info['wp']['is_multisite']            = is_multisite();

        $info['wp']['is_subdomain_install']    = null;//if multisite and this value comes as null is_subdomain_install() is not exits
        if(is_multisite() && function_exists('is_subdomain_install')){
            $info['wp']['is_subdomain_install']    = is_subdomain_install();
        }

        $info_json = wpmerge_prepare_response($info);
        echo $info_json;
        exit();
    }
    elseif($_POST['wpmerge_action'] === 'receive_db_delta'){
        try{
            //$_FILES['db_delta_file']['tmp_name'],['error'],['size']
            if(/*empty($_POST['db_delta_base64_queries']) || */
            empty($_FILES['db_delta_file']) || 
            !isset($_POST['initial_offset']) || 
            !isset($_POST['offset']) || 
            !isset($_POST['initial_last_query_id']) || 
            !isset($_POST['total']) ) {
                throw new wpmerge_exception('invalid_request');
            }

            if(!empty($_FILES['db_delta_file']['error']) ||
            empty($_FILES['db_delta_file']['size'])
            ) {
                wpmerge_debug::log($_FILES['db_delta_file'], '-----------file upload error----------------');
                throw new wpmerge_exception('invalid_uploaded_file');
            }

            $fh = fopen($_FILES['db_delta_file']['tmp_name'], "r");
            if(!$fh){
                throw new wpmerge_exception('unable_to_open_uploaded_file');
            }

            $appending_last_query_id = $last_query_id = $_POST['initial_last_query_id'];
            $appending_offset = $offset = $_POST['initial_offset'];
            $is_gz_data = isset($_POST['is_gz_data']) ? $_POST['is_gz_data'] : '';
    
            $query_glu = PHP_EOL.'|**wpm**|';//should be same as source, as we deal with zip content
            // $db_delta_base64_queries = $_POST['db_delta_base64_queries'];
            // unset($_POST['db_delta_base64_queries']);
            // $db_delta_base64_queries = explode($query_glu, $db_delta_base64_queries);
            // $db_delta_base64_queries = array_filter($db_delta_base64_queries);

            // if(empty($db_delta_base64_queries)){
            //     throw new wpmerge_exception('invalid_request');
            // }

            $table_name = $GLOBALS['wpdb']->base_prefix .'wpmerge_log_queries';

            if($_POST['initial_offset'] === '0' || $_POST['initial_offset'] === 0){

                $wpmerge_common_db_obj = new wpmerge_common_db();
                $wpmerge_common_db_obj->create_query_log_table();

                $GLOBALS['wpdb']->query("TRUNCATE TABLE `".$table_name."`");
                wpmerge_update_option('delta_import_is_atleast_one_query_is_successful', 0);
            }

            include_once(WPMERGE_PATH.'/includes/common_exim.php');
            $wpmerge_common_exim_obj = new wpmerge_common_exim('');
            $wpmerge_common_exim_obj->resetTempQuery();

            $columns = $GLOBALS['wpdb']->get_results("SHOW COLUMNS IN `$table_name`", OBJECT_K);

            $is_multi_insert_sql_ran_once = false;
            $multi_insert_sql = '';
            $file_buffer_content = '';
            while (($buffer = fgets($fh, 4096)) !== false) {
                $file_buffer_content .= $buffer;
                while(strpos($file_buffer_content, $query_glu) !== false){
                    //found
                    $data_array = explode($query_glu, $file_buffer_content, 2);
                    $delta_query = $data_array[0];
                    $file_buffer_content = $data_array[1];
                    unset($data_array);
                    //$delta_query = trim($delta_query);
                    if(empty($delta_query)){
                        continue;
                    }
                    if($is_gz_data){
                        $delta_query = gzuncompress($delta_query);
                        if($delta_query === false){
                            throw new wpmerge_exception('gzuncompress_failed');
                        }                        
                    }
                    $delta_query = json_decode($delta_query, true);
                    if($delta_query === false){
                        throw new wpmerge_exception('json_decode_failed');
                    }
                    if(!is_array($delta_query)){
                        throw new wpmerge_exception('unexpected_data');
                    }
                    //$insert_result = $GLOBALS['wpdb']->insert($table_name, $delta_query);

                    $multi_insert_prepare_result =  $wpmerge_common_exim_obj->create_multi_insert_statement($table_name, $delta_query, $columns);
                    $run_previous_sql = $multi_insert_prepare_result['run_previous_sql'];
                    $new_sql_part = $multi_insert_prepare_result['sql'];
                    
                    if($run_previous_sql){
                        //$__multi_sql_len = strlen($multi_insert_sql);
                        //$__multi_start_time = microtime(1);
                        $GLOBALS['wpdb']->query($multi_insert_sql);
                        //$__multi_total_time = microtime(1) - $__multi_start_time;
                        //wpmerge_debug::printr(compact('__multi_total_time', '__multi_sql_len', 'offset', 'appending_offset'), '--receive db multi insert---');
                        $offset = $appending_offset;
                        $last_query_id = $appending_last_query_id;
                        $multi_insert_sql = '';
                        $is_multi_insert_sql_ran_once = true;
                    }
                
                    $multi_insert_sql .= $new_sql_part;
                    $appending_offset++;
                    $appending_last_query_id = $delta_query['id'];
                
                    if(wpmerge_is_time_limit_exceeded(10)){
                        break 2;
                    }
                    unset($delta_query);
                }
            }

            //run if pending sql
            if(!$is_multi_insert_sql_ran_once && !empty($multi_insert_sql)){
                //$__multi_sql_len = strlen($multi_insert_sql);
                //$__multi_start_time = microtime(1);
                $GLOBALS['wpdb']->query($multi_insert_sql);
                //$__multi_total_time = microtime(1) - $__multi_start_time;
                //wpmerge_debug::printr(compact('__multi_total_time', '__multi_sql_len', 'offset', 'appending_offset'), '--after loop receive db multi insert---');
                $offset = $appending_offset;
                $last_query_id = $appending_last_query_id;
                $multi_insert_sql = '';
            }

            fclose($fh);
            @unlink($_FILES['db_delta_file']['tmp_name']);

            if($offset == $_POST['initial_offset']){
                throw new wpmerge_exception('file_has_empty_data');
            }


            // foreach($db_delta_base64_queries as $delta_base64_row){
            //     $delta_json_row = base64_decode($delta_base64_row);
            //     if($delta_json_row === false){
            //         throw new wpmerge_exception('base_64_decode_failed');
            //     }
            //     $delta_row = json_decode($delta_json_row, true);
            //     if($delta_row === false){
            //         throw new wpmerge_exception('json_decode_failed');
            //     }
            //     if(!is_array($delta_row)){
            //         throw new wpmerge_exception('unexpected_data');
            //     }

            //     $insert_result = $GLOBALS['wpdb']->insert($table_name, $delta_row);
            //     $offset++;
            //     if(wpmerge_is_time_limit_exceeded(10)){
            //         break;
            //     }
            // }

            $result = array();
            $result['status'] = 'success';
            $result['value'] = array(
                'offset' => $offset,
                'last_query_id' => $last_query_id
            );

        }
        catch(wpmerge_exception $e){
            $error = $e->getError();
            $error_msg = $e->getErrorMsg();

            $result = array();
            $result['status'] = 'error';
            $result['error'] = $error;
            $result['error_msg'] = $error_msg;
            $result['value'] = array(
                'offset' => $offset,
                'last_query_id' => $last_query_id
            );
        }

        $result = wpmerge_prepare_response($result);
        echo $result;
        exit();
    }
    elseif($_POST['wpmerge_action'] === 'initiate_clone_db'){
        try{
            if(empty($_POST['clone_db_state_json_64'])) {
                throw new wpmerge_exception('invalid_request');
            }

            $clone_db_state_json = base64_decode($_POST['clone_db_state_json_64']);
            $clone_db_state = json_decode($clone_db_state_json, true);
            if($clone_db_state === null){
                throw new wpmerge_exception('invalid_json');
            }

            if( empty($clone_db_state['clone_status']) || empty($clone_db_state['clone_table_details']) ){
                throw new wpmerge_exception('invalid_request');
            }

            wpmerge_delete_option('prod_clone_db_state');

            $testing_db_prefix = wpmerge_db_table_prefix::check_and_get_db_table_prefix('same_server_clone_in_prod_testing_db_prefix');//generate once for one overall task, it wont be changing next use
            $tmp_swap_db_prefix = wpmerge_db_table_prefix::check_and_get_db_table_prefix('same_server_clone_in_prod_tmp_swap_db_prefix');//generate once for one overall task, it wont be changing next use

            //clean up db tables in that prefix, just in case//this task might take time if to be deleted db tables are big. NEED IMPROVEMENT multicall delete tables
            if(!empty($testing_db_prefix)){
                wpmerge_delete_tables_with_prefix($testing_db_prefix);
            }
            if(!empty($tmp_swap_db_prefix)){
                wpmerge_delete_tables_with_prefix($tmp_swap_db_prefix);
            }

            $wp_db_prefix = $GLOBALS['wpdb']->base_prefix;
            $clone_db_state['testing_db_prefix'] = $testing_db_prefix;
            $clone_db_state['tmp_swap_db_prefix'] = $tmp_swap_db_prefix;
            $clone_db_state['wp_db_prefix'] = $wp_db_prefix;

            $clone_db_state['clone_status'] = 'initiated';

            wpmerge_update_option('prod_clone_db_state', $clone_db_state);
            
            $result = array();
            $result['status'] = 'success';
            $result['clone_db_state'] = $clone_db_state;
        }
        catch(wpmerge_exception $e){
            $error = $e->getError();
            $error_msg = $e->getErrorMsg();

            $result = array();
            $result['status'] = 'error';
            $result['error'] = $error;
            $result['error_msg'] = $error_msg;
            
        }

        $result = wpmerge_prepare_response($result);
        echo $result;
        exit();

    }
    elseif($_POST['wpmerge_action'] === 'continue_clone_db'){
        try{
            $clone_db_state = wpmerge_get_option('prod_clone_db_state');

            if($clone_db_state['clone_status'] == 'completed'){

            }
            elseif($clone_db_state['clone_status'] == 'error'){
                
            }
            elseif($clone_db_state['clone_status'] == 'paused' || $clone_db_state['clone_status'] == 'initiated'){
                include_once(WPMERGE_PATH . '/includes/same_server_db_clone.php');

                $GLOBALS['wpdb']->query('SET FOREIGN_KEY_CHECKS = 0');
                $GLOBALS['wpdb']->query('SET unique_checks = 0');//to as we using same server clone, this will speed up

                wpmerge_prod_is_db_cloned($clone_db_state);//this will also validate the data, if fails throws
                foreach($clone_db_state['clone_table_details'] as $_key => $clone_table_details){

                    if(wpmerge_is_time_limit_exceeded()){
                        break;
                    }

                    if($clone_table_details['structure'] == 'completed' && $clone_table_details['data_eof'] === true){
                        continue;
                    }

                    $table = $clone_table_details['name'];
                    $new_table = wpmerge_replace_prefix($clone_db_state['wp_db_prefix'], $clone_db_state['testing_db_prefix'], $table);

                    $same_server_db_clone_obj = new same_server_db_clone();

                    if($clone_table_details['structure'] == 'pending'){

                        if($clone_table_details['table_type'] == 'BASE TABLE'){
                            $is_cloned = $same_server_db_clone_obj->clone_table_structure($table, $new_table);
                        }
                        elseif($clone_table_details['table_type'] == 'VIEW'){
                            $is_cloned = $same_server_db_clone_obj->clone_view_structure($table, $new_table, $clone_db_state['wp_db_prefix'], $clone_db_state['testing_db_prefix']);
                        }
                        if($is_cloned === true){
                            $clone_db_state['clone_table_details'][$_key]['structure'] = 'completed';
                            wpmerge_update_option('prod_clone_db_state', $clone_db_state);
                        }
                    }

                    if(wpmerge_is_time_limit_exceeded()){
                        break;
                    }

                    if($clone_table_details['data_eof'] === false){
                        $limit = 100000;
                        $offset = $clone_table_details['offset'];
                        $misc_options = array();
                        $misc_options['total_rows'] = $clone_table_details['total_rows'];

                        $data_clone_status = $same_server_db_clone_obj->clone_table_content($table, $new_table, $limit, $offset, $misc_options);

                        $clone_db_state['clone_table_details'][$_key]['data_eof'] = $data_clone_status['eof'];
                        $clone_db_state['clone_table_details'][$_key]['offset'] = $data_clone_status['offset'];

                        if($data_clone_status['eof'] === true){
                            $clone_db_state['clone_table_details'][$_key]['offset'] = 0;
                        }
                        wpmerge_update_option('prod_clone_db_state', $clone_db_state);
                    }

                    if($clone_db_state['clone_table_details'][$_key]['structure'] == 'completed' && $clone_db_state['clone_table_details'][$_key]['data_eof'] === true && $clone_db_state['clone_table_details'][$_key]['status'] == 'pending'){
                        $clone_db_state['clone_table_details'][$_key]['status'] = 'completed';
                        wpmerge_update_option('prod_clone_db_state', $clone_db_state);
                    }


                }
                if(wpmerge_prod_is_db_cloned($clone_db_state)){
                    $clone_db_state['clone_status'] = 'completed';
                }
                else{
                    $clone_db_state['clone_status'] = 'paused';
                }
                wpmerge_update_option('prod_clone_db_state', $clone_db_state);
            }            

            $result = array();
            $result['status'] = 'success';
            $result['clone_db_state'] = $clone_db_state;
        }
        catch(wpmerge_exception $e){
            $error = $e->getError();
            $error_msg = $e->getErrorMsg();

            $result = array();
            $result['status'] = 'error';
            $result['error'] = $error;
            $result['error_msg'] = $error_msg;
            
        }

        $result = wpmerge_prepare_response($result);
        echo $result;
        exit();
    }
    elseif($_POST['wpmerge_action'] === 'run_db_delta_queries'){
        try{
            if(empty($_POST['dev_db_prefix']) || 
            empty($_POST['testing_db_prefix']) || 
            !isset($_POST['offset']) || 
            !isset($_POST['last_query_id'])  || 
            !isset($_POST['total']) ||
            ( empty($_POST['old_tables_list_json']) && empty($_POST['old_tables_list_str']) )
            ) {
                throw new wpmerge_exception('invalid_request');
            }

            if(!empty($_POST['old_tables_list_json'])){
                $old_tables_list = json_decode(stripslashes($_POST['old_tables_list_json']), true);
            }
            elseif(!empty($_POST['old_tables_list_str'])){//backward compatibility
                $old_tables_list = explode("**|tl|**", trim($_POST['old_tables_list_str']));
            }

            if(count($old_tables_list) < 8){//default WP tables should is around 12, checking 8 here
                throw new wpmerge_exception('invalid_request');
            }

            $old_db_prefix = trim($_POST['dev_db_prefix']);
           // $new_db_prefix = $GLOBALS['wpdb']->base_prefix;
            $new_db_prefix = trim($_POST['testing_db_prefix']);


            $new_tables_list = wpmerge_replace_prefix_for_table_names_list($old_db_prefix, $new_db_prefix, $old_tables_list);

            if(empty($new_tables_list)){
                throw new wpmerge_exception('invalid_new_tables_list');
            }

            $options = array(
                'offset' => is_numeric($_POST['offset'])? (int)$_POST['offset'] : '',
                'last_query_id' => is_numeric($_POST['last_query_id'])? (int)$_POST['last_query_id'] : '',
                'total' => is_numeric($_POST['total'])? (int)$_POST['total'] : '',
                'old_db_prefix' => $old_db_prefix,
                'new_db_prefix' => $new_db_prefix,
                'old_tables_list' => $old_tables_list,
                'new_tables_list' => $new_tables_list

            );
            require_once(WPMERGE_PATH . '/includes/common_deploy.php');
            $common_deploy_obj = new wpmerge_common_deploy();

            $res = $common_deploy_obj->do_apply_changes_for_prod($options);
            
            $result = array();
            $result['status'] = 'success';
            $result['value'] = array(
                'offset' => $res['offset'],
                'last_query_id' => $res['last_query_id'],
                'eof' => $res['eof']
            );
        }
        catch(wpmerge_exception $e){
            $error = $e->getError();
            $error_msg = $e->getErrorMsg();

            $result = array();
            $result['status'] = 'error';
            $result['error'] = $error;
            $result['error_msg'] = $error_msg;
            $result['is_atleast_one_query_is_successful'] = wpmerge_get_option('delta_import_is_atleast_one_query_is_successful');
            
        }
        wpmerge_debug::printr(wpmerge_select_by_memory_limit::get_all_perform_data(), 'all_perform_data');

        $result = wpmerge_prepare_response($result);
        echo $result;
        exit();
    }
    elseif($_POST['wpmerge_action'] === 'replace_db_links'){

        try{
            $___site_url = get_option('siteurl');
            $___home_url = get_option('home');

            if(
                ( empty($_POST['replace_tables_state_json']) && empty($_POST['replace_tables_state']) )  || 
            empty($_POST['find_replace_data']) || 
            empty($_POST['dev_db_prefix']) || 
            !isset($_POST['is_multisite_subdomain_install'])) {
                throw new wpmerge_exception('invalid_request');
            }
            
            if(!empty($_POST['replace_tables_state_json'])){
                $replace_tables_state =  json_decode(stripslashes($_POST['replace_tables_state_json']), true);

            }elseif(!empty($_POST['replace_tables_state'])){//backward compatibility
                $replace_tables_state =  $_POST['replace_tables_state'];
            }

            if(empty($replace_tables_state)){
                throw new wpmerge_exception('invalid_request');
            }

            $is_multisite_subdomain_install = (bool)$_POST['is_multisite_subdomain_install'];

            $old_db_prefix = trim($_POST['dev_db_prefix']);
            $new_db_prefix = $GLOBALS['wpdb']->base_prefix;

            $find_replace_data = $_POST['find_replace_data'];
            $find_replace_data['old_url'] = untrailingslashit($find_replace_data['old_url']);
            $find_replace_data['old_file_path'] = untrailingslashit($find_replace_data['old_file_path']);
            $find_replace_data['new_url'] = untrailingslashit(get_site_url());
            $find_replace_data['new_file_path'] = untrailingslashit(ABSPATH);

            if(
                empty($find_replace_data['old_url']) || 
                empty($find_replace_data['new_url']) ||
                empty($find_replace_data['old_file_path']) ||
                empty($find_replace_data['new_file_path'])        
            ){
                throw new wpmerge_exception('find_replace_data_missing');
            }

            // following commented as this data come as json in $_POST now
            // foreach($replace_tables_state as $key =>  &$table){
            //     //as coming via $_POST it changes false to 0, 1 to '1'(string 1)
            //     $table['eof'] = (bool)$table['eof'];
            //     $table['current_offset'] = (int)$table['current_offset'];
            // }

            $wp_task_completed_table_count = 0;
            $whitelist = array('name', 'current_offset');
            foreach($replace_tables_state as $key =>  &$table){
    
                if( $table['status'] === 'completed' ){
                    $wp_task_completed_table_count++;
                    continue;
                }
                
                if( in_array($table['status'], array('pending', 'paused')) ){
                    while($table['eof'] === false && !wpmerge_is_time_limit_exceeded()){
                    
                        $table_replace_state = $table;//avoid passing by reference here, $table is reference
                        $table_replace_state_args = array_intersect_key( $table_replace_state, array_flip( $whitelist ) );
    
                        include_once(WPMERGE_PATH.'/includes/common_replace_db_links.php');
    
                        $replace_db_links_obj = new wpmerge_replace_db_links();
    
                        $response = $replace_db_links_obj->replace_uri($find_replace_data['old_url'],$find_replace_data['new_url'], $find_replace_data['old_file_path'], $find_replace_data['new_file_path'], '', $table_replace_state_args, $is_multisite_subdomain_install);
    
                        if(!isset($response['eof']) || !is_bool($response['eof'])){
                            throw new wpmerge_exception('invalid_response');
                        }
    
                        if($response['eof'] === true){
                            $table['status'] = 'completed';
                            $table['eof'] = true;
                            $table['current_offset'] = 0;
                        }
                        else{
                            $table['status'] = 'paused';
                            $table['eof'] = false;
                            $table['current_offset'] = $response['current_offset'];
                        }
                    }
                }
                if(wpmerge_is_time_limit_exceeded()){
                    break;
                }
            }
            wpmerge_debug::printr(wpmerge_select_by_memory_limit::get_all_perform_data(), 'all_perform_data');

            $result = array();
            $result['status'] = 'success';
            $result['replace_tables_state'] = $replace_tables_state;
        }
        catch(wpmerge_exception $e){
            $error = $e->getError();
            $error_msg = $e->getErrorMsg();

            $result = array();
            $result['status'] = 'error';
            $result['error'] = $error;
            $result['error_msg'] = $error_msg;
            $result['replace_tables_state'] = $replace_tables_state;
        }
        if(1){//for safety reason - as the plugin is not using bridge.
            update_option('siteurl', $___site_url);
            update_option('home', $___home_url);
        }

        $result = wpmerge_prepare_response($result);
        echo $result;
        exit();
    }
    elseif($_POST['wpmerge_action'] === 'finalise_tables'){
        try{
            if(empty($_POST['testing_db_prefix']) ||
            empty($_POST['tmp_swap_db_prefix']) ||
            empty($_POST['wp_db_prefix'])  ||
            empty($_POST['dev_db_prefix'])) {
                throw new wpmerge_exception('invalid_request');
            }

            $testing_db_prefix = $_POST['testing_db_prefix'];
            $tmp_swap_db_prefix = $_POST['tmp_swap_db_prefix'];
            $wp_db_prefix = $_POST['wp_db_prefix'];
            $dev_db_prefix = $_POST['dev_db_prefix'];

            include_once(WPMERGE_PATH.'/includes/swap_tables.php');
            $wpmerge_swap_tables_obj = new wpmerge_swap_tables();

            $wpmerge_swap_tables_obj->swap_tables($wp_db_prefix, $testing_db_prefix, $tmp_swap_db_prefix);

            $wpmerge_swap_tables_obj->delete_tmp_swap_tables($tmp_swap_db_prefix);
            
            $result = array();
            $result['status'] = 'success';
        }
        catch(wpmerge_exception $e){
            $error = $e->getError();
            $error_msg = $e->getErrorMsg();

            $result = array();
            $result['status'] = 'error';
            $result['error'] = $error;
            $result['error_msg'] = $error_msg;
            
        }

        $result = wpmerge_prepare_response($result);
        echo $result;
        exit();

    }
    elseif($_POST['wpmerge_action'] === 'run_db_final_modifications'){

        try{
            if(empty($_POST['dev_site_url']) || 
            empty($_POST['dev_db_prefix']) ) {
                throw new wpmerge_exception('invalid_request');
            }
            $old_site_url = trim($_POST['dev_site_url']);
            $old_db_prefix = trim($_POST['dev_db_prefix']);

            $new_db_prefix = $GLOBALS['wpdb']->base_prefix;
            $new_site_url = untrailingslashit(get_site_url());


            //-------------following code to fix user data prefixes ---->
            wpmerge_run_db_final_modifications($old_db_prefix);
            //----ENDS---------following code to fix user data prefixes ---->


            //for multisite - currently no if is checked. Currently no harms running to all sites.
            include_once(WPMERGE_PATH.'/includes/common_replace_db_links.php');
            $replace_db_links_obj = new wpmerge_replace_db_links();
            $replace_db_links_obj->multi_site_db_changes($new_db_prefix, $new_site_url, $old_site_url);
            
            $result = array();
            $result['status'] = 'success';

        }
        catch(wpmerge_exception $e){
            $error = $e->getError();
            $error_msg = $e->getErrorMsg();

            $result = array();
            $result['status'] = 'error';
            $result['error'] = $error;
            $result['error_msg'] = $error_msg;
            
        }

        $result = wpmerge_prepare_response($result);
        echo $result;
        exit();
    }
    else if($_POST['wpmerge_action'] === 'delete_prod_bridge'){
        wpmerge_delete_bridge();
        echo wpmerge_prepare_response(array('status' => 'success'));
        exit();
    }
    elseif($_POST['wpmerge_action'] === 'fix_db_serialization'){

        try{
            $___site_url = get_option('siteurl');
            $___home_url = get_option('home');

            if(
                ( empty($_POST['tables_fix_state_json']) && empty($_POST['tables_fix_state_json']) ) ) {
                throw new wpmerge_exception('invalid_request');
            }
            
            if(!empty($_POST['tables_fix_state_json'])){
                $tables_fix_state =  json_decode(stripslashes($_POST['tables_fix_state_json']), true);
            }

            if(empty($tables_fix_state)){
                throw new wpmerge_exception('invalid_request');
            }

            $wp_task_completed_table_count = 0;
            $whitelist = array('name', 'current_offset');
            foreach($tables_fix_state as $key =>  &$table){
    
                if( $table['status'] === 'completed' ){
                    $wp_task_completed_table_count++;
                    continue;
                }
                
                if( in_array($table['status'], array('pending', 'paused')) ){
                    while($table['eof'] === false && !wpmerge_is_time_limit_exceeded()){
                    
                        $table_fix_state = $table;//avoid passing by reference here, $table is reference
                        $table_fix_state_args = array_intersect_key( $table_fix_state, array_flip( $whitelist ) );

                        include_once(WPMERGE_PATH.'/includes/common_fix_db_serialization.php');

                        $fix_db_serialization_obj = new wpmerge_fix_db_serialization();
    
                        $response = $fix_db_serialization_obj->fix_table( $table_fix_state_args);
    
                        if(!isset($response['eof']) || !is_bool($response['eof'])){
                            throw new wpmerge_exception('invalid_response');
                        }
    
                        if($response['eof'] === true){
                            $table['status'] = 'completed';
                            $table['eof'] = true;
                            $table['current_offset'] = 0;
                        }
                        else{
                            $table['status'] = 'paused';
                            $table['eof'] = false;
                            $table['current_offset'] = $response['current_offset'];
                        }
                    }
                }
                if(wpmerge_is_time_limit_exceeded()){
                    break;
                }
            }
            wpmerge_debug::printr(wpmerge_select_by_memory_limit::get_all_perform_data(), 'all_perform_data');

            $result = array();
            $result['status'] = 'success';
            $result['tables_fix_state'] = $tables_fix_state;
        }
        catch(wpmerge_exception $e){
            $error = $e->getError();
            $error_msg = $e->getErrorMsg();

            $result = array();
            $result['status'] = 'error';
            $result['error'] = $error;
            $result['error_msg'] = $error_msg;
            $result['tables_fix_state'] = $tables_fix_state;
        }

        $result = wpmerge_prepare_response($result);
        echo $result;
        exit();
    }
    elseif($_POST['wpmerge_action'] === 'disable_maintenance_mode'){
        wpmerge_maintenance_mode_disable();
        
        $result = array();
        $result['status'] = 'success';
        $result = wpmerge_prepare_response($result);
        echo $result;
        exit();
    }
}

function wpmerge_prod_is_db_cloned($clone_db_state){

    if(empty($clone_db_state['clone_table_details'])){
        throw new wpmerge_exception('clone_table_details_invalid_data');
    }
    $total_tables = count($clone_db_state['clone_table_details']);

    if(empty($total_tables)){
        throw new wpmerge_exception('clone_table_details_invalid_tables_count');
    }

    $cloned_tables_count = 0;

    foreach($clone_db_state['clone_table_details'] as $_key => $clone_table_details){

        if($clone_table_details['structure'] == 'completed' && $clone_table_details['data_eof'] === true){
            $cloned_tables_count++;
        }
        else{
            return false;
        }
    }
    if($total_tables === $cloned_tables_count){
        return true;
    }
    return false;
}

//following wpmerge_prod_fall_back_endpoint() changes how POST process works from dev to prod site till v1.0.7, even it calls same admin-post.php, now it works through "init" hook instead of normal flow
function wpmerge_prod_fall_back_endpoint(){
    //following commented to work, with exisiting connection url
    // if(basename($_SERVER['SCRIPT_FILENAME']) === "admin-post.php"){
    //     return;
    // }//
    $allowed_actions = array(
        'wpmerge_prod_connect_site',
        'wpmerge_prod_db_export',
        'wpmerge_prod_db_delta_import',
        'wpmerge_prod_process_dev_request'
    );
    $action = empty( $_POST['action'] ) ? '' : $_POST['action'];
    
    if ( !empty( $action ) && in_array($action, $allowed_actions, true) ) {
        do_action( "admin_post_nopriv_{$action}" );//this function should call exit at the end
        exit();
    }
}

add_action('init', 'wpmerge_prod_fall_back_endpoint');
