<?php
/**
 * WPMerge
 * Copyright (c) 2018 Revmakx LLC
 * revmakx.com
 */

if(!defined('ABSPATH')){ exit; }

if($is_requiements_met){
?>
<script type="text/javascript">
function wpmerge_confirm_setup_env(){
    var env_selected = jQuery("input[name=wpmerge_env]:checked").val();
    var is_confirm = confirm('Do you really want mark this installation as '+env_selected+'?');
    if(is_confirm){
      jQuery('#wpmerge_common_setup_save_btn').addClass('loading');      
    }
    return is_confirm;
}
</script>
<div class="">
<h1>WPMerge.io</h1>
<div style="float: right;margin: -40px 10px 0 0;"><a href="mailto:help@wpmerge.io?body=WPMerge Plugin v<?php echo WPMERGE_VERSION; ?>" target="_blank">Support</a></div>

As of now, we do not clone the production site for development. Clone using your existing tools and install the WPMerge plugin on BOTH the production and dev site. 
<form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>"  onsubmit="return wpmerge_confirm_setup_env();">
  <input type="hidden" name="action" value="wpmerge_setup_env">
  <h3>Is this the production site or dev site?</h3>
  <label><input type="radio" name="wpmerge_env" value="PROD" onclick="jQuery('#dev_info_cont').hide();">This is production site</label>
  <br>
  <label><input type="radio" name="wpmerge_env" value="DEV" onclick="jQuery('#dev_info_cont').show();">This is development site</label>
  <br>  <br>
  <span id="dev_info_cont" style="display: none;">
  Note: For Development site, DB triggers will be added and int type will be converted to bigint to record and apply changes. <a onclick="jQuery('#dev_info_detailed_cont').show();" style="cursor: pointer;">Know more.</a>
  <br><br>
  <span id="dev_info_detailed_cont" style="display: none;">
  In this development environment triggers will be added to all your tables to record queries. We will change all the int columns to bigint and any new record will have a big primary key instead of your normal DB primary key. When you Test a merge or when you deploy the changes to production, these changes will be reverted and the primary keys will go to the normal state, bigint columns will be changed to normal int and triggers will be removed. At any point if you want to roll back to the original, unmodified DB, do a test merge under advanced settings.
  <br><br>
  </span>
  </span>

  <input type="submit" name="submit" class="button button-primary" id="wpmerge_common_setup_save_btn" value="Save">
</form>
</div>
<?php
}
else{//!$is_requiements_met
$rr = $requirements_result;
?>
<div class="">
<h1>WPMerge.io</h1>
<table width="300" border="0" cellspacing="5"><tbody>
  <tr><td class="wpmerge_b" colspan="3"><span class="error-box">Requirements not met!</span><br><br></td></tr>
<tr><td></td><td align="left"><strong>Required</strong></td><td align="left"><strong>Current</strong></td></tr>
<tr><td>PHP</td><td><?php echo $rr['required']['php']['version'] ?></td> <td><?php echo $rr['installed']['php']['version'] ?></td></tr>
<tr><td>MySQL</td><td><?php echo $rr['required']['mysql']['version'] ?></td> <td><?php echo $rr['installed']['mysql']['version'] ?></td></tr>
<tr><td>WP</td><td><?php echo $rr['required']['wp']['version'] ?></td> <td><?php echo $rr['installed']['wp']['version'] ?></td></tr>
</tbody></table>
<?php
}