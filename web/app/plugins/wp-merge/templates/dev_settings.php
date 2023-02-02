<?php 
/**
 * WPMerge
 * Copyright (c) 2018 Revmakx LLC
 * revmakx.com
 */

if(!defined('ABSPATH')){ exit; }

$wpmerge_page = 'dev_settings';
include(WPMERGE_PATH . '/templates/dev_header.php');
$prod_site_url = wpmerge_dev_get_prod_site_url();
$account_info = wpmerge_service_auth::get_account_info();
?>
    <div class="main-cols-cont cf">
        <div class="main-cols">

            <div class="m-box connect dev">
                <h2 class="hd">
                    Connect Dev &amp; Prod WPMerge plugins
                </h2>
                <div class="pad">
                    The WPMerge plugins in the dev site and prod site have to be connected before merging.
                </div>
                <div class="pad" style="border-top: 1px solid #eee;">
                    <span id="wpmerge_dev_connect_prod_btn_result">
                    <?php
                        if(!empty($prod_site_url)){
                        ?><div class="info-box" style="word-break: break-word;">This is the dev site for the production site at
                        <strong><a href="<?php echo $prod_site_url; ?>" target="_black"><?php echo $prod_site_url; ?></a></strong>.
                        </div>
                    <?php } ?>
                    </span>
                    <div class="cf">
                        <input type="text" id="wpmerge_dev_connect_str" value="" />
                        <input type="submit" class="button button-primary" id="wpmerge_dev_connect_prod_btn" value="Connect">
                    </div>
                    <div style="font-size: 11px; padding-top: 5px;">Paste connection key from the Prod site's Admin (WPMerge &rarr; Settings &rarr; Connect)</div>
                </div>
            </div>

            
            <div class="m-box">
                <h2 class="hd">
                    Your WPMerge.io account <a href="<?php echo WPMERGE_SITE_MY_ACCOUNT_URL; ?>" target="_blank" style="float:right;font-weight:normal;font-size:13px;">My Account <span class="dashicons dashicons-external" style="font-size: 12px; margin-left: -4px;"></span></a>
                </h2>
                <div class="pad">
                <?php 
                    if(isset($account_info['status']) && $account_info['status'] == 'valid'){ ?>
                    Connected account: <strong><?php echo !empty($account_info['email']) ? $account_info['email'] : '-'; ?></strong>
                    <?php }
                    else{
                        ?>You have been logged out. Please <a href="admin.php?page=wpmerge_dev_service_login">login again</a>.
                        <?php
                    }
                    ?>
                </div>
            </div>


             <div class="m-box">
                <h2 class="hd">
                Include/Exclude Files &amp; Folders
                </h2>
                <div class="pad">
                    <div>
                       <fieldset>
                        <button class="button button-secondary" id="wpmerge_init_toggle_files" style="width: 100%; outline:none; text-align: left; position:relative;">
                        <span class="dashicons dashicons-portfolio" style="position: relative; top: 3px; font-size: 20px"></span>
                        <span style="display: inline-block; padding: 1px 5px;">Select to Include/Exclude </span>
                        
                            <span class="dashicons dashicons-arrow-down" style="position: absolute; right: 5px;top: 2px;"></span>
                        </button>
                        <div style="display:none;" id="wpmerge_exc_files"></div>
                    </fieldset>
                    </div>

                    <br>
                    <div class="cf">
                    <div class="col col50">
                       Exclude Files of Extensions
                        <?php $user_excluded_extenstions = wpmerge_get_option('user_excluded_extenstions') ?>
                        <input type="text" placeholder=".git,.zip,.pdf" value="<?php echo $user_excluded_extenstions;?>" name="wpmerge_exclude_extensions" id="wpmerge_exclude_extensions">
                     </div>
                     <div class="col col50">
                        Exclude files bigger than (in MB)
                        <br>
                        <?php
                        $wpmerge_inc_exc_contents = new wpmerge_inc_exc_contents();
                        $settings = $wpmerge_inc_exc_contents->get_user_excluded_files_more_than_size() ?>
                        <input type="number" placeholder="50" min="0" value="<?php echo $settings['hr'];?>" name="wpmerge_exclude_by_size" id="wpmerge_exclude_by_size">
                     </div>
                        </div>
                </div>
                <div class="ft pad">
                    <span id="wpmerge_save_filter_contents_result"></span>
                    <input type="button" class="button button-primary" id="wpmerge_save_filter_contents" value="Save Changes">
                </div>
            </div>

            <div class="m-box">
                <h2 class="hd">
                Include/Exclude DB Tables from recording content
                </h2>
                <div class="pad">
                    <div>
                       <fieldset>
                        <button class="button button-secondary" id="wpmerge_init_toggle_tables" style="width: 100%; outline:none; text-align: left; position:relative;">
                        <span class="dashicons dashicons-portfolio" style="position: relative; top: 3px; font-size: 20px"></span>
                        <span style="display: inline-block; padding: 1px 5px;">Select tables to include/exclude its content from recording</span>
                        
                            <span class="dashicons dashicons-arrow-down" style="position: absolute; right: 5px;top: 2px;"></span>
                        </button>
                        <div style="display:none;" id="wpmerge_exc_tables"></div>
                        
                        <div>
                        <br>
                        Important notes:
                        <ul style="margin-left: 20px; list-style: disc;">
                            <li>Once excluded, changes to the content of the tables will not be recorded. But changes to the structure of the table will be.</li>
                            <?php /* <li>DML queries like insert, update, delete will not be recorded for the excluded tables.</li> */ ?>
                            <li>Once you excluded tables' content from recording, <span style="color: #a94442;font-weight: bold;">please do DB Mod to activate the changes.</span></li>
                            <li>If a table has contents of a previously recorded table, it will not be removed.</li>
                            <li>Excluded tables with primary auto increment column (commonly known as ID) should not be referred in any other table.</li>
                        </ul>
                        </div>
                      </fieldset>
                    </div>

                </div>
            </div>

            <a id="deactivate_instruct" style="position: relative;top: -25px; z-index: -100;"><?php if(isset($_GET['show']) && $_GET['show'] == 'deactivate_instruct'){ ?>&nbsp;<?php } /* hack to jump link, to avoid wp admin bar hiding title  */ ?></a>
            <div class="m-box">
                <h2 class="hd" style="color: #a94442;">
                    Deactivate/Delete instructions
                </h2>
                <div class="pad">
                Before you deactivate/delete this plugin, you have to revert the DB modifications done previously. To do this -<br>
                    1) Do a test merge and discard the changes, if you want the dev changes to be applied.<br>
                    2) Or, if you don't want the dev changes to be applied, clone prod db once.
                <?php if(isset($_GET['show']) && $_GET['show'] == 'deactivate_instruct'){ ?>
                <br><div class="error-box">Once you performed the above actions, you should be able to see the deactivate link on your plugins page. <a href="https://docs.wpmerge.io/article/why-the-wpmerge-plugin-deactivation-link-is-hidden?utm_source=plugin-help" target="_blank">Know more</a>.</div>
                <?php } ?>
                </div>
            </div>

            <?php if(wpmerge_debug::is_debug_enabled()){ ?>
            <div class="m-box">
                <h2 class="hd">
                    Reset the plugin(Debug only)
                </h2>
                <div class="pad">
                    This will discard all recorded changes and WPMerge settings. Plugin will go to newly-installed state. This setting not intended for regular use. This will not remove DB modifications (like Triggers)
                </div>
                <div class="ft pad">
                <span id="wpmerge_dev_reset_plugin_btn_result"></span>
                    <input type="button" class="button button-primary" id="wpmerge_dev_reset_plugin_btn" value="Reset this plugin">
                </div>
            </div>
            <?php } ?>

            <br>
            <br>
            <br>
            <br>
            <br>

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
        </div>
        <?php /* <?php if(empty($prod_site_url)){ ?>
        <div class="main-cols notes">
            <div class="m-box">
                <div class="pad">
                    <ul>
                        <li>Connect your production environment to clone the production database and then start recording the changes in Dev.
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <?php } ?> */ ?>

    </div>
<?php include(WPMERGE_PATH . '/templates/dev_footer.php'); ?>

