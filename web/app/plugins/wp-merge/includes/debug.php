<?php
/**
 * WPMerge
 * Copyright (c) 2018 Revmakx LLC
 * revmakx.com
 */

if(!defined('ABSPATH')){ exit; }

class wpmerge_debug {
	private static $uniq_id = null;

	public static function log($value = '', $key = '') {

		if (!self::is_debug_enabled()) {
			return ;
		}

		self::create_log_dir();

		return file_put_contents(WPMERGE_LOG_DIR . '/log.txt', "\n --" . microtime(true) . "(".round((microtime(true) - WPMERGE_START_TIME), 5)." Secs) ". self::get_uniq_id() ." ----$key---- " . var_export($value, true) . "\n", FILE_APPEND);
	}

	public static function printr($value = '', $key = ''){
		if(!self::is_debug_enabled()){
			return;
		}

		if(wpmerge_is_dev_env()){
			return self::log($value, $key);
		}

		echo "\n --" . microtime(true) . "(".round((microtime(true) - WPMERGE_START_TIME), 5)." Secs) ". self::get_uniq_id() ." ----$key---- " . var_export($value, true) . "\n";
	}

	private static function log_misc($log_file_name, $value = '', $key = '') {
		//this will run it will not debug is on/off
		static $log_query_attempt = false;

		if(empty($log_file_name)){
			return;
		}		

		if(!$log_query_attempt){
			self::create_log_dir();
			$log_query_attempt = true;
		}

		return file_put_contents(WPMERGE_LOG_DIR . '/'. $log_file_name, "\n --" . microtime(true) . "(".round((microtime(true) - WPMERGE_START_TIME), 5)." Secs) ". self::get_uniq_id() ." ----$key---- " . var_export($value, true) . "\n", FILE_APPEND);
	}

	private static function reset_log_misc($log_file_name) {
		//this will run it will not debug is on/off

		self::create_log_dir();

		return file_put_contents(WPMERGE_LOG_DIR . '/'. $log_file_name, '');
	}

	public static function log_deploy_issues($value = '', $key = '') {//only for test merge and prod apply changes i.e wpmerge_common_deploy::do_apply_changes_for_prod()
		//this will run it will not debug is on/off
		static $log_file_name = '';

		if(empty($log_file_name)){
			$log_file_name = 'log_dev_test_merge.txt';
			if(wpmerge_is_prod_env()){
				$log_file_name = 'log_prod_merge.txt';
			}
		}
		return self::log_misc($log_file_name, $value, $key);
	}

	public static function reset_log_deploy_issues() {
		//this will run it will not debug is on/off

		$log_file_name = 'log_dev_test_merge.txt';
		if(wpmerge_is_prod_env()){
			$log_file_name = 'log_prod_merge.txt';
		}

		return self::reset_log_misc($log_file_name);
	}	

	public static function log_prod_clone_db_import_missed_queries($value = '', $key = ''){
		$log_file_name = 'log_prod_clone_db_import_missed_queries.txt';
		return self::log_misc($log_file_name, $value, $key);
	}

	public static function reset_prod_clone_db_import_missed_queries(){
		$log_file_name = 'log_prod_clone_db_import_missed_queries.txt';
		return self::reset_log_misc($log_file_name);
	}

	public static function log_resource_usage($title = '', $forEvery = 0) {

		if (!self::is_debug_enabled()) {
			return ;
		}

		self::create_log_dir();

		$title = str_replace(' ', '', $title);

		global $wpmerge_debug_count;

		$wpmerge_debug_count++;

		$title = '-' . $title;

		global $wpmerge_every_count;

		if (empty($forEvery)) {
			return self::_log_resource_usage($wpmerge_debug_count, $title);
		}

		$wpmerge_every_count++;

		if ($wpmerge_every_count % $forEvery == 0) {
			return self::_log_resource_usage($wpmerge_debug_count, $title);
		}

	}

	private static function _log_resource_usage($debug_count, $title = '') {

		$this_memory_peak_in_mb = memory_get_peak_usage();
		$this_memory_peak_in_mb = $this_memory_peak_in_mb / 1048576;

		$this_memory_in_mb = memory_get_usage();
		$this_memory_in_mb = $this_memory_in_mb / 1048576;

		$current_cpu_load = 0;

		if (function_exists('sys_getloadavg')) {
			$cpu_load = sys_getloadavg();
			$current_cpu_load = $cpu_load[0];
		}

		$this_time_taken = time() - WPMERGE_START_TIME;

		file_put_contents(WPMERGE_LOG_DIR . '/memory-usage.txt', $debug_count . $title . " " . round($this_memory_in_mb, 2) . "\n", FILE_APPEND);
		file_put_contents(WPMERGE_LOG_DIR . '/time-taken.txt', $debug_count . $title . " " . round($this_time_taken, 2) . "\n", FILE_APPEND);
		file_put_contents(WPMERGE_LOG_DIR . '/cpu-usage.txt', $debug_count . $title . " " . $current_cpu_load . "\n", FILE_APPEND);
		file_put_contents(WPMERGE_LOG_DIR . '/memory-peak.txt', $debug_count . $title . " " . round($this_memory_peak_in_mb, 2) . "\n", FILE_APPEND);
	}

	public static function is_debug_enabled(){
		if(defined('WPMERGE_DEBUG') && WPMERGE_DEBUG){
			return true;
		}
		return false;
	}

	private static function create_log_dir(){
		if(file_exists(WPMERGE_LOG_DIR)){
			return ;
		}

		if(!mkdir(WPMERGE_LOG_DIR, 0775, true)){
			throw new wpmerge_exception('log_make_dir_failed');
		}
	}

	private static function get_uniq_id(){//the main purpose of this, to differeciate between the php calls logged in same file
		if(!self::$uniq_id){
			self::$uniq_id = uniqid('', true);
		}
		return self::$uniq_id;
	}
}