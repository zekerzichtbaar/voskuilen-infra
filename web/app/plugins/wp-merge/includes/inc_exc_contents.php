<?php
/**
 * WPMerge
 * Copyright (c) 2018 Revmakx LLC
 * revmakx.com
 */

if(!defined('ABSPATH')){ exit; }

class wpmerge_inc_exc_contents {
	private $default_wp_folders;
	private $default_wp_files;
	private $db;
	private $default_exclude_files;
	private $processed_files;
	private $bulk_limit;
	private $default_wp_files_n_folders;
	private $excluded_files;
	private $included_files;
	private $excluded_tables;
	private $included_tables;
	private $max_table_size_allowed = 104857600; //100 MB
	private $max_file_size_allowed  = 52428800; //50 MB
	private $file_list;
	private $analyze_files_response = array();
	private $default_structure_only_tables = array(
						'blc_instances',
						'bwps_log',
						'Counterize',
						'Counterize_Referers',
						'Counterize_UserAgents',
						'et_bloom_stats',
						'itsec_log',
						'lbakut_activity_log',
						'redirection_404',
						'redirection_logs',
						'relevanssi_log',
						'simple_feed_stats',
						'slim_stats',
						'statpress',
						'svisitor_stat',
						'tts_referrer_stats',
						'tts_trafficstats',
						'wbz404_logs',
						'wbz404_redirects',
						'woocommerce_sessions',
						'wponlinebackup_generations',
						'wysija_email_user_stat',
						'wfknownfilelist'//as of v1.2.3
					);

	public function __construct($category = 'development_site') {
		$this->init_db();
		$this->default_exclude_dirs();
		$this->set_default_wp_things();
		$this->set_force_exclude_folders();
		$this->merge_wp_things();
		$this->init_iterator();
		$this->load_saved_keys($category);
	}

	private function set_default_wp_things(){
		$this->default_wp_folders = array(
				WPMERGE_RELATIVE_ABSPATH . 'wp-admin',
				WPMERGE_RELATIVE_ABSPATH . 'wp-includes',
				WPMERGE_RELATIVE_WP_CONTENT_DIR,
			);
		$this->default_wp_files = array(
			WPMERGE_RELATIVE_ABSPATH . 'favicon.ico',
			WPMERGE_RELATIVE_ABSPATH . 'index.php',
			WPMERGE_RELATIVE_ABSPATH . 'license.txt',
			WPMERGE_RELATIVE_ABSPATH . 'readme.html',
			WPMERGE_RELATIVE_ABSPATH . 'robots.txt',
			WPMERGE_RELATIVE_ABSPATH . 'sitemap.xml',
			WPMERGE_RELATIVE_ABSPATH . 'wp-activate.php',
			WPMERGE_RELATIVE_ABSPATH . 'wp-blog-header.php',
			WPMERGE_RELATIVE_ABSPATH . 'wp-comments-post.php',
			WPMERGE_RELATIVE_ABSPATH . 'wp-config-sample.php',
			WPMERGE_RELATIVE_ABSPATH . 'wp-config.php',
			WPMERGE_RELATIVE_ABSPATH . 'wp-cron.php',
			WPMERGE_RELATIVE_ABSPATH . 'wp-links-opml.php',
			WPMERGE_RELATIVE_ABSPATH . 'wp-load.php',
			WPMERGE_RELATIVE_ABSPATH . 'wp-login.php',
			WPMERGE_RELATIVE_ABSPATH . 'wp-mail.php',
			WPMERGE_RELATIVE_ABSPATH . 'wp-settings.php',
			WPMERGE_RELATIVE_ABSPATH . 'wp-signup.php',
			WPMERGE_RELATIVE_ABSPATH . 'wp-trackback.php',
			WPMERGE_RELATIVE_ABSPATH . 'wp-salt.php',//some people added this file in wp-config.php
			WPMERGE_RELATIVE_ABSPATH . 'xmlrpc.php',
			WPMERGE_RELATIVE_ABSPATH . '.htaccess',
			WPMERGE_RELATIVE_ABSPATH . 'google',//google analytics files
			WPMERGE_RELATIVE_ABSPATH . 'gd-config.php',//go daddy configuration file
			WPMERGE_RELATIVE_ABSPATH . 'wp',//including all wp files on root
			WPMERGE_RELATIVE_ABSPATH . '.user.ini',//User custom settings / WordFence Files
			WPMERGE_RELATIVE_ABSPATH . 'wordfence-waf.php',//WordFence Files
		);
	}

	private function set_force_exclude_folders(){
		$this->force_exclude_folders = array(
			WPMERGE_RELATIVE_ABSPATH . 'wpmerge-bridge',
		);
	}

	private function default_exclude_dirs() {
		$upload_dir_path = wpmerge_get_upload_dir();
		$this->default_exclude_files = array(
			WPMERGE_RELATIVE_WP_CONTENT_DIR . "/managewp/backups",
			WPMERGE_RELATIVE_WP_CONTENT_DIR   . "/" . md5('iwp_mmb-client') . "/iwp_backups",
			WPMERGE_RELATIVE_WP_CONTENT_DIR . "/infinitewp",
			WPMERGE_RELATIVE_WP_CONTENT_DIR . "/".md5('mmb-worker')."/mwp_backups",
			WPMERGE_RELATIVE_WP_CONTENT_DIR . "/backupwordpress",
			WPMERGE_RELATIVE_WP_CONTENT_DIR . "/contents/cache",
			WPMERGE_RELATIVE_WP_CONTENT_DIR . "/content/cache",
			WPMERGE_RELATIVE_WP_CONTENT_DIR . "/cache",
			WPMERGE_RELATIVE_WP_CONTENT_DIR . "/logs",
			WPMERGE_RELATIVE_WP_CONTENT_DIR . "/old-cache",
			WPMERGE_RELATIVE_WP_CONTENT_DIR . "/w3tc",
			WPMERGE_RELATIVE_WP_CONTENT_DIR . "/cmscommander/backups",
			WPMERGE_RELATIVE_WP_CONTENT_DIR . "/gt-cache",
			WPMERGE_RELATIVE_WP_CONTENT_DIR . "/wfcache",
			WPMERGE_RELATIVE_WP_CONTENT_DIR . "/widget_cache",
			WPMERGE_RELATIVE_WP_CONTENT_DIR . "/bps-backup",
			WPMERGE_RELATIVE_WP_CONTENT_DIR . "/old-cache",
			WPMERGE_RELATIVE_WP_CONTENT_DIR . "/updraft",
			WPMERGE_RELATIVE_WP_CONTENT_DIR . "/nfwlog",
			WPMERGE_RELATIVE_WP_CONTENT_DIR . "/upgrade",
			WPMERGE_RELATIVE_WP_CONTENT_DIR . "/wflogs",
			WPMERGE_RELATIVE_WP_CONTENT_DIR . "/tmp",
			WPMERGE_RELATIVE_WP_CONTENT_DIR . "/backups",
			WPMERGE_RELATIVE_WP_CONTENT_DIR . "/updraftplus",
			WPMERGE_RELATIVE_WP_CONTENT_DIR . "/wishlist-backup",
			WPMERGE_RELATIVE_WP_CONTENT_DIR . "/wptouch-data/infinity-cache/",
			WPMERGE_RELATIVE_WP_CONTENT_DIR . "/mysql.sql",
			WPMERGE_RELATIVE_WP_CONTENT_DIR . "/debug.log",
			WPMERGE_RELATIVE_WP_CONTENT_DIR . "/Dropbox_Backup",
			WPMERGE_RELATIVE_WP_CONTENT_DIR . "/backup-db",
			WPMERGE_RELATIVE_WP_CONTENT_DIR . "/updraft",
			WPMERGE_RELATIVE_WP_CONTENT_DIR . "/w3tc-config",
			WPMERGE_RELATIVE_WP_CONTENT_DIR . "/aiowps_backups",
			WPMERGE_TEMP_DIR ,
			rtrim ( trim ( WPMERGE_RELATIVE_PLUGIN_DIR ) , '/' ), //wpmerge plugin's file path
			$upload_dir_path . "/wp-clone",
			$upload_dir_path . "/db-backup",
			$upload_dir_path . "/ithemes-security",
			$upload_dir_path . "/mainwp/backup",
			$upload_dir_path . "/backupbuddy_backups",
			$upload_dir_path . "/vcf",
			$upload_dir_path . "/pb_backupbuddy",
			$upload_dir_path . "/sucuri",
			$upload_dir_path . "/aiowps_backups",
			$upload_dir_path . "/gravity_forms",
			$upload_dir_path . "/mainwp",
			$upload_dir_path . "/snapshots",
			$upload_dir_path . "/wp-clone",
			$upload_dir_path . "/wp_system",
			$upload_dir_path . "/wpcf7_captcha",
			$upload_dir_path . "/wc-logs",
			$upload_dir_path . "/siteorigin-widgets",
			$upload_dir_path . "/wp-hummingbird-cache",
			$upload_dir_path . "/wp-security-audit-log",
			$upload_dir_path . "/freshizer",
			$upload_dir_path . "/report-cache",
			$upload_dir_path . "/cache",
			$upload_dir_path . "/et_temp",
			$upload_dir_path . "/bb-plugin",
			WPMERGE_RELATIVE_ABSPATH . "wp-admin/error_log",
			WPMERGE_RELATIVE_ABSPATH . "wp-admin/php_errorlog",
			WPMERGE_RELATIVE_ABSPATH . "error_log",
			WPMERGE_RELATIVE_ABSPATH . "error.log",
			WPMERGE_RELATIVE_ABSPATH . "debug.log",
			WPMERGE_RELATIVE_ABSPATH . "WS_FTP.LOG",
			WPMERGE_RELATIVE_ABSPATH . "security.log",
			WPMERGE_RELATIVE_ABSPATH . "dbcache",
			WPMERGE_RELATIVE_ABSPATH . "pgcache",
			WPMERGE_RELATIVE_ABSPATH . "objectcache",
			WPMERGE_RELATIVE_ABSPATH . 'wp-config.php'
		);
	}

	private function merge_wp_things(){
		$this->default_wp_files_n_folders = array_merge($this->default_wp_folders, $this->default_wp_files);
	}

	private function init_db(){
		global $wpdb;
		$this->db = $wpdb;
	}

	private function init_iterator(){
		require_once WPMERGE_PATH . '/includes/file_iterator.php';
	}

	private function load_saved_keys($category){
		if (!wpmerge_is_table_exist($this->db->base_prefix . 'wpmerge_inc_exc_contents')) {
			return ;
		}

		$this->load_exc_inc_files($category);
		$this->load_exc_inc_tables($category);
	}

	private function load_saved_keys_manually(){

		if (empty($_GET['category'])) {
			return ;
		}

		$this->load_exc_inc_files($_GET['category']);
		$this->load_exc_inc_tables($_GET['category']);
	}

	private function load_exc_inc_files($category){
		$this->excluded_files = $this->get_keys($action = 'exclude', $type = 'file', $category);
		$this->included_files = $this->get_keys($action = 'include', $type = 'file', $category);
	}

	private function load_exc_inc_tables($category){
		$this->excluded_tables = $this->get_keys($action = 'exclude', $type = 'table', $category);
		$this->included_tables = $this->get_keys($action = 'include', $type = 'table', $category);
	}

	public function insert_default_excluded_files(){
		$status = wpmerge_get_option('insert_default_excluded_files');

		if ($status) {
			return false;
		}

		$files = $this->format_excluded_files($this->default_exclude_files);

		foreach ($files as $file) {
			$file['category'] = 'development_site';
			$this->exclude_file_list($file, true);
		}

		wpmerge_update_option('insert_default_excluded_files', true);
	}

	public function add_default_excluded_files_while_db_update($new_exclude_files){

		$files = $this->format_excluded_files($new_exclude_files);

		foreach ($files as $file) {
			$file['category'] = 'development_site';
			$this->exclude_file_list($file, true);
		}
	}

	public function insert_default_structure_only_tables(){
		$status = wpmerge_get_option('insert_default_structure_only_tables');
		
		if ($status) {
			return false;
		}

		if(empty($this->default_structure_only_tables)){
			return false;
		}
		
		foreach ($this->default_structure_only_tables as $table) {
			$data = array();
			$data['file'] = $this->db->base_prefix.$table;//add prefix
			$data['category'] = 'development_site';
			$this->include_table_structure_only($data, $do_not_die = true);
		}

		wpmerge_update_option('insert_default_structure_only_tables', true);
	}

	private function format_excluded_files($files){

		if (empty($files)) {
			return false;
		}

		$selected_files = array();

		foreach ($files as $file) {
				$selected_files[] = array(
							"id"    => NULL,
							"file"  => $file,
							"isdir" => wpmerge_is_dir($file) ? 1 : 0 ,
						);
		}
		return $selected_files;
	}

	public function update_default_excluded_files(){
		$status = wpmerge_get_option('update_default_excluded_files');

		if ($status) {
			return false;
		}

		$new_default_exclude_files = $this->update_default_excluded_files_list();

		if (empty($new_default_exclude_files)) {
			wpmerge_update_option('update_default_excluded_files', true);
			return false;
		}

		$files = $this->format_excluded_files($new_default_exclude_files);

		foreach ($files as $file) {
			$file['category'] = 'development_site';
			$this->exclude_file_list($file, true);
		}

		wpmerge_update_option('update_default_excluded_files', true);
	}

	public function get_tables(/*$exc_wp_tables = false*/) {
		$this->load_saved_keys_manually();

		$tables = $this->get_all_tables();

		//following commented because not using this buggy method of inserting or updating tables.
		// if ($exc_wp_tables && !wpmerge_get_option('non_wp_tables_excluded')) {
		// 	//$this->exclude_non_wp_tabes($tables); //in WPMerge non wp tables defaultly not proccess in this dev case except of common_exim.php codes
		// 	$this->exclude_content_for_default_log_tables($tables);
		// 	wpmerge_update_option('non_wp_tables_excluded', true);
		// }

		$tables_arr = array();

		foreach ($tables as $table) {

			$table_status = $this->is_excluded_table($table);

			if ($table_status === 'table_included') {
				$temp = array(
					'title'            => $table,
					'key'              => $table,
					'content_excluded' => 0,
					'size'             => $this->get_table_size($table),
					'preselected'      => true,
				);
			} else if ($table_status === 'content_excluded') {
				$temp = array(
					'title'            => $table,
					'key'              => $table,
					'content_excluded' => 1,
					'size'             => $this->get_table_size($table),
					'preselected'      => true,
				);
			} else  {
				$temp = array(
					'title'       => $table,
					'key'         => $table,
					'size'        => $this->get_table_size($table),
					'preselected' => false,
				);
			}
			$temp['size_in_bytes'] = $this->get_table_size($table, false);
			$tables_arr[] = $temp;
		}
		die(json_encode($tables_arr));
	}

	public function get_root_files($exc_wp_files = false) {

		$this->load_saved_keys_manually();


		$root_files    = $this->get_wp_content_files();
		$root_files    = $this->get_abspath_files($exc_wp_files, array($root_files));

		die(json_encode($root_files));
	}

	private function get_abspath_files($exc_wp_files, $root_files){
		$files_object = $this->get_files_obj_by_path(WPMERGE_ABSPATH);

		if ($exc_wp_files && !wpmerge_get_option('non_wp_files_excluded')) {
			$this->exclude_non_wp_files($files_object);
			wpmerge_update_option('non_wp_files_excluded', true);
		}

		return $this->format_result_data($files_object, $root_files, $skip_wp_content = true);
	}

	public function get_files_obj_by_path($path, $recursive = false){

		$path = wpmerge_add_fullpath($path);

		$path = wpmerge_iterator_common::is_valid_path($path);

		if( is_array($path) ) {
			return $path;
		}

		if($recursive){
			return new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path , RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST, RecursiveIteratorIterator::CATCH_GET_CHILD);
		}

		return new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path , RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CATCH_GET_CHILD);
	}

	private function get_wp_content_files(){

		$is_excluded = $this->is_excluded_file(WPMERGE_WP_CONTENT_DIR, true);

		return array(
			'folder'        => true,
			'lazy'          => true,
			'size'          => '',
			'title'         => basename(WPMERGE_WP_CONTENT_DIR),
			'key'           => WPMERGE_WP_CONTENT_DIR,
			'size_in_bytes' => '0',
			'partial'       => $is_excluded ? false : true,
			'preselected'   => $is_excluded ? false : true,
		);
	}

	public function update_default_files_n_tables(){
		wpmerge_update_option('insert_default_excluded_files', false);

		$this->insert_default_excluded_files();
	}

	public function exclude_non_wp_files($file_obj){
		wpmerge_debug::log(func_get_args(), "--------" . __FUNCTION__ . "--------");
		$selected_files = array();
		foreach ($file_obj as $Ofiles) {
			$file_path = $Ofiles->getPathname();
			$file_path = wp_normalize_path($file_path);
			$file_name = basename($file_path);
			if ($file_name == '.' || $file_name == '..') {
				continue;
			}
			wpmerge_debug::log($file_path,'-----------$file_path----------------');
			if(!$this->is_wp_file($file_path)){
				$isdir = wpmerge_is_dir($file_path);
				$this->exclude_file_list(array('file'=> $file_path, 'isdir' => $isdir, 'category' => 'development_site' ), true);
			}
		}
	}

	private function exclude_non_wp_tabes($tables){
		foreach ($tables as $table) {
			if (!$this->is_wp_table($table)) {
				$this->exclude_table_list(array('file' => $table, 'category' => 'development_site'), true);
			}
		}
	}

	public function get_files_by_key($path) {
		$this->load_saved_keys_manually();
		$result_obj = $this->get_files_obj_by_path($path);
		$result = $this->format_result_data($result_obj);
		die(json_encode($result));
	}

	private function format_result_data($file_obj, $files_arr = array(), $skip_wp_content = false){

		if (empty($file_obj)) {
			return false;
		}

		foreach ($file_obj as $Ofiles) {

			$file_path = $Ofiles->getPathname();

			$file_path = wp_normalize_path($file_path);

			$file_name = basename($file_path);

			if ($file_name == '.' || $file_name == '..') {
				continue;
			}

			if (!$Ofiles->isReadable()) {
				continue;
			}

			$file_size = $Ofiles->getSize();

			$temp = array(
					'title' => basename($file_name),
					'key'   => $file_path,
					'size'  => $this->convert_bytes_to_hr_format($file_size),
				);

			$is_dir = wpmerge_is_dir($file_path);


			if ($is_dir) {
				if ($skip_wp_content) {
					if ($file_path === WPMERGE_WP_CONTENT_DIR) {
						continue;
					}
				}
				$is_excluded    = $this->is_excluded_file($file_path, true);
				$temp['folder'] = true;
				$temp['lazy']   = true;
				$temp['size']   = '';
			} else {
				$is_excluded = $this->is_excluded_file($file_path, false);

				if (!$is_excluded) {
					$is_excluded = ( $this->in_ignore_list($file_path) && !$this->is_included_file($file_path) ) ? true : false;
				}

				if (!$is_excluded) {
					$is_excluded = $this->is_bigger_than_allowed_file_size($file_path) ? true : false;
				}

				$temp['folder']        = false;
				$temp['size_in_bytes'] = $Ofiles->getSize();
			}

			if($is_excluded){
				$temp['partial']     = false;
				$temp['preselected'] = false;
			} else {
				$temp['preselected'] = true;
			}

			$files_arr[] = $temp;
		}

		$this->sort_by_folders($files_arr);

		return $files_arr;
	}

	private function sort_by_folders(&$files_arr) {
		if (empty($files_arr) || !is_array($files_arr)) {
			return false;
		}
		foreach ($files_arr as $key => $row) {
			$volume[$key]  = $row['folder'];
		}
		array_multisort($volume, SORT_DESC, $files_arr);
	}

	public function exclude_file_list($data, $do_not_die = false){

		$data = stripslashes_deep($data);

		if (empty($data['file']) || WPMERGE_ABSPATH ===  trailingslashit($data['file'])) {
			wpmerge_debug::log(array(), '--------Matches abspath--------');
			return false;
		}

		$data['file'] = wp_normalize_path($data['file']);

		if ($data['isdir']) {
			$this->delete($data['file'], $data['category'], $force = true);
		} else {
			$this->delete($data['file'], $data['category'], $force = false );
		}

		$data['file'] = wpmerge_remove_fullpath($data['file']);

		$result = $this->insert( array(
					'key'      => $data['file'],
					'type'     => 'file',
					'category' => $data['category'],
					'action'   => 'exclude',
					'is_dir'   => $data['isdir'],
				));

		if($do_not_die){
			return true;
		}

		if ($result) {
			wpmerge_die_with_json_encode( array('status' => 'success') );
		}
		wpmerge_die_with_json_encode( array('status' => 'error') );
	}

	public function include_file_list($data, $force_insert = false){

		$data = stripslashes_deep($data);

		if (empty($data['file'])) {
			return false;
		}

		if (empty($data['category'])) {
			$data['category'] = 'development_site';
		}

		$data['file'] = wp_normalize_path($data['file']);

		if ($data['isdir']) {
			$this->delete($data['file'], $data['category'], $force = true );
		} else {
			$this->delete($data['file'], $data['category'], $force = false );
		}

		if ( $this->is_wp_file($data['file'] ) && !$this->in_ignore_list( $data['file'] ) && !$this->is_bigger_than_allowed_file_size( $data['file'] ) ) {
			wpmerge_debug::log(array(), '---------------wordpress folder so no need to inserted ----------------');
			wpmerge_die_with_json_encode( array('status' => 'success') );
			return false;
		}

		$data['file'] = wpmerge_remove_fullpath($data['file']);

		$result = $this->insert( array(
					'key'      => $data['file'],
					'type'     => 'file',
					'category' => $data['category'],
					'action'   => 'include',
					'is_dir'   => $data['isdir'],
				));

		if ($result) {
			wpmerge_die_with_json_encode( array('status' => 'success') );
		}
		wpmerge_die_with_json_encode( array('status' => 'error') );
	}

	private function is_wp_file($file){
		if (empty($file)) {
			return false;
		}
		$file = wp_normalize_path($file);
		foreach ($this->default_wp_files_n_folders as $path) {

			$path = wpmerge_add_fullpath($path);
			$path = untrailingslashit($path);
			if(strpos($file, $path) !== false){
				return true;
			}
		}
		return false;
	}

	public function is_excluded_file($file, $is_dir = false){

		if (empty($file)) {
			return true;
		}

		if( !$is_dir && $this->in_ignore_list( $file ) && !$this->is_included_file( $file ) ) {
			wpmerge_debug::log($file, '---------------skip, file in ignore list-----------------');
			return true;
		}

		$file = wp_normalize_path($file);

		if (!$is_dir && $this->is_bigger_than_allowed_file_size( $file )) {
			wpmerge_debug::log($file, '---------------skip bigger than allowed size so reject,-----------------');
			return true;
		}

		if ($this->force_exclude_files($file)) {
			wpmerge_debug::log($file, '---------------skip force_exclude_files-----------------');
			return true;
		}

		$found = false;
		if ($this->is_wp_file($file)) {
			return $this->exclude_file_check_deep($file);
		}
		if (!$this->is_included_file($file)) {
			wpmerge_debug::log($file, '---------------skip file not in included list-----------------');
			return true;
		} else {
			return $this->exclude_file_check_deep($file);
		}
	}

	private function exclude_file_check_deep($file){

		if (empty($this->excluded_files)) {
			return false;
		}

		foreach ($this->excluded_files as $key_meta) {
			$value = str_replace('(', '-', $key_meta->key);
			$value = str_replace(')', '-', $value);
			$file = str_replace('(', '-', $file);
			$file = str_replace(')', '-', $file);
			if(strpos($file.'/', $value.'/') === 0){
				return true;
			}
		}
		return false;
	}

	private function get_keys($action, $type = 'file', $category = 'development_site'){

		$sql = "SELECT * FROM {$this->db->base_prefix}wpmerge_inc_exc_contents WHERE `type` = '$type' AND `action` = '$action' AND `category` = '$category'";
		$raw_data = $this->db->get_results($sql);

		if (empty($raw_data)) {
			return array();
		}

		$result = array();

		foreach ($raw_data as $value) {
			if ($type === 'file') {
				$value->key = wpmerge_add_fullpath($value->key);
			}
			elseif ($type === 'table') {
				//add_table_prefix
				$value->key = $this->db->base_prefix.$value->key;
			}

			$result[] = $value;
		}

		return empty($result) ? array() : $result;
	}

	public function is_included_file($file, $is_dir = false){
		$found = false;
		$file = wp_normalize_path($file);
		foreach ($this->included_files as $key_meta) {
			$value = str_replace('(', '-', $key_meta->key);
			$value = str_replace(')', '-', $value);
			$file = str_replace('(', '-', $file);
			$file = str_replace(')', '-', $file);
			if(strpos($file.'/', $value.'/') === 0){
				$found = true;
				break;
			}
		}
		return $found;
	}

	private function is_included_file_deep($file, $is_dir = false){
		$found = false;
		foreach ($this->included_files as $value->key) {
			if ($value->key === $file) {
				$found = true;
				break;
			}
		}
		return $found;
	}

	//table related functions
	public function exclude_table_list($data, $do_not_die = false){
		if (empty($data['file'])) {
			return false;
		}

		$this->delete($data['file'], $data['category'], $force = false, $type = 'table' );

		$result = $this->insert( array(
					'key'      => $data['file'],
					'type'     => 'table',
					'category' => $data['category'],
					'action'   => 'exclude',
				));

		if ($do_not_die) {
			return false;
		}
		if ($result) {
			wpmerge_die_with_json_encode( array('status' => 'success') );
		}
		wpmerge_die_with_json_encode( array('status' => 'error') );
	}

	private function insert($data){
		wpmerge_debug::log(func_get_args(), "--------" . __FUNCTION__ . "--------");

		if (empty($data['category'])) {
			$data['category'] = 'development_site';
		}

		$result = $this->db->insert("{$this->db->base_prefix}wpmerge_inc_exc_contents", $data);

		if ($result === false) {
			wpmerge_debug::log($this->db->last_error,'-----------$this->db->last_error----------------');
		}

		return $result;
	}

	public function include_table_list($data){
		if (empty($data['file'])) {
			return false;
		}
		
		$data['file'] = trim($data['file']);
		
		$data['file'] = wpmerge_remove_prefix($this->db->base_prefix, $data['file']);

		$this->delete($data['file'], $data['category'], $force = false, $type = 'table' );

		//if ($this->is_wp_table($data['file'])) {
			wpmerge_debug::log($data['file'], '---------------Wordpress table so no need to insert-----------------');
			wpmerge_die_with_json_encode( array('status' => 'success') );
		//}

		//following commented as we only handling wp tables in WPMerge

		// $result = $this->insert( array(
		// 		'key'                  => $data['file'],
		// 		'type'                 => 'table',
		// 		'category'             => $data['category'],
		// 		'action'               => 'include',
		// 		'table_structure_only' => 0,
		// 	));

		// if ($result) {
		// 	wpmerge_die_with_json_encode( array('status' => 'success') );
		// }

		// wpmerge_die_with_json_encode( array('status' => 'error') );
	}

	public function include_table_structure_only($data, $do_not_die = false){

		if (empty($data['file'])) {
			return false;
		}

		$data['file'] = trim($data['file']);
		
		$data['file'] = wpmerge_remove_prefix($this->db->base_prefix, $data['file']);

		$this->delete($data['file'], $data['category'], $force = false, $type = 'table' );

		$result = $this->insert( array(
				'key'                  => $data['file'],
				'type'                 => 'table',
				'category'             => $data['category'],
				'action'               => 'include',
				'table_structure_only' => 1,
			));

		if ($do_not_die) {
			return ;
		}

		if ($result) {
			wpmerge_die_with_json_encode( array('status' => 'success') );
		}

		wpmerge_die_with_json_encode( array('status' => 'error') );
	}

	private function delete($key, $category = 'development_site', $force = false, $type = 'file'){

		wpmerge_debug::log(func_get_args(), "--------" . __FUNCTION__ . "--------");

		if (empty($key)) {
			return false;
		}

		if (empty($category)) {
			$category = 'development_site';
		}

		if($type === 'file'){
			$key = wpmerge_remove_fullpath($key);
		}

		if ($force) {
			$re_sql = $this->db->prepare(" DELETE FROM {$this->db->base_prefix}wpmerge_inc_exc_contents WHERE `key` LIKE  '%%%s%%' AND `category` = '%s' ", $key, $category);
		} else {
			$re_sql = $this->db->prepare(" DELETE FROM {$this->db->base_prefix}wpmerge_inc_exc_contents WHERE `key` = '%s' AND `category` = '%s' ", $key, $category);
		}

		$result = $this->db->query($re_sql);

		if ($result === false) {
			wpmerge_debug::log($this->db->last_error,'-----------$this->db->last_error----------------');
		}
	}

	private function is_wp_table($table){
		if (preg_match('#^' . $this->db->base_prefix . '#', $table) === 1) {
			return true;
		}
		return false;
	}

	public function is_excluded_table($table){
		if (empty($table)) {
			return 'table_excluded';
		}

		$is_wp_table = false;

		if($this->is_wp_table($table) ){
			if($this->exclude_table_check_deep($table)){
				return 'table_excluded';
			}

			$is_wp_table = true;
		}

		return $this->is_included_table($table, $is_wp_table);
	}

	private function exclude_table_check_deep($table){
		foreach ($this->excluded_tables as $key_meta) {
			if (preg_match('#^' . $key_meta->key . '#', $table) === 1 ) {
				return true;
			}
		}

		return false;
	}

	private function is_included_table($table, $is_wp_table){

		foreach ($this->included_tables as $key_meta) {
			if (preg_match('#^' . $key_meta->key . '#', $table) === 1) {
				return $key_meta->table_structure_only == 1 ? 'content_excluded' : 'table_included';
			}
		}
		return $is_wp_table === true ? 'table_included' : 'table_excluded';
	}

	private function force_exclude_files($file){
		if (empty($file)) {
			return false;
		}

		$file = wp_normalize_path($file);

		foreach ($this->force_exclude_folders as $path) {

			$path = wpmerge_add_fullpath($path);

			if(strpos($file, $path) !== false){
				return true;
			}
		}

		return false;
	}

	public function analyze_inc_exc(){

		$excluded_tables = $this->analyze_tables();

		wpmerge_die_with_json_encode( array('status' => 'completed', 'files' => $this->analyze_files_response, 'tables' => $excluded_tables));
	}

	public function analyze_tables(){
		$tables = $this->get_all_tables();
		$exclude_tables = array();
		$counter = 0;
		foreach ($tables as $table) {
			$table_status = $this->is_excluded_table($table);
			if ($table_status !== 'table_included') {
				continue;
			}

			$size = $this->get_table_size($table, false);

			// if ($size < $this->max_table_size_allowed) {
			// 	continue;
			// }

			$exclude_tables[$counter]['title']         = $table;
			$exclude_tables[$counter]['key']           = $table;
			$exclude_tables[$counter]['size_in_bytes'] = $size;
			$exclude_tables[$counter]['size']          = $this->convert_bytes_to_hr_format($size);
			$exclude_tables[$counter]['preselected']   = true;
			$counter++;
		}

		return $exclude_tables;
	}

	private function is_log_table($table){
		foreach ($this->default_structure_only_tables as $skip_table) {
			if (stripos($table, $skip_table) !== false) {
				return true;
			}
		}

		return false;
	}

	//pending
	public function exclude_all_suggested_items($request, $category = 'development_site'){
		wpmerge_debug::log(func_get_args(), "--------" . __FUNCTION__ . "--------");

		if (empty($request['data'])) {
			wpmerge_die_with_json_encode( array('status' => 'success' ) );
		}

		if (!empty($request['data']['tables']) || !is_array($request['data']['tables'])) {
			$query = '';
			foreach ($request['data']['tables'] as $table) {
				$query .= empty($query) ? "(" : ",(" ;
				$query .= $this->wpdb->prepare("NULL, %s, 'table', %s, 'include', '1')", $table, $category);
				$this->delete($data['file'], $category, $force = false, $type = 'table' );
			}
			if (!empty($query)) {
				$query = "insert into " . $this->db->base_prefix . "wpmerge_inc_exc_contents (`id`, `key`, `type`, `category`, `action` ,`table_structure_only`) values $query";
				$this->db->query($query);
			}
		}


		if (empty($request['data']['files']) || !is_array($request['data']['files'])) {
			wpmerge_die_with_json_encode( array('status' => 'success' ) );
		}

		$query = '';
		foreach ($request['data']['files'] as $file) {
			$query .= empty($query) ? "(" : ",(" ;
			$query .= $this->wpdb->prepare("NULL, %s, 'file', %s, 'exclude', '0')",  wpmerge_remove_fullpath($file), $category);
			$this->delete($file, $category, $force = false );
		}

		if (empty($query)) {
			wpmerge_die_with_json_encode( array('status' => 'success' ) );
		}

		$query = "insert into " . $this->db->base_prefix . "wpmerge_inc_exc_contents (`id`, `key`, `type`, `category`, `action` ,`is_dir`) values $query";
		$this->db->query($query);
		wpmerge_die_with_json_encode( array('status' => 'success' ) );
	}

	public function get_all_excluded_files($category = 'development_site'){
		$files = $this->get_keys($action = 'exclude', $type = 'file', $category );

		if (empty($files)) {
			wpmerge_die_with_json_encode( array('status' => 'success', 'files' => array() ) );
		}

		$analyze_files_response = array();

		foreach ($files as $file) {

			if (!file_exists($file)) {
				continue;
			}

			$size = is_readable($file) ? filesize($file) : '-' ;

			$suggested_file['title']         = wpmerge_remove_fullpath($file);
			$suggested_file['key'] 	         = wpmerge_add_fullpath($file);;
			$suggested_file['size_in_bytes'] = $size;
			$suggested_file['size']          = is_numeric($size) ? $this->convert_bytes_to_hr_format($size) : $size;
			$suggested_file['preselected']   = false;
			$analyze_files_response[]        = $suggested_file;
		}

		wpmerge_die_with_json_encode( array('status' => 'success', 'files' => $analyze_files_response) );
	}

	// public function exclude_content_for_default_log_tables($tables = false){

	// 	if(wpmerge_get_option('exclude_content_for_default_log_tables')){
	// 		return ;
	// 	}

	// 	if (empty($tables)) {
	// 		$tables = $this->get_all_tables();
	// 	}

	// 	if (empty($tables)) {
	// 		wpmerge_update_option('exclude_content_for_default_log_tables', true);
	// 	}

	// 	foreach ($tables as $table) {
	// 		if(!$this->is_log_table($table)){
	// 			continue;
	// 		}

	// 		$this->include_table_structure_only(array('file' => $table, 'category' => 'development_site'), $do_not_die = true);
	// 	}

	// 	wpmerge_update_option('exclude_content_for_default_log_tables', true);
	// }

	public function get_user_excluded_files_more_than_size(){
		$raw_settings = wpmerge_get_option('user_excluded_files_more_than_size_settings');

		if (empty($raw_settings)) {
			return array(
				'status' => 'no',
				'size' => 50 * 1024 * 1024,
				'hr' => 50,
			);
		}

		$settings       = unserialize($raw_settings);
		$settings['hr'] = $this->convert_bytes_to_mb($settings['size']);
		return $settings;
	}

	public function save_settings($data){

		wpmerge_debug::log(func_get_args(), "--------" . __FUNCTION__ . "--------");

		if (empty($data)) {
			return ;
		}

		if (!empty($data['user_excluded_extenstions'])) {
			wpmerge_update_option('user_excluded_extenstions', strtolower($data['user_excluded_extenstions']) );
		} else {
			wpmerge_update_option('user_excluded_extenstions', false);
		}

		if (!isset($data['user_excluded_files_more_than_size_settings']) || !isset($data['user_excluded_files_more_than_size_settings']['size']) || $data['user_excluded_files_more_than_size_settings']['size'] == 0) {
			$updateSettings = array(
				'status' => 'no',
				'size' => 0,
			);
		} else{
			$updateSettings = array(
				'status' => 'yes',
				'size' => $this->convert_mb_to_bytes($data['user_excluded_files_more_than_size_settings']['size']),
				);
		}

		wpmerge_update_option('user_excluded_files_more_than_size_settings', serialize($updateSettings));

		wpmerge_die_with_json_encode(array('success' => true));
	}

	private function is_bigger_than_allowed_file_size($file){

		$settings = $this->get_user_excluded_files_more_than_size();

		if ($settings['status'] === 'no') {
			return false;
		}

		if ( $this->is_included_file($file) ) {
			return false;
		}

		if (filesize($file) > $settings['size']) {
			return true;
		}

		return false;
	}

	private function convert_bytes_to_mb($size){

		if (empty($size)) {
			return 0;
		}

		$size = trim($size);
		return ( ($size / 1024 ) / 1024 );
	}

	private function convert_mb_to_bytes($size){
		$size = trim($size);
		return $size * pow( 1024, 2 );
	}

	private function in_ignore_list($file) {

		if (empty($file)) {
			return false;
		}

		$user_excluded_extenstions = $this->get_user_excluded_extensions_arr();

		$file_extension = $this->get_extension($file);

		if (empty($file_extension)) {
			return false;
		}

		return in_array($file_extension, $user_excluded_extenstions);
	}

	private function get_user_excluded_extensions_arr() {

		if (!empty($this->cached_user_extensions)) {
			return $this->cached_user_extensions;
		}

		$raw_extenstions = wpmerge_get_option('user_excluded_extenstions');

		if ( empty ( $raw_extenstions ) ){
			return array();
		}

		$excluded_extenstions = array();
		$extensions = explode(',', strtolower( $raw_extenstions ) );

		foreach ($extensions as $extension) {
			if (empty($extension)) {
				continue;
			}

			$excluded_extenstions[] = trim( trim ( $extension ), '.');
		}

		return $excluded_extenstions;
	}

	private function get_extension($file) {

		$extension = explode ( ".", $file );

		if (empty($extension)) {
			return false;
		}

		$extension = end($extension);
		return $extension ? strtolower($extension) : false;
	}

	private function convert_bytes_to_hr_format($size){
		if (1024 > $size) {
			return $size.' B';
		} else if (1048576 > $size) {
			return round( ($size / 1024) , 2). ' KB';
		} else if (1073741824 > $size) {
			return round( (($size / 1024) / 1024) , 2). ' MB';
		} else if (1099511627776 > $size) {
			return round( ((($size / 1024) / 1024) / 1024) , 2). ' GB';
		}
	}

	private function get_all_tables(){
		$db_name = DB_NAME;
		$escaped_wp_base_prefix = wpmerge_esc_table_prefix($GLOBALS['wpdb']->base_prefix);
		$result_obj = $this->db->get_results("SELECT `TABLE_NAME` FROM `INFORMATION_SCHEMA`.`TABLES` WHERE `TABLE_NAME` LIKE '".$escaped_wp_base_prefix."%' AND `TABLE_NAME` NOT LIKE '%wpmerge\_%' AND `TABLE_SCHEMA` = '".$db_name."'", ARRAY_N);

		foreach ($result_obj as $table) {
			$tables[] = $table[0];
		}

		return $tables;
	}

	public function get_table_size($table_name, $return = true){

		$result = $this->db->get_results("SHOW TABLE STATUS LIKE '".$table_name."'");

		if (isset($result[0]->Data_length) && isset($result[0]->Index_length) && $return) {
			return $this->convert_bytes_to_hr_format(($result[0]->Data_length) + ($result[0]->Index_length));
		} else {
			return $result[0]->Data_length + $result[0]->Index_length;
		}

		return '0 B';
	}
}