<?php
/**
 * WPMerge
 * Copyright (c) 2018 Revmakx LLC
 * revmakx.com
 */

if(!defined('ABSPATH')){ exit; }

class wpmerge_wpdb{
	private static $exclude_our_ddl_query = false;

	//as this method just wraps the another function, using same style of naming, might be helpful search	
	public static function dbDelta($queries = '', $execute = true){//purpose is to exclude our ddl queries logged by this plugin
		// include_once ( ABSPATH . 'wp-admin/includes/upgrade.php');//to use dbDelta()
		self::$exclude_our_ddl_query = true;
		$result = dbDelta($queries, $execute);
		self::$exclude_our_ddl_query = false;
		return $result;
	}

	public static function query($query){//purpose is to exclude our ddl queries logged by this plugin, use it only for ddl queries
		self::$exclude_our_ddl_query = true;
		$result = $GLOBALS['wpdb']->query($query);
		self::$exclude_our_ddl_query = false;
		return $result;		
	}

	public static function is_exclude_our_ddl_query(){
		return self::$exclude_our_ddl_query;
	}
}

class wpmerge_common_db{

	protected $db_collation;

	protected $exclude_tables = ['wpmerge_options', 'wpmerge_log_queries', 'wpmerge_unique_ids', 'wpmerge_process_files', 'wpmerge_inc_exc_contents', 'wpmerge_relog'];//with out wp db prefix

    function __construct(){
		// include_once ( ABSPATH . 'wp-admin/includes/upgrade.php');//to use dbDelta()
		$this->db_collation = wpmerge_get_collation();
	}
		
    public function on_plugin_activation(){
		try{
			$this->create_options_table();

			//going for wordpress option because autoload will be optimized and it will not overide if already exists
			add_option('wpmerge_first_activation_redirect', true);
			wpmerge_add_option('wpmerge_db_version', WPMERGE_VERSION);//while fresh install
			
		}
		catch(wpmerge_exception $e){
			//its hard to debug while thing in activation process. If required write a workaround by saving error in db later.
		}
	}
	
    public function create_options_table(){
		$table_name = $GLOBALS['wpdb']->base_prefix .'wpmerge_options';
		$create_table_result = wpmerge_wpdb::query("CREATE TABLE IF NOT EXISTS `".$table_name."` (
				`option_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				`option_name` varchar(191) NOT NULL DEFAULT '',
				`option_value` longtext NOT NULL,
				PRIMARY KEY (`option_id`),
				UNIQUE KEY `option_name` (`option_name`)
				) ENGINE=InnoDB " . $this->db_collation . "");

		$last_db_error = $GLOBALS['wpdb']->last_error;

		if(!wpmerge_is_table_exist($table_name)){
			$query_error = wpmerge_get_error_msg('create_table_error').' Table:('.$table_name.')';
				if($last_db_error){
					$query_error = wpmerge_get_error_msg('create_table_error').' Error:('.$last_db_error.') Table:('.$table_name.')';
				}
				throw new wpmerge_exception('create_table_error', $query_error);
		}
	}

	public function create_query_log_table(){
		$table_name = $GLOBALS['wpdb']->base_prefix .'wpmerge_log_queries';
		$create_table_result = wpmerge_wpdb::query("CREATE TABLE IF NOT EXISTS `".$table_name."` (
				`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
				`query` longtext,
				`query_b` longtext,
				`unique_insert_id` bigint(20) unsigned DEFAULT NULL,
				`old_unique_insert_id` bigint(20) unsigned DEFAULT NULL,
				`is_unique_insert_id_used` tinyint(1) unsigned DEFAULT '0',
				`auto_increment_column` varchar(64) DEFAULT NULL,
				`table_name` varchar(64) DEFAULT NULL,
				`unique_column_value` varchar(191) DEFAULT NULL,
				`http_request_id` varchar(64) DEFAULT NULL,
				`old_http_request_id` varchar(64) DEFAULT NULL,
				`type` enum('query','httpRequest') NOT NULL,
				`query_stmt_type` varchar(64) DEFAULT NULL,
				`misc` varchar(512) DEFAULT NULL,
				`logtime` int(10) unsigned NOT NULL,
				`is_record_on` tinyint(1) unsigned NOT NULL DEFAULT '0',
				`apply_changes_group` int(10) unsigned DEFAULT NULL,
				`is_insert_id_mapped` tinyint(1) unsigned DEFAULT '0' COMMENT 'only for type = httpRequest',
				`applying_changes_id` int(10) unsigned DEFAULT NULL COMMENT 'Used while applying changes',
				`apply_changes_insert_id` bigint(20) unsigned DEFAULT NULL,
 				`is_applied` tinyint(1) unsigned NOT NULL DEFAULT '0',
				PRIMARY KEY (`id`),
				KEY `unique_insert_id` (`unique_insert_id`),
				KEY `old_unique_insert_id` (`old_unique_insert_id`),
				KEY `http_request_id` (`http_request_id`),
				KEY `apply_changes_insert_id` (`apply_changes_insert_id`),
				KEY `type` (`type`),
				KEY `is_record_on` (`is_record_on`),
				KEY `table_name` (`table_name`),
				KEY `unique_column_value` (`unique_column_value`),
				KEY `query_stmt_type` (`query_stmt_type`),
				KEY `logtime` (`logtime`)
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

	public function get_excluded_tables(){
		return $this->exclude_tables;
	}

	public static function get_query_stmt_type($query){
		if( preg_match( '/^\s*(create|alter|truncate|drop|rename|insert|delete|update|replace)\s/i', $query, $matches ) ){
			return strtolower($matches[1]);
		}
		if( preg_match( '/^\s*([a-z]+)\s/i', $query, $matches ) ){
			return strtolower($matches[1]);
		}
		return NULL;
	}
}