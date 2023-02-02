<?php

if(!defined('ABSPATH')){ exit; }

class wpmerge_replace_db_links{
    private $current_offset;
    private $eof;

	public function __construct(){
		$this->wpdb = $GLOBALS['wpdb'];
	}

	public function replace_uri($old_url, $new_url, $old_file_path, $new_file_path, $table_prefix, $table_details, $is_multisite_subdomain_install=false){

		//$new_url -> getting from get_site_url(), which should give the data with proper protocol

		$old_url = untrailingslashit(trim($old_url));
		$new_url = untrailingslashit(trim($new_url));
		$old_file_path = untrailingslashit(trim($old_file_path));
		$new_file_path = untrailingslashit(trim($new_file_path));

		$old_url = wpmerge_check_and_protocol($old_url);
		$new_url = wpmerge_check_and_protocol($new_url);

		$replace_list = array();

		/*

		http://example.com to https://example.com/staging

		(1) with and without www search and replace with placeholder

		//example.com					|		
		//www.example.com				|	to	|	//|---NEW----URL---|

		urlencode(//example.com)		|	
		urlencode(//www.example.com)	|	to |	urlencode(//|---NEW----URL---|)

		json_encode(//example.com)		|	
		json_encode(//www.example.com)	|	to |	json_encode(//|---NEW----URL---|)

		(2) If new url is https, then http to https or if new url is http then https to http

		http://|---NEW----URL---|				|	to	|	https://|---NEW----URL---|

		urlencode(http://|---NEW----URL---|)	|	to |	urlencode(https://|---NEW----URL---|)

		json_encode(http://|---NEW----URL---|)	|	to |	json_encode(https://|---NEW----URL---|)

		(3) with out protocol, change the placeholder to new url

		//|---NEW----URL---|		|	to	|	//example.com/staging

		urlencode(//|---NEW----URL---|)		|	to	|	urlencode
			(//example.com/staging)

		json_encode(//|---NEW----URL---|)		|	to	|	json_encode(//example.com/staging)

		*/
		
		//old urls
		$old_relative_url_without_www = wpmerge_add_protocal_to_url($old_url, $protocal = '//', $add_www = false);
		$old_relative_url_with_www = wpmerge_add_protocal_to_url($old_url, $protocal = '//', $add_www = true);

		$old_relative_url_without_www_urlencoded = urlencode($old_relative_url_without_www);
		$old_relative_url_with_www_urlencoded  = urlencode($old_relative_url_with_www);

		$old_relative_url_without_www_json = str_replace('"', "", json_encode($old_relative_url_without_www));
		$old_relative_url_with_www_json = str_replace('"', "", json_encode($old_relative_url_with_www));


		//placeholder urls
		$placeholder_relative_url = '//|---NEW----URL---|';//there is advantage of using '//' in front of this placeholder. Which get converted to '\/\/' in json so it will avoid double replace

		$placeholder_relative_url_urlencoded = urlencode($placeholder_relative_url);

		$placeholder_relative_url_json  = str_replace('"', "", json_encode($placeholder_relative_url));


		//new url s
		$new_relative_url = str_ireplace(array('http://', 'https://'), '//', $new_url);

		$new_relative_url_urlencoded = urlencode($new_relative_url);

		$new_relative_url_json  = str_replace('"', "", json_encode($new_relative_url));

	if($is_multisite_subdomain_install){
		//wildcard subdomain handling - only letters, alphabets and (-) hypen is allowed in domain based on this assumtion following is done for subdomain capturing in ($1)
		$old_url_starts_with_domain = wpmerge_remove_protocal_from_url($old_url);
		$placeholder_url_starts_with_domain = '|---DOM--URL--PATH---|';
		$new_url_starts_with_domain = wpmerge_remove_protocal_from_url($new_url);

		$old_relative_url_wild_subdomain_search = preg_quote('//', '/').'(.+\.)'.preg_quote($old_url_starts_with_domain, '/');
		$placeholder_relative_url_wild_subdomain_replace = '//$1'.$placeholder_url_starts_with_domain;
		$placeholder_relative_url_wild_subdomain_search = preg_quote('//', '/').'(.+\.)'.preg_quote($placeholder_url_starts_with_domain, '/');
		$new_relative_url_wild_subdomain_replace = '//$1'.$new_url_starts_with_domain;


		$old_relative_url_wild_subdomain_urlencode_search = preg_quote(urlencode('//'), '/').'(.+\.)'.preg_quote(urlencode($old_url_starts_with_domain), '/');
		$placeholder_relative_url_wild_subdomain_urlencode_replace = urlencode('//').'$1'.urlencode($placeholder_url_starts_with_domain);
		$placeholder_relative_url_wild_subdomain_urlencode_search = preg_quote(urlencode('//'), '/').'(.+\.)'.preg_quote(urlencode($placeholder_url_starts_with_domain), '/');
		$new_relative_url_wild_subdomain_urlencode_replace = urlencode('//').'$1'.urlencode($new_url_starts_with_domain);

		$old_relative_url_wild_subdomain_json_search = preg_quote(str_replace('"', "", json_encode('//')), '/').'(.+\.)'.preg_quote(str_replace('"', "", json_encode($old_url_starts_with_domain)), '/');
		$placeholder_relative_url_wild_subdomain_json_replace = str_replace('"', "", json_encode('//')).'$1'.str_replace('"', "", json_encode($placeholder_url_starts_with_domain));
		$placeholder_relative_url_wild_subdomain_json_search = preg_quote(str_replace('"', "", json_encode('//')), '/').'(.+\.)'.preg_quote(str_replace('"', "", json_encode($placeholder_url_starts_with_domain)), '/');
		$new_relative_url_wild_subdomain_json_replace = str_replace('"', "", json_encode('//')).'$1'.str_replace('"', "", json_encode($new_url_starts_with_domain));
	}

		//protocols

		if(stristr($new_url, 'https:')){
			$from_protocol = 'http:';//even old url is https, it better to clean away http protocols
			$to_protocol = 'https:';			
		}
		else{
			$from_protocol = 'https:';//read above comment - similar
			$to_protocol = 'http:';
		}

		$from_protocol_urlencode = urlencode($from_protocol);
		$to_protocol_urlencode = urlencode($to_protocol);

		//no need to encode http: and https: but still ;)
		$from_protocol_json  = str_replace('"', "", json_encode($from_protocol));
		$to_protocol_json  = str_replace('"', "", json_encode($to_protocol));

		//placeholders for path
		$placeholder_path = '|---NEW----PATH---|';
		$placeholder_path_urlencode = '|--URLENCODE--NEW----PATH---|';
		$placeholder_path_json = '|--JSON--NEW----PATH---|';


		array_push($replace_list,
			//old url to placeholder url
            array(
                'search'  => $old_relative_url_without_www,
                'replace' => $placeholder_relative_url
            ),
            array(
                'search'  => $old_relative_url_with_www,
                'replace' => $placeholder_relative_url
			),
			array(
                'search'  => $old_relative_url_without_www_urlencoded,
                'replace' => $placeholder_relative_url_urlencoded
            ),
            array(
                'search'  => $old_relative_url_with_www_urlencoded,
                'replace' => $placeholder_relative_url_urlencoded
			),
			array(
                'search'  => $old_relative_url_without_www_json,
                'replace' => $placeholder_relative_url_json
            ),
            array(
                'search'  => $old_relative_url_with_www_json,
                'replace' => $placeholder_relative_url_json
			)
		);

	if($is_multisite_subdomain_install){
		array_push($replace_list,
			//calling wild card subdomain replace after www. => placeholder is better is required
			array(
				'search'  => $old_relative_url_wild_subdomain_search,
				'replace' => $placeholder_relative_url_wild_subdomain_replace,
				'method' => 'preg_replace'
			),
			array(
				'search'  => $old_relative_url_wild_subdomain_urlencode_search,
				'replace' => $placeholder_relative_url_wild_subdomain_urlencode_replace,
				'method' => 'preg_replace'
			),
			array(
				'search'  => $old_relative_url_wild_subdomain_json_search,
				'replace' => $placeholder_relative_url_wild_subdomain_json_replace,
				'method' => 'preg_replace'
			)
		);
	}

		array_push($replace_list,
			//file paths
			array(//json replace should fist happen before plain path replace to avoid subset issue('/public_html' is subset of its json version '\/public_html' it happen when it is single folder)
                'search'  => str_replace('"', "", json_encode($old_file_path)),
                'replace' => str_replace('"', "", json_encode($placeholder_path_json))
			),
					
            array(
                'search'  => $old_file_path,
                'replace' => $placeholder_path
			),
			
			array(
                'search'  => urlencode($old_file_path),
                'replace' => urlencode($placeholder_path_urlencode)
            ),

			//---------------------Start replacing placeholers----------------
			array(
                'search'  => str_replace('"', "", json_encode($placeholder_path_json)),
                'replace' => str_replace('"', "", json_encode($new_file_path))
			),
			array(
                'search'  => $placeholder_path,
                'replace' => $new_file_path
			),
			
			array(
                'search'  => urlencode($placeholder_path_urlencode),
                'replace' => urlencode($new_file_path)
            ),

            array(//exceptional case
                'search'  => rtrim(wpmerge_unset_safe_path($old_file_path), '\\'),
				'replace' => rtrim($new_file_path, '/')
			),

			//from protocol placeholder url (to) to protocol placeholder url 
			array(
                'search'  => $from_protocol . $placeholder_relative_url,
                'replace' => $to_protocol . $placeholder_relative_url
			),
			array(
                'search'  => $from_protocol_urlencode . $placeholder_relative_url_urlencoded,
                'replace' => $to_protocol_urlencode . $placeholder_relative_url_urlencoded
            ),
            array(
                'search'  => $from_protocol_json. $placeholder_relative_url_json,
                'replace' => $to_protocol_json . $placeholder_relative_url_json
			)
		);

	if($is_multisite_subdomain_install){
		array_push($replace_list,
			array(
				'search'  => preg_quote($from_protocol, '/'). $placeholder_relative_url_wild_subdomain_search,
				'replace' => $to_protocol . $placeholder_relative_url_wild_subdomain_replace,
				'method' => 'preg_replace'
			),
			array(
				'search'  => preg_quote($from_protocol_urlencode, '/'). $placeholder_relative_url_wild_subdomain_urlencode_search,
				'replace' => $to_protocol_urlencode . $placeholder_relative_url_wild_subdomain_urlencode_replace,
				'method' => 'preg_replace'
			),
			array(
				'search'  => preg_quote($from_protocol_json, '/'). $placeholder_relative_url_wild_subdomain_json_search,
				'replace' => $to_protocol_json . $placeholder_relative_url_wild_subdomain_json_replace,
				'method' => 'preg_replace'
			)
		);
	}
	
			//placeholder url to new url
		array_push($replace_list,
			array(
                'search'  => $placeholder_relative_url,
                'replace' => $new_relative_url
			),
			array(
                'search'  => $placeholder_relative_url_urlencoded,
                'replace' => $new_relative_url_urlencoded
            ),
            array(
                'search'  => $placeholder_relative_url_json,
				'replace' => $new_relative_url_json
			)
		);

	if($is_multisite_subdomain_install){
		array_push($replace_list,
			array(
				'search'  => $placeholder_relative_url_wild_subdomain_search,
				'replace' => $new_relative_url_wild_subdomain_replace,
				'method' => 'preg_replace'
			),
			array(
				'search'  => $placeholder_relative_url_wild_subdomain_urlencode_search,
				'replace' => $new_relative_url_wild_subdomain_urlencode_replace,
				'method' => 'preg_replace'
			),
			array(
				'search'  => $placeholder_relative_url_wild_subdomain_json_search,
				'replace' => $new_relative_url_wild_subdomain_json_replace,
				'method' => 'preg_replace'
			)
		);
	}

        array_walk_recursive($replace_list, 'wpmerge_dupx_array_rtrim');

        $table_name = $table_details['name'];
        $this->current_offset = $table_details['current_offset'];        
		$this->eof = false;
		
        $table_name = $table_details['name'];
		wpmerge_debug::log(array($table_details),'-----------replace urls before----------------');
		// $GLOBALS['WPMERGE_UPDATE_DATA_REPLACE_TIME_TAKEN'] = 0;
		// $GLOBALS['WPMERGE_UPDATE_DATA_REPLACE_COUNTER'] = 0;
		// $GLOBALS['WPMERGE_recursive_unserialize_replace_TIME_TAKEN'] = 0;
		// $GLOBALS['WPMERGE_preg_replace_TIME_TAKEN'] = 0;
		// $GLOBALS['WPMERGE_preg_replace_TIME_TAKEN2'] = 0;
		// $GLOBALS['WPMERGE_str_replace_TIME_TAKEN'] = 0;
		// $GLOBALS['WPMERGE_replace_unserialize_TIME_TAKEN'] = 0;
		// $GLOBALS['WPMERGE_replace_serialize_TIME_TAKEN'] = 0;
        
        $this->replace_old_url_depth($replace_list, array($table_name), true);

        $table_details['eof'] = $this->eof;
		$table_details['current_offset'] = $this->current_offset;
		
		// wpmerge_debug::log(array($table_details),'-----------replace urls after----------------');
		// wpmerge_debug::log($GLOBALS['WPMERGE_UPDATE_DATA_REPLACE_TIME_TAKEN'],'-----------WPMERGE_UPDATE_DATA_REPLACE_TIME_TAKEN----------------');
		// wpmerge_debug::log($GLOBALS['WPMERGE_UPDATE_DATA_REPLACE_COUNTER'],'-----------WPMERGE_UPDATE_DATA_REPLACE_COUNTER----------------');
		// wpmerge_debug::log($GLOBALS['WPMERGE_recursive_unserialize_replace_TIME_TAKEN'],'-----------WPMERGE_recursive_unserialize_replace_TIME_TAKEN----------------');
		// wpmerge_debug::log($GLOBALS['WPMERGE_preg_replace_TIME_TAKEN'],'-----------WPMERGE_preg_replace_TIME_TAKEN----------------');
		// wpmerge_debug::log($GLOBALS['WPMERGE_preg_replace_TIME_TAKEN2'],'-----------WPMERGE_preg_replace_TIME_TAKEN2----------------');
		// wpmerge_debug::log($GLOBALS['WPMERGE_str_replace_TIME_TAKEN'],'-----------WPMERGE_str_replace_TIME_TAKEN----------------');
		// wpmerge_debug::log($GLOBALS['WPMERGE_replace_unserialize_TIME_TAKEN'],'-----------WPMERGE_replace_unserialize_TIME_TAKEN----------------');
		// wpmerge_debug::log($GLOBALS['WPMERGE_replace_serialize_TIME_TAKEN'],'-----------WPMERGE_replace_serialize_TIME_TAKEN----------------');

        return $table_details;

	}

    //one table will be sent $tables array
	private function replace_old_url_depth($list = array(), $tables = array(), $fullsearch = false) {
		$report = array(
			'scan_tables' => 0,
			'scan_rows'   => 0,
			'scan_cells'  => 0,
			'updt_tables' => 0,
			'updt_rows'   => 0,
			'updt_cells'  => 0,
			'errsql'      => array(),
			'errser'      => array(),
			'errkey'      => array(),
			'errsql_sum'  => 0,
			'errser_sum'  => 0,
			'errkey_sum'  => 0,
			'time'        => '',
			'err_all'     => 0
		);

		//$walk_function = @create_function('&$str', '$str = "`$str`";'); //create_function not supported in php8
		$walk_function = function(&$str){
			$str = "`$str`";
		};


		if (is_array($tables) && !empty($tables)) {

			foreach ($tables as $table) {
				$report['scan_tables']++;
				$columns = array();
				$fields = $this->wpdb->get_results('DESCRIBE ' . "`$table`"); //modified

				foreach ($fields as $key => $column) {
					$columns[$column->Field] = $column->Key == 'PRI' ? true : false;
				}

				$row_count =  $this->wpdb->get_var("SELECT COUNT(*) FROM `{$table}`");


				if ($row_count == 0) {
					$this->eof = true;
					continue;
				}

				// $page_size = 500; //25000; - 25000 commented because if more row having url to replace it will take more time to complete a page. so to avoid time going above the time limit.
				// $offset = ($page_size + 1);
				//$pages = (int)ceil($row_count / $page_size);
				$colList = '*';
				$colMsg  = '*';

				if (! $fullsearch) {
					$colList = $this->get_text_columns($table);
					if ($colList != null && is_array($colList)) {
						array_walk($colList, $walk_function);
						$colList = implode(',', $colList);
					}
					$colMsg = (empty($colList)) ? '*' : '~';
				}

				if (empty($colList)) {
					$this->eof = true;
					continue;
				}

				//$start_page = $this->current_page;
				$row_offset = $this->current_offset;

				$limit = 25000;

				//Paged Records
				// for ($page = $start_page; $page < $pages; $page++) {
				// 	$current_row = 0;
				// 	$start = $page * $page_size;
				// 	$end   = $start + $page_size;
				// 	$sql = sprintf("SELECT {$colList} FROM `%s` LIMIT %d, %d", $table, $start, $offset);
				// 	$data  = $this->wpdb->get_results($sql);
				// 	if (empty($data)){
				// 		$scan_count = ($row_count < $end) ? $row_count : $end;
				// 	}
				$upd = false;
				while($row_count > $row_offset){

					$sql = sprintf("SELECT {$colList} FROM `%s` LIMIT %d OFFSET %d", $table, $limit, $row_offset);

					$select_args = array();
					$select_args['columns'] = $colList;//currently class wpmerge_select_by_memory_limit, only * and `table_name`.`column_name1` supported
					$select_args['table'] = $table;
					$select_args['limit'] = $limit;
					$select_args['offset'] = $row_offset;
					$select_args['total_rows'] = $row_count;

					$select_by_memory_limit_obj = new wpmerge_select_by_memory_limit($select_args);
					$data = $select_by_memory_limit_obj->process_and_get_results();

				foreach ($data as $key => $row) {

						$report['scan_rows']++;
						$current_row = $row_offset + 1;
						$upd_col = array();
						$upd_sql = array();
						$where_sql = array();
						$upd = false;
						$serial_err = 0;

						foreach ($columns as $column => $primary_key) {
							$report['scan_cells']++;
							$edited_data = $data_to_fix = $row->$column;
							$base64coverted = false;
							$txt_found = false;

							if ($primary_key) {
								$where_sql[] = $column . ' = "' . $this->wpdb->_real_escape($data_to_fix) . '"';
							}

							if (!empty($row->$column) && !is_numeric($row->$column)) {
								//Base 64 detection
								if (base64_decode($row->$column, true)) {
									$decoded = base64_decode($row->$column, true);
									if ($this->is_serialized($decoded)) {
										$edited_data = $decoded;
										$base64coverted = true;
									}
								}

								//Skip table cell if match not found
								foreach ($list as $item) {
									if(isset($item['method']) && $item['method'] === 'preg_replace'){
										//$__update_data_start_time = microtime(1);
										$___temp = preg_match('/'.$item['search'].'/U', $edited_data);
										//$GLOBALS['WPMERGE_preg_replace_TIME_TAKEN2'] += microtime(1) - $__update_data_start_time;
										if($___temp ){
											$txt_found = true;
											break;
										}
									}
									elseif (strpos($edited_data, $item['search']) !== false) {
										$txt_found = true;
										break;
									}
								}
								//following if commented for all data goes under fix_serial_string check
								// if (! $txt_found) {
								// 	continue;
								// }
							//$__update_data_start_time = microtime(1);
								//Replace logic - level 1: simple check on any string or serlized strings
								if ( $txt_found) {
									$edited_data = $this->recursive_unserialize_replace($list, $edited_data, false, (isset($item['method']) ? $item['method'] : false));
								}

							//$GLOBALS['WPMERGE_recursive_unserialize_replace_TIME_TAKEN'] += microtime(1) - $__update_data_start_time;
								//Replace logic - level 2: repair serilized strings that have become broken
								$serial_check = $this->fix_serial_string($edited_data);
								if ($serial_check['fixed']) {
									$edited_data = $serial_check['data'];
								} else if ($serial_check['tried'] && !$serial_check['fixed']) {
									$serial_err++;
								}
							}

							//Change was made
							if ($edited_data != $data_to_fix || $serial_err > 0) {
								$report['updt_cells']++;
								//Base 64 encode
								if ($base64coverted) {
									$edited_data = base64_encode($edited_data);
								}
								$upd_col[] = $column;
								$upd_sql[] = $column . ' = "' . $this->wpdb->_real_escape($edited_data) . '"';
								$upd = true;
							}
						}

						if ($upd && !empty($where_sql)) {

							$sql = "UPDATE `{$table}` SET " . implode(', ', $upd_sql) . ' WHERE ' . implode(' AND ', array_filter($where_sql));
							//$__update_data_start_time = microtime(1);
							$result = $this->wpdb->query($sql);
							//$GLOBALS['WPMERGE_UPDATE_DATA_REPLACE_TIME_TAKEN'] += microtime(1) - $__update_data_start_time;
							//$GLOBALS['WPMERGE_UPDATE_DATA_REPLACE_COUNTER']++;

							if ($result) {
								if ($serial_err > 0) {
									$report['errser'][] = "SELECT " . implode(', ', $upd_col) . " FROM `{$table}`  WHERE " . implode(' AND ', array_filter($where_sql)) . ';';
								}
								$report['updt_rows']++;
							}
						} elseif ($upd) {
							$report['errkey'][] = sprintf("Row [%s] on Table [%s] requires a manual update.", $current_row, $table);
						}

						$row_offset++;

						if(wpmerge_is_time_limit_exceeded()){
							$this->current_offset = $row_offset;
							$this->eof = false;
							return;
						}
                    }

                    if( $row_count <= $row_offset ){
                        //table is completed, page for loop going to complete
						$this->eof = true;
						$this->current_offset = 0;
                        return;
                    }

					if(wpmerge_is_time_limit_exceeded()){
                        $this->current_offset = $row_offset;
                        $this->eof = false;
                        return;
					}

				}//END OF while($row_count > $row_offset)

				if ($upd) {
					$report['updt_tables']++;
				}
            }//END OF foreach ($tables as $table) 
            
		}

		$report['errsql_sum'] = empty($report['errsql']) ? 0 : count($report['errsql']);
		$report['errser_sum'] = empty($report['errser']) ? 0 : count($report['errser']);
		$report['errkey_sum'] = empty($report['errkey']) ? 0 : count($report['errkey']);
		$report['err_all']    = $report['errsql_sum'] + $report['errser_sum'] + $report['errkey_sum'];
		return $report;
	}

	private function get_text_columns($table) {

		$type_where  = "type NOT LIKE 'tinyint%' AND ";
		$type_where .= "type NOT LIKE 'smallint%' AND ";
		$type_where .= "type NOT LIKE 'mediumint%' AND ";
		$type_where .= "type NOT LIKE 'int%' AND ";
		$type_where .= "type NOT LIKE 'bigint%' AND ";
		$type_where .= "type NOT LIKE 'float%' AND ";
		$type_where .= "type NOT LIKE 'double%' AND ";
		$type_where .= "type NOT LIKE 'decimal%' AND ";
		$type_where .= "type NOT LIKE 'numeric%' AND ";
		$type_where .= "type NOT LIKE 'date%' AND ";
		$type_where .= "type NOT LIKE 'time%' AND ";
		$type_where .= "type NOT LIKE 'year%' ";

		$result = $this->wpdb->get_results("SHOW COLUMNS FROM `{$table}` WHERE {$type_where}", ARRAY_N);
		if (empty($result)) {
			return null;
		}
		$fields = array();
		if (count($result) > 0 ) {
			foreach ($result as $key => $row) {
				$fields[] = $row['Field'];
			}
		}

		$result =  $this->wpdb->get_results("SHOW INDEX FROM `{$table}`", ARRAY_N);
		if (count($result) > 0) {
			foreach ($result as $key => $row) {
				$fields[] = $row['Column_name'];
			}
		}

		return (count($fields) > 0) ? $fields : null;
	}

	private function initiate_anonymous_classes_for_serialize_data($str){
		//make sure the input $str string is serialized. 
		$reg = '/O:[\d]+:"([a-zA-Z0-9_\\\\]*)"/m'; //it can deduct namespace classes too
		preg_match_all($reg, $str, $matches, PREG_SET_ORDER, 0);
		if (empty($matches)) {
			return;
		}
		foreach ($matches as $match) {
			if(!empty($match[1])){
				$required_class = $match[1];
				if(!class_exists($required_class)){
					$new_class = new class{}; //create an anonymous class
					$new_class_name = get_class($new_class); //get the name PHP assigns the anonymous class
					class_alias($new_class_name, $required_class); //alias the anonymous class with your class name
				}
			}
		}
	}

	private function recursive_unserialize_replace($from_to_list=array(), $data = '', $serialised = false, $method=false) {
		try {
			//$__update_data_start_time = microtime(1);
			//$unserialized = @unserialize($data);
			//$GLOBALS['WPMERGE_replace_unserialize_TIME_TAKEN'] += microtime(1) - $__update_data_start_time;

			if (is_string($data) && ($unserialized = @unserialize($data)) !== false) {
				//once confirmed it is serialzed data now try to initiate anonymous classes if found in serialized data
				$this->initiate_anonymous_classes_for_serialize_data($data);
				$unserialized = @unserialize($data);

				$data = $this->recursive_unserialize_replace($from_to_list, $unserialized, true, $method);
			} else if (is_array($data)) {
				$_tmp = array();
				foreach ($data as $key => $value) {
					$_tmp[$key] = $this->recursive_unserialize_replace($from_to_list, $value, false, $method);
				}
				$data = $_tmp;
				unset($_tmp);
			} else if (is_object($data)) {

				$_tmp = $data;
				$props = get_object_vars( $data );
				foreach ($props as $key => $value) {
					//If some objects has \0 in the key it creates the fatal error so skip such contents
					if (strstr($key, "\0") !== false ) {
						continue;
					}
					$_tmp->$key = $this->recursive_unserialize_replace( $from_to_list, $value, false, $method );
				}
				$data = $_tmp;
				unset($_tmp);
			} else {
				if (is_string($data)) {
					foreach ($from_to_list as $item) {
						//$item['search'], $item['replace']
						if(isset($item['method']) && $item['method'] === 'preg_replace'){
							//$__update_data_start_time = microtime(1);
							
							$data = preg_replace('/'.$item['search'].'/U', $item['replace'], $data);
							//$GLOBALS['WPMERGE_preg_replace_TIME_TAKEN'] += microtime(1) - $__update_data_start_time;
						}
						else{
							//$__update_data_start_time = microtime(1);
							$data = str_replace($item['search'], $item['replace'], $data);
							//$GLOBALS['WPMERGE_str_replace_TIME_TAKEN'] += microtime(1) - $__update_data_start_time;
						}
					}
				}
			}

			if ($serialised){
				//$__update_data_start_time = microtime(1);
				$___return_tets = serialize($data);
				//$GLOBALS['WPMERGE_replace_serialize_TIME_TAKEN'] += microtime(1) - $__update_data_start_time;
				return $___return_tets;
			}

		} catch (Exception $error){

		}
		return $data;
	}

	private function fix_serial_string($data) {
		$result = array('data' => $data, 'fixed' => false, 'tried' => false);
		if (preg_match("/s:[0-9]+:/", $data)) {
			if (!$this->is_serialized($data)) {
				$regex = '!(?<=^|;|{|})s:(\d+)(?=:"(.*?)";(?:}|s:|a:|b:|d:|i:|N;|O:|C:|$))!ms';
				$serial_string = preg_match('/^s:[0-9]+:"(.*$)/s', trim($data), $matches);
				//Nested serial string
				if ($serial_string) {
					$inner = preg_replace_callback($regex, array($this, 'fix_string_callback'), rtrim($matches[1], '";'));
					$serialized_fixed = 's:' . strlen($inner) . ':"' . $inner . '";';
				} else {
					$serialized_fixed = preg_replace_callback($regex, array($this, 'fix_string_callback'), $data);
				}
				if ($this->is_serialized($serialized_fixed)) {
					$result['data'] = $serialized_fixed;
					$result['fixed'] = true;
				}
				$result['tried'] = true;
			}
		}
		return $result;
	}

	public function fix_string_callback($matches) {
		return 's:'.strlen(($matches[2]));
	}

	private function is_serialized($data){
		$test = @unserialize($data);
		return ($test !== false || $test === 'b:0;') ? true : false;
	}

	public function update_site_and_home_url($prefix, $url){
		$result = $this->wpdb->query(
			$this->wpdb->prepare(
				'UPDATE ' . $prefix . 'options SET option_value = %s WHERE option_name = \'siteurl\' OR option_name = \'home\'',
				$url
			)
		);

		return $result;
	}

	public function update_user_roles($new_prefix, $old_prefix){
		$result = $this->wpdb->query(
			"UPDATE  ". $new_prefix . "options SET option_name = '" . $new_prefix . "user_roles' WHERE option_name = '" . $old_prefix . "user_roles' LIMIT 1"
		);

		if ($result === false) {
			$error = isset($this->wpdb->error) ? $this->wpdb->error : '';
			return ;
		}

	}

	//replace table prefix in meta_keys
	public function replace_prefix($new_prefix, $old_prefix){
		$usermeta_sql = $this->wpdb->prepare(
				'UPDATE ' . $new_prefix . 'usermeta SET meta_key = REPLACE(meta_key, %s, %s) WHERE meta_key LIKE %s',
				$old_prefix,
				$new_prefix,
				$old_prefix . '_%'
			);

		$result_usermeta = $this->wpdb->query( $usermeta_sql );

		$options_sql = $this->wpdb->prepare(
				'UPDATE ' . $new_prefix . 'options SET option_name = REPLACE(option_name, %s, %s) WHERE option_name LIKE %s',
				$old_prefix,
				$new_prefix,
				$old_prefix . '_%'
			);

		$result_options = $this->wpdb->query( $options_sql );

		if ($result_options === false || $result_usermeta === false) {
			return ;
		}

	}

	public function multi_site_db_changes($new_prefix, $new_site_url, $old_site_url){
		//this functions should run only once. If run multiple times then subset issue can happen

		wpmerge_debug::log(func_get_args(), '--------multi_site_db_changes----------');

		$new_site_url_parts = parse_url($new_site_url);
		$old_site_url_parts = parse_url($old_site_url);
		$new_site_host = $new_site_url_parts['host'];
		$old_site_host = $old_site_url_parts['host'];
		$new_site_path = parse_url($new_site_url, PHP_URL_PATH);
		$old_site_path = parse_url($old_site_url, PHP_URL_PATH);

		//Force a path
		$new_site_path = (empty($new_site_path) || ($new_site_path == '/')) ? '/'  : rtrim($new_site_path, '/') . '/';
		$old_site_path = (empty($old_site_path) || ($old_site_path == '/')) ? '/'  : rtrim($old_site_path, '/') . '/';


		if(wpmerge_is_table_exist($new_prefix . 'site')){
			//update site table
			$result = $this->wpdb->query(
					"UPDATE " . $new_prefix . "site SET path = REPLACE(path, '".$old_site_path."', '".$new_site_path."'), domain = REPLACE(domain, '".$old_site_host."', '".$new_site_host."')"
			);

			wpmerge_debug::log($result, '--------multi_site_db_changes $result----------');
			if ($result === false ) {
				$error = isset($this->wpdb->error) ? $this->wpdb->error : '';
				wpmerge_debug::log('modifying site table is failed. ' . $error, '--------FAILED----------');
			} else {
				wpmerge_debug::log('modifying site table is successfully done.', '--------SUCCESS----------');
			}
		}

		if(wpmerge_is_table_exist($new_prefix . 'blogs')){
			//update blogs table
			$sql2 = "UPDATE " . $new_prefix . "blogs SET path = REPLACE(path, '".$old_site_path."', '".$new_site_path."'), domain = REPLACE(domain, '".$old_site_host."', '".$new_site_host."')";
			wpmerge_debug::log($sql2, '--------$sql2----------');
			$result = $this->wpdb->query($sql2);

			if ( $result === false ) {
				$error = isset($this->wpdb->error) ? $this->wpdb->error : '';
				wpmerge_debug::log('modifying blogs table is failed. ' . $error, '--------FAILED----------');
			} else {
				wpmerge_debug::log('modifying blogs table is successfully done.', '--------SUCCESS----------');
			}
		}

		//update users_meta table
		$sql3 = "UPDATE " . $new_prefix . "usermeta SET meta_value = REPLACE(meta_value, '".$old_site_host."', '".$new_site_host."') WHERE meta_key = 'source_domain'";
		wpmerge_debug::log($sql3, '--------$sql3----------');
		$result = $this->wpdb->query($sql3);

		if ( $result === false ) {
			$error = isset($this->wpdb->error) ? $this->wpdb->error : '';
			wpmerge_debug::log('modifying usermeta table is failed. ' . $error, '--------FAILED----------');
		} else {
			wpmerge_debug::log('modifying usermeta table is successfully done.', '--------SUCCESS----------');
		}

	}
}


function wpmerge_dupx_array_rtrim(&$value) {
    $value = rtrim($value, '\/');
}

function wpmerge_remove_protocal_from_url($url){
	$url = preg_replace("(^https?://?www.)", "", $url );
	return preg_replace("(^https?://)", "", $url );
}

function wpmerge_add_protocal_to_url($url, $protocal, $add_www){
	$trimmed_url = wpmerge_remove_protocal_from_url($url);
	if($protocal !== '//'){
		$protocal = $protocal . '://';
	}
	return $add_www ? $protocal . 'www.' . $trimmed_url : $protocal . $trimmed_url ;
}