<?php 
/**
 * WPMerge
 * Copyright (c) 2018 Revmakx LLC
 * revmakx.com
 */

if(!defined('ABSPATH')){ exit; }

$wpmerge_page = 'dev_service_login';
include(WPMERGE_PATH . '/templates/dev_header.php');


if(isset($_GET['redirect_to']) && !empty($_GET['redirect_to'])){
    $redirect_to = $_GET['redirect_to'];
}
else{    
    if(wpmerge_dev_is_dev_tables_exists()){
        $redirect_to = 'wpmerge_dev_options';
    }
    else{
        $redirect_to = 'wpmerge_dev_initial_setup&dev_do_initial_setup=1';
    }    
}
$redirect_url = network_admin_url( 'admin.php?page='.$redirect_to );

?> 
<div class="main-cols">
<div class="m-box">
    <h2 class="hd">
    Login to your WPMerge.io account
    </h2>
    <div class="pad">
        <div id="wpmerge_service_login_btn_result"></div>
        <div style="margin-bottom: 10px;">
            <fieldset>
                <label for="wpmerge_service_email">Email</label> <br>
                <input type="text" id="wpmerge_service_email">
            </fieldset>
        </div>
        <div>
            <fieldset>
                <label for="wpmerge_service_password">Password</label> <br>
                <input type="password" id="wpmerge_service_password">
            </fieldset>
        </div>
    </div>
    <div class="ft pad">
        <span style="float:left;"><a href="<?php echo WPMERGE_SITE_LOST_PASS_URL; ?>" target="_blank">Forgot password?</a></span>
        <input type="submit"  value="Login to your account" name="service_login" class="button-primary" id="wpmerge_service_login_btn">
    </div>
</div>
</div>

<script type="text/javascript">
var redirect_after_login = '<?php echo $redirect_url; ?>';
</script>
<?php include(WPMERGE_PATH . '/templates/dev_footer.php'); ?>