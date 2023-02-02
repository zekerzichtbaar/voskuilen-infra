<?php
/**
 * WPMerge
 * Copyright (c) 2018 Revmakx LLC
 * revmakx.com
 */

class same_server_db_clone{

    public function clone_table_structure($table, $new_table){//before run SET FOREIGN_KEY_CHECKS = 0
        
		$GLOBALS['wpdb']->query("DROP TABLE IF EXISTS `$new_table`");
		$sql = "CREATE TABLE `$new_table` LIKE `$table`";
		$is_cloned = $GLOBALS['wpdb']->query($sql);
		if ($is_cloned === false) {
            $query_error = wpmerge_get_error_msg('clone_table_structure_query_error').' Error:('.$GLOBALS['wpdb']->last_error.') Table:('.$table.') Query:('.$sql.')';
            throw new wpmerge_exception('clone_table_structure_query_error', $query_error);
		}
		return true;
    }
    
    public function clone_view_structure($table, $new_table, $old_db_prefix, $new_db_prefix){//before run SET FOREIGN_KEY_CHECKS = 0


        $create_view_details = $GLOBALS['wpdb']->get_row("SHOW CREATE TABLE $table", ARRAY_A);

        if (empty($create_view_details['View'])) {
            //not a view table
            throw new wpmerge_exception('clone_view_structure_not_view_table');
        }

        $create_view_sql = $create_view_details['Create View'];

        $create_view_sql = wpmerge_normalise_create_view_sql($create_view_sql, $table);

        $create_view_sql = wpmerge_replace_table_prefix_in_query($old_db_prefix, $new_db_prefix, $create_view_sql);//this is the fix for view_table_references

        wpmerge_wpdb::query("DROP TABLE IF EXISTS `$new_table`");
        wpmerge_wpdb::query("DROP VIEW IF EXISTS `$new_table`");
        $is_cloned =  wpmerge_wpdb::query($create_view_sql);
        if ($is_cloned === false) {
            $query_error = wpmerge_get_error_msg('clone_view_structure_query_error').' Error:('.$GLOBALS['wpdb']->last_error.') Table:('.$table.') Query:('.$sql.')';
            throw new wpmerge_exception('clone_view_structure_query_error', $query_error);
		}
		return true;
    }

    public function clone_table_content($table, $new_table, $limit, $offset, $misc_options){//before run SET FOREIGN_KEY_CHECKS = 0
        $_total_time_taken = 0 ;
        $_rows_this_loop = 0;

        //following code to optimize the query
        $select_args = array();
        $select_args['columns'] = '*';
        $select_args['table'] = $table;
        $select_args['limit'] = $limit;
        $select_args['offset'] = $offset;
        if(isset($misc_options['total_rows'])){
            $select_args['total_rows'] = $misc_options['total_rows'];
        }

        $insert_select_by_memory_limit_obj = new wpmerge_insert_select_by_memory_limit($select_args);

        $primary_auto_inc_col = $insert_select_by_memory_limit_obj->get_primary_auto_inc_col();
        $limit = $insert_select_by_memory_limit_obj->get_calculated_limit_by_data_index_len();
        //data to get optimized value ends here

		while(true){
            $inserted_rows = 0;

            if( !empty($primary_auto_inc_col) ){
                $insert_select_query = "INSERT `$new_table` SELECT `$table`.* FROM (SELECT `$primary_auto_inc_col` FROM `$table` LIMIT " . $limit . " OFFSET ".$offset.") q JOIN `$table` ON `$table`.`$primary_auto_inc_col` = q.`$primary_auto_inc_col`";
            }
            else{
                $insert_select_query =  "INSERT `$new_table` SELECT * FROM `$table` LIMIT $limit OFFSET $offset";
            }
            $_start_time = microtime(1);
			$inserted_rows = $GLOBALS['wpdb']->query( $insert_select_query );
            $_time_taken = microtime(1) - $_start_time;
            $_total_time_taken += $_time_taken;
            $_rows_this_loop += $inserted_rows;
            wpmerge_debug::printr(array('query_time_taken' => $_time_taken, $table, 'offset' => $offset, 'all_queries_time_taken' => $_total_time_taken, '_rows_this_loop' => $_rows_this_loop, 'insert_select_query' => $insert_select_query), 'data_clone_cont');

			if ($inserted_rows !== false) {
				$offset = $offset + $inserted_rows;
				if ($inserted_rows < $limit) {
                    //even $inserted_rows == 0 is fine, that means done
					return array('eof' => true, 'offset' => $offset);
				}
			} else {
                //possible error
                $query_error = wpmerge_get_error_msg('clone_table_content_query_error').' Error:('.$GLOBALS['wpdb']->last_error.') Table:('.$table.') Query:('.$sql.')'.' '.$insert_select_query;
                throw new wpmerge_exception('clone_table_content_query_error', $query_error);
			}
            if(wpmerge_is_time_limit_exceeded()){
                return array('eof' => false, 'offset' => $offset);
            }
        }
	}
}

