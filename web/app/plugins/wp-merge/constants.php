<?php
/**
 * WPMerge
 * Copyright (c) 2018 Revmakx LLC
 * revmakx.com
 */

if(!defined('ABSPATH')){ exit; }

$_wpmerge_plugin_dir = dirname(__FILE__);
if(file_exists($_wpmerge_plugin_dir.'/_dev_config.php')){
	@include_once($_wpmerge_plugin_dir.'/_dev_config.php');
}

class wpmerge_constants{

	public function init_plugin(){
		$this->path();
		$this->debug();
		$this->general();
		$this->versions();
	}

	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	private function versions(){
		$this->define( 'WPMERGE_VERSION', '1.2.9' );
	}

	private function debug(){
		$this->define( 'WPMERGE_DEBUG', false );
		$this->define( 'WPMERGE_LOG_DIR', WPMERGE_TEMP_DIR . '/logs' );
	}

	private function general(){

		$this->define( 'WPMERGE_TIMEOUT', 23 );
		$this->define( 'WPMERGE_RELATIVE_MEMORY_LIMIT', (10*1024*1024) );
		$this->define( 'WPMERGE_HASH_FILE_LIMIT', 1024 * 1024 * 15); //15 MB

		$this->define( 'WPMERGE_OPTIMIZE_QUERIES_OLDER_THAN', 60 * 30 );//30 mins

		//below PHP 5.4
		$this->define( 'JSON_UNESCAPED_SLASHES', 64);
		$this->define( 'JSON_UNESCAPED_UNICODE', 256);

	}

	private function path(){

		$this->define( 'WPMERGE_ABSPATH', wp_normalize_path( ABSPATH ) );
		$this->define( 'WPMERGE_RELATIVE_ABSPATH', '/' );
		$this->define( 'WPMERGE_WP_CONTENT_DIR', wp_normalize_path( WP_CONTENT_DIR ) );
		$this->define( 'WPMERGE_WP_CONTENT_BASENAME', basename( WPMERGE_WP_CONTENT_DIR ) );
		$this->define( 'WPMERGE_RELATIVE_WP_CONTENT_DIR', '/' . WPMERGE_WP_CONTENT_BASENAME );
		$this->define( 'WPMERGE_PATH', untrailingslashit(WP_PLUGIN_DIR . '/' . basename(dirname(__FILE__))));
		$this->define( 'WPMERGE_PLUGIN_URL', plugin_dir_url( __FILE__ ));
		$this->define( 'WPMERGE_TEMP_DIR', WPMERGE_WP_CONTENT_DIR . '/wpmerge-temp');
		$this->define( 'WPMERGE_START_TIME', microtime(true));
		$this->define( 'WPMERGE_RELATIVE_PLUGIN_DIR', str_replace(WPMERGE_ABSPATH, WPMERGE_RELATIVE_ABSPATH, WPMERGE_PATH ) );

		$this->define( 'WPMERGE_SITE_URL', 'https://wpmerge.io/' );
		$this->define( 'WPMERGE_SERVICE_URL', WPMERGE_SITE_URL.'applogin/' );
		$this->define( 'WPMERGE_SITE_MY_ACCOUNT_URL', WPMERGE_SITE_URL.'my-account/' );
		$this->define( 'WPMERGE_SITE_SUBSCRIPTION_URL', WPMERGE_SITE_URL.'my-account/subscriptions/' );
		$this->define( 'WPMERGE_SITE_UPGRADE_URL', WPMERGE_SITE_URL.'pre-launch/' );
		$this->define( 'WPMERGE_SITE_LOST_PASS_URL', WPMERGE_SITE_URL.'my-account/lost-password/' );

		$this->define( 'WPMERGE_UPDATE_URL', 'https://wpmerge.io/rx-update-server/' );

	}
}
