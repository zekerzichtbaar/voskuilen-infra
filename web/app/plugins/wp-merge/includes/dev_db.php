<?php
/**
 * WPMerge
 * Copyright (c) 2018 Revmakx LLC
 * revmakx.com
 */

if(!defined('ABSPATH')){ exit; }

class wpmerge_dev_db extends wpmerge_common_db {
	
	//private $plugin_db_prefix = '';
	const SQL_INT_TYPES = array( //php 5.6 required for array as constant
		0 => 'TINYINT',
		1 => 'SMALLINT',
		2 => 'MEDIUMINT',
		3 => 'INT',
		4 => 'BIGINT'
	);
	private $after_db_modification_state = array();
	private $triggers_added_tables = array();
	private $foreign_key_alter_queries = array();
	private $check_time_limit = true;

	private $tables_needs_mod = array();

	function __construct(){
		parent::__construct();//this is required
		//$this->plugin_db_prefix = $GLOBALS['wpdb']->base_prefix . 'wpmerge_';
	}

	public function do_modifications($is_continue=false){//multicall

		ignore_user_abort(true);
		set_time_limit(300);

		try{
			if($is_continue){
				$this->after_db_modification_state = wpmerge_get_option('after_db_modification_state');
				$this->triggers_added_tables = wpmerge_get_option('triggers_added_tables');
				$this->foreign_key_alter_queries = wpmerge_get_option('foreign_key_alter_queries');
			}
			else{//fresh starting do_modifications

				//validate
				$is_changes_applied_for_prod_in_dev = wpmerge_dev_is_changes_applied_for_prod_in_dev();
				if($is_changes_applied_for_prod_in_dev){
					throw new wpmerge_exception('already_changes_for_prod_in_dev_is_applied');
				}

				//start work
				wpmerge_delete_option('db_modification_background_error');
				wpmerge_update_option('db_modification_process_state', 'started');
				wpmerge_update_option('after_db_modification_state', $this->after_db_modification_state);//mostly an empty array
				wpmerge_update_option('triggers_added_tables', $this->triggers_added_tables);//mostly an empty array
				wpmerge_update_option('foreign_key_alter_queries', $this->foreign_key_alter_queries);//mostly an empty array
			}

			$this->create_options_table();
			$this->create_query_log_table();
			$this->create_unique_auto_increment_table();
			$this->create_process_files_table();
			$this->create_inc_exc_contents_table();
			$this->create_relog_table();

			$wpmerge_inc_exc_contents = new wpmerge_inc_exc_contents();
			$wpmerge_inc_exc_contents->insert_default_excluded_files();
			$wpmerge_inc_exc_contents->insert_default_structure_only_tables();

			$result = $this->save_and_drop_foreign_key_constraints();
			if($result === 'continue'){
				return $result;
			}

			$result = $this->alter_any_int_column_to_bigint();
			if($result === 'continue'){
				return $result;
			}

			$result = $this->add_saved_foreign_key_constraints();
			if($result === 'continue'){
				return $result;
			}

			$result = $this->add_or_remove_triggers_to_all_tables('add');
			if($result === 'continue'){
				return $result;
			}

			wpmerge_add_option('is_recording_on', 1);//if already exist should not over ride
			wpmerge_update_option('is_fresh_or_no_changes_recorded', 1);//for freshly installed plugin, this is required for $this->get_recording_state()

			wpmerge_update_option('is_wpmerge_dev_tables_present', 1);
			wpmerge_update_option('is_triggers_added_to_all_tables', 1);
			wpmerge_update_option('is_dev_db_modifications_applied', 1);
			wpmerge_update_option('is_dev_db_modifications_required', 0);
			wpmerge_update_option('after_db_modification_state', $this->after_db_modification_state);
			wpmerge_dev_set_min_mtime_if_not_set();
			wpmerge_update_option('db_modification_process_state', 'completed');
		}
		catch(wpmerge_exception $e){
			if( isset($_POST['is_background_task']) && $_POST['is_background_task'] === '1' ){
				$error = $e->getError();
				$error_msg = $e->getErrorMsg();
				wpmerge_update_option('db_modification_background_error', array('error' => $error, 'message' => $error_msg, 'time' => time() ));
			}
			wpmerge_update_option('db_modification_process_state', 'error');
			throw $e;
		}

		return true;
	}

	public function do_modifications_by_tables($tables){//assuming this will done in a single call, mostt cases single table will be modified. Rare case multiple tables
		

		if(empty($tables) || !is_array($tables)){
			return false;
		}

		$this->check_time_limit = false;

		ignore_user_abort(true);
		set_time_limit(300);

		try{

			/*
			Following data will be modified.
			wpmerge_get_option('after_db_modification_state');
			wpmerge_get_option('triggers_added_tables');
			wpmerge_get_option('foreign_key_alter_queries');
			*/

			//after_db_modification_state remove this table
			$this->after_db_modification_state = wpmerge_get_option('after_db_modification_state');
			foreach($tables as $table){
				unset($this->after_db_modification_state['tables'][$table]);
			}
			wpmerge_update_option('after_db_modification_state', $this->after_db_modification_state);


			//triggers_added_tables remove this table
			$this->triggers_added_tables = wpmerge_get_option('triggers_added_tables');
			foreach($tables as $table){
				if (($key = array_search($table, $this->triggers_added_tables)) !== false) {
					unset($this->triggers_added_tables[$key]);
				}
			}
			wpmerge_update_option('triggers_added_tables', $this->triggers_added_tables);


			//foreign_key_alter_queries remove this table
			$this->foreign_key_alter_queries = array();
			wpmerge_update_option('foreign_key_alter_queries', $this->foreign_key_alter_queries);//mostly an empty array

			//lets start the action
			$this->save_and_drop_foreign_key_constraints();

			$this->alter_any_int_column_to_bigint($tables);

			$this->add_saved_foreign_key_constraints();

			$this->add_or_remove_triggers_to_all_tables('add', $tables);

			wpmerge_update_option('after_db_modification_state', $this->after_db_modification_state);

		}
		catch(wpmerge_exception $e){
			// if( isset($_POST['is_background_task']) && $_POST['is_background_task'] === '1' ){
			// 	$error = $e->getError();
			// 	$error_msg = $e->getErrorMsg();
			// 	wpmerge_update_option('db_modification_background_error', array('error' => $error, 'message' => $error_msg, 'time' => time() ));
			// }
			// wpmerge_update_option('db_modification_process_state', 'error');
			// throw $e;
		}
		$this->check_time_limit = true;

		return true;
	}

	public function remove_modifications(){//single call
		$this->add_or_remove_triggers_to_all_tables('remove');
	}

	public function check_fresh_db_modifications_are_required($update_db_as_if_mod_required=true){//it can return true|false|null
		$tables_needs_mod_in_key = array();//keys as table_names

		$is_dev_db_modifications_applied = wpmerge_dev_is_dev_db_modifications_applied();//gets from flag
		if(!$is_dev_db_modifications_applied){
			return false;//no need to continue, as modifications are not applied
		}

		$is_dev_db_modifications_required = wpmerge_dev_is_dev_db_modifications_required();//gets from flag
		if($is_dev_db_modifications_required){
			return true;//as flag is already set no need to check and reset it
		}

		//lets start the checking
		$after_db_mod_state = wpmerge_get_option('after_db_modification_state');
		if(empty($after_db_mod_state)){
			//nothing to compare
			return null;//may be turn the flag on???
		}
		$tables = wpmerge_get_wp_prefix_base_tables();

		if(empty($tables)){
			//echo 'Something not right';
			return null;
		}
		$this->tables_needs_mod = array();//reseting only here
		foreach ($tables as $table) {
			//if exclude table continue
			$table_without_prefix = wpmerge_remove_prefix($GLOBALS['wpdb']->base_prefix, $table);
			if(in_array($table_without_prefix, $this->exclude_tables)){
				continue;//skip this table
			}
			//no need to worry about deleted table, only present tables matters in the current context

			if(!isset($after_db_mod_state['tables'][$table])){
				//may be new table
				$tables_needs_mod_in_key[$table] = 1;
				// wpmerge_update_option('is_dev_db_modifications_required', 1);
				// return true;
			}

			$create_table_sql = $this->get_create_table_query($table);
			if($after_db_mod_state['tables'][$table]['create_table_sql'] != $create_table_sql){//this check will also take take if int to bigint modification required
				//table schema changed
				$tables_needs_mod_in_key[$table] = 1;
				// wpmerge_update_option('is_dev_db_modifications_required', 1);
				// return true;
			}

			$old_triggers = isset($after_db_mod_state['tables'][$table]['triggers']) ? $after_db_mod_state['tables'][$table]['triggers'] : '';
			if(!empty($old_triggers)){
				$escaped_table = wpmerge_esc_table_prefix($table);
				$present_triggers = $GLOBALS['wpdb']->get_col("SHOW TRIGGERS LIKE  '".$escaped_table."'");//first col "Trigger" name
				foreach($old_triggers as $old_trigger){
					if(!in_array($old_trigger, $present_triggers )){
						//trigger is missing
						$tables_needs_mod_in_key[$table] = 1;
						//wpmerge_update_option('is_dev_db_modifications_required', 1);
						//return true;
					}
				}
			}
		}
		if(!empty($tables_needs_mod_in_key)){
			$this->tables_needs_mod = array_keys($tables_needs_mod_in_key);
			if($update_db_as_if_mod_required){
				wpmerge_update_option('is_dev_db_modifications_required', 1);
			}
			return true;
		}
		return false;
	}

	public function get_tables_needs_mod(){//should be called after check_fresh_db_modifications_are_required()
		return $this->tables_needs_mod;
	}

	// public function check_flag_and_do_modifications(){
	// 	$is_dev_db_modifications_applied = wpmerge_dev_is_dev_db_modifications_applied();
	// 	if(!$is_dev_db_modifications_applied){
	// 		return;//no need to continue, as modifications are not applied
	// 	}

	// 	$is_dev_db_modifications_required = wpmerge_dev_is_dev_db_modifications_required();
	// 	if(!$is_dev_db_modifications_required){
	// 		return;
	// 	}

	// 	$db_modification_process_state = wpmerge_get_option('db_modification_process_state');
	// 	if($db_modification_process_state !== 'completed'){
	// 		return;//it might be in 'error' or 'started' state, so lets not do anything
	// 	}

	// 	$this->do_modifications(false);//need to support multicall
	// }

	public function is_any_changes_recorded(){
		$table_log_queries = $GLOBALS['wpdb']->base_prefix .'wpmerge_log_queries';
		$results = $GLOBALS['wpdb']->get_row("SELECT id FROM `".$table_log_queries."` WHERE `is_record_on` = '1' AND `type` = 'query' LIMIT 1");
		return !empty($results);
	}

	public function get_recording_state(){
		//output array('status' => true|false, 'status_slug' => 'on'|'off', status_decr_slug' = '', 'status_decr' = '')
		$return = array();

		$is_changes_applied_for_prod_in_dev = wpmerge_dev_is_changes_applied_for_prod_in_dev();
		if($is_changes_applied_for_prod_in_dev){
			$return['can_record'] = false;
			$return['status'] = false;
			$return['status_slug'] = 'off';
			$return['status_decr_slug'] = 'changes_applied_for_prod_do_prod_db_clone_and_apply_changes_for_dev';
			$return['status_decr'] = wpmerge_get_lang($return['status_decr_slug']);//we should recommend user to import_prod_db and then do apply_changes_for_dev_in_dev if any
			return $return;
		}

		$is_dev_db_modifications_applied = wpmerge_dev_is_dev_db_modifications_applied();
		if(!$is_dev_db_modifications_applied){
			$return['can_record'] = false;
			$return['status'] = false;
			$return['status_slug'] = 'off';
			$return['status_decr_slug'] = 'db_modification_not_present';
			$return['status_decr'] = wpmerge_get_lang($return['status_decr_slug']);
			return $return;
		}

		$is_dev_db_modifications_required = wpmerge_dev_is_dev_db_modifications_required();
		if($is_dev_db_modifications_required){
			$return['can_record'] = false;
			$return['status'] = false;
			$return['status_slug'] = 'off';
			$return['status_decr_slug'] = 'db_modification_required';
			$return['status_decr'] = wpmerge_get_lang($return['status_decr_slug']);
			return $return;
		}

		$is_fresh_or_no_changes_recorded = (bool)wpmerge_get_option('is_fresh_or_no_changes_recorded');//when initial install or after prod 2 dev db sync, if no changes to apply. In this case its better to take this flag instead of checking the current recordings

		$is_changes_applied_for_dev_in_dev = wpmerge_dev_is_changes_applied_for_dev_in_dev();
		if(!$is_fresh_or_no_changes_recorded && !$is_changes_applied_for_dev_in_dev){
			$return['can_record'] = false;
			$return['status'] = false;
			$return['status_slug'] = 'off';
			$return['status_decr_slug'] = 'changes_for_dev_in_dev_not_applied';
			$return['status_decr'] = wpmerge_get_lang($return['status_decr_slug']);
			return $return;
		}

		//check recording switch(flag)
		$is_recording_on = wpmerge_get_option('is_recording_on');
		$is_recording_on = (bool) $is_recording_on;
		if(!$is_recording_on){
			$return['can_record'] = true;
			$return['status'] = false;
			$return['status_slug'] = 'off';
			$return['status_decr_slug'] = 'recording_set_to_off';
			$return['status_decr'] = wpmerge_get_lang($return['status_decr_slug']);
			return $return;
		}

		$return['can_record'] = true;
		$return['status'] = true;
		$return['status_slug'] = 'on';
		$return['status_decr_slug'] = 'all_ok';
		$return['status_decr'] = wpmerge_get_lang($return['status_decr_slug']);
		return $return;
	}

	private function get_auto_inc_start_int(){
		//this number and say another 1 billion increment should be compatible with max safe interget of mysql(bigint), php and javascript
		//Check max used number in DB autoincrement and decide one number if required later

		//following only PHP related stuff
		if(PHP_INT_SIZE === 4){//32 bit system MAX int is 2,147,483,647(10 digit) to avoid conflit with timestamp(10 digit), lets use 9 digit
			$start_int = 111222001;//potential conflict with unix timestamp from 07/11/1973 @ 7:00am (UTC)
		}
		elseif(PHP_INT_SIZE === 8){//64 bit system MAX int is 9,223,372,036,854,775,807(19 digit) (it is 2^63-1, this limit holds good for mysql bigint max signed value)  to avoid conflit till micro timestamp(16 digit) or any big number used in transaction or etc, lets use 17 digit
			$start_int = 21112223334440001;
		}

		//JS max safe number is 9007199254740991(9,007,199,254,740,991) //I am not sure all browser have same value, so we can start leaving few trillions 9004111222000001 (16 digit), this can conflict with nano time or any big number used in transactions etc
		$js_start = 9004111222000001;
		if($start_int > $js_start ){
			$start_int = $js_start;
		}
		return $start_int;
	}

	public function create_unique_auto_increment_table(){
		$auto_inc_start_int = $this->get_auto_inc_start_int();
		$table_name = $GLOBALS['wpdb']->base_prefix .'wpmerge_unique_ids';
		$create_table_result = wpmerge_wpdb::query("CREATE TABLE IF NOT EXISTS `".$table_name."` (
				`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				PRIMARY KEY (`id`)
				) ENGINE=InnoDB AUTO_INCREMENT=".$auto_inc_start_int." " . $this->db_collation . ";");
				//this table should be in MyISAM, if keep it in InnoDB if server restarts it will get auto increment number from max of auto_inc_col, if no rows then 1. so that will not suite this application
				//(update) Few customer's server not allowing MyISAM, when discard records this table is truncated, but auto increament value will be preserved see $this->delete_all_recordings(); so it is ok to have this table as InnoDB

		$last_db_error = $GLOBALS['wpdb']->last_error;

		if(!wpmerge_is_table_exist($table_name)){
			$query_error = wpmerge_get_error_msg('create_table_error').' Table:('.$table_name.')';
				if($last_db_error){
					$query_error = wpmerge_get_error_msg('create_table_error').' Error:('.$last_db_error.') Table:('.$table_name.')';
				}
				throw new wpmerge_exception('create_table_error', $query_error);
		}
	}

	public function create_process_files_table(){
		$auto_inc_start_int = $this->get_auto_inc_start_int();
		$table_name = $GLOBALS['wpdb']->base_prefix .'wpmerge_process_files';
		$create_table_result = wpmerge_wpdb::query("CREATE TABLE IF NOT EXISTS `".$table_name."` (
				`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				`path` text NOT NULL,
				`type` varchar(20) NOT NULL,
				`group` varchar(20) NOT NULL,
				`mtime` int(11) NOT NULL,
				`size` int NOT NULL,
				PRIMARY KEY (`id`)
				) ENGINE=InnoDB " . $this->db_collation . ";");

		$last_db_error = $GLOBALS['wpdb']->last_error;

		if(!wpmerge_is_table_exist($table_name)){
			$query_error = wpmerge_get_error_msg('create_table_error').' Table:('.$table_name.')';
				if($last_db_error){
					$query_error = wpmerge_get_error_msg('create_table_error').' Error:('.$last_db_error.') Table:('.$table_name.')';
				}
				throw new wpmerge_exception('create_table_error', $query_error);
		}
	}

	public function create_inc_exc_contents_table(){
		$auto_inc_start_int = $this->get_auto_inc_start_int();
		$table_name = $GLOBALS['wpdb']->base_prefix .'wpmerge_inc_exc_contents';
		$create_table_result = wpmerge_wpdb::query("CREATE TABLE IF NOT EXISTS `".$table_name."` (
				`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				`key` text NOT NULL,
				`type` varchar(20) NOT NULL,
				`category` varchar(30) NOT NULL,
				`action` varchar(30) NOT NULL,
				`table_structure_only` int(1) NULL,
				`is_dir` int(1) NULL,
				PRIMARY KEY (`id`),
				INDEX `key` (`key`(191))
				) ENGINE=InnoDB " . $this->db_collation . ";");
		
		$last_db_error = $GLOBALS['wpdb']->last_error;

		if(!wpmerge_is_table_exist($table_name)){
			$query_error = wpmerge_get_error_msg('create_table_error').' Table:('.$table_name.')';
			if($last_db_error){
				$query_error = wpmerge_get_error_msg('create_table_error').' Error:('.$last_db_error.') Table:('.$table_name.')';
			}
			throw new wpmerge_exception('create_table_error', $query_error);
		}
	}

	public function create_relog_table(){

		$table_name = $GLOBALS['wpdb']->base_prefix .'wpmerge_relog';
		$create_table_result = wpmerge_wpdb::query("CREATE TABLE IF NOT EXISTS `".$table_name."` (
				`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				`log_queries_id` bigint(20) unsigned NOT NULL,
				`table_name` varchar(64) NOT NULL,
				`unique_insert_id` bigint(20) unsigned DEFAULT NULL,
				`unique_column_name` varchar(64) DEFAULT NULL,
				`status` enum('pending','processing','completed','error') DEFAULT 'pending',
				`error` varchar(255) DEFAULT NULL,
				`logtime` int(10) unsigned NOT NULL,
				`rewrite_time` int(10) unsigned DEFAULT NULL,
				PRIMARY KEY (`id`)
			) ENGINE=InnoDB " . $this->db_collation . ";");
		
		$last_db_error = $GLOBALS['wpdb']->last_error;

		if(!wpmerge_is_table_exist($table_name)){
			$query_error = wpmerge_get_error_msg('create_table_error').' Table:('.$table_name.')';
			if($last_db_error){
				$query_error = wpmerge_get_error_msg('create_table_error').' Error:('.$last_db_error.') Table:('.$table_name.')';
			}
			throw new wpmerge_exception('create_table_error', $query_error);
		}
	}

	private function get_create_table_query($table){
		$result = $GLOBALS['wpdb']->get_row("SHOW CREATE TABLE `".$table."`");//(`) backtick quote should be used
		if(empty($result)){
			return false;
		}
		$create_table_sql = $result->{'Create Table'};
		if(empty($create_table_sql)){
			return false;
		}
		$create_table_sql_parts = explode("\n", $create_table_sql);
		array_pop($create_table_sql_parts);//to remove auto increment text which will contact dynamic auto inc id
		$create_table_sql = implode("\n", $create_table_sql_parts);
		return $create_table_sql;
	}

	private function log_create_table_query($table){
		$create_table_sql = $this->get_create_table_query($table);
		$this->after_db_modification_state['tables'][$table]['create_table_sql'] = $create_table_sql;
	}

	private function add_or_remove_triggers_to_all_tables($do_action, $tables=''){//$do_action == 'add' is multi call and $do_action == 'remove' single call
		
		if(!empty($tables) && is_array($tables)){
			$tables = wpmerge_get_valid_wp_prefix_base_tables($tables);
			if(empty($tables) || !is_array($tables)){
				return true;//it's ok to ignore here
			}
		}
		else{
			$tables = wpmerge_get_wp_prefix_base_tables();
			if(empty($tables) || !is_array($tables)){
				throw new wpmerge_exception('wp_table_listing_error');
			}
		}

		if(empty($tables)){
			throw new wpmerge_exception('create_trigger_no_tables_found');
		}

		$wpmerge_inc_exc_contents = new wpmerge_inc_exc_contents();
		foreach ($tables as $table) {
			//if exclude table continue
			$table_without_prefix = wpmerge_remove_prefix($GLOBALS['wpdb']->base_prefix, $table);
			if(in_array($table_without_prefix, $this->exclude_tables)){
				continue;//skip this table
			}
			if($do_action == 'add' && in_array($table, $this->triggers_added_tables)){
				continue;//skip this table as it is already completed
			}


			if($do_action == 'add'){
				$this->log_create_table_query($table);
				$this->drop_all_wpmerge_triggers_by_table($table);
				if($wpmerge_inc_exc_contents->is_excluded_table($table) !== 'content_excluded'){
					$this->add_unique_auto_increment_trigger_to_table($table);
					$this->add_query_log_triggers_to_table($table);
				}				
				$this->triggers_added_tables[] = $table;
			}
			elseif($do_action == 'remove'){
				//$this->remove_unique_auto_increment_trigger_to_table($table);//lets not remove this trigger, better way to do this is change bigint unique number to normal numbers per table basis, its hard to implement, so fresh prod db clone is the solution(where it will delete all the triggers, when deleting and creating a table)
				$this->remove_query_log_triggers_to_table($table);
			}
			if($do_action == 'add' && $this->check_time_limit && wpmerge_is_time_limit_exceeded()){
				wpmerge_update_option('after_db_modification_state', $this->after_db_modification_state);
				wpmerge_update_option('triggers_added_tables', $this->triggers_added_tables);
				return 'continue';//optimize if last table LATER
			}
		}
		wpmerge_update_option('after_db_modification_state', $this->after_db_modification_state);
		wpmerge_update_option('triggers_added_tables', $this->triggers_added_tables);
		return true;
	}

	private function add_unique_auto_increment_trigger_to_table($table){
		//check trigger limits later
		$table_auto_inc_col = $this->get_auto_increment_column($table);
		if(!$table_auto_inc_col){
			return false;//this table doesn't have auto increment col so ignore
		}
		$when = 'before';
		$triggering_action = 'insert';
		$table_trigger_name = $this->get_trigger_name($table, $when, $triggering_action);

		//if exists drop and create again
		$drop_trigger_result = wpmerge_wpdb::query("DROP TRIGGER IF EXISTS `".$table_trigger_name."`;");

		$create_trigger_result = wpmerge_wpdb::query("
			CREATE TRIGGER `".$table_trigger_name."` BEFORE INSERT ON `".$table."` FOR EACH ROW
			BEGIN
				IF (SELECT @wpmerge_dev_dont_change_auto_increment_id IS NULL) THEN
					INSERT INTO `".$GLOBALS['wpdb']->base_prefix .'wpmerge_unique_ids'."` () VALUES ();
					SET NEW.`".$table_auto_inc_col."` = (SELECT LAST_INSERT_ID());
				ELSEIF ((SELECT @wpmerge_dev_dont_change_auto_increment_id IS TRUE) AND (SELECT @wpmerge_dev_next_insert_id IS NOT NULL)) THEN
					SET NEW.`".$table_auto_inc_col."` = @wpmerge_dev_next_insert_id;
					SET @wpmerge_dev_next_insert_id = NULL;
				END IF;
			END
			");
		if($create_trigger_result === false){
			$query_error = wpmerge_get_error_msg('create_trigger_error').' Table:('.$table.')';
				if($GLOBALS['wpdb']->last_error){
					$query_error = wpmerge_get_error_msg('create_trigger_error').' Error:('.$GLOBALS['wpdb']->last_error.') Table:('.$table.')';
				}
				throw new wpmerge_exception('create_trigger_error', $query_error);
		}
		$this->after_db_modification_state['tables'][$table]['triggers'][] = $table_trigger_name;
		//wpmerge_debug_print($create_trigger_result, 'add_unique_auto_increment_trigger_to_table');
	}

	private function remove_unique_auto_increment_trigger_to_table($table){
		$when = 'before';
		$triggering_action = 'insert';
		$table_trigger_name = $this->get_trigger_name($table, $when, $triggering_action);

		//drop - check and drop Later if required
		$drop_trigger_result = wpmerge_wpdb::query("DROP TRIGGER IF EXISTS `".$table_trigger_name."`;");
	}

	private function get_auto_increment_column($table){
		//assuming one auto increment per table
		//assuming INFORMATION_SCHEMA is accessible
		$db_name = DB_NAME;
		$table_col = $GLOBALS['wpdb']->get_row("SELECT * FROM `INFORMATION_SCHEMA`.`COLUMNS` WHERE `TABLE_SCHEMA` = '".$db_name."' AND `TABLE_NAME` = '".$table."' AND `EXTRA` = 'auto_increment'");
		if(empty($table_col)){
			return false;
		}
		return $table_col->COLUMN_NAME;
	}

	private function add_query_log_triggers_to_table($table){
		$this->add_query_log_trigger_to_table_by_action($table, 'insert');
		$this->add_query_log_trigger_to_table_by_action($table, 'update');
		$this->add_query_log_trigger_to_table_by_action($table, 'delete');
	}

	private function remove_query_log_triggers_to_table($table){
		$this->remove_query_log_trigger_to_table_by_action($table, 'insert');
		$this->remove_query_log_trigger_to_table_by_action($table, 'update');
		$this->remove_query_log_trigger_to_table_by_action($table, 'delete');
	}

	private function add_query_log_trigger_to_table_by_action($table, $triggering_action){
		//check trigger limits later
		$when = 'after';
		$cap_action = strtoupper($triggering_action);
		//validate $triggering_action later
		$table_without_prefix = wpmerge_remove_prefix($GLOBALS['wpdb']->base_prefix, $table);
		$table_trigger_name = $this->get_trigger_name($table, $when, $triggering_action);

		$drop_trigger_result = wpmerge_wpdb::query("DROP TRIGGER IF EXISTS `".$table_trigger_name."`;");

		$set_unique_insert_id_var_sql = '';
		$set_auto_increment_column_var_sql = '';
		if($triggering_action == 'insert'){
			$table_auto_inc_col = $this->get_auto_increment_column($table);
			if($table_auto_inc_col){//if auto increment column only then do so, not required for all the insert
				$set_unique_insert_id_var_sql = "SET unique_insert_id_var = NEW.`".$table_auto_inc_col."`;";//using (SELECT LAST_INSERT_ID()) doesn't work https://stackoverflow.com/a/17795250/188371 although it is session based doesn't work
				$set_auto_increment_column_var_sql = "SET auto_increment_column_var = '".$table_auto_inc_col."';";
			}
		}

		$set_unique_column_value_sql = '';
		if( $table_without_prefix === 'options' ){

			$trigger_obj_new_or_old = 'NEW';//for insert and update
			if( $triggering_action == 'delete' ){
				$trigger_obj_new_or_old = 'OLD';
			}

			//this is hard coding
			$set_unique_column_value_sql = "SET unique_column_value_var = ".$trigger_obj_new_or_old.".`option_name`;";
		}

		$set_after_delete_query_log_if = '';
		$set_after_delete_query_log_end_if = '';
		if($triggering_action == 'delete'){
			//to avoid REPLACE queries from recorded twice because of the following
			/*The REPLACE statement is executed with the following workflow in triggers:
			BEFORE INSERT;
			BEFORE DELETE (only if a row is being deleted);
			AFTER DELETE (only if a row is being deleted);
			AFTER INSERT.*/
			//if not Replace query in AFTER DELETE Trigger
			$set_after_delete_query_log_if ="IF(SELECT @wpmerge_dev_cur_query NOT REGEXP '^[[:space:]]*replace') THEN";//case in-sentive search
			$set_after_delete_query_log_end_if = "END IF;";
		}

		/* for sake of later reference
			The INSERT ... ON DUPLICATE KEY UPDATE statement, when a row already exists, follows the following workflow in triggers:
			BEFORE INSERT;
			BEFORE UPDATE;
			AFTER UPDATE.

			As this coming only once in AFTER statement, query duplicate won't happen, so no need to worry about it now.
		*/

		$code_to_handle_multi_2_single_insert_or_replace = '';
		if($triggering_action == 'insert' || $triggering_action == 'update'){//'update' support required for 'on duplicate key update' cases 
			$multi_2_single_insert_or_replace = new wpmerge_multi_2_single_insert_or_replace();
			$code_to_handle_multi_2_single_insert_or_replace = $multi_2_single_insert_or_replace->get_sql_code($table);
		}

		$create_trigger_result = wpmerge_wpdb::query("
		CREATE TRIGGER `".$table_trigger_name."` AFTER ".$cap_action." ON `".$table."` FOR EACH ROW
		block_1:BEGIN
			DECLARE original_query LONGTEXT;
			DECLARE original_query_b LONGTEXT;
			DECLARE long_query LONGTEXT;
			DECLARE is_record_on_var TINYINT(1);
			DECLARE unique_insert_id_var BIGINT(20);
			DECLARE auto_increment_column_var VARCHAR(64);
			DECLARE unique_column_value_var VARCHAR(512);
			DECLARE log_queries_id BIGINT(20);
			IF ( (SELECT @wpmerge_dev_is_logging_on IS NOT NULL) AND (@wpmerge_dev_is_logging_on = TRUE) ) THEN
				".$set_unique_insert_id_var_sql."
				".$set_auto_increment_column_var_sql."
				".$set_unique_column_value_sql."
				IF (SELECT @wpmerge_dev_is_record_on IS NULL) THEN
					SET is_record_on_var = 0;
				ELSE
					SET is_record_on_var = @wpmerge_dev_is_record_on;
				END IF;
				IF (SELECT @wpmerge_dev_query IS NOT NULL) THEN
					SET original_query = @wpmerge_dev_query;
					SET original_query_b = @wpmerge_dev_query_b;
					#SET @wpmerge_dev_query = NULL;#commented creating issue when last query and truncated query not matching
				END IF;
				IF original_query IS NULL THEN
					LEAVE block_1;
				END IF;
				SET @wpmerge_dev_cur_query = original_query;
				SET @wpmerge_dev_cur_query_b = original_query_b;
				".$code_to_handle_multi_2_single_insert_or_replace."
				IF  ( (SELECT @wpmerge_dev_last_logged_query IS NULL) OR (@wpmerge_dev_cur_query <> @wpmerge_dev_last_logged_query) ) THEN
				".$set_after_delete_query_log_if."
					SET @wpmerge_dev_last_logged_query = @wpmerge_dev_cur_query;
					IF (SELECT @wpmerge_dev_cur_query_b IS NOT NULL) THEN
						SET @wpmerge_dev_cur_query_final = NULL;
						SET @wpmerge_dev_cur_query_b_final = @wpmerge_dev_cur_query_b;
					ELSE
						SET @wpmerge_dev_cur_query_final = @wpmerge_dev_cur_query;
						SET @wpmerge_dev_cur_query_b_final = NULL;
					END IF;

					INSERT INTO `".$GLOBALS['wpdb']->base_prefix .'wpmerge_log_queries'."` (id, query, query_b, unique_insert_id, auto_increment_column, table_name, unique_column_value, http_request_id, old_http_request_id, type, query_stmt_type, misc, logtime, is_record_on, applying_changes_id) VALUES (NULL, @wpmerge_dev_cur_query_final, @wpmerge_dev_cur_query_b_final, unique_insert_id_var, auto_increment_column_var, '".$table_without_prefix."',  unique_column_value_var, @wpmerge_dev_http_request_id, @wpmerge_dev_old_http_request_id, 'query', @wpmerge_dev_query_stmt_type, @usr_var01, UNIX_TIMESTAMP(), is_record_on_var, @wpmerge_dev_applying_changes_id);

					SELECT LAST_INSERT_ID() INTO log_queries_id;

					IF ( (SELECT @wpmerge_is_multi_insert_or_replace) AND (SELECT count(*) FROM `information_schema`.`TABLES` WHERE `TABLE_SCHEMA` = (SELECT DATABASE()) AND `TABLE_NAME` = '".$GLOBALS['wpdb']->base_prefix."wpmerge_relog') AND (SELECT log_queries_id) ) THEN
						INSERT INTO `".$GLOBALS['wpdb']->base_prefix."wpmerge_relog` (`log_queries_id`, `table_name`, `unique_insert_id`, `unique_column_name`, `status`, `logtime`)VALUES (log_queries_id, '".$table_without_prefix."', unique_insert_id_var, NULL, 'pending', UNIX_TIMESTAMP());
					END IF;#this part should come conditionally may be insert and replace alone.

				".$set_after_delete_query_log_end_if."
				END IF;
			END IF;
		END
		");

		/* debugging 
		SET @result_m = 'hi';
		SET original_query = (SELECT @result_m := CONCAT_WS('|', @result_m, '^^' , ifnull(ID, '-') ,ifnull(USER, '-') ,ifnull(HOST, '-') ,ifnull(DB, '-') ,ifnull(COMMAND, '-') ,ifnull(STATE, '-') ,ifnull(INFO, '-')) FROM INFORMATION_SCHEMA.PROCESSLIST WHERE id = CONNECTION_ID());
		SET original_query = (SELECT  @result_m);

		*/
		if($create_trigger_result === false){
			$query_error = wpmerge_get_error_msg('create_trigger_error').' Table:('.$table.')';
				if($GLOBALS['wpdb']->last_error){
					$query_error = wpmerge_get_error_msg('create_trigger_error').' Error:('.$GLOBALS['wpdb']->last_error.') Table:('.$table.')';
				}
				throw new wpmerge_exception('create_trigger_error', $query_error);
		}
		$this->after_db_modification_state['tables'][$table]['triggers'][] = $table_trigger_name;
		//wpmerge_debug_print($create_trigger_result, 'add_query_log_trigger_to_table_by_action');

	}

	private function remove_query_log_trigger_to_table_by_action($table, $triggering_action){
		//check trigger limits later
		$when = 'after';
		//validate $triggering_action later

		$table_trigger_name = $this->get_trigger_name($table, $when, $triggering_action);
		//drop - check and drop Later if required
		$drop_trigger_result = wpmerge_wpdb::query("DROP TRIGGER IF EXISTS `".$table_trigger_name."`;");
	}

	private function get_trigger_name($table, $when, $triggering_action){
		return 'wpmerge_'.md5($table).'_'.$when.'_'.$triggering_action;//use full table name, trigger name should be unique across the database.
	}

	private function drop_all_wpmerge_triggers_by_table($table){
		$escaped_table = wpmerge_esc_table_prefix($table);
		$present_triggers = $GLOBALS['wpdb']->get_col("SHOW TRIGGERS LIKE  '".$escaped_table."'");
		if(empty($present_triggers)){
			return true;
		}

		foreach($present_triggers as $trigger_name){
			if(strpos($trigger_name, 'wpmerge_') === 0){
				$drop_trigger_result = wpmerge_wpdb::query("DROP TRIGGER `".$trigger_name."`;");
			}
		}
		return true;
	}

	private function save_and_drop_foreign_key_constraints(){

		//$GLOBALS['wpdb']->query("SET FOREIGN_KEY_CHECKS = 0");
		/* using SET FOREIGN_KEY_CHECKS = 0 will not help, following error may occur
		Error on rename of './db_name_example/#sql-3ba_302' to './db_name_example/table_name_example' (errno: 150 - Foreign key constraint is incorrectly formed)

		Thats why we need to remove and add foreign keys
		refer https://stackoverflow.com/a/31423637/188371
		*/

		/*
		Foreign key special cases
		1) Multi column foreign key
		2) Reference table can be in different DB

		REFERENTIAL_CONSTRAINTS RC
		KEY_COLUMN_USAGE KCU
		RC.CONSTRAINT_NAME = KCU.CONSTRAINT_NAME (1 to N relationship i.e KCU can more than one row)
		RC.CONSTRAINT_SCHEMA = KCU.CONSTRAINT_SCHEMA //which have foreign key sql
		RC.TABLE_NAME = KCU.TABLE_NAME
		RC.REFERENCED_TABLE_NAME = KCU.REFERENCED_TABLE_NAME
		RC.UNIQUE_CONSTRAINT_SCHEMA = KCU.REFERENCED_TABLE_SCHEMA //~different col names

		Assumptions
		1) RC table will have one row for a unique CONSTRAINT_NAME for a table
		2) If a row exists for a CONSTRAINT_NAME in RC, then that reference will be there in KCU
		3) If referenced table is not part of table prefix group or belongs to some other DB. Then that the data if it is a type of int but not bigint, then when trying to alter foreign key again it might fail because of different data type. As we are changing all int(except big int) to bigint in another process.

		*/

		$db_name = DB_NAME;
		// $escaped_base_prefix = wpmerge_esc_table_prefix($GLOBALS['wpdb']->base_prefix);

		$tables = wpmerge_get_wp_prefix_base_tables();
		if(empty($tables) || !is_array($tables)){
			throw new wpmerge_exception('wp_table_listing_error');
		}	

		$tables_list = "'".implode("', '", $tables)."'";
		$table_name_filter = " AND `TABLE_NAME` IN(".$tables_list.")";

		$referential_constraints = $GLOBALS['wpdb']->get_results("SELECT * FROM `INFORMATION_SCHEMA`.`REFERENTIAL_CONSTRAINTS` WHERE `CONSTRAINT_SCHEMA` = '".$db_name."' ".$table_name_filter."");

		if(empty($referential_constraints)){
			//no foreign keys found
			return true;
		}
		
		foreach($referential_constraints as $rc_row){
			$kcu_column_names = array();
			$kcu_referenced_column_names = array();

			$kcu_rows = $GLOBALS['wpdb']->get_results("SELECT * FROM `INFORMATION_SCHEMA`.`KEY_COLUMN_USAGE` WHERE 
			`CONSTRAINT_NAME` = '".$rc_row->CONSTRAINT_NAME."' AND 
			`CONSTRAINT_SCHEMA` = '".$rc_row->CONSTRAINT_SCHEMA."' AND 
			`TABLE_NAME` = '".$rc_row->TABLE_NAME."'
			ORDER BY `POSITION_IN_UNIQUE_CONSTRAINT` ASC");

			//to support multi column foreign key
			foreach($kcu_rows as $kcu_row){
				$kcu_column_names[] = '`'.$kcu_row->COLUMN_NAME.'`';
				$kcu_referenced_column_names[] = '`'.$kcu_row->REFERENCED_COLUMN_NAME.'`';
			}

			$kcu_column_names_str = implode(', ', $kcu_column_names);
			$kcu_referenced_column_names_str = implode(', ', $kcu_referenced_column_names);

			$reference_db_schema = '';
			if($rc_row->UNIQUE_CONSTRAINT_SCHEMA != $rc_row->CONSTRAINT_SCHEMA){
				$reference_db_schema = '`'.$rc_row->UNIQUE_CONSTRAINT_SCHEMA.'`.';
			}

			//ALTER TABLE `wp_cool_c` ADD CONSTRAINT `wp_cool_c_ibfk_1` FOREIGN KEY (`p_id`) REFERENCES `wp_cool_p` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

			$foreign_key_alter_query = 'ALTER TABLE `'.$rc_row->TABLE_NAME.'` ADD CONSTRAINT `'.$rc_row->CONSTRAINT_NAME.'` FOREIGN KEY ('.$kcu_column_names_str.') REFERENCES '.$reference_db_schema.'`'.$rc_row->REFERENCED_TABLE_NAME.'` ('.$kcu_referenced_column_names_str.') ON DELETE '.$rc_row->DELETE_RULE.' ON UPDATE '.$rc_row->UPDATE_RULE.';';

			//ALTER TABLE `wp_cool_c` DROP FOREIGN KEY `wp_cool_c_ibfk_2`;
			$foreign_key_drop_query = 'ALTER TABLE `'.$rc_row->TABLE_NAME.'` DROP FOREIGN KEY `'.$rc_row->CONSTRAINT_NAME.'`;';

			//adding to array before deleting
			//$this->foreign_key_alter_queries[TABLE_NAME][CONSTRAINT_NAME] = arrat('alter_query' => query, )
			$this->foreign_key_alter_queries[$rc_row->TABLE_NAME][$rc_row->CONSTRAINT_NAME] = array(
				'alter_query' => $foreign_key_alter_query,
				'exec_status' => 0 //0 => not executed, 1 => taken for executed, 2 => execution completed
			);

			wpmerge_update_option('foreign_key_alter_queries', $this->foreign_key_alter_queries);

			$drop_result = wpmerge_wpdb::query($foreign_key_drop_query);
			if($drop_result === false){
				$query_error = wpmerge_get_error_msg('drop_foreign_key_query_error').' Table:('.$rc_row->TABLE_NAME.')';
                    if($GLOBALS['wpdb']->last_error){
                        $query_error = wpmerge_get_error_msg('drop_foreign_key_query_error').' Error:('.$GLOBALS['wpdb']->last_error.') Table:('.$rc_row->TABLE_NAME.')';
                    }
                    throw new wpmerge_exception('drop_foreign_key_query_error', $query_error);
			}

			if($this->check_time_limit && wpmerge_is_time_limit_exceeded(20)){//general is 25 secs, here keeping 20 secs just in case if upcoming table takes little longer, let it fall under 30 secs.
				return 'continue';//optimize if last row LATER
			}
		}
		return true;
	}

	private function add_saved_foreign_key_constraints(){
		if(empty($this->foreign_key_alter_queries)){
			return true;
		}

		foreach($this->foreign_key_alter_queries as $table_name => $constraint){
			foreach($constraint as $constraint_name => $constraint_details){
				if($constraint_details['exec_status'] === 0){

					$this->foreign_key_alter_queries[$table_name][$constraint_name]['exec_status'] = 1;

					wpmerge_update_option('foreign_key_alter_queries', $this->foreign_key_alter_queries);

					$alter_result = wpmerge_wpdb::query($constraint_details['alter_query']);
					if($alter_result === false){
						$query_error = wpmerge_get_error_msg('alter_foreign_key_query_error').' Table:('.$table_name.')';
							if($GLOBALS['wpdb']->last_error){
								$query_error = wpmerge_get_error_msg('alter_foreign_key_query_error').' Error:('.$GLOBALS['wpdb']->last_error.') Table:('.$table_name.') Query:('.$constraint_details['alter_query'].')';
							}
							throw new wpmerge_exception('alter_foreign_key_query_error', $query_error);
					}

					$this->foreign_key_alter_queries[$table_name][$constraint_name]['exec_status'] = 2;

					wpmerge_update_option('foreign_key_alter_queries', $this->foreign_key_alter_queries);

					if($this->check_time_limit && wpmerge_is_time_limit_exceeded(20)){//general is 25 secs, here keeping 20 secs just in case if upcoming table takes little longer, let it fall under 30 secs.
						return 'continue';//optimize if last row LATER
					}
				}
			}
		}
		return true;
	}

	private function alter_any_int_column_to_bigint($tables=''){
		//default wordpress sites uses bigint for auto increment column
		//Need to change all columns which have any one of the int types to bigint, so that if table contains ID(auto increment) of another table, it will support the big int

		//so here changing all columns that having any int type to the bigint including auto increment column

		//$smallest_auto_inc_int_type = $this->get_min_int_type_having_auto_inc(); //lets not use $smallest_auto_inc_int_type, because say we changed one non auto inc column(say int) of table2 to bigint, due to plugin update it changes back int, but not the orignal table(table1) is changed, then it will result in data loss when bigint data set in int col.

		$db_name = DB_NAME;
		// $escaped_base_prefix = wpmerge_esc_table_prefix($GLOBALS['wpdb']->base_prefix);
		// $table_name_filter = " AND `TABLE_NAME` LIKE '".$escaped_base_prefix."%'";

		if(!empty($tables) && is_array($tables)){
			$tables = wpmerge_get_valid_wp_prefix_base_tables($tables);
			if(empty($tables) || !is_array($tables)){
				return true;//it's ok to ignore here
			}
		}
		else{
			$tables = wpmerge_get_wp_prefix_base_tables();
			if(empty($tables) || !is_array($tables)){
				throw new wpmerge_exception('wp_table_listing_error');
			}
		}		
		
		$tables_list = "'".implode("', '", $tables)."'";
		$table_name_filter = " AND `TABLE_NAME` IN(".$tables_list.")";
		

		$results = $GLOBALS['wpdb']->get_results("SELECT * FROM `INFORMATION_SCHEMA`.`COLUMNS` as info_cols WHERE `TABLE_SCHEMA` = '".$db_name."' ".$table_name_filter." AND `DATA_TYPE` IN ('tinyint', 'smallint', 'mediumint', 'int')");
		if(empty($results)){
			//nothing to change
			return true;
		}

		foreach($results as $row){
			$table = $row->TABLE_NAME;

			//exclude tables
			$table_without_prefix = wpmerge_remove_prefix($GLOBALS['wpdb']->base_prefix, $table);
			if(in_array($table_without_prefix, $this->exclude_tables)){
				continue;//skip this table
			}

			$column_name = $row->COLUMN_NAME;
			$column_type = $row->COLUMN_TYPE;
			$is_nullable = $row->IS_NULLABLE;
			$column_comment = $row->COLUMN_COMMENT;
			$column_default = $row->COLUMN_DEFAULT;
			$column_key = $row->COLUMN_KEY;


			$extra = $row->EXTRA;

			//build the alter query
			$signed_txt = (stripos($column_type, 'unsigned') !== false) ? 'UNSIGNED' : '';
			$null_txt = ($is_nullable === 'YES') ? 'NULL' : 'NOT NULL';
			$comment_txt = (!empty($column_comment)) ? "COMMENT '".$GLOBALS['wpdb']->_escape($column_comment)."'" : '';
			$default_txt = (!is_null($column_default) && $column_default != 'NULL' ) ? "DEFAULT '".$column_default."'" : '';//even we only handle int here, lets wrap with quotes like phpmyadmin and adminer
			$auto_inc_txt = (stripos($extra, 'auto_increment') !== false) ? 'AUTO_INCREMENT' : '';
			if(stripos($column_key, 'PRI') !== false){
				$default_txt = ''; //All parts of a PRIMARY KEY must be NOT NULL; if you need NULL in a key, use UNIQUE instead
			}

			$alter_query = "ALTER TABLE `".$table."` CHANGE `".$column_name."` `".$column_name."` BIGINT ".$signed_txt." ".$null_txt." ".$default_txt." ".$auto_inc_txt." ".$comment_txt."";

			//echo '<br>'.$alter_query;

			$alter_query_result = wpmerge_wpdb::query($alter_query);
			if($alter_query_result === false){
				$query_error = wpmerge_get_error_msg('alter_query_error').' Table:('.$table.')';
                    if($GLOBALS['wpdb']->last_error){
						$query_error = wpmerge_get_error_msg('alter_query_error').' Error:('.$GLOBALS['wpdb']->last_error.') Table:('.$table.') Query:('.$alter_query.')';
                    }
                    throw new wpmerge_exception('alter_query_error', $query_error);
			}
			if($this->check_time_limit && wpmerge_is_time_limit_exceeded(20)){//general is 25 secs, here keeping 20 secs just in case if upcoming table takes little longer, let it fall under 30 secs.
				return 'continue';//optimize if last row LATER
			}

		}
		return true;
	}

	public function get_min_int_type_having_auto_inc(){
		$int_types = SELF::SQL_INT_TYPES;
		$key = array_search('BIGINT', $int_types);
		unset($int_types[$key]);

		$db_name = DB_NAME;
		$escaped_base_prefix = wpmerge_esc_table_prefix($GLOBALS['wpdb']->base_prefix);

		$results = $GLOBALS['wpdb']->get_results("SELECT `TABLE_NAME`,`COLUMN_NAME`,`DATA_TYPE` FROM `INFORMATION_SCHEMA`.`COLUMNS` as info_cols WHERE `TABLE_SCHEMA` = '".$db_name."' AND `TABLE_NAME` LIKE '".$escaped_base_prefix."%' AND `EXTRA` = 'auto_increment' AND `DATA_TYPE` IN ('".implode("', '", $int_types)."')");
		if(empty($results)){
			return 'BIGINT';
		}

		$smallest_int_type = 'BIGINT';//default
		foreach($results as $row){
			$table = $row->TABLE_NAME;

			//exclude tables
			$table_without_prefix = wpmerge_remove_prefix($GLOBALS['wpdb']->base_prefix, $table);
			if(in_array($table_without_prefix, $this->exclude_tables)){
				continue;//skip this table
			}

			$int_type = strtoupper($row->DATA_TYPE);
			if(!empty($int_type) && in_array($int_type, SELF::SQL_INT_TYPES) && $int_type != $smallest_int_type){
				$smallest_int_type_key = array_search($smallest_int_type, SELF::SQL_INT_TYPES);
				$int_type_key = array_search($int_type, SELF::SQL_INT_TYPES);
				if($int_type_key < $smallest_int_type_key){
					$smallest_int_type = $int_type;
					$smallest_int_type_key = $int_type_key;
					if($smallest_int_type_key === 0){
						//these is already smallest(TINYINT) lets break(return here)
						return $smallest_int_type;
					}
				}
			}
		}
		return $smallest_int_type;
	}

	public function pre_prod_to_dev_db_import(){
		//save current recording status
		$recording_state = $this->get_recording_state();
		$last_recording_state = $recording_state['status'] === true ? 1 : 0;
		wpmerge_update_option('last_recording_state', $last_recording_state);

		wpmerge_update_option('is_recording_on', 0);//it's better to turn off the recording
		wpmerge_dev_copy_wp_active_plugins();
		return true;
	}

	public function post_prod_to_dev_db_import(){
		wpmerge_dev_restore_wp_active_plugins();
		//reset the apply changes as we imported db from prod to dev, you can apply changes here only once
		wpmerge_update_option('is_changes_applied_for_dev_in_dev', 0);
		wpmerge_update_option('is_changes_applied_for_prod_in_dev', 0);
		wpmerge_update_option('is_triggers_added_to_all_tables', 0);
		wpmerge_update_option('is_dev_db_modifications_applied', 0);
		wpmerge_update_option('is_dev_db_modifications_required', 0);

		$is_fresh_or_no_changes_recorded = 1;
		$is_any_changes_recorded = $this->is_any_changes_recorded();
		if($is_any_changes_recorded){
			$is_fresh_or_no_changes_recorded = 0;
		}

		wpmerge_update_option('is_fresh_or_no_changes_recorded', $is_fresh_or_no_changes_recorded);//when initial install or after prod 2 dev db sync, if no changes to apply
		return true;
	}

	public function delete_all_recordings($is_confirm){
		/* this will delete all rows in wpmerge_unique_ids & wpmerge_log_queries tables */
		if($is_confirm !== true){
			return false;
		}

		$db_name = DB_NAME;

		$table_log_queries = $GLOBALS['wpdb']->base_prefix .'wpmerge_log_queries';
		//as we are deleting all rows as of now. Using "Where 1" to maintain autoincreament takes lot of time when queries goes 4Lakhs+. Therefore get autoincreament value, set after truncate might work well now.

		//$delete_logs_query = "DELETE FROM `".$table_log_queries."` WHERE 1";
		$auto_increment_value =  $GLOBALS['wpdb']->get_var("SELECT `AUTO_INCREMENT` FROM `INFORMATION_SCHEMA`.`TABLES` WHERE `TABLE_SCHEMA` = '".$db_name."' AND `TABLE_NAME`   = '".$table_log_queries ."'");

		$delete_logs_query = "TRUNCATE TABLE  `".$table_log_queries."`";
		$result = wpmerge_wpdb::query($delete_logs_query);

		if(!empty($auto_increment_value)){
			wpmerge_wpdb::query("ALTER TABLE `".$table_log_queries."` AUTO_INCREMENT = ".$auto_increment_value);
		}
		
		if($result === false){
			return false;
		}

		$table_unique_ids = $GLOBALS['wpdb']->base_prefix .'wpmerge_unique_ids';

		$auto_increment_value =  $GLOBALS['wpdb']->get_var("SELECT `AUTO_INCREMENT` FROM `INFORMATION_SCHEMA`.`TABLES` WHERE `TABLE_SCHEMA` = '".$db_name."' AND `TABLE_NAME`   = '".$table_unique_ids ."'");

		//$delete_unique_ids_query = "DELETE FROM `".$table_unique_ids."` WHERE 1";
		$delete_unique_ids_query = "TRUNCATE TABLE  `".$table_unique_ids."`";
		$result = wpmerge_wpdb::query($delete_unique_ids_query);

		if(!empty($auto_increment_value)){
			wpmerge_wpdb::query("ALTER TABLE `".$table_unique_ids."`  AUTO_INCREMENT = ".$auto_increment_value);
		}
		
		if($result === false){
			return false;
		}

		return true;
	}

	public function reset_this_plugin($is_confirm){
		/* this will delete our plugin tables(all recordings will be lost) and it will also delete this plugin settings
		IMPORTANT - WP DB tables might have triggers and DB modification done by this plugin, so its better to clone it from LIVE completely to reset those tables.
		*/
		if($is_confirm !== true){
			return false;
		}

		//similar codes are in uninstall.php

		$table_log_queries = $GLOBALS['wpdb']->base_prefix .'wpmerge_log_queries';
		$drop_query = "DROP TABLE IF EXISTS `".$table_log_queries."`";
		$result = wpmerge_wpdb::query($drop_query);

		$table_unique_ids = $GLOBALS['wpdb']->base_prefix .'wpmerge_unique_ids';
		$drop_query = "DROP TABLE IF EXISTS `".$table_unique_ids."`";
		$result = wpmerge_wpdb::query($drop_query);

		$table_options = $GLOBALS['wpdb']->base_prefix .'wpmerge_options';
		$drop_query = "DROP TABLE IF EXISTS `".$table_options."`";
		$result = wpmerge_wpdb::query($drop_query);

		$table_process_files = $GLOBALS['wpdb']->base_prefix .'wpmerge_process_files';
		$drop_query = "DROP TABLE IF EXISTS `".$table_process_files."`";
		$result = wpmerge_wpdb::query($drop_query);

		$table_inc_exc_contents = $GLOBALS['wpdb']->base_prefix .'wpmerge_inc_exc_contents';
		$drop_query = "DROP TABLE IF EXISTS `".$table_inc_exc_contents."`";
		$result = wpmerge_wpdb::query($drop_query);

		$table_relog = $GLOBALS['wpdb']->base_prefix .'wpmerge_relog';
		$drop_query = "DROP TABLE IF EXISTS `".$table_relog."`";
		$result = wpmerge_wpdb::query($drop_query);

		delete_option('wpmerge_first_activation_redirect');

		return true;
	}
}

class wpmerge_query_misc{
	public function check_multi_insert_or_replace_query_and_process($query){
		//only insert and replace queries should come here

		if($this->is_multi_insert_or_replace_query($query)){
			wpmerge_dev_rewrite_multi_insert_query::set_multi_insert_or_replace_query_about_to_run();

			$insert_or_replace = 'INSERT';
			if(preg_match( '/^\s*(replace)\s/i', $query )){
				$insert_or_replace = 'REPLACE';
			}
			wpmerge_wpdb::query("SET @wpmerge_insert_or_replace = '".$insert_or_replace."'");

			if(preg_match( '/^\s*(insert)\s/i', $query ) && $this->is_on_duplicate_key_update_query($query)){//"on duplicate key update" applies only for "Insert" statement
				$on_duplicate_key_update_part = $this->get_on_duplicate_key_update_part($query);
				if($on_duplicate_key_update_part !== false){
					wpmerge_wpdb::query("SET @wpmerge_multi_row_on_duplicate_key_update_part= '".addslashes($on_duplicate_key_update_part)."'");
				}
			}
			wpmerge_wpdb::query("SET @wpmerge_is_multi_insert_or_replace = true");
		}

	}
	public function is_multi_insert_or_replace_query($query){
		require_once(WPMERGE_PATH . '/lib/PHP-SQL-Parser/vendor/autoload.php');
		$parser = new PHPSQLParser\PHPSQLParser();

		//  following hack no longer required, as PHP-SQL-Parser updated to v4.2.1
		// //this parser not working properly if a column value starts with "'#" (single-quote with hash). So it consider even multi row insert as single row insert based on where this content placed. It no hard to remove it and check as we not going use the modified query other than checking for multi row or not
		// 	//PHPSQLLexer.php
		// 	/*
		// 	# is checked at starting of string
		// 	/* and -- any part of query
		// 	if close of /* is used after that, in same row no prob. If next it can create problem
			
		// 	*/
		// 	$replace_comment_start_in_string_set = array(
		// 		"'#" => "'", 
		// 		'"#' => '"',
		// 		'/*' => '', 
		// 		'--' => ''
		// 	);
		// 	$replace_comment_start_in_string_find = array_keys($replace_comment_start_in_string_set);
		// 	$replace_comment_start_in_string_replace = array_values($replace_comment_start_in_string_set);

		// 	$query = str_replace($replace_comment_start_in_string_find, $replace_comment_start_in_string_replace, $query);//hack fix

		// //--- end of hack fix --
		

		$parsed_query = $parser->parse($query);
		if(!isset($parsed_query['INSERT']) && !isset($parsed_query['REPLACE'])){
			return false;
		}

		if(isset($parsed_query['SET'])){
			return false;
		}

		if(!isset($parsed_query['VALUES'])  && !isset($parsed_query['SELECT'])){
			return false;
		}
 
		if(isset($parsed_query['SELECT'])){
			return true;
		}

		if(isset($parsed_query['VALUES']) && !empty($parsed_query['VALUES'])){
			$record = 0;
			foreach($parsed_query['VALUES'] as $possible_row){
				if($possible_row['expr_type'] == 'record'){//consectively more than once it should come
					$record++;
					if($record >= 2){
						return true;
					}
				}
				else{
					return false;
				}
			}
		}
		return false;
	}

	public function get_on_duplicate_key_update_part($query){
		$pattern = '/\bON\s+DUPLICATE\s+KEY\s+UPDATE\b/mi';
		$result = preg_split ($pattern , $query);
		if($result === false){
			return false;
		}
		if(count($result) == 2){//only two parts are expected
			return ' ON DUPLICATE KEY UPDATE '.$result[1];
		}
		return false;
	}

	public function is_on_duplicate_key_update_query($query){
		$pattern = '/\bON\s+DUPLICATE\s+KEY\s+UPDATE\b/mi';
		$is_on_duplicate_query = preg_match( $pattern, $query) ;
		return $is_on_duplicate_query;
	}
}

class wpmerge_multi_2_single_insert_or_replace{

	public function get_columns_detail($table_name) {
		return $GLOBALS['wpdb']->get_results("SHOW columns FROM `$table_name`", ARRAY_A);
	}

	public function get_query_string($table_name) {
		$columns_arr = $this->get_columns_detail($table_name);

		$auto_inc_column_name = wpmerge_get_auto_increment_column($table_name);

		$column_names_str = $column_values_str = '';

		foreach($columns_arr as $k => $single_column){
			if(!empty($auto_inc_column_name) && $single_column['Field'] === $auto_inc_column_name ){
				continue;
			}
			$column_names_str  .= "`".$single_column['Field'] . "` , ";
			$column_values_str .= 'QUOTE(NEW.`' . $single_column['Field'] . '`), ",", ';
		}

		//Remove extra chars
		$column_names_str  = rtrim($column_names_str, " , ");
		$column_values_str = rtrim($column_values_str, ", \",\", ");

		return array(
			'column_names_str'  => $column_names_str,
			'column_values_str' => $column_values_str,
		);
	}

	public function get_sql_code($table) {

		$col_dets = $this->get_query_string($table);

		$_column_values_str = empty($col_dets['column_values_str']) ? '""' : 'CONCAT(' . $col_dets['column_values_str'] . ')';

		$trigger_query_part = '
				IF (SELECT @wpmerge_is_multi_insert_or_replace) THEN
					SET @col_names_str = "' . $col_dets['column_names_str'] . '";
					SET @col_vals_str = '. $_column_values_str .';
					SET @wpmerge_dev_cur_query = CONCAT(@wpmerge_insert_or_replace, " INTO `' . $table . '` ", "(", @col_names_str, ")", " VALUES ", "(", @col_vals_str, ")", @wpmerge_multi_row_on_duplicate_key_update_part);
					SET @wpmerge_dev_cur_query_b = NULL;
				END IF;
';//SET @wpmerge_dev_cur_query_b = NULL; is used as workaround, as the query is already come inside MySQL, it character encoding already set, so base64 it further may not be helpful.
		return $trigger_query_part;
	}

}

class wpmerge_db_mod_on_the_go{

	//private static $tables_needs_mod = array();
	private static $is_db_mod_may_required = false;

	public static function is_ddl_query_requires_db_mod($query){

		$pattern = '/^\s*(?:CREATE(?:\s+TEMPORARY)?\s+TABLE|ALTER(?:\s+IGNORE)?\s+TABLE|RENAME\s+TABLE)/is';//create table, alter table and rename table deductions only

		if(preg_match($pattern, $query)){
			return true;
		}
		return false;
	}

	public static function check_and_flag_if_db_mod_may_required($query){
		if(!self::is_ddl_query_requires_db_mod($query)){
			return false;
		}

		self::$is_db_mod_may_required = true;
		// //rename table need to get new table name, if multiple table rename has to taken care. Alter table having rename option too
		// $table_name = wpmerge_get_table_from_query($query);
		// if(!empty($table_name) && is_string($table_name)){
		// 	if(!in_array($table_name, self::$tables_needs_mod)){
		// 		array_push(self::$tables_needs_mod, $table_name);
		// 		return true;
		// 	}
		// }
		// return false;
	}

	public static function check_and_do_db_mod_for_required_tables(){
		// if(empty(self::$tables_needs_mod)){
		// 	return false;
		// }
		// $_tables_needs_mod = self::$tables_needs_mod;

		if(!self::$is_db_mod_may_required){
			return;
		}

		//$start_time = microtime(1);
		
		$dev_db_obj = new wpmerge_dev_db();

		$dev_db_obj->check_fresh_db_modifications_are_required(false);//if db mod applied only then this will check
		//$check_db_mod = microtime(1) - $start_time;
		$_tables_needs_mod = $dev_db_obj->get_tables_needs_mod();
		if(empty($_tables_needs_mod)){
			self::$is_db_mod_may_required = false;
			//file_put_contents(ABSPATH.'/__debug.php', var_export(array('check_db_mod' => $check_db_mod), 1), FILE_APPEND);
			return;
		}
		//$start_time2 = microtime(1);
		
		$dev_db_obj->do_modifications_by_tables($_tables_needs_mod);
		//$do_db_mod = microtime(1) - $start_time2;
		self::$is_db_mod_may_required = false;
		//$total_mod = microtime(1) - $start_time;
		//file_put_contents(ABSPATH.'/__debug.php', var_export(array('check_db_mod' => $check_db_mod, 'do_db_mod' => $do_db_mod,'total_mod' => $total_mod), 1), FILE_APPEND);
		//self::$tables_needs_mod = array();
	}
}
//file_put_contents(ABSPATH.'/__debug.php', "\n\n", FILE_APPEND);

class wpmerge_dev_rewrite_multi_insert_query{
	//currently supports table with auto increment column 

	private $wpmerge_common_exim_obj;
	private $cache = array();
	private static $is_probable_multi_insert_relog_pending = false;

	public static function is_probable_multi_insert_relog_pending(){
		return self::$is_probable_multi_insert_relog_pending;
	}

	public static function set_multi_insert_or_replace_query_about_to_run(){
		return self::$is_probable_multi_insert_relog_pending = true;
	}

	public function check_and_process(){
		if( !wpmerge_is_table_exist($GLOBALS['wpdb']->base_prefix .'wpmerge_relog') ){//to avoid sql errors without creating relog table
			return;
		}
		$pending_queries = $this->get_pending_rewrite_queries();
		if(empty($pending_queries)){
			self::$is_probable_multi_insert_relog_pending = false;
			$this->delete_all_completed_queries();
			return true;
		}
		include_once(WPMERGE_PATH.'/includes/common_exim.php');
		$this->wpmerge_common_exim_obj = new wpmerge_common_exim('');
		$this->process_pending_queries($pending_queries);
		self::$is_probable_multi_insert_relog_pending = false;
		$this->delete_all_completed_queries();
	}

	private function get_pending_rewrite_queries(){
		$table_relog = $GLOBALS['wpdb']->base_prefix .'wpmerge_relog';
		$pending_queries = $GLOBALS['wpdb']->get_results("SELECT * FROM `".$table_relog."` WHERE `status` = 'pending'", ARRAY_A);
		return $pending_queries;
	}

	private function process_pending_queries($pending_queries){
		$table_relog = $GLOBALS['wpdb']->base_prefix .'wpmerge_relog';

		foreach($pending_queries as $pending_query){
			$is_updated = $GLOBALS['wpdb']->update($table_relog, array('status' => 'processing'), array('id' => $pending_query['id']));
			if( !$is_updated || empty($GLOBALS['wpdb']->rows_affected) ){//then this task  taken by any other script, so continue
				continue;
			}
			$this->rewrite_query($pending_query);
		}
	}

	private function delete_all_completed_queries(){
		$table_relog = $GLOBALS['wpdb']->base_prefix .'wpmerge_relog';
		$GLOBALS['wpdb']->delete($table_relog, array('status' => 'completed'));
	}

	public function delete_all_queries(){//used during "discard changes" or similarly if required
		$table_relog = $GLOBALS['wpdb']->base_prefix .'wpmerge_relog';
		wpmerge_wpdb::query("TRUNCATE TABLE `".$table_relog."`");
	}

	private function rewrite_query($pending_query){
		$table_relog = $GLOBALS['wpdb']->base_prefix .'wpmerge_relog';
		$table_log_queries = $GLOBALS['wpdb']->base_prefix .'wpmerge_log_queries';

		//get the data from the actual table and rewrite
		if( empty($pending_query['log_queries_id']) || 
		empty($pending_query['table_name'])  || 
		( empty($pending_query['unique_insert_id']) && empty($pending_query['unique_column_name']) )		
		){
			$GLOBALS['wpdb']->update($table_relog, array('status' => 'error', 'error' => 'data_missing'), array('id' => $pending_query['id']));
			return;
		}

		$log_queries_details = $GLOBALS['wpdb']->get_row("SELECT * from `$table_log_queries` WHERE id = ".$pending_query['log_queries_id']."", ARRAY_A);

		if( empty($log_queries_details) ){
			$GLOBALS['wpdb']->update($table_relog, array('status' => 'error', 'error' => 'log_queries_data_missing'), array('id' => $pending_query['id']));
			return;
		}

		$table = $GLOBALS['wpdb']->base_prefix . $pending_query['table_name'];

		if(isset($this->cache['last_table']) && $this->cache['last_table'] === $table && !empty($this->cache['columns']) && !empty($this->cache['select_cols']) ){
			$columns = $this->cache['columns'];
			$select_cols = $this->cache['select_cols'];
			$insert_mention_cols = $this->cache['insert_mention_cols'];
		}
		else{
			//following code taken from common_exim.php wpmerge_common_exim_obj::createSQLPHP()
			$columns = $GLOBALS['wpdb']->get_results("SHOW COLUMNS IN `$table`", OBJECT_K);

			$columnArr = array();
			$quoted_column_names_alone = array();
			foreach ($columns as $columnName => $metadata) {
				if(!empty($log_queries_details['auto_increment_column']) && $log_queries_details['auto_increment_column'] == $columnName){
					continue;
				}
				$quoted_column_names_alone[] = '`'.$columnName.'`';
				if (strpos($metadata->Type, 'blob') !== false || strpos($metadata->Type, 'binary')!==false) {
					$fullColumnName = "`$table`.`$columnName`";
					$columnArr[]      = "HEX($fullColumnName) as `$columnName`";
				} else {
					$columnArr[] = "`$table`.`$columnName`";
				}
			}
			$select_cols = join(', ', $columnArr);
			$insert_mention_cols = join(', ', $quoted_column_names_alone);
			//end here common_exim.php wpmerge_common_exim_obj::createSQLPHP()
			

			$this->cache['last_table'] = $table;
			$this->cache['columns'] = $columns;
			$this->cache['select_cols'] = $select_cols;
			$this->cache['insert_mention_cols'] = $insert_mention_cols;
		}

		$get_data_where = '';
		if( !empty($log_queries_details['auto_increment_column']) ){
			$get_data_where .= " `".$log_queries_details['auto_increment_column']."` = '".$pending_query['unique_insert_id']."'";
		}

		if(empty($get_data_where)){
			$GLOBALS['wpdb']->update($table_relog, array('status' => 'error', 'error' => 'empty_where_part'), array('id' => $pending_query['id']));
			return;
		}

		$select_query = "SELECT ".$select_cols." FROM `$table` WHERE $get_data_where";
		$row_data_to_rewrite = $GLOBALS['wpdb']->get_row($select_query, OBJECT);

		if(empty($row_data_to_rewrite)){
			$GLOBALS['wpdb']->update($table_relog, array('status' => 'error', 'error' => 'data_missing_before_rewrite'), array('id' => $pending_query['id']));
			return;
		}
		
		$row_data_in_format = $this->wpmerge_common_exim_obj->create_row_insert_values($row_data_to_rewrite, $columns, $table);

		if(empty($row_data_in_format)){
			$GLOBALS['wpdb']->update($table_relog, array('status' => 'error', 'error' => 'data_missing_rewrite_format'), array('id' => $pending_query['id']));
			return;
		}
		
		$joined_row_data = join(', ', $row_data_in_format);
		$new_query = "INSERT INTO `$table` ($insert_mention_cols) VALUES ($joined_row_data)";

		$new_query_b = wpmerge_base64_encode_query($new_query);

		$GLOBALS['wpdb']->update($table_log_queries, array('query_b' => $new_query_b, 'query' => null), array('id' => $pending_query['log_queries_id']));
		
		$GLOBALS['wpdb']->update($table_relog, array('status' => 'completed', 'rewrite_time' => time()), array('id' => $pending_query['id']));
	}
}

class wpmerge_dev_optimize_recorded_queries{
	private static $optimize_queries_older_than = 60 * 30;//30 mins
	private static $optimize_queries_cutoff_time;

	private static function set_optimize_queries_older_than(){
		if( 
			!defined( 'WPMERGE_OPTIMIZE_QUERIES_OLDER_THAN' ) || 
			!is_numeric( WPMERGE_OPTIMIZE_QUERIES_OLDER_THAN ) ||
			WPMERGE_OPTIMIZE_QUERIES_OLDER_THAN <= 0
			){
			return;
		}
		self::$optimize_queries_older_than = (int) WPMERGE_OPTIMIZE_QUERIES_OLDER_THAN;
	}

	private static function set_optimize_queries_cutoff_time(){
		self::set_optimize_queries_older_than();

		self::$optimize_queries_cutoff_time = time() - self::$optimize_queries_older_than;
	}

	public static function run(){
		if( !wpmerge_is_table_exist($GLOBALS['wpdb']->base_prefix .'wpmerge_log_queries') ){//to avoid sql errors without creating log_queries table
			return;
		}
		self::set_optimize_queries_cutoff_time();
		self::optimize_option_table();
	}

	private static function optimize_option_table(){//only for WP single site and main option table of multisite
		self::mark_option_table_transients_as_not_recorded();
		self::mark_option_table_smart_unwanted_as_not_recorded();
	}

	private static function mark_option_table_transients_as_not_recorded(){

		$table_log_queries = $GLOBALS['wpdb']->base_prefix .'wpmerge_log_queries';

		$GLOBALS['wpdb']->query("UPDATE `".$table_log_queries."` SET `is_record_on` = 5 WHERE `type` = 'query' AND `is_record_on` = 1 AND `logtime` < '". self::$optimize_queries_cutoff_time ."' AND `table_name` = 'options' AND `query_stmt_type` IN('insert', 'replace', 'update', 'delete') AND ( `unique_column_value` LIKE '\_transient%' OR `unique_column_value` LIKE '\_site\_transient%' )");//`is_record_on` = 5 is considered as excluded from merging, 5 means optimization exclusion
	}

	private static function mark_option_table_smart_unwanted_as_not_recorded(){

		$table_log_queries = $GLOBALS['wpdb']->base_prefix .'wpmerge_log_queries';

		//get the non transient queries grouped by value

		$all_option_name_records = $GLOBALS['wpdb']->get_col("SELECT unique_column_value FROM (
			SELECT count(id) as unique_column_counter, `unique_column_value` FROM `".$table_log_queries."` where `type` = 'query' AND `is_record_on` = 1 AND `logtime` < '". self::$optimize_queries_cutoff_time ."' AND `table_name` = 'options' AND `query_stmt_type` IN('insert', 'replace', 'update', 'delete')
			AND ( `unique_column_value` NOT LIKE '\_transient%' AND `unique_column_value` NOT LIKE '\_site\_transient%' )
			GROUP BY `unique_column_value`	
		) as unique_column_virtual WHERE `unique_column_counter` > 2");//`unique_column_counter` > 2 already optimized might have 2 records max, so ignoring all those

		if( empty($all_option_name_records) ){
			return true;
		}

		foreach($all_option_name_records as $option_name){
			if( wpmerge_is_time_limit_exceeded() ){
				return false;
			}
			self::_mark_option_table_smart_unwanted_as_not_recorded_by_option_name($option_name);
		}
	}

	private static function _mark_option_table_smart_unwanted_as_not_recorded_by_option_name($option_name){
		/*
		Assumptions:
		1) wp_options table records will always be processed via WP functions like add_option, update_option, delete_option(therefore set_transients like functions will also work). WP functions will only do update_option or delete_option when the option exists.
		2) This plugin currently records a DML query in after-trigger therefore successful queries will only be recorded
		*/

		$table_log_queries = $GLOBALS['wpdb']->base_prefix .'wpmerge_log_queries';

		$option_name_records = $GLOBALS['wpdb']->get_results("SELECT `id`, `query_stmt_type` FROM `".$table_log_queries."` where `type` = 'query' AND `is_record_on` = 1 AND `logtime` < '". self::$optimize_queries_cutoff_time ."' AND `table_name` = 'options' AND `query_stmt_type` IN('insert', 'replace', 'update', 'delete')
		AND `unique_column_value` = '".$GLOBALS['wpdb']->_real_escape($option_name)."' ORDER BY `id` DESC", ARRAY_A);

		if( empty($option_name_records) ){
			return false;
		}

		if( wpmerge_is_time_limit_exceeded() ){
			return false;
		}

		//last_query means last recorded query for option_name, the data is in desending order so last_query taken from first array element
		$last_query_row = reset($option_name_records);
		$last_query_stmt_type = $last_query_row['query_stmt_type'];

		$required_query_ids = array();
		$required_query_ids[0] = $last_query_row['id'];

		/*
		if last_query_stmt_type is 
			case 'delete' that query is enough exit
			case 'update' that query + skip all the previous update queries untill finding a insert/replace query(we can ignore delete query(s) if it comes in between unexpectedly, because if it gets deleted it won't get updated)
			case 'insert' | 'replace' add_option uses INSERT INTO ... ON DUPLICATE KEY UPDATE syntex
			so that query + skip all previous update or insert queries untill finding a delete query
		*/

		if( $last_query_stmt_type === 'delete' ){
			//do nothing
		}
		elseif( in_array($last_query_stmt_type, array('insert', 'replace', 'update'), true ) ){
			if( $last_query_stmt_type === 'update' ){
				$needed_query_stmt_type = array('insert', 'replace');
			}
			elseif( in_array($last_query_stmt_type, array('insert', 'replace'), true ) ){
				$needed_query_stmt_type = array('delete');
			}
			$skip_next = true;//skip the first one
			foreach($option_name_records as $_order_key => $row){
				if( $skip_next ){ $skip_next = false; continue; }
				if( in_array($row['query_stmt_type'], $needed_query_stmt_type, true) ){
					$required_query_ids[1] = $row['id'];
					break;
				}
			}
		}
		else{
			return false;
		}
		
		if( empty($required_query_ids) ){
			return false;
		}

		//now reverse update others queries
		return $GLOBALS['wpdb']->query("UPDATE `".$table_log_queries."` SET `is_record_on` = 5 WHERE `type` = 'query' AND `is_record_on` = 1 AND `logtime` < '". self::$optimize_queries_cutoff_time ."' AND `table_name` = 'options' AND `query_stmt_type` IN('insert', 'replace', 'update', 'delete') AND `unique_column_value` = '".$GLOBALS['wpdb']->_real_escape($option_name)."' AND `id` NOT IN( ".implode(', ', $required_query_ids).")");//`is_record_on` = 5 is considered as excluded from merging, 5 means optimization exclusion
	}
}