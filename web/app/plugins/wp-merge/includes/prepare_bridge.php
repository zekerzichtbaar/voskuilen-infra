<?php
/**
 * WPMerge
 * Copyright (c) 2018 Revmakx LLC
 * revmakx.com
 */

if(!defined('ABSPATH')){ exit; }

class wpmerge_prepare_bridge{

	private $iterator;
	private $wpdb;

	public  function __construct(){
		$this->init_iterator();
		$this->init_db();
	}

	private function init_db(){
		global $wpdb;
		$this->wpdb = $wpdb;
	}

	public function prepare(){
		try {

			//delete existing bridge.
			$this->delete();

			$this->set_flags();

			$config_file = $this->create_config_file();

			if (!empty($config_file['error'])) {
				return $config_file;
			}

			$bridge_dir = $this->get_bridge_dir();

			if (!empty($bridge_dir['error'])) {
				return $bridge_dir;
			}

			set_time_limit(0);

			$copy_result = $this->copy_bridge_files($config_file, $bridge_dir);

			if (!empty($copy_result) && !empty($copy_result['error'])) {
				return ( $copy_result );
			}

			$bridge_path = trailingslashit( site_url() ) . wpmerge_get_option('current_bridge_file_name');

			$bridge_path .= wpmerge_is_dev_env() ? '' : '/bridge/request.php';

			return array(
				'bridge_path' =>  $bridge_path ,
			);

		} catch (Exception $e) {
			return ( array('error' => $e->getMessage()) );
		}
	}

	private function set_flags(){
		if(wpmerge_debug::is_debug_enabled()){
			$bridge_dir = 'wpmerge-bridge';
		}
		else{
			$bridge_dir = "wpmerge-bridge-" . hash("crc32", time());
		}
		//initializing restore options
		wpmerge_update_option('current_bridge_file_name', $bridge_dir, true);
		wpmerge_update_option('admin_url', network_admin_url() , true);

	}

	public function copy_bridge_files($config_file, $bridge_dir) {

		$dirs = array(
			'bridge',
			//'debug-chart',//commented as we are sending this in production
			'includes',
			'css',
			'js',
			'lib',
			'templates'
		);

		foreach ($dirs as $dir) {

			$plugin_folder  = trailingslashit(WPMERGE_PATH .'/'. $dir);
			$bridge_dir_sub = $bridge_dir . $dir;

			if (!is_dir($bridge_dir_sub)) {
				if (!mkdir($bridge_dir_sub)) {
					return array('error' => 'Cannot create Plugin Directory in bridge.');
				}
			}

			$copy_res = $this->copy_dir($plugin_folder, $bridge_dir_sub, array('multicall_exit' => true));

			if (!$copy_res) {
				return array('error' => 'Cannot copy Plugin Directory(' . $plugin_folder . ').');
			}

		}

		$files                          = array();
		$files['config.php']            = $config_file; //config file which was prepared already
		$files['dev.php']               = WPMERGE_PATH . '/dev.php';
		$files['parse_compare_sql.php'] = WPMERGE_PATH . '/parse_compare_sql.php';
		$files['prod.php']              = WPMERGE_PATH . '/prod.php';
		$files['index.php']             = WPMERGE_PATH . '/bridge/index.php';

		foreach ($files as $basename => $file) {
			$copy_result = $this->copy($file, $bridge_dir . $basename);
			if (!$copy_result) {
				return array('error' => 'Cannot copy Bridge files(' . $file . ').');
			}
		}

		unlink($config_file);
		return true;
	}

	public function copy($source, $destination) {

		$copy_result = copy($source, $destination);

		if (!$copy_result) {
			return true;
		}

		return $copy_result;
	}

	private function copy_dir($source, $destination = '') {

		$source      = trailingslashit($source);
		$destination = trailingslashit($destination);
		$file_list   = $this->get_dir_list($source); // get file list here

		foreach ( $file_list as $file_info) {

			$source_file      = $file_info['path'];
			$destination_file = $destination . str_replace(WPMERGE_PATH, '', basename($file_info['path'])) ;

			if ('f' == $file_info['type']) {

				if (!$this->copy($source_file, $destination_file)) {
					chmod($destination_file, 0644);

					if (!$this->copy($source_file, $destination_file)) {
						return false;
					}
				}

			} elseif ('d' == $file_info['type']) {

				if (!is_dir($destination_file)) {

					if (!mkdir($destination_file)) {
						return false;
					}

				}

				if (!$this->copy_dir($source_file, $destination_file)) {
					return false;
				}
			}
		}
		return true;
	}

	private function get_dir_list($source){
		return $this->iterator->get_file_list($source);
	}

	private function init_iterator(){
		require WPMERGE_PATH . '/includes/file_iterator.php';
		$this->iterator = new wpmerge_file_iterator();
	}

	private function get_bridge_dir(){

		$bridge_dir = ABSPATH . wpmerge_get_option('current_bridge_file_name');

		wpmerge_debug::log($bridge_dir,'-----------$bridge_dir----------------');

		$bridge_dir = trailingslashit($bridge_dir);

		if (is_dir($bridge_dir)) {
			return $bridge_dir;
		}

		if (mkdir($bridge_dir)) {
			return $bridge_dir;
		}

		return array('error' => 'Cannot create Bridge Directory in root.');
	}



	private function create_config_file() {

		$contents_to_be_written = "
		<?php

		/** The name of the database for WordPress */
		define('DB_NAME', '" . DB_NAME . "');

		/** MySQL database username */
		define('DB_USER', '" . DB_USER . "');

		/** MySQL database password */
		define('DB_PASSWORD', '" . str_replace( "'", "\\'", DB_PASSWORD ) . "');

		/** MySQL hostname */
		define('DB_HOST', '" . DB_HOST . "');

		/** Database Charset to use in creating database tables. */
		define('DB_CHARSET', '" . DB_CHARSET . "');

		/** The Database Collate type. Don't change this if in doubt. */
		define('DB_COLLATE', '" . DB_COLLATE . "');

		define('DB_PREFIX', '" . $this->wpdb->base_prefix . "');

		define('WPMERGE_VERSION', '" . WPMERGE_VERSION . "');

		define('WPMERGE_BRIDGE', true);

		define('WPMERGE_BRIDGE_NAME', '" . wpmerge_get_option('current_bridge_file_name') . "');

		define('WPMERGE_TEMP_DIR', '" . WPMERGE_TEMP_DIR . "');

		define('WPMERGE_PLUGIN_URL', '" . WPMERGE_PLUGIN_URL . "');

		define('WPMERGE_PATH', '" . WPMERGE_PATH . "');

		define('WPMERGE_START_TIME', time());

		define('WPMERGE_TIMEOUT', " . WPMERGE_TIMEOUT . ");

		define('WPMERGE_RELATIVE_MEMORY_LIMIT', " . WPMERGE_RELATIVE_MEMORY_LIMIT . ");

		define('WP_MAX_MEMORY_LIMIT', '256M');

		define('WP_DEBUG', false);

		define('WP_DEBUG_DISPLAY', false);

		define('ABSPATH',  '" . ABSPATH . "');

		define('WP_CONTENT_DIR',  '" . wp_normalize_path(WP_CONTENT_DIR) . "');

		define('WPMERGE_ABSPATH',  '" . wp_normalize_path(ABSPATH) . "');

		define('WPMERGE_RELATIVE_ABSPATH',  '/');

		define('WPMERGE_WP_CONTENT_DIR',  '" . wp_normalize_path(WP_CONTENT_DIR) . "');

		define('WPMERGE_WP_CONTENT_BASENAME',  '" . basename( WPMERGE_WP_CONTENT_DIR ) . "');

		define('WPMERGE_RELATIVE_PLUGIN_DIR',  '" . str_replace(WPMERGE_ABSPATH, WPMERGE_RELATIVE_ABSPATH, WPMERGE_PATH ) . "');

		define('WPMERGE_RELATIVE_WP_CONTENT_DIR',  '/" . WPMERGE_WP_CONTENT_BASENAME . "');

		define('WPMERGE_DEBUG',  '" . WPMERGE_DEBUG . "');

		define('WPMERGE_LOG_DIR',  '" . WPMERGE_LOG_DIR . "');

		define('WPMERGE_SITE_URL',  '" . WPMERGE_SITE_URL . "');
		define('WPMERGE_SERVICE_URL',  '" . WPMERGE_SERVICE_URL . "');
		define('WPMERGE_SITE_MY_ACCOUNT_URL',  '" . WPMERGE_SITE_MY_ACCOUNT_URL . "');
		define('WPMERGE_SITE_SUBSCRIPTION_URL',  '" . WPMERGE_SITE_SUBSCRIPTION_URL . "');
		define('WPMERGE_SITE_UPGRADE_URL',  '" . WPMERGE_SITE_UPGRADE_URL . "');
		define('WPMERGE_SITE_LOST_PASS_URL',  '" . WPMERGE_SITE_LOST_PASS_URL . "');";

		if(defined('MULTISITE')){
			$contents_to_be_written .= "
			define('MULTISITE',  '" . MULTISITE . "');";
		}

		if(defined('SUBDOMAIN_INSTALL')){
			$contents_to_be_written .= "
			define('SUBDOMAIN_INSTALL',  '" . SUBDOMAIN_INSTALL . "');";
		}

		if(defined('VHOST')){
			$contents_to_be_written .= "
			define('VHOST',  '" . VHOST . "');";
		}

		if(defined('SUNRISE')){
			$contents_to_be_written .= "
			define('SUNRISE',  '" . SUNRISE . "');";
		}

		$temp_dir = WPMERGE_TEMP_DIR;

		if(!file_exists($temp_dir)){
            $mkDir = mkdir($temp_dir, 0775, true);
            if(!$mkDir){
				return array('error' => 'Unable to create WPMerge temp directory - ('.$temp_dir.').');
            }
        }

		$config_file = $temp_dir . '/config.php';

		$result = file_put_contents($config_file, $contents_to_be_written, 0644);

		if (!$result) {
			return array('error' => 'Error creating config like file.');
		}

		return $config_file;
	}

	public function delete(){
		$bridge_dir = wpmerge_get_option('current_bridge_file_name');

		if (empty($bridge_dir)) {
			return ;
		}

		$source = ABSPATH . $bridge_dir;

		$common_iterator = new wpmerge_iterator_common();

		$common_iterator->delete($source);
	}
}