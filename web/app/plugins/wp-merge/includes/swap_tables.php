<?php
/**
 * WPMerge
 * Copyright (c) 2018 Revmakx LLC
 * revmakx.com
 */

class wpmerge_swap_tables{

    public function swap_tables($current_db_prefix, $testing_db_prefix, $tmp_swap_db_prefix){

        //$current_db_prefix - mostly wp db table prefix

        /*
        1) rename current_db_prefix to tmp_swap_db_prefix,
        2) rename testing_db_prefix to current_db_prefix
        */

        //DIFFERENT APPROACH NEEDED! for rename_current_tables_with_tmp_swap_prefix and rename_testing_tables_with_current_prefix

        $this->rename_current_tables_with_tmp_swap_prefix($current_db_prefix, $tmp_swap_db_prefix);

        //rename imported tmp tables to current table prefix  -- tmp_new_db_prefix_tables => current_tables_prefix
        $this->rename_testing_tables_with_current_prefix($testing_db_prefix, $current_db_prefix);
    }

    private function rename_current_tables_with_tmp_swap_prefix($current_db_prefix, $tmp_swap_db_prefix){

        $wpmerge_common_db_obj  = new wpmerge_common_db();
        $excluded_tables = $wpmerge_common_db_obj->get_excluded_tables();

        $escaped_current_db_prefix = wpmerge_esc_table_prefix($current_db_prefix);
        $tables = $GLOBALS['wpdb']->get_col("SHOW TABLES LIKE '".$escaped_current_db_prefix."%'");

		if(!$tables){
			throw new wpmerge_exception('tables_missing_for_rename');
        }

        $GLOBALS['wpdb']->query("SET FOREIGN_KEY_CHECKS = 0");
		foreach ($tables as $table) {
			//if exclude table continue
			$table_without_prefix = wpmerge_remove_prefix($current_db_prefix, $table);
			if(in_array($table_without_prefix, $excluded_tables)){
				continue;//skip this table
            }
            $tmp_swap_table =  $tmp_swap_db_prefix . $table_without_prefix;
			$GLOBALS['wpdb']->query("RENAME TABLE `".$table."` TO `".$tmp_swap_table."`");//RENAME can handle VIEW table //verify rename improve later?
		}
    }
    
    private function rename_testing_tables_with_current_prefix($testing_db_prefix, $current_db_prefix){

        $wpmerge_common_db_obj  = new wpmerge_common_db();
        $excluded_tables = $wpmerge_common_db_obj->get_excluded_tables();
        
        //$escaped_testing_db_prefix = wpmerge_esc_table_prefix($testing_db_prefix);
        //$tables = $GLOBALS['wpdb']->get_col("SHOW TABLES LIKE '".$escaped_testing_db_prefix."%'");
        $tables = wpmerge_get_tables_details($testing_db_prefix);//this will give view tables at the end
        if(!$tables){
            //echo 'Something not right';
			throw new wpmerge_exception('tables_missing_for_rename');
        }

        $GLOBALS['wpdb']->query("SET FOREIGN_KEY_CHECKS = 0");
		foreach ($tables as $table_details) {
            $table = $table_details['TABLE_NAME'];
            $table_type = $table_details['TABLE_TYPE'];
            //if exclude table continue
			$table_without_prefix = wpmerge_remove_prefix($testing_db_prefix, $table);
			if(in_array($table_without_prefix, $excluded_tables)){
                continue;//skip this table
            }
            $table_with_current_prefix =  $current_db_prefix . $table_without_prefix;
            $GLOBALS['wpdb']->query("RENAME TABLE `".$table."` TO `".$table_with_current_prefix."`");//RENAME can handle VIEW table //verify rename improve later?
            if($table_type == 'VIEW'){
                wpemerge_fix_view_table_references($table_with_current_prefix, $testing_db_prefix, $current_db_prefix);
            }
		}
    }
    
    public function delete_tmp_swap_tables($tmp_swap_db_prefix){

        //$escaped_tmp_swap_db_prefix = wpmerge_esc_table_prefix($tmp_swap_db_prefix);
        //$tables = $GLOBALS['wpdb']->get_col("SHOW TABLES LIKE '".$escaped_tmp_swap_db_prefix."%'");
        $tables_details = wpmerge_get_tables_details($tmp_swap_db_prefix);
        if(empty($tables_details)){
            //echo 'Something not right';
            throw new wpmerge_exception('tables_missing_for_delete');
        }

        $GLOBALS['wpdb']->query("SET FOREIGN_KEY_CHECKS = 0");
        foreach ($tables_details as $table_detail) {
            $table = $table_detail['TABLE_NAME'];
            $table_type = $table_detail['TABLE_TYPE'];
            //$tmp_table =  $this->tmp_old_db_prefix.$table_without_prefix;
            if($table_type == 'BASE TABLE'){
                $GLOBALS['wpdb']->query("DROP TABLE IF EXISTS `".$table."`");//verify delete improve later?
            }
            elseif($table_type == 'VIEW'){
                $GLOBALS['wpdb']->query("DROP VIEW IF EXISTS `".$table."`");//verify delete improve later?
            }
        }
    }
}