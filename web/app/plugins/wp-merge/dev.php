<?php
/**
 * WPMerge
 * Copyright (c) 2018 Revmakx LLC
 * revmakx.com
 */

if(!defined('ABSPATH')){ exit; }

include_once(WPMERGE_PATH . '/includes/dev_db.php');
include_once(WPMERGE_PATH . '/includes/dev_http_admin_log.php');
include_once(WPMERGE_PATH . '/includes/dev_deploy.php');
include_once(WPMERGE_PATH . '/includes/dev_exim_control.php');
include_once(WPMERGE_PATH . '/includes/inc_exc_contents.php');
include_once(WPMERGE_PATH . '/includes/dev_query_selector.php');
include_once(WPMERGE_PATH . '/includes/service_auth.php');
include_once(WPMERGE_PATH . '/includes/same_server_db_clone.php');
include_once(WPMERGE_PATH . '/includes/swap_tables.php');

include_once(WPMERGE_PATH . '/templates/dev_query_browser.php');

add_action('plugins_loaded', 'wpmerge_dev_init');
add_action('shutdown', 'wpmerge_dev_shutdown', 99999);

wpmerge_download_changed_files_list();

function wpmerge_dev_on_plugin_activate(){
	//$wpmerge_dev_db_obj = new wpmerge_dev_db();
	//$wpmerge_dev_db_obj->do_modifications(false);
	wpmerge_dev_add_cron();
}

function wpmerge_dev_on_plugin_deactivate(){
	$wpmerge_dev_db_obj = new wpmerge_dev_db();
	$wpmerge_dev_db_obj->remove_modifications();
	wpmerge_dev_remove_cron();
}

function wpemerge_on_setup_site_as_dev(){

}

add_action('wp_ajax_wpmerge_dev_record_switch', 'wpmerge_dev_record_switch');
function wpmerge_dev_record_switch(){
	if(isset($_POST['action']) && $_POST['action'] === 'wpmerge_dev_record_switch'){
		$do_record_switch = isset($_POST['wpmerge_dev_record_switch']) ? $_POST['wpmerge_dev_record_switch'] : '';
		if(in_array($do_record_switch, array('on', 'off'))){
			if($do_record_switch == 'on'){
				$is_recording_on = 1;
			}
			elseif($do_record_switch == 'off'){
				$is_recording_on = 0;
			}
			$result = wpmerge_update_option('is_recording_on', $is_recording_on);

			$wpmerge_dev_db_obj = new wpmerge_dev_db();
			$recording_state = $wpmerge_dev_db_obj->get_recording_state();
			if($do_record_switch == 'on' && $recording_state['status_slug'] == 'off'){
				//recording is on because of some reason, so turn off the flag
				wpmerge_update_option('is_recording_on', 0);
			}

			$response = array();
			$response['status'] = 'success';
			$response['get_recording_state'] = $recording_state;
			echo wpmerge_prepare_response($response);
			exit();
		}
	}
}

if ( is_multisite() ) {
	add_action('network_admin_menu', 'wpmerge_dev_menu');
} else {
	add_action('admin_menu', 'wpmerge_dev_menu');
}

//add_action('admin_menu', 'wpmerge_dev_menu');
function wpmerge_dev_menu() {

	add_menu_page($page_title = 'WPMerge', $menu_title = 'WPMerge', $capability = 'activate_plugins', $menu_slug = 'wpmerge_dev_options', $function = 'wpmerge_dev_options_page', $icon_url = '', $position = 61);

	add_submenu_page(	$parent_slug = 'wpmerge_dev_options', $page_title = 'WPMerge Settings', $menu_title = 'Settings', $capability = 'activate_plugins',  $menu_slug = 'wpmerge_dev_settings', $function = 'wpmerge_dev_settings_page');

	add_submenu_page($parent_slug = null, $page_title = 'WPMerge Dev Initial Setup', $menu_title = 'Dev Initial Setup', $capability = 'activate_plugins',  $menu_slug = 'wpmerge_dev_initial_setup', $function = 'wpmerge_dev_initial_setup_page');

	add_submenu_page($parent_slug = null, $page_title = 'WPMerge Service Login', $menu_title = 'Service Login', $capability = 'activate_plugins',  $menu_slug = 'wpmerge_dev_service_login', $function = 'wpmerge_dev_service_login_page');

	add_submenu_page($parent_slug = 'wpmerge_dev_options', $page_title = 'WPMerge Query browser', $menu_title = 'Queries', $capability = 'activate_plugins',  $menu_slug = 'wpmerge_dev_query_browser', $function = 'wpmerge_dev_query_browser_page');

	$internal_dev_options_page_parent_slug = null;
	if(wpmerge_debug::is_debug_enabled()){
		$internal_dev_options_page_parent_slug = 'wpmerge_dev_options';
	}
	add_submenu_page($parent_slug = $internal_dev_options_page_parent_slug, $page_title = 'Internal Dev options', $menu_title = 'Internal Dev options', $capability = 'activate_plugins',  $menu_slug = 'wpmerge_internal_dev_options', $function = 'wpmerge_internal_dev_options');
}

if(wpmerge_dev_is_valid_db_modifications_required_is_set()){

	if(is_multisite() && function_exists('is_network_admin') && is_network_admin()){
		add_action('network_admin_notices', 'wpmerge_dev_show_db_modification_required_notice');
	} else {
		add_action('admin_notices', 'wpmerge_dev_show_db_modification_required_notice');
	}
}
function wpmerge_dev_show_db_modification_required_notice(){
	if( isset($_GET['page']) &&  $_GET['page'] == 'wpmerge_dev_options' && isset($_GET['wpmerge_do']) && $_GET['wpmerge_do'] == 'db_mod' ){
		?>
<script type="text/javascript">
var wpmerge_dev_db_mod_required_popup_ignore = true;
</script>
		<?php
		return;
	}
	?>
	<div class="notice error wpmerge_dev_db_modification_required_notice">
		<p>WPMerge: We've paused recording changes since we detected a change in the DB structure. To continue recording, we need to modify a few aspects of the DB. <a href="admin.php?page=wpmerge_dev_options&wpmerge_do=db_mod&show_adv=1" target="_blank">Modify DB now <span class="dashicons dashicons-external" style="font-size: 14px; margin-left: -3px;"></span></a></p>
	</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
	setTimeout(() => {
		wpmerge_dev_db_mod_required_popup(); 
        }, 500);  
});
</script>

	<?php
}

function wpmerge_dev_options_page(){
	wpmerge_set_error_reporting();
	include(WPMERGE_PATH . '/templates/dev_options.php');
}

function wpmerge_dev_settings_page(){
	wpmerge_set_error_reporting();
	include(WPMERGE_PATH . '/templates/dev_settings.php');
}

function wpmerge_dev_initial_setup_page(){
	wpmerge_set_error_reporting();
	include(WPMERGE_PATH . '/templates/dev_initial_setup.php');
}

function wpmerge_internal_dev_options(){
	wpmerge_set_error_reporting();
	include(WPMERGE_PATH . '/templates/unit-testing.php');
}

function wpmerge_dev_service_login_page(){
	wpmerge_set_error_reporting();
	include(WPMERGE_PATH . '/templates/dev_service_login.php');
}


add_action('admin_enqueue_scripts', 'wpmerge_dev_admin_enqueue_scripts');
function wpmerge_dev_admin_enqueue_scripts(){
	wp_register_script('wpmerge_dev_admin_script', WPMERGE_PLUGIN_URL . 'js/dev_admin.js', array('jquery'), WPMERGE_VERSION, true);
	wp_enqueue_script('wpmerge_dev_admin_script');

	wp_register_script('wpmerge_dev_exim_script', WPMERGE_PLUGIN_URL . 'js/dev_exim.js', array('jquery'), WPMERGE_VERSION, true);
	wp_enqueue_script('wpmerge_dev_exim_script');

	if(isset($_GET['page']) && $_GET['page'] === 'wpmerge_dev_settings' ){
		wp_enqueue_script('wpmerge_jquery_ui_custom_js', WPMERGE_PLUGIN_URL . '/lib/treeView/jquery-ui.custom.js',   array('jquery'), WPMERGE_VERSION);
		wp_enqueue_script('wpmerge_fancytree_js',        WPMERGE_PLUGIN_URL . '/lib/treeView/jquery.fancytree.js',   array('jquery'), WPMERGE_VERSION);
		wp_enqueue_style('wpmerge_fancytree_css',        WPMERGE_PLUGIN_URL . '/lib/treeView/skin/ui.fancytree.css', array(), WPMERGE_VERSION);
	}

	wp_localize_script('wpmerge_dev_admin_script', 'wpmerge_dev_ajax', array( 'ajax_url' => admin_url('admin-ajax.php'), 'admin_url' => network_admin_url()));
}

add_action('admin_enqueue_scripts', 'wpmerge_dev_admin_enqueue_styles');
function wpmerge_dev_admin_enqueue_styles() {
	wp_register_style('wpmerge_dev_admin_style', WPMERGE_PLUGIN_URL . 'css/dev_admin.css', array(), WPMERGE_VERSION);
	wp_enqueue_style('wpmerge_dev_admin_style');
}

//
// add_action('admin_bar_menu', 'wpmerge_dev_add_record_menu', 999);
// function wpmerge_dev_add_record_menu($wp_admin_bar) {
// 	$is_recording_on = wpmerge_dev_is_recording_on();
// 	$class = $is_recording_on ? 'wpmerge_dev_record_on' : 'wpmerge_dev_record_off';
// 	$content = $is_recording_on ? 'R on' : 'R off';
// 	$args = array(
// 		'id'	=> 'wpmerge_dev_record_menu',
// 		'title'	=> '<div id="wpmerge_dev_record_switch" class="'.$class.'"><span>'.$content.'</span></div>',
// 		'parent' => 'top-secondary'/*,
// 		'href'  => 'http://mysite.com/my-page/',
// 		'meta'  => array( 'onclick' => 'my-toolbar-page' )*/
// 	);
// 	global $wp_admin_bar;
// 	$wp_admin_bar->add_node( $args );
// }


// add_action('admin_bar_menu', 'wpmerge_dev_add_db_mod_notify_menu', 999);
// function wpmerge_dev_add_db_mod_notify_menu($wp_admin_bar) {
// 	$args = array(
// 		'id'	=> 'wpmerge_dev_db_mod_notify_menu',
// 		'title'	=> '<div id="wpmerge_dev_db_mod_notify_cont"></div>',
// 		'parent' => 'top-secondary'/*,
// 		'href'  => 'http://mysite.com/my-page/',
// 		'meta'  => array( 'onclick' => 'my-toolbar-page' )*/
// 	);
// 	global $wp_admin_bar;
// 	$wp_admin_bar->add_node( $args );
// }


function wpmerge_dev_init(){
	wpmerge_set_plugin_top_priority();

	wpmerge_dev_check_service_auth_if_required_redirect();

	wpmerge_dev_bind_for_plugin_deactivate_when_required();

	//following commented for logs to keep working in WP Frontend(Non Admin dashboard)
	// if( ! defined( 'WP_ADMIN' ) ){
	// 	return;
	// }
	// if(WP_ADMIN !== true){
	// 	return;
	// }
	if(wpmerge_dev_http_admin_log::is_excluded_http_call()){
		return;
	}

	define('WPMERGE_DEV_CAN_RECORD', wpmerge_dev_can_record());

	wpmerge_dev_check_and_enable_logging();//if enabled it will start recording, marked as recording based on wpmerge_dev_is_recording_on()
	wpmerge_dev_check_and_on_recorder();//it will mark as recorded
	wpmerge_dev_http_admin_log::start();
}

function wpmerge_dev_shutdown(){
	$last_error = error_get_last();

	if(!empty($last_error)){
		wpmerge_debug::log($last_error['message'] . " on " . $last_error['file'] . " line " . $last_error['line'], "--------last err--------");
	}

	$rewrite_multi_insert_query_obj = new wpmerge_dev_rewrite_multi_insert_query();
	$rewrite_multi_insert_query_obj->check_and_process();

	wpmerge_db_mod_on_the_go::check_and_do_db_mod_for_required_tables();

	//one more loggin of error, if above method call creating an issue
	$last_error = error_get_last();

	if(!empty($last_error)){
		wpmerge_debug::log($last_error['message'] . " on " . $last_error['file'] . " line " . $last_error['line'], "--------last err--------");
	}

	wpmerge_dev_http_admin_log::end();
}

function wpmerge_set_plugin_top_priority(){
	$plugin_base = 'wp-merge/wp-merge.php';
	$active_plugins  = get_option('active_plugins');

	if (reset($active_plugins) === $plugin_base) {
		return;
	}

	$wpmerge_key = array_search($plugin_base, $active_plugins);

	if ($wpmerge_key === false) {
		return;
	}

	unset($active_plugins[$wpmerge_key]);
	array_unshift($active_plugins, $plugin_base);
	update_option('active_plugins', array_values($active_plugins));
}

function wpmerge_dev_is_recording_on(){
	$is_recording_on = wpmerge_get_option('is_recording_on');
	return (bool)$is_recording_on;
}

function wpmerge_dev_can_record(){
	$wpmerge_dev_db_obj = new wpmerge_dev_db();
	$state = $wpmerge_dev_db_obj->get_recording_state();
	return $state['can_record'];
}

function wpmerge_dev_on_any_plugin_activate_and_deactivate( $plugin, $network_wide ) {
	wpmerge_set_plugin_top_priority();
}
add_action( 'activated_plugin', 'wpmerge_dev_on_any_plugin_activate_and_deactivate', 99999, 2 );//plugin order changes when activates. But not sure on deactivate.
add_action( 'deactivated_plugin', 'wpmerge_dev_on_any_plugin_activate_and_deactivate', 99999, 2 );

function wpmerge_dev_bind_for_plugin_deactivate_when_required(){
	
	$filter_name = is_multisite() ? 'network_admin_plugin_action_links' : 'plugin_action_links';//network_admin_plugin_action_links and plugin_action_links are very similar
	add_filter( $filter_name, 'wpmerge_dev_alert_plugin_deactivate_when_required', 10, 5 );

}

function wpmerge_dev_alert_plugin_deactivate_when_required($actions, $plugin_file, $plugin_data){
	static $plugin;

	if(!$plugin){
		$plugin = plugin_basename(WPMERGE_PATH.'/wp-merge.php' );
	}

	if ($plugin != $plugin_file) {
		return $actions;
	}

	$deactivate_instruct_link = array();

	if(wpmerge_dev_is_dev_db_modifications_applied()){

		if (array_key_exists('deactivate', $actions)){
			unset($actions['deactivate']);
		}

		$checkbox_id =  "checkbox_" . md5($plugin_data['Name']);

		//to disable the checkbox to avoid bulk action
		echo '
		<script type="text/javascript">
		jQuery(document).ready(function($){
			jQuery("#'.$checkbox_id.'").prop( "disabled", true );
		});
		</script>
		';//echoing here may not be best solution. But this filters calls after in main section of body, so it fine.

		$deactivate_instruct_href = network_admin_url( 'admin.php?page=wpmerge_dev_settings&show=deactivate_instruct#deactivate_instruct' );

		$deactivate_instruct_link = array('deactivate_instruct' => '<a href="'.$deactivate_instruct_href.'">Deactivate instruction</a>');
	}

	$support_link = array('support' => '<a href="mailto:help@wpmerge.io?body=WPMerge Plugin v'.WPMERGE_VERSION.'" target="_blank">Support</a>');

	$actions = array_merge($support_link, $deactivate_instruct_link, $actions);

	return $actions;
}

function wpmerge_dev_is_wpmerge_dev_tables_present(){
	//check db and get info
	$is_wpmerge_dev_tables_present = wpmerge_get_option('is_wpmerge_dev_tables_present');
	return (bool)$is_wpmerge_dev_tables_present;//check db and do later
}

function wpmerge_dev_is_dev_db_modifications_applied(){
	$is_dev_db_modifications_applied = wpmerge_get_option('is_dev_db_modifications_applied');
	return (bool)$is_dev_db_modifications_applied;
}

function wpmerge_dev_is_dev_db_modifications_required(){
	$is_dev_db_modifications_required = wpmerge_get_option('is_dev_db_modifications_required');
	return (bool)$is_dev_db_modifications_required;
}

function wpmerge_dev_is_valid_db_modifications_required_is_set(){
	$d =  wpmerge_dev_is_dev_db_modifications_applied() && wpmerge_dev_is_dev_db_modifications_required();
	return $d;
}

function wpmerge_dev_is_triggers_added_to_all_tables(){
	//check db and get info
	$is_triggers_added_to_all_tables = wpmerge_get_option('is_triggers_added_to_all_tables');
	return (bool)$is_triggers_added_to_all_tables;//check db and do later
}

function wpmerge_dev_check_and_on_recorder(){
	if(wpmerge_dev_is_recording_on()){
		$GLOBALS['wpdb']->query("SET @wpmerge_dev_is_record_on = TRUE");
	}
}

function wpmerge_dev_check_and_enable_logging(){
	if(wpmerge_dev_can_record()){
		wpmerge_dev_enable_query_logging();
	}
	else{
		wpmerge_dev_disable_query_logging();
	}
}

function wpmerge_dev_enable_query_logging(){
	$GLOBALS['wpdb']->query("SET @wpmerge_dev_is_logging_on = TRUE");
}

function wpmerge_dev_disable_query_logging(){
	$GLOBALS['wpdb']->query("SET @wpmerge_dev_is_logging_on = FALSE");
}

function wpmerge_dev_disable_auto_increment_change_on_insert(){
	$GLOBALS['wpdb']->query("SET @wpmerge_dev_dont_change_auto_increment_id = TRUE");
}

function wpmerge_dev_copy_wp_active_plugins(){
	$active_plugins = get_option('active_plugins');
	wpmerge_update_option('wp_active_plugins', $active_plugins);
}

function wpmerge_dev_restore_wp_active_plugins(){
	$active_plugins = wpmerge_get_option('wp_active_plugins');
	//wp_cache_delete('active_plugins', 'options' );//this option not working may be because autoload is enable for this 'active_plugins'
	update_option('active_plugins', array());//to clear the cache
	update_option('active_plugins', $active_plugins);//if cache doesn't clear, it will not update the DB, thinking same data already present.
}

function wpmerge_dev_is_changes_applied_in_dev(){//this can be apply_changes_for_dev_in dev or apply_changes_for_prod_in_dev
	return wpmerge_dev_is_changes_applied_for_dev_in_dev() || wpmerge_dev_is_changes_applied_for_prod_in_dev();
}

function wpmerge_dev_is_changes_applied_for_dev_in_dev(){
	return (bool)wpmerge_get_option('is_changes_applied_for_dev_in_dev');
}

function wpmerge_dev_is_changes_applied_for_prod_in_dev(){
	return (bool)wpmerge_get_option('is_changes_applied_for_prod_in_dev');
}

add_filter( 'query', 'wpmerge_dev_query_action_hook', 10 );//this should be  before "add_filter('query', array( 'wpmerge_dev_http_admin_log', 'query_hook' ) );"
add_filter( 'query', array( 'wpmerge_dev_http_admin_log', 'query_hook' ), PHP_INT_MAX );//this should be after "add_filter('query', 'wpmerge_dev_query_action_hook' );"

function wpmerge_dev_query_action_hook($query){
	static $exclude_this_query;
	
	if($exclude_this_query === true){
		return $query;
	}

	$exclude_this_query = true;

	if(wpmerge_dev_rewrite_multi_insert_query::is_probable_multi_insert_relog_pending()){//this if condition is used to avoid DB call in each and every 'query' hook, found_rows() kind of queries(double query in succession) will fail otherwise.
		$rewrite_multi_insert_query_obj = new wpmerge_dev_rewrite_multi_insert_query();
		$rewrite_multi_insert_query_obj->check_and_process();
	}	


	wpmerge_db_mod_on_the_go::check_and_do_db_mod_for_required_tables();
	
	$exclude_this_query = false;
	return $query;
}

function wpmerge_dev_process_ajax_request(){
	if(!isset($_POST['wpmerge_action']) && !isset($_GET['wpmerge_action'])){
		return;
	}
	// if(isset($_POST['wpmerge_action']) && $_POST['wpmerge_action'] === 'http_requests_deploy_get_db_changing_http_requests_ids'){
	// 	$apply_changes_group = isset($_POST['wpmerge_apply_changes_group']) ? $_POST['wpmerge_apply_changes_group'] : '';
	// 	$wpmerge_http_requests_deploy_obj = new wpmerge_http_requests_deploy();
	// 	$result = $wpmerge_http_requests_deploy_obj->get_db_changing_http_requests_ids($apply_changes_group);
	// 	$response = array();
	// 	$response['status'] = ($result !== false) ? 'success' : 'error';
	// 	$response['db_changing_http_requests_ids'] = $result;

	// }
	// else
	if(isset($_POST['wpmerge_action']) && $_POST['wpmerge_action'] === 'connect_to_prod'){

		$response = wpmerge_dev_process_connect_prod();
	}
	elseif(isset($_POST['wpmerge_action']) && $_POST['wpmerge_action'] === 'discard_changes'){
		$discard_changes_confirm = isset($_POST['discard_changes_confirm']) ? $_POST['discard_changes_confirm'] : '';
		$result = wpmerge_dev_discard_changes($discard_changes_confirm);
		$recorded_queries_count = wpmerge_dev_get_recorded_queries_count();
		$response = array();
		$response['status'] = ($result !== false) ? 'success' : 'error';
		$response['recorded_queries_count'] = $recorded_queries_count;
	}
	elseif(isset($_POST['wpmerge_action']) && $_POST['wpmerge_action'] === 'reset_plugin'){
		$reset_plugin_confirm = isset($_POST['reset_plugin_confirm']) ? $_POST['reset_plugin_confirm'] : '';
		$result = wpmerge_reset_plugin($_POST['reset_plugin_confirm']);

		$response = array();
		$response['status'] = ($result !== false) ? 'success' : 'error';
	}
	elseif(isset($_POST['wpmerge_action']) && $_POST['wpmerge_action'] === 'dev_background_works'){

		$wpmerge_dev_db_obj = new wpmerge_dev_db();
		$is_fresh_db_modifications_required = $wpmerge_dev_db_obj->check_fresh_db_modifications_are_required();
		//$wpmerge_dev_db_obj->check_flag_and_do_modifications();

		wpmerge_dev_optimize_recorded_queries::run();

		$response = array();
		$response['status'] = 'success';
		$response['is_fresh_db_modifications_required'] = $is_fresh_db_modifications_required;
	}
	elseif(isset($_POST['wpmerge_action']) && $_POST['wpmerge_action'] === 'get_recording_state'){

		$wpmerge_dev_db_obj = new wpmerge_dev_db();
		$recording_state = $wpmerge_dev_db_obj->get_recording_state();
		$recorded_queries_count = wpmerge_dev_get_recorded_queries_count();

		$response = array();
		$response['status'] = 'success';
		$response['get_recording_state'] = $recording_state;
		$response['recorded_queries_count'] = $recorded_queries_count;
	}
	elseif(isset($_POST['wpmerge_action']) && $_POST['wpmerge_action'] === 'get_recorded_queries_count'){

		$wpmerge_dev_db_obj = new wpmerge_dev_db();
		$recorded_queries_count = wpmerge_dev_get_recorded_queries_count();

		$response = array();
		$response['status'] = 'success';
		$response['recorded_queries_count'] = $recorded_queries_count;
	}
	elseif(isset($_POST['wpmerge_action']) && $_POST['wpmerge_action'] === 'is_changes_applied_in_dev'){
		$response = array();
		$response['status'] = 'success';
		$response['is_changes_applied_in_dev'] = wpmerge_dev_is_changes_applied_in_dev();
	}
	elseif(isset($_POST['wpmerge_action']) && $_POST['wpmerge_action'] === 'test_func'){
		//this for only dev testing purpose
		//$wpmerge_http_requests_deploy_obj = new wpmerge_http_requests_deploy();
		// $new_http_request_id = '';
		// $result = $wpmerge_http_requests_deploy_obj->map_new_and_old_insert_ids($new_http_request_id);
		//$wpmerge_http_requests_deploy_obj->find_unmapped_insert_ids_and_map_it();
		$result = true;
		$response = array();
		$response['status'] = ($result !== false) ? 'success' : 'error';
		$response['apply_changes_group'] = $result;

	}
	elseif(isset($_POST['wpmerge_action']) && $_POST['wpmerge_action'] === 'prepare_bridge'){
		$response = wpmerge_prepare_bridge();
	}
	elseif(isset($_POST['wpmerge_action']) && $_POST['wpmerge_action'] === 'delete_bridge'){
		wpmerge_delete_bridge();
		$response = array();
		$response['status'] = 'success';
	} elseif(isset($_GET['wpmerge_action']) && $_GET['wpmerge_action'] === 'wpmerge_get_root_files'){
		wpmerge_set_error_reporting();
		$wpmerge_inc_exc_contents = new wpmerge_inc_exc_contents();
		$wpmerge_inc_exc_contents->get_root_files();
	}
	elseif(isset($_GET['wpmerge_action']) && $_GET['wpmerge_action'] === 'wpmerge_get_files_by_key'){
		wpmerge_set_error_reporting();
		$wpmerge_inc_exc_contents = new wpmerge_inc_exc_contents();
		$request_key = isset($_REQUEST['key']) ? $_REQUEST['key'] : '';
		$wpmerge_inc_exc_contents->get_files_by_key($request_key);
	}
	elseif(isset($_POST['wpmerge_action']) && $_POST['wpmerge_action'] === 'wpmerge_exclude_file_list'){
		wpmerge_set_error_reporting();
		$wpmerge_inc_exc_contents = new wpmerge_inc_exc_contents();
		if (!isset($_POST['data'])) {
			$response = array('status' => 'no data found');
			die(json_encode($response));
		}
		else{
			$wpmerge_inc_exc_contents->exclude_file_list($_POST['data']);
		}
	}
	elseif(isset($_POST['wpmerge_action']) && $_POST['wpmerge_action'] === 'wpmerge_include_file_list'){
		wpmerge_set_error_reporting();
		$wpmerge_inc_exc_contents = new wpmerge_inc_exc_contents();
		if (!isset($_POST['data'])) {
			$response = array('status' => 'no data found');
			die(json_encode($response));
		}
		else{
			$wpmerge_inc_exc_contents->include_file_list($_POST['data']);
		}
	}
	elseif(isset($_POST['wpmerge_action']) && $_POST['wpmerge_action'] === 'wpmerge_save_filter_contents'){
		wpmerge_set_error_reporting();
		$wpmerge_inc_exc_contents = new wpmerge_inc_exc_contents();
		if (!isset($_POST['data'])) {
			$response = array('status' => 'no data found');
			die(json_encode($response));
		}
		else{
			$wpmerge_inc_exc_contents->save_settings($_POST['data']);
		}
	}
	elseif(isset($_GET['wpmerge_action']) && $_GET['wpmerge_action'] === 'wpmerge_get_tables_with_inc_exc'){
		wpmerge_set_error_reporting();
		$wpmerge_inc_exc_contents = new wpmerge_inc_exc_contents();
		$wpmerge_inc_exc_contents->get_tables();
	}
	elseif(isset($_POST['wpmerge_action']) && $_POST['wpmerge_action'] === 'wpmerge_include_table_structure_only'){
		wpmerge_set_error_reporting();
		$wpmerge_inc_exc_contents = new wpmerge_inc_exc_contents();
		if (!isset($_POST['data'])) {
			$response = array('status' => 'no data found');
			die(json_encode($response));
		}
		else{
			$wpmerge_inc_exc_contents->include_table_structure_only($_POST['data']);
		}
	}
	elseif(isset($_POST['wpmerge_action']) && $_POST['wpmerge_action'] === 'wpmerge_include_table_list'){
		wpmerge_set_error_reporting();
		$wpmerge_inc_exc_contents = new wpmerge_inc_exc_contents();
		if (!isset($_POST['data'])) {
			$response = array('status' => 'no data found');
			die(json_encode($response));
		}
		else{
			$wpmerge_inc_exc_contents->include_table_list($_POST['data']);
		}
	}
	elseif(isset($_POST['wpmerge_action']) && $_POST['wpmerge_action'] === 'check_old_export_dev_db_delta_2_prod'){
		$response = array();
		$response['status'] = 'success';

		$is_export_dev_db_delta_2_prod_already_done = wpmerge_get_option('is_export_dev_db_delta_2_prod_already_done');
		$prod_delta_import_is_atleast_one_query_is_successful = wpmerge_get_option('prod_delta_import_is_atleast_one_query_is_successful');//priority to this

		if($prod_delta_import_is_atleast_one_query_is_successful){
			$response['prod_delta_import_is_atleast_one_query_is_successful'] = 1;
		}
		elseif($is_export_dev_db_delta_2_prod_already_done){
			$response['is_export_dev_db_delta_2_prod_already_done'] = 1;
		}
	}
	elseif(isset($_POST['wpmerge_action']) && $_POST['wpmerge_action'] === 'save_help_toggles_state'){
		if(!empty($_POST['help_toggles_state'])){
			wpmerge_update_help_toggle_state($_POST['help_toggles_state']);
		}
		$response = array();
		$response['status'] = 'success';
	}
	elseif(isset($_POST['wpmerge_action']) && $_POST['wpmerge_action'] === 'save_queries_selection_state'){
		if(!empty($_POST['queries_selection_state_json'])){
			$queries_selection_state = json_decode(stripslashes($_POST['queries_selection_state_json']), true);
			$query_selector_obj = new wpmerge_dev_query_selector();
			$query_selector_obj->select_and_unselect_queries($queries_selection_state);
			$total_selected_queries = $query_selector_obj->get_total_selected_queries();
			// $browse_options = array();
			// $browse_options['pagination']['current_page'] = $_GET['paged'];
			// $query_browser_all_data = $query_selector_obj->get_all_page_data($browse_options);
		}
		$response = array();
		$response['status'] = 'success';
		$response['total_selected_queries'] = $total_selected_queries;
	}
	elseif(isset($_POST['wpmerge_action']) && $_POST['wpmerge_action'] === 'service_login'){
		$response = array();

		$creds = array();
		$creds['email'] = isset($_POST['email']) ? $_POST['email'] : '';
		$creds['password'] = isset($_POST['password']) ? $_POST['password'] : '';

		try{
			$result = wpmerge_service_auth::login($creds);
			$response['status'] = $result ? 'success' : 'error';
			$response['error_msg'] = '';
		}
		catch(wpmerge_exception $e){
			$error = $e->getError();
			$error_msg = $e->getErrorMsg();
	
			$response = array();
			$response['status'] = 'error';
			$response['error_msg'] = $error_msg;
		}
		
	}
	elseif(isset($_POST['wpmerge_action']) && $_POST['wpmerge_action'] === 'purge_cache_for_dev_from_dev'){
		$response = array();
		wpmerge_update_option( 'dev_wp_purge_cache', '' );//reset first
		$result = wpmerge_purge_wp_cache();
		$response['status'] = $result ? 'success' : 'error';
		$response['error_msg'] = '';
	}
	elseif(isset($_POST['wpmerge_action']) && $_POST['wpmerge_action'] === 'purge_cache_for_prod_from_dev'){
		//background task
		try{
			$prod_site_url = wpmerge_dev_get_prod_site_url();
			$prod_request_url = $prod_site_url.'wp-load.php';
			
			if(empty($prod_site_url)){
                throw new wpmerge_exception('invalid_prod_site_url');
			}
			
            $body = array(
                'action' => 'wpmerge_prod_process_dev_request',
                'wpmerge_action' => 'wpmerge_prod_purge_wp_cache',
                'prod_api_key' => wpmerge_dev_get_prod_api_key(),
                'dev_plugin_version' => WPMERGE_VERSION
            );
            $http_args = array(
                'method' => "POST",
                'timeout' => 30,//30 is enough as of now, most clear cache will happen via cron. As it is background call, if takes long time, other ajax calls may delay
                'body' => $body
            );
            $response = wpmerge_do_call($prod_request_url, $http_args);
            $response_data = wpmerge_get_response_from_json($response);

			if( !isset($response_data['status']) || !$response_data['status'] ){
				throw new wpmerge_exception('invalid_response');
			}

			if( $response_data['status'] === 'success' ){
				$result = true;
			}
			else{
				$result = false;
			}
		}
		catch(wpmerge_exception $e){
			$result = false;
		}
		
		$response = array();
		$response['status'] = $result ? 'success' : 'error';
		$response['error_msg'] = '';
	}

	if(!empty($response)){
		echo wpmerge_prepare_response($response);
		exit();
	}

}

add_action('wp_ajax_wpmerge_dev_process_ajax_request', 'wpmerge_dev_process_ajax_request');

// function wpmerge_upgrader_process_complete( $upgrader_object, $options ) {
// 	//possible ddl changed
// 	//because one or more PTC is upgraded there is chance that DDL statement could have run. so mark db modifications required, so our plugin will take this flag run the db modifications
// 	wpmerge_update_option('is_dev_db_modifications_required', 1);
// }
// add_action( 'upgrader_process_complete', 'wpmerge_upgrader_process_complete', 10, 2 );

function wpmerge_dev_wp_login_message( $message ) {
    if ( isset($_GET['redirect_to']) && strpos($_GET['redirect_to'], 'wpmerge_completed_bridge_action') ){
		return '
		<div>
			<p class="message">Since the database has changed, you have to login again.</p>
		</div>';
    } else {
        return $message;
    }
}
add_filter( 'login_message', 'wpmerge_dev_wp_login_message' );

function wpmerge_dev_process_connect_prod(){
	$post_connect_str = isset($_POST['connect_str']) ? $_POST['connect_str'] : '';
	$connect_str = stripslashes(trim($post_connect_str));

	if(empty($connect_str)){
		$response = array();
		$response['status'] = 'error';
		$response['error_msg'] = 'Invalid connect string.';
		return $response;
	}

	$connect_array = json_decode($connect_str, true);
	if(empty($connect_array) || empty($connect_array['username']) || empty($connect_array['prod_api_key']) || empty($connect_array['admin_url'])){
		$response = array();
		$response['status'] = 'error';
		$response['error_msg'] = 'Invalid connect string.';
		return $response;
	}

	$url = untrailingslashit($connect_array['admin_url']).'/admin-post.php';

	$body = array(
		'action' => 'wpmerge_prod_connect_site',
		'prod_api_key' => $connect_array['prod_api_key'],
		'dev_plugin_version' => WPMERGE_VERSION
	);
	$http_args = array(
		'method' => "POST",
		'timeout' => 10,
		'body' => $body
	);

	try{
		$response = wpmerge_do_call($url, $http_args);
		$response_data = wpmerge_get_response_from_json($response);
	}
	catch(wpmerge_exception $e){
		$error = $e->getError();
		$error_msg = $e->getErrorMsg();

		$response = array();
		$response['status'] = 'error';
		$response['error_msg'] = $error_msg;
		return $response;
	}

	if(empty($response_data)){
		$response = array();
		$response['status'] = 'error';
		$response['error_msg'] = 'Invalid response.';
		return $response;
	}

	if($response_data['status'] === 'success'){
		if(!empty($response_data['site_url'])){
			$connect_array['site_url'] = $response_data['site_url'];
		}
		wpmerge_update_option('prod_connect', $connect_array);
		$response = array();
		$response['status'] = 'success';
		$response['prod_site_url'] = wpmerge_dev_get_prod_site_url();		
		return $response;
	}
	elseif($response_data['status'] === 'error'){
		$response = array();
		$response['status'] = 'error';
		$response['error_msg'] = $response_data['error_msg'];
		return $response;
	}
}

function wpmerge_dev_discard_changes($confirm){
	if($confirm !== 'confirm'){
		return false;
	}
	$wpmerge_dev_db_obj = new wpmerge_dev_db();
	$wpmerge_dev_db_obj->delete_all_recordings(true);

	$rewrite_multi_insert_query_obj = new wpmerge_dev_rewrite_multi_insert_query();
	$rewrite_multi_insert_query_obj->delete_all_queries();

	wpmerge_dev_set_min_mtime();
	wpmerge_update_option('is_fresh_or_no_changes_recorded', 1);
	wpmerge_update_option('is_changes_applied_for_dev_in_dev', 0);
	wpmerge_update_option('is_changes_applied_for_prod_in_dev', 0);
	wpmerge_update_option('is_export_dev_db_delta_2_prod_already_done', 0);
	wpmerge_update_option('prod_delta_import_is_atleast_one_query_is_successful', 0);
	return true;
}

function wpmerge_reset_plugin($confirm){
	//reset this plugin - use with caution, it tries to reset the plugin as if it freshly installed, A fresh DB clone is necessary.
	if($confirm !== 'confirm'){
		return false;
	}

	$wpmerge_dev_db_obj = new wpmerge_dev_db();
	$wpmerge_dev_db_obj->reset_this_plugin(true);

	$wpmerge_common_db_obj = new wpmerge_common_db();
	$wpmerge_common_db_obj->on_plugin_activation();

	return true;
}

function wpmerge_dev_get_prod_api_key(){
	$prod_connect = wpmerge_get_option('prod_connect');
	if(empty($prod_connect) ||
	!is_array($prod_connect) ||
	empty($prod_connect['username']) ||
	empty($prod_connect['admin_url'])  ||
	empty($prod_connect['prod_api_key']) ){
		return false;
	}
	return $prod_connect['prod_api_key'];
}

function wpmerge_dev_get_prod_admin_url(){
	$prod_connect = wpmerge_get_option('prod_connect');
	if(empty($prod_connect) ||
	!is_array($prod_connect) ||
	empty($prod_connect['username']) ||
	empty($prod_connect['admin_url'])  ||
	empty($prod_connect['prod_api_key']) ){
		return false;
	}
	return trailingslashit($prod_connect['admin_url']);
}

function wpmerge_dev_get_prod_site_url(){
	$prod_connect = wpmerge_get_option('prod_connect');
	if(empty($prod_connect) ||
	!is_array($prod_connect) ||
	empty($prod_connect['username']) ||
	empty($prod_connect['admin_url'])  ||
	empty($prod_connect['prod_api_key']) ){
		return false;
	}

	//$prod_connect['site_url'] from next version of v1.0.7
	if(!empty($prod_connect['site_url'])){
		return trailingslashit($prod_connect['site_url']);
	}

	//old method
	$prod_admin_url = wpmerge_dev_get_prod_admin_url();
	if($prod_admin_url === false){
		return false;
	}
	$prod_site_url =  wpmerge_remove_suffix('/wp-admin/', $prod_admin_url);
	return trailingslashit($prod_site_url);
}

function wpmerge_dev_set_min_mtime(){
	wpmerge_update_option('dev_min_mtime', time());
	return true;
}

function wpmerge_dev_set_min_mtime_if_not_set(){
	if(!wpmerge_is_exists_option('dev_min_mtime')){
		wpmerge_dev_set_min_mtime();
	}
	return true;
}

function wpmerge_dev_get_recorded_queries_count(){
	//return 0;//tmp testing
	$table_log_queries = $GLOBALS['wpdb']->base_prefix .'wpmerge_log_queries';
	$__start = microtime(1);
	$total_rows = $GLOBALS['wpdb']->get_var("SELECT COUNT(id) FROM `".$table_log_queries."` WHERE `is_record_on` = '1' AND `type` = 'query'");
	//var_dump(round( (microtime(1) - $__start), 2));
	return $total_rows;
}

function wpmerge_download_changed_files_list(){
	wpmerge_set_error_reporting();

	if (empty($_GET) || empty($_GET['page']) || empty($_GET['download_changed_file']) || $_GET['page'] != 'wpmerge_dev_options' || $_GET['download_changed_file'] != 1) {
		return ;
	}

	$wpmerge_file_path = wpmerge_get_option('changed_zip_file');

	if (empty($wpmerge_file_path)) {
		return ;
	}

	include_once ( WPMERGE_PATH . '/templates/download_changed_file.php' );
}

function wpmerge_dev_get_db_modification_background_error(){
	//following comment as wpmerge_dev_is_dev_db_modifications_applied will be set on successful db modification
	// $is_dev_db_modifications_applied = wpmerge_dev_is_dev_db_modifications_applied();//gets from flag
	// if(!$is_dev_db_modifications_applied){
	// 	return false;
	// }

	if(wpmerge_get_option('db_modification_process_state') !== 'error'){
		return false;
	}

	$error_data = wpmerge_get_option('db_modification_background_error');
	if(!is_array($error_data)  || empty($error_data['message'])){
		return false;
	}

	if((time() - $error_data['time']) > 86400){
		return false;
	}

	return $error_data;
}

function wpmerge_dev_check_service_auth_if_required_redirect(){
	$check_pages = array('wpmerge_dev_options');

	if( isset($_GET['page']) && in_array($_GET['page'], $check_pages) ){	
		if(!wpmerge_service_auth::is_valid()){

			$redirect_url = network_admin_url( 'admin.php?page=wpmerge_dev_service_login' );

			wpmerge_redirect_php_js($redirect_url);
		}
	}
}

function wpmerge_service_auth_check_cron(){
	wpmerge_service_auth::check_validity();
}

function wpmerge_dev_add_cron(){
	if ( !wp_next_scheduled( 'wpmerge_service_auth_check_cron' ) ) {
		wp_schedule_event( time() , 'twicedaily', 'wpmerge_service_auth_check_cron' );
	}
}

function wpmerge_dev_remove_cron(){
	wp_clear_scheduled_hook('wpmerge_service_auth_check_cron');
}

function wpmerge_dev_is_dev_tables_exists(){
	$wpmerge_dev_tables = ['wpmerge_options', 'wpmerge_log_queries', 'wpmerge_unique_ids', 'wpmerge_process_files', 'wpmerge_inc_exc_contents', 'wpmerge_relog'];
	foreach($wpmerge_dev_tables as $_table){
		$dev_table =  $GLOBALS['wpdb']->base_prefix.$_table;
		$is_table_exist = wpmerge_is_table_exist($dev_table);
		if(!$is_table_exist){
			return false;
		}
	}
	return true;
}

function wpmerge_change_any_int_to_bigint_in_ddl_query($query){
	$pattern = '/\b(?:tinyint|smallint|mediumint|int)\b(?:\s*\([0-9]+\))?/i';
	$query = preg_replace($pattern, 'BIGINT', $query);
	return $query;
}