<?php 
/**
 * WPMerge
 * Copyright (c) 2018 Revmakx LLC
 * revmakx.com
 */

if(!defined('ABSPATH')){ exit; }

?>
<br>
<br>
<br>
<br>
<div class="wpmerge_b" style="background: #f1f1f1; color: #444; font-family: 'Helvetica Neue',sans-serif; font-size: 13px; line-height: 1.4em;">
<div class="main-cols-cont cf">
  <div class="main-cols">



<?php

if(!$is_current_user_is_super_admin){ ?>
<div class="notice notice-error">
    <p>Only super admin can have access to this page.</p>
</div><?php } 

if($is_current_user_is_super_admin){ ?>

    <div class="m-box connect">
        <h2 class="hd">
            Connect Dev &amp; Prod WPMerge plugins
        </h2>
        <div class="pad">
            The WPMerge plugins in the dev site and prod site have to be connected before merging.
        </div>
        <div class="pad" style="border-top: 1px solid #eee;">
                <span id="wpmerge_prod_regenerate_key_btn_result"></span>
            <div class="cf">
                <input type="text" value='<?php echo $connect_str; ?>'  id="wpmerge_prod_connect_str" style="width:270px;"/>
                <input type="submit" class="button button-primary"  id="wpmerge_prod_copy_key"value="Copy">
                <a class="button" id="wpmerge_prod_regenerate_key_btn">Regenerate</a>
            </div>
            <div style="font-size: 11px; padding-top: 5px;">Copy &amp; Paste this key in the Dev site's Admin (WPMerge &rarr; Settings &rarr; Connect)</div>

        </div>

    </div>
<?php } ?>
  </div>
</div>
</div>

<?php /*
<!-- Please copy paste the following string in Dev site.
<textarea id="wpmerge_prod_connect_str"><?php echo $connect_str; ?></textarea>
<button onclick="wpmerge_copy_connect_str()">Copy text</button><span id="wpmerge_prod_copy_status"></span>

<br><br>
<button id="wpmerge_prod_regenerate_key">Generate or Regenerate Key</button>
<span>If you regenerate the key, the old key will not work.</span>
 -->
*/ ?>

<script type="text/javascript">
jQuery(document).ready(function($) {
  $( "#wpmerge_prod_connect_str" ).select();
});
</script>
