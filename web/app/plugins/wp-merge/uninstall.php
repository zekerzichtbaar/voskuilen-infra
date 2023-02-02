<?php
/**
 * WPMerge
 * Copyright (c) 2018 Revmakx LLC
 * revmakx.com
 */

// if uninstall.php is not called by WordPress, die
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

function wpmerge_uninstall(){

    $ENV = wpmerge_get_option('ENV');

    if($ENV !== 'PROD'){
        return;
    }

    //folowing codes are copy from includes/dev_db.php

    $table_log_queries = $GLOBALS['wpdb']->base_prefix .'wpmerge_log_queries';
    $drop_query = "DROP TABLE IF EXISTS `".$table_log_queries."`";
    $result = $GLOBALS['wpdb']->query($drop_query);

    // $table_unique_ids = $GLOBALS['wpdb']->base_prefix .'wpmerge_unique_ids';
    // $drop_query = "DROP TABLE IF EXISTS `".$table_unique_ids."`";
    // $result = $GLOBALS['wpdb']->query($drop_query);

    $table_options = $GLOBALS['wpdb']->base_prefix .'wpmerge_options';
    $drop_query = "DROP TABLE IF EXISTS `".$table_options."`";
    $result = $GLOBALS['wpdb']->query($drop_query);

    // $table_options = $GLOBALS['wpdb']->base_prefix .'wpmerge_process_files';
    // $drop_query = "DROP TABLE IF EXISTS `".$table_options."`";
    // $result = $GLOBALS['wpdb']->query($drop_query);

    // $table_options = $GLOBALS['wpdb']->base_prefix .'wpmerge_inc_exc_contents';
    // $drop_query = "DROP TABLE IF EXISTS `".$table_options."`";
    // $result = $GLOBALS['wpdb']->query($drop_query);

    delete_option('wpmerge_first_activation_redirect');
    
}

if(!function_exists('wpmerge_get_option')){
    function wpmerge_get_option($option_name){
        $query = $GLOBALS['wpdb']->prepare('select option_value from '.$GLOBALS['wpdb']->base_prefix .'wpmerge_options where option_name = %s', $option_name);
        $result = $GLOBALS['wpdb']->get_var($query);
        return maybe_unserialize($result);
    }
}

wpmerge_uninstall();