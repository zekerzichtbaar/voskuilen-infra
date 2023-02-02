<?php
/**
 * WPMerge
 * Copyright (c) 2018 Revmakx LLC
 * revmakx.com
 */

class wpmerge_include {

	protected $root;

	public function __construct(){
		$this->root = dirname( dirname(__FILE__) ) . '/';
	}

	public function init(){

		$this->include_config();
		$this->include_wp_files();
		$this->include_common_functions();
		$this->include_heart();
		$this->include_primary_file();

	}

	private function include_common_functions(){
		$this->include_file( $this->root . '/includes/common_func.php');
	}

	private function include_wp_files(){
		$this->include_file( $this->root . "/bridge/wp-db-modified.php" );
	}

	private function include_config(){
		//Loading wp functions first to use some core functions in config file.
		$this->include_file( $this->root . "/bridge/wp-functions-modified.php" );
		$this->include_file( $this->root . "config.php");
	}

	private function include_heart(){
		$this->include_file($this->root . '/includes/debug.php');
		$this->include_file($this->root . '/includes/common_func.php');
		$this->include_file($this->root . '/includes/common_db.php');
		$this->include_file($this->root . '/bridge/common.php');
	}

	private function include_primary_file(){
		if(wpmerge_is_dev_env()){
			return $this->include_file($this->root . '/dev.php');
		}

		return $this->include_file($this->root . '/prod.php');
	}

	public function include_file($file){
		// if(!file_exists($file)){
		// 	return false;
		// }

		require_once $file;
	}
}