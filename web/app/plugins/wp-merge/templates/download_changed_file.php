<?php
/**
 * WPMerge
 * Copyright (c) 2018 Revmakx LLC
 * revmakx.com
 */

if(!defined('ABSPATH')){ exit; }

@ini_set('display_errors', 0);

if (empty($wpmerge_file_path) || !file_exists($wpmerge_file_path)) { ?>
	<script type="text/javascript">
		alert('No new or modified files found.');
	</script>
	<?php
	wpmerge_debug::log($wpmerge_file_path,'-----------file not found for downloading----------------');
	return ;
}

if (dirname($wpmerge_file_path) !== WPMERGE_TEMP_DIR ) {
	wpmerge_debug::log(array(),'-----------filename not match----------------');
	return ;
}

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="'.basename($wpmerge_file_path).'"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($wpmerge_file_path));


//To avoid any memory exaust - https://stackoverflow.com/a/31277949/2975952
if (@ob_get_level()) {
	@ob_end_clean();
}

@ob_clean();
@flush();

@readfile($wpmerge_file_path);
@unlink($wpmerge_file_path);
wpmerge_delete_option('changed_zip_file');
exit;

?>