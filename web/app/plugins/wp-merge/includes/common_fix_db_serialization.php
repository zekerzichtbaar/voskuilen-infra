<?php

if(!defined('ABSPATH')){ exit; }

class wpmerge_fix_db_serialization{//this class is very similar to includes/common_replace_db_links.php class wpmerge_replace_db_links
	private $current_offset;
	private $eof;

	public function __construct(){
		$this->wpdb = $GLOBALS['wpdb'];
	}

	public function fix_table($table_details){

		$table_name = $table_details['name'];
		$this->current_offset = $table_details['current_offset'];
		$this->eof = false;
		
		$table_name = $table_details['name'];
		
		$this->fix_in_depth($replace_list=array(), array($table_name), true);

		$table_details['eof'] = $this->eof;
		$table_details['current_offset'] = $this->current_offset;

		return $table_details;
	}

	//one table will be sent $tables array
	private function fix_in_depth($list = array(), $tables = array(), $fullsearch = false) {
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

				$row_offset = $this->current_offset;

				$limit = 25000;

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

								//Repair serilized strings that have become broken
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
							$result = $this->wpdb->query($sql);

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

}