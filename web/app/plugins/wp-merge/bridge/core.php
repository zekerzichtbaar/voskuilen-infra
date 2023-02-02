<?php
/**
 * WPMerge
 * Copyright (c) 2018 Revmakx LLC
 * revmakx.com
 */


class wpmerge_bridge_core {

	public function load_page($action) {
		echo '
			<html>
				<head>
					<title>WPMerge Bridge</title>
					' . $this->include_js() . '
					' . $this->include_css() . '
				</head>
				<body style="background: #f1f1f1;font-family: -apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,Oxygen-Sans,Ubuntu,Cantarell,\'Helvetica Neue\',sans-serif;">
					<div class="wpmerge_b" style="padding: 50px;">
					<a href="javascript:history.back()" style="    font-size: 13px;">&lt; Go Back</a>
						<div class="main-cols" style="float:none;">
							<span class="maintenance_info_task_progress"><br>Maintenance mode is enabled for this Dev site and it will be disabled at the end of process.</span>
							<div id="wpmerge_dev_exim_progress">
								<div class="process-steps-progress" style="padding: 10px 0 0 30px;"></div>
							</div>
							<div id="wpmerge_dev_exim_progress2">
							</div>
						</div>
					</div>
					<script type="text/javascript" language="javascript">
					var wpmerge_dev_is_bridge_call = true;
					var wpmerge_dev_ajax = ' . $this->vars() .';
					var wpmerge_ajax = ' . $this->vars() .';
					</script>
					'.$this->get_onload_script($action).'
				</body>
			</html>';
	}

	private function include_js(){
		return '<script type="text/javascript" src="bridge/wp-files/jquery.js"></script>
				<script type="text/javascript" src="js/dev_admin.js"></script>
				<script type="text/javascript" src="js/common_admin.js"></script>
				<script type="text/javascript" src="js/dev_exim.js"></script>
				<script type="text/javascript" src="bridge/script.js"></script>';
	}

	private function include_css(){
		return '<link rel="stylesheet"  href="css/dev_admin.css" type="text/css"/>
				<link rel="stylesheet"  href="css/common.css" type="text/css"/>';
	}

	private function get_onload_script($action){
		$call = '';
		if($action == 'prod_db_import'){
			$call = 'wpmerge_dev_exim_initiate_overall_task_js(\'prod_db_import\', {on_complete:wpmerge_dev_bridge_task_on_complete});';
		}
		elseif($action == 'apply_changes_for_dev_in_dev' || $action == 'apply_changes_for_prod_in_dev'){
			$call = 'wpmerge_dev_check_and_do_apply_changes(\''.$action.'\');';
		}
		elseif($action == 'do_prod_db_import_and_db_mod_then_record_on'){
			$call = 'wpmerge_do_prod_db_import_and_db_mod_then_record_on();';
		}
		elseif($action == 'do_prod_db_import_and_db_mod'){
			$call = 'wpmerge_do_prod_db_import_and_db_mod();';
		}
		return '
		<script type="text/javascript" language="javascript">
		wpmerge_bridge_action = \''.$action.'\';
		jQuery(document).ready(function(){
			'.$call.'
		});
		</script>';
	}

	public function process(){
		$this->include_common();
		wpmerge_bridge_common::init();
		wpmerge_bridge_common::include_js_var();
	}

	private function start_from_beginning(){?>
		<script type="text/javascript" language="javascript">
			wpmerge_dev_exim_initiate_overall_task_js('export_changed_files_in_dev');
		</script> <?php
	}

	private function vars(){
		return json_encode(array( 'ajax_url' => 'bridge/request.php' ));
	}

	private static function include_common() {
		require_once dirname(__FILE__). '/common.php';
	}
}