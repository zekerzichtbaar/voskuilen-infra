<?php
/**
 * WPMerge
 * Copyright (c) 2018 Revmakx LLC
 * revmakx.com
 */

if(!defined('ABSPATH')){ exit; }

//use PHPSQLParser;
//define('WPMERGE_PATH', dirname(__FILE__));//for testing

class wpmerge_compare_insert_queries{
    private $query1;
    private $query2;
    private $query1_parse_obj;
    private $query2_parse_obj;
    function __construct($query1, $query2){
        $this->query1 = $query1;
        $this->query2 = $query2;
    }
    function parse_and_standardise_query_parts(){
        $this->query1_parse_obj = new wpmerge_parse_insert_queries($this->query1);
        $this->query2_parse_obj = new wpmerge_parse_insert_queries($this->query2);
        $this->query1_parse_obj->parse_and_standardise();
        $this->query2_parse_obj->parse_and_standardise();
    }
    function is_table_matched(){
        $query1_table = $this->query1_parse_obj->get_table_name();
        $query2_table = $this->query2_parse_obj->get_table_name();
        //var_dump($query1_table, $query2_table);
        if(empty($query1_table) || empty($query2_table)){
            return false;//throw error Improve Later
        }
        return $query1_table === $query2_table;
    }
    function is_columns_matched(){
        $query1_columns = $this->query1_parse_obj->get_columns();
        $query2_columns = $this->query2_parse_obj->get_columns();
        //var_dump($query1_columns,$query2_columns);
        return $query1_columns === $query2_columns;//will it be enough??, need to check later
        
    }
    function is_values_matched(){//lets not do this

    }
    function values_match_percent(){//lets not do this

    }
}

class wpmerge_parse_insert_queries{
    //assuming insert id query is valid according to mysql
    private $query;
    private $parsed_query;
    private $standardised_parsed_query;
    private $is_parsed = NULL;
    private $table_name;
    private $columns = array();
    private $values = array();
    function __construct($query){
        $this->query = $query;
    }
    function get_parsed_query(){
        return $this->parsed_query;
    }
    function get_table_name(){//need to support table name without wp table prefix Later
        return $this->table_name;
    }
    function get_columns(){
        return $this->columns;
    }
    function parse_and_standardise(){
        //validate insert query in detail before parsing if required LATER
        if(empty($this->query)){
            return false;
        }
        require_once(WPMERGE_PATH . '/lib/PHP-SQL-Parser/vendor/autoload.php');
        //require_once(WPMERGE_PATH . '/lib/PHP-SQL-Parser/src/PHPSQLParser/PHPSQLParser.php');
        $parser = new PHPSQLParser\PHPSQLParser();
        $this->parsed_query = $parser->parse($this->query);
        $this->standardise_parsed_query();
    }
    function standardise_parsed_query(){
        //validating
        if(!isset($this->parsed_query['INSERT'])){
            return false;//not an insert query. Improve handle error LATER
        }
        if(!isset($this->parsed_query['VALUES']) && !isset($this->parsed_query['SET']) && !isset($this->parsed_query['SELECT'])){//used to $this->parsed_query['SELECT'] to find table name, we can't find column name or values using for INSERT...SELECT queries
            return false;//not an expected insert query format. Improve handle error LATER
        }
        $this->find_table_name();
        $this->find_columns();
        //$this->find_values();////its hard to standardise the values if it uses id = id +1 or pass = md5('fffff') better not depend on values
    }
    function find_table_name(){//assuming $this->parsed_query['INSERT'] exists is validated
        $insert_part = $this->parsed_query['INSERT'];
        foreach($insert_part as $key => $value){
            if(isset($value['expr_type']) && $value['expr_type'] === 'table'){
                $this->table_name = $value['no_quotes']['parts'][0];//if required improve validation of accession array LATER
                break;
            }
        }

    }
    function find_columns(){
        if($this->is_values_format()){
            $this->_find_columns_in_values_format();
        }
        elseif($this->is_set_format()){
           $this->_find_columns_in_set_format();
        }
    }
    function _find_columns_in_values_format(){
        $insert_part = $this->parsed_query['INSERT'];
        foreach($insert_part as $key => $value){
            if(isset($value['expr_type']) && $value['expr_type'] === 'column-list'){
                $sub_tree =  $value['sub_tree'];
                break;
            }
        }
        if(empty($sub_tree)){
            return false;
        }
        foreach($sub_tree as $key => $column){
            if(isset($value['expr_type']) && $value['expr_type'] === 'column-list'){
                $this->columns[$key]['column'] =  $column['no_quotes']['parts'][0];//if required improve validation of accession array LATER
            }
        }
    }
    function _find_columns_in_set_format(){
        $set_part = $this->parsed_query['SET'];
        foreach($set_part as $key => $value){
            if(!empty($value['sub_tree'])){
                $sub_tree =  $value['sub_tree'];
                foreach($sub_tree as $sub_tree_key => $sub_tree_part){
                    if(isset($sub_tree_part['expr_type']) && $sub_tree_part['expr_type'] === 'colref'){
                        $this->columns[$key]['column'] =  $sub_tree_part['no_quotes']['parts'][0];//if required improve validation of accession array LATER
                    }
                }
            }
        }
        
    }
    function find_values(){
        //its hard to standardise the values if it uses id = id +1 or pass = md5('fffff') better not depend on values
        if($this->is_values_format()){
            $this->_find_values_in_values_format();
        }
        elseif($this->is_set_format()){
           $this->_find_values_in_set_format();
        }
    }
    function _find_values_in_values_format(){
        $values_part = $this->parsed_query['VALUES'];
        foreach($values_part as $key => $value){
            if(isset($value['expr_type']) && $value['expr_type'] === 'record'){
                $values_data =  $value['data'];
                break;
            }
        }
        foreach($values_data as $order_key => $column_value){
            if(isset($column_value['expr_type']) && $column_value['expr_type'] === 'const'){
                $this->values[$order_key]['value'] =  $column_value['base_expr'];//if required improve validation of accession array LATER
            }
        }
    }
    function _find_values_in_set_format(){

    }
    function is_set_format(){
        return !empty($this->parsed_query['SET']);
    }
    function is_values_format(){
        return !empty($this->parsed_query['VALUES']);
    }
}

// $sql = "INSERT INTO `wp_01_posts` SET `post_author` = md5('heloo'), `post_date` = '2017-12-01'";

// $query1_parse_obj = new wpmerge_parse_insert_queries($sql);
// $query1_parse_obj->parse_and_standardise();
// var_dump($query1_parse_obj->get_table_name());
// var_dump($query1_parse_obj->get_columns());

// $sql1 = "INSERT INTO `wp_01_posts` (`post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_content_filtered`, `post_title`, `post_excerpt`, `post_status`, `post_type`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, `post_parent`, `menu_order`, `post_mime_type`, `guid`) VALUES (1, '2018-04-06 06:32:36', '0000-00-00 00:00:00', '', '', 'Auto Draft', '', 'auto-draft', 'post', 'open', 'open', '', '', '', '', '2018-04-06 06:32:36', '0000-00-00 00:00:00', 0, 0, '', '')";

// $sql2 = "INSERT INTO `wp_01_posts` (`post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_content_filtered`, `post_title`, `post_excerpt`, `post_status`, `post_type`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, `post_parent`, `menu_order`, `post_mime_type`, `guid`) VALUES (1, '2018-04-06 07:55:51', '0000-00-00 00:00:00', '', '', 'Auto Draft', '', 'auto-draft', 'post', 'open', 'open', '', '', '', '', '2018-04-06 07:55:51', '0000-00-00 00:00:00', 0, 0, '', '')";

// $compare_obj = new wpmerge_compare_insert_queries($sql1, $sql2);
// $compare_obj->parse_and_standardise_query_parts();
// var_dump($compare_obj->is_table_matched());
// var_dump($compare_obj->is_columns_matched());



// $sql = "INSERT INTO `wp_options` (`option_name`, `option_value`, `autoload`) VALUES ('_transient_product-transient-version', '1529651433', 'yes') ON DUPLICATE KEY UPDATE `option_name` = VALUES(`option_name`), `option_value` = VALUES(`option_value`), `autoload` = VALUES(`autoload`), `option_id` = `option_id` + 1, `option_id` = LAST_INSERT_ID(`option_id`)";

// $query1_parse_obj = new wpmerge_parse_insert_queries($sql);
// $query1_parse_obj->parse_and_standardise();
// var_dump($query1_parse_obj->get_table_name());
// var_dump($query1_parse_obj->get_columns());
// var_dump($query1_parse_obj->get_parsed_query());

// ini_set('xdebug.var_display_max_depth', 5);
// ini_set('xdebug.var_display_max_children', 256);
// ini_set('xdebug.var_display_max_data', 1024);




// $sql = "INSERT INTO `wp_options` (`option_name`, `option_value`, `autoload`) VALUES ('_transient_product-transient-version', '1529651433', 'yes') ON DUPLICATE KEY UPDATE `option_name` = VALUES(`option_name`), `option_value` = VALUES(`option_value`), `autoload` = VALUES(`autoload`), `option_id` = `option_id` + 1, `option_id` = LAST_INSERT_ID(`option_id`)";


// $sql = "INSERT INTO `wp_options` SET `option_name` ='_transient_product-transient-version', `option_value` = '1529651433', `autoload` = 'yes' ON DUPLICATE KEY UPDATE `option_name` = VALUES(`option_name`), `option_value` = VALUES(`option_value`), `autoload` = VALUES(`autoload`), `option_id` = `option_id` + 1, `option_id` = LAST_INSERT_ID(`option_id`);";

// $sql = "INSERT INTO t1 (a, b)
// SELECT * FROM
//   (SELECT c, d FROM t2
//    UNION
//    SELECT e, f FROM t3) AS dt
// ON DUPLICATE KEY UPDATE b = b + c;";

// $sql = "INSERT INTO tasks(subject,start_date,end_date,description) VALUES (111, 222, 333), ('Task 1','2010-01-01','2010-01-02','Description 1'), ('Task 2','2010-01-01','2010-01-02','Description 2 ON'), ('Task 3','2010-01-01','2010-01-02','Description 3 SELECT * FROM taaaab where 1') ON DUPLICATE KEY UPDATE b = b + c;;";

// $sql = "INSERT INTO `wp_01_posts` (`post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_content_filtered`, `post_title`, `post_excerpt`, `post_status`, `post_type`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, `post_parent`, `menu_order`, `post_mime_type`, `guid`) VALUES (1, '2018-04-06 06:32:36', '0000-00-00 00:00:00', '', '', 'Auto Draft', '', 'auto-draft', 'post', 'open', 'open', '', '', '', '', '2018-04-06 06:32:36', '0000-00-00 00:00:00', 0, 0, '', '')";

// var_dump($sql);

// $query1_parse_obj = new wpmerge_parse_insert_queries($sql);
// $query1_parse_obj->parse_and_standardise();
// var_dump($query1_parse_obj->get_table_name());
// var_dump($query1_parse_obj->get_columns());
// var_dump($query1_parse_obj->get_parsed_query());
// var_dump('is_set_format',$query1_parse_obj->is_set_format());
// var_dump('is_values_format', $query1_parse_obj->is_values_format());