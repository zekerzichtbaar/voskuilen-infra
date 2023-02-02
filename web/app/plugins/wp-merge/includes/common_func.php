<?php
/**
 * WPMerge
 * Copyright (c) 2018 Revmakx LLC
 * revmakx.com
 */

if(!defined('ABSPATH')){ exit; }

function wpmerge_add_option($option_name, $option_value){
	if(!wpmerge_is_exists_option($option_name)){
		$option_value = maybe_serialize($option_value);
		return $GLOBALS['wpdb']->insert($GLOBALS['wpdb']->base_prefix .'wpmerge_options', compact('option_name', 'option_value'));
	}
	return false;
}

function wpmerge_update_option($option_name, $option_value){
	if(wpmerge_is_exists_option($option_name)){
		$option_value = maybe_serialize($option_value);
		return $GLOBALS['wpdb']->update($GLOBALS['wpdb']->base_prefix .'wpmerge_options', compact('option_name', 'option_value'), compact('option_name'));
	}
	else{
		return wpmerge_add_option($option_name, $option_value);
	}
}

function wpmerge_delete_option($option_name){
	return $GLOBALS['wpdb']->delete($GLOBALS['wpdb']->base_prefix .'wpmerge_options', compact('option_name'));
}

function wpmerge_get_option($option_name){
	$query = $GLOBALS['wpdb']->prepare('select option_value from '.$GLOBALS['wpdb']->base_prefix .'wpmerge_options where option_name = %s', $option_name);
	$result = $GLOBALS['wpdb']->get_var($query);
	return maybe_unserialize($result);
}

function wpmerge_is_exists_option($option_name){
	$query = $GLOBALS['wpdb']->prepare('select option_value from '.$GLOBALS['wpdb']->base_prefix .'wpmerge_options where option_name = %s', $option_name);
	$result = $GLOBALS['wpdb']->get_row($query);
	return !empty($result);
}


function wpmerge_is_valid_env_saved(){//need better name LATER
	$options_table = $GLOBALS['wpdb']->base_prefix . 'wpmerge_options';
	if( !wpmerge_is_table_exist($options_table) ){
		return false;
	}

	if( !wpmerge_is_exists_option('ENV') ){
		return false;
	}

	$env = wpmerge_get_option('ENV');
	if( !wpmerge_is_valid_env($env) ){
		return false;
	}
	return true;
}

function wpmerge_is_valid_env($env){
    $expected_envs = array('PROD', 'DEV');
	if(empty($env) || !in_array($env, $expected_envs, true)){
		return false;
    }
    return true;
}

function wpmerge_set_env($env){
	$result = wpmerge_update_option('ENV', $env);
	wpmerge_get_env($cache=false);//clear the cache
	return $result;
}

function wpmerge_get_env($cache=true){//this caching will help save time wpmerge_is_dev_env() and wpmerge_is_prod_env() when called in big loops
	static $env_cache;
	if($cache && !empty($env_cache)){
		return $env_cache;
	}
	return $env_cache = wpmerge_get_option('ENV');
}

function wpmerge_is_prod_env(){
	if(wpmerge_get_env() === 'PROD'){
		return true;
	}
	return false;
}

function wpmerge_is_dev_env(){
	if(wpmerge_get_env() === 'DEV'){
		return true;
	}
	return false;
}

function wpmerge_get_collation(){
	global $wpdb;
	if (method_exists( $wpdb, 'get_charset_collate')) {
		$charset_collate =  $wpdb->get_charset_collate();
	}

	return !empty($charset_collate) ?  $charset_collate : ' DEFAULT CHARACTER SET utf8 DEFAULT COLLATE utf8_general_ci ' ;
}

function wpmerge_remove_prefix($prefix, $string){
	if (substr($string, 0, strlen($prefix)) == $prefix) {//this method is faster https://stackoverflow.com/a/4517270/188371
		$string = substr($string, strlen($prefix));
	}
	return $string;
}

function wpmerge_remove_suffix($suffix, $string){

	if (substr($string, (strlen($string) - strlen($suffix)), strlen($suffix)) == $suffix) {
		$string = substr($string, 0, (strlen($string) - strlen($suffix)));
	}
	return $string;
}
function wpmerge_replace_prefix($old_prefix, $new_prefix, $string){
	return $new_prefix.wpmerge_remove_prefix($old_prefix, $string);
}

function wpmerge_debug_print($var, $wrap=''){
	if(!empty($wrap)) echo '<br>'.$wrap.' START:';
	echo '<pre>';var_dump($var);echo '</pre>';
	if(!empty($wrap)) echo '<br>'.$wrap.' END.';
}

function wpmerge_is_time_limit_exceeded($time_limit=''){
	$default_time_limit = WPMERGE_TIMEOUT;
	if(!defined('WPMERGE_START_TIME')){
		return true;
	}
	$time_limit = empty($time_limit) ? $default_time_limit : $time_limit;
	$time_taken = microtime(true) - WPMERGE_START_TIME;
	if($time_taken >= $time_limit){
		return true;
	}
	return false;
}


function wpmerge_replace_table_prefix_in_query($old_db_prefix, $new_db_prefix, $query){

	if($old_db_prefix === $new_db_prefix){//same prefixes
		return $query;
	}

	$create_view_find = preg_match( '/^\s*CREATE\s+VIEW\s/i', $query ) ;
	if($create_view_find){
		$query = str_ireplace($old_db_prefix, $new_db_prefix, $query);
		return $query;
	}

	$old_table_name = wpmerge_get_table_from_query($query);
	if($old_table_name !== false){
		$new_table_name = preg_replace("/$old_db_prefix/i", $new_db_prefix, $old_table_name, 1);

		$sql_query_array = explode("(", $query, 2);
		$sql_query_array[0] = str_ireplace($old_table_name, $new_table_name, $sql_query_array[0]);
		$query =  implode("(", $sql_query_array);
	}
	else{
		//following code may not accurate, not the best way to do it
		$sql_query_array = explode("(", $query, 2);
		$sql_query_array[0] = str_ireplace($old_db_prefix, $new_db_prefix, $sql_query_array[0]);
		$query =  implode("(", $sql_query_array);
	}

	//following code only works for insert, create table, alter table etc which we can see in mysql dump not works for update|delete etc
	// global $wpmerge___old_db_prefix, $wpmerge___new_db_prefix;
	// $wpmerge___old_db_prefix = $old_db_prefix;
	// $wpmerge___new_db_prefix = $new_db_prefix;

	// $query = preg_replace_callback("/(TABLE[S]?|INSERT\ INTO|DROP\ TABLE\ IF\ EXISTS) [`]?([^`\;\ ]+)[`]?/", 'wpmerge_do_preg_replace_prefix', $query);// this will replace the old prefix to new one


	return $query;
}

function wpmerge_replace_table_prefix_in_query_use_full_table_names($old_db_prefix, $new_db_prefix, $old_tables_list, $new_tables_list, $query){
	//please validate all inputs($old_db_prefix, $new_db_prefix, $query, $old_tables_list, $new_tables_list) before calling this func.
	//use wpmerge_replace_prefix_for_table_names_list() to prepare $new_tables_list

	if( !empty($old_tables_list) && !empty($new_tables_list) && count($old_tables_list) === count($new_tables_list) ){
		if($old_tables_list === $new_tables_list){//same prefixes
			return $query;
		}
		$query = str_ireplace($old_tables_list, $new_tables_list, $query);
		return $query;
	}
	else{
		return wpmerge_replace_table_prefix_in_query($old_db_prefix, $new_db_prefix, $query);
	}
}

function wpmerge_replace_prefix_for_table_names_list($old_db_prefix, $new_db_prefix, $old_tables_list){
	//IMPORTANT the output new_tables_list should be similar to input, same order and same count. same tables both side with prefix changed

	if(empty($old_db_prefix) || empty($new_db_prefix) || !is_string($old_db_prefix) || !is_string($new_db_prefix) ||  !is_array($old_tables_list)){
		return false;
	}

	if($old_db_prefix === $new_db_prefix){
		return $old_tables_list;
	}

	$new_tables_list = array();
	foreach($old_tables_list as $old_table_name){

		$new_table_name = preg_replace("/^$old_db_prefix/i", $new_db_prefix, $old_table_name, 1);
		if($new_table_name === null){//preg_replace error case
			return false;
		}
		$new_tables_list[] = $new_table_name;

	}
	if(count($old_tables_list) !== count($new_tables_list)){
		return false;
	}
	return empty($new_tables_list) ? false : $new_tables_list;
}

// function wpmerge_do_preg_replace_prefix($matches){
// 	global $wpmerge___old_db_prefix, $wpmerge___new_db_prefix;
// 	$query = $matches[0];
// 	$old_table_name = $matches[2];

// 	$new_table_name = preg_replace("/$wpmerge___old_db_prefix/", $wpmerge___new_db_prefix, $old_table_name, 1);

// 	return str_replace($old_table_name, $new_table_name, $query);
// }

class wpmerge_exception extends Exception {
	//$error is as error code like slug
	protected $error;
	public function __construct($error = '', $message = '', $code = 0, $previous_throwable = NULL){
		$this->error = $error;
		parent::__construct($message, $code, $previous_throwable);
	}
	public function getError(){
		return $this->error;
	}
	public function getFormatedError(){
		return wpmerge_get_error_msg($this->error);
	}
	public function getErrorMsg(){
		$msg = $this->getMessage();
		return empty($msg) ?  $this->getFormatedError() : $msg;
	}
}

function wpmerge_get_error_msg($error_slug){
	return wpmerge_get_lang($error_slug);
}

function wpmerge_get_lang($lang_slug){
	static $lang;
	if(!isset($lang)){
		include_once(WPMERGE_PATH . '/lang.php');
		$lang = $wpmerge_lang;
	}
	return isset($lang[$lang_slug]) ? $lang[$lang_slug] : $lang_slug;
}

function wpmerge_esc_table_prefix($prefix){
	$tmp_replacer = '||**^**||';
	$search = array('\\_', '_', $tmp_replacer);
	$replace = array($tmp_replacer, '\\_', '\\_');
	return str_replace($search, $replace, $prefix);//do left to right if already escapsed string comes in, i am trying to maintain that with left to right replacement of str_replace with $tmp_replacer //why escaping the (_) because it is single character whild card in mysql
}

function wpmerge_get_auto_increment_column($table){//this function is duplicate of wp-merge/includes/dev_db.php method get_auto_increment_column()
	//assuming one auto increment per table
	//assuming INFORMATION_SCHEMA is accesible
	$db_name = DB_NAME;
	$table_col = $GLOBALS['wpdb']->get_row("SELECT * FROM `INFORMATION_SCHEMA`.`COLUMNS` WHERE `TABLE_SCHEMA` = '".$db_name."' AND `TABLE_NAME` = '".$table."' AND `EXTRA` = 'auto_increment'");
	if(empty($table_col)){
		return false;
	}
	return $table_col->COLUMN_NAME;
}

/**
 * Find the first table name referenced in a query.
 *
 * @since WP 4.2.0
 *
 * @param string $query The query to search.
 * @return string|false $table The table name found, or false if a table couldn't be found.
 */
function wpmerge_get_table_from_query( $query ){//taken from wordpress wpdb:get_table_from_query wp v4.9.6
	// Remove characters that can legally trail the table name.
	$query = rtrim( $query, ';/-#' );

	// Allow (select...) union [...] style queries. Use the first query's table name.
	$query = ltrim( $query, "\r\n\t (" );

	// Strip everything between parentheses except nested selects.
	$query = preg_replace( '/\((?!\s*select)[^(]*?\)/is', '()', $query );

	// Quickly match most common queries.
	if ( preg_match( '/^\s*(?:'
			. 'SELECT.*?\s+FROM'
			. '|INSERT(?:\s+LOW_PRIORITY|\s+DELAYED|\s+HIGH_PRIORITY)?(?:\s+IGNORE)?(?:\s+INTO)?'
			. '|REPLACE(?:\s+LOW_PRIORITY|\s+DELAYED)?(?:\s+INTO)?'
			. '|UPDATE(?:\s+LOW_PRIORITY)?(?:\s+IGNORE)?'
			. '|DELETE(?:\s+LOW_PRIORITY|\s+QUICK|\s+IGNORE)*(?:.+?FROM)?'
			. ')\s+((?:[0-9a-zA-Z$_.`-]|[\xC2-\xDF][\x80-\xBF])+)/is', $query, $maybe ) ) {
		return str_replace( '`', '', $maybe[1] );
	}

	// SHOW TABLE STATUS and SHOW TABLES WHERE Name = 'wp_posts'
	if ( preg_match( '/^\s*SHOW\s+(?:TABLE\s+STATUS|(?:FULL\s+)?TABLES).+WHERE\s+Name\s*=\s*("|\')((?:[0-9a-zA-Z$_.-]|[\xC2-\xDF][\x80-\xBF])+)\\1/is', $query, $maybe ) ) {
		return $maybe[2];
	}

	// SHOW TABLE STATUS LIKE and SHOW TABLES LIKE 'wp\_123\_%'
	// This quoted LIKE operand seldom holds a full table name.
	// It is usually a pattern for matching a prefix so we just
	// strip the trailing % and unescape the _ to get 'wp_123_'
	// which drop-ins can use for routing these SQL statements.
	if ( preg_match( '/^\s*SHOW\s+(?:TABLE\s+STATUS|(?:FULL\s+)?TABLES)\s+(?:WHERE\s+Name\s+)?LIKE\s*("|\')((?:[\\\\0-9a-zA-Z$_.-]|[\xC2-\xDF][\x80-\xBF])+)%?\\1/is', $query, $maybe ) ) {
		return str_replace( '\\_', '_', $maybe[2] );
	}

	// Big pattern for the rest of the table-related queries.
	if ( preg_match( '/^\s*(?:'
			. '(?:EXPLAIN\s+(?:EXTENDED\s+)?)?SELECT.*?\s+FROM'
			. '|DESCRIBE|DESC|EXPLAIN|HANDLER'
			. '|(?:LOCK|UNLOCK)\s+TABLE(?:S)?'
			. '|(?:RENAME|OPTIMIZE|BACKUP|RESTORE|CHECK|CHECKSUM|ANALYZE|REPAIR).*\s+TABLE'
			. '|TRUNCATE(?:\s+TABLE)?'
			. '|CREATE(?:\s+TEMPORARY)?\s+TABLE(?:\s+IF\s+NOT\s+EXISTS)?'
			. '|ALTER(?:\s+IGNORE)?\s+TABLE'
			. '|DROP\s+TABLE(?:\s+IF\s+EXISTS)?'
			. '|CREATE(?:\s+\w+)?\s+INDEX.*\s+ON'
			. '|DROP\s+INDEX.*\s+ON'
			. '|LOAD\s+DATA.*INFILE.*INTO\s+TABLE'
			. '|(?:GRANT|REVOKE).*ON\s+TABLE'
			. '|SHOW\s+(?:.*FROM|.*TABLE)'
			. ')\s+\(*\s*((?:[0-9a-zA-Z$_.`-]|[\xC2-\xDF][\x80-\xBF])+)\s*\)*/is', $query, $maybe ) ) {
		return str_replace( '`', '', $maybe[1] );
	}

	return false;
}

function wpmerge_is_table_exist($table){
	$escaped_table_name = wpmerge_esc_table_prefix($table);
	if( $GLOBALS['wpdb']->get_var("SHOW TABLES LIKE '$escaped_table_name'") == $table ){
		return true;
	}
	return false;
}

function wpmerge_is_dir($path){
	$path = wp_normalize_path($path);

	if (is_dir($path)) {
		return true;
	}

	$ext = pathinfo($path, PATHINFO_EXTENSION);

	if (!empty($ext)) {
		return false;
	}

	if (is_file($path)) {
		return false;
	}

	return true;
}

/*
* wpmerge_http_build_query_for_curl() supports build query with file upload
* where as http_build_query() supports only multi dimension array not with file upload
*/
function wpmerge_http_build_query_for_curl( $arrays, &$new = array(), $prefix = null ) {

	if ( $arrays  instanceof CURLFile ) {
		$new[$prefix] = $arrays;
		return $new;
	}
	
    if ( is_object( $arrays ) ) {
        $arrays = get_object_vars( $arrays );
    }

    foreach ( $arrays as $key => $value ) {
        $k = isset( $prefix ) ? $prefix . '[' . $key . ']' : $key;
        if ( is_array( $value ) || is_object( $value )  ) {
            $new = wpmerge_http_build_query_for_curl( $value, $new, $k );
        } else {
            $new[$k] = $value;
        }
	}
	return $new;
}

function wpmerge_do_call($URL, $options=array()){

	$SSLVerify = false;
	$URL = trim($URL);
	//if(stripos($URL, 'https://') !== false){ $SSLVerify = true; }
	$timeout = 60;

	if(isset($options['body'])){
		$data = $options['body'];
	}
	if(isset($options['timeout'])){
		$timeout = $options['timeout'];
	}


	$HTTPCustomHeaders = array();
	if(isset($options['headers'])){
		$HTTPCustomHeaders = $options['headers'];
	}

	$userAgent = 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.2526.73 Safari/537.36';

	$ch = curl_init($URL);
	curl_setopt($ch, CURLOPT_URL, $URL);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	//curl_setopt($ch, CURLOPT_MAXREDIRS, 2);
	curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
	curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, ($SSLVerify === true) ? 2 : false );
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $SSLVerify);
	curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

	//Using Proxy starts here
	if(defined('WP_PROXY_HOST')){
		curl_setopt($ch, CURLOPT_PROXY, WP_PROXY_HOST);
	}
	if(defined('WP_PROXY_PORT')){
		curl_setopt($ch, CURLOPT_PROXYPORT, WP_PROXY_PORT);
	}
	if(defined('WP_PROXY_HOST') && defined('WP_PROXY_USERNAME') && defined('WP_PROXY_PASSWORD')){
		curl_setopt($ch, CURLOPT_PROXYUSERPWD, WP_PROXY_USERNAME.":".WP_PROXY_PASSWORD);
	}	
	//Using Proxy ends here

	if(empty($options['filename'])){
		curl_setopt($ch, CURLOPT_HEADER, true);
	}
	curl_setopt($ch, CURLINFO_HEADER_OUT, true);


	// if(!defined('REFERER_OPT') || (defined('REFERER_OPT') && REFERER_OPT === TRUE) ){
	// 	curl_setopt($ch, CURLOPT_REFERER, $URL);
	// }

	// if(defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')){
	// 	curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
	// }


	if(!empty($options['contentType'])){//'text/plain', 'application/x-www-form-urlencoded', 'multipart/form-data'
		$contentType = $options['contentType'];
		$HTTPCustomHeaders[] = 'Content-Type: '.trim($contentType);
	}


	curl_setopt($ch, CURLOPT_HTTPHEADER, $HTTPCustomHeaders);
	if (isset($options['SSLVersion'])) {
		$SSLVersion = (int) $options['SSLVersion'];
		curl_setopt($ch, CURLOPT_SSLVERSION, $SSLVersion);
	}
	if(!empty($options['httpAuth'])){
		curl_setopt($ch, CURLOPT_USERPWD, $options['httpAuth']['username'].':'.$options['httpAuth']['password']);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
	}

	if(!empty($options['useCookie'])){
		if(!empty($options['cookie'])){
			curl_setopt($ch, CURLOPT_COOKIE, $options['cookie']);
		}
	}

	// if (!ini_get('safe_mode') && !ini_get('open_basedir')){
	// 	@curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	// }

	if(!empty($options['filename'])){
		$fp = fopen($options['filename'], "a");
    	curl_setopt($ch, CURLOPT_FILE, $fp);
	}

	if($options['method'] == 'POST'){
		curl_setopt($ch, CURLOPT_POST, 1);
		$post_data = wpmerge_http_build_query_for_curl($data);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data );
	}

	// curl_setopt($ch, CURLOPT_VERBOSE, 1);
	// $df = fopen(ABSPATH.'/_debug_curl.php', "rw+");
	// curl_setopt($ch, CURLOPT_STDERR, $df);


	$microtimeStarted 	= microtime(true);
	$rawResponse		= curl_exec($ch);
	$microtimeEnded 	= microtime(true);

	//$curlInfo = array();
	$curlInfo = curl_getinfo($ch);
	$curlInfo['_start_time'] = $microtimeStarted;
	$curlInfo['_end_time'] = $microtimeEnded;

	if(curl_errno($ch)){
		$curlInfo['_error_no'] = curl_errno($ch);
		$curlInfo['_error_msg'] = curl_error($ch);
	}

	curl_close($ch);
	//fclose($df);
	if(!empty($options['filename'])){
		fclose($fp);
	}

	list($responseHeader, $responseBody) = wpmerge_do_call_split_raw_response($rawResponse, $curlInfo);
	$curlInfo['_response_header'] = $responseHeader;

    wpmerge_debug::log(array('url' => $URL, 'body' => $responseBody, 'header' => ($curlInfo['http_code'] != 200 ? $responseHeader : '')),'-----------response curl----------------');

	return array('body' => $responseBody,
				 'info' => $curlInfo);
}

function wpmerge_do_call_split_raw_response($rawResponse, $curlInfo){
	$header;
	$body = $rawResponse;//safety
    if(isset($curlInfo["header_size"])) {
        $header_size = $curlInfo["header_size"];
        $header = substr($rawResponse, 0, $header_size);
        $body = substr($rawResponse, $header_size);
    }
    return array($header, $body);
}

function wpmerge_get_response_from_json($response){
	$response_str = wpmerge_get_response_body($response);
	$clean_response_str = wpmerge_remove_response_junk($response_str);
    $response_data = json_decode($clean_response_str, true);

    if($response_data === null){
        //if required use json_last_error()
        throw new wpmerge_exception('invalid_response_json_failed', wpmerge_get_lang('invalid_response_json_failed').' '.wpmerge_show_http_response_in_error_msg($response));
	}
	
	if(!empty($response_data['status']) && in_array($response_data['status'], array('error_incompatible_plugins_version', 'error_auth_failed')) ){
		if( !empty($response_data['error']) && !empty($response_data['error_msg']) ){
			throw new wpmerge_exception($response_data['error'], $response_data['error_msg']);
		}
		elseif( !empty($response_data['error']) ){
			throw new wpmerge_exception($response_data['error']);
		}
		throw new wpmerge_exception('invalid_response');
	}

    return $response_data;
}

function wpmerge_get_response_body($response){
    if(isset($response['info']['_error_no'])){
        throw new wpmerge_exception('http_error', wpmerge_get_error_msg('http_error').' Error('.$response['info']['_error_no'].'): '.$response['info']['_error_msg']);
    }

    $http_code = $response['info']['http_code'];
    if($http_code !== 200){
        throw new wpmerge_exception('http_error', wpmerge_get_error_msg('http_error').' HTTP status code: ('.$http_code.')');
    }
    $response_str = $response['body'];
    return $response_str;
}

function wpmerge_remove_response_junk($response){
	$start_tag_len = strlen('<wpmerge_response>');
	$start_pos = stripos($response, '<wpmerge_response');
	$end_pos = stripos($response, '</wpmerge_response');
    if($start_pos === false || $end_pos === false){
		return false;
	}

	$response = substr($response, $start_pos);//clearing anything before start tag
	$end_pos = stripos($response, '</wpmerge_response');//new end_pos
	$response = substr($response, $start_tag_len, $end_pos-$start_tag_len);

	return $response;
}

function wpmerge_remove_file_data_response_junk($response){
	$start_tag_len = strlen('<wpmerge_file_data>');
	$start_pos = stripos($response, '<wpmerge_file_data');
	$end_pos = stripos($response, '</wpmerge_file_data');
    if($start_pos === false || $end_pos === false){
		return false;
	}

	$response = substr($response, $start_pos);//clearing anything before start tag
	$end_pos = stripos($response, '</wpmerge_file_data');//new end_pos
	$response = substr($response, $start_tag_len, $end_pos-$start_tag_len);

	return $response;
}

function wpmerge_prepare_response($response){//to send response in form json with a wrapper
	$json = json_encode($response);
	return '<wpmerge_response>'.$json.'</wpmerge_response>';
}

function wpmerge_show_http_response_in_error_msg($http_response, $additional_msg=''){

	$http_response_str = var_export($http_response, true);
	$http_response_str = htmlentities($http_response_str);
	if(!empty($additional_msg)){
		$http_response_str = $additional_msg."\n".$http_response_str;
	}
	$return = '<br><a onClick="jQuery(this).next(\'textarea.wpmerge_http_response_display\').toggle();" style="color: unset; cursor: pointer; text-decoration: underline;">Click here to see HTTP response</a>.<textarea class="wpmerge_http_response_display" style="display:none;width:400px;height:100px;">'.$http_response_str.'</textarea>';
	return $return;
}

function wpmerge_maintenance_mode( $enable = false ){
	$maintenance_cont = '<?php $upgrading =  time(); ?>';//let the time be dynamic so it never removes.
	$file = trailingslashit(ABSPATH) .'.maintenance';

	$is_old_file_deleted = true;
	if( file_exists( $file ) ){
		$is_old_file_deleted = @unlink($file);
	}

	if ($enable) {
		$file_write_result = @file_put_contents($file, $maintenance_cont);
		if( $file_write_result === false ){
			return false;
		}
		return true;
	}
	else{
		return $is_old_file_deleted;
	}
}

function wpmerge_maintenance_mode_enable(){
	return wpmerge_maintenance_mode( $enable = true );
}

function wpmerge_maintenance_mode_disable(){
	return wpmerge_maintenance_mode( $enable = false );
}

function wpmerge_prepare_bridge(){
    require_once WPMERGE_PATH . '/includes/prepare_bridge.php';
    $prepare_bridge = new wpmerge_prepare_bridge();
    $response = $prepare_bridge->prepare();

    if (empty($response)) {
		return	array('error' => 'something went wrong');
	}
	
	wpmerge_maintenance_mode_enable();

    return	$response;
}

function wpmerge_delete_bridge(){
    require_once WPMERGE_PATH . '/includes/prepare_bridge.php';
    $prepare_bridge = new wpmerge_prepare_bridge();
	$response = $prepare_bridge->delete();
	
	if( wpmerge_is_dev_env() ){
		wpmerge_update_option( 'dev_wp_purge_cache', 'yes' );//set the flag
	}

    // if (empty($response)) {
	// 	return	array('error' => 'something went wrong');
	// }
	
	wpmerge_maintenance_mode_disable();

    return	$response;
}

function wpmerge_get_upload_dir(){
	if (defined('WPMERGE_BRIDGE')) {
		$uploadDir['basedir'] = WPMERGE_RELATIVE_WP_CONTENT_DIR . '/uploads';
	} else {
		$uploadDir = wp_upload_dir();
	}

	$upload_dir = str_replace(WPMERGE_ABSPATH, WPMERGE_RELATIVE_ABSPATH, $uploadDir['basedir']);

	return wp_normalize_path($upload_dir);
}

function wpmerge_add_fullpath($file){
	$file = wp_normalize_path($file);

	if (wpmerge_is_wp_content_path($file)) {
		//Special patch for wp-content dir to support common functions of paths.

		$temp_file = $file;

		if(strpos($file, WPMERGE_RELATIVE_WP_CONTENT_DIR ) === 0 ){
			$temp_file = substr_replace($file, '', 0, strlen(WPMERGE_RELATIVE_WP_CONTENT_DIR));
			if($temp_file === '' || $temp_file === '/'){
				$temp_file = WPMERGE_WP_CONTENT_DIR;
			}
		}

		return wpmerge_add_custom_path($temp_file, $custom_path = WPMERGE_WP_CONTENT_DIR . '/');
	}

	return wpmerge_add_custom_path($file, $custom_path = WPMERGE_ABSPATH);
}

function wpmerge_remove_fullpath($file){
	$file = wp_normalize_path($file);

	if (wpmerge_is_wp_content_path($file)) {

		$temp_file = $file;

		if(strpos($file, WPMERGE_RELATIVE_WP_CONTENT_DIR ) === 0 ){
			$temp_file = substr_replace($file, '', 0, strlen(WPMERGE_RELATIVE_WP_CONTENT_DIR));
			if($temp_file === '' || $temp_file === '/'){
				$temp_file = WPMERGE_WP_CONTENT_DIR;
			}
		}

		if(untrailingslashit($file) === untrailingslashit(WPMERGE_WP_CONTENT_DIR)  ){
			$temp_file = untrailingslashit($temp_file);
		}


		return wpmerge_remove_custom_path($temp_file, $custom_path = WPMERGE_WP_CONTENT_DIR , $relative_path = WPMERGE_RELATIVE_WP_CONTENT_DIR );
	}

	return wpmerge_remove_custom_path($file, $custom_path = WPMERGE_ABSPATH, $relative_path = WPMERGE_RELATIVE_ABSPATH);
}

function wpmerge_is_wp_content_path($file){
	if (strpos($file, '/' . WPMERGE_WP_CONTENT_BASENAME) === 0 || strpos($file, WPMERGE_WP_CONTENT_DIR) === 0) {
		return true;
	}

	return false;
}

function wpmerge_add_custom_path($file, $custom_path){

	$temp_file = trailingslashit($file);

	if (strpos($temp_file, $custom_path) !== false) {
		return $file;
	}

	return $custom_path . ltrim($file, '/');
}

function wpmerge_remove_custom_path($file, $custom_path, $relative_path){

	if (strpos($file, $custom_path) === false) {
		if(substr($relative_path, -1) === '/'){
			return $relative_path . ltrim($file, '/');
		}

		return $relative_path . '/' . ltrim($file, '/');
	}

	return str_replace($custom_path, $relative_path, $file);
}

function wpmerge_die_with_json_encode($msg = 'empty data', $escape = 0){

	switch ($escape) {
		case 1:
		die(json_encode($msg, JSON_UNESCAPED_SLASHES));
		case 2:
		die(json_encode($msg, JSON_UNESCAPED_UNICODE));
	}

	die(json_encode($msg));
}

function wpmerge_function_exist($function){

	if (empty($function)) {
		return false;
	}

	if ( !function_exists($function) ) {
		return false;
	}

	$disabled_functions = explode(',', ini_get('disable_functions'));
	$function_enabled = !in_array($function, $disabled_functions);
	return ($function_enabled) ? true : false;
}

function wpmerge_is_gz_available(){
	wpmerge_debug::log(func_get_args(), "--------" . __FUNCTION__ . "--------");
    if(!wpmerge_function_exist('gzwrite') || !wpmerge_function_exist('gzopen') || !wpmerge_function_exist('gzclose') ){
        wpmerge_debug::log(array(), '--------ZGIP not available--------');
        return false;
    }

    return true;
}

function wpmerge_is_gz_txt_available(){

    if(!wpmerge_function_exist('gzcompress') || !wpmerge_function_exist('gzuncompress')){
        return false;
    }

    return true;
}

/*
* $filename_details is string use as file name
* $filename_details if array take file name parts
*
*/
function wpmerge_create_temp_file($dir_name, $filename_details){
	if( empty($dir_name) || empty($filename_details)){
		throw new wpmerge_exception('create_temp_file_invalid_request');
	}

	$filename_prefix = '';
	$filename_ext = 'tmp';
	if(is_string($filename_details)){
		$filename = $filename_details;
	}
	elseif(is_array($filename_details)){
		if(!empty($filename_details['prefix'])){
			$filename_prefix = $filename_details['prefix'];
		}
		if(!empty($filename_details['ext'])){
			$filename_ext = $filename_details['ext'];
		}
	}

	$tmp_dir = WPMERGE_TEMP_DIR . '/'.$dir_name;
	if(!file_exists($tmp_dir)){
		$mkDir = mkdir($tmp_dir, 0775, true);
		if(!$mkDir){
			throw new wpmerge_exception('create_temp_file_make_dir_failed');
		}
	}
	if(!empty($filename)){
		$temp_file = $tmp_dir. '/' . $filename;
	}
	elseif(!empty($filename_prefix)){
		$temp_file = $tmp_dir. '/' . md5(uniqid($filename_prefix, true)) .'.'.$filename_ext;
	}
	$create_file = touch($temp_file);
	if($create_file === false){
		throw new wpmerge_exception('create_temp_file_failed');
	}
	return $temp_file;
}

function wpmerge_get_default_help_toggle_state(){
	return $default_help_toggles_state = array(
		'dev_main_help_show' => '1',
		'dev_selected_queries_info' => '1'
	);
}

function wpmerge_update_help_toggle_state($update_help_toggles_state){
	$default_help_toggles_state = wpmerge_get_default_help_toggle_state();
	$current_user_id = get_current_user_id();
	$slug = 'user_help_toggles_state_'.$current_user_id;	
	$current_user_help_toggles_state = wpmerge_get_help_toggles_state();

	$final_help_toggles_state = array_merge($current_user_help_toggles_state, $update_help_toggles_state);

	//wpmerge_update_option('help_toggles_state', $help_toggles_state);
	wpmerge_update_option($slug, $final_help_toggles_state);
}

function wpmerge_get_help_toggle_state($slug){
	$default_help_toggles_state = wpmerge_get_default_help_toggle_state();

	$current_user_help_toggles_state = wpmerge_get_help_toggles_state();
	//$help_toggles_state = wpmerge_get_option('help_toggles_state');
	$help_toggles_state = array_merge($default_help_toggles_state, $current_user_help_toggles_state);
	if(isset($help_toggles_state[$slug])){
		return $help_toggles_state[$slug];
	}
	return false;
}

function wpmerge_print_help_toggle_state($slug, $if_state, $print){
	if(wpmerge_is_help_toggle_state($slug, $if_state)){
		echo $print;
	}
}

function wpmerge_is_help_toggle_state($slug, $if_state){
	if($if_state == wpmerge_get_help_toggle_state($slug)){
		return true;
	}
	return false;
}

function wpmerge_get_help_toggles_state(){
	$current_user_id = get_current_user_id();
	$slug = 'user_help_toggles_state_'.$current_user_id;
	$current_user_help_toggles_state = wpmerge_get_option($slug);
	if(!is_array($current_user_help_toggles_state)){
		$current_user_help_toggles_state = array();
	}
	return $current_user_help_toggles_state;
}

function wpmerge_redirect_php_js($url){
	if(!headers_sent()){
		header('Location: '.$url);
	}
	else{
		echo '
	<script>
		window.location = "'.$url.'";
	</script>
	<a href="'.$url.'">Redirecting...</a>
	';
	}	
}

//wpmerge_is_db_table_exists() is not efficient, count might take time in large tables
// function wpmerge_is_db_table_exists($table){
// 	if( empty($table) || !is_string($table)){
// 		return false;
// 	}
// 	$db_name = DB_NAME;
// 	$result = $GLOBALS['wpdb']->get_var("SELECT count(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = '".$db_name."' AND TABLE_NAME = '".$table."'");
// 	return !empty($result);//result is expected to be number
// }

function wpmerge_get_db_tables($prefix, $validate_tables='', $get_details='', $get_all_table_type=false){
	if(empty($prefix)){
		return false;
	}
	$db_name = DB_NAME;
	if(empty($db_name)){
		return false;
	}
	
	$table_name_filter = '';
	if(!empty($validate_tables) && is_array($validate_tables)){
		$validate_tables_list = "'".implode("', '", $validate_tables)."'";
		$table_name_filter = " AND `TABLE_NAME` IN(".$validate_tables_list.")";
		//$table_name_filter = " AND `Tables_in_".$db_name."` IN(".$validate_tables_list.")";
	}

	$table_type_filter = " AND `TABLE_TYPE` = 'BASE TABLE'";//default 'BASE TABLE'(s) only
	if($get_all_table_type == true){
		$table_type_filter = "";
	}

	$escaped_base_prefix = wpmerge_esc_table_prefix($prefix);
	$show_tables_sql = "SELECT `TABLE_NAME`, `TABLE_TYPE` FROM `INFORMATION_SCHEMA`.`TABLES` WHERE `TABLE_SCHEMA` = '".$db_name."' AND `TABLE_NAME` LIKE '".$escaped_base_prefix."%' ".$table_name_filter." ".$table_type_filter." ORDER BY FIELD(TABLE_TYPE, 'BASE TABLE', 'VIEW')";
	
	//"SHOW FULL TABLES WHERE `Tables_in_".$db_name."` LIKE '".$escaped_base_prefix."%' ".$table_name_filter." AND Table_type = 'BASE TABLE'";

	if(empty($get_details)){
		$tables = $GLOBALS['wpdb']->get_col($show_tables_sql);//first columns is list of tables so get_col() will do the job
	}
	else{
		$tables = $GLOBALS['wpdb']->get_results($show_tables_sql, ARRAY_A);//first columns is list of tables so get_col() will do the job
	}
	return $tables;
}

function wpmerge_get_wp_prefix_base_tables(){
	$prefix = $GLOBALS['wpdb']->base_prefix;
	return wpmerge_get_db_tables($prefix);
}

function wpmerge_get_valid_wp_prefix_base_tables($validate_tables){ //$validate_tables should be array of table name with prefix
	if(empty($validate_tables) || !is_array($validate_tables)){
		return false;
	}
	$prefix = $GLOBALS['wpdb']->base_prefix;
	return wpmerge_get_db_tables($prefix, $validate_tables);
}

function wpmerge_get_tables_details($prefix){
	return wpmerge_get_db_tables($prefix, '', true, true);
}

function wpmerge_sort_table_type($a, $b){//this function return unstable data of same value i.e the order is not matching the input order when two items considered equal because unstable sorting.
	$order = array('BASE TABLE', 'VIEW');
	$order_filpped = array_flip($order);
	$a_pos = isset($order_filpped[$a['table_type']]) ? $order_filpped[$a['table_type']] : 100;
	$b_pos = isset($order_filpped[$b['table_type']]) ? $order_filpped[$b['table_type']] : 100;

	if ($a_pos == $b_pos) {
        return 0;
    }
    return ($a_pos < $b_pos) ? -1 : 1;
}

function wpmerge_sort_table_by_type($tables){
	$_base_tables = $_view_tables = $_other_tables = array();
	if(empty($tables) || !is_array($tables)){
		return $tables;
	}
    foreach ($tables as $key => $table) {
        
        if($table['table_type'] == 'BASE TABLE' ){
            array_push($_base_tables, $table);
        }
        elseif($table['table_type'] == 'VIEW' ){
            array_push($_view_tables, $table);
        }
        else{
            array_push($_other_tables, $table);
        }
	}
	$tables = array_merge($_base_tables, $_view_tables, $_other_tables);
	return $tables;
}

function wpemerge_fix_view_table_references($table, $old_db_prefix, $new_db_prefix){
	$create_view_details = $GLOBALS['wpdb']->get_row("SHOW CREATE TABLE $table", ARRAY_A);

	if (empty($create_view_details)) {
		return false;
	}

	if (!isset($create_view_details['View'])) {
		//not a view table
		return false;
	}

	$create_view_sql = $create_view_details['Create View'];

	$create_view_sql = wpmerge_normalise_create_view_sql($create_view_sql, $table);

	$create_view_sql = wpmerge_replace_table_prefix_in_query($old_db_prefix, $new_db_prefix, $create_view_sql);//this is the fix for view_table_references

	wpmerge_wpdb::query("DROP TABLE IF EXISTS `$table`");
	wpmerge_wpdb::query("DROP VIEW IF EXISTS `$table`");
	wpmerge_wpdb::query($create_view_sql);
}

function wpmerge_normalise_create_view_sql($create_view_sql, $table){
	$find_pos = strpos($create_view_sql, 'VIEW `'.$table.'` AS');
	if($find_pos !== false){//following 
		//example CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `wp_aview_users` AS select `wp_users`.`ID` AS `ID`,`wp_users`.`user_login` AS `user_login`,`wp_users`.`user_pass` AS `user_pass`,`wp_users`.`user_nicename` AS `user_nicename`,`wp_users`.`user_email` AS `user_email`,`wp_users`.`user_url` AS `user_url`,`wp_users`.`
		//we going to remove "ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER".
		$before_create_view_sql = $create_view_sql;
		$create_view_sql = "CREATE ";
		$create_view_sql .= substr($before_create_view_sql, $find_pos);
	}
	return $create_view_sql;
}

function wpmerge_set_error_reporting(){
	static $is_set_once = null;
	if($is_set_once){
		return;
	}
	if(defined('WPMERGE_DEBUG') && WPMERGE_DEBUG){
		return;
	}

	//commented not required, what user can see that matters.
	// $errorlevel = @error_reporting();
	// @error_reporting($errorlevel & ~E_NOTICE);

	//turn off showing errors
	@ini_set('display_errors', 0);

	$is_set_once = true;
}

function wpmerge_unset_safe_path($path){
	return str_replace("/", "\\", $path);
}

function wpmerge_check_and_protocol($url) {
    $url = trim($url);
    if(substr($url, 0, 2) == '//'){
        return 'http:'.$url;
    }
    return (substr($url, 0, 7) == 'http://' || substr($url, 0, 8) == 'https://')
        ? $url
        : 'http://'.$url;
}

function wpmerge_run_db_final_modifications($src_site_db_table_prefix){
	if(empty($src_site_db_table_prefix)){
		throw new wpmerge_exception('src_site_db_table_prefix_missing');
	}

	$exim_request = array(
		'action' => 'runDBFinalModifications',
		'new_db_prefix' => $GLOBALS['wpdb']->base_prefix,//this site db table prefix
		'db_prefix' => $src_site_db_table_prefix //src site db table prefix
	);
	include_once(WPMERGE_PATH.'/includes/common_exim.php');
	$common_exim_obj = new wpmerge_common_exim($exim_request);
	$response = $common_exim_obj->getResponse();
}

class wpmerge_select_by_memory_limit{
	protected $select_cols;
	protected $select_table;
	protected $select_db;
	protected $select_where;
	protected $select_order;
	protected $select_offset;
	protected $select_limit;

	protected $select_calc_limit;
	protected $primary_auto_inc_col;
	protected $result_format;
	protected $relative_memory_limit;
	protected $absolute_time_limit;
	protected $min_limit = 100;
	protected $total_rows = NULL;
	protected $optimize_query = true;
	static protected $tables_data_cache = array();

	static protected $all_perform_data = array( //this across all queries per php call
		'query_and_get_results_time_taken' => 0,
		'query_time_taken' => 0,
		'query_data_fetch_time_taken' => 0,
		'query_free_results_time_taken' => 0,
		'total_fetched_rows' => 0
	);

	protected $perform_data = array(
		'query_and_get_results_time_taken' => 0,
		'query_time_taken' => 0,
		'query_data_fetch_time_taken' => 0,
		'query_free_results_time_taken' => 0,
		'total_fetched_rows' => 0
	);

	function __construct($select_args, $relative_memory_limit=WPMERGE_RELATIVE_MEMORY_LIMIT, $absolute_time_limit=WPMERGE_TIMEOUT){
		$this->select_cols = $select_args['columns'];
		$this->select_table = $select_args['table'];
		$this->select_limit = $select_args['limit'];
		$this->select_offset = $select_args['offset'];
		$this->select_where = isset($select_args['where']) ? ' WHERE '.$select_args['where'] : '';
		$this->select_order = isset($select_args['order']) ? ' ORDER BY '.$select_args['order'] : '';
		$this->result_format = isset($select_args['result_format']) ? $select_args['result_format'] : NULL;
		$this->total_rows = (isset($select_args['total_rows']) && is_int($select_args['total_rows']) ) ? $select_args['total_rows'] : NULL;
		$this->select_db = DB_NAME;
		$this->relative_memory_limit = $relative_memory_limit;
		$this->absolute_time_limit = $absolute_time_limit;

		$this->next_offset = $this->select_offset;//safety for get_next_offset() to work better

		$this->optimize_query =  isset($select_args['optimize_query']) ? (bool) $select_args['optimize_query'] : $this->optimize_query;

	}

	public function process_and_get_results(){//process
		$this->select_calc_limit = $this->get_calculated_limit();
		$this->primary_auto_inc_col = $this->get_primary_auto_inc_col();
		return $this->query_and_get_results();
	}

	private function get_calculated_limit(){//calculating this because mysql_result->free_results() taking time.
		if( !empty(self::$tables_data_cache[$this->select_table]['calculated_limit']) ){//check cache
			return self::$tables_data_cache[$this->select_table]['calculated_limit'];
		}

		$total_rows = $this->get_total_rows();
		if($total_rows < $this->min_limit){
			$calculated_limit = $this->min_limit;
			if($calculated_limit > $this->select_limit){
				$calculated_limit = $this->select_limit;
			}

			self::$tables_data_cache[$this->select_table]['calculated_limit'] = $calculated_limit;
			return self::$tables_data_cache[$this->select_table]['calculated_limit'];
		}

		$_calc_limit_start = microtime(1);
		
		$table_details = $GLOBALS['wpdb']->get_row("SELECT * FROM information_schema.TABLES WHERE TABLE_SCHEMA = '".$this->select_db."' AND TABLE_NAME = '".$this->select_table."'", ARRAY_A);
		if($table_details['ENGINE'] == 'MyISAM'){
			$avg_row_length = $table_details['AVG_ROW_LENGTH'];
		}
		else{			
			$avg_row_length = 0;
			if($total_rows > 0){
				$avg_row_length = $table_details['DATA_LENGTH'] / $total_rows;
			}
		}

		if($avg_row_length > 0){
			$calculated_limit = ceil( $this->relative_memory_limit / $avg_row_length );
		}
		else{
			$calculated_limit = $this->min_limit;
		}

		if($calculated_limit > $this->select_limit){
			$calculated_limit = $this->select_limit;
		}

		self::$tables_data_cache[$this->select_table]['calculated_limit'] = $calculated_limit;
		wpmerge_debug::printr(( microtime(1) - $_calc_limit_start ), 'time_taken_for_calc_limit');

		return self::$tables_data_cache[$this->select_table]['calculated_limit'];
	}

	protected function get_total_rows(){

		if(is_int($this->total_rows)){
			return self::$tables_data_cache[$this->select_table]['total_rows'] = $this->total_rows;
		}

		if(isset(self::$tables_data_cache[$this->select_table]['total_rows'])){
			return self::$tables_data_cache[$this->select_table]['total_rows'];
		}

		$__total_time_query_start_time = microtime(1);

		$total_rows = $GLOBALS['wpdb']->get_var("SELECT count(*) FROM `$this->select_table`");

		$__total_time_query_time_taken =  microtime(1) - $__total_time_query_start_time;
		wpmerge_debug::printr($__total_time_query_time_taken, '__total_time_query_time_taken');

		if(!is_numeric($total_rows)){//then table might be crashed
			$total_rows = 0;
		}
		return self::$tables_data_cache[$this->select_table]['total_rows'] = $total_rows;
	}

	protected function get_primary_auto_inc_col(){
		if( isset(self::$tables_data_cache[$this->select_table]['primary_auto_inc_col']) ){//check cache
			return self::$tables_data_cache[$this->select_table]['primary_auto_inc_col'];
		}

		$primary_auto_inc_col = $GLOBALS['wpdb']->get_var("SELECT `COLUMN_NAME` FROM `INFORMATION_SCHEMA`.`COLUMNS` as info_cols WHERE `TABLE_SCHEMA` = '".$this->select_db."' AND `TABLE_NAME` = '".$this->select_table."' AND `COLUMN_KEY` = 'PRI' AND `EXTRA` = 'auto_increment'");
		
		if(empty($primary_auto_inc_col)){
			$primary_auto_inc_col = false;
		}
		return self::$tables_data_cache[$this->select_table]['primary_auto_inc_col'] = $primary_auto_inc_col;
	}

	private function query_and_get_results(){

		$result_format_func = 'fetch_object';
		if($this->result_format == ARRAY_A){
			$result_format_func = 'fetch_assoc';
		}
	
		$_dbh = $GLOBALS['wpdb']->dbh;

		if( !($_dbh instanceof mysqli) ){
			throw new wpmerge_exception('wp_should_use_mysqli');
		}
	
		$time_limit = $this->absolute_time_limit;

		wpmerge_debug::printr($time_limit, 'time_limit');
		wpmerge_debug::printr($this->relative_memory_limit/(1024*1024), 'relative_memory_limit');
	
		$table_data = array();
		$before_query_memory_usage = memory_get_usage();
		$before_query_relative_memory_limit = $before_query_memory_usage + $this->relative_memory_limit;	

		wpmerge_debug::printr($before_query_memory_usage/(1024*1024), 'before_query_memory_usage');
	
		$__before_query_time = microtime(1);
	
		$rows_count = 0;
		$total_rows = $this->get_total_rows();
		wpmerge_debug::printr($total_rows, 'total_rows');
		wpmerge_debug::printr($this->primary_auto_inc_col, 'primary_auto_inc_col');
		
		if( $this->optimize_query && !empty($this->primary_auto_inc_col) && $total_rows > $this->min_limit ){
			
			if($this->select_cols == '*' || empty($this->select_cols)){
				$cols = "`$this->select_table`.*";
			}
			else{
				$cols = $this->select_cols;//all columns should have table name before like this `wp_options`.`option_id`
			}
			$query = "SELECT $cols FROM (SELECT `$this->primary_auto_inc_col` FROM `$this->select_table` $this->select_where $this->select_order LIMIT " . $this->select_calc_limit . " OFFSET ".$this->select_offset.") q JOIN `$this->select_table` ON `$this->select_table`.`$this->primary_auto_inc_col` = q.`$this->primary_auto_inc_col`";
		}else{
			$query = "SELECT $this->select_cols FROM `$this->select_table` $this->select_where $this->select_order LIMIT " . $this->select_calc_limit . " OFFSET ".$this->select_offset;
		}
		wpmerge_debug::printr($query, 'query');
	
		$__query_start_time = microtime(1);

		$_dbh_result = $_dbh->query($query, MYSQLI_USE_RESULT);

		$__query_time_taken =  microtime(1) - $__query_start_time;
		$this->log_perform_data('query_time_taken', $__query_time_taken);
	
		//echo "\n _dbh_result- >num_rows:"; var_export($_dbh_result->num_rows);
		if ($_dbh_result) {
	
			$__query_data_fetch_start_time = microtime(1);
			while ($row = $_dbh_result->$result_format_func()) {
				$table_data[] = $row;
				$rows_count++;
				if( memory_get_usage() > $before_query_relative_memory_limit){
					wpmerge_debug::printr('memory break', 'break');
					break;
				}elseif(wpmerge_is_time_limit_exceeded($time_limit) ){
					wpmerge_debug::printr('time break', 'break');
					break;
				}
			}
			$__query_data_fetch_time_taken = microtime(1) - $__query_data_fetch_start_time;
			$this->log_perform_data('query_data_fetch_time_taken', $__query_data_fetch_time_taken);
			
			$__before_free_results_time = microtime(1);
			$_dbh_result->free_result();//taking more time when more unread rows are there
			$__free_result_time_taken = microtime(1) - $__before_free_results_time;
			$this->log_perform_data('query_free_results_time_taken', $__free_result_time_taken);

			$this->log_perform_data('total_fetched_rows', $rows_count);
		}	
	
		wpmerge_debug::printr($rows_count, 'rows_count');	
		
		$__query_and_get_results_time_taken = microtime(1) - $__before_query_time;
		$this->log_perform_data('query_and_get_results_time_taken', $__query_and_get_results_time_taken);

		$__after_query_data_fetch_memory_usage = memory_get_usage();
		$__inc_mem = $__after_query_data_fetch_memory_usage - $before_query_memory_usage;

		wpmerge_debug::printr($__after_query_data_fetch_memory_usage/(1024*1024), 'current_memory_usage');
		wpmerge_debug::printr($__inc_mem/(1024*1024), 'increased_memory_usage');
		wpmerge_debug::printr($this->perform_data, 'perform_data');

		$this->next_offset = $this->select_offset + $rows_count;
		return $table_data;
	}

	public function get_next_offset(){
		return $this->next_offset;
	}

	public static function get_all_perform_data(){
		return self::$all_perform_data;
	}

	private function log_perform_data($key, $value){//very specific this usage
		$this->perform_data[$key] = $value;
		self::$all_perform_data[$key] += $value;
	}
}

class wpmerge_insert_select_by_memory_limit extends wpmerge_select_by_memory_limit {
	protected $min_limit = 1000;

	function __construct($select_args, $relative_memory_limit=WPMERGE_RELATIVE_MEMORY_LIMIT){

		parent::__construct($select_args, $relative_memory_limit);
	}

	public function get_calculated_limit_by_data_index_len(){

		if( !empty(self::$tables_data_cache[$this->select_table]['calculated_limit_by_data_index_len']) ){//check cache
			return self::$tables_data_cache[$this->select_table]['calculated_limit_by_data_index_len'];
		}

		$total_rows = $this->get_total_rows();
		if($total_rows < $this->min_limit){
			$calculated_limit = $this->min_limit;
			if($calculated_limit > $this->select_limit){
				$calculated_limit = $this->select_limit;
			}

			self::$tables_data_cache[$this->select_table]['calculated_limit_by_data_index_len'] = $calculated_limit;
			return self::$tables_data_cache[$this->select_table]['calculated_limit_by_data_index_len'];
		}

		$_calc_limit_start = microtime(1);
		
		$table_details = $GLOBALS['wpdb']->get_row("SELECT * FROM information_schema.TABLES WHERE TABLE_SCHEMA = '".$this->select_db."' AND TABLE_NAME = '".$this->select_table."'", ARRAY_A);
		$avg_row_length = 0;
		if($total_rows > 0){
			$avg_row_length = ($table_details['DATA_LENGTH'] + $table_details['INDEX_LENGTH']) / $total_rows;
		}

		if($avg_row_length > 0){
			$calculated_limit = ceil( $this->relative_memory_limit / $avg_row_length );
		}
		else{
			$calculated_limit = $this->min_limit;
		}

		if($calculated_limit > $this->select_limit){
			$calculated_limit = $this->select_limit;
		}

		self::$tables_data_cache[$this->select_table]['calculated_limit_by_data_index_len'] = $calculated_limit;
		wpmerge_debug::printr(( microtime(1) - $_calc_limit_start ), 'time_taken_for_calc_limit');

		return self::$tables_data_cache[$this->select_table]['calculated_limit_by_data_index_len'];
	}

	public function get_primary_auto_inc_col(){
		return parent::get_primary_auto_inc_col();
	}

}

// function get_primary_auto_inc_col($table){//code and functionality similar to wpmerge_select_by_memory_limit::get_primary_auto_inc_col();
// 	static $tables_data_cache = array();

// 	if(empty($table)){
// 		return false;
// 	}

// 	if( isset($tables_data_cache[$table]['primary_auto_inc_col']) ){//check cache
// 		return $tables_data_cache[$table]['primary_auto_inc_col'];
// 	}

// 	$db_name = DB_NAME;

// 	$primary_auto_inc_col = $GLOBALS['wpdb']->get_var("SELECT `COLUMN_NAME` FROM `INFORMATION_SCHEMA`.`COLUMNS` as info_cols WHERE `TABLE_SCHEMA` = '".$db_name."' AND `TABLE_NAME` = '".$table."' AND `COLUMN_KEY` = 'PRI' AND `EXTRA` = 'auto_increment'");
	
// 	if(empty($primary_auto_inc_col)){
// 		$primary_auto_inc_col = false;
// 	}
// 	return $tables_data_cache[$table]['primary_auto_inc_col'] = $primary_auto_inc_col;
// }

class wpmerge_db_table_prefix{

	static private $purpose_prepend_list = array(
		'prod_clone_in_dev_testing_db_prefix' => 'tpcd',//tpcd -> Testing table Prod Clone in Dev
		'prod_clone_in_dev_tmp_swap_db_prefix' => 'spcd',//spcd -> Swap table Prod Clone in Dev
		'same_server_clone_in_prod_testing_db_prefix' => 'tscp',//tscp -> Testing table Same server Clone in Dev
		'same_server_clone_in_prod_tmp_swap_db_prefix' => 'sscp'//sscp -> Swap table Same server Clone in Dev
	);

	public static function check_and_get_db_table_prefix($purpose){		

		if( empty(self::$purpose_prepend_list[$purpose]) ){
			return false;
		}

		$prepend = self::$purpose_prepend_list[$purpose];

		//check old prefix which already used
		$existing_prefix = wpmerge_get_option($purpose);

		if( empty($existing_prefix) ){/* || !empty($existing_prefix) && self::check_prefix_has_tables($existing_prefix) */ //checking already generated prefix for table exists commented. Because there is chance if clean up not properly down, then some tables can stay in that prefix, will resulted in creating more prefix and more abandoned table which might increase the size. 
			$new_prefix = self::get_new_db_table_prefix($prepend);
			if(empty($new_prefix)){
				return false;
			}
			wpmerge_update_option($purpose, $new_prefix);
			return wpmerge_get_option($purpose);//to make sure it is saved for later use, instead of returning from variable, returning from db.
		}else{
			return $existing_prefix;
		}		
	}

	private static function get_new_db_table_prefix($prepend){
		$skip_numbers = array();
		$max_attempt = 20;
		
		$i = 0;
		while($i < $max_attempt){
			do {//get rand_num exclude unsuccesful one
				$rand_num = rand(100, 999);
			
			} while(in_array($rand_num, $skip_numbers));

			$prefix = $prepend . $rand_num . '_';
			if( !self::check_prefix_has_tables($prefix) ){
				return $prefix;
			}
			array_push($skip_numbers, $rand_num);
			$i++;
		}
		return false;
	}

	private static function check_prefix_has_tables($prefix){
		$db_name = DB_NAME;
		$escaped_base_prefix = wpmerge_esc_table_prefix($prefix);
		
		$get_tables_sql = "SELECT `TABLE_NAME` FROM `INFORMATION_SCHEMA`.`TABLES` WHERE `TABLE_SCHEMA` = '".$db_name."' AND `TABLE_NAME` LIKE '".$escaped_base_prefix."%' ";

		$result = $GLOBALS['wpdb']->get_row($get_tables_sql);
		if(!empty($result)){
			return true;
		}
		return false;
	}
}

function wpmerge_delete_tables_with_prefix($prefix){
	include_once(WPMERGE_PATH . '/includes/swap_tables.php');
	try{
		$swap_tables_obj = new wpmerge_swap_tables();
		$swap_tables_obj->delete_tmp_swap_tables($prefix);
	}
	catch(wpmerge_exception $e){
		$error = $e->getError();
		if($error != 'tables_missing_for_delete'){//supress error if 'tables_missing_for_delete'
			throw $e;
		}
	}
}

function wpmerge_base64_encode_query($query){

	$base64_prefix = '|--wpm-b64---|';

	if(empty($query) || !is_string($query)){
		return $query;
	}

	if( strpos($query, $base64_prefix) === 0 ){
		//already base64 encoded just return it
		return $query;
	}

	$b64_query =  $base64_prefix . base64_encode($query);
	return $b64_query;
}

function wpmerge_base64_decode_query($query){

	$base64_prefix = '|--wpm-b64---|';

	if(empty($query) || !is_string($query)){
		return $query;
	}

	$query = trim($query);

	if( strpos($query, $base64_prefix) !== 0 ){
		return $query;
	}

	$query = wpmerge_remove_prefix($base64_prefix, $query);
	return base64_decode($query);
}

function wpmerge_get_query_from_row($query){//to select data from query_b or query column
	if(is_object($query)){
		if( !isset($query->query_b) || empty($query->query_b) ){
			return $query->query;
		}
	
		return wpmerge_base64_decode_query($query->query_b);
	}

	if(is_array($query)){
		if( !isset($query['query_b']) || empty($query['query_b']) ){
			return $query['query'];
		}
	
		return wpmerge_base64_decode_query($query['query_b']);
	}
}

function wpmerge_get_unique_table_names_from_log_queries($add_this_prefix=''){
	$table_names_without_prefix = $GLOBALS['wpdb']->get_col("SELECT DISTINCT `table_name` FROM `". $GLOBALS['wpdb']->base_prefix ."wpmerge_log_queries` WHERE `is_record_on` = '1' AND `type` = 'query'");
	if(empty($table_names_without_prefix)){
		return false;
	}

	$table_names_without_prefix = array_filter($table_names_without_prefix);//to remove NULL and empty string

	if(!empty($add_this_prefix) && is_string($add_this_prefix)){
		foreach($table_names_without_prefix as $key => $table_name_without_prefix){
			$table_names_without_prefix[$key] = $add_this_prefix.wpmerge_remove_prefix($add_this_prefix, $table_name_without_prefix);
		}
	}

	return $table_names_without_prefix;
}

function wpmerge_purge_wp_cache(){
	//currently supports 6 famous cache plugins only

	include_once ( WPMERGE_PATH . '/includes/common_clear_cache.php' );

	$common_clear_cache_obj = new wpmerge_common_clear_cache();
	$common_clear_cache_obj->purgeAllCacheOfCachingPlugins();

	return true;
}