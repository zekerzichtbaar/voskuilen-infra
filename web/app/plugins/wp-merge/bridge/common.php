<?php
/**
 * WPMerge
 * Copyright (c) 2018 Revmakx LLC
 * revmakx.com
 */


class wpmerge_bridge_common{

	public static function init(){
		self::include_config();
		self::initiate_database();
		self::include_files();
	}

	private static function include_config() {
		include_once dirname(__FILE__). '/wp-functions-modified.php';
		include_once dirname(__FILE__). '/wp-db-modified.php';
		include_once dirname(dirname(__FILE__)). '/config.php';
	}

	private static function initiate_database() {
		//initialize wpdb since we are using it independently
		global $wpdb;

		$wpdb = new wpdb(DB_USER, DB_PASSWORD, DB_NAME, DB_HOST);

		//setting the prefix from post value;
		$wpdb->prefix = $wpdb->base_prefix = DB_PREFIX;
	}

	private static function include_files() {
		require_once dirname(__FILE__). '/include.php';
		$include = new wpmerge_include();
		$include->init();
	}

	public static function include_js_var(){ ?>
		<script type='text/javascript' language='javascript'>
			var wpmerge_admin_url = '<?php echo wpmerge_get_option('admin_url') ?>';
		</script>
	<?php }
}
