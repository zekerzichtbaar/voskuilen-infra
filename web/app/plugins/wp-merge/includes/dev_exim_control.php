<?php
/**
 * WPMerge
 * Copyright (c) 2018 Revmakx LLC
 * revmakx.com
 */

if(!defined('ABSPATH')){ exit; }

class wpmerge_dev_exim_control{
    private $remote_url;
    private $remote_wp_url;

    private $valid_statuses = array('pending', 'completed', 'error', 'paused', 'running', 'retry');

    private $overall_task_details = array(
        'import_prod_db' => array(
            'db_state_slug' => 'import_prod_db_state'
        ),
        'export_dev_db_delta_2_prod' => array(
            'db_state_slug' => 'export_dev_db_delta_2_prod_state'
        ),
        'export_changed_files_in_dev' => array(
            'db_state_slug' => 'export_changed_files_in_dev_state'
        ),
        'apply_changes_for_prod_in_dev' => array(
            'db_state_slug' => 'apply_changes_for_prod_in_dev_state'
        ),
        'apply_changes_for_dev_in_dev' => array(
            'db_state_slug' => 'apply_changes_for_dev_in_dev_state'
        ),
        'do_db_modification_in_dev' => array(
            'db_state_slug' => 'do_db_modification_in_dev_state'
        ),
        'decode_encoded_logged_queries' => array(
            'db_state_slug' => 'decode_encoded_logged_queries_state'
        ),
        'remove_decoded_logged_queries' => array(
            'db_state_slug' => 'remove_decoded_logged_queries_state'
        ),
        'fix_db_serialization_in_dev' => array(
            'db_state_slug' => 'fix_db_serialization_in_dev_state'
        ),
        'fix_db_serialization_in_prod' => array(
            'db_state_slug' => 'fix_db_serialization_in_prod_state'
        )
    );

    private $import_prod_db_state_default = array(
        'overall_task' => 'import_prod_db',
        'overall_status' => 'pending',
        'overall_error'=> null,
        'overall_error_msg'=> null,
        'time_added' => null,
        'time_updated' => null,
        'tasks'=> array(
            'list_db_tables' => array('status' => 'pending'),
            'backup_db' => array('status' => 'pending'),
            'compress_db' => array('status' => 'pending'),
            'download_db' => array('status' => 'pending'),
            'un_compress_db' => array('status' => 'pending'),
            'pre_run_queries' => array('status' => 'pending'),
            'run_queries' => array('status' => 'pending'),
            'get_server_info' => array('status' => 'pending'),
            'replace_db_links' => array('status' => 'pending'),
            'post_run_queries' => array('status' => 'pending')
        ),
        'data' => array(
            'site_url' => '',
            'site_api_url'=> '',
            'src_site_db_table_prefix' => ''
        )
    );

    private $export_dev_db_delta_2_prod_state_default = array(
        'overall_task' => 'export_dev_db_delta_2_prod',
        'overall_status' => 'pending',
        'overall_error'=> null,
        'overall_error_msg'=> null,
        'time_added' => null,
        'time_updated' => null,
        'tasks'=> array(
            'prepare_prod_bridge' => array('status' => 'pending'),
            'get_server_info' => array('status' => 'pending'),
            'push_db_delta' => array('status' => 'pending'),
            'list_db_tables' => array('status' => 'pending'),
            'remote_clone_db' => array('status' => 'pending'),//maintainance mode need before this //remote_clone_db should run after push_db_delta for less down time and latest data
            'remote_run_delta_queries' => array('status' => 'pending'),
            'remote_replace_db_links' => array('status' => 'pending'),
            'remote_finalise_tables' => array('status' => 'pending'),
            'remote_run_db_final_modifications' => array('status' => 'pending'),
            'delete_prod_bridge' => array('status' => 'pending'),
        ),
        'data' => array(
            'site_url' => '',
            'site_api_url'=> '',
            'src_site_db_table_prefix' => ''
        )
    );

    private $export_changed_files_in_dev_state_default = array(
        'overall_task' => 'export_changed_files_in_dev',
        'overall_status' => 'pending',
        'overall_error'=> null,
        'overall_error_msg'=> null,
        'time_added' => null,
        'time_updated' => null,
        'tasks'=> array(
            'get_all_changed_files' => array('status' => 'pending'),
            'zip_changed_files' => array('status' => 'pending')
        ),
        'data' => array(
        )
    );

    private $apply_changes_for_prod_in_dev_state_default = array(
        'overall_task' => 'apply_changes_for_prod_in_dev',
        'overall_status' => 'pending',
        'overall_error'=> null,
        'overall_error_msg'=> null,
        'time_added' => null,
        'time_updated' => null,
        'tasks'=> array(
            'do_apply_changes_for_prod_in_dev' => array('status' => 'pending')/*,
            'delete_dev_bridge' => array('status' => 'pending')*/
        ),
        'data' => array(
        )
    );

    private $apply_changes_for_dev_in_dev_state_default = array(
        'overall_task' => 'apply_changes_for_dev_in_dev',
        'overall_status' => 'pending',
        'overall_error'=> null,
        'overall_error_msg'=> null,
        'time_added' => null,
        'time_updated' => null,
        'tasks'=> array(
            'apply_changes_for_dev_in_dev_pre_check' => array('status' => 'pending'),
            'do_db_modification' => array('status' => 'pending'),
            'do_apply_changes_for_dev_in_dev' => array('status' => 'pending')/*,
            'delete_dev_bridge' => array('status' => 'pending')*/
        ),
        'data' => array(
        )
    );

    private $do_db_modification_in_dev_state_default = array(
        'overall_task' => 'do_db_modification_in_dev',
        'overall_status' => 'pending',
        'overall_error'=> null,
        'overall_error_msg'=> null,
        'time_added' => null,
        'time_updated' => null,
        'tasks'=> array(
            'do_db_modification' => array('status' => 'pending'),
        ),
        'data' => array(
        )
    );

    private $decode_encoded_logged_queries_state_default = array(
        'overall_task' => 'decode_encoded_logged_queries',
        'overall_status' => 'pending',
        'overall_error'=> null,
        'overall_error_msg'=> null,
        'time_added' => null,
        'time_updated' => null,
        'tasks'=> array(
            'do_decode_encoded_logged_queries' => array('status' => 'pending'),
        ),
        'data' => array(
        )
    );

    private $remove_decoded_logged_queries_state_default = array(
        'overall_task' => 'remove_decoded_logged_queries',
        'overall_status' => 'pending',
        'overall_error'=> null,
        'overall_error_msg'=> null,
        'time_added' => null,
        'time_updated' => null,
        'tasks'=> array(
            'do_remove_decoded_logged_queries' => array('status' => 'pending'),
        ),
        'data' => array(
        )
    );

    private $fix_db_serialization_in_dev_state_default = array(
        'overall_task' => 'fix_db_serialization_in_dev',
        'overall_status' => 'pending',
        'overall_error'=> null,
        'overall_error_msg'=> null,
        'time_added' => null,
        'time_updated' => null,
        'tasks'=> array(
            'list_dev_db_tables' => array('status' => 'pending'),
            'do_fix_db_serialization_in_dev' => array('status' => 'pending'),
        ),
        'data' => array(
        )
    );

    private $fix_db_serialization_in_prod_state_default = array(
        'overall_task' => 'fix_db_serialization_in_prod',
        'overall_status' => 'pending',
        'overall_error'=> null,
        'overall_error_msg'=> null,
        'time_added' => null,
        'time_updated' => null,
        'tasks'=> array(
            'list_db_tables' => array('status' => 'pending'),
            'remote_fix_db_serialization' => array('status' => 'pending'),
        ),
        'data' => array(
        )
    );

    private $state;
    private $current_overall_task;
    private $download_list = array();

    public function __construct(){

            add_action('wp_ajax_wpmerge_exim_initiate_overall_task', array($this, 'initiate_overall_task'));
            add_action('wp_ajax_wpmerge_exim_continue_overall_task', array($this, 'continue_overall_task'));
            add_action('wp_ajax_wpmerge_exim_get_default_state_for_dummy', array($this, 'get_default_state_for_dummy'));
                       
    }
    public function do_tasks(){

        if(empty($this->state) || empty($this->state['tasks'])){

            return false;
        }
        if(in_array($this->state['overall_status'], array('completed', 'error'))){

            return false;
        }
        end($this->state['tasks']);
        $last_task = key($this->state['tasks']); 
        reset($this->state['tasks']);
        foreach($this->state['tasks'] as $task => $task_details){
            if($task_details['status'] == 'error'){

                return false;//improve 
            }
            if($task_details['status'] == 'completed'){
				continue;
			}
            if($this->can_run_task($task)){
                $this->update_task_status($task, 'running');
                switch($task){

                    case 'list_db_tables':
                        $this->remote_db_list_tables();
                        break;

                    case 'backup_db':
                        $this->remote_db_backup_tables();
                        break;

                    case 'compress_db':
                        $this->remote_compress_db_backup();
                        break;

                    case 'download_db':
                        $this->download_remote_db_backup();
                        break;

                    case 'un_compress_db':
                        $this->un_compress_db_backup();
                        break;

                    case 'pre_run_queries':
                        $this->pre_run_queries();
                        break;

                    case 'run_queries':
                        $this->run_queries_from_downloaded_db();
                        break;

                    case 'replace_db_links':
                        $this->replace_db_links();
                        break;

                    case 'post_run_queries':
                        $this->post_run_queries();
                        break;
                    
                    case 'prepare_prod_bridge':
                        $this->prepare_prod_bridge();
                        break;

                    case 'delete_dev_bridge':
                        $this->delete_dev_bridge();
                        break;

                    case 'get_server_info':
                        $this->get_server_info();
                        break;

                    case 'push_db_delta':
                        $this->push_db_delta();
                        break;

                    case 'remote_clone_db':
                        $this->remote_clone_db();
                        break;

                    case 'remote_run_delta_queries':
                        $this->remote_run_delta_queries();
                        break;
                    
                    case 'remote_replace_db_links':
                        $this->remote_replace_db_links();
                        break;
                        
                    case 'remote_finalise_tables':
                        $this->remote_finalise_tables();
                        break;

                    case 'remote_run_db_final_modifications':
                        $this->remote_run_db_final_modifications();
                        break;

                    case 'delete_prod_bridge':
                        $this->delete_prod_bridge();
                        break;
                    
                    case 'get_all_files_meta':
                        $this->get_all_files_meta();
                        break;

                    case 'filter_changed_files':
                        $this->filter_changed_files();
                        break;

                    case 'get_all_changed_files':
                        $this->get_all_changed_files();
                        break;

                    case 'zip_changed_files':
                        $this->zip_changed_files();
                        break;

                    case 'do_apply_changes_for_prod_in_dev':
                        $this->do_apply_changes_for_prod_in_dev();
                        break;

                    case 'apply_changes_for_dev_in_dev_pre_check':
                        $this->apply_changes_for_dev_in_dev_pre_check();
                        break;

                    case 'do_db_modification':
                        $this->do_db_modification();
                        break;

                    case 'do_apply_changes_for_dev_in_dev':
                        $this->do_apply_changes_for_dev_in_dev();
                        break;

                    case 'do_decode_encoded_logged_queries':
                        $this->do_decode_encoded_logged_queries();
                        break;

                    case 'do_remove_decoded_logged_queries':
                        $this->do_remove_decoded_logged_queries();
                        break;

                    case 'list_dev_db_tables':
                        $this->db_list_tables();
                        break;

                    case 'do_fix_db_serialization_in_dev':
                        $this->do_fix_db_serialization_in_dev();
                        break;

                    case 'remote_fix_db_serialization':
                        $this->remote_fix_db_serialization();
                        break;
                }
            }
            // if(wpmerge_is_time_limit_exceeded()){
            //     return false;//improve 
            // }
            if(
                $last_task === $task && 
                $this->state['tasks'][$task]['status'] == 'completed' && 
                !in_array($this->state['overall_status'], array('completed', 'error'))
                ){//assuming all the previous task are completed and validated above 
				$this->update_overall_task_status('completed');
			}
            break;//loop only runs onces
        }
    }

    private function add_progress_for_a_task($task){
        $progress_status = array();
        switch($task){

            // case 'list_db_tables':
            //     $this->remote_db_list_tables();
            //     break;

            case 'backup_db':
                $progress_status = $this->remote_db_backup_tables__get_progress_status();
                break;

            // case 'compress_db':
            //     $this->remote_compress_db_backup();
            //     break;

            // case 'download_db':
            //     $this->download_remote_db_backup();
            //     break;

            // case 'un_compress_db':
            //     $this->un_compress_db_backup();
            //     break;

            // case 'pre_run_queries':
            //     $this->pre_run_queries();
            //     break;

            case 'run_queries':
                $progress_status = $this->run_queries_from_downloaded_db__get_progress_status();
                break;

            case 'replace_db_links':
                $progress_status = $this->replace_db_links__get_progress_status();
                break;

            // case 'post_run_queries':
            //     $this->post_run_queries();
            //     break;
            
            // case 'prepare_prod_bridge':
            //     $this->prepare_prod_bridge();
            //     break;

            // case 'delete_dev_bridge':
            //     $this->delete_dev_bridge();
            //     break;

            // case 'get_server_info':
            //     $this->get_server_info();
            //     break;

            case 'push_db_delta':
                $progress_status = $this->push_db_delta__get_progress_status();
                break;

            case 'remote_clone_db':
                $progress_status = $this->remote_clone_db__get_progress_status();
                break;

            case 'remote_run_delta_queries':
                $progress_status = $this->remote_run_delta_queries__get_progress_status();
                break;
            
            case 'remote_replace_db_links':
                $progress_status = $this->remote_replace_db_links__get_progress_status();
                break;

            // case 'remote_finalise_tables':
            //     $this->remote_finalise_tables();
            //     break;

            // case 'delete_prod_bridge':
            //     $this->delete_prod_bridge();
            //     break;
            
            // case 'get_all_files_meta':
            //     $this->get_all_files_meta();
            //     break;

            // case 'filter_changed_files':
            //     $this->filter_changed_files();
            //     break;

            // case 'get_all_changed_files':
            //     $this->get_all_changed_files();
            //     break;

            // case 'zip_changed_files':
            //     $this->zip_changed_files();
            //     break;

            case 'do_apply_changes_for_prod_in_dev':
                $progress_status = $this->do_apply_changes_for_prod_in_dev__get_progress_status();
                break;

            // case 'apply_changes_for_dev_in_dev_pre_check':
            //     $this->apply_changes_for_dev_in_dev_pre_check();
            //     break;

            // case 'do_db_modification':
            //     $this->do_db_modification();
            //     break;

            case 'do_apply_changes_for_dev_in_dev':
                $progress_status = $this->do_apply_changes_for_dev_in_dev__get_progress_status();
                break;

            case 'do_decode_encoded_logged_queries':
                $progress_status = $this->do_decode_encoded_logged_queries__get_progress_status();
                break;

            // case 'do_remove_decoded_logged_queries':
            //     $this->do_remove_decoded_logged_queries();
            //     break;

            case 'do_fix_db_serialization_in_dev':
                $progress_status = $this->do_fix_db_serialization_in_dev__get_progress_status();
                break;

            case 'remote_fix_db_serialization':
                $progress_status = $this->remote_fix_db_serialization__get_progress_status();
                break;

        }
        return !empty($progress_status) ? $progress_status : false;
    }

    private function run_task(){

    }

    private function load_prod_urls(){
        $prod_admin_url = wpmerge_dev_get_prod_admin_url();
        $prod_site_url = wpmerge_dev_get_prod_site_url();

        if(empty($prod_admin_url) || empty($prod_site_url)){
            $prod_connect = wpmerge_get_option('prod_connect');
            if(empty($prod_connect)){
                throw new wpmerge_exception('invalid_connect_str_please_reconnect_prod_site');
            }
            if(empty($prod_site_url)){
                throw new wpmerge_exception('invalid_prod_site_url');
            }
            elseif(empty($prod_admin_url)){
                throw new wpmerge_exception('invalid_prod_admin_url');
            }
        }

        //$default_remote_url = $prod_admin_url.'admin-post.php';
        $default_remote_url = $prod_site_url.'wp-load.php';

        $bridge_url = '';
        if(isset($this->state['data']['prod_bridge_info']['bridge_path'])){
            $bridge_url = $this->state['data']['prod_bridge_info']['bridge_path'];
        }

        $this->remote_url = empty($bridge_url) ? $default_remote_url : $bridge_url;

        $this->remote_wp_url = $prod_site_url;
    }

    private function remote_db_list_tables(){
        try{
            $this->load_prod_urls();

            $exim_request = array('action' => 'listTables');
            $body = array(
                'action' => 'wpmerge_prod_db_export',
                'exim_request' => $exim_request,
                'prod_api_key' => wpmerge_dev_get_prod_api_key(),
                'dev_plugin_version' => WPMERGE_VERSION
            );
            $http_args = array(
                'method' => "POST",
                'timeout' => 10,//10 - will be enough to list the db tables
                'body' => $body
            );
            $response = wpmerge_do_call($this->remote_url, $http_args);
            $response_data = wpmerge_get_response_from_json($response);

            if(!isset($response_data['code'])){
                throw new wpmerge_exception('invalid_response_code');
            }
            if($response_data['code'] < 200){
                $error_msg = empty($response_data['message']) ? 'empty_error_msg' : $response_data['message'];
                throw new wpmerge_exception('db_backup_error', $error_msg);
            }
            if($response_data['code'] >= 200){//this is not http code, this is application status code
                //all ok
                if(!isset($response_data['value']) || !isset($response_data['value']['tables']) || empty($response_data['value']['tables'])){

                    return false;//throw imrpove later
                }
                $this->state['data']['prod_db_tables'] = $response_data['value']['tables'];
                $this->save_state();
                $this->update_task_status('list_db_tables', 'completed');
                if($this->state['overall_task'] == 'import_prod_db'){
                    $this->_delete_remote_db_backup_dir();//to run it once, placed it here May be find a better place later
                }
            }
        }
        catch(wpmerge_exception $e){
            $error = $e->getError();
            $error_msg = $e->getErrorMsg();

            $result = $this->is_retry_required('list_db_tables', $e);
            if(!$result){
                $this->update_task_status('list_db_tables', 'error', $error, $error_msg, true);
            }
            
            return false;
        }

    }

    private function remote_db_backup_tables(){
        //$tables = '{"tables":[{"name":"wp_01_terms","is_wp_table":false},{"name":"wp_01_usermeta","is_wp_table":false},{"name":"wp_01_users","is_wp_table":false},{"name":"wp_01_wpmerge_log_queries","is_wp_table":false},{"name":"wp_01_wpmerge_options","is_wp_table":false},{"name":"wp_01_wpmerge_unique_ids","is_wp_table":false},{"name":"wp_02_commentmeta","is_wp_table":true},{"name":"wp_02_users","is_wp_table":true},{"name":"wp_02_wpmerge_options","is_wp_table":true},{"name":"wpdbsync_options","is_wp_table":false}]}';
        //$tables = json_decode($tables, true);
        $tables = &$this->state['data']['prod_db_tables'];

        reset($tables);
        $first_key = key($tables);
        // if ($key === key($tables))
        //     echo 'FIRST ELEMENT!';

        end($tables);
        $last_key = key($tables);
        // if ($key === key($tables))
        //     echo 'LAST ELEMENT!';

        $all_db_tables_backup_ok = true;
        $success_count = 0;
        $wp_tables_count = 0;
        $last_key_trigged = false;
        foreach($tables as $key => &$table){
            if($key === $last_key){
                $last_key_trigged = true;
            }
			if($table['is_wp_table'] === false){
				continue;
            }
            $wp_tables_count++;
			if(!isset($table['backup_status'])){
				$table['backup_status'] = 'pending';
			}
			if($table['backup_status'] == 'completed'){
                $success_count++;
                continue;
			}
			if($table['backup_status'] == 'error'){
				break;//we need all tables to be backed up, give error 
			}
			// if($table['name'] != 'wp_02_options'){//testing time if condition remove later
			// 	continue;
			// }
			if( $table['backup_status'] == 'pending' 
                || $table['backup_status'] == 'paused'){
                $this->remote_db_backup_table($table);
				// $table_db_call_count = 0;
				// while( ($table['backup_status'] == 'pending' || $table['backup_status'] == 'paused') && $table_db_call_count < 11 ){
				// 	$this->remote_db_backup_table($table);
				// 	usleep(200);
				// }
				if($table['backup_status'] == 'error'){
					break;
                }
                if($table['backup_status'] == 'completed'){
                    $success_count++;
                }
            }
			break;
        }
        if($last_key_trigged){
            if($wp_tables_count > 0 && $success_count === $wp_tables_count){
                $this->update_task_status('backup_db', 'completed');
                $this->_delete_download_directory();//to run it once, placed it here May be find a better place later
                return;
            }
            else{                
                $this->update_task_status('backup_db', 'error', 'backup_error', 'One or More tables didn\'t backuped up', true);                

                return;
            }
        }
        else{
            $this->update_task_status('backup_db', 'paused');
        }        
    }

    private function remote_db_backup_tables__get_progress_status(){
        $tables = &$this->state['data']['prod_db_tables'];

        $success_tables_count = 0;
        $wp_tables_count = 0;

        $wp_all_tables_rows_count = 0;
        $success_tables_rows_count = 0;

        if(empty($tables) || !is_array($tables)){
            return false;
        }
        foreach($tables as $key => $table){

			if($table['is_wp_table'] === false){
				continue;
            }
            $wp_tables_count++;			

            if(isset($table['total_rows'])){
                $wp_all_tables_rows_count += $table['total_rows'];//rows count might change when Prod site is live new records might keep adding or deleted. We will fetch till EOF(end of table rows)
            }

            if(!isset($table['backup_status'])){
				continue;
            }
            
			if($table['backup_status'] == 'completed'){
                if(isset($table['total_rows'])){
                    $success_tables_rows_count += $table['total_rows'];
                }
                $success_tables_count++;
                continue;
			}
			elseif($table['backup_status'] == 'paused'){
				
            }
            if(is_numeric($table['offset'])){
                $success_tables_rows_count += $table['offset'];
            }
        }
        if($wp_tables_count < 1){
            return false;
        }

        $table_count_progress_percent = floor( ($success_tables_count/$wp_tables_count) * 100);

        $rows_count_progress_percent = false;
        if($wp_all_tables_rows_count > 0){
            $rows_count_progress_percent = floor( ($success_tables_rows_count/$wp_all_tables_rows_count) * 100);
        }

        if($rows_count_progress_percent === false){
            $progress_percent = $table_count_progress_percent;
        }
        else{
            $progress_percent = ($rows_count_progress_percent * 0.9) + ($table_count_progress_percent * 0.1);
            $progress_percent = floor($progress_percent);

            if($progress_percent > 99 && $table_count_progress_percent < 100){
                $progress_percent = 99;
            }
        }
        //wpmerge_debug::log(array('progress_percent' => $progress_percent, 'success_tables_rows_count' => $success_tables_rows_count, 'wp_all_tables_rows_count' => $wp_all_tables_rows_count, 'success_tables_count' => $success_tables_count, 'wp_tables_count' => $wp_tables_count/* , 'tables' => $tables */), 'backup_progress');
        return array('percent' => $progress_percent);
       
    }

    private function remote_db_backup_table(&$table){
        try{
            $this->load_prod_urls();

            if(!isset($table['offset'])){
                $table['offset'] = 0;
            }
            if(!isset($table['eof'])){
                $table['eof'] = false;
            }

            // $findandreplace = array(
            //     'find' => $this->remote_wp_url,
            //     'replace' => get_site_url()//this site url
            // );//array commented because will be find and replace will be handled in replace_db_links steps
            
            $exim_request = array(
                'action' => 'createSQL',
                'is_remote' => true,
                // 'is_phpdump' => true,
                'table' => $table['name'],
                'offset' => $table['offset']//,
                //'findandreplace' => (object) $findandreplace
            );
            if(isset($table['is_phpdump']) && $table['is_phpdump'] === true){
                $exim_request['is_phpdump'] = true;
            }
            if(isset($table['table_type']) && $table['table_type'] == 'VIEW'){
                $exim_request['action'] = 'createViewTableSQLPHP';
            }
            $body = array(
                'action' => 'wpmerge_prod_db_export',
                'exim_request' => $exim_request,
                'prod_api_key' => wpmerge_dev_get_prod_api_key(),
                'dev_plugin_version' => WPMERGE_VERSION
            );
            $http_args = array(
                'method' => "POST",
                'timeout' => 60,
                'body' => $body
            );
          
            $response = wpmerge_do_call($this->remote_url, $http_args);
            $response_data = wpmerge_get_response_from_json($response);

            if(!isset($response_data['code'])){
                throw new wpmerge_exception('invalid_response_code');
            }
            if($response_data['code'] < 200){
                $error_msg = empty($response_data['message']) ? 'empty_error_msg' : $response_data['message'];
                throw new wpmerge_exception('db_backup_error', $error_msg);
            }
            if($response_data['code'] >= 200){
                if(!isset($response_data['value'])){
                    throw new wpmerge_exception('invalid_response_value');
                }
                if($response_data['value']['eof'] === true){
                    //table backup completed
                    $table = array_merge($table, $response_data['value']);
                    $table['backup_status'] = 'completed';
                    $this->save_state();
                    return true;
                }
                else if($response_data['value']['eof'] === false){
                    if(
                        isset($response_data['value']['mysqldumpfailed']) && 
                        $response_data['value']['mysqldumpfailed'] === true
                    ){
                        $table['offset'] = 0;
                        $table['is_phpdump'] = true;
                        $this->save_state();
                        return true;
                    }
                    
                    //table backup not completed, send another request
                    if($response_data['value']['offset'] > $table['offset']){
                        //looks good there is progress
                        $table = array_merge($table, $response_data['value']);
                        $table['backup_status'] = 'paused';//partial
                        if(!isset($table['continue_count'])){
                            $table['continue_count'] = 0;
                        }
                        $table['continue_count']++;
                        $this->save_state();
                        return true;
                    }
                    else{
                    
                        //$response_data['value']['offset'] == $table['offset'] then no progress
                        throw new wpmerge_exception('unexpected_offset');
                    }
                }
            }
        }
        catch(wpmerge_exception $e){
            $error = $e->getError();
            $error_msg = $e->getErrorMsg();

            $result = $this->is_retry_required('backup_db', $e);
            if(!$result){
                $this->update_task_status('backup_db', 'error', $error, $error_msg, true);
            }

            return false;
        }
    }

    private function prepare_compress_file_list(){
        if( !empty($this->state['data']['gz_files_status'])){
            return $this->state['data']['gz_files_status'];
        }

        if( empty($this->state['data']['prod_db_tables']) ){
            throw new wpmerge_exception('prepare_compress_file_list_table_empty');
        }

        $list = array();
        foreach($this->state['data']['prod_db_tables'] as &$table){
            if($table['is_wp_table'] === false){
                continue;
            }
            $list[$table['file']] = array(
                'current_offset' => 0,
                'status'         => 'pending'
            );
        }

        $this->state['data']['gz_files_status'] = $list;

        $this->save_state();

        return $this->state['data']['gz_files_status'];
    }


    private function remote_compress_db_backup(){

        if (!wpmerge_is_gz_available()) {
            return $this->update_task_status('compress_db', 'completed');
        }

        try{
            $this->load_prod_urls();
            $files_list = $this->prepare_compress_file_list();

            if(!$files_list){
                $this->update_task_status('compress_db', 'error', '', 'File list is empty', true);
                return false;
            }

            else{
                $this->remote_compress_db_files($files_list);
            }
        }
        catch(wpmerge_exception $e){

            $error = $e->getError();
            $error_msg = $e->getErrorMsg();

            wpmerge_debug::log($error,'-----------remote_compress_db_backup $error----------------');
            wpmerge_debug::log($error_msg,'-----------remote_compress_db_backup $error_msg----------------');

            $this->update_task_status('compress_db', 'error', $error, $error_msg, true);
            return false;
        }
    }

    private function remote_compress_db_files($files){

        if(empty($files)){
            return false;
        }

        $exim_request = array(
            'action' => 'compressDBBackups',
            'is_remote' => true,
            'files' => $files
        );
        $exim_request_json = json_encode($exim_request);

        $body = array(
            'action' => 'wpmerge_prod_db_export',
            //'exim_request' => $exim_request,
            'exim_request_json' => $exim_request_json,
            'prod_api_key' => wpmerge_dev_get_prod_api_key(),
            'dev_plugin_version' => WPMERGE_VERSION
        );

        $http_args = array(
            'method' => "POST",
            'timeout' => 60,
            'body' => $body
        );

        $response = wpmerge_do_call($this->remote_url, $http_args);
        $response_data = wpmerge_get_response_from_json($response);//here to trigger any http error throw

        if (isset($response_data['value']['error'])) {
            $this->update_task_status('compress_db', 'completed');
            return ;
        }

        if (isset($response_data['value']['status']) && $response_data['value']['status'] == 'completed') {
            $this->state['data']['gz_files_status'] = array();
            $this->save_state();
            $this->add_gz_extension_in_file_name();
            $this->update_task_status('compress_db', 'completed');
            return ;
        }

        $this->state['data']['gz_files_status'] = $response_data['value'];
        $this->save_state();
        $this->update_task_status('compress_db', 'paused');
        return $response_data;
    }

    private function add_gz_extension_in_file_name(){
        $tables = $this->state['data']['prod_db_tables'];
        foreach ($tables as $key => $table) {
            if($table['is_wp_table'] === false){
                continue;
            }
            $tables[$key]['file'] = $table['file'] . '.gz';
        }

        $this->state['data']['prod_db_tables'] = $tables;
        $this->save_state();
        return ;
    }

    private function _delete_download_directory(){
        $download_db_dir = WPMERGE_TEMP_DIR . '/'.'download_db';
        if(file_exists($download_db_dir)){
            require_once WPMERGE_PATH . '/includes/file_iterator.php';
            $common_iterator = new wpmerge_iterator_common();
            $common_iterator->delete($download_db_dir);
        }
    }

    private function download_remote_db_backup(){
        try{
            $this->load_prod_urls();
            $is_ok = $this->prepare_download_remote_db_file_list();

            if(!$is_ok){
                //errors are already handled, don't worry
                return false;
            }
            if(empty($this->download_list)){
                //errors are already handled, don't worry
            }
            else{
                $this->download_files($this->download_list);
                $this->update_task_status('download_db', 'paused');
            }
        }
        catch(wpmerge_exception $e){
            $error = $e->getError();
            $error_msg = $e->getErrorMsg();

            $result = $this->is_retry_required('download_db', $e);
            if(!$result){
                $this->update_task_status('download_db', 'error', $error, $error_msg, true);
            }

            return false;
        }
    }

    private function prepare_download_remote_db_file_list(){
        if( !empty($this->state['data']) && !empty($this->state['data']['prod_db_tables']) ){
            $wp_tables_count = 0;
            $wp_downloaded_tables_count = 0;
            $whitelist = array('name', 'current_offset');
            //$db_tables = $this->state['data']['prod_db_tables'];
            $download_list = array();
            foreach($this->state['data']['prod_db_tables'] as &$table){
                if($table['is_wp_table'] === false){
                    continue;
                }
                $wp_tables_count++;
                if(empty($this->state['data']['src_site_db_table_prefix'])){
                    $this->state['data']['src_site_db_table_prefix'] = $table['db_prefix'];
                    $this->save_state();
                }
                if($table['backup_status'] === 'completed'){
                    if(!isset($table['download_data'])){
                        $file = array(
                            'name' => $table['file'],
                            'current_offset' => 0,
                            'eof' => false,
                            'download_status' => 'pending'
                        );
                        $table['download_data'] = $file;
                        $file = array_intersect_key( $file, array_flip( $whitelist ) );
                        array_push($download_list, $file);
                    }
                    else{
                        if( $table['download_data']['download_status'] === 'completed' ){
                            $wp_downloaded_tables_count++;
                            continue;
                        }
                        if( in_array($table['download_data']['download_status'], array('pending', 'paused')) ){
                            $file = $table['download_data'];
                            $file = array_intersect_key( $file, array_flip( $whitelist ) );
                        array_push($download_list, $file);
                        }
                        if( $table['download_data']['download_status'] === 'error' ){
                            //need all the tables backed up, so throw error, this error shouldn't come here should be already handled
                            throw new wpmerge_exception('download_db_table_backup_failed_unhandled');
                        }
                    }
                }
            }
            if(empty($download_list)){
                if($wp_tables_count > 0 && $wp_tables_count === $wp_downloaded_tables_count ){
                    //mark task as completed
                    $this->update_task_status('download_db', 'completed');
                    $this->_delete_remote_db_files();
                    return true;
                }
                //no download_list and also backup not completed
                throw new wpmerge_exception('prepare_download_remote_db_unexpected_error');
            }
            $this->download_list = $download_list;
            $this->save_state();
        }
        else{
            throw new wpmerge_exception('prepare_download_remote_db_invalid_data');
        }
        return true;
    }

    private function _delete_remote_db_files(){//this is not a task that goes in do_tasks() 
        try{
            $delete_files = array();
            if( empty($this->state['data']) || empty($this->state['data']['prod_db_tables']) ){
                return false;
            }
            foreach($this->state['data']['prod_db_tables'] as $table){
                if(isset($table['file']) && !empty($table['file'])){
                    $file = array();
                    $file['name'] = $table['file'];
                    $delete_files[] = $file;
                }
            }

            $exim_request = array(
                'action' => 'deleteSQL',
                'files' => $delete_files,
            );
            $exim_request_json = json_encode($exim_request);
            $body = array(
                'action' => 'wpmerge_prod_db_export',
                // 'exim_request' => $exim_request,
                'exim_request_json' => $exim_request_json,
                'prod_api_key' => wpmerge_dev_get_prod_api_key(),
                'dev_plugin_version' => WPMERGE_VERSION
            );
            $http_args = array(
                'method' => "POST",
                'timeout' => 60,
                'body' => $body
            );
            
            $response = wpmerge_do_call($this->remote_url, $http_args);
            $response_data = wpmerge_get_response_from_json($response);
            
            if(!isset($response_data['code'])){
                throw new wpmerge_exception('invalid_response_code');
            }
            if($response_data['code'] < 200){
                $error_msg = empty($response_data['message']) ? 'empty_error_msg' : $response_data['message'];
                throw new wpmerge_exception('delete_remote_db_files', $error_msg);
            }
            if($response_data['code'] >= 200){
               
            }
        }
        catch(wpmerge_exception $e){
            //lets not handle error has of now

            //$error = $e->getError();
            //$error_msg = $e->getErrorMsg();            
        }
    }

    private function _delete_remote_db_backup_dir(){//this is not a task that goes in do_tasks() 
        try{
            $exim_request = array(
                'action' => 'deleteSQLBackupDir'
            );
            $body = array(
                'action' => 'wpmerge_prod_db_export',
                'exim_request' => $exim_request,
                'prod_api_key' => wpmerge_dev_get_prod_api_key(),
                'dev_plugin_version' => WPMERGE_VERSION
            );
            $http_args = array(
                'method' => "POST",
                'timeout' => 60,
                'body' => $body
            );
            
            $response = wpmerge_do_call($this->remote_url, $http_args);
            $response_data = wpmerge_get_response_from_json($response);
            
            if(!isset($response_data['code'])){
                throw new wpmerge_exception('invalid_response_code');
            }
            if($response_data['code'] < 200){
                $error_msg = empty($response_data['message']) ? 'empty_error_msg' : $response_data['message'];
                throw new wpmerge_exception('delete_remote_db_files', $error_msg);
            }
            if($response_data['code'] >= 200){
               
            }
        }
        catch(wpmerge_exception $e){
            //lets not handle error has of now

            //$error = $e->getError();
            //$error_msg = $e->getErrorMsg();            
        }
    }

    private function download_files($download_list){
        if(empty($download_list)){
            return false;
        }

        $files = array();
        foreach($download_list as $file){
            $files[] = array(
                //'name' => $file['name'], 
                'name_64' => base64_encode($file['name']),//wordfence 403 issue fix (LFI: Local File Inclusion)
                'current_offset' => $file['current_offset']
            );
        }
        $exim_request = array(
            'action' => 'getFileGroup',
            'is_remote' => true,
            'files' => $files,
            'maxBreakTime' => 10 //10 secs prepare download data, then download and process it within total 30 secs of this script start time
        );
        $exim_request_json = json_encode($exim_request);
        
        $body = array(
            'action' => 'wpmerge_prod_db_export',
            // 'exim_request' => $exim_request,
            'exim_request_json' => $exim_request_json,
            'prod_api_key' => wpmerge_dev_get_prod_api_key(),
            'dev_plugin_version' => WPMERGE_VERSION
        );

        //create temp file for saving response
        $download_db_dir = WPMERGE_TEMP_DIR . '/'.'download_db';
        if(!file_exists($download_db_dir)){
            $mkDir = mkdir($download_db_dir, 0775, true);
            if(!$mkDir){
                throw new wpmerge_exception('download_db_make_dir_failed');
            }
        }
        $temp_download_file = $download_db_dir. '/' . md5(uniqid('db', true)) .'.tmp';
        $create_file = touch($temp_download_file);
        if($create_file === false){
            throw new wpmerge_exception('download_db_create_file_failed');
        }

        // if(!isset($this->state['data']['process_db_table_tmp_files'])){
        //     $this->state['data']['process_db_table_tmp_files'] = array();
        // }
        // array_push($this->state['data']['process_db_table_tmp_files'], $temp_download_file);
        // $this->save_state();

        $http_args = array(
            'method' => "POST",
            'timeout' => 60,
            'body' => $body,
            'stream' => true,
            'filename' => $temp_download_file
        );

        $response = wpmerge_do_call($this->remote_url, $http_args);
        $response_data = wpmerge_get_response_body($response);//here to trigger any http error throw

        //if file is downloaded as expected, process and update
        $this->process_downloaded_db_file($temp_download_file);
    }

    private function process_downloaded_db_file($downloaded_file){
        if(!file_exists($downloaded_file)){
            throw new wpmerge_exception('download_db_file_not_exists');
        }

        $glu = '**|ls|**';
        $handle = @fopen($downloaded_file, "r");
        $file_buffer_content = '';
        if ($handle) {
            while (($buffer = fgets($handle, 4096)) !== false) {
                $file_buffer_content .= $buffer;
                while(strpos($file_buffer_content, $glu) !== false){
                    //found
                    $data_array = explode($glu, $file_buffer_content, 2);
                    $json_data_txt = $data_array[0];
                    $file_buffer_content = $data_array[1];
                    unset($data_array);
                    $this->process_extracted_json_from_download($json_data_txt);
                    unset($json_data_txt);
                }
            }
            if (!feof($handle)) {
                //echo "Error: unexpected fgets() fail\n";
                throw new wpmerge_exception('download_db_unexpected_fgets');
            }
            fclose($handle);
        }
        //here assuming all ok
        @unlink($downloaded_file);
    }

    private function process_extracted_json_from_download($json_data_txt){

        $json_data_txt = wpmerge_remove_file_data_response_junk($json_data_txt);
        if(empty($json_data_txt)){
            throw new wpmerge_exception('download_db_empty_json');
        }
        
        $json_data = json_decode($json_data_txt, true);
        if($json_data === null){
            //if required use json_last_error()
            throw new wpmerge_exception('invalid_response_json_failed', wpmerge_get_lang('invalid_response_json_failed').' '.wpmerge_show_http_response_in_error_msg($json_data_txt, $additional_msg='Showing partial data only.'));
        }
        unset($json_data_txt);
        if(empty($json_data['path'])){
            throw new wpmerge_exception('download_db_empty_path');
        }
        if(!empty($json_data['error'])){
            throw new wpmerge_exception('download_db_file_error');
        }

        //map it with table
        $prod_db_tables = &$this->state['data']['prod_db_tables'];
        $table_key = wpmerge_search_multi_array($json_data['path'], 'file', $prod_db_tables);
        if($table_key === false){
            throw new wpmerge_exception('download_db_search_table_key_missing');//something wrong
        }

        $download_db_dir = WPMERGE_TEMP_DIR . '/'.'download_db';
        $file_name =  basename($json_data['path']);
        $file = $download_db_dir . '/' . $file_name;

        $file_content = base64_decode($json_data['stream']);
        unset($json_data['stream']);

        if($json_data['current_offset'] == 0){
            //create file
            $file_put = file_put_contents($file, $file_content);
        }
        else{
            //append in the file
            $file_put = file_put_contents($file, $file_content, FILE_APPEND);
        }
        if($file_put === false){
            throw new wpmerge_exception('download_db_file_put_failed');
        }
        unset($file_content);
        
        //update database
        $table_download_data = &$prod_db_tables[$table_key]['download_data'];
        $table_download_data['eof'] = $json_data['eof'];
        if($json_data['eof']){
            $table_download_data['download_status'] = 'completed';
            $table_download_data['current_offset'] = 0;
        }else{
            $table_download_data['current_offset'] = $json_data['next_offset'];
        }
        $this->save_state();
    }

    private function un_compress_db_backup(){

        if (!wpmerge_is_gz_available()) {
            return $this->update_task_status('un_compress_db', 'completed');
        }

        try{
            $tables = $this->state['data']['prod_db_tables'];
            foreach ($tables as $key => $table) {
                if($table['is_wp_table'] === false){
                    continue;
                }

                if(wpmerge_is_time_limit_exceeded() ) {
                    $this->state['data']['prod_db_tables'] = $tables;
                    $this->save_state();
                    $this->update_task_status('un_compress_db', 'paused');
                    return;
                }

                $file = WPMERGE_TEMP_DIR . '/download_db/' . basename($table['file']);

                if (strstr($table['file'], '.gz') !== false) {
                    $this->gz_uncompress_file($file);
                    $tables[$key]['file'] = str_replace('.gz', '', $table['file']);
                }
            }
        }  catch(wpmerge_exception $e){

            $error = $e->getError();
            $error_msg = $e->getErrorMsg();

            wpmerge_debug::log($error,'-----------un_compress_db_backup $error----------------');
            wpmerge_debug::log($error_msg,'-----------un_compress_db_backup $error_msg----------------');

            $this->update_task_status('un_compress_db', 'error', $error, $error_msg, true);
            return false;
        }

        $this->state['data']['prod_db_tables'] = $tables;
        $this->save_state();
        $this->update_task_status('un_compress_db', 'completed');
    }

    private function gz_uncompress_file($source, $offset = 0){

        $dest =  str_replace('.gz', '', $source);

        $fp_in = gzopen($source, 'rb');

        if (empty($fp_in)) {
            throw new wpmerge_exception('cannot_open_gzfile_to_uncompress_sql');//something wrong
        }

        $fp_out = ($offset === 0) ? fopen($dest, 'wb') : fopen($dest, 'ab');

        if (empty($fp_out)) {
            fclose($fp_out);
            throw new wpmerge_exception('cannot_open_tmp_file_to_uncompress_sql');//something wrong
        }

        gzseek($fp_in, $offset);

        $emptimes = 0;

        while (!gzeof($fp_in)){

            $chunk_data = gzread($fp_in, 1024 * 1024 * 5); //read 5MB per chunk

            if (empty($chunk_data)) {

                $emptimes++;

                if ($emptimes > 3){
                    throw new wpmerge_exception('got_empty_gzread');//something wrong
                }

            } else {
                @fwrite($fp_out, $chunk_data);
            }

            $current_offset = gztell($fp_in);

            //Clearning to save memory
            unset($chunk_data);
        }

        fclose($fp_out);
        gzclose($fp_in);

        @unlink($source);

        return $dest;
    }

    public function pre_run_queries(){
       $wpmerge_dev_db_obj =  new wpmerge_dev_db();
       $wpmerge_dev_db_obj->pre_prod_to_dev_db_import();

       wpmerge_debug::reset_prod_clone_db_import_missed_queries();
       
       $this->update_task_status('pre_run_queries', 'completed');
    }

    /* This function will run all the queries in the downloaded tabl sql files. All tables will be saved with temporary prefix and next stag of import, it will be converted to proper prefix.
    */
    public function run_queries_from_downloaded_db(){
        try{
            $this->do_run_queries_from_downloaded_db();
        }
        catch(wpmerge_exception $e){
            $error = $e->getError();
            $error_msg = $e->getErrorMsg();
            $this->update_task_status('run_queries', 'error', $error, $error_msg, true);
            return false;
        }
    }

    public function do_run_queries_from_downloaded_db(){

        if( empty($this->state['data']) || empty($this->state['data']['prod_db_tables']) ){
            throw new wpmerge_exception('run_queries_data_missing');
        }

        if( empty($this->state['data']['prefixes']['testing_db_prefix']) ){
            if( !isset($this->state['data']['prefixes']) ){
                $this->state['data']['prefixes'] = array();
            }
            //only here generation for this overall task
            $this->state['data']['prefixes']['testing_db_prefix'] = wpmerge_db_table_prefix::check_and_get_db_table_prefix('prod_clone_in_dev_testing_db_prefix');
            $this->state['data']['prefixes']['tmp_swap_db_prefix'] = wpmerge_db_table_prefix::check_and_get_db_table_prefix('prod_clone_in_dev_tmp_swap_db_prefix');

            $this->save_state();  

            //clean up db tables in that prefix, just in case//this task might take time if to be deleted db tables are big. NEED IMPROVEMENT multicall delete tables
            if(!empty($this->state['data']['prefixes']['testing_db_prefix'])){
                wpmerge_delete_tables_with_prefix($this->state['data']['prefixes']['testing_db_prefix']);
            }
            if(!empty($this->state['data']['prefixes']['tmp_swap_db_prefix'])){
                wpmerge_delete_tables_with_prefix($this->state['data']['prefixes']['tmp_swap_db_prefix']);
            }
        }

        $testing_db_prefix = $this->_get_db_table_prefix_from_state_data('testing_db_prefix');

        $GLOBALS['wpmerge_log_prod_clone_db_import_missed_queries'] = 1;

        //to get old_db_prefix following foreach loop
        $old_db_prefix = '';
        foreach($this->state['data']['prod_db_tables'] as $_key =>  $_table){
            if($_table['is_wp_table'] === false){
                continue;
            }
            $old_db_prefix = $_table['db_prefix'];
            break;
        }

        $old_tables_list = $this->_get_wp_tables_from_prod_db_tables();
        if($old_tables_list === false){
            throw new wpmerge_exception('invalid_old_tables_list');
        }

        $new_tables_list = wpmerge_replace_prefix_for_table_names_list($old_db_prefix, $testing_db_prefix, $old_tables_list);

        if(empty($new_tables_list)){
            throw new wpmerge_exception('invalid_new_tables_list');
        }

        
        $whitelist = array('name', 'current_offset');
        $wp_tables_count = 0;
        $wp_imported_tables_count = 0;
        $download_db_dir = WPMERGE_TEMP_DIR . '/'.'download_db';

        $tables = $this->state['data']['prod_db_tables'];
        end($tables);
        $last_key = key($tables);
        reset($tables);
        $last_key_triggered = false;

        foreach($this->state['data']['prod_db_tables'] as $key =>  &$table){
            if($key === $last_key){
                $last_key_triggered = true;
            }
            if($table['is_wp_table'] === false){
                continue;
            }
            $wp_tables_count++;
            if($table['backup_status'] !== 'completed' || $table['download_data']['download_status'] !== 'completed'){
               //one of the table not downloaded or backed up throw
               throw new wpmerge_exception('run_queries_unhandled_download_error');
            }

            if(!isset($table['import_data'])){               
                $file = array(
                    'name' => $download_db_dir.'/'.basename($table['file']),
                    'current_offset' => 0,
                    'eof' => false,
                    'import_status' => 'pending'
                );
                $table['import_data'] = $file;
                //$file = array_intersect_key( $file, array_flip( $whitelist ) );
                //array_push($download_list, $file);
                $this->save_state();
            }

            if( $table['import_data']['import_status'] === 'completed' ){
                $wp_imported_tables_count++;
                continue;
            }
            if( in_array($table['import_data']['import_status'], array('pending', 'paused')) ){
                $file = $table['import_data'];
                $req_file_args = array_intersect_key( $file, array_flip( $whitelist ) );
                //array_push($download_list, $file);
                $args = array('file' => $req_file_args, 'db_prefix' => $table['db_prefix'], 'new_db_prefix' => $testing_db_prefix, 'old_tables_list' => $old_tables_list, 'new_tables_list' => $new_tables_list);

                $response = $this->run_queries_in_file($args);

                if(!isset($response['code'])){
                    throw new wpmerge_exception('invalid_response_code');
                }
                if($response['code'] < 200){
                    $error_msg = empty($response['message']) ? 'empty_error_msg' : $response['message'];
                    throw new wpmerge_exception('run_queries_error', $error_msg);
                }

                if($response['code'] >= 200){
                    //success
                    if(!isset($response['value']) && empty($response['value'])){
                        throw new wpmerge_exception('run_queries_invalid_response_value');
                    }
                    $response_value = $response['value'];
                    $table['import_data']['eof']  = $response_value['eof'];
                    if($response_value['eof']){
                        $table['import_data']['import_status'] = 'completed';
                        $table['import_data']['current_offset'] = 0;
                        @unlink( $table['import_data']['name'] );//on success clearing the file
                    }
                    else{
                        $table['import_data']['current_offset'] = $response_value['next_offset'];
                    }                    
                }
                else{
                    $table['import_data']['import_status'] = 'error';
                    //$response['code'] and $response['message'] for marking proper error
                }
                $this->save_state();
            }
            if( $table['import_data']['import_status'] === 'error' ){
                //need all the tables, so throw error
                throw new wpmerge_exception('run_queries_import_error_unhandled');
            }
            if(wpmerge_is_time_limit_exceeded()){
                break;
            }
        }
        if($last_key_triggered && $wp_tables_count > 0 && $wp_tables_count === $wp_imported_tables_count ){
            //mark task as completed
            $this->update_task_status('run_queries', 'completed');
            $this->save_state();
            return true;
        }
        else{
            $this->update_task_status('run_queries', 'paused');
        }
    }

    private function run_queries_in_file($args){
        if(empty($args['new_db_prefix']) || empty($args['db_prefix'])){
            throw new wpmerge_exception('run_queries_prefix_missing');
        }       

        $exim_request = array(
            'action' => 'runSQL',
            'is_phpdump' => true,
            'files' => array($args['file']),
            'new_db_prefix' => $args['new_db_prefix'],
            'db_prefix' => $args['db_prefix'],
            'old_tables_list' => $args['old_tables_list'],
            'new_tables_list' => $args['new_tables_list']
        );
        //runSQL
        include_once(WPMERGE_PATH.'/includes/common_exim.php');
        $common_exim_obj = new wpmerge_common_exim($exim_request);
        $response = $common_exim_obj->getResponse();

        return $response;
    }

    private function run_queries_from_downloaded_db__get_progress_status(){
        $tables = $this->state['data']['prod_db_tables'];

        $wp_tables_count = 0;
        $wp_imported_tables_count = 0;

        if(empty($tables) || !is_array($tables)){
            return false;
        }
        foreach($tables as $key => $table){

            if($table['is_wp_table'] === false){
                continue;
            }
            $wp_tables_count++;

            if(!isset($table['import_data'])){               
               continue;
            }

            if( $table['import_data']['import_status'] === 'completed' ){
                $wp_imported_tables_count++;
                continue;
            }
            elseif( $table['import_data']['import_status'] === 'paused' ){

            }
        }
        if($wp_tables_count < 1){
            return false;
        }
        $progress_percent = floor( ($wp_imported_tables_count/$wp_tables_count) * 100);
        return array('percent' => $progress_percent);
    }

    private function replace_db_links__get_progress_status(){
        $tables = $this->state['data']['prod_db_tables'];

        $wp_tables_count = 0;
        $wp_success_tables_count = 0;

        $wp_all_tables_rows_count = 0;
        $success_tables_rows_count = 0;


        if(empty($tables) || !is_array($tables)){
            return false;
        }
        foreach($tables as $key => $table){

            if($table['is_wp_table'] === false){
                continue;
            }
            $wp_tables_count++;

            if(isset($table['total_rows'])){
                $wp_all_tables_rows_count += $table['total_rows'];//rows count might change when Prod site is live new records might keep adding or deleted. We will fetch till EOF(end of table rows)
            }//here this should come before if(!isset($table['replace_db_links_data']))

            if(!isset($table['replace_db_links_data'])){               
               continue;
            }

            if( $table['replace_db_links_data']['status'] === 'completed' ){
                if(isset($table['total_rows'])){
                    $success_tables_rows_count += $table['total_rows'];
                }
                $wp_success_tables_count++;
                continue;
            }
            elseif( $table['replace_db_links_data']['status'] === 'paused' ){

            }
            $success_tables_rows_count += $table['replace_db_links_data']['current_offset'];
        }
        if($wp_tables_count < 1){
            return false;
        }

        $table_count_progress_percent = floor( ($wp_success_tables_count/$wp_tables_count) * 100);

        $rows_count_progress_percent = false;
        if($wp_all_tables_rows_count > 0){
            $rows_count_progress_percent = floor( ($success_tables_rows_count/$wp_all_tables_rows_count) * 100);
        }

        if($rows_count_progress_percent === false){
            $progress_percent = $table_count_progress_percent;
        }
        else{
            $progress_percent = $rows_count_progress_percent;

            if($rows_count_progress_percent > 99 && $table_count_progress_percent < 100){
                $progress_percent = 99;
            }
        }

        return array('percent' => $progress_percent);
    }


    private function remote_replace_db_links__get_progress_status(){
        $tables = $this->state['data']['prod_db_tables'];

        $wp_tables_count = 0;
        $wp_success_tables_count = 0;

        $wp_all_tables_rows_count = 0;
        $success_tables_rows_count = 0;

        if(empty($tables) || !is_array($tables)){
            return false;
        }
        foreach($tables as $key => $table){

            if($table['is_wp_table'] === false){
                continue;
            }
            $wp_tables_count++;

            if(isset($table['total_rows'])){
                $wp_all_tables_rows_count += $table['total_rows'];//rows count might change when Prod site is live new records might keep adding or deleted. We will fetch till EOF(end of table rows)
            }//here this should come before if(!isset($table['replace_db_links_data']))

            if(!isset($table['remote_replace_db_links_data'])){               
               continue;
            }

            if( $table['remote_replace_db_links_data']['status'] === 'completed' ){
                if(isset($table['total_rows'])){
                    $success_tables_rows_count += $table['total_rows'];
                }
                $wp_success_tables_count++;
                continue;
            }
            elseif( $table['remote_replace_db_links_data']['status'] === 'paused' ){

            }
            $success_tables_rows_count += $table['remote_replace_db_links_data']['current_offset'];
        }
        if($wp_tables_count < 1){
            return false;
        }

        $table_count_progress_percent = floor( ($wp_success_tables_count/$wp_tables_count) * 100);

        $rows_count_progress_percent = false;
        if($wp_all_tables_rows_count > 0){
            $rows_count_progress_percent = floor( ($success_tables_rows_count/$wp_all_tables_rows_count) * 100);
        }

        if($rows_count_progress_percent === false){
            $progress_percent = $table_count_progress_percent;
        }
        else{
            $progress_percent = $rows_count_progress_percent;

            if($rows_count_progress_percent > 99 && $table_count_progress_percent < 100){
                $progress_percent = 99;
            }
        }

        return array('percent' => $progress_percent);
    }

    private function _get_wp_tables_from_prod_db_tables(){
        if(empty($this->state['data']['prod_db_tables'])){
            return false;
        }

        $final_wp_tables = array();
        $tables = $this->state['data']['prod_db_tables'];

        if(empty($tables) || !is_array($tables)){
            return false;
        }
        foreach($tables as $key => $table){

            if($table['is_wp_table'] === false){
                continue;
            }
            $final_wp_tables[] = $table['name'];
        }
        return empty($final_wp_tables) ? false : $final_wp_tables;
    }

    private function _get_db_table_prefix_from_state_data($name){
        if( !empty($this->state['data']['prefixes'][$name]) ){
            return $this->state['data']['prefixes'][$name];
        }
        else{
            throw new wpmerge_exception('unable_find_unique_prefixes');
        }
    }

    public function replace_db_links(){
        try{
            $this->do_replace_db_links();
        }
        catch(wpmerge_exception $e){
            $error = $e->getError();
            $error_msg = $e->getErrorMsg();
            $this->update_task_status('replace_db_links', 'error', $error, $error_msg, true);
            return false;
        }
    }

    public function do_replace_db_links(){

        $this->load_prod_urls();

        if( empty($this->state['data']) || empty($this->state['data']['prod_db_tables']) ){
            throw new wpmerge_exception('replace_db_links_data_missing');
        }
        if(!isset($this->state['data']['find_replace_data'])){
            //one time just get the data and save
            $find_replace_data = array();
            $find_replace_data['old_url'] = untrailingslashit($this->remote_wp_url);
            $find_replace_data['new_url'] = untrailingslashit(get_site_url());
            $find_replace_data['new_file_path'] = untrailingslashit(ABSPATH);

            if(
                !isset($this->state['data']['prod_server_info'])
                || empty($this->state['data']['prod_server_info']['wp']['abspath'])){
                    throw new wpmerge_exception('prod_abspath_missing');
            }
            $_old_file_path = $this->state['data']['prod_server_info']['wp']['abspath'];
            $find_replace_data['old_file_path'] = untrailingslashit($_old_file_path);
            $this->state['data']['find_replace_data'] = $find_replace_data;
            $this->save_state();
        }

        $find_replace_data = $this->state['data']['find_replace_data'];
        $is_multi_site = false;
        $is_subdomain_install = false;
        
        if(
            isset($this->state['data']['prod_server_info']['wp']['is_multisite'])
            ){
                $wp_info = $this->state['data']['prod_server_info']['wp'];
                $is_multi_site = $wp_info['is_multisite'];
                $is_subdomain_install = $wp_info['is_subdomain_install'];
        }

        $is_multisite_subdomain_install = $is_multi_site && $is_subdomain_install;

        if(
            empty($find_replace_data['old_url']) || 
            empty($find_replace_data['new_url']) ||
            empty($find_replace_data['old_file_path']) ||
            empty($find_replace_data['new_file_path'])        
        ){
            throw new wpmerge_exception('find_replace_data_missing');
        }

        $testing_db_prefix = $this->_get_db_table_prefix_from_state_data('testing_db_prefix');
        $whitelist = array('name', 'current_offset');
        $wp_tables_count = 0;
        $wp_task_completed_table_count = 0;

        $tables = $this->state['data']['prod_db_tables'];
        end($tables);
        $last_key = key($tables);
        reset($tables);
        $last_key_triggered = false;

        foreach($this->state['data']['prod_db_tables'] as $key =>  &$table){
            if($key === $last_key){
                $last_key_triggered = true;
            }
            if($table['is_wp_table'] === false){
                continue;
            }
            $wp_tables_count++;
            if($table['import_data']['import_status'] !== 'completed'){
               //one of the table not imported throw
               throw new wpmerge_exception('replace_db_links_unhandled_import_error');
            }

            if(!isset($table['replace_db_links_data'])){
                $table_with_new_prefix = wpmerge_replace_prefix($table['db_prefix'], $testing_db_prefix, $table['name']);

                $table_replace_state = array(
                    'name' => $table_with_new_prefix,
                    'current_offset' => 0,
                    'eof' => false,
                    'status' => 'pending'
                );
                $table['replace_db_links_data'] = $table_replace_state;
                $this->save_state();
            }

            if( $table['replace_db_links_data']['status'] === 'completed' ){
                $wp_task_completed_table_count++;
                continue;
            }
            if( in_array($table['replace_db_links_data']['status'], array('pending', 'paused')) ){
                while($table['replace_db_links_data']['eof'] === false && !wpmerge_is_time_limit_exceeded()){
                
                    $table_replace_state = $table['replace_db_links_data'];
                    $table_replace_state_args = array_intersect_key( $table_replace_state, array_flip( $whitelist ) );

                    include_once(WPMERGE_PATH.'/includes/common_replace_db_links.php');

                    $replace_db_links_obj = new wpmerge_replace_db_links();

                    $response = $replace_db_links_obj->replace_uri($find_replace_data['old_url'],$find_replace_data['new_url'], $find_replace_data['old_file_path'], $find_replace_data['new_file_path'], '', $table_replace_state_args, $is_multisite_subdomain_install);

                    if(!isset($response['eof']) || !is_bool($response['eof'])){
                        throw new wpmerge_exception('invalid_response');
                    }

                    if($response['eof'] === true){
                        $table['replace_db_links_data']['status'] = 'completed';
                        $table['replace_db_links_data']['eof'] = true;
                        $table['replace_db_links_data']['current_offset'] = 0;
                    }
                    else{
                        $table['replace_db_links_data']['status'] = 'paused';
                        $table['replace_db_links_data']['eof'] = false;
                        $table['replace_db_links_data']['current_offset'] = $response['current_offset'];
                    }
                    $this->save_state();
                }
            }
            if(wpmerge_is_time_limit_exceeded()){
                break;
            }
        }
        wpmerge_debug::printr(wpmerge_select_by_memory_limit::get_all_perform_data(), 'all_perform_data');
        if($last_key_triggered && $wp_tables_count > 0 && $wp_tables_count === $wp_task_completed_table_count ){
            //mark task as completed
            $this->update_task_status('replace_db_links', 'completed');
            $this->save_state();
            return true;
        }
        else{
            $this->update_task_status('replace_db_links', 'paused');
        }
    }

    public function post_run_queries(){
        //assuming this function will run in one shot

        try{
            //similar following code is there in prod.php under $_POST['wpmerge_action'] === 'finalise_tables'

            include_once(WPMERGE_PATH.'/includes/common_replace_db_links.php');

            $testing_db_prefix = $this->_get_db_table_prefix_from_state_data('testing_db_prefix');
            $tmp_swap_db_prefix = $this->_get_db_table_prefix_from_state_data('tmp_swap_db_prefix');

            $replace_db_links_obj = new wpmerge_replace_db_links();

            $replace_db_links_obj->multi_site_db_changes($testing_db_prefix, get_site_url(), wpmerge_dev_get_prod_site_url());

            $wpmerge_swap_tables_obj = new wpmerge_swap_tables();

            $current_db_prefix = $GLOBALS['wpdb']->base_prefix;

            $wpmerge_swap_tables_obj->swap_tables($current_db_prefix, $testing_db_prefix, $tmp_swap_db_prefix);

            wpmerge_run_db_final_modifications($this->state['data']['src_site_db_table_prefix']);

            $wpmerge_dev_db_obj =  new wpmerge_dev_db();
            $wpmerge_dev_db_obj->post_prod_to_dev_db_import();

            $wpmerge_swap_tables_obj->delete_tmp_swap_tables($tmp_swap_db_prefix);
        }
        catch(wpmerge_exception $e){
            $error = $e->getError();
            $error_msg = $e->getErrorMsg();
            $this->update_task_status('post_run_queries', 'error', $error, $error_msg, true);
            return false;
        }
        $this->update_task_status('post_run_queries', 'completed');
        //$this->update_overall_task_status('completed');
        $this->save_state();
    }

    private function reset_retry($task){
        $this->state['tasks'][$task]['retry_count'] = 0;
        $this->state['overall_status'] = 'pending';
        $this->state['overall_retry_interval'] = 200;
        $this->update_task_status($task, 'running');
    }

    private function is_retry_required($task, $exception)
    {
        static $retriable_errors = array(
            'http_error',
            'invalid_response_json_failed',
            'download_db_empty_json'
        );

        if( empty($this->state['tasks']) 
            || empty($this->state['tasks'][$task]) ){

            return false;
        }

        $error = $exception->getError();
        $is_retriable_error = in_array($error, $retriable_errors);

        if(!$is_retriable_error){

            return false;
        }

        $retry_count = ( empty($this->state['tasks'][$task]['retry_count']) ) ? 0 : $this->state['tasks'][$task]['retry_count'];

        if($retry_count >= 2){

            return false;
        }

        $retry_interval = 5000;

        $retry_count++;
        $this->state['tasks'][$task]['retry_count'] = $retry_count;
        $this->state['overall_retry_interval'] = $retry_interval;
        $this->state['overall_status'] = 'retry';

        $this->update_task_status($task, 'retry');

        wpmerge_debug::log($retry_count, "--------retrying $task--------");

        return true;
    }

    private function prepare_prod_bridge(){
        try{
            $this->load_prod_urls();
            $body = array(
                'action' => 'wpmerge_prod_db_delta_import',
                'wpmerge_action' => 'prepare_bridge',
                'prod_api_key' => wpmerge_dev_get_prod_api_key(),
                'dev_plugin_version' => WPMERGE_VERSION
            );
            $http_args = array(
                'method' => "POST",
                'timeout' => 10,//10 - is enough to bring server info
                'body' => $body
            );

            $response = wpmerge_do_call($this->remote_url, $http_args);
            $response_data = wpmerge_get_response_from_json($response);
            //wpmerge_debug::log($response_data, 'response_data');

            if(empty($response_data)){
                throw new wpmerge_exception('invalid_response');
            }
        }
        catch(wpmerge_exception $e){
            $error = $e->getError();
            $error_msg = $e->getErrorMsg();

            $result = $this->is_retry_required('prepare_prod_bridge', $e);
            if(!$result){
                $this->update_task_status('prepare_prod_bridge', 'error', $error, $error_msg, true);
            }

            return false;
        }

        $this->state['data']['prod_bridge_info'] = $response_data;
        wpmerge_debug::log($this->state, 'state stored');
        $this->save_state();
        $this->update_task_status('prepare_prod_bridge', 'completed');
    }

    private function delete_dev_bridge(){
        require WPMERGE_PATH . '/includes/prepare_bridge.php';
        $prepare_bridge = new wpmerge_prepare_bridge();
        $prepare_bridge->delete();
        $this->update_task_status('delete_dev_bridge', 'completed');
        //$this->update_overall_task_status('completed');
    }

    private function get_server_info(){
        try{
            $this->load_prod_urls();
            $body = array(
                'action' => 'wpmerge_prod_db_delta_import',
                'wpmerge_action' => 'get_server_info',
                'prod_api_key' => wpmerge_dev_get_prod_api_key(),
                'dev_plugin_version' => WPMERGE_VERSION
            );
            $http_args = array(
                'method' => "POST",
                'timeout' => 10,//10 - is enough to bring server info
                'body' => $body
            );

            $response = wpmerge_do_call($this->remote_url, $http_args);
            $response_data = wpmerge_get_response_from_json($response);

            if(empty($response_data['php'])){
                throw new wpmerge_exception('invalid_response');
            }
        }
        catch(wpmerge_exception $e){
            $error = $e->getError();
            $error_msg = $e->getErrorMsg();

            $result = $this->is_retry_required('get_server_info', $e);
            if(!$result){
                $this->update_task_status('get_server_info', 'error', $error, $error_msg, true);
            }

            return false;
        }

        $this->state['data']['prod_server_info'] = $response_data;
        $this->save_state();
        $this->update_task_status('get_server_info', 'completed');
    }

    private function push_db_delta(){
        try{
            $this->load_prod_urls();
            $log_queries_table = $GLOBALS['wpdb']->base_prefix .'wpmerge_log_queries';

            if(empty($this->state['data']['push_db_delta_state'])){
                $this->state['data']['push_db_delta_state'] = array();
                $push_db_delta_state = &$this->state['data']['push_db_delta_state'];

                $total_rows = $GLOBALS['wpdb']->get_var("SELECT COUNT(id) FROM `".$log_queries_table."` WHERE `is_record_on` = '1' AND `type` = 'query'");

                if(empty($total_rows)){
                    throw new wpmerge_exception('no_changes_to_apply');//'invalid_rows_count'
                }

                $push_db_delta_state['offset'] = 0;
                $push_db_delta_state['total'] = $total_rows;
                $push_db_delta_state['last_query_id'] = 0;
                $this->save_state();
            }
            else{
                $push_db_delta_state = &$this->state['data']['push_db_delta_state'];
            }
            
            $prod_server_info = $this->state['data']['prod_server_info'];
            $prod_post_max_size = wpmerge_prod_post_max_size($prod_server_info['php']);
            if($prod_post_max_size === false){
                throw new wpmerge_exception('invalid_prod_post_max_size');
            }

            //check for gz availability
            $do_gz = true;
            if($do_gz && !wpmerge_is_gz_txt_available()){//check this server
                $do_gz = false;
            }
            //check prod server
            if($do_gz && !isset($prod_server_info['php']['is_gz_txt_available'])){
                $do_gz = false;
            }
            if($do_gz && !$prod_server_info['php']['is_gz_txt_available']){
                $do_gz = false;
            }

            //get prod PHP_EOL
            $prod_php_eol = "\n";
            if(isset($prod_server_info['php']['PHP_EOL'])){
                $prod_php_eol = $prod_server_info['php']['PHP_EOL'];
            }
            
            $row_limit = 5000;
            $query_glu = $prod_php_eol . '|**wpm**|';
            $initial_offset = $offset = $push_db_delta_state['offset'];
            $total_rows = $push_db_delta_state['total'];
            $initial_last_query_id = $last_query_id =  $push_db_delta_state['last_query_id'];
            $time_limit = 10; //10 sec here, then posting time(say 5 secs max), another 10 secs to save it total 25 secs.


            $delta_queries_batch = '';
            $total_length_added = 0;
            $file_write_buffer_size = 0;
            
            //create temp file for saving response
            $temp_upload_file = wpmerge_create_temp_file('upload_delta', array('prefix' => 'db_delta'));
            
            while($total_rows > $offset && !wpmerge_is_time_limit_exceeded($time_limit)){
                $q_offset = $offset;
                $sql_where_append = '';
                
                if(isset($last_query_id)){
                    $q_offset = 0;
                    $sql_where_append = " AND `id` > $last_query_id";//to increase the efficiency for a million rows+ when rows and offset searching becomes big.
                }

                //$db_delta_rows = $GLOBALS['wpdb']->get_results("SELECT * FROM `".$log_queries_table."` WHERE `is_record_on` = '1' AND `type` = 'query' ".$sql_where_append." ORDER BY id ASC LIMIT $row_limit OFFSET $q_offset", ARRAY_A);
                
                $select_args = array();
                $select_args['columns'] = '*';
                $select_args['table'] = $log_queries_table;
                $select_args['where'] = "`is_record_on` = '1' AND `type` = 'query'  ".$sql_where_append."";
                $select_args['order'] = "id ASC";
                $select_args['limit'] = $row_limit;
                $select_args['offset'] = $q_offset;
                $select_args['result_format'] = ARRAY_A;
                $select_args['optimize_query'] = isset($last_query_id) ? false : true;

                $select_by_memory_limit_obj = new wpmerge_select_by_memory_limit($select_args);
                $db_delta_rows = $select_by_memory_limit_obj->process_and_get_results();

                if(empty($db_delta_rows)){
                    throw new wpmerge_exception('no_rows_returned'); 
                }

                
                foreach($db_delta_rows as $db_delta_row){
                    $delta_query = json_encode($db_delta_row);
                    if($do_gz){
                        $gz_delta_query = gzcompress($delta_query, 9);
                        if($delta_query ===  false){
                            if($total_length_added === 0){//if not previous gzip is done
                                $do_gz = false;
                            }
                            else{
                                throw new wpmerge_exception('gz_compress_failed');
                            }
                        }
                        $delta_query = $gz_delta_query;
                    }
                    $delta_query_size = strlen($delta_query) + strlen($query_glu);
                    if(($total_length_added + $delta_query_size) >= $prod_post_max_size){
                        break 2;
                    }
                    $delta_queries_batch .= $delta_query.$query_glu;
                    $total_length_added += $delta_query_size;
                    $file_write_buffer_size += $delta_query_size;
                    $delta_query = '';
                    $offset++;
                    $last_query_id = $db_delta_row['id'];
                    if(wpmerge_is_time_limit_exceeded($time_limit)){
                        break 2;
                    }
                    if($offset == $total_rows){
                        break 2;
                    }
                    if( $file_write_buffer_size > 1048576){//1048576 => 1MB
                        $file_write = file_put_contents($temp_upload_file, $delta_queries_batch, FILE_APPEND); 
                        if($file_write === false){
                            throw new wpmerge_exception('upload_file_writing_failed');
                        }
                        $delta_queries_batch = '';
                        $file_write_buffer_size = 0;
                    }
                }
            }

            wpmerge_debug::printr(wpmerge_select_by_memory_limit::get_all_perform_data(), 'all_perform_data');

            //to write remaining data
            if(!empty($delta_queries_batch)){
                $file_write = file_put_contents($temp_upload_file, $delta_queries_batch, FILE_APPEND);
                if($file_write === false){
                    throw new wpmerge_exception('upload_file_writing_failed');
                }
            }

            $curl_file_upload_obj = curl_file_create($temp_upload_file);//requires php 5.5

            $_log_data = "\n ==================================";
            $_log_data .=  "\n Total offsets:".($offset - $initial_offset);
            $_log_data .=  "\n push offset:".$offset;
            $_log_data .=  "\n push initial_offset:".$initial_offset;
            $_log_data .=  "\n push initial_last_query_id:".$initial_last_query_id;
            $_log_data .=  "\n push total_length_added:".($total_length_added/(1024*1024));
            $_log_data .=  "\n file size total:".(filesize($temp_upload_file)/(1024*1024));
            $_log_data .=  "\n Time taken before upload:".(microtime(true) - WPMERGE_START_TIME);

            $_log_data .=  "\n";
            wpmerge_debug::log($_log_data, '-----------push delta stats---------------');
            
            $body = array(
                'action' => 'wpmerge_prod_db_delta_import',
                'wpmerge_action' => 'receive_db_delta',
                //'db_delta_base64_queries' => $base64_queries,
                'db_delta_file' => $curl_file_upload_obj,
                'initial_offset' => $initial_offset,
                'offset' => $offset,
                'initial_last_query_id' => $initial_last_query_id,
                'total' => $total_rows,
                'is_gz_data' => (int)$do_gz,
                'prod_api_key' => wpmerge_dev_get_prod_api_key(),
                'dev_plugin_version' => WPMERGE_VERSION       
            );
            unset($base64_queries);
            $http_args = array(
                'method' => "POST",
                'timeout' => 60,
                'body' => $body//,
                //'contentType' => 'multipart/form-data'
            );

            $response = wpmerge_do_call($this->remote_url, $http_args);
            @unlink($temp_upload_file);
            $response_data = wpmerge_get_response_from_json($response);

            $_log_data = "\n Time taken after upload response:".(microtime(true) - WPMERGE_START_TIME);

            wpmerge_debug::log($_log_data, '-----------push delta stats---------------');

            if(empty($response_data) || !isset($response_data['status'])){
                throw new wpmerge_exception('invalid_response');
            }

            if($response_data['status'] == 'error'){
                throw new wpmerge_exception('prod_response_error', $response_data['error_msg']);
            }

            if(!isset($response_data['value']['offset']) || !isset($response_data['value']['last_query_id'])){
                throw new wpmerge_exception('response_data_missing');
            }

            $response_offset = (int)$response_data['value']['offset'];
            $response_last_query_id = (int)$response_data['value']['last_query_id'];

            wpmerge_debug::log('Sucess offsets:'.($response_offset - $initial_offset), '-----------push delta stats---------------');

            if($initial_offset >=  $response_offset){
                throw new wpmerge_exception('response_no_progress');
            }

            $push_db_delta_state['offset'] = $response_offset;
            $push_db_delta_state['last_query_id'] = $response_last_query_id;

            $this->save_state();

            if($response_offset == $push_db_delta_state['total']){
                $this->update_task_status('push_db_delta', 'completed');
            }
            else{
                $this->update_task_status('push_db_delta', 'paused');
            }

        }
        catch(wpmerge_exception $e){
            $error = $e->getError();
            $error_msg = $e->getErrorMsg();

            $result = $this->is_retry_required('push_db_delta', $e);
            if(!$result){
                $this->update_task_status('push_db_delta', 'error', $error, $error_msg, true);
            }

            return false;
        }
    }

    private function push_db_delta__get_progress_status(){
        if(!isset($this->state['data']['push_db_delta_state'])){
            return false;
        }

        $push_db_delta_state = $this->state['data']['push_db_delta_state'];

        $offset = $push_db_delta_state['offset'];
        $total = $push_db_delta_state['total'];

        if($total < 1){
            return false;
        }
        if($offset == $total){
            $progress_percent = 100;
        }
        else{
            $progress_percent = floor( ($offset/$total) * 100);
        }
        return array('percent' => $progress_percent);
    }

    private function remote_run_delta_queries(){
        try{
            $this->load_prod_urls();
            
            if(empty($this->state['data']['remote_run_delta_queries_state'])){
                $this->state['data']['remote_run_delta_queries_state'] = array();
                $remote_run_delta_queries_state = &$this->state['data']['remote_run_delta_queries_state'];
                $remote_run_delta_queries_state['offset'] = 0;
                $remote_run_delta_queries_state['last_query_id'] = 0;
                $remote_run_delta_queries_state['total'] = $this->state['data']['push_db_delta_state']['total'];
                $remote_run_delta_queries_state['eof'] = false;
                $this->save_state();
            }
            else{
                $remote_run_delta_queries_state = &$this->state['data']['remote_run_delta_queries_state'];
            }

            $offset = $remote_run_delta_queries_state['offset'];
            $last_query_id = $remote_run_delta_queries_state['last_query_id'];
            $total = $remote_run_delta_queries_state['total'];

            if($offset > 0){
                wpmerge_update_option('prod_delta_import_is_atleast_one_query_is_successful', 1);
            }

            if($offset > $total){
                throw new wpmerge_exception('invalid_offset_or_total');
            }

            $old_tables_list = wpmerge_get_wp_prefix_base_tables();
            if(empty($old_tables_list)){
                throw new wpmerge_exception('invalid_old_tables_list');
            }

            $old_table_list_from_log_queries = wpmerge_get_unique_table_names_from_log_queries($add_this_prefix=$GLOBALS['wpdb']->base_prefix);
            if(empty($old_table_list_from_log_queries)){
                throw new wpmerge_exception('invalid_old_table_list_from_log_queries');
            }

            $old_tables_list = array_merge($old_tables_list, $old_table_list_from_log_queries);

            $old_tables_list = array_unique($old_tables_list);//to remove duplicate
            $old_tables_list = array_values($old_tables_list);//to reindex keys

            //to avoid variables limit
            //$old_tables_list_str = implode('**|tl|**', $old_tables_list);
            $old_tables_list_json = json_encode($old_tables_list);

            if(empty($this->state['data']['remote_clone_db_state']['testing_db_prefix'])){
                throw new wpmerge_exception('invalid_testing_db_prefix');
            }

            $testing_db_prefix = $this->state['data']['remote_clone_db_state']['testing_db_prefix'];

            $body = array(
                'action' => 'wpmerge_prod_db_delta_import',
                'wpmerge_action' => 'run_db_delta_queries',
                'offset' => $offset,
                'last_query_id' => $last_query_id,
                'total' => $total,
                'dev_db_prefix' => $GLOBALS['wpdb']->base_prefix,
                'testing_db_prefix' => $testing_db_prefix,
                // 'old_tables_list_str' => $old_tables_list_str,
                'old_tables_list_json' => $old_tables_list_json,
                'prod_api_key' => wpmerge_dev_get_prod_api_key(),
                'dev_plugin_version' => WPMERGE_VERSION
            );
            $http_args = array(
                'method' => "POST",
                'timeout' => 60,
                'body' => $body
            );

            $response = wpmerge_do_call($this->remote_url, $http_args);
            $response_data = wpmerge_get_response_from_json($response);

            if(empty($response_data) || !isset($response_data['status'])){
                throw new wpmerge_exception('invalid_response');
            }

            if($response_data['status'] == 'error'){
                if(isset($response_data['is_atleast_one_query_is_successful']) && $response_data['is_atleast_one_query_is_successful']){
                    wpmerge_update_option('prod_delta_import_is_atleast_one_query_is_successful', 1);
                }
                throw new wpmerge_exception('prod_response_error', $response_data['error_msg']);
            }

            if(!isset($response_data['value']['offset'])){
                throw new wpmerge_exception('response_data_missing');
            }

            $response_offset = $response_data['value']['offset'];
            $response_last_query_id = $response_data['value']['last_query_id'];
            $response_eof = $response_data['value']['eof'];

            if(!empty($initial_offset) && $initial_offset >=  $response_offset){
                throw new wpmerge_exception('response_no_progress');
            }

            $remote_run_delta_queries_state['offset'] = $response_offset;
            $remote_run_delta_queries_state['last_query_id'] = $response_last_query_id;

            if($response_eof === true){
                $remote_run_delta_queries_state['offset'] = 0;
                $remote_run_delta_queries_state['last_query_id'] = 0;
                $remote_run_delta_queries_state['eof'] = true;                
            }

            $this->save_state();

            if($response_eof === true){
                $this->update_task_status('remote_run_delta_queries', 'completed');
            }
            else{
                $this->update_task_status('remote_run_delta_queries', 'paused');
            }
        }
        catch(wpmerge_exception $e){
            $error = $e->getError();
            $error_msg = $e->getErrorMsg();

            $result = $this->is_retry_required('remote_run_delta_queries', $e);
            if(!$result){
                $this->update_task_status('remote_run_delta_queries', 'error', $error, $error_msg, true);
            }

            return false;
        }
    }
    
    private function remote_run_delta_queries__get_progress_status(){
        if(!isset($this->state['data']['remote_run_delta_queries_state'])){
            return false;
        }

        $remote_run_delta_queries_state = $this->state['data']['remote_run_delta_queries_state'];

        $offset = $remote_run_delta_queries_state['offset'];
        $total = $remote_run_delta_queries_state['total'];
        $eof = $remote_run_delta_queries_state['eof'];

        if($total < 1){
            return false;
        }
        if($eof === true){
            $progress_percent = 100;
        }
        else{
            $progress_percent = floor( ($offset/$total) * 100);
        }
        return array('percent' => $progress_percent);
    }

    private function remote_replace_db_links(){
        try{
            $this->do_remote_replace_db_links();
        }
        catch(wpmerge_exception $e){
            $error = $e->getError();
            $error_msg = $e->getErrorMsg();

            $result = $this->is_retry_required('remote_replace_db_links', $e);
            if(!$result){
                $this->update_task_status('remote_replace_db_links', 'error', $error, $error_msg, true);
            }

            return false;
        }
    }

    public function do_remote_replace_db_links(){

        $this->load_prod_urls();

        if( empty($this->state['data']) || empty($this->state['data']['prod_db_tables']) ){

            throw new wpmerge_exception('replace_db_links_data_missing');
        }

        if(empty($this->state['data']['prod_db_tables'])){

            throw new wpmerge_exception('prod_db_tables_list_missing');
        }

        if(empty($this->state['data']['remote_clone_db_state']['testing_db_prefix']) || empty($this->state['data']['remote_clone_db_state']['wp_db_prefix'])){
            throw new wpmerge_exception('invalid_db_prefixes');
        }
        
        $testing_db_prefix = $this->state['data']['remote_clone_db_state']['testing_db_prefix'];
        $prod_wp_db_prefix = $this->state['data']['remote_clone_db_state']['wp_db_prefix'];

        if(!isset($this->state['data']['remote_replace_db_links_data'])){
            $filtered_prod_tables = array();
            foreach($this->state['data']['prod_db_tables'] as $key =>  $table){
                if($table['is_wp_table'] === false){
                    continue;
                }
                $initial_replace_tables_state = array(
                    'name' => wpmerge_replace_prefix($prod_wp_db_prefix, $testing_db_prefix, $table['name']),
                    'current_offset' => 0,
                    'eof' => false,
                    'status' => 'pending'
                );
                array_push($filtered_prod_tables, $initial_replace_tables_state);
            }
            $this->state['data']['remote_replace_db_links_data'] = $filtered_prod_tables;
            $this->save_state();
        }
        $replace_tables_state = $this->state['data']['remote_replace_db_links_data'];

        if(empty($replace_tables_state)){
            throw new wpmerge_exception('remote_replace_db_links_data_missing');
        }

        $is_multi_site = false;
        $is_subdomain_install = false;

        if(!isset($this->state['data']['prod_server_info']['wp'])){
            throw new wpmerge_exception('server_wp_info_missing');
        }
        
        if(
            isset($this->state['data']['prod_server_info']['wp']['is_multisite'])
            ){
                $wp_info = $this->state['data']['prod_server_info']['wp'];
                $is_multi_site = $wp_info['is_multisite'];
                $is_subdomain_install = $wp_info['is_subdomain_install'];
        }

        $is_multisite_subdomain_install = $is_multi_site && $is_subdomain_install;


        if($this->is_remote_replace_db_links_all_tables_completed($replace_tables_state)){
            $this->update_task_status('remote_replace_db_links', 'completed');

            return true;
        }

        $find_replace_data = array();
        $find_replace_data['old_url'] = get_site_url();
        $find_replace_data['old_file_path'] = ABSPATH;

        $replace_tables_state_json = json_encode($replace_tables_state);

        $body = array(
            'action' => 'wpmerge_prod_db_delta_import',
            'wpmerge_action' => 'replace_db_links',
            // 'replace_tables_state' => $replace_tables_state,
            'replace_tables_state_json' => $replace_tables_state_json,
            'find_replace_data' => $find_replace_data,
            'dev_db_prefix' => $GLOBALS['wpdb']->base_prefix,
            'is_multisite_subdomain_install' => $is_multisite_subdomain_install,
            'prod_api_key' => wpmerge_dev_get_prod_api_key(),
            'dev_plugin_version' => WPMERGE_VERSION
        );
        $http_args = array(
            'method' => "POST",
            'timeout' => 60,
            'body' => $body
        );

        $response = wpmerge_do_call($this->remote_url, $http_args);
        $response_data = wpmerge_get_response_from_json($response);

        if(empty($response_data) || !isset($response_data['status'])){
            throw new wpmerge_exception('invalid_response');
        }

        if($response_data['status'] == 'error'){
            throw new wpmerge_exception('prod_response_error', $response_data['error_msg']);
        }

        if(!isset($response_data['replace_tables_state']) || empty($response_data['replace_tables_state'])){
            throw new wpmerge_exception('response_data_missing');
        }

        $this->state['data']['remote_replace_db_links_data'] = $response_data['replace_tables_state'];

        $this->save_state();

        if($this->is_remote_replace_db_links_all_tables_completed($response_data['replace_tables_state'])){
            $this->update_task_status('remote_replace_db_links', 'completed');
        }
        else{
            $this->update_task_status('remote_replace_db_links', 'paused');
        }
    }

    private function is_remote_replace_db_links_all_tables_completed($remote_replace_data){
        $table_count = 0;
        $completed_table_count = 0;
        foreach($remote_replace_data as $key => $remote_table){
            $table_count++;
            //right now no errors involved in the process so no error checking
            if($remote_table['status'] === 'completed'){
                $completed_table_count++;
            }
        }
        if(!$table_count){
            return false;//something wrong better return false
        }
        if($table_count > 0 && $table_count === $completed_table_count){
            return true;
        }
        return false;
    }

    private function remote_clone_db(){//create tmp clone db tables with new prefix, if all ok later on convert to live prefix
        try{
            $this->do_remote_clone_db();
        }
        catch(wpmerge_exception $e){
            $error = $e->getError();
            $error_msg = $e->getErrorMsg();

            $result = $this->is_retry_required('remote_clone_db', $e);
            if(!$result){
                $this->update_task_status('remote_clone_db', 'error', $error, $error_msg, true);
            }

            return false;
        }
    }

    private function do_remote_clone_db(){

        $this->load_prod_urls();
        
        if(empty($this->state['data']['prod_db_tables'])){

            throw new wpmerge_exception('prod_db_tables_list_missing');
        }
        if(!isset($this->state['data']['remote_clone_db_state'])){
            $filtered_prod_tables = array();
            foreach($this->state['data']['prod_db_tables'] as $key =>  $table){
                if($table['is_wp_table'] === false){
                    continue;
                }
                $initial_clone_db_state = array(
                    'name' => $table['name'],
                    'offset' => 0,
                    'structure' => 'pending',
                    'data_eof' => $table['table_type'] == 'VIEW' ? true : false,
                    'total_rows' => $table['total_rows'],//this value might change is site is LIVE(but no problem, we using for some approx calculations)
                    'table_type' => $table['table_type'],
                    'status' => 'pending'
                );
                array_push($filtered_prod_tables, $initial_clone_db_state);
            }
            $this->state['data']['remote_clone_db_state'] = array();
            $this->state['data']['remote_clone_db_state']['clone_status'] = 'pending';//'pending', 'initiated', 'paused', 'completed', 'error', 'running' ('running' only used in prod)
            $this->state['data']['remote_clone_db_state']['clone_table_details'] = $filtered_prod_tables;
            $this->save_state();
        }
        $remote_clone_db_state = $this->state['data']['remote_clone_db_state'];

        if(empty($remote_clone_db_state) || empty($remote_clone_db_state['clone_status']) || empty($remote_clone_db_state['clone_table_details'])){
            throw new wpmerge_exception('remote_clone_db_state_data_missing');
        }

        if($remote_clone_db_state['clone_status'] == 'pending'){
            $body = array(
                'action' => 'wpmerge_prod_db_delta_import',
                'wpmerge_action' => 'initiate_clone_db',
                'clone_db_state_json_64' => base64_encode(json_encode($remote_clone_db_state)),
                'prod_api_key' => wpmerge_dev_get_prod_api_key(),
                'dev_plugin_version' => WPMERGE_VERSION
            );
            $http_args = array(
                'method' => "POST",
                'timeout' => 60,
                'body' => $body
            );
    
            $response = wpmerge_do_call($this->remote_url, $http_args);
            $response_data = wpmerge_get_response_from_json($response);
    
            if(empty($response_data) || !isset($response_data['status'])){
                throw new wpmerge_exception('invalid_response');
            }

            if($response_data['status'] == 'error'){
                throw new wpmerge_exception('prod_response_error', $response_data['error_msg']);
            }
    
            if(empty($response_data['clone_db_state']) || !isset($response_data['clone_db_state']['clone_status'])){
                throw new wpmerge_exception('response_data_missing');
            }

            if($response_data['clone_db_state']['clone_status'] != 'initiated'){
                throw new wpmerge_exception('remote_clone_invalid_status');
            }

            $this->state['data']['remote_clone_db_state'] = $response_data['clone_db_state'];
            $this->save_state();
            return; //next call will continue the cloning
        }
        elseif($remote_clone_db_state['clone_status'] == 'initiated' || $remote_clone_db_state['clone_status'] == 'paused' ){
            $body = array(
                'action' => 'wpmerge_prod_db_delta_import',
                'wpmerge_action' => 'continue_clone_db',
                'prod_api_key' => wpmerge_dev_get_prod_api_key(),
                'dev_plugin_version' => WPMERGE_VERSION
            );
            $http_args = array(
                'method' => "POST",
                'timeout' => 60,
                'body' => $body
            );
    
            $response = wpmerge_do_call($this->remote_url, $http_args);
            $response_data = wpmerge_get_response_from_json($response);
    
            if(empty($response_data) || !isset($response_data['status'])){
                throw new wpmerge_exception('invalid_response');
            }

            if($response_data['status'] == 'error'){
                throw new wpmerge_exception('prod_response_error', $response_data['error_msg']);
            }

            if(empty($response_data['clone_db_state']) || !isset($response_data['clone_db_state']['clone_status'])){
                throw new wpmerge_exception('response_data_missing');
            }

            $this->state['data']['remote_clone_db_state'] = $response_data['clone_db_state'];
            $this->save_state();

            if($response_data['clone_db_state']['clone_status'] == 'completed'){
                $this->update_task_status('remote_clone_db', 'completed');
            }
            elseif($response_data['clone_db_state']['clone_status'] == 'paused'){
                $this->update_task_status('remote_clone_db', 'paused');
            }
            else{
                throw new wpmerge_exception('remote_clone_db_unexpected_status');
            }      
        }
        else{
            throw new wpmerge_exception('remote_clone_db_unexpected_status');
        }
    }

    private function remote_clone_db__get_progress_status(){

        if( empty($this->state['data']['remote_clone_db_state']['clone_table_details']) ){
            return false;
        }

        $clone_table_details =  $this->state['data']['remote_clone_db_state']['clone_table_details'];


        $wp_tables_count = 0;
        $wp_success_tables_count = 0;

        $wp_all_tables_rows_count = 0;
        $success_tables_rows_count = 0;

        if(empty($clone_table_details) || !is_array($clone_table_details)){
            return false;
        }
        foreach($clone_table_details as $key => $table){

            $wp_tables_count++;

            if(isset($table['total_rows'])){
                $wp_all_tables_rows_count += $table['total_rows'];//rows count might change when Prod site is live new records might keep adding or deleted. We will fetch till EOF(end of table rows)
            }//here this should come before if(!isset($table['replace_db_links_data']))


            if( $table['status'] === 'completed' ){
                if(isset($table['total_rows'])){
                    $success_tables_rows_count += $table['total_rows'];
                }
                $wp_success_tables_count++;
                continue;
            }
            elseif( $table['status'] === 'paused' ){

            }
            $success_tables_rows_count += $table['offset'];
        }
        if($wp_tables_count < 1){
            return false;
        }

        $table_count_progress_percent = floor( ($wp_success_tables_count/$wp_tables_count) * 100);

        $rows_count_progress_percent = false;
        if($wp_all_tables_rows_count > 0){
            $rows_count_progress_percent = floor( ($success_tables_rows_count/$wp_all_tables_rows_count) * 100);
        }

        if($rows_count_progress_percent === false){
            $progress_percent = $table_count_progress_percent;
        }
        else{
            $progress_percent = $rows_count_progress_percent;

            if($rows_count_progress_percent > 99 && $table_count_progress_percent < 100){
                $progress_percent = 99;
            }
        }

        return array('percent' => $progress_percent);
    }

    private function remote_finalise_tables(){
        try{

            $this->load_prod_urls();

            if(empty($this->state['data']['remote_clone_db_state']['testing_db_prefix']) || 
            empty($this->state['data']['remote_clone_db_state']['wp_db_prefix']) || 
            empty($this->state['data']['remote_clone_db_state']['tmp_swap_db_prefix'])){
                throw new wpmerge_exception('invalid_db_prefixes');
            }
            
            $testing_db_prefix = $this->state['data']['remote_clone_db_state']['testing_db_prefix'];
            $prod_wp_db_prefix = $this->state['data']['remote_clone_db_state']['wp_db_prefix'];
            $tmp_swap_db_prefix = $this->state['data']['remote_clone_db_state']['tmp_swap_db_prefix'];


            $body = array(
                'action' => 'wpmerge_prod_db_delta_import',
                'wpmerge_action' => 'finalise_tables',
                'testing_db_prefix' => $testing_db_prefix,
                'tmp_swap_db_prefix' => $tmp_swap_db_prefix,
                'wp_db_prefix' => $prod_wp_db_prefix,
                'dev_db_prefix' => $GLOBALS['wpdb']->base_prefix,
                'prod_api_key' => wpmerge_dev_get_prod_api_key(),
                'dev_plugin_version' => WPMERGE_VERSION
            );
            $http_args = array(
                'method' => "POST",
                'timeout' => 60,
                'body' => $body
            );
    
            $response = wpmerge_do_call($this->remote_url, $http_args);
            $response_data = wpmerge_get_response_from_json($response);
    
            if(empty($response_data) || !isset($response_data['status'])){
                throw new wpmerge_exception('invalid_response');
            }

            if($response_data['status'] == 'error'){
                throw new wpmerge_exception('prod_response_error', $response_data['error_msg']);
            }

            if($response_data['status'] == 'success'){
                $this->update_task_status('remote_finalise_tables', 'completed');
                wpmerge_update_option('is_export_dev_db_delta_2_prod_already_done', 1);
                wpmerge_update_option('prod_delta_import_is_atleast_one_query_is_successful', 0);//reset this
            }
            else{
                throw new wpmerge_exception('unexpected_response');
            }
            
        }
        catch(wpmerge_exception $e){
            $error = $e->getError();
            $error_msg = $e->getErrorMsg();

            $result = $this->is_retry_required('remote_finalise_tables', $e);
            if(!$result){
                $this->update_task_status('remote_finalise_tables', 'error', $error, $error_msg, true);
            }

            return false;
        }
    }

    private function remote_run_db_final_modifications(){
        try{
            $this->load_prod_urls();

            $find_replace_data = array();
            $find_replace_data['old_url'] = get_site_url();
            $find_replace_data['old_file_path'] = ABSPATH;

            $body = array(
                'action' => 'wpmerge_prod_db_delta_import',
                'wpmerge_action' => 'run_db_final_modifications',
                'dev_site_url' => get_site_url(),
                'dev_db_prefix' => $GLOBALS['wpdb']->base_prefix,
                'prod_api_key' => wpmerge_dev_get_prod_api_key(),
                'dev_plugin_version' => WPMERGE_VERSION
            );
            $http_args = array(
                'method' => "POST",
                'timeout' => 60,
                'body' => $body
            );
    
            $response = wpmerge_do_call($this->remote_url, $http_args);
            $response_data = wpmerge_get_response_from_json($response);
    
            if(empty($response_data) || !isset($response_data['status'])){
                throw new wpmerge_exception('invalid_response');
            }
    
            if($response_data['status'] == 'error'){
                throw new wpmerge_exception('prod_response_error', $response_data['error_msg']);
            }

            $this->update_task_status('remote_run_db_final_modifications', 'completed');
        }
        catch(wpmerge_exception $e){
            $error = $e->getError();
            $error_msg = $e->getErrorMsg();

            $result = $this->is_retry_required('remote_run_db_final_modifications', $e);
            if(!$result){
                $this->update_task_status('remote_run_db_final_modifications', 'error', $error, $error_msg, true);
            }

            return false;
        }
    }

    private function delete_prod_bridge(){
           try{
            $this->load_prod_urls();
            $body = array(
                'action' => 'wpmerge_prod_db_delta_import',
                'wpmerge_action' => 'delete_prod_bridge',
                'prod_api_key' => wpmerge_dev_get_prod_api_key(),
                'dev_plugin_version' => WPMERGE_VERSION
            );
            $http_args = array(
                'method' => "POST",
                'timeout' => 10,//10 - is enough to bring server info
                'body' => $body
            );

            $response = wpmerge_do_call($this->remote_url, $http_args);
            //wpmerge_debug::log($response, 'response');
            $response = wpmerge_get_response_from_json($response);

            if(empty($response)){
                throw new wpmerge_exception('invalid_response');
            }
        }
        catch(wpmerge_exception $e){
            $error = $e->getError();
            $error_msg = $e->getErrorMsg();

            if(!$result){
                $this->update_task_status('delete_prod_bridge', 'error', $error, $error_msg, true);
            }

            return false;
        }

        $this->update_task_status('delete_prod_bridge', 'completed');
        //$this->update_overall_task_status('completed');
    }

    private function get_all_files_meta(){
        if(!isset($this->state['data']['files_meta'])){
            $this->state['data']['files_meta'] = array();
            $this->state['data']['get_all_files_meta'] = array();
            $this->state['data']['get_all_files_meta']['eof'] = false;
            $this->state['data']['get_all_files_meta']['offset'] = 0;
            $this->save_state();
        }
        $get_all_files_meta_options = &$this->state['data']['get_all_files_meta'];

        $exim_request = array(
            'action' => 'getDirMeta',
            'offset' => $get_all_files_meta_options['offset']
        );
        
        include_once(WPMERGE_PATH.'/includes/common_exim.php');
        $common_exim_obj = new wpmerge_common_exim($exim_request);
        $response = $common_exim_obj->getResponse();
        try{
            if($response === null){
                //if required use json_last_error()
                throw new wpmerge_exception('invalid_response_json_failed');
            }            
            if(!isset($response['code'])){
                throw new wpmerge_exception('invalid_response_code');
            }
            if($response['code'] < 200){
                $error_msg = empty($response['message']) ? 'empty_error_msg' : $response['message'];
                throw new wpmerge_exception('get_files_meta_error', $error_msg);
            }
            if(!isset($response['value']['files'])){
                throw new wpmerge_exception('invalid_response_code');
            }

            $this->state['data']['files_meta'] = array_merge($this->state['data']['files_meta'], $response['value']['files']);//If file list is huge we may encounter mysql allow max packet limit issue. Need to improve it LATER

            if($response['value']['eof'] === true){
                $get_all_files_meta_options['eof'] = true;
                $get_all_files_meta_options['offset'] = 0;

            }elseif($response['value']['eof'] === false){
                $get_all_files_meta_options['eof'] = false;
                $get_all_files_meta_options['offset'] = $response['value']['offset'];
            }
            else{
                throw new wpmerge_exception('invalid_eof');
            }
        }
        catch(wpmerge_exception $e){
            $error = $e->getError();
            $error_msg = $e->getErrorMsg();

            $result = $this->is_retry_required('get_all_files_meta', $e);
            if(!$result){
                $this->update_task_status('get_all_files_meta', 'error', $error, $error_msg, true);
            }

            return false;
        }
        $this->save_state();

        if($response['value']['eof'] === true){
            $this->update_task_status('get_all_files_meta', 'completed');
        }
        else{
            $this->update_task_status('get_all_files_meta', 'paused');
        }
    }

    private function get_all_changed_files(){
        require WPMERGE_PATH . '/includes/file_iterator.php';

        try{
            $iterator = new wpmerge_file_iterator();
            $response = $iterator->check_changed_files(ABSPATH, $this->state);
        } catch(wpmerge_exception $e){
            wpmerge_debug::log($e->getError(), '-----------getError----------------');
            if ($e->getError() === 'file_iteration_timedout') {
                $this->update_task_status('get_all_changed_files', 'paused');
                return ;
            }
        }

        $this->save_state();

        $this->update_task_status('get_all_changed_files', 'completed');
    }

    private function zip_changed_files(){
         wpmerge_debug::log(__FUNCTION__,'-----------zip_changed_files----------------');
        try{
            require WPMERGE_PATH . '/includes/common_zip.php';
            new wpmerge_zip();
        }  catch(wpmerge_exception $e){
            wpmerge_debug::log('', $e->getError());
             if ($e->getError() === 'timedout') {
                $this->update_task_status('zip_changed_files', 'paused');
             } else if ($e->getError() === 'finished') {
                $this->update_task_status('zip_changed_files', 'completed');
                //$this->update_overall_task_status('completed');
             } else {
                $this->update_task_status('zip_changed_files', 'error', $e->getError(), '', true);
             }
        }
    }

    public function apply_changes_for_dev_in_dev_pre_check(){
        //if this check gives error, we will save time for doing db modifications, thats why it is used
        try{

            if(wpmerge_dev_is_changes_applied_in_dev()){
                throw new wpmerge_exception("changes_already_applied_import_db_again");
            }

            $log_queries_table = $GLOBALS['wpdb']->base_prefix .'wpmerge_log_queries';

            $total_rows = $GLOBALS['wpdb']->get_var("SELECT COUNT(id) FROM `".$log_queries_table."` WHERE `is_record_on` = '1' AND `type` = 'query'");

            if(empty($total_rows)){
                throw new wpmerge_exception('no_changes_to_apply');//'invalid_rows_count'
            }
           
            $this->update_task_status('apply_changes_for_dev_in_dev_pre_check', 'completed');
  
        }
        catch(wpmerge_exception $e){
            $error = $e->getError();
            $error_msg = $e->getErrorMsg();
            $this->update_task_status('apply_changes_for_dev_in_dev_pre_check', 'error', $error, $error_msg, true);
            return false;
        }
    }

    public function do_db_modification(){
        try{
            if(!isset($this->state['data']['do_db_modification_state'])){
                $is_continue = false;
                $this->state['data']['do_db_modification_state'] = array(
                    'call_count' => 0
                );                
            }
            else{
                $is_continue = true;
                $this->state['data']['do_db_modification_state']['call_count']++;
            }
            $this->save_state();            

            $wpmerge_dev_db_obj = new wpmerge_dev_db();
            $response = $wpmerge_dev_db_obj->do_modifications($is_continue);
            if($response === true){
                $this->update_task_status('do_db_modification', 'completed');
                if($this->state['overall_task'] === 'do_db_modification_in_dev'){
                    //$this->update_overall_task_status('completed');
                }
            }
            elseif($response === 'continue'){
                $this->update_task_status('do_db_modification', 'paused');
            }
            else{
                throw new wpmerge_exception("unexpected_response");
            }            
        }
        catch(wpmerge_exception $e){
            $error = $e->getError();
            $error_msg = $e->getErrorMsg();
            $this->update_task_status('do_db_modification', 'error', $error, $error_msg, true);
            return false;
        }
    }

    public function do_apply_changes_for_dev_in_dev(){
        try{
            if(!isset($this->state['data']['offset'])){
                $this->state['data']['offset'] = 0;
                $this->state['data']['last_query_id'] = 0;
                $this->state['data']['eof'] = false;

                $table_log_queries = $GLOBALS['wpdb']->base_prefix .'wpmerge_log_queries';
                $total_rows = $GLOBALS['wpdb']->get_var("SELECT COUNT(id) FROM `".$table_log_queries."` WHERE `is_record_on` = '1' AND `type` = 'query'");
                $total_rows = is_numeric($total_rows) ? (int) $total_rows : '';
                if(empty($total_rows)){
                    throw new wpmerge_exception('no_changes_to_apply');//'invalid_rows_count'
                }

                $this->state['data']['total'] = $total_rows;
                $this->save_state();
            }
            while($this->state['data']['eof'] === false && !wpmerge_is_time_limit_exceeded()){
                $options = array();
                $options['offset'] = $this->state['data']['offset'];
                $options['total'] = $this->state['data']['total'];
                $options['last_query_id'] = $this->state['data']['last_query_id'];

                $deploy = new wpmerge_dev_deploy();
                $res = $deploy->apply_changes_for_dev_in_dev($options);

                $this->state['data']['offset'] = $res['offset'];
                $this->state['data']['last_query_id'] = $res['last_query_id'];
                if($res['eof']){
                    $this->state['data']['offset'] = 0;
                    $this->state['data']['last_query_id'] = 0;
                    $this->state['data']['eof'] = true;
                }
                $this->save_state();
            }
            wpmerge_debug::printr(wpmerge_select_by_memory_limit::get_all_perform_data(), 'all_perform_data');

            if($this->state['data']['eof'] === true){
                $this->update_task_status('do_apply_changes_for_dev_in_dev', 'completed');
                
                //turn on recording if last recording state is on
                $last_recording_state = wpmerge_get_option('last_recording_state');
                if( $last_recording_state ){
                    wpmerge_update_option('is_recording_on', 1);//try turning on recording after syncing user want to continue developement. According to wpmerge_dev_db::get_recording_state() this will go through
                }
                
                //$this->update_overall_task_status('completed');
            }
            else{
                $this->update_task_status('do_apply_changes_for_dev_in_dev', 'paused');
            }

        }
        catch(wpmerge_exception $e){
            $error = $e->getError();
            $error_msg = $e->getErrorMsg();
            $this->update_task_status('do_apply_changes_for_dev_in_dev', 'error', $error, $error_msg, true);
            return false;
        }
    }

    private function do_apply_changes_for_dev_in_dev__get_progress_status(){
        if(!isset($this->state['data']['offset'])){
            return false;
        }

        $offset = $this->state['data']['offset'];
        $eof = $this->state['data']['eof'];
        $total = $this->state['data']['total'];
        if($total < 1){
            return false;
        }
        if($eof === true){
            $progress_percent = 100;
        }
        else{
            $progress_percent = floor( ($offset/$total) * 100);
        }
        return array('percent' => $progress_percent);
    }

    public function do_apply_changes_for_prod_in_dev(){
        try{
            if(!isset($this->state['data']['offset'])){
                $this->state['data']['offset'] = 0;
                $this->state['data']['last_query_id'] = 0;
                $this->state['data']['eof'] = false;

                $table_log_queries = $GLOBALS['wpdb']->base_prefix .'wpmerge_log_queries';
                $total_rows = $GLOBALS['wpdb']->get_var("SELECT COUNT(id) FROM `".$table_log_queries."` WHERE `is_record_on` = '1' AND `type` = 'query'");
                $total_rows = is_numeric($total_rows) ? (int) $total_rows : '';
                if(empty($total_rows)){
                    throw new wpmerge_exception('no_changes_to_apply');//'invalid_rows_count'
                }
                $this->state['data']['total'] = $total_rows;
                $this->save_state();
            }
            while($this->state['data']['eof'] === false && !wpmerge_is_time_limit_exceeded()){
                $options = array();
                $options['offset'] = $this->state['data']['offset'];
                $options['total'] = $this->state['data']['total'];
                $options['last_query_id'] = $this->state['data']['last_query_id'];

                $deploy = new wpmerge_dev_deploy();
                $res = $deploy->apply_changes_for_prod_in_dev($options);

                $this->state['data']['offset'] = $res['offset'];
                $this->state['data']['last_query_id'] = $res['last_query_id'];
                if($res['eof'] === true){
                    $this->state['data']['offset'] = 0;
                    $this->state['data']['last_query_id'] = 0;
                    $this->state['data']['eof'] = true;
                }
                $this->save_state();
            }
            wpmerge_debug::printr(wpmerge_select_by_memory_limit::get_all_perform_data(), 'all_perform_data');

            if($this->state['data']['eof'] === true){
                $this->update_task_status('do_apply_changes_for_prod_in_dev', 'completed');
                //$this->update_overall_task_status('completed');
            }
            else{
                $this->update_task_status('do_apply_changes_for_prod_in_dev', 'paused');
            }
        }
        catch(wpmerge_exception $e){
            $error = $e->getError();
            $error_msg = $e->getErrorMsg();
            $this->update_task_status('do_apply_changes_for_prod_in_dev', 'error', $error, $error_msg, true);
            return false;
        }

    }

    private function do_apply_changes_for_prod_in_dev__get_progress_status(){
        if(!isset($this->state['data']['offset'])){
            return false;
        }

        $offset = $this->state['data']['offset'];
        $eof = $this->state['data']['eof'];
        $total = $this->state['data']['total'];
        if($total < 1){
            return false;
        }
        if($eof === true){
            $progress_percent = 100;
        }
        else{
            $progress_percent = floor( ($offset/$total) * 100);
        }
        return array('percent' => $progress_percent);
    }

    public function do_decode_encoded_logged_queries(){
        /*how php does base64_decode can be different from how mysql does. So doing it in php.

        The following lines from mysql website 
        https://dev.mysql.com/doc/refman/5.7/en/string-functions.html#function_to-base64

            Different base-64 encoding schemes exist. These are the encoding and decoding rules used by TO_BASE64() and FROM_BASE64():

            The encoding for alphabet value 62 is '+'.

            The encoding for alphabet value 63 is '/'.

            Encoded output consists of groups of 4 printable characters. Each 3 bytes of the input data are encoded using 4 characters. If the last group is incomplete, it is padded with '=' characters to a length of 4.

            A newline is added after each 76 characters of encoded output to divide long output into multiple lines.

            Decoding recognizes and ignores newline, carriage return, tab, and space.
        */

        try{

            if( !isset($this->state['data']['do_decode_encoded_logged_queries_state']) ){
                if( !isset($this->state['data']['overall_task_options']) || empty($this->state['data']['overall_task_options']) || empty($this->state['data']['overall_task_options']) ){
                    throw new wpmerge_exception('invalid_request');
                }
                $overall_task_options = $this->state['data']['overall_task_options'];
                $this->state['data']['do_decode_encoded_logged_queries_state'] = array();
                $this_task_state = &$this->state['data']['do_decode_encoded_logged_queries_state'];
                $this_task_state['options'] = array();
                
                if(isset($overall_task_options['which'])){
                    if($overall_task_options['which'] == 'all' || $overall_task_options['which'] == 'undecoded'){
                        $this_task_state['options']['which'] = $overall_task_options['which'];
                    }
                    else{
                        throw new wpmerge_exception('invalid_request');
                    }
                }

                if(isset($overall_task_options['range_min']) && !empty($overall_task_options['range_min'])){
                    if(!is_numeric($overall_task_options['range_min'])){
                        throw new wpmerge_exception('invalid_request');
                    }
                    $this_task_state['options']['range_min'] = $overall_task_options['range_min'];
                }

                if(isset($overall_task_options['range_max']) && !empty($overall_task_options['range_max'])){
                    if(!is_numeric($overall_task_options['range_max'])){
                        throw new wpmerge_exception('invalid_request');
                    }
                    $this_task_state['options']['range_max'] = $overall_task_options['range_max'];
                }
                $this->save_state();
            }

            $this_task_state = &$this->state['data']['do_decode_encoded_logged_queries_state'];
            if(empty($this_task_state)){
                throw new wpmerge_exception('data_missing');
            }

            $sql_where_append = "`is_record_on` = '1' AND `type` = 'query' AND `query_b` IS NOT NULL";

            if($this_task_state['options']['which'] == 'undecoded'){
                $sql_where_append .= ' AND `query` IS NULL ';//assumming `query_b` value is present
            }

            if( isset($this_task_state['options']['range_min']) ){
                $sql_where_append .= " AND `id` >= '".$this_task_state['options']['range_min']."' ";
            }

            if( isset($this_task_state['options']['range_max']) ){
                $sql_where_append .= " AND `id` <= '".$this_task_state['options']['range_max']."' ";
            }

            $table_log_queries = $GLOBALS['wpdb']->base_prefix .'wpmerge_log_queries';
            $row_limit = 5000;

            if(!isset($this_task_state['progress_state'])){
                $this_task_state['progress_state'] = array();
                $this_task_state['progress_state']['offset'] = 0;
                $this_task_state['progress_state']['last_query_id'] = 0;
                $this_task_state['progress_state']['eof'] = false;                

                $total_rows = $GLOBALS['wpdb']->get_var("SELECT COUNT(id) FROM `".$table_log_queries."` WHERE ".$sql_where_append);
                $total_rows = is_numeric($total_rows) ? (int) $total_rows : '';
                if(empty($total_rows)){
                    throw new wpmerge_exception('no_rows_found_for_decoding');//'invalid_rows_count'
                }
                $this_task_state['progress_state']['total'] = $total_rows;
                $this->save_state();
            }

            $total_rows = $this_task_state['progress_state']['total'];
            $offset = $this_task_state['progress_state']['offset'];
            $last_query_id = $this_task_state['progress_state']['last_query_id'];
            
            while($total_rows > $offset && !wpmerge_is_time_limit_exceeded()){
                $q_offset = $offset;

                if(isset($last_query_id)){
                    $q_offset = 0;
                    $sql_where_append .= " AND `id` > $last_query_id";//to increase the efficiency for a million rows+ when rows and offset searching becomes big.
                }

                $select_args = array();
                $select_args['columns'] = '*';
                $select_args['table'] = $table_log_queries;
                $select_args['where'] = "".$sql_where_append;
                $select_args['order'] = "id ASC";
                $select_args['limit'] = $row_limit;
                $select_args['offset'] = $q_offset;
                $select_args['result_format'] = ARRAY_A;
                $select_args['optimize_query'] = isset($last_query_id) ? false : true;

                $select_by_memory_limit_obj = new wpmerge_select_by_memory_limit($select_args);
                $db_delta_rows = $select_by_memory_limit_obj->process_and_get_results();

                if(empty($db_delta_rows)){
                    throw new wpmerge_exception('no_rows_returned'); 
                }

                foreach($db_delta_rows as $db_delta_row){
                    if(empty($db_delta_row['query_b'])){
                        $last_query_id = $db_delta_row['id'];
                        $offset++;
                        continue;
                    }

                    $decoded_query = wpmerge_base64_decode_query($db_delta_row['query_b']);
                    $update_log_queries = array('query' => $decoded_query);
                    $GLOBALS['wpdb']->update($table_log_queries, $update_log_queries, array('id' => $db_delta_row['id']));

                    $last_query_id = $db_delta_row['id'];
                    $offset++;

                    if( wpmerge_is_time_limit_exceeded() ){
                        break;
                    }
                }
                $this->save_state();
            }
            wpmerge_debug::printr(wpmerge_select_by_memory_limit::get_all_perform_data(), 'all_perform_data');

            $this_task_state['progress_state']['last_query_id'] = $last_query_id;
            $this_task_state['progress_state']['offset'] = $offset;
            $this->save_state();

            if($total_rows <= $offset){
                $this->update_task_status('do_decode_encoded_logged_queries', 'completed');
            }
            else{
                $this->update_task_status('do_decode_encoded_logged_queries', 'paused');
            }
        }
        catch(wpmerge_exception $e){
            $error = $e->getError();
            $error_msg = $e->getErrorMsg();
            $this->update_task_status('do_decode_encoded_logged_queries', 'error', $error, $error_msg, true);
            return false;
        }
    }

    private function do_decode_encoded_logged_queries__get_progress_status(){
        $task_state = $this->state['data']['do_decode_encoded_logged_queries_state'];
        if(!isset($task_state['progress_state']['offset'])){
            return false;
        }

        $offset = $task_state['progress_state']['offset'];
        $eof = $task_state['progress_state']['eof'];
        $total = $task_state['progress_state']['total'];
        if($total < 1){
            return false;
        }
        if($eof === true){
            $progress_percent = 100;
        }
        else{
            $progress_percent = floor( ($offset/$total) * 100);
        }
        return array('percent' => $progress_percent);
    }

    public function do_remove_decoded_logged_queries(){
        try{
            $limit = 5000;
            
            do{
                $table_log_queries = $GLOBALS['wpdb']->base_prefix .'wpmerge_log_queries';
                $GLOBALS['wpdb']->query("update `".$table_log_queries."` SET `query` = NULL WHERE `is_record_on` = '1' AND `type` = 'query' AND `query_b` IS NOT NULL AND `query_b` != '' AND `query` IS NOT NULL LIMIT $limit");
                $rows_affected = $GLOBALS['wpdb']->rows_affected;
            }
            while($rows_affected > 0 && !wpmerge_is_time_limit_exceeded());

            if($rows_affected > 0){
                $this->update_task_status('do_remove_decoded_logged_queries', 'paused');
            }
            else{
                $this->update_task_status('do_remove_decoded_logged_queries', 'completed');
            }
        }
        catch(wpmerge_exception $e){
            $error = $e->getError();
            $error_msg = $e->getErrorMsg();
            $this->update_task_status('do_remove_decoded_logged_queries', 'error', $error, $error_msg, true);
            return false;
        }
    }

    private function db_list_tables(){
        try{
            $exim_request = array('action' => 'listTables');
            include_once(WPMERGE_PATH.'/includes/common_exim.php');
            $common_exim_obj = new wpmerge_common_exim($exim_request);
            $response_data = $common_exim_obj->getResponse();

            if(!isset($response_data['code'])){
                throw new wpmerge_exception('invalid_response_code');
            }
            if($response_data['code'] < 200){
                $error_msg = empty($response_data['message']) ? 'empty_error_msg' : $response_data['message'];
                throw new wpmerge_exception('db_backup_error', $error_msg);
            }
            if($response_data['code'] >= 200){//this is not http code, this is application status code
                //all ok
                if(!isset($response_data['value']) || !isset($response_data['value']['tables']) || empty($response_data['value']['tables'])){

                    return false;//throw imrpove later
                }
                $this->state['data']['dev_db_tables'] = $response_data['value']['tables'];
                $this->save_state();
                $this->update_task_status('list_dev_db_tables', 'completed');
            }
        }
        catch(wpmerge_exception $e){
            $error = $e->getError();
            $error_msg = $e->getErrorMsg();

            $result = $this->is_retry_required('list_dev_db_tables', $e);
            if(!$result){
                $this->update_task_status('list_dev_db_tables', 'error', $error, $error_msg, true);
            }
            
            return false;
        }
    }

    private function do_fix_db_serialization_in_dev(){
        try{
            $this->_do_fix_db_serialization_in_dev();
        }
        catch(wpmerge_exception $e){
            $error = $e->getError();
            $error_msg = $e->getErrorMsg();
            $this->update_task_status('do_fix_db_serialization_in_dev', 'error', $error, $error_msg, true);
            return false;
        }
    }

    private function _do_fix_db_serialization_in_dev(){

        if( empty($this->state['data']) || empty($this->state['data']['dev_db_tables']) ){
            throw new wpmerge_exception('fix_db_serialization_in_dev_data_missing');
        }

        $whitelist = array('name', 'current_offset');
        $wp_tables_count = 0;
        $wp_task_completed_table_count = 0;

        $tables = $this->state['data']['dev_db_tables'];
        end($tables);
        $last_key = key($tables);
        reset($tables);
        $last_key_triggered = false;

        foreach($this->state['data']['dev_db_tables'] as $key =>  &$table){
            if($key === $last_key){
                $last_key_triggered = true;
            }
            if($table['is_wp_table'] === false){
                continue;
            }
            $wp_tables_count++;

            if(!isset($table['fix_db_serialization_data'])){
                $table_fix_state = array(
                    'name' => $table['name'],
                    'current_offset' => 0,
                    'eof' => false,
                    'status' => 'pending'
                );
                $table['fix_db_serialization_data'] = $table_fix_state;
                $this->save_state();
            }

            if( $table['fix_db_serialization_data']['status'] === 'completed' ){
                $wp_task_completed_table_count++;
                continue;
            }
            if( in_array($table['fix_db_serialization_data']['status'], array('pending', 'paused')) ){
                while($table['fix_db_serialization_data']['eof'] === false && !wpmerge_is_time_limit_exceeded()){
                
                    $table_fix_state = $table['fix_db_serialization_data'];
                    $table_fix_state_args = array_intersect_key( $table_fix_state, array_flip( $whitelist ) );

                    include_once(WPMERGE_PATH.'/includes/common_fix_db_serialization.php');

                    $fix_db_serialization_obj = new wpmerge_fix_db_serialization();

                    $response = $fix_db_serialization_obj->fix_table( $table_fix_state_args);

                    if(!isset($response['eof']) || !is_bool($response['eof'])){
                        throw new wpmerge_exception('invalid_response');
                    }

                    if($response['eof'] === true){
                        $table['fix_db_serialization_data']['status'] = 'completed';
                        $table['fix_db_serialization_data']['eof'] = true;
                        $table['fix_db_serialization_data']['current_offset'] = 0;
                    }
                    else{
                        $table['fix_db_serialization_data']['status'] = 'paused';
                        $table['fix_db_serialization_data']['eof'] = false;
                        $table['fix_db_serialization_data']['current_offset'] = $response['current_offset'];
                    }
                    $this->save_state();
                }
            }
            if(wpmerge_is_time_limit_exceeded()){
                break;
            }
        }
        wpmerge_debug::printr(wpmerge_select_by_memory_limit::get_all_perform_data(), 'all_perform_data');
        if($last_key_triggered && $wp_tables_count > 0 && $wp_tables_count === $wp_task_completed_table_count ){
            //mark task as completed
            $this->update_task_status('do_fix_db_serialization_in_dev', 'completed');
            $this->save_state();
            return true;
        }
        else{
            $this->update_task_status('do_fix_db_serialization_in_dev', 'paused');
        }
    }

    private function remote_fix_db_serialization(){
        try{
            $this->do_remote_fix_db_serialization();
        }
        catch(wpmerge_exception $e){
            $error = $e->getError();
            $error_msg = $e->getErrorMsg();

            $result = $this->is_retry_required('remote_fix_db_serialization', $e);
            if(!$result){
                $this->update_task_status('remote_fix_db_serialization', 'error', $error, $error_msg, true);
            }
            return false;
        }
    }

    public function do_remote_fix_db_serialization(){

        $this->load_prod_urls();

        if( empty($this->state['data']) || empty($this->state['data']['prod_db_tables']) ){
            throw new wpmerge_exception('remote_fix_db_serialization_data_missing');
        }

        if(empty($this->state['data']['prod_db_tables'])){
            throw new wpmerge_exception('prod_db_tables_list_missing');
        }

        if(!isset($this->state['data']['remote_fix_db_serialization_data'])){
            $filtered_prod_tables = array();
            foreach($this->state['data']['prod_db_tables'] as $key =>  $table){
                if($table['is_wp_table'] === false){
                    continue;
                }
                $initial_table_fix_state = array(
                    'name' => $table['name'],
                    'current_offset' => 0,
                    'eof' => false,
                    'status' => 'pending'
                );
                array_push($filtered_prod_tables, $initial_table_fix_state);
            }
            $this->state['data']['remote_fix_db_serialization_data'] = $filtered_prod_tables;
            $this->save_state();
        }
        $tables_fix_state = $this->state['data']['remote_fix_db_serialization_data'];

        if(empty($tables_fix_state)){
            throw new wpmerge_exception('remote_fix_db_serialization_data_missing');
        }

        if($this->is_remote_fix_db_serialization_all_tables_completed($tables_fix_state)){
            $this->update_task_status('remote_fix_db_serialization', 'completed');
            return true;
        }

        $tables_fix_state_json = json_encode($tables_fix_state);

        $body = array(
            'action' => 'wpmerge_prod_db_delta_import',
            'wpmerge_action' => 'fix_db_serialization',
            'tables_fix_state_json' => $tables_fix_state_json,
            'prod_api_key' => wpmerge_dev_get_prod_api_key(),
            'dev_plugin_version' => WPMERGE_VERSION
        );
        $http_args = array(
            'method' => "POST",
            'timeout' => 60,
            'body' => $body
        );

        $response = wpmerge_do_call($this->remote_url, $http_args);
        $response_data = wpmerge_get_response_from_json($response);

        if(empty($response_data) || !isset($response_data['status'])){
            throw new wpmerge_exception('invalid_response');
        }

        if($response_data['status'] == 'error'){
            throw new wpmerge_exception('prod_response_error', $response_data['error_msg']);
        }

        if(!isset($response_data['tables_fix_state']) || empty($response_data['tables_fix_state'])){
            throw new wpmerge_exception('response_data_missing');
        }

        $this->state['data']['remote_fix_db_serialization_data'] = $response_data['tables_fix_state'];

        $this->save_state();

        if($this->is_remote_fix_db_serialization_all_tables_completed($response_data['tables_fix_state'])){
            $this->update_task_status('remote_fix_db_serialization', 'completed');
        }
        else{
            $this->update_task_status('remote_fix_db_serialization', 'paused');
        }
    }

    private function is_remote_fix_db_serialization_all_tables_completed($tables_fix_state){
        $table_count = 0;
        $completed_table_count = 0;
        foreach($tables_fix_state as $key => $remote_table){
            $table_count++;
            //right now no errors involved in the process so no error checking
            if($remote_table['status'] === 'completed'){
                $completed_table_count++;
            }
        }
        if(!$table_count){
            return false;//something wrong better return false
        }
        if($table_count > 0 && $table_count === $completed_table_count){
            return true;
        }
        return false;
    }

    private function do_fix_db_serialization_in_dev__get_progress_status(){
        $tables = $this->state['data']['dev_db_tables'];

        $wp_tables_count = 0;
        $wp_success_tables_count = 0;

        $wp_all_tables_rows_count = 0;
        $success_tables_rows_count = 0;


        if(empty($tables) || !is_array($tables)){
            return false;
        }
        foreach($tables as $key => $table){

            if($table['is_wp_table'] === false){
                continue;
            }
            $wp_tables_count++;

            if(isset($table['total_rows'])){
                $wp_all_tables_rows_count += $table['total_rows'];//rows count might change when Dev site is live new records might keep adding or deleted. We will fetch till EOF(end of table rows)
            }//here this should come before if(!isset($table['fix_db_serialization_data']))

            if(!isset($table['fix_db_serialization_data'])){               
               continue;
            }

            if( $table['fix_db_serialization_data']['status'] === 'completed' ){
                if(isset($table['total_rows'])){
                    $success_tables_rows_count += $table['total_rows'];
                }
                $wp_success_tables_count++;
                continue;
            }
            elseif( $table['fix_db_serialization_data']['status'] === 'paused' ){

            }
            $success_tables_rows_count += $table['fix_db_serialization_data']['current_offset'];
        }
        if($wp_tables_count < 1){
            return false;
        }

        $table_count_progress_percent = floor( ($wp_success_tables_count/$wp_tables_count) * 100);

        $rows_count_progress_percent = false;
        if($wp_all_tables_rows_count > 0){
            $rows_count_progress_percent = floor( ($success_tables_rows_count/$wp_all_tables_rows_count) * 100);
        }

        if($rows_count_progress_percent === false){
            $progress_percent = $table_count_progress_percent;
        }
        else{
            $progress_percent = $rows_count_progress_percent;

            if($rows_count_progress_percent > 99 && $table_count_progress_percent < 100){
                $progress_percent = 99;
            }
        }

        return array('percent' => $progress_percent);
    }


    private function remote_fix_db_serialization__get_progress_status(){
        $tables = $this->state['data']['prod_db_tables'];

        $wp_tables_count = 0;
        $wp_success_tables_count = 0;

        $wp_all_tables_rows_count = 0;
        $success_tables_rows_count = 0;

        if(empty($tables) || !is_array($tables)){
            return false;
        }
        foreach($tables as $key => $table){

            if($table['is_wp_table'] === false){
                continue;
            }
            $wp_tables_count++;

            if(isset($table['total_rows'])){
                $wp_all_tables_rows_count += $table['total_rows'];//rows count might change when Prod site is live new records might keep adding or deleted. We will fetch till EOF(end of table rows)
            }//here this should come before if(!isset($table['replace_db_links_data']))

            if(!isset($table['remote_fix_db_serialization_data'])){               
               continue;
            }

            if( $table['remote_fix_db_serialization_data']['status'] === 'completed' ){
                if(isset($table['total_rows'])){
                    $success_tables_rows_count += $table['total_rows'];
                }
                $wp_success_tables_count++;
                continue;
            }
            elseif( $table['remote_fix_db_serialization_data']['status'] === 'paused' ){

            }
            $success_tables_rows_count += $table['remote_fix_db_serialization_data']['current_offset'];
        }
        if($wp_tables_count < 1){
            return false;
        }

        $table_count_progress_percent = floor( ($wp_success_tables_count/$wp_tables_count) * 100);

        $rows_count_progress_percent = false;
        if($wp_all_tables_rows_count > 0){
            $rows_count_progress_percent = floor( ($success_tables_rows_count/$wp_all_tables_rows_count) * 100);
        }

        if($rows_count_progress_percent === false){
            $progress_percent = $table_count_progress_percent;
        }
        else{
            $progress_percent = $rows_count_progress_percent;

            if($rows_count_progress_percent > 99 && $table_count_progress_percent < 100){
                $progress_percent = 99;
            }
        }

        return array('percent' => $progress_percent);
    }

    public function remote_disable_maintenance_mode_direct_call(){
        try{
            $this->load_prod_urls();

            $body = array(
                'action' => 'wpmerge_prod_db_delta_import',
                'wpmerge_action' => 'disable_maintenance_mode',
                'prod_api_key' => wpmerge_dev_get_prod_api_key(),
                'dev_plugin_version' => WPMERGE_VERSION
            );
            $http_args = array(
                'method' => "POST",
                'timeout' => 60,
                'body' => $body
            );

            $response = wpmerge_do_call($this->remote_url, $http_args);
            $response_data = wpmerge_get_response_from_json($response);

            if(empty($response_data) || !isset($response_data['status'])){
                throw new wpmerge_exception('invalid_response');
            }

            if($response_data['status'] == 'error'){
                throw new wpmerge_exception('prod_response_error', $response_data['error_msg']);
            }
        }
        catch(wpmerge_exception $e){
            $error = $e->getError();
            $error_msg = $e->getErrorMsg();
        }
    }


    //=====================================================================>

    public function initiate_prod_import_db(){
        //need to validate exisiting prod_import_db or current staging db state wheather importable LATER
        //validate();

        $this->current_overall_task = 'import_prod_db';

        $default = $this->import_prod_db_state_default;
        //do default and overlap merging if required
        $this->state = $default;
        $tmp_time = microtime(true);
        $this->state['time_added'] = $tmp_time;
        $this->state['time_updated'] = $tmp_time;
        $this->save_state();

        $state = $this->state;
        unset($state['data']);
        return $state;
    }

    public function initiate_export_dev_db_delta_2_prod(){
        $this->current_overall_task = 'export_dev_db_delta_2_prod';

        $default = $this->export_dev_db_delta_2_prod_state_default;
        //do default and overlap merging if required

        $this->state = $default;
        $tmp_time = microtime(true);
        $this->state['time_added'] = $tmp_time;
        $this->state['time_updated'] = $tmp_time;
        $this->save_state();

        $state = $this->state;
        unset($state['data']);
        return $state;
    }

    public function initiate_export_changed_files_in_dev(){
        $this->current_overall_task = 'export_changed_files_in_dev';

        $default = $this->export_changed_files_in_dev_state_default;
        //do default and overlap merging if required

        $this->state = $default;
        $min_mtime = wpmerge_get_option('dev_min_mtime');
        if(empty($min_mtime)){
            return false;//improve error later
        }
        $this->state['data']['min_mtime'] = $min_mtime;
        $tmp_time = microtime(true);
        $this->state['time_added'] = $tmp_time;
        $this->state['time_updated'] = $tmp_time;
        $this->save_state();

        $state =  $this->state;
        unset($state['data']);
        return $state;
    }

    public function initiate_apply_changes_for_prod_in_dev(){
        $this->current_overall_task = 'apply_changes_for_prod_in_dev';

        $default = $this->apply_changes_for_prod_in_dev_state_default;
        //do default and overlap merging if required

        $this->state = $default;        
        $tmp_time = microtime(true);
        $this->state['time_added'] = $tmp_time;
        $this->state['time_updated'] = $tmp_time;
        $this->save_state();

        $state =  $this->state;
        unset($state['data']);
        return $state;
    }

    public function initiate_apply_changes_for_dev_in_dev(){
        $this->current_overall_task = 'apply_changes_for_dev_in_dev';

        $default = $this->apply_changes_for_dev_in_dev_state_default;
        //do default and overlap merging if required
        
        $this->state = $default;        
        $tmp_time = microtime(true);
        $this->state['time_added'] = $tmp_time;
        $this->state['time_updated'] = $tmp_time;
        $this->save_state();

        $state =  $this->state;
        unset($state['data']);
        return $state;
    }

    public function initiate_do_db_modification_in_dev(){
        $this->current_overall_task = 'do_db_modification_in_dev';

        $default = $this->do_db_modification_in_dev_state_default;
        //do default and overlap merging if required
        
        $this->state = $default;        
        $tmp_time = microtime(true);
        $this->state['time_added'] = $tmp_time;
        $this->state['time_updated'] = $tmp_time;
        $this->save_state();

        $state =  $this->state;
        unset($state['data']);
        return $state;
    }

    public function initiate_decode_encoded_logged_queries(){
        $this->current_overall_task = 'decode_encoded_logged_queries';

        $default = $this->decode_encoded_logged_queries_state_default;
        //do default and overlap merging if required
        
        $this->state = $default;        
        $tmp_time = microtime(true);
        $this->state['time_added'] = $tmp_time;
        $this->state['time_updated'] = $tmp_time;
        if(!empty($_POST['overall_task_options']) && is_array($_POST['overall_task_options'])){
            $this->state['data']['overall_task_options'] = $_POST['overall_task_options'];
        }
        $this->save_state();

        $state =  $this->state;
        unset($state['data']);
        return $state;
    }

    public function initiate_remove_decoded_logged_queries(){
        $this->current_overall_task = 'remove_decoded_logged_queries';

        $default = $this->remove_decoded_logged_queries_state_default;
        //do default and overlap merging if required
        
        $this->state = $default;        
        $tmp_time = microtime(true);
        $this->state['time_added'] = $tmp_time;
        $this->state['time_updated'] = $tmp_time;
        $this->save_state();

        $state =  $this->state;
        unset($state['data']);
        return $state;
    }

    public function initiate_fix_db_serialization_in_dev(){
        $this->current_overall_task = 'fix_db_serialization_in_dev';

        $default = $this->fix_db_serialization_in_dev_state_default;
        //do default and overlap merging if required
        
        $this->state = $default;        
        $tmp_time = microtime(true);
        $this->state['time_added'] = $tmp_time;
        $this->state['time_updated'] = $tmp_time;
        $this->save_state();

        $state =  $this->state;
        unset($state['data']);
        return $state;
    }

    public function initiate_fix_db_serialization_in_prod(){
        $this->current_overall_task = 'fix_db_serialization_in_prod';

        $default = $this->fix_db_serialization_in_prod_state_default;
        //do default and overlap merging if required
        
        $this->state = $default;        
        $tmp_time = microtime(true);
        $this->state['time_added'] = $tmp_time;
        $this->state['time_updated'] = $tmp_time;
        $this->save_state();

        $state =  $this->state;
        unset($state['data']);
        return $state;
    }

    public function initiate_overall_task(){
        if(empty($_POST['overall_task'])){
            echo 'invalid request';//improve error later
            exit();
        }
        if($_POST['overall_task'] === 'export_changed_files_in_dev'){
            $response = $this->initiate_export_changed_files_in_dev();
        }
        elseif($_POST['overall_task'] === 'prod_db_import'){
            $response = $this->initiate_prod_import_db();
        }
        elseif($_POST['overall_task'] === 'export_dev_db_delta_2_prod'){
            $response = $this->initiate_export_dev_db_delta_2_prod();
        }
        elseif($_POST['overall_task'] === 'apply_changes_for_prod_in_dev'){
            $response = $this->initiate_apply_changes_for_prod_in_dev();
        }
        elseif($_POST['overall_task'] === 'apply_changes_for_dev_in_dev'){
            $response = $this->initiate_apply_changes_for_dev_in_dev();
        }
        elseif($_POST['overall_task'] === 'do_db_modification_in_dev'){
            $response = $this->initiate_do_db_modification_in_dev();
        }
        elseif($_POST['overall_task'] === 'decode_encoded_logged_queries'){
            $response = $this->initiate_decode_encoded_logged_queries();
        }
        elseif($_POST['overall_task'] === 'remove_decoded_logged_queries'){
            $response = $this->initiate_remove_decoded_logged_queries();
        }
        elseif($_POST['overall_task'] === 'fix_db_serialization_in_dev'){
            $response = $this->initiate_fix_db_serialization_in_dev();
        }
        elseif($_POST['overall_task'] === 'fix_db_serialization_in_prod'){
            $response = $this->initiate_fix_db_serialization_in_prod();
        }
        else{
            echo 'invalid request';//improve error later
            exit();
        }
        $this->add_lang_to_response($response);
        echo wpmerge_prepare_response($response);
        exit();
    }

    public function continue_overall_task(){

        if(empty($_POST['overall_task'])){
            echo 'invalid request';//improve error later
            exit();
        }
        if($_POST['overall_task'] === 'export_changed_files_in_dev'){
            $this->current_overall_task = 'export_changed_files_in_dev';
        }
        elseif($_POST['overall_task'] === 'prod_db_import'){
            $this->current_overall_task = 'import_prod_db';
        }
        elseif($_POST['overall_task'] === 'export_dev_db_delta_2_prod'){
            $this->current_overall_task = 'export_dev_db_delta_2_prod';
        }
        elseif($_POST['overall_task'] === 'apply_changes_for_prod_in_dev'){
            $this->current_overall_task = 'apply_changes_for_prod_in_dev';
        }
        elseif($_POST['overall_task'] === 'apply_changes_for_dev_in_dev'){
            $this->current_overall_task = 'apply_changes_for_dev_in_dev';
        }
        elseif($_POST['overall_task'] === 'do_db_modification_in_dev'){
            $this->current_overall_task = 'do_db_modification_in_dev';
        }
        elseif($_POST['overall_task'] === 'decode_encoded_logged_queries'){
            $this->current_overall_task = 'decode_encoded_logged_queries';
        }
        elseif($_POST['overall_task'] === 'remove_decoded_logged_queries'){
            $this->current_overall_task = 'remove_decoded_logged_queries';
        }
        elseif($_POST['overall_task'] === 'fix_db_serialization_in_dev'){
            $this->current_overall_task = 'fix_db_serialization_in_dev';
        }
        elseif($_POST['overall_task'] === 'fix_db_serialization_in_prod'){
            $this->current_overall_task = 'fix_db_serialization_in_prod';
        }
        else{
            echo 'invalid request';//improve error later
            exit();
        }
        
        $this->load_state_from_db();
        $this->do_tasks();

        $state = $this->state;
        unset($state['data']);
        $response = $state;
        $this->add_lang_to_response($response);
        $this->add_progress_status_to_response($response);
        echo wpmerge_prepare_response($response);
        exit();
    }

    private function add_lang_to_response(&$response){
        if(!empty($response['overall_task'])){
            $response['overall_task_title'] = wpmerge_get_lang($response['overall_task']);
        }
        if(!empty($response['tasks'])){
            foreach($response['tasks'] as $task => &$task_details){
                $task_details['task_title'] = wpmerge_get_lang($task);
            }
        }
    }

    private function add_progress_status_to_response(&$response){
        // if(!empty($response['overall_task'])){
        //     $response['overall_task_title'] = ;
        // }
        if(!empty($response['tasks'])){
            foreach($response['tasks'] as $task => &$task_details){
                if( !in_array($task_details['status'], array('running', 'paused')) ){
                    continue;
                }
                $progress_status = $this->add_progress_for_a_task($task);
                if(!empty($progress_status) && is_array($progress_status)){
                    $task_details['progress_status'] = $progress_status;
                }
            }
        }
    }

    public function get_default_state_for_dummy(){
        $overall_task = isset($_POST['overall_task']) ? $_POST['overall_task'] : '';
        if(empty($overall_task) || !is_string($overall_task)){
            return false;
        }
        $overall_task_default_property = $overall_task.'_state_default';
        if(!property_exists($this, $overall_task_default_property)){
            return false;
        }
        $state = $this->{$overall_task_default_property};
        unset($state['data']);
        $response = $state;
        $this->add_lang_to_response($response);
        echo wpmerge_prepare_response($response);
        exit();
    }

    private function update_task_status($task, $status, $error='', $error_msg='', $update_overall_task=false){

        wpmerge_debug::log_resource_usage($status .'_' . $task);

        if(empty($this->state['tasks']) || !isset($this->state['tasks'][$task])){

            return false;
        }
        if(!in_array($status, $this->valid_statuses)){

            return false;
        }
        $this->state['tasks'][$task]['status'] = $status;

        if($status === 'error'){
            if(!empty($error)){
                $this->state['tasks'][$task]['error'] = $error;
            }            
            if(!empty($error_msg)){
                $this->state['tasks'][$task]['error_msg'] = $error_msg;
            }            
        }

        if(!isset($this->state['tasks'][$task]['time_started']) && $status == 'running'){
            $this->state['tasks'][$task]['time_started'] =  microtime(true);
        }

        if(in_array($status, array('completed', 'error'))){
            $this->state['tasks'][$task]['time_ended'] =  microtime(true);
        }

        $this->state['tasks'][$task]['time_updated'] =  microtime(true);

        $this->save_state();

        if($update_overall_task){
            $this->update_overall_task_status($status, $error, $error_msg);
        }
    }

    private function update_overall_task_status($status, $error='', $error_msg=''){
        if(!in_array($status, $this->valid_statuses)){

            return false;
        }

        $this->state['overall_status'] = $status;
        if($status === 'error'){

            //disable maintenance mode on error
            if( $this->state['overall_task'] === 'export_dev_db_delta_2_prod' ){
                $this->remote_disable_maintenance_mode_direct_call();
            }
            elseif( in_array( $this->state['overall_task'], array( 'import_prod_db', 'apply_changes_for_prod_in_dev', 'apply_changes_for_dev_in_dev' ) ) ){
                wpmerge_maintenance_mode_disable();
            }

            if(!empty($error)){
                $this->state['overall_error'] = $error;
            }            
            if(!empty($error_msg)){
                $this->state['overall_error_msg'] = $error_msg;

                //exceptional case -- add some content to error_msg
                if($this->state['overall_task'] === 'export_dev_db_delta_2_prod'){
                    if(
                        isset($this->state['tasks']['remote_finalise_tables'])
                        &&
                        $this->state['tasks']['remote_finalise_tables']['status'] == 'pending' //assuming tasks run in correct order etc.
                    ){
                        $special_msg = wpmerge_get_lang('no_changes_applied_to_prod_all_action_done_on_tmp_tables');
                        $this->state['overall_error_msg'] = $error_msg. '<br> ('.$special_msg.')';
                    }
                }
            }            
        }

        $this->save_state();
    }

    public function is_validate_task_status($for, $task, $task_array){
        if($task !== $task_array['task']){

            return false;//throw improve later
        }
        if( $task_array['status'] === 'pending' 
            || $task_array['status'] === 'paused'){

            return true;
        }

        return false;
    }

    private function can_run_task($task=null){
        $ok = false;
        // if(empty($status)){
        //     return false;//throw improve later
        // }
        //check overall task
        if(in_array($this->state['overall_status'], array('pending', 'running', 'paused', 'retry'))){
            $ok = true;
        }

        if($ok && $task !== null){
            if(!isset($this->state['tasks'][$task]['status'])){

                return false;
            }

            $task_status = $this->state['tasks'][$task]['status'];
            if(in_array($task_status, array('pending', 'running', 'paused', 'retry'))){

                return true;
            }
        }

        return $ok;
    }

    public function save_state(){
        $task_name = $this->current_overall_task;

        if(empty($task_name)){

            return false;//throw later
        }

        $db_state_slug = $this->overall_task_details[$task_name]['db_state_slug'];
        $this->state['time_updated'] = microtime(true);

        $response = wpmerge_update_option($db_state_slug, $this->state);
        if ($response === false) {
            wpmerge_debug::log($GLOBALS['wpdb']->last_error, '--------save state failed--------');
        }
    }

    public function load_state_from_db(){
        $task_name = $this->current_overall_task;
        if(empty($task_name)){

            return false;//throw later
        }

        $db_state_slug = $this->overall_task_details[$task_name]['db_state_slug'];
        $state = wpmerge_get_option($db_state_slug);
        $this->state = $state;
    }

    public function do_exim_http_call(){

    }
    
}   

$wpmerge_exim_control = new wpmerge_dev_exim_control();

function wpmerge_search_multi_array($value, $key, $array) {//assume first dimension uses numeric keys and in 2nd one search happen, get the numeric key
    foreach ($array as $k => $val) {
        if (isset($val[$key]) && $val[$key] === $value) {
            return $k;
        }
    }
    return false;
 }

 function wpmerge_return_bytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    $val  = substr($val, 0, -1); // necessary since PHP 7.1; otherwise optional
    switch($last){
        //The 'G' modifier is available since PHP 5.1.0
        case 'g':
        $val *= 1024;
        case 'm':
        $val *= 1024;
        case 'k':
        $val *= 1024;
    }
    return $val;
}

function wpmerge_prod_post_max_size($prod_server_info){
    //along with sql queries other parameters will be passed consider there size
    //assuming Max 512KB of other data(which should be very rare, as other data don't have major payload)
    $prod_upload_max_file_size  = wpmerge_return_bytes($prod_server_info['upload_max_filesize']);
    $prod_post_max_size = wpmerge_return_bytes($prod_server_info['post_max_size']);

    $upload_max_file_size = $prod_upload_max_file_size;
    if($prod_upload_max_file_size > $prod_post_max_size){
        $upload_max_file_size = $prod_post_max_size;
    }
    if(!is_int($upload_max_file_size)){
        return false;
    }
    $upload_limit = 50 * 1024 * 1024;//50 MB
    if($upload_max_file_size > $upload_limit){
        $upload_max_file_size = $upload_limit;
    }
    $other_params_size = 512 * 1024;
    $final_max_size = $upload_max_file_size - $other_params_size;
    if($final_max_size < 0){
        return false;
    }
    return $final_max_size;
}
