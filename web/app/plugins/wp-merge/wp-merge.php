<?php
/*
Plugin Name: WPMerge
Plugin URI: https://wpmerge.io/
Description: WPMerge which syncs development and production sites.
Author: Revmakx
Version: 1.2.9
Author URI: https://wpmerge.io/
*/

if(!defined('ABSPATH')){ exit; }

wpmerge_define_constants();

include_once(WPMERGE_PATH . '/includes/debug.php');
include_once(WPMERGE_PATH . '/includes/common_func.php');
include_once(WPMERGE_PATH . '/includes/common_db.php');

register_activation_hook( __FILE__, 'wpmerge_plugin_on_activate_common');
register_deactivation_hook( __FILE__, 'wpmerge_plugin_on_deactivate_common');

wpmerge_check_env_and_include();
wpmerge_common_http_post_calls_hooks();

//TP update
require 'plugin-update-checker/plugin-update-checker.php';
$MyUpdateChecker =  \WPMerge\PluginUpdateChecker\Puc_v4_Factory::buildUpdateChecker(
	WPMERGE_UPDATE_URL . '?action=get_metadata&slug=wp-merge', //Metadata URL.
	__FILE__, //Full path to the main plugin file.
	'wp-merge' //Plugin slug. Usually it's the same as the name of the directory.
);

function wpmerge_define_constants() {
	include_once ( dirname(__FILE__) . '/constants.php' );
	$constants = new wpmerge_constants();
	$constants->init_plugin();
}

function wpmerge_plugin_on_activate_common(){
	$wpmerge_common_db_obj = new wpmerge_common_db();
	$wpmerge_common_db_obj->on_plugin_activation();
	if(wpmerge_is_dev_env()){
		wpmerge_dev_on_plugin_activate();
	}
}

function wpmerge_plugin_on_deactivate_common(){
	if(wpmerge_is_dev_env()){
		wpmerge_dev_on_plugin_deactivate();
	}
}

function wpmerge_check_env_and_include(){
	if(!wpmerge_is_valid_env_saved()){
		wpmerge_init_setup_menu();
		wpmerge_init_setup_required_admin_notice();
		return false;
	}

	if(wpmerge_is_dev_env()){
		include_once(WPMERGE_PATH . '/dev.php');
	}
	elseif(wpmerge_is_prod_env()){
		include_once(WPMERGE_PATH . '/prod.php');
	}

}

function wpmerge_add_setup_required_admin_notice(){
	?>
    <div class="notice notice-info is-dismissible">
        <p>Setup required for WPMerge <a href="<?php echo network_admin_url( 'admin.php?page=wpmerge_setup_env' ); ?>">Click here</a>.</p>
    </div>
    <?php
}


function wpmerge_init_setup_required_admin_notice(){
	if(empty($_GET['page']) || strpos($_GET['page'], 'wpmerge') === false){
		if(is_multisite()){
			add_action('network_admin_notices', 'wpmerge_add_setup_required_admin_notice');
		} else {
			add_action('admin_notices', 'wpmerge_add_setup_required_admin_notice');
		}
	}
}

function wpmerge_add_setup_menu(){

	$page_title = 'WPMerge Setup';
	$menu_title = 'WPMerge Setup';
	$capability = 'activate_plugins';
	$menu_slug  = 'wpmerge_setup_env';
	$function   = 'wpmerge_setup_env_page';
	$icon_url   = '';
	$position   = 61;
	add_menu_page($page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position);
}

function wpmerge_init_setup_menu(){
	if ( is_multisite() ) {
		add_action('network_admin_menu', 'wpmerge_add_setup_menu');
	} else {
		add_action('admin_menu', 'wpmerge_add_setup_menu');
	}
}

function wpmerge_setup_env_page(){

	wpmerge_set_error_reporting();

	$is_requiements_met = true;
	$requirements_result = wpmerge_check_min_version_requirements();
	if(!empty($requirements_result) && is_array($requirements_result)){
		$is_requiements_met = false;
	}
	include(WPMERGE_PATH . '/templates/common_setup_env.php');
}

function wpmerge_common_http_post_calls_hooks(){
	add_action('admin_post_wpmerge_setup_env', 'wpmerge_process_post_setup_env');
}

function wpmerge_process_post_setup_env(){
	wpmerge_set_error_reporting();

	if(empty($_POST)){
		return false;//better handle this error
	}
	$_action = isset($_POST['action']) ? $_POST['action'] : '';
	if($_POST['action'] !== 'wpmerge_setup_env' || empty($_POST['wpmerge_env'])){
		return false;//better handle this error
	}
	if(!wpmerge_is_valid_env($_POST['wpmerge_env'])){
		return false;
	}

	wpmerge_set_env($_POST['wpmerge_env']);
	if(wpmerge_is_dev_env()){
		//install dev db tables, triggers and customizations
		include_once(WPMERGE_PATH . '/dev.php');
		wpemerge_on_setup_site_as_dev();

		//assuming this will be called once(only during initial setup) redirecting to make db modifications
		wp_redirect( network_admin_url( 'admin.php?page=wpmerge_dev_service_login&redirect_to='.urlencode('wpmerge_dev_initial_setup&dev_do_initial_setup=1') ) );
		exit();
	}
	elseif(wpmerge_is_prod_env()){
		include_once(WPMERGE_PATH . '/prod.php');
		wpmerge_prod_generate_api_key();
		wp_redirect( network_admin_url( 'admin.php?page=wpmerge_prod_setting' ) );
		exit();
	}
}

function wpmerge_check_min_version_requirements(){
	//php 5.6
	//MySQL 5.1 
	//WP 4.0 
	$required = array();
	$required['php']['version'] = '5.6';
	$required['mysql']['version'] = '5.1';
	$required['wp']['version'] = '4.0';

	$mysql_full_version = $GLOBALS['wpdb']->get_var("SELECT VERSION()");
	$mysql_tmp = explode('-', $mysql_full_version);
	$mysql_version = array_shift($mysql_tmp);

	$php_version = PHP_VERSION;
	$php_tmp = explode('-', $php_version);
	$php_version = array_shift($php_tmp);

	$installed = array();
	$installed['php']['version'] = $php_version;
	$installed['mysql']['version'] = $mysql_version;
	$installed['wp']['version'] = get_bloginfo( 'version' );

	$is_all_ok = true;
	if (version_compare($php_version, $required['php']['version'], '<')) {
		//not ok
		$is_all_ok = false;
	}
	if (version_compare($mysql_version, $required['mysql']['version'], '<')) {
		//not ok
		$is_all_ok = false;
	}
	if (version_compare($installed['wp']['version'], $required['wp']['version'], '<')) {
		//not ok
		$is_all_ok = false;
	}
	if($is_all_ok){
		return true;
	}
	return array('required' => $required, 'installed' => $installed);
}

add_action('admin_enqueue_scripts', 'wpmerge_common_admin_enqueue_scripts');//i think its adding in all pages in wp dashboard need to improve
function wpmerge_common_admin_enqueue_scripts(){
	wp_register_script('wpmerge_common_admin_script', plugin_dir_url( __FILE__ ) . 'js/common_admin.js', array('jquery'), WPMERGE_VERSION, true);
	wp_enqueue_script('wpmerge_common_admin_script');
	wp_localize_script('wpmerge_common_admin_script', 'wpmerge_ajax', array( 'ajax_url' => admin_url('admin-ajax.php')));
}


add_action('admin_enqueue_scripts', 'wpmerge_common_admin_enqueue_styles');
function wpmerge_common_admin_enqueue_styles() {
	wp_register_style('wpmerge_common_style', WPMERGE_PLUGIN_URL . 'css/common.css', array(), WPMERGE_VERSION);
	wp_enqueue_style('wpmerge_common_style');
}

if (get_option('wpmerge_first_activation_redirect')) {
	add_action('admin_init', 'wpmerge_on_activate_redirect');
}
function wpmerge_on_activate_redirect() {

	if (get_option('wpmerge_first_activation_redirect')) {
		update_option('wpmerge_first_activation_redirect', false);//don't change to delete_option, as we are using add_option it will add only if slug not exisits that maintain 1 time use

		//in rare case lets redirect to respective dev and prod page
		if(!defined('LWMWP_SITE')){
			if(!isset($_GET['activate-multi'])){
				if(wpmerge_is_dev_env()){

					wp_redirect( network_admin_url( 'admin.php?page=wpmerge_dev_options') );
					exit();
				}
				elseif(wpmerge_is_prod_env()){
		
					wp_redirect( network_admin_url( 'admin.php?page=wpmerge_prod_setting' ) );
					exit();
				}
				else{
					wp_redirect(network_admin_url( 'admin.php?page=wpmerge_setup_env' ));
					exit();
				}
			}
		}
	}
}

function wpmerge_db_update(){

	$options_table = $GLOBALS['wpdb']->base_prefix . 'wpmerge_options';
	if( !wpmerge_is_table_exist($options_table) ){
		return false;
	}

	//$current_db_version = get_option('wpmerge_db_version');//WP options table loads faster because of autoload and cache so using it here //for multisite it is creating issues as it adds for each site separately.
	
	$current_db_version = wpmerge_get_option('wpmerge_db_version');

	if(empty($current_db_version)){
		$current_db_version = get_option('wpmerge_db_version');
	}

	$old_db_version = $current_db_version;
	
	if(empty($current_db_version)){
		$current_db_version = '0.0';
	}
	
	if(version_compare($current_db_version, '1.0.5', '<')){
		//run the update code
		if(wpmerge_is_dev_env()){
			$is_dev_db_modifications_applied = wpmerge_dev_is_dev_db_modifications_applied();
			if($is_dev_db_modifications_applied){
				wpmerge_update_option('is_dev_db_modifications_required', 1);
			}
		}

		$current_db_version = '1.0.5';
	}

	if(version_compare($current_db_version, '1.0.6', '<')){
		//run the update code

		if(wpmerge_is_dev_env()){
			//add to exclusion file list
			$inc_exc_contents_obj =  new wpmerge_inc_exc_contents();
			$new_exclude_files = array(WPMERGE_RELATIVE_ABSPATH . 'wp-config.php');
			$inc_exc_contents_obj->add_default_excluded_files_while_db_update($new_exclude_files);
		}

		//removing old 'wpmerge_db_version' in options tables(multiple tables in multi site), which is moved to wpmerge_db_version
		if(is_multisite()){
			$blogs_ids = get_sites();
			if(!empty($blogs_ids)){
				foreach( $blogs_ids as $b ){
					switch_to_blog( $b->blog_id );
					//Do stuff
					delete_option('wpmerge_db_version');
					restore_current_blog();
				}
			}
		}
		else{
			delete_option('wpmerge_db_version');
		}
		
		$current_db_version = '1.0.6';
	}
	if(version_compare($current_db_version, '1.1.0', '<')){
		//run the update code

		$log_queries_table = $GLOBALS['wpdb']->base_prefix .'wpmerge_log_queries';

		if(wpmerge_is_table_exist($log_queries_table)){

			$index_result = $GLOBALS['wpdb']->get_results("SHOW INDEX FROM `$log_queries_table` WHERE `Key_name` = 'type' or `Key_name` = 'is_record_on' ");

			if(empty($index_result) || count($index_result) < 2){
				wpmerge_wpdb::query("ALTER TABLE `$log_queries_table`
				ADD INDEX `type` (`type`),
				ADD INDEX `is_record_on` (`is_record_on`)");//this query will consume some time, base on number of rows.			
			}
		}
		
		$current_db_version = '1.1.0';
	}

	if(version_compare($current_db_version, '1.2.0', '<')){
		//run the update code
		
		if(wpmerge_is_dev_env()){
			$is_dev_db_modifications_applied = wpmerge_dev_is_dev_db_modifications_applied();
			if($is_dev_db_modifications_applied){
				wpmerge_update_option('is_dev_db_modifications_required', 1);
			}
		}
		
		$current_db_version = '1.2.0';
	}

	if(version_compare($current_db_version, '1.2.3', '<')){
		//run the update code
		
		$log_queries_table = $GLOBALS['wpdb']->base_prefix .'wpmerge_log_queries';
		$db_name = DB_NAME;

		if(wpmerge_is_table_exist($log_queries_table)){

			$column_exists = $GLOBALS['wpdb']->get_results("SELECT * 
			FROM information_schema.COLUMNS 
			WHERE 
				TABLE_SCHEMA = '$db_name' 
			AND TABLE_NAME = '$log_queries_table' 
			AND COLUMN_NAME = 'query_b'");

			if( empty($column_exists) ){
				wpmerge_wpdb::query("ALTER TABLE `$log_queries_table`
				CHANGE `query` `query` longtext NULL AFTER `id`,
				ADD `query_b` longtext NULL AFTER `query`;");//this query will consume some time, base on number of rows.
			}
		}

		if(wpmerge_is_table_exist($log_queries_table)){

			$index_exists = $GLOBALS['wpdb']->get_results("SHOW INDEX FROM `$log_queries_table` WHERE  `Key_name` = 'table_name'");

			if( empty($index_exists) ){
				wpmerge_wpdb::query("ALTER TABLE `$log_queries_table`
				ADD INDEX `table_name` (`table_name`);");//this query will consume some time, base on number of rows.
			}
		}

		if(wpmerge_is_dev_env()){
			$table_relog = $GLOBALS['wpdb']->base_prefix .'wpmerge_relog';
			if(!wpmerge_is_table_exist($table_relog)){
				$_db_collation = wpmerge_get_collation();
				wpmerge_wpdb::query("CREATE TABLE IF NOT EXISTS `".$table_relog."` (
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
				) ENGINE=InnoDB " . $_db_collation . ";");
			}
		}

		//wfKnownFileList table to structure only list
		$table_inc_exc_contents = $GLOBALS['wpdb']->base_prefix .'wpmerge_inc_exc_contents';
		if(wpmerge_is_dev_env() && wpmerge_is_table_exist($table_inc_exc_contents)){
			$exclude_table_exists = $GLOBALS['wpdb']->get_row("SELECT `key` FROM `$table_inc_exc_contents` WHERE `key` = 'wfKnownFileList' AND `type` = 'table' AND `category` = 'development_site'");

			if(empty($exclude_table_exists)){
				$GLOBALS['wpdb']->query("INSERT INTO `".$table_inc_exc_contents."` (`key`, `type`, `category`, `action`, `table_structure_only`) VALUES ('wfKnownFileList', 'table', 'development_site', 'include', '1')");	
			}
		}

		if(wpmerge_is_dev_env()){
			$is_dev_db_modifications_applied = wpmerge_dev_is_dev_db_modifications_applied();
			if($is_dev_db_modifications_applied){
				wpmerge_update_option('is_dev_db_modifications_required', 1);
			}
		}
		
		$current_db_version = '1.2.3';
	}

	if(version_compare($current_db_version, '1.2.6', '<')){
		//run the update code
		
		$log_queries_table = $GLOBALS['wpdb']->base_prefix .'wpmerge_log_queries';
		$db_name = DB_NAME;

		if(wpmerge_is_table_exist($log_queries_table)){

			$column_exists = $GLOBALS['wpdb']->get_results("SELECT * 
			FROM information_schema.COLUMNS 
			WHERE 
				TABLE_SCHEMA = '$db_name' 
			AND TABLE_NAME = '$log_queries_table' 
			AND COLUMN_NAME = 'unique_column_value'");

			if( empty($column_exists) ){
				wpmerge_wpdb::query("ALTER TABLE `$log_queries_table`
				ADD `unique_column_value` varchar(191) NULL AFTER `table_name`,
				ADD `query_stmt_type` varchar(64) NULL AFTER `type`;");//this query will consume some time, base on number of rows.
			}

			$column_exists = $GLOBALS['wpdb']->get_results("SELECT * 
			FROM information_schema.COLUMNS 
			WHERE 
				TABLE_SCHEMA = '$db_name' 
			AND TABLE_NAME = '$log_queries_table' 
			AND COLUMN_NAME = 'unique_column_value'");

			if( !empty($column_exists) ){
				$index_result = $GLOBALS['wpdb']->get_results("SHOW INDEX FROM `$log_queries_table` WHERE `Key_name` = 'unique_column_value' or `Key_name` = 'query_stmt_type' or `Key_name` = 'logtime' ");

				if(empty($index_result) || count($index_result) < 3){
					wpmerge_wpdb::query("ALTER TABLE `$log_queries_table`
					ADD INDEX `unique_column_value` (`unique_column_value`),
					ADD INDEX `query_stmt_type` (`query_stmt_type`),
					ADD INDEX `logtime` (`logtime`)");//this query will consume some time, base on number of rows.
				}
			}
		}

		if(wpmerge_is_dev_env()){
			$is_dev_db_modifications_applied = wpmerge_dev_is_dev_db_modifications_applied();
			if($is_dev_db_modifications_applied){
				wpmerge_update_option('is_dev_db_modifications_required', 1);
			}
		}

		$current_db_version = '1.2.6';
	}
	
	if($old_db_version !== $current_db_version){
		wpmerge_update_option('wpmerge_db_version', $current_db_version);
	}
}

function wpmerge_init(){
	wpmerge_db_update();
}

add_action('init', 'wpmerge_init');