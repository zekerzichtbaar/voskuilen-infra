<?php
/**
 * WPMerge
 * Copyright (c) 2018 Revmakx LLC
 * revmakx.com
 */

if(!defined('ABSPATH')){ exit; }

class wpmerge_dev_query_selector{
    private $items_per_page;
    private $total_pages;
    private $current_page;
    private $current_page_http_request_ids = array();
    private $current_page_total_items;
    private $total_items;
    private $order_by = 'id';//hard core
    private $order;
    private $filters = array();

    public function __construct(){
        $this->set_default_browse_options();
    }

    private function set_default_browse_options(){
        $this->items_per_page = 10;
        $this->current_page = 1;
        $this->filters['show_queries'] = 'recorded';
        $this->order = 'DESC';
    }

    public function set_browse_options($options){
        //$options['filters'] - recorded or not, time_from, time_to, query_search
        //$options['pagination']
        if(!empty($options['pagination'])){
            $pagination = $options['pagination'];
            if(isset($pagination['items_per_page']) && $pagination['items_per_page'] > 0 && $pagination['items_per_page'] < 101){
                $this->items_per_page = $pagination['items_per_page'];
            } 
            if(is_numeric($pagination['current_page']) && (int)$pagination['current_page'] > 0){
                $this->current_page = (int)$pagination['current_page'];
            }
            if(isset($pagination['order']) && in_array(strtolower($pagination['order']), array('asc', 'desc'))){
                $this->order = strtoupper($pagination['order']);
            }
        }
        if(!empty($options['filters'])){
            $filters = $options['filters'];
            if(isset($filters['show_queries']) && in_array($filters['show_queries'], array('recorded', 'all'))){
                $this->filters['show_queries'] = $filters['show_queries'];
            } 
        }

    }

    public function prepare_pagination(){

        $is_record_on_sql_join = "";
        if($this->filters['show_queries'] == 'recorded'){
            $is_record_on_sql_join = " AND `is_record_on` = '1'";
        }

        $log_queries_table = $GLOBALS['wpdb']->base_prefix .'wpmerge_log_queries';

        $this->total_items = $GLOBALS['wpdb']->get_var("SELECT COUNT( DISTINCT `http_request_id`) AS `count_unique_rows` FROM `".$log_queries_table."` WHERE  `type` = 'query' ".$is_record_on_sql_join."");
        if($this->total_items === 0 || $this->total_items === '0'){

        }
        elseif($this->total_items > 0){
            $this->total_pages = (int)ceil($this->total_items/$this->items_per_page);

            if($this->current_page < 1 ||  $this->current_page > $this->total_pages){
                $this->current_page = 1;
            }
            $limit = $this->items_per_page;
            $offset  = ($this->current_page - 1) * $this->items_per_page;
            
            $http_request_ids = $GLOBALS['wpdb']->get_results("SELECT `http_request_id`, `logtime` FROM `".$log_queries_table."` WHERE  `type` = 'query' ".$is_record_on_sql_join." GROUP BY `http_request_id` ORDER BY `id` ".$this->order." LIMIT $offset, $limit", ARRAY_A);

            $this->current_page_total_items = count($http_request_ids);

            foreach($http_request_ids as $row){
                //to maintain the order
                $this->current_page_http_request_ids[$row['http_request_id']] = array(
                    'logtime' => $row['logtime'],
                    'http_request_id' => $row['http_request_id'],
                    'queries' => array()
                );
            }
        }
    }

    public function get_total_selected_queries(){
        return wpmerge_dev_get_recorded_queries_count();
    }

    public function get_queries_for_page(){

        $log_queries_table = $GLOBALS['wpdb']->base_prefix .'wpmerge_log_queries';

        $http_request_ids = array_keys($this->current_page_http_request_ids);
        $rows = $GLOBALS['wpdb']->get_results("SELECT `id`, `http_request_id`, `query`, `query_b`, `is_record_on` FROM `".$log_queries_table."` WHERE  `type` = 'query'  AND `http_request_id` IN('".implode("', '", $http_request_ids)."') ORDER BY `id` ASC", ARRAY_A);
        if(empty($rows)){
            //do something
            return;
        }
        $http_calls_details = array();
        foreach($rows as $row){
            $row['query'] = wpmerge_get_query_from_row($row);
            unset($row['query_b']);
            $this->current_page_http_request_ids[$row['http_request_id']]['queries'][] = $row;
        }
        return $this->current_page_http_request_ids;
    }

    public function get_all_page_data($browse_options){
        $response = array();
        $this->set_browse_options($browse_options);
        $this->prepare_pagination();
        $response['pagination']['items_per_page'] = $this->items_per_page;
        $response['pagination']['current_page'] = $this->current_page;
        $response['pagination']['current_page_total_items'] = $this->current_page_total_items;
        $response['pagination']['total_pages'] = $this->total_pages;
        $response['pagination']['total_items'] = $this->total_items;
        $response['pagination']['order'] = $this->order;
        $response['filters'] = $this->filters;
        $response['total_selected_queries'] = $this->get_total_selected_queries();
        $response['page_data'] = $this->get_queries_for_page();
        return $response;
    }

    public function select_and_unselect_queries($queries_selection_state){//array('query_id' => '1'|'0', 'query_id2' => '1'|'0') //return true or false
        if(empty($queries_selection_state)){
            return false;
        }
        $table_log_queries = $GLOBALS['wpdb']->base_prefix .'wpmerge_log_queries';

        foreach($queries_selection_state as $query_id => $state){
            $GLOBALS['wpdb']->update($table_log_queries, array('is_record_on' => $state), array('id' => $query_id));
        }
    }

    function select_and_unselect_all_queries($filters, $is_selected){

    }


}