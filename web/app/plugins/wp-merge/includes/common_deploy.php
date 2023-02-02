<?php
/**
 * WPMerge
 * Copyright (c) 2018 Revmakx LLC
 * revmakx.com
 */

if(!defined('ABSPATH')){ exit; }

class wpmerge_common_deploy{
    private $is_atleast_one_query_is_successful = false;

    public function do_apply_changes_for_prod($options){
        //$__start_do_apply_time = microtime(1) - WPMERGE_START_TIME;
        //$__start_new_start = microtime(1);
        if(empty($options) || !is_int($options['offset']) || !is_int($options['total']) || $options['offset'] >= $options['total'] ){
            throw new wpmerge_exception("invalid_options");
        }

        // following code commented for optimization, these codes are coded for optimization but now these codes takes more time in some cases
        // $use_all_find_replace_pairs_threshold = 100000;

        // if(!isset($GLOBALS['WPMERGE_ALL_FIND_REPLACE_PAIRS'])){
        //     $GLOBALS['WPMERGE_ALL_FIND_REPLACE_PAIRS'] = NULL;
        // }

        wpmerge_wpdb::query("SET FOREIGN_KEY_CHECKS = 0");

        if( $options['offset'] === 0 ){
            //file_put_contents(ABSPATH.'/__in_prod.php', '');
            $this->reset_queries_for_fresh_apply_changes();//need to improve this part, may it can go to exim control as one of the task

            wpmerge_debug::reset_log_deploy_issues();
            $GLOBALS['WPMERGE_UNIQUE_INSERT_ID_MIN_MAX'] = $this->get_min_max_unique_insert_id(false);
        }
        elseif(empty($GLOBALS['WPMERGE_UNIQUE_INSERT_ID_MIN_MAX'])){
            $GLOBALS['WPMERGE_UNIQUE_INSERT_ID_MIN_MAX'] = $this->get_min_max_unique_insert_id(true);
        }

        if(
            wpmerge_is_prod_env() && 
            ( 
                empty($options['old_db_prefix']) || empty($options['new_db_prefix'])
            )
        ){
            throw new wpmerge_exception("old_or_new_db_prefix_missing");
        }
        $eof = false;
        $offset = $options['offset'];
        $total = $options['total'];
        $last_query_id = $options['last_query_id'];
        $row_limit = 5000;
        $q_offset = $offset;
        $sql_where_append = '';
        
        if(isset($last_query_id)){
			$q_offset = 0;
			$sql_where_append = " AND `id` > $last_query_id";//to increase the efficiency for a million rows+ when rows and offset searching becomes big.
		}
      
        
        // following code commented for optimization, these codes are coded for optimization but now these codes takes more time in some cases

        // global $wpmerge_apply_changes_find_and_replace;

        // if(is_null($GLOBALS['WPMERGE_ALL_FIND_REPLACE_PAIRS'])){
        //     $table_log_queries = $GLOBALS['wpdb']->base_prefix .'wpmerge_log_queries';
        //     $_query = "SELECT COUNT(`id`) FROM `".$table_log_queries."` WHERE `apply_changes_insert_id` IS NOT NULL AND `is_applied` = '1' AND `is_record_on` = '1' AND `type` = 'query'";

        //     wpmerge_debug::log($_query);
            
        //     $__get_pairs_count_start = microtime(1);
        //     $total_pairs = $GLOBALS['wpdb']->get_var($_query);
        //     $__get_pairs_count_time = microtime(1) - $__get_pairs_count_start;

        //     if($total_pairs <= $use_all_find_replace_pairs_threshold){

        //         $__get_all_pairs_start = microtime(1); $__get_all_pair_mem_start = memory_get_usage();
        //         $wpmerge_apply_changes_find_and_replace = wpmerge_get_all_find_replace_pairs();
        //         $__get_all_pair_time = microtime(1) - $__get_all_pairs_start;$__get_all_pair_mem_used = memory_get_usage() - $__get_all_pair_mem_start;
        //         $is_all_pairs = true;
        //     }
        //     else{
        //         $is_all_pairs = false;
        //     }
        //     $GLOBALS['WPMERGE_ALL_FIND_REPLACE_PAIRS'] = $is_all_pairs;
        // }
        
        //$__delta_query_exec_total = 0;

        //$__query_start = microtime(true);
        $table_log_queries = $GLOBALS['wpdb']->base_prefix .'wpmerge_log_queries';
        //$db_delta_queries = $GLOBALS['wpdb']->get_results("SELECT * FROM `".$table_log_queries."` WHERE `is_record_on` = '1' AND `type` = 'query'  ".$sql_where_append." ORDER BY id ASC LIMIT $row_limit OFFSET $q_offset");

        $select_args = array();
        $select_args['columns'] = '*';
        $select_args['table'] = $table_log_queries;
        $select_args['where'] = "`is_record_on` = '1' AND `type` = 'query'  ".$sql_where_append."";
        $select_args['order'] = "id ASC";
        $select_args['limit'] = $row_limit;
        $select_args['offset'] = $q_offset;
        $select_args['optimize_query'] = isset($last_query_id) ? false : true;

        $select_by_memory_limit_obj = new wpmerge_select_by_memory_limit($select_args);
		$db_delta_queries = $select_by_memory_limit_obj->process_and_get_results();
        //$__query_end = microtime(true);

        if(empty($db_delta_queries)){
            throw new wpmerge_exception("no_queries_to_apply");
        }

        //$__for_start_time = microtime(1);
        
        //$i = count($wpmerge_apply_changes_find);//old_method
        $run_i = 0;
        foreach ($db_delta_queries as $query) {
            $GLOBALS['EZSQL_ERROR'] = array();//query error logger, fix for memory leak by wordpress

            $last_insert_id = null;
            //$__for_block_01_start = microtime(1);
            //echo '<br>'.$query->query;
            $query->query = wpmerge_get_query_from_row($query);
            $query->query = trim($query->query);

            //$__replace_prefix_start = microtime(1);
            if(wpmerge_is_prod_env()){
                //rename table prefix
                //$query->query = wpmerge_replace_table_prefix_in_query($options['old_db_prefix'], $options['new_db_prefix'], $query->query);
                $query->query = wpmerge_replace_table_prefix_in_query_use_full_table_names($options['old_db_prefix'], $options['new_db_prefix'], $options['old_tables_list'], $options['new_tables_list'], $query->query);
            }
            //$__replace_prefix_total += microtime(1) - $__replace_prefix_start;

            //$__do_find_replace_start = microtime(true);
            $query->query = wpmerge_find_and_replace_insert_ids($query->query);
            //file_put_contents(ABSPATH.'/__in_prod_queries.php', "\n\n ".$query->query, FILE_APPEND);
            //$__do_find_replace_time += microtime(true) - $__do_find_replace_start;

            //$__if_ins_rep_start = microtime(true);
            $insert_find = preg_match( '/^\s*(insert|replace)\s/i', $query->query ) ;// preg_match( '/^\s*(insert|replace)\s/i', $query ) 
            //$__if_ins_rep_total += microtime(true) - $__if_ins_rep_start;

            //$__for_block_01_total += microtime(1) - $__for_block_01_start;

            //" !empty($query->auto_increment_column) " If a table doesn't have auto_increment_column, then there won't be insert id, run like update and delete query
            if($insert_find &&
            !empty($query->auto_increment_column)
            ){//insert|replace query
                //if insert|replace query find the new insert_id and replace the previous bigint id with new one for all the upcoming queries(insert/update/delete)

                //$query->unique_insert_id should be valid integer
                if(empty($query->unique_insert_id)){
                    $query_error = wpmerge_get_error_msg('unexpected_empty_unique_insert_id').' Query:('.$query->query.')';
                    throw new wpmerge_exception('unexpected_empty_unique_insert_id', $query_error);
                }
                $GLOBALS['wpdb']->insert_id = 0;

                //$__delta_query_start = microtime(true);
                $query_result = $GLOBALS['wpdb']->query($query->query);
                //$__delta_query_exec_total += microtime(true) - $__delta_query_start;
                
                $last_insert_id = $GLOBALS['wpdb']->insert_id;
                if($query_result === false){
                    $query_error = wpmerge_get_error_msg('query_error').' Query:('.$query->query.')';
                    if($GLOBALS['wpdb']->last_error){
                        $query_error = wpmerge_get_error_msg('query_error').' Error:('.$GLOBALS['wpdb']->last_error.') Query:('.$query->query.')';
                    }
                    //throw new wpmerge_exception('query_error', $query_error);//commented to ignore query errors
                   
                    wpmerge_debug::log_deploy_issues($query_error);
                }
                else{
                    $this->update_atleast_one_query_is_successful();
                }

                if($query_result !== false){//as we ignoring query errors by disabling throw above, so this is needed
                    if(empty($last_insert_id)){
                        //$__try_get_ins_id_start = microtime(1);
                        $last_insert_id = wpmerge_try_getting_insert_id($last_insert_id, $query);
                        //$__try_get_ins_id_total += microtime(1) - $__try_get_ins_id_start;
                    }
                    if(empty($last_insert_id)){
                        $query_error = wpmerge_get_error_msg('invalid_insert_id').' Query:('.$query->query.')';
                        throw new wpmerge_exception('invalid_insert_id', $query_error);
                    }
                    // commented because both check in the above code
                    // if(empty($query->unique_insert_id) || empty($last_insert_id)){
                    //     throw new wpmerge_exception('old_or_new_insert_id_is_missing');
                    // }
                    
                    // $wpmerge_apply_changes_find[$i] = $query->unique_insert_id;
                    // $wpmerge_apply_changes_replace[$i] = $last_insert_id;
                    
                    // following code commented for optimization, these codes are coded for optimization but now these codes takes more time in some cases
                    //$wpmerge_apply_changes_find_and_replace[$query->unique_insert_id] = $last_insert_id;

                    //file_put_contents(ABSPATH.'/__in_prod_ins_ids.php', "\n".$query->unique_insert_id." ==> ".$last_insert_id, FILE_APPEND);
                    //$i++;
                }
            }
            else{
                //$__delta_query_start = microtime(true);
                //run update|delete and ddl queries as such
                if ( preg_match( '/^\s*(create|alter|truncate|drop|rename)\s/i', $query->query ) ) {
                    $query_result = wpmerge_wpdb::query($query->query);
                    
                }else{//should be update|delete queries
                    $query_result = $GLOBALS['wpdb']->query($query->query);
                }
                //$__delta_query_exec_total += microtime(true) - $__delta_query_start;
                if($query_result === false){
                    $query_error = wpmerge_get_error_msg('query_error').' Query:('.$query->query.')';
                    if($GLOBALS['wpdb']->last_error){
                        $query_error = wpmerge_get_error_msg('query_error').' Error:('.$GLOBALS['wpdb']->last_error.') Query:('.$query->query.')';
                    }
                   // throw new wpmerge_exception('query_error', $query_error);commented to ignore query errors
                   
                   wpmerge_debug::log_deploy_issues($query_error);
                }
                else{
                    $this->update_atleast_one_query_is_successful();
                }
                
            }
            $update_log_queries = array('is_applied' => 1);
            if(!empty($last_insert_id)){
                $update_log_queries['apply_changes_insert_id'] = $last_insert_id;
            }
            //$__update_ins_id_and_applied_start = microtime(1);
            $GLOBALS['wpdb']->update($table_log_queries, $update_log_queries, array('id' => $query->id));
            //$__update_ins_id_and_applied_total += microtime(1) - $__update_ins_id_and_applied_start;

            $last_query_id = $query->id;
            $offset++;
            $run_i++;
            if($offset === $total){
                $eof = true;
                break;
            }
            if(wpmerge_is_time_limit_exceeded()){
                break;
            }
        }
        //$__for_end_time = microtime(1) - $__start_new_start;
        //$__for_alone_end_time = microtime(1) - $__for_start_time;

        //$__start_new_time = microtime(true) - $__start_new_start;

        // wpmerge_debug::printr("\n\n t:".microtime(1)
        // ."|\n run_i:".$run_i." offset:".$offset
        // ."|\n last_query_id:".$last_query_id
        // ."|\n get delta queries:".round($__query_end-$__query_start,2)
        // ."|\n __delta_query_exec_total:".round($__delta_query_exec_total, 2)
        // ."|\n get_find_rep_cont_db:".round($__get_find_replace_insert_ids_time, 2)
        // ."|\n do_find_replace:".round($__do_find_replace_time, 2)
        // ."|\n start_apply:".round($__start_do_apply_time, 2)
        // ."|\n save_find_replace:".round($__save_find_replace_data_time, 2)
        // ."|\n pre_find_count:".$total_pairs
        // ."|\n full_time:".round($__start_new_time, 2)
        // ."|\n replace_prefix:".round($__replace_prefix_total, 2)
        // ."|\n try_get_ins_id:".round($__try_get_ins_id_total, 2)
        // ."|\n update_ins_id_and_applied_total:".round($__update_ins_id_and_applied_total, 2) 
        // ."|\n __for_end_time:".round($__for_end_time, 2)
        // ."|\n __for_alone_end_time:".round($__for_alone_end_time, 2)
        // ."|\n __for_block_01_total:".round($__for_block_01_total, 2)
        // ."|\n __if_ins_rep_total:".round($__if_ins_rep_total, 2)
        // ."|\n __find_rep_serial_total:".round($GLOBALS['__find_rep_serial_total'], 2)
        // ."|\n __find_rep_str_replace_total:".round($GLOBALS['__find_rep_str_replace_total'], 2) 
        // ."|\n __find_rep_other_total:".round($GLOBALS['__find_rep_other_total'], 2)
        // ."|\n __query_based_get_pairs_total:".round($GLOBALS['__query_based_get_pairs_total'], 2) 
        // ."|\n __get_pairs_count_time:".round($__get_pairs_count_time, 2) 
        // ."|\n __get_all_pair_time:".round($__get_all_pair_time, 2) 
        // ."|\n __memory_peak(MB):".round(memory_get_peak_usage()/(1024*1024), 3) 
        // ."|\n __get_all_pair_mem_used(MB):".round($__get_all_pair_mem_used/(1024*1024), 3) , 'common_deploy');

        return array('offset' => $offset, 'eof' => $eof, 'last_query_id' => $last_query_id);
    }

    public function reset_queries_for_fresh_apply_changes(){
        $table_log_queries = $GLOBALS['wpdb']->base_prefix .'wpmerge_log_queries';
        $GLOBALS['wpdb']->query("UPDATE $table_log_queries SET apply_changes_insert_id =  NULL, is_applied = 0 WHERE `is_record_on` = '1' AND `type` = 'query' ");
        $dummy = null;
    }

    private function update_atleast_one_query_is_successful(){
        if(!wpmerge_is_prod_env()){
            return;
        }
        if(!$this->is_atleast_one_query_is_successful){//shuld go inside only once per call
            $this->is_atleast_one_query_is_successful = wpmerge_get_option('delta_import_is_atleast_one_query_is_successful');
            if(!$this->is_atleast_one_query_is_successful){
                wpmerge_update_option('delta_import_is_atleast_one_query_is_successful', 1);
                $this->is_atleast_one_query_is_successful = 1;
            }
        }
    }

    private function get_min_max_unique_insert_id($cache){
        if($cache !== true && $cache !== false){
            throw new wpmerge_exception('invalid_params');
        }

        if($cache){

            $cached_min_max_data = wpmerge_get_option('apply_changes_unique_id_min_max');
            if( 
                !empty($cached_min_max_data) && 
                isset($cached_min_max_data['min_id']) && 
                is_int($cached_min_max_data['min_id']) && 
                isset($cached_min_max_data['max_id']) && 
                is_int($cached_min_max_data['max_id']) 
                ){
                return $cached_min_max_data;
            }
            else{
                //fallback go and fetch the data
            }
        }


        $table_log_queries = $GLOBALS['wpdb']->base_prefix .'wpmerge_log_queries';
        $_query = "SELECT min(`unique_insert_id`) as min_id, max(`unique_insert_id`) as max_id FROM `".$table_log_queries."` WHERE `is_record_on` = '1' AND `type` = 'query' ";

        wpmerge_debug::log($_query);
        
        //$__min_max_query_start = microtime(1);
        $result = $GLOBALS['wpdb']->get_row($_query, ARRAY_A);
        //$__min_max_query_time_taken = microtime(1) - $__min_max_query_start;

        //wpmerge_debug::printr($__min_max_query_time_taken, '__unique_id_min_max_time_taken');

        $min_max_data = array();

        //may be no insert query is recorded when no valid min and max is found.

        $min_max_data['min_id'] = is_numeric($result['min_id']) ? (int)$result['min_id'] : 0;

        $min_max_data['max_id'] = is_numeric($result['max_id']) ? (int)$result['max_id'] : PHP_INT_MAX;

        $min_max_data['last_updated'] = microtime(1);
        wpmerge_update_option('apply_changes_unique_id_min_max', $min_max_data);

        return $min_max_data;
    }

}

function wpmerge_get_find_replace_pairs_from_numbers_used_in_query($numbers){
    return wpmerge_get_new_find_replace_pairs_to_replace($numbers, null);
}

function wpmerge_get_all_find_replace_pairs(){
    return wpmerge_get_new_find_replace_pairs_to_replace(null, $is_all=true);
}

function wpmerge_get_new_find_replace_pairs_to_replace($numbers=null, $is_all=null){
    $q_where_part = '';
    $q_order_limit_part = '';
    if( !empty($numbers) ){
        $q_where_part = " AND `unique_insert_id` IN(".implode(", ", $numbers).")";
    }
    elseif( $is_all ){
        $q_order_limit_part = " ORDER BY `id` DESC";
    }
    else{
        //something not right
        throw new wpmerge_exception('invalid_params');
    }
    
    $table_log_queries = $GLOBALS['wpdb']->base_prefix .'wpmerge_log_queries';
    $_query = "SELECT `unique_insert_id`, `apply_changes_insert_id` FROM `".$table_log_queries."` WHERE `apply_changes_insert_id` IS NOT NULL AND `is_applied` = '1' AND `is_record_on` = '1' AND `type` = 'query' ".$q_where_part.$q_order_limit_part;
    //file_put_contents(ABSPATH.'/__in_prod_find_pairs.php', "\n $_query",FILE_APPEND);
    
    if($is_all){
        wpmerge_debug::log($_query);
    }
    
    $results = $GLOBALS['wpdb']->get_results($_query, ARRAY_A);
    
    if($results === false){
        throw new wpmerge_exception('find_replace_get_query_error');
    }
    
    $find_replace = array();
    if(empty($results)){
        return $find_replace;
    }
    foreach($results as $row){
        $find_replace[$row['unique_insert_id']] = $row['apply_changes_insert_id'];
    }
    return $find_replace;
}

function wpmerge_find_and_replace_insert_ids($query){
    // following code commented for optimization, these codes are coded for optimization but now these codes takes more time in some cases
    // global $wpmerge_apply_changes_find_and_replace;

    //find all numbers in the query - optimization work, to work 100k+ find and replace pair to search
    $reg = '/[\d]+/m';
    preg_match_all($reg, $query, $matches);
    if(empty($matches[0])){
        //no number found
        return $query;
    }
    $query_numbers = $matches[0];
    $query_numbers = array_unique($query_numbers);
    $query_numbers = array_filter($query_numbers);
    if(empty($query_numbers)){
        //no valid numbers
        return $query;
    }

    $unique_insert_id_min_max = $GLOBALS['WPMERGE_UNIQUE_INSERT_ID_MIN_MAX'];

    if(isset($unique_insert_id_min_max['min_id']) && isset($unique_insert_id_min_max['max_id'])){//internal mysql query optimizer not considering `unique_insert_id` index, because some times number bigger than max bigint is coming. Also this might remove some small numbers, which may help to redeuce the queries
        foreach($query_numbers as $_key => $q_num){
            if($q_num < $unique_insert_id_min_max['min_id'] || $q_num > $unique_insert_id_min_max['max_id']){
                unset($query_numbers[$_key]);
            }
        }
    }

    if(empty($query_numbers)){
        //numbers not in range
        return $query;
    }
    
    global $wpmerge_query_based_find, $wpmerge_query_based_replace,  $wpmerge_query_based_find_and_replace;

    $wpmerge_query_based_find_and_replace = array();
    $wpmerge_query_based_find = array();
    $wpmerge_query_based_replace = array();

    // following code commented for optimization, these codes are coded for optimization but now these codes takes more time in some cases
        // $use_all_find_replace_pairs_threshold = 100000;
    // if($GLOBALS['WPMERGE_ALL_FIND_REPLACE_PAIRS'] === true){//optimization

    //     if(empty($wpmerge_apply_changes_find_and_replace)){
    //         return $query;
    //     }
    //     foreach($query_numbers as $q_num){
    //         if(isset($wpmerge_apply_changes_find_and_replace[$q_num])){
    //             $wpmerge_query_based_find_and_replace[$q_num] = $wpmerge_apply_changes_find_and_replace[$q_num];
    //         }
    //     }

    // }
    // else{//fall back
        //$__query_based_get_pairs_start = microtime(1);
        $wpmerge_query_based_find_and_replace = wpmerge_get_find_replace_pairs_from_numbers_used_in_query($query_numbers);
        //$GLOBALS['__query_based_get_pairs_total'] += microtime(1) - $__query_based_get_pairs_start;
    //}
    
    if(empty($wpmerge_query_based_find_and_replace)){
        //no find and replace pairs found - nothing to replace
        return $query;
    }

    $wpmerge_query_based_find = array_keys($wpmerge_query_based_find_and_replace);
    $wpmerge_query_based_replace = array_values($wpmerge_query_based_find_and_replace);

    return wpmerge_do_replace_insert_ids($query);    
}

function wpmerge_do_replace_insert_ids($query){
    global $wpmerge_query_based_find, $wpmerge_query_based_replace,  $wpmerge_query_based_find_and_replace;
    
    //$GLOBALS['__find_rep_str_replace_start'] = microtime(1);
    $new_str = strtr($query, $wpmerge_query_based_find_and_replace);
    if($new_str !== false && $new_str !== NULL){
        $query = $new_str;
    }
    else{//mostly this won't run, this is fallback not efficient and not reliable when compared to strtr
        $query = str_replace($wpmerge_query_based_find, $wpmerge_query_based_replace, $query);        
    }
    //$GLOBALS['__find_rep_str_replace_total'] += microtime(1) - $GLOBALS['__find_rep_str_replace_start'];
    
    $pattern = '/(?<=^|;|{|})s:(\d+):([\\\\]*)"(.*?)([\\\\]*)";(?=(?:}|s:|a:|b:|d:|i:|N;|O:|C:|$))/ms';
    $query = preg_replace_callback($pattern, 'wpmerge_fix_serialized_str_len_cb',		$query);

	return $query;
}

// The following wpmerge_serialized_data_replace_cb() commented because find and replace runs for each string in serialized data, but the new method will run find and replace once per query so the new method is efficient.
// function wpmerge_serialized_data_replace_cb($matches){
//     global $wpmerge_query_based_find, $wpmerge_query_based_replace,  $wpmerge_query_based_find_and_replace;

// 	if(empty($matches[3])){
// 		return $matches[0];
//     }

//     $old_serial_str = $matches[3];
//     $new_str = strtr($matches[3], $wpmerge_query_based_find_and_replace);
//     if($new_str !== false && $new_str !== NULL){
//         $matches[3] = $new_str;
//     }
//     else{
//         $matches[3] = str_replace($wpmerge_query_based_find, $wpmerge_query_based_replace, $matches[3]);
//     }    
//     $new_serial_str = $matches[3];

//     if( $old_serial_str === $new_serial_str ){
//         return $matches[0];
//     }

//     $new_serial_str_len = strlen(str_replace('\"', '"', $matches[3]));//fix for serial str containing " with escape \ i.e \"
// 	return 's:'.$new_serial_str_len.':'.$matches[2].'"'.$matches[3].''.$matches[4].'";'; 
// }

function wpmerge_fix_serialized_str_len_cb($matches){

    $new_serial_str_len = strlen(str_replace('\"', '"', $matches[3]));//fix for serial str containing " with escape \ i.e \"
	return 's:'.$new_serial_str_len.':'.$matches[2].'"'.$matches[3].''.$matches[4].'";'; 
}

function wpmerge_try_getting_insert_id($last_insert_id, $query_obj){
    /* 
    when to use this: 
        Case 1: INSERT ............ ON DUPLICATE KEY UPDATE .....
        Case 2: INSERT IGNORE ........
        Case 3: INSERT IGNORE ........ ON DUPLICATE KEY UPDATE .....

    if above query caes is not giving a insert_id because the rows already having the same data, so affected rows is 0.    
    */

    //(1) what if a content of query having "ON DUPLICATE KEY UPDATE", yes that is edge case have to do something for that, right now not handled.

    //(2) This workaround doesn't work(DOESN't WORK), if the table is having triggers, especially wpmerge triggers, where LAST_INSERT_ID() will be accessed. We have planned to use it in PROD and in DEV(prod testing) only where currently no triggers used

    //(2a) LAST_INSERT_ID() should not be called after query() and getting results, that causes the issue of getting LAST_INSERT_ID to 0;

    if(wpmerge_is_dev_env() && wpmerge_dev_is_changes_applied_in_dev()){//refer point (2) above
        return $last_insert_id;
    }

    $query = $query_obj->query;

    $is_on_duplicate_query = false;
    $is_insert_ignore_query = false;

    $pattern = '/\bON\s+DUPLICATE\s+KEY\s+UPDATE\b/mi';
    $is_on_duplicate_query = preg_match( $pattern, $query) ;

    $pattern = '/INSERT(?:\s+LOW_PRIORITY|\s+DELAYED|\s+HIGH_PRIORITY)?(?:\s+IGNORE)(?:\s+INTO)/mi';
    $is_insert_ignore_query = preg_match( $pattern, $query);

    if(!$is_on_duplicate_query && !$is_insert_ignore_query){
        return $last_insert_id;
    }

    include_once(WPMERGE_PATH . '/parse_compare_sql.php');
    $query_parse_obj = new wpmerge_parse_insert_queries($query);
    $query_parse_obj->parse_and_standardise();
    $table_name = $query_parse_obj->get_table_name();
    if(empty($table_name)){
        return $last_insert_id;
    }

    //$wpmerge_dev_db_obj = new wpmerge_dev_db();
    //$column_name = $wpmerge_dev_db_obj->get_auto_increment_column($table_name);
    $column_name = wpmerge_get_auto_increment_column($table_name);
    if(empty($column_name)){
        return $last_insert_id;
    }

    $query = rtrim($query, ';');
    if($is_on_duplicate_query){
        $query .=', ';
    }
    else{
        $query .=' ON DUPLICATE KEY UPDATE ';
    }
    $query .=' `'.$column_name.'` = LAST_INSERT_ID(`'.$column_name.'`)';

    //no harm in running the query again
    $query_result = wpmerge_wpdb::query($query);

    if($query_result === false){//query error
        return $last_insert_id;
    }
    $new_last_insert_id = $GLOBALS['wpdb']->insert_id;
    $rows_affected = $GLOBALS['wpdb']->rows_affected;
    return $new_last_insert_id;
}
