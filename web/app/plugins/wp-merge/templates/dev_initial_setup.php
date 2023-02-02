<?php
/**
 * WPMerge
 * Copyright (c) 2018 Revmakx LLC
 * revmakx.com
 */

if(!defined('ABSPATH')){ exit; }

?>
<script type="text/javascript">
var wpmerge_dev_do_initial_setup = <?php echo (isset($_GET['dev_do_initial_setup']) && $_GET['dev_do_initial_setup'] === '1') ? 'true' : 'false'; ?>;
function wpmerge_dev_do_initial_setup_on_result_redirect(response_obj){
    //if any error in initial setup that will displayed in next page
    setTimeout(function() { location.assign(wpmerge_dev_ajax.admin_url + 'admin.php?page=wpmerge_dev_settings'); }, 1000);
}
</script>


<script type="text/javascript">
jQuery(document).ready(function($){
    if(wpmerge_dev_do_initial_setup){
        var options = {
            request_data : {is_background_task: 1},
            on_complete : wpmerge_dev_do_initial_setup_on_result_redirect,
            on_error : wpmerge_dev_do_initial_setup_on_result_redirect,
        }
        wpmerge_dev_exim_initiate_overall_task_js('do_db_modification_in_dev', options);
    }
});
</script>
<div class="wpmerge_b" style="padding: 50px;">
    <div class="loader"></div> Please wait till initial setup is over...
</div>  