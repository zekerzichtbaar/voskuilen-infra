<?php
/**
 * WPMerge
 * Copyright (c) 2018 Revmakx LLC
 * revmakx.com
 */

if(!defined('ABSPATH')){ exit; }

class wpmerge_common_clear_cache{
	
	public function purgeAllCacheOfCachingPlugins(){
		//all methods starting with 'deleteAll' should check for active plugin and callables then run delete process
		//generally we can expect any one of this plugin may present in one site

		$result = array();
		try{
			$result['wp-fastest-cache'] = $this->deleteAllWPFCCache();
			$result['wp-super-cache'] = $this->deleteAllWPSuperCache();
			$result['w3-total-cache'] = $this->deleteAllW3TotalCache();
			$result['wp-rocket'] = $this->deleteAllWPRocketCache();
			$result['comet-cache'] = $this->deleteAllCometCache();
			$result['autoptimize'] = $this->deleteAllAutoptimizeCache();
		}
		catch(Exception $e){
			wpmerge_debug::log($e, 'purgeAllCacheOfCachingPlugins_exception');
		}
		wpmerge_debug::log($result, 'purgeAllCacheOfCachingPlugins_result');
	}

	/*
	 * This function will return the WpFastestCache is loaded or not
	 */
	private function checkWPFCPlugin() {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		if ( is_plugin_active( 'wp-fastest-cache/wpFastestCache.php' ) ) {
			@include_once(WP_PLUGIN_DIR . '/wp-fastest-cache/wpFastestCache.php');
			if (class_exists('WpFastestCache')) {
				return true;
			}
		}
		return false;
	}

	/*
	 * This function will return the WP Super cache Plugin is loaded or not
	 */
	private function checkWPSuperCachePlugin() {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		if ( is_plugin_active( 'wp-super-cache/wp-cache.php' ) ) {
			@include_once(WP_PLUGIN_DIR . '/wp-super-cache/wp-cache.php');
			if (function_exists('wp_cache_clean_cache')) {
				return true;
			}
		}
		return false;
	}

	/*
	 * This function will return the W3 Total cache Plugin is loaded or not
	 */
	private function checkW3TotalCachePlugin() {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		if ( is_plugin_active( 'w3-total-cache/w3-total-cache.php' ) ) {
			@include_once(WP_PLUGIN_DIR . '/w3-total-cache/w3-total-cache.php');
			if (function_exists('w3tc_flush_all')) {
				return true;
			}
		}
		return false;
	}

	/*
	 * This function will return the Comet cache plugin is loaded or not
	 */
	private function checkCometCachePlugin() {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		if ( is_plugin_active( 'comet-cache/comet-cache.php' ) ) {
			@include_once(WP_PLUGIN_DIR . '/comet-cache/comet-cache.php');
			if (class_exists('WebSharks\CometCache\Classes\ApiBase')) {
				return true;
			}
		}
		return false;
	}

	/*
	 * This function will return the WP Rocket plugin is loaded or not
	 */
	private function checkWPRocketPlugin() {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		if ( is_plugin_active( 'wp-rocket/wp-rocket.php' ) ) {
			@include_once(WP_PLUGIN_DIR . '/wp-rocket/wp-rocket.php');
			if (function_exists('rocket_clean_domain') && function_exists('rocket_clean_minify') && function_exists('rocket_clean_cache_busting') && function_exists('create_rocket_uniqid')) {
				return true;
			}
		}
		return false;
	}

	/*
	 * This function will return the Autoptimize plugin is loaded or not
	 */
	private function checkAutoptimizePlugin() {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		if ( is_plugin_active( 'autoptimize/autoptimize.php' ) ) {
			@include_once(WP_PLUGIN_DIR . 'autoptimize/autoptimize.php');
			if (class_exists('autoptimizeCache') && is_callable(array('autoptimizeCache', 'clearall'))) {
				return true;
			}
		}
		return false;
	}

	/*
	 * This function will delete all cache files for WP Fastest Plugin
	 */
	private function deleteAllWPFCCache(){
		if($this->checkWPFCPlugin() && class_exists('wpmerge_common_clear_wpfastest_cache')) {
			$wpfc = new wpmerge_common_clear_wpfastest_cache();
			$wpfc->deleteMinifiedCache();
			$response = $wpfc->_getSystemMessage();
			if ($response[1] == 'error') {
				return array('error' => $response[0], 'error_code' => 'wpfc_plugin_delete_cache');
			}elseif($response[1] == 'success'){
				return array('success' => $response[0]);
			}else{
				return array('error' => 'Unable to perform WP Fastest cache', 'error_code' => 'wpfc_plugin_delete_cache');
			}
		} else {
			return array('error'=>"WP fastest cache not activated", 'error_code' => 'wpfc_plugin_is_not_activated');
		}
	}

	/*
	 * This function will delete all cache files for WP Super Cache Plugin
	 */
	private function deleteAllWPSuperCache(){
		if($this->checkWPSuperCachePlugin()) {
			global $file_prefix;
			$wp_super_cache = wp_cache_clean_cache($file_prefix, true);
			if ($wp_super_cache == false) {
				return array('error' => 'Unable to perform WP Super cache', 'error_code' => 'wp_super_cache_plugin_delete_cache');
			}
			return array('success' => 'All cache files have been deleted');
		} else {
			return array('error'=>"WP Super cache not activated", 'error_code' => 'wp_super_cache_plugin_is_not_activated');
		}
	}

	/*
	 * This function will delete all cache files for W3 Total Cache Plugin
	 */
	private function deleteAllW3TotalCache(){
		if($this->checkW3TotalCachePlugin()) {
			w3tc_flush_all();
			return array('success' => 'All cache files have been deleted');
		} else {
			return array('error'=>"W3 Total cache not activated", 'error_code' => 'wp_super_cache_plugin_is_not_activated');
		}
	}

	/*
	 * This function will delete all cache files for Comet Cache Plugin
	 */
	private function deleteAllCometCache(){
		if($this->checkCometCachePlugin()) {
			$api = new WebSharks\CometCache\Classes\ApiBase();
			$plugin = $api->plugin(true);
			$response = $plugin->clearCache(true);
			if ($response === false) {
				return array('error' => 'Unable to perform Comet cache', 'error_code' => 'comet_cache_plugin_delete_cache');
			}
			return array('success' => 'All cache files have been deleted');
		} else {
			return array('error'=>"Comet cache not activated", 'error_code' => 'comet_cache_plugin_is_not_activated');
		}
	}

	/*
	 * This function will delete all cache files for WP Rocket Plugin
	 */
	private function deleteAllWPRocketCache(){
		if($this->checkWPRocketPlugin()) {
			$lang = '';
			// Remove all cache files.
			rocket_clean_domain( $lang );

			// Remove all minify cache files.
			rocket_clean_minify();

			// Remove cache busting files.
			rocket_clean_cache_busting();

			// Generate a new random key for minify cache file.
			$options = get_option( WP_ROCKET_SLUG );
			$options['minify_css_key'] = create_rocket_uniqid();
			$options['minify_js_key'] = create_rocket_uniqid();
			remove_all_filters( 'update_option_' . WP_ROCKET_SLUG );
			update_option( WP_ROCKET_SLUG, $options );
			return array('success' => 'All cache files have been deleted');
		} else {
			return array('error'=>"WP Rocket not activated", 'error_code' => 'comet_cache_plugin_is_not_activated');
		}
	}

	/*
	 * This function will delete all cache files for Autoptimize Plugin
	 */
	private function deleteAllAutoptimizeCache(){
		if ($this->checkAutoptimizePlugin()) {
			$wp_auto_optimize = autoptimizeCache::clearall();
			if ($wp_auto_optimize == false) {
				return array('error' => 'Unable to perform Autoptimize cache', 'error_code' => 'auto_optimize_cache_plugin_delete_cache');
			}
			return array('success' => 'All cache files have been deleted');
		}else {
			return array('error'=>"Autoptimize not activated", 'error_code' => 'auto_optimize_plugin_is_not_activated');
		}
	}

}

if(class_exists('WpFastestCache')){
	class wpmerge_common_clear_wpfastest_cache extends WpFastestCache{
		
		public function deleteAllCache(){
			if( is_callable( array( $this, 'deleteCacheToolbar') ) ){
				$this->deleteCacheToolbar();
			}
		}

		public function deleteMinifiedCache(){
			if( is_callable( array( $this, 'deleteCssAndJsCacheToolbar') ) ){
				$this->deleteCssAndJsCacheToolbar();
			}
		}

		public function _getSystemMessage(){
			if( is_callable( array( $this, 'getSystemMessage') ) ){
				$this->getSystemMessage();
			}
		}
	}
}
