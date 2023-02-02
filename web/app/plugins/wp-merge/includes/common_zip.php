<?php
/**
 * WPMerge
 * Copyright (c) 2018 Revmakx LLC
 * revmakx.com
 */

if(!defined('ABSPATH')){ exit; }

if ( ! defined('ABSPATH') ){
    die();
}

class wpmerge_zip{

    private $method;
    private $zip;
    private $zip_file;
    private $offset;
    private $wpdb;
    private $is_finished;
    private $current_size = 0;

    const MAX_BATCH_FILES = 50;
    const MAX_BATCH_FILES_SIZE = 20971520; //20 MB

    public function __construct(){
        // define('WPMERGE_PREFER_PCLZIP', true);
        $this->init();
        $this->do_zipping();
        $this->reset();
        $this->send_response();
    }

    private function init(){
        $this->init_db();
        $this->choose_zip_method();
        $this->set_zip_file();
        $this->set_offset();
    }

    private function init_db(){
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    private function set_zip_file(){
        if(!file_exists(WPMERGE_TEMP_DIR)){
			if(!mkdir(WPMERGE_TEMP_DIR, 0775, true)){
                throw new wpmerge_exception('tmp_make_dir_failed');
            }
        }
		
        $this->zip_file = WPMERGE_TEMP_DIR . '/' . hash_hmac('sha1', time(), uniqid(mt_rand(), true)) . '-changed_files.zip';
    }

    private function set_offset(){
        $offset = wpmerge_get_option('dev_to_prod_zipping_offset');
        $this->offset = empty($offset) ? 0 : $offset;
    }

    private function choose_zip_method(){
        $this->method = $this->is_pclzip_must_use() || !$this->is_zip_archive_available() ? 'wpmerge_pclzip' : 'wpmerge_zipArchive';
        $this->zip = new $this->method;
    }

    private function is_pclzip_must_use(){
        if ( defined('WPMERGE_PREFER_PCLZIP') && WPMERGE_PREFER_PCLZIP == true) {
            return true;
        }

        return false;
    }

    private function is_zip_archive_available(){
        if ( !class_exists('ZipArchive') || !class_exists('wpmerge_zipArchive') || (!extension_loaded('zip') && !method_exists('ZipArchive', 'AddFile') )) {
            return false;
        }

        return true;
    }

    private function open(){
        if (file_exists($this->zip_file)) {
            $response = $this->zip->open($this->zip_file);
            clearstatcache();
        } else {
            $create_code = defined('ZIPARCHIVE::CREATE') ? ZIPARCHIVE::CREATE : 1;
            $response = $this->zip->open($this->zip_file, $create_code);
        }

        if ($response !== true) {
            throw new wpmerge_exception('zip_error', "Failed to open the zip file ($zipfile) - $zip->last_error");
        }
    }

    private function close(){
        wpmerge_debug::log(__FUNCTION__ .'_start');
        wpmerge_debug::log_resource_usage(__FUNCTION__ .'_start');

        if(!$this->zip->close()){
            wpmerge_debug::log($this->zip->last_error,'-----------$this->zip->last_error----------------');
        }
        wpmerge_debug::log(__FUNCTION__ .'_end');
        wpmerge_debug::log_resource_usage(__FUNCTION__ .'_end');

    }

    private function do_zipping(){
        wpmerge_debug::log_resource_usage(__FUNCTION__ .'_start');

        $break = false;

        while ( !$break ) {
           $this->open();
           $this->add_files();
           $this->close();
           $this->increase_offset();
           $this->check_time_out_zip_archive_zipping();
           $break = $this->is_finished();
       }
        wpmerge_debug::log_resource_usage(__FUNCTION__ .'_end');
        wpmerge_update_option('changed_zip_file', $this->zip_file);
    }

    private function is_finished(){
        return $this->is_finished;
    }

    private function set_is_finished($value){
        $this->is_finished = $value;
    }

    private function check_time_out_pzl_zipping(){
        if(!wpmerge_is_time_limit_exceeded()){
            return ;
        }

        $this->close();
        $this->save_state();
        throw new wpmerge_exception("timedout");//used for non error throwing purpose
    }

    private function check_time_out_zip_archive_zipping(){
        if(!wpmerge_is_time_limit_exceeded( $time = 20 )){
            return ;
        }

        $this->save_state();
        throw new wpmerge_exception("timedout");//used for non error throwing purpose
    }

    private function add_files(){

        $files = $this->get_files();
        $abspath = wp_normalize_path(ABSPATH);

        foreach ($files as $key => $file ) {
            
            $add_as = str_replace($abspath, '', $file->path);
            $this->zip->addFile($file->path, $add_as);
            $this->add_zip_status($file->size);
            $this->check_zip_limits();
        }

    }

    private function add_zip_status($size){
        $this->current_size += $size;
    }

    private function check_zip_limits(){
        if ($this->current_size < self::MAX_BATCH_FILES_SIZE) {
            return ;
        }

        wpmerge_debug::log(__FUNCTION__ .'crossed' . $this->current_size, '');

        $this->close();
        $this->open();
        $this->current_size = 0;
    }

    private function increase_offset(){
        $this->offset += self::MAX_BATCH_FILES;
    }

    private function get_files(){

        $response = $this->wpdb->get_results("SELECT `path`, `size` FROM " . $this->wpdb->base_prefix . "wpmerge_process_files WHERE `type` = 'file' AND `group` = 'dev_to_prod' LIMIT " . $this->offset .", " . self::MAX_BATCH_FILES);

        if (empty($response)) {
            $this->set_is_finished(true);
            return array();
        }

        $this->set_is_finished(false);

        return $response;

    }

    private function save_state(){

        wpmerge_update_option('dev_to_prod_zipping_offset', $this->offset);

    }

    private function reset(){

        $this->truncate_table();
        $this->reset_flags();

    }

    private function truncate_table(){

        wpmerge_wpdb::query("TRUNCATE TABLE " . $this->wpdb->base_prefix . "wpmerge_process_files");

    }

    private function reset_flags(){

        wpmerge_delete_option('dev_to_prod_zipping_offset');

    }

    private function send_response(){
        throw new wpmerge_exception("finished");//used for non error throwing purpose
    }
}

if (class_exists('ZipArchive')){
    class wpmerge_zipArchive extends ZipArchive {
        public $last_error = 'Unknown: ZipArchive does not return error messages';
    }
}

# A ZipArchive compatibility layer, with behaviour sufficient for our usage of ZipArchive
class wpmerge_pclzip {

    protected $pclzip;
    protected $path;
    protected $addfiles;
    protected $adddirs;
    private   $statindex;
    private   $include_mtime = false;
    public    $last_error;

    public function __construct() {
        $this->addfiles = array();
        $this->adddirs = array();
        // Put this in a non-backed-up, writeable location, to make sure that huge temporary files aren't created and then added to the backup - and that we have somewhere writable

        $this->set_backup_location();
    }

    //rewrite
    private function set_backup_location(){

        if (!defined('PCLZIP_TEMPORARY_DIR')){
            define('PCLZIP_TEMPORARY_DIR', $this->get_backup_location());
        }

    }

    //rewrite
    private function get_backup_location(){
        return WPMERGE_TEMP_DIR;
    }

    # Used to include mtime in statindex (by default, not done - to save memory; probably a bit paranoid)
    public function ud_include_mtime() {
        $this->include_mtime = true;
    }

    public function __get($name) {

        if ($name != 'numFiles' && $name != 'numAll') {
            return null;
        }

        if (empty($this->pclzip)){
            return false;
        }

        $statindex = $this->pclzip->listContent();

        if (empty($statindex)) {
            $this->statindex = array();
            // We return a value that is == 0, but allowing a PclZip error to be detected (PclZip returns 0 in the case of an error).
            if (0 === $statindex) $this->last_error = $this->pclzip->errorInfo(true);
            return (0 === $statindex) ? false : 0;
        }

        if ($name == 'numFiles') {

            $result = array();

            foreach ($statindex as $i => $file) {
                if (!isset($statindex[$i]['folder']) || 0 == $statindex[$i]['folder']) {
                    $result[] = $file;
                }
                unset($statindex[$i]);
            }

            $this->statindex = $result;

        } else {
            $this->statindex = $statindex;
        }

        return count($this->statindex);

    }

    public function statIndex($i) {
        if (empty($this->statindex[$i])){
            return array('name' => null, 'size' => 0);
        }

        $v = array(
            'name' => $this->statindex[$i]['filename'],
            'size' => $this->statindex[$i]['size']
        );

        if ($this->include_mtime){
            $v['mtime'] = $this->statindex[$i]['mtime'];
        }

        return $v;
    }

    private function include_pclzip(){
        if(!class_exists('PclZip')){
            include_once(ABSPATH . '/wp-admin/includes/class-pclzip.php');
        }
    }

    private function is_pclzip_available(){
        if(!class_exists('PclZip')) {
            $this->last_error = "No PclZip class was found";
            return false;
        }

        return true;
    }

    public function open($path, $flags = 0) {

        $this->include_pclzip();

        if (!$this->is_pclzip_available()) {
            return false;
        }

        # Route around PHP bug (exact version with the problem not known)
        $ziparchive_create_match = (version_compare(PHP_VERSION, '5.2.12', '>') && defined('ZIPARCHIVE::CREATE')) ? ZIPARCHIVE::CREATE : 1;

        if ($flags == $ziparchive_create_match && file_exists($path)){
            @unlink($path);
        }

        $this->pclzip = new PclZip($path);

        if (empty($this->pclzip)) {
            $this->last_error = 'Could not get a PclZip object';
            return false;
        }

        // $wpmerge_backup_dir = $this->get_backup_location();

        # Make the empty directory we need to implement addEmptyDir()
        // if (!is_dir($wpmerge_backup_dir.'/emptydir') && !mkdir($wpmerge_backup_dir.'/emptydir')) {
        //     $this->last_error = "Could not create empty directory ($wpmerge_backup_dir/emptydir)";
        //     return false;
        // }

        $this->path = $path;

        return true;

    }

    # Do the actual write-out - it is assumed that close() is where this is done. Needs to return true/false
    public function close() {
        if (empty($this->pclzip)) {
            $this->last_error = 'Zip file was not opened';
            return false;
        }

        $wpmerge_backup_dir = $this->get_backup_location();

        $activity = false;

        # Add the empty directories
        foreach ($this->adddirs as $dir) {
            if (false == $this->pclzip->add($wpmerge_backup_dir.'/emptydir', PCLZIP_OPT_REMOVE_PATH, $wpmerge_backup_dir.'/emptydir', PCLZIP_OPT_ADD_PATH, $dir)) {
                $this->last_error = $this->pclzip->errorInfo(true);
                return false;
            }
            $activity = true;
        }

        foreach ($this->addfiles as $rdirname => $adirnames) {
            foreach ($adirnames as $adirname => $files) {
                if (false == $this->pclzip->add($files, PCLZIP_OPT_REMOVE_PATH, $rdirname, PCLZIP_OPT_ADD_PATH, $adirname)) {
                    $this->last_error = $this->pclzip->errorInfo(true);
                    return false;
                }
                $activity = true;
            }
            unset($this->addfiles[$rdirname]);
        }

        $this->pclzip   = false;
        $this->addfiles = array();
        $this->adddirs  = array();

        clearstatcache();

        if ($activity && filesize($this->path) < 50) {
            $this->last_error = "Write failed - unknown cause (check your file permissions)";
            return false;
        }

        return true;
    }

    # Note: basename($add_as) is irrelevant; that is, it is actually basename($file) that will be used. But these are always identical in our usage.
    public function addFile($file, $add_as) {
        # Add the files. PclZip appears to do the whole (copy zip to temporary file, add file, move file) cycle for each file - so batch them as much as possible. We have to batch by dirname(). On a test with 1000 files of 25KB each in the same directory, this reduced the time needed on that directory from 120s to 15s (or 5s with primed caches).
        $rdirname = dirname($file);
        $adirname = dirname($add_as);
        $this->addfiles[$rdirname][$adirname][] = $file;
    }

    # PclZip doesn't have a direct way to do this
    public function addEmptyDir($dir) {
        $this->adddirs[] = $dir;
    }

    public function extract($path_to_extract, $path) {
        return $this->pclzip->extract(PCLZIP_OPT_PATH, $path_to_extract, PCLZIP_OPT_BY_NAME, $path);
    }

}