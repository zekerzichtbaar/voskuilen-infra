<?php
/**
 * WPMerge
 * Copyright (c) 2018 Revmakx LLC
 * revmakx.com
 */

if(!defined('ABSPATH')){ exit; }

class wpmerge_dev_http_admin_log{
	private static $unique_id_for_this_call;

	public static function start(){
		if(!wpmerge_dev_can_record()){
			return false;//if recording state is not recordable lets stop here
		}
		self::$unique_id_for_this_call = uniqid(rand(100000, 999999), true);
		$log_data = array(
			'query' => 'default',
			'http_request_id' => self::$unique_id_for_this_call,
			'type' => 'httpRequest',
			'misc' => 'page_start',
			'logtime' => time(),
			'is_record_on' => (int)wpmerge_dev_is_recording_on()
		);
		$log_http_data = array();
		$log_http_data['_SERVER'] = $_SERVER;

		$unset_SERVER_list = array('HTTP_COOKIE', 'PATH');
		foreach ($unset_SERVER_list as $value) {
			unset($log_http_data['_SERVER'][$value]);
		}
		if($_SERVER['REQUEST_METHOD'] === 'POST'){
			$log_http_data['_POST'] = $_POST;
		}
		$log_http_data['_GET'] = $_GET;

		$log_data['query'] = json_encode($log_http_data);
		
		$GLOBALS['wpdb']->query("SET @wpmerge_dev_http_request_id = '".self::$unique_id_for_this_call."'");
		if(isset($_GET['wpmerge_dev_http_requests_applying_changes']) && $_GET['wpmerge_dev_http_requests_applying_changes'] === '1' && !empty($_GET['wpmerge_dev_old_http_request_id']) && !empty($_GET['wpmerge_dev_applying_changes_id'])){//if apply changes happening then set this
			//NEED to verify $_GET['wpmerge_dev_old_http_request_id'] the ID for safety and to mysql espace to avoid sql injection
			$GLOBALS['wpdb']->query("SET @wpmerge_dev_old_http_request_id = '".$_GET['wpmerge_dev_old_http_request_id']."'");
			$log_data['old_http_request_id'] = $_GET['wpmerge_dev_old_http_request_id'];
			$GLOBALS['wpdb']->query("SET @wpmerge_dev_applying_changes_id = '".$_GET['wpmerge_dev_applying_changes_id']."'");
			$log_data['applying_changes_id'] = $_GET['wpmerge_dev_applying_changes_id'];
		}

		$GLOBALS['wpdb']->insert($GLOBALS['wpdb']->base_prefix .'wpmerge_log_queries', $log_data);
	}

	public static function end(){
		if(!wpmerge_dev_can_record()){
			return false;//if recording state is not recordable lets stop here
		}
		if(empty(self::$unique_id_for_this_call)){
			return false;//may be start() not initiated, may not a admin page
		}
		$log_data = array(
			'query' => 'default',
			'http_request_id' => self::$unique_id_for_this_call,
			'type' => 'httpRequest',
			'misc' => 'page_end',
			'logtime' => time(),
			'is_record_on' => (int)wpmerge_dev_is_recording_on()
		);
		$log_http_data = array();
		$log_data['query'] = json_encode($log_http_data);
		$GLOBALS['wpdb']->insert($GLOBALS['wpdb']->base_prefix .'wpmerge_log_queries', $log_data);
		//$GLOBALS['wpdb']->query("SET @wpmerge_dev_http_request_id = NULL");//commented because in rare case after this function runs, some queries are logged without wpmerge_dev_http_request_id
	}

	public static function is_excluded_http_call(){
		/* $post_action_excluded = ['heartbeat'];
		if($_SERVER['REQUEST_METHOD'] === 'POST'){
			if(!empty($_POST['action']) && in_array($_POST['action'], $post_action_excluded)){
				return true;
			}
		} */

		/* //if heartbeat without data variable then don't record. sometimes data variable have auto save post etc.
		if($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'heartbeat' && empty($_POST['data'])){
			return true;
		} */

		return false;
	}

	public static function query_hook($query){//wp filter function

		if(defined('WPMERGE_DEV_CAN_RECORD') && !WPMERGE_DEV_CAN_RECORD){//WPMERGE_DEV_CAN_RECORD to work call this after wpmerge_dev_init(), using constant because of recurssive issue 
			return $query;//if recording state is not recordable lets stop here
		}
		if(wpmerge_wpdb::is_exclude_our_ddl_query()){
			return $query;
		}
		
		$query_stmt_type = wpmerge_common_db::get_query_stmt_type($query);
		
		if ( preg_match( '/^\s*(create|alter|truncate|drop|rename)\s/i', $query ) ) {

			wpmerge_wpdb::query("SET FOREIGN_KEY_CHECKS = 0");//as we have runtime db mod for ddl queries, depended foreign key table may result in error if int changes to bigint. so checking this.

			//log the ddl query
			$log_data = array();
			//$log_data['query'] = $query;
			$log_data['query_b'] = wpmerge_base64_encode_query($query);
			$log_data['http_request_id'] = self::$unique_id_for_this_call;
			$log_data['type'] = 'query';
			$log_data['query_stmt_type'] = $query_stmt_type;
			$log_data['logtime'] = time();
			$log_data['is_record_on'] = (int)wpmerge_dev_is_recording_on();

			$_table_name = wpmerge_get_table_from_query($query);
			if(!empty($_table_name)){
				$_table_name_without_prefix = wpmerge_remove_prefix($GLOBALS['wpdb']->base_prefix, $_table_name);
				if(!empty($_table_name_without_prefix) && strpos($_table_name_without_prefix, ' ') === false){//checking spaces in the table name
					$log_data['table_name'] = $_table_name_without_prefix;
				}
			}

			$GLOBALS['wpdb']->insert($GLOBALS['wpdb']->base_prefix .'wpmerge_log_queries', $log_data);
			//wpmerge_update_option('is_dev_db_modifications_required', 1);

			wpmerge_db_mod_on_the_go::check_and_flag_if_db_mod_may_required($query);

			/* To avoid foreign key create or alter table errors on dependent table. Changing the query from any int type to bigint */
			$may_modfied_query = wpmerge_change_any_int_to_bigint_in_ddl_query($query);
			return $may_modfied_query;
		}
		elseif ( preg_match( '/^\s*(insert|delete|update|replace)\s/i', $query ) ){

			wpmerge_wpdb::query("SET @wpmerge_dev_query = '".$GLOBALS['wpdb']->_real_escape($query)."'");//query may be big so single variable assign

			wpmerge_wpdb::query("SET @wpmerge_dev_query_b = '".wpmerge_base64_encode_query($query)."'");//query may be big so single variable assign

			$query_stmt_type_assign_sql = is_null($query_stmt_type) ? 'NULL' : "'".$GLOBALS['wpdb']->_real_escape($query_stmt_type)."'";

			wpmerge_wpdb::query("SET @wpmerge_dev_query_stmt_type = ".$query_stmt_type_assign_sql);

			//to handle multi_2_single_insert_or_replace
			wpmerge_wpdb::query("SET @wpmerge_is_multi_insert_or_replace = NULL");
			wpmerge_wpdb::query("SET @wpmerge_multi_row_on_duplicate_key_update_part = ''");//should be empty string '', shouldn't be NULL

			if ( preg_match( '/^\s*(insert|replace)\s/i', $query ) ){
				$wpmerge_query_misc_obj = new wpmerge_query_misc();
				$wpmerge_query_misc_obj->check_multi_insert_or_replace_query_and_process($query);
			}
		}
		return $query;
	}
}
