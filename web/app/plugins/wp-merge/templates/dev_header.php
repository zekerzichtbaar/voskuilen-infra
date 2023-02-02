<?php
/**
 * WPMerge
 * Copyright (c) 2018 Revmakx LLC
 * revmakx.com
 */

if(!defined('ABSPATH')){ exit; }

?>

<div class="wrap">
    <h1 class="wp-heading-inline">WPMerge.io</h1>
    <div style="float: right;">
    <a href="https://docs.wpmerge.io/article/your-development-workflow-with-wpmerge?utm_source=plugin-help" target="_blank" style="margin-right:10px;"><span class="dashicons dashicons-editor-help" style="text-decoration: none;"></span> How to</a>

    <a href="mailto:help@wpmerge.io?body=WPMerge Plugin v<?php echo WPMERGE_VERSION; ?>" target="_blank"><span class="dashicons dashicons-sos" style="text-decoration: none;"></span> Support</a></div>

    <!-- <script src="<?php echo WPMERGE_PLUGIN_URL; ?>js/dev_exim.js"  type="text/javascript"></script> -->
<br>
<br>
<div class="wpmerge_b" style="background: #f1f1f1; color: #444; font-family: 'Helvetica Neue',sans-serif; font-size: 13px; line-height: 1.4em;">

<?php
if( isset($_GET['wpmerge_completed_bridge_action'])  && !empty($_GET['wpmerge_completed_bridge_action']) && $wpmerge_page == 'dev_options' ){ 
    $success_msg = 'Action is successfully executed.';
    switch($_GET['wpmerge_completed_bridge_action']){
        case 'prod_db_import':
        $success_msg = 'Production DB cloned successfully.';
        break;

        case 'apply_changes_for_dev_in_dev':
        $success_msg = 'Production DB cloned and changes applied successfully.';
        break;

        case 'apply_changes_for_prod_in_dev':
            $success_msg = 'Production DB cloned and merge tested successfully.';
        break;

        case 'do_prod_db_import_and_db_mod_then_record_on':
        $success_msg = 'Production DB cloned and DB modified successully.';
        break;

        case 'do_prod_db_import_and_db_mod':
        $success_msg = 'Production DB cloned and DB modified successully.';
        break;
    }
    
    ?>
<div class="notice notice-success">
    <p><?php echo $success_msg; ?></p>
</div>
<?php } ?>

<?php if( !in_array($wpmerge_page, array('dev_settings', 'dev_service_login')) ){ ?> 

<?php
$prod_site_url = wpmerge_dev_get_prod_site_url();
if(!empty($prod_site_url)){ ?>
<div class="notice notice-info">
    <p>This is the dev site for the production site at <strong><a href="<?php echo $prod_site_url; ?>" target="_black"><?php echo $prod_site_url; ?></a></strong>.</p>
</div>
<?php } ?>

<?php } ?>

<div class="error-box">
    <strong>Do not clone this Dev site to Prod site using third party plugins. <a href="https://docs.wpmerge.io/article/do-not-clone-dev-site-to-prod-site-using-third-party-plugins?utm_source=plugin-help" target="_blank">Know more</a>.</strong>
</div>

<?php if( !in_array($wpmerge_page, array('dev_service_login')) ){ ?> 
<?php
$db_mod_error_data = wpmerge_dev_get_db_modification_background_error();
if(!empty($db_mod_error_data)){
?><div class="notice notice-error">
    <p>DB Modification resulted in error. Please fix the issue and retry.<br>
    <strong><?php echo $db_mod_error_data['message']; ?></strong></p>
</div><?php } ?>
<?php } ?>