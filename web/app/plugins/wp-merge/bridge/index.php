<?php
/**
 * WPMerge
 * Copyright (c) 2018 Revmakx LLC
 * revmakx.com
 */

class wpmerge_bridge{

	private $action;
	private $bridge_core;

	public function __construct(){
		$this->set_action();
		$this->include_bridge_core();
		$this->choose_action();
	}

	private function set_action(){
		if (!isset($_GET['action'])) {
			die('Please choose the action');
		}

		$this->action = $_GET['action'];
	}

	private function include_bridge_core(){
		require_once dirname(__FILE__) . "/bridge/core.php";
		$this->bridge_core = new wpmerge_bridge_core();
	}

	private function choose_action(){
		if ($this->action === 'prod_db_import' || $this->action === 'apply_changes_for_dev_in_dev' || $this->action === 'apply_changes_for_prod_in_dev' || $this->action === 'do_prod_db_import_and_db_mod_then_record_on' || $this->action === 'do_prod_db_import_and_db_mod') {
			return $this->core_choose_step();
		}
	}

	private function core_choose_step(){
		$this->bridge_core->process();
		$this->bridge_core->load_page($this->action);
	}
}

new wpmerge_bridge();