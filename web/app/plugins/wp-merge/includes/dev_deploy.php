<?php
/**
 * WPMerge
 * Copyright (c) 2018 Revmakx LLC
 * revmakx.com
 */

if(!defined('ABSPATH')){ exit; }

require_once(WPMERGE_PATH . '/includes/common_deploy.php');

class wpmerge_dev_deploy extends wpmerge_common_deploy{

	public function apply_changes_for_dev_in_dev($options){
		if(!is_int($options['offset']) || !is_int($options['total']) || $options['offset'] >= $options['total'] ){
			throw new wpmerge_exception("invalid_request");
		}
		//is this changes appliable??
			//check the current status of DB, check whether already changes applied, then we can't apply changes without refreshing DB from Prod
		if(wpmerge_dev_is_changes_applied_in_dev()){
			// echo '<br>Changes already applied once. Please again import the prod DB and then try to apply the changes.'; 
			// return false; 
			throw new wpmerge_exception("changes_already_applied_import_db_again");
		}

		if(!$this->can_apply_changes_for_dev_in_dev()){
			// return false;
			throw new wpmerge_exception("modify_db_to_proceed");
		}

		wpmerge_wpdb::query("SET FOREIGN_KEY_CHECKS = 0");

		//disable db table triggers(insert/update/delete) for the following db queries, remember the tables might have triggers or not
		wpmerge_dev_disable_query_logging();
		wpmerge_dev_disable_auto_increment_change_on_insert();

		$eof = false;
        $offset = $options['offset'];
		$total = $options['total'];
		$last_query_id = $options['last_query_id'];
		$row_limit = 5000;
		$q_offset = $offset;
		$sql_where_append = '';

		if(isset($last_query_id)){
			$q_offset = 0;
			$sql_where_append = " AND `id` > $last_query_id";//to increase the efficiency for a million rows+ when rows and offset searching becomes big.
		}

		$__delta_query_exec_total = 0;
		
		$__query_start = microtime(true);
		//get all the queries and run by its flow
		$table_log_queries = $GLOBALS['wpdb']->base_prefix .'wpmerge_log_queries';
		//$db_queries = $GLOBALS['wpdb']->get_results("SELECT * FROM `".$table_log_queries."` WHERE `is_record_on` = '1' AND `type` = 'query' ".$sql_where_append." ORDER BY id ASC LIMIT $row_limit OFFSET $q_offset");
		
		$select_args = array();
        $select_args['columns'] = '*';
        $select_args['table'] = $table_log_queries;
        $select_args['where'] = "`is_record_on` = '1' AND `type` = 'query'  ".$sql_where_append."";
        $select_args['order'] = "id ASC";
        $select_args['limit'] = $row_limit;
		$select_args['offset'] = $q_offset;
		$select_args['optimize_query'] = isset($last_query_id) ? false : true;

		$select_by_memory_limit_obj = new wpmerge_select_by_memory_limit($select_args);
		$db_queries = $select_by_memory_limit_obj->process_and_get_results();

		$__query_end = microtime(true);

		if(empty($db_queries)){
			// return false;//error or no queries
			throw new wpmerge_exception("no_queries_to_apply");
		}
		$run_i = 0;
		foreach ($db_queries as $query) {
			$GLOBALS['EZSQL_ERROR'] = array();//query error logger, fix for memory leak by wordpress

			wpmerge_db_mod_on_the_go::check_and_do_db_mod_for_required_tables();//1 of 2 call

			$query->query = wpmerge_get_query_from_row($query);
			$query->query = trim($query->query);

			$insert_find = preg_match( '/^\s*(insert|replace)\s/i', $query->query ) ;// preg_match( '/^\s*(insert|replace)\s/i', $query ) 

			//" !empty($query->auto_increment_column) " If a table doesn't have auto_increment_column, then there won't be insert id, run like update and delete query
			if($insert_find !== false &&
			!empty($query->auto_increment_column)
			){//insert query
				//modify insert queries so auto increment col ids should go in with old big int

				//$query->unique_insert_id should be valid integer
				if(empty($query->unique_insert_id)){
					// echo '<br>Error in empty($query->unique_insert_id) check';
					// return false;//generate error message Unexpected case
					throw new wpmerge_exception("unexpected_empty_unique_insert_id");
				}
				$GLOBALS['wpdb']->query("SET @wpmerge_dev_next_insert_id = ".$query->unique_insert_id);
				$__delta_query_start = microtime(true);
				$GLOBALS['wpdb']->query($query->query);//improve check query runs successfully or not Later
				$__delta_query_exec_total += microtime(true) - $__delta_query_start;
				$GLOBALS['wpdb']->query("SET @wpmerge_dev_next_insert_id = NULL");//for safety
				
			}
			else{
				$__delta_query_start = microtime(true);
				//run update|delete and ddl queries as sunch
				if ( preg_match( '/^\s*(create|alter|truncate|drop|rename)\s/i', $query->query ) ) {

					/* To avoid foreign key create or alter table errors on dependent table. Changing the query from any int type to bigint */
					$query->query = wpmerge_change_any_int_to_bigint_in_ddl_query($query->query);

					wpmerge_wpdb::query($query->query);
					wpmerge_db_mod_on_the_go::check_and_flag_if_db_mod_may_required($query->query);

				}else{//should be update|delete queries
					$GLOBALS['wpdb']->query($query->query);
				}
				$__delta_query_exec_total += microtime(true) - $__delta_query_start;
			}
			$last_query_id = $query->id;
			$offset++;
			$run_i++;
			if($offset === $total){
                $eof = true;
                break;
            }
            if(wpmerge_is_time_limit_exceeded()){
				//file_put_contents(ABSPATH.'/__in_dev.php', "\n t:".microtime(1)." run_i:".$run_i." offset:".$offset." last_query_id:".$last_query_id." query time:".round($__query_end-$__query_start,2)." delta query:".round($__delta_query_exec_total, 2) ,FILE_APPEND);
                break;
            }
		}

		wpmerge_db_mod_on_the_go::check_and_do_db_mod_for_required_tables();//2 of 2 call

		if($eof){
			wpmerge_update_option('is_changes_applied_for_dev_in_dev', 1);
		}
		return array('offset' => $offset, 'eof' => $eof, 'last_query_id' => $last_query_id);
	}

	public function apply_changes_for_prod_in_dev($options){
		//is this changes appliable??
			//if fresh copy form prod is better
		if(wpmerge_dev_is_changes_applied_in_dev()){
			// echo '<br>Changes already applied once. Please again import the prod DB and then try to apply the changes.'; 
			// return false; 
			throw new wpmerge_exception("changes_already_applied_import_db_again");
		}

		//make sure no triggers are added to tables
		if(!$this->can_apply_changes_for_prod_in_dev()){
			// return false;
			throw new wpmerge_exception("import_db_again_and_try");
		}		

		$res = $this->do_apply_changes_for_prod($options);
		if($res['eof']){
			wpmerge_update_option('is_changes_applied_for_prod_in_dev', 1);
		}

		return $res;
	}

	public function get_logged_queries($where='recorded'){//$where can be all/recorded/selected //do later
		$table_log_queries = $GLOBALS['wpdb']->base_prefix .'wpmerge_log_queries';
		$results = $GLOBALS['wpdb']->get_results("SELECT * FROM `".$table_log_queries."` WHERE `is_record_on` = '1' AND `type` = 'query' ORDER BY id ASC");
		return $results;

	}

	public function can_apply_changes_for_dev_in_dev(){
		//wpmerge tables and triggers for all tables should be present
		if(
			wpmerge_dev_is_wpmerge_dev_tables_present() !== true
			||
			wpmerge_dev_is_triggers_added_to_all_tables() !== true
		){
			//echo '<br>Please initiate the DB before apply changes in dev.';
			return false;
		}
		return true;
	}

	public function can_apply_changes_for_prod_in_dev(){
		//wpmerge tables NEEDED, but triggers for all tables should NOT be present
		if(
			wpmerge_dev_is_wpmerge_dev_tables_present() !== true
			||
			wpmerge_dev_is_triggers_added_to_all_tables() === true
		){
			//echo '<br>Please do apply changes right after syncing db from production.';
			return false;
		}
		return true;
	}

}
