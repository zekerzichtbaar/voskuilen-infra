<?php 
/**
 * WPMerge
 * Copyright (c) 2018 Revmakx LLC
 * revmakx.com
 */

if(!defined('ABSPATH')){ exit; }

$wpmerge_page = 'dev_options';
include(WPMERGE_PATH . '/templates/dev_header.php'); ?>   


<?php 
if(isset($_GET['test']) && $_GET['test'] == '1'){
   
    // $GLOBALS['wpdb']->query("INSERT INTO wp_dev_test_t2_test (`name`) VALUES('eyruihfh001'), ('eyruihfh002')");
    // var_dump($GLOBALS['wpdb']->last_error);

    // $new_obj = new wpmerge_dev_rewrite_multi_insert_query();
    // $new_obj->check_and_process();

    //$__common_db_obj = new wpmerge_common_db();
    //$__common_db_obj->get_query_stmt_type('INSERT INTO wp_dev_test_t2_test (`name`) VALUES');



}


function wpmerge_test_page(){

}
//$recorded_queries_count = wpmerge_dev_get_recorded_queries_count();
?>
    <div class="main-cols-cont cf">
        <div class="main-cols">

            <div class="m-box">
                <h2 class="hd">
                    Dev DB changes - <?php echo '<a href="admin.php?page=wpmerge_dev_query_browser"><span id="wpmerge_recorded_queries_count_str_heading_cont"></span></a>'; ?>
                    <span class="dashicons dashicons-info" title="Queries count might reduce when the optimization process runs in the background." style="font-size:15px; color: #23282d;margin-top: 2px; position: absolute;"></span>
                </h2>
                
                <div class="g-bg pad cf">
                <span id="wpmerge_dev_discard_changes_btn_result"></span>
                    <div class="col col70" style="float:left;">
                        <div style="float: left;" id="wpmerge_dev_record_switch_in_page_cont">
                            <div class="loader"></div>
                        <!-- <input type="checkbox" id="wpmerge_dev_record_switch_in_page" name="wpmerge_dev_record_radio" value="1" ><label for="wpmerge_dev_record_switch_in_page">On </label> -->
                        </div>
                        <div id="wpmerge_dev_recording_state_in_page"  style="float: left; margin: -8px 0 0 0; width: 240px;">
                        <!-- <div class="record-status on">
                            <strong>Changes are being recorded.</strong>
                        </div>
                        <div class="record-status off"><strong>Changes are not being recorded.</strong></div>
                        <div style="font-size: 12px;">All changes to the Dev DB are being recorded.</div> -->
                        </div>
                    </div>
                    <div class="col col30">
                        <a class="button" style="float: right; margin: 0;" id="wpmerge_dev_discard_changes_btn">Discard records</a>
                    </div>
                </div>
                <div class="cf">

                    <div class="pad">
                    The production DB will be freshly cloned and the <span id="wpmerge_recorded_queries_count_str_desc_cont">queries</span> recorded in the dev environment will be applied. The DB will be modified again to continue recording your changes.
                    </div>
                    <div class="ft pad">
                        <input type="button" class="button button-primary" id="wpmerge_apply_changes_for_dev_in_dev_btn" value="Clone Prod DB &amp; Apply Dev Changes">
                    </div>

                    <!-- <div class="col col50 pad" style="border-right: 1px solid #eee;">You can apply the recorded changes, since cloning, to the development DB.
                        <br>
                        <br>
                        <input type="button" style="width:100%;" class="button button-primary" id="wpmerge_apply_changes_for_dev_in_dev_btn" value="Apply changes in Dev">
                    </div>
                    <div class="col col50 pad">Once development is done, apply recorded changes, readying it for pushing to production.
                        <br>
                        <br>
                        <input type="button" style="width:100%;" class="button button-primary" id="wpmerge_apply_changes_for_prod_btn"  value="Apply changes for Prod">
                    </div> -->
                </div>
            </div>


            <!--<div class="m-box">
                <h2 class="hd">
                    DB changes for Prod
                </h2>
                <div class="pad">
                    Once development is done, you should apply the changes, readying it for pushing to production. This will also serve as a
                    test deployment.
                    <br>
                    <br>IDs of items that were altered (by the plugin) will be reverted to it's original state, like how it will
                    be on the live site.
                </div>
                <div class="ft pad">
                    <input type="submit" class="button button-primary" value="Apply changes for Prod">
                </div>
            </div>-->

            <div class="m-box">
                <h2 class="hd">
                    Test merging in Dev
                </h2>

                <div class="pad">
                Once development is done, a fresh clone of the Prod site is taken and the recorded changes are applied. This will also revert certain DB modifications done to enable recording of changes. We can now test the Development site knowing that it is an exact copy of what will be pushed to Production.
                </div>
                <div class="ft pad">
                    <input type="button" class="button button-primary" id="wpmerge_apply_changes_for_prod_in_dev_btn"  value="Clone Prod DB &amp; Test Merge">
                </div>

            </div>

            <div class="m-box">
                <h2 class="hd">
                    Apply Dev Changes to Prod
                </h2>
                <div class="pad">
                Apply all recorded Dev DB Changes to Prod.
                </div>
                <div class="ft pad">
                    <span class="warn" style="margin-left: -5px;">
                        <!-- <span class="dashicons dashicons-warning"></span>Backup your prod site before applying changes --></span>
                    <input type="button" class="button button-primary" id="wpmerge_initiate_export_dev_db_delta_2_prod_btn" value="Apply Dev Changes to Prod">
                </div>
            </div>


            <div class="m-box">
                <h2 class="hd">
                    Download new &amp; modified files from Dev
                </h2>
                <div class="pad">
                This will download the new and modified files as a zip. Unzip and upload the folder to the production site's web root directory. The files will be extracted to their respective directories.
                </div>
                <div class="ft pad">
                    <input type="button" class="button button-primary" id="wpmerge_exim_initiate_export_changed_files_in_dev_btn" value="Download files as a zip">
                </div>
            </div>
            
            <?php if(wpmerge_debug::is_debug_enabled()){ ?>
            <div class="m-box">
                <h2 class="hd">
                    Decode encoded queries | Remove decoded queries (Debug only)
                </h2>
                <div class="pad">
                    Decode encoded queries
                    <br><br>
                    <label><input type="radio" name="decode_queries_which" value="all" checked="checked">All</label>
                    <label><input type="radio" name="decode_queries_which" value="undecoded">Undecoded</label>
                    <br><br>
                    Range Min: <input type="text" name="decode_queries_range_min" value="" size="15" style="float:unset;width:120px;">
                    Max: <input type="text" name="decode_queries_range_max" value="" size="15" style="float:unset;width:120px;">
                    <br><br>
                    <input type="button" class="button button-primary toggle_loading" id="wbdbsync_initiate_decode_encoded_log_queries" value="Decode encoded queries">

                </div>
                <div class="ft pad"></div>
                <div class="pad">
                    Remove decoded queries
                    <br><br>
                    <input type="button" class="button button-primary toggle_loading" id="wbdbsync_remove_decoded_log_queries" value="Remove decoded queries">
                </div>
            </div>
            <?php } ?>

            <?php if(wpmerge_debug::is_debug_enabled()){ ?>
            <div class="m-box">
                <h2 class="hd">
                    State (Debug only)
                </h2>
                <div class="pad">
                    DB Mod applied: <?php echo wpmerge_dev_is_dev_db_modifications_applied();?><br>
                    DB Mod required: <?php echo wpmerge_dev_is_dev_db_modifications_required();?><br>
                    Change applied: <?php echo wpmerge_dev_is_changes_applied_in_dev();?><br>
                    Change for prod applied: <?php echo wpmerge_dev_is_changes_applied_for_prod_in_dev();?><br>
                    Change for dev applied: <?php echo wpmerge_dev_is_changes_applied_for_dev_in_dev();?><br>

                </div>
                <div class="ft pad">
                    <!-- <input type="button" class="button button-primary toggle_loading" id="wbdbsync_initiate_prod_db_import_btn" value="Clone Prod DB"> -->
                </div>
            </div>
            <?php } ?>

            <?php 
            $toggle_text = '+ Show advanced options';
            $hide_style = 'display:none;';
            if(isset($_GET['show_adv']) && $_GET['show_adv'] == '1'){
                $toggle_text = '- Hide advanced options';
                $hide_style = '';
            } 
            ?>
            <p><a id="wpmerge_toggle_adv_opts" style="cursor: pointer;"><?php echo $toggle_text; ?></a></p>
            <div id="wpmerge_adv_opts_cont" style="<?php echo $hide_style; ?>">

                <div class="m-box">
                    <h2 class="hd">
                        Clone Prod DB
                    </h2>
                    <div class="pad">
                        Before making changes, the prod DB has to be cloned onto the dev site.
                    </div>
                    <div class="ft pad">
                        <input type="button" class="button button-primary toggle_loading" id="wbdbsync_initiate_prod_db_import_btn" value="Clone Prod DB">
                    </div>
                </div>

                <div class="m-box">
                    <h2 class="hd">
                        Modify Dev DB for recording
                    </h2>
                    <div class="pad">
                        We need to modify a few aspects of the dev DB to record changes. We do this automatically before applying changes. You may need to do it manually in rare instances.
                    </div>
                    <div class="ft pad">
                    <span id="wpmerge_do_db_mod_btn_result"></span>
                        <input type="button" class="button button-primary" id="wpmerge_do_db_mod_btn" value="Modify Dev DB">
                    </div>
                </div>

                <?php if(wpmerge_debug::is_debug_enabled()){ ?>
                <div class="m-box">
                    <h2 class="hd">
                        Cache plugins purge cache (Debug only)
                    </h2>
                    <div class="pad">
                        Tries to clear 6 famous cache plugin's cache.
                    </div>
                    <div class="ft pad">
                        <input type="button" class="button button-primary" id="wpmerge_wp_purge_cache_btn" value="Purge Cache">
                    </div>
                </div>
                <?php } ?>

                <div class="m-box">
                    <h2 class="hd">
                        Fix DB Serialization.
                    </h2>
                    <div class="pad">
                        If your site have unexpected output after merging then clearing the cache. You can try this option to fix any DB Serialization issue.
                    </div>
                    <div class="ft pad">
                        <input type="button" class="button button-primary toggle_loading" id="wpmerge_initiate_dev_fix_db_serialization" value="Fix Dev DB Serialization">
                        <input type="button" class="button button-primary toggle_loading" id="wpmerge_initiate_prod_fix_db_serialization" value="Fix Prod DB Serialization">
                    </div>
                </div>

            </div>
            <br>
            <br>
            <br>
            <br>
            <br>

            <!--<div class="m-box">
                <h2 class="hd">
                    Merge Production site with Dev site
                </h2>

                <div class="pad">
                    Once development is complete, you can start the merging process.
                    <br>This happends in a 2-step process -
                    <br>
                    <br>
                    <div class="cf">
                        <div class="col col70">
                            <strong>Step 1:</strong>
                            <br>Clone the Prod DB onto the Dev DB. This will log you out of the dashboard. Re-login to proceed.
                        </div>

                        <div class="col col30">
                            <input type="submit" style="float: right;" class="button button-primary" value="Clone Prod DB">
                        </div>
                    </div>
                    <br>
                    <div class="cf">
                        <div class="col col70">
                            <strong>Step 2:</strong>
                            <br>Apply all changes recorded in the Dev DB.
                        </div>
                        <div class="col col30">
                            <input type="submit" style="float: right;" class="button button-primary" value="Apply changes">
                        </div>
                    </div>
                </div>
            </div>-->
        </div>
        <div class="main-cols">
            <div id="wpmerge_dev_exim_progress">
                <div class="process-steps-progress" style="padding: 10px 0 0 30px;">
                    <!-- <h3>Clone Prod DB</h3>
                    <p class="done">Listing DB tables... </p>
                    <p class="done">Backing up DB... </p>
                    <p class="processing">Downloading DB... </p>
                    <p class="waiting">Pre-run queries... </p>
                    <p class="waiting">Running queries... </p>
                    <p class="waiting">Post-run queries... </p>
                    <p class="result success">Prod DB cloned successfully!</p>
                    <p class="result error">Oops... Something went berserk. Please try again.</p> -->
                    
                </div>
            </div>
            <div id="wpmerge_dev_exim_progress2">
			</div>
        </div>

        <?php /* <!-- <div class="main-cols notes collapsed" id="wpmerge_help_collapsed" style="<?php wpmerge_print_help_toggle_state('dev_main_help_show', '1', 'display:none;'); ?>">
            <div class="m-box">
                <div class="pad wpmerge_toggle_help">See How-tos +</div>
            </div>
        </div>

        <div class="main-cols notes" id="wpmerge_help_cont" style="<?php wpmerge_print_help_toggle_state('dev_main_help_show', '0', 'display:none;'); ?>">
            <div class="m-box">
                <div class="pad">
                    <span class="wpmerge_toggle_help">Hide -</span>
                    <h4 style="margin-top:0">Before starting development</h4>
                    <ul>
                        <li>
                            Once you connect the prod and dev sites, you can clone the Prod DB (Show advanced options &rarr; Clone Prod DB) to make sure
                            you have the latest copy of the Prod DB before starting dev.
                        </li>
                        <li>
                            Make sure the recording switch is turned ON (green). If, for some reason, you do not want some changes to be recorded, you
                            can switch it OFF and turn it back ON when done, without fail!
                        </li>
                    </ul>
                    <h4>During development</h4>
                    <ul>
                        <li>
                            At any stage during development, if you want to continue development on a fresh copy of production DB, you can do so by clicking
                            on the
                            <strong>[Clone Prod DB and Apply changes]</strong> button. This will clone a current copy of the Prod
                            DB onto the Dev DB and apply all recorded changes so far.
                        </li>
                    </ul>
                    <h4>After completing development</h4>
                    <ul>
                        <li>
                            You can test the merging process by clicking on the <strong>[Test Merge]</strong> button (Show advanced options &rarr; Test Merge). This will merge the current
                            Prod DB with the changes made and all DB modifications made to record changes will be reverted.
                            In other words, the Dev site will be exactly how the Production site will be after pushing it
                            live.
                        </li>
                        <li>
                            After testing the merge, you can upload new and modified files to your site via FTP. You can get the files as a ZIP by clicking
                            on the <strong>[Download files as a ZIP]</strong> button. Make sure you extract the files and upload them to
                            the WordPress root directory. Files will be dropped in the appropriate folders.
                        </li>
                        <li>
                            Once you verify that all changes have been applied and everything looks good, you can push the Dev DB to Prod by clicking
                            on the <strong>[Push Dev to Prod]</strong> button.
                        </li>
                        <li>
                                After confirming that everything looks good and pushing the site live, you can discard the recorded changes, readying it for the next development cycle.
                            </li>
                    </ul>
                </div>
            </div>
        </div> --> */ ?>


    </div>

<script type="text/javascript">
jQuery(document).ready(function($){
    wpmerge_dev_get_recording_state();
});
</script>
<div style="clear:both;"></div>
<!-- <br>
	<button type="submit" onclick="location.assign(wpmerge_dev_ajax.admin_url + '?page=wpmerge_dev_options&test=1')">Run this page Test </button>
	<br><br>
	<button id="wbdbsync_run_test_func">Run test functionality</button>
	<br>

<div id="wpmerge_debug_exim_progress" style="clear:both;"></div> -->

<script type="text/javascript">
var wpmerge_on_page_load_do_actions = {};//have to take data elsewhere so it works on every page load or every this plugin's page load
<?php if( wpmerge_get_option( 'dev_wp_purge_cache') === 'yes' ){ echo 'wpmerge_on_page_load_do_actions[\'dev_wp_purge_cache\']=true;'."\n"; } ?>

var wpmerge_on_page_load = false;
<?php if(isset($_GET['wpmerge_do']) && ($_GET['wpmerge_do'] == 'db_mod' || $_GET['wpmerge_do'] == 'show_prod_clone_dialog')){ echo 'wpmerge_on_page_load=\''.$_GET['wpmerge_do'].'\';'; } ?>

jQuery(document).ready(function($) {
    if(wpmerge_on_page_load == 'db_mod'){
        setTimeout(() => {
            jQuery('#wpmerge_do_db_mod_btn').trigger('click');
        }, 500);
        
    }
    else if(wpmerge_on_page_load == 'show_prod_clone_dialog'){
        setTimeout(() => {
            var is_confirm = confirm('We recommend a fresh clone of the production DB, before starting development. Do you want to clone prod DB now?');
            if(is_confirm){
                wpmerge_prepare_bridge('do_prod_db_import_and_db_mod_then_record_on');
            }     
        }, 500);   
    }

    if( wpmerge_on_page_load_do_actions.hasOwnProperty('dev_wp_purge_cache') && wpmerge_on_page_load_do_actions.dev_wp_purge_cache === true ){
        setTimeout( function(){
            wpmerge_this_element = '';
            wpmerge_dev_wp_purge_cache_in_dev();
            wpmerge_dev_show_clear_cache_notification('dev');
        }, 300);
    }
});
</script>
<?php include(WPMERGE_PATH . '/templates/dev_footer.php'); ?>