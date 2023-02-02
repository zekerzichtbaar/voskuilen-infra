<?php
/**
 * WPMerge
 * Copyright (c) 2018 Revmakx LLC
 * revmakx.com
 */


class wpmerge_request{

	private $action;
	private $dev_exim_control;

	public function __construct(){
		$this->set_action();
		$this->include_common();
		$this->choose_action();
	}

	private function set_action(){
		if (!isset($_POST['action'])) {
			die('Please choose the action');
		}

		$this->action = $_POST['action'];
	}

	private function include_common() {
		require_once dirname(__FILE__). '/common.php';
		wpmerge_bridge_common::init();
	}

	private function choose_action(){
		if ($this->action === 'wpmerge_exim_initiate_overall_task') {
			$dev_exim_control = new wpmerge_dev_exim_control();
			return $dev_exim_control->initiate_overall_task();
		}

		if ($this->action === 'wpmerge_exim_continue_overall_task') {
			$dev_exim_control = new wpmerge_dev_exim_control();
			return $dev_exim_control->continue_overall_task();
		}

		if ($this->action === 'wpmerge_exim_get_default_state_for_dummy') {
			$dev_exim_control = new wpmerge_dev_exim_control();
			return $dev_exim_control->get_default_state_for_dummy();
		}

		if ($this->action === 'wpmerge_prod_db_delta_import') {
			wpmerge_prod_db_delta_import();
		}

		if ($this->action === 'wpmerge_dev_process_ajax_request') {
			wpmerge_dev_process_ajax_request();
		}

		if ($this->action === 'wpmerge_prod_db_export') {
			wpmerge_prod_db_export();
		}

		if ($this->action === 'wpmerge_dev_record_switch') {
			wpmerge_dev_record_switch();
		}
	}
}

new wpmerge_request();