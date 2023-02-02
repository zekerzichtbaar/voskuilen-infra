<?php
/**
 * WPMerge
 * Copyright (c) 2018 Revmakx LLC
 * revmakx.com
 */

if(!defined('ABSPATH')){ exit; }

Class wpmerge_file_iterator{

	private $offset,
			$files,
			$state,
			$save_files_query,
			$cached_files_counter = 0,
			$wpdb,
			$exclude_array = array(),
			$exluded_contents,
			$root;

	const BULK_INSERT_LIMIT = 1000;

	public function __construct(){

	}

	private function set_flags($root, $state){

		wpmerge_debug::log($state,'-----------$state----------------');

		$this->root = wp_normalize_path($root);
		$this->state = $state;

		global $wpdb;

		$this->wpdb = $wpdb;
	}

	private function set_offset(){
		$offset = wpmerge_get_option('wpmerge_tmp_offset');

		wpmerge_debug::log($offset,'-----------$offset----------------');

		$this->offset = empty($offset) ? array() : explode('-', $offset);
	}

	public function check_changed_files($root, $state){

		$this->set_flags($root, $state);

		$this->set_offset();

		$iterator = $this->create_recursive($this->root);

		$this->iterate($iterator, $key = false);

		$this->save_files();

		$this->reset();

		throw new wpmerge_exception("iterator completed", 200);//used for non error throwing purpose
	}

	public function reset(){
		wpmerge_delete_option('wpmerge_tmp_offset');
	}

	public function get_file_list($source){
		if (empty($source)) {
			return ;
		}

		$source = wp_normalize_path($source);

		if (trailingslashit($source) === ABSPATH || !file_exists($source) ) {
			return ;
		}

		$iterator = $this->create_recursive($source);
		return $this->get_list($iterator);
	}

	private function get_list($iterator){
		$list = array();
		while ($iterator->valid()) {
			//Forming current path from iterator
			$path = $iterator->getPathname();

			$path = wp_normalize_path($path);

			if ($iterator->isDot() || !$iterator->isReadable() ) {
				$iterator->next();
				continue;
			}

			if ($iterator->isDir()) {
				array_push($list, array('path' => $path, 'type' => 'd'));
			} else{
				array_push($list, array('path' => $path, 'type' => 'f'));
			}

			$iterator->next();
		}

		return $list;
	}

	private function create_recursive($path){
		return new DirectoryIterator($path);
	}

	public function iterate($iterator, $key_recursive) {

		$this->seek($iterator);

		while ($iterator->valid()) {

			//Forming current path from iterator
			$path = $iterator->getPathname();

			$path = wp_normalize_path($path);

			//Mapping keys
			$key = ($key_recursive !== false) ? $key_recursive . '-' . $iterator->key() : $iterator->key();

			//Do recursive iterator if its a dir
			if (!$iterator->isDot() && $iterator->isReadable() && $iterator->isDir()) {

				if (!$this->is_excluded($path)) {
				//create new object for new dir
					$sub_iterator = $this->create_recursive($path);

					$this->iterate($sub_iterator, $key);
				}

			}

			//Ignore dots paths
			if (!$iterator->isDot() && !$this->is_excluded($path)) {
				$this->process($iterator, $key);
			}

			//move to next file
			$iterator->next();
		}

		$this->check_counter_and_insert();
	}

	private function seek(&$iterator) {

		if (!count($this->offset)) {
			return false;
		}

		//Moving satelite into position.
		$iterator->seek($this->offset[0]);

		//remove positions from the array after moved satelite
		unset($this->offset[0]);

		//reset array index
		$this->offset = array_values($this->offset);

	}

	private function is_excluded($file){
		$this->set_exluded_contents();
		$result = $this->exluded_contents->is_excluded_file($file);//strstr($file, 'wpmerge') || strstr($file, 'wp-db-sync') || //commented 

		wpmerge_debug::log('is_excluded - ' . $file, 'Result - ' . $result);

		return $result;
	}

	private function set_exluded_contents(){
		if (!empty($this->exluded_contents)) {
			return ;
		}

		$this->exluded_contents = new wpmerge_inc_exc_contents();
	}

	private function is_changed($mtime){
        return $mtime < $this->state['data']['min_mtime'] ? false : true;
	}

	private function process($iterator, $key){
		wpmerge_debug::log_resource_usage('process_files', 500);
		$file   = $iterator->getPathname();

		$file   = wp_normalize_path($file);

		$is_dir = wpmerge_is_dir($file);

		if ($is_dir) {
			$this->check_timeout($key);
			return ;
		}

		if (!$this->is_changed($iterator->getMTime())) {
			$this->check_timeout($key);
			return ;
		}

		$this->prepare_query($iterator, $file);

		$this->check_counter_and_insert();

		$this->check_timeout($key);
	}

	private function prepare_query($iterator, $file){
		$this->save_files_query .= empty($this->save_files_query) ? "(" : ",(" ;

		$this->save_files_query .= $this->wpdb->prepare("%s, 'file', 'dev_to_prod', %d, %d)", $file, $iterator->getMTime(), $iterator->getsize());
	}

	private function check_counter_and_insert(){
		if($this->cached_files_counter++ < self::BULK_INSERT_LIMIT){
			return ;
		}

		$this->save_files();
		$this->cached_files_counter = 0;
	}

	private function check_timeout($key){
		if(!wpmerge_is_time_limit_exceeded() ) {
			return ;
		}

		$this->save_files();
		$this->save_offset($key);

		throw new wpmerge_exception("file_iteration_timedout");//used for non error throwing purpose
	}

	private function save_offset($key){
		wpmerge_update_option('wpmerge_tmp_offset', $key);
	}

	private function save_files(){
		if( empty($this->save_files_query) ){
			$this->save_files_query = '';
			return;
		}
		
		$sql = "INSERT INTO " . $this->wpdb->base_prefix . "wpmerge_process_files (`path`, `type`, `group`, `mtime`, `size`) VALUES $this->save_files_query";
		$result = $this->wpdb->query($sql);
		wpmerge_debug::log($result,'-----------$result----------------');

		if ($result === false) {
			wpmerge_debug::log($sql, 'failed');
		}

		$this->save_files_query = '';
	}
}

Class wpmerge_iterator_common{

	private $iterator;

	public function __construct(){
		$this->iterator = new wpmerge_file_iterator();
	}

	public static function is_valid_path($path){
		$default = array();

		if (empty($path)) {
			return $default;
		}

		$path = rtrim($path, '/');

		$path = wp_normalize_path($path);

		if (empty($path)) {
			return $default;
		}

		$basename = basename($path);

		if ($basename == '..' || $basename == '.') {
			return $default;
		}

		if (!is_readable($path)) {
			return $default;
		}

		return $path;
	}

	private function delete_dir($source){

		$file_list = $this->iterator->get_file_list($source);

		if(!empty($file_list)){
			foreach ( $file_list as $file_info) {
				if ('f' == $file_info['type']) {
					$this->delete_file($file_info['path']);
				} else if('d' == $file_info['type']){
					$this->delete_dir($file_info['path']);
					$this->rmdir($file_info['path']);
				}
			}
		}

		$this->rmdir($source);
	}

	private function rmdir($dir){
		if (file_exists($dir)) {
			rmdir($dir);
		}
	}

	private function delete_file($file){
		unlink($file);
	}

	public function delete($source, $delete_any_dir = false){
		if(empty($source)){
			return ;
		}

		if (ABSPATH === trailingslashit($source)) {
			//I cannot delete the root
			return ;
		}

		if (!$delete_any_dir && strstr($source, 'wpmerge') === false) {
			//I cannot delete other dir
			return ;
		}

		$this->delete_dir($source);
	}
}