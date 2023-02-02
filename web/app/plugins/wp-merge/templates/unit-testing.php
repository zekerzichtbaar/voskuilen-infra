<?php
/**
 * WPMerge
 * Copyright (c) 2018 Revmakx LLC
 * revmakx.com
 */
?>
<div class="wrap" id="wpmerge">
	<form action="#" method="post">
			<tr><td> <input type="submit" name='dev-custom-test' class="button-primary" value="Custom Test"/></td></tr>
	</form>
</div>

<?php

if (isset($_POST['dev-custom-test'])) {
	Dev_Options_Custom_works::init()->custom_works();
}

class Dev_Options_Custom_works {
	private $wpdb;

	public static function init() {
		$class_name = get_class();
		return new $class_name();
	}

	public function __construct() {
		$this->init_db();
	}

	public function init_db(){
		global $wpdb;
		$this->wpdb = $wpdb;
	}

	public function custom_works(){
		  require WPMERGE_PATH . '/includes/prepare_bridge.php';
    		$prepare_bridge = new wpmerge_prepare_bridge();
    		$response = $prepare_bridge->delete();
	}
}

?>
