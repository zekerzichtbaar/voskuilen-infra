<?php
/**
 * WPMerge
 * Copyright (c) 2018 Revmakx LLC
 * revmakx.com
 */

if(!defined('ABSPATH')){ exit; }

$globalCon =  '';

if (!class_exists('wpmerge_inside_wordpress')) {
    class wpmerge_inside_wordpress
    {
        private $db_name;
        private $db_user;
        private $db_password;
        private $db_host;
        private $db_prefix;


        /**
         * Set database name
         * @param NULL
         * @return String
         */
        public function getDBName()
        {
            return $this->db_name;
        }

        /**
         * Set database username
         * @param NULL
         * @return String
         */
        public function getDBUser()
        {
            return $this->db_user;
        }

        /**
         * Set database password
         * @param NULL
         * @return String
         */
        public function getDBPassword()
        {
            return $this->db_password;
        }

        /**
         * Set database hostname
         * @param NULL
         * @return String
         */
        public function getDBHost()
        {
            return $this->db_host;
        }

        /**
         * Set database table prefix
         * @param NULL
         * @return String
         */
        public function getDBPrefix()
        {
            global $wpdb;
            return $wpdb->base_prefix;
        }

        /**
         * Connect the database
         * @param NULL
         * @return database resource
         */

        public function connectDB()
        {
            global $globalCon, $wpdb;
            $globalCon = $wpdb;
            $this->getDB();
            return $globalCon;
        }

        public function getDB(){

            global $globalCon;            
            //$globalCon->set_charset($globalCon->dbh,"utf8");I think wordpress takes care
            //$globalCon->query("SET GLOBAL max_allowed_packet=268435456");//commented because issue in client 
        }

        public function isWordPress(){
            return true;
        }

        public function isMultiSite(){
           return is_multisite();
        }

    }
}

if (!class_exists('wpmerge_common_exim')) {
    class wpmerge_common_exim extends wpmerge_inside_wordpress{

        private $isAuth             = false;
        private $isValidRequest     = false;
        private $request            = array();
        private $response           = array();
        private $configParams       = array();
        private $action             = null;
        private $responseMode       = 'JSON';
        private $dir                = '';
        private $file               = '';
        private $files              = array();
        private $content            = '';
        private $overwrite          = true;
        private $debugMode          = true;
        private $encript            = true;
        private $deep               = false;
        private $authKey            = '';

        private $dirHashSizeLimit   = 15000000;
        private $fileStreamMaxSize  = 1000000;           // 1000 KB
        private $sqlRunMaxQueryLimit= 1000;
        private $sqlMaxQueryLimit   = 25000;           // 300 for development 300 for production
        private $scriptStartTime    = 0;
        private $maxBreakTime       = WPMERGE_TIMEOUT;               // 30 Sec
        private $offset             = 0;
        private $maxOutputStrLimit  = 48 * 1024 * 1024;//48 MB(Consider 2MB for other print) used in getFileGroup() to limit total length of string printed
        private $currentOutputStrSize  = 0;//used in getFileGroup()

        private $platform = '';
        private $is_remote = '';
        private $is_phpdump = '';
        private $dbprefix = '';
        private $newDBprefix = '';
        private $oldTablesList = array();
        private $newTablesList = array();

        private $tmpFolder          = 'tmp';
        private $tmpFilePrefix      = '';
        private $table              = "";

        private $ftpServer          = "localhost";
        private $ftpPort            = 21;
        private $ftpUser            = "";
        private $ftpPass            = "";
        private $ftpBasePath        = "";
        private $ftpPath            = "";
        private $metaOffset         = "";
        private $metaFiles          = array();
        private $fileSeek           = 0;
        private $writeFilesArray    = array();
        private $tempQueryCount     = 0;
        private $tempQuerySize      = 0;
        private $tempQueryTable     = '';

        private $findandreplace     = array();

        private $content_dir        = "./wp-content";
        private $abspath            = "./";

        private $excludeArray       = array();

        /**
         * Check is authenticate request
         * @param NULL
         * @return Boolean
         */
        private function auth()
        {
            if(wpmerge_is_prod_env()){
                wpmerge_prod_check_auth_and_dev_compatability_may_exit_w_msg();
            }
            return true;//local
        }

        /**
         * LovalSync Constructor
         * @param Array
         */
        public function __construct($request)
        {
            $this->content_dir        = defined('WP_CONTENT_DIR') ? untrailingslashit(WP_CONTENT_DIR) : "./wp-content";
            $this->abspath            = defined('ABSPATH') ? untrailingslashit(ABSPATH) : "./";
            $this->content_dir        = trim($this->content_dir);
            $this->abspath            = trim($this->abspath);
            
            if(defined('WPMERGE_START_TIME')){
                $this->scriptStartTime = WPMERGE_START_TIME;
            }
            else{
                $this->scriptStartTime = microtime(1);
            }

            $this->request = $request;
            $this->process();
        }

        /**
         * Get request array
         * @param NULL
         * @return Array
         */
        public function request()
        {
            return $this->request;
        }

        /**
         * Setup response
         * @param NULL
         * @return NULL
         */
        public function response()
        {
            $this->buildResponse();
        }

        /**
         * Check is valid request
         * @param NULL
         * @return Boolean
         */
        private function isValidRequest()
        {
            if (is_array($this->request)) {
                if (array_key_exists('action', $this->request)) {
                    $this->setAction($this->request['action']);
                    if (array_key_exists('responseMode', $this->request)) {
                        $this->setResponseMode($this->request['responseMode']);
                    }
                    if (array_key_exists('dir', $this->request)) {
                        $this->setDir($this->request['dir']);
                    }
                    if (array_key_exists('file', $this->request)) {
                        $this->setFile($this->request['file']);
                    }
                    if (array_key_exists('files', $this->request)) {
                        $this->setFiles($this->request['files']);
                    }
                    if (array_key_exists('content', $this->request)) {
                        $this->setContent($this->request['content']);
                    }
                    if (array_key_exists('streamsize', $this->request)) {
                        $this->setStreamSize($this->request['streamsize']);
                    }
                    if (array_key_exists('platform', $this->request)) {
                        $this->setPlatform($this->request['platform']);
                    }
                    if (array_key_exists('is_remote', $this->request)) {
                        $this->setSource($this->request['is_remote']);
                    }
                    if (array_key_exists('db_prefix', $this->request)) {
                        $this->setDBPrefix($this->request['db_prefix']);
                    }
                    if (array_key_exists('new_db_prefix', $this->request)) {
                        $this->setNewDBPrefix($this->request['new_db_prefix']);
                    }
                    if (array_key_exists('old_tables_list', $this->request)) {
                        $this->setOldTablesList($this->request['old_tables_list']);
                    }
                    if (array_key_exists('new_tables_list', $this->request)) {
                        $this->setNewTablesList($this->request['new_tables_list']);
                    }
                    if (array_key_exists('is_phpdump', $this->request)) {
                        $this->setPHPDump($this->request['is_phpdump']);
                    }

                    if (array_key_exists('overwrite', $this->request)) {
                        $this->setOverwrite($this->request['overwrite']);
                    }
                    if (array_key_exists('offset', $this->request)) {
                        $this->setOffset($this->request['offset']);
                    }
                    if (array_key_exists('deep', $this->request)) {
                        $this->setDeep($this->request['deep']);
                    }
                    if (array_key_exists('table', $this->request)) {
                        $this->setTables($this->request['table']);
                    }
                    if (array_key_exists('ftp', $this->request)) {
                        $this->setFTP($this->request['ftp']);
                    }
                    if (array_key_exists('findandreplace', $this->request)) {
                        $this->setFindAndReplace($this->request['findandreplace']);
                    }
                    if (array_key_exists('configParams', $this->request)) {
                        $this->setConfigParams($this->request['configParams']);
                    }
                    if (array_key_exists('authKey', $this->request)) {
                        $this->setConfigParams($this->request['authKey']);
                    }
                    if (array_key_exists('maxBreakTime', $this->request)) {
                        $this->setMaxBreakTime($this->request['maxBreakTime']);
                    }
                    return true;
                } else {
                    return false;
                }
            } elseif (is_object($this->request)) {
                $request = $this->request;
                if (isset($request->action)) {
                    $this->setAction($request->action);
                    if (isset($request->responseMode)) {
                        $this->setResponseMode($request->responseMode);
                    }
                    if (isset($request->dir)) {
                        $this->setDir($request->dir);
                    }
                    if (isset($request->file)) {
                        $this->setFile($request->file);
                    }
                    if (isset($request->files)) {
                        $this->setFiles($request->files);
                    }
                    if (isset($request->content)) {
                        $this->setContent($request->content);
                    }
                    if (isset($request->streamsize)) {
                        $this->setStreamSize($request->streamsize);
                    }
                     if (isset($request->platform)) {
                        $this->setPlatForm($request->platform);
                    }
                     if (isset($request->is_remote)) {
                        $this->setSource($request->is_remote);
                    }
                    if (isset($request->db_prefix)) {
                        $this->setDBPrefix($request->db_prefix);
                    }
                    if (isset($request->new_db_prefix)) {
                        $this->setNewDBPrefix($request->new_db_prefix);
                    }
                    if (isset($request->old_tables_list)) {
                        $this->setOldTablesList($request->old_tables_list);
                    }
                    if (isset($request->new_tables_list)) {
                        $this->setNewTablesList($request->new_tables_list);
                    }
                    if (isset($request->is_phpdump)) {
                        $this->setPHPDump($request->is_phpdump);
                    }
                    if (isset($request->overwrite)) {
                        $this->setOverwrite($request->overwrite);
                    }
                    if (isset($request->offset)) {
                        $this->setOffset($request->offset);
                    }
                    if (isset($request->deep)) {
                        $this->setDeep($request->deep);
                    }
                    if (isset($request->table)) {
                        $this->setTables($request->table);
                    }
                    if (isset($request->ftp)) {
                        $this->setFTP($request->ftp);
                    }
                    if (isset($request->findandreplace)) {
                        $this->setFindAndReplace($request->findandreplace);
                    }
                    if (isset($request->configParams)) {
                        $this->setConfigParams($request->configParams);
                    }
                    if (isset($request->authKey)) {
                        $this->setAuthKey($request->authKey);
                    }
                    if (isset($request->maxBreakTime)) {
                        $this->setMaxBreakTime($request->maxBreakTime);
                    }
                    return true;
                } else {
                    return false;
                }
            }
        }

        function normalize_path( $path ) {
            $path = str_replace( '\\', '/', $path );
            $path = preg_replace( '|(?<=.)/+|', '/', $path );
            if ( ':' === substr( $path, 1, 1 ) ) {
                $path = ucfirst( $path );
            }
            return $path;
        }

        private function setAuthKey($authKey)
        {
            $this->authKey = $authKey;
        }

        private function setMaxBreakTime($maxBreakTime)
        {
            $this->maxBreakTime = $maxBreakTime;
        }

        /**
         * Check max execution time
         * @return Boolean
         */
        private function checkTimeBreak()
        {
            $extractTimeTaken = microtime(1) - $this->scriptStartTime;
            if ($extractTimeTaken >= $this->maxBreakTime) {
                return true;
            }
        }

        private function willMaxOutputStrLimitReach($newBufferSize)
        {
            $newOutputStrSize = $this->currentOutputStrSize + $newBufferSize;
            if ($newOutputStrSize >= $this->maxOutputStrLimit) {
                return true;
            }
            return false;
        }

        private function checkMaxOutputStrLimitAndEcho($str)
        {//return false => str not printed, return true => str printed and currentOutputStrSize value changed
            $newBufferSize = strlen($str);
            if( $this->willMaxOutputStrLimitReach( $newBufferSize ) ){
                wpmerge_debug::log(array('currentOutputStrSize' => $this->currentOutputStrSize, 'newBufferSize' => $newBufferSize), 'this_call_maxOutputStrLimit_reached');
                return false;
            }
            echo $str;
            $this->currentOutputStrSize += $newBufferSize;
            return true;
        }

        /**
         * Translation
         * @param String
         * @return String
         */
        private function lang($content)
        {
            return $content;
        }

        /**
         * Set invalid action values
         * @param NULL
         * @return Array
         */
        public function invalidAction()
        {
            $this->setResponse(100);
        }

        /**
         * Set API Action
         * @param String
         * @return NULL
         */
        public function setAction($action)
        {
            $this->action = $action;
        }

        /**
         * Set API response mode
         * @param String [JSON|XML|PLAIN|HTML]
         * @return Boolean
         */
        public function setResponseMode($mode)
        {
            $this->responseMode = $mode;
        }

        /**
         * Set ConfigParams
         * @param Object
         */
        public function setConfigParams($configParams)
        {
            $this->configParams = $configParams;
        }

        /**
         * Error code messages
         * @param Int
         * @return String
         */
        public function getErrorCodeRef($code = 100)
        {
            $preCode = array(
                99 => 'Auth Failed',
                100 => 'Invalid Request',
                101 => 'Invalid Directory',
                102 => 'Invalid File',
                103 => 'Required Field Missing',
                104 => 'Failed to create folder',
                105 => 'Failed to remove folder',
                106 => 'Invalid Site',
                107 => 'SQL Error',
                108 => 'WP Config Could not modified',
                109 => 'Connected successfully',
                110 => 'Could not write MySQL in the temp folder',
                111 => 'Table doesn\'t exist',
                112 => 'File list empty',
                113 => 'gz is not available',
                114 => 'Table is not view table',

                200 => 'Hash Created',
                201 => 'Modified Time Created',
                202 => 'File Created',
                203 => 'Directory Meta Created',
                204 => 'File Created',
                205 => 'Folder Created',
                206 => 'File Overwritted',
                207 => 'File Appended, Waiting for next eof',
                208 => 'Folder Removed',
                209 => 'File array created',
                210 => 'SQL Flushed',
                211 => 'SQL Tables Listed',
                212 => 'SQL Sync successfully',
                213 => 'Find and Replace Successfully',
                214 => 'File Removed Successfully',
                215 => 'Exclude files',
                216 => 'WP Config modified Successfully',
                217 => 'Compression resumed',
                218 => 'Compression finished'

            );
            if ($code) {
                if (!array_key_exists($code, $preCode)) {
                    $code = 100;
                }
                return $this->lang($preCode[$code]);
            }
        }

        /**
         * Set response array
         * @param Int
         * @param String
         * @return NULL
         */
        public function setResponse($code = 100, $value = '')
        {
            if ($code) {
                $this->response['code']    = $code;
                $this->response['message'] = $this->getErrorCodeRef($code);
            }
            if ($value) {
                $this->response['value'] = $value;
            }
            /*if ($this->request) {
                $this->response['request'] = $this->request;
            }*/
        }

        /**
         * Set dir
         * @param String
         * @return NULL
         */
        public function setDir($dir)
        {
            $this->dir = $dir;
        }

        /**
         * Set file
         * @param String
         * @return NULL
         */
        public function setFile($file)
        {
            $this->file = $file;
        }

        /**
         * Set files array
         * @param Array
         * @return NULL
         */
        public function setFiles($file)
        {
            $this->files = $file;
        }

        /**
         * Set file content
         * @param String
         * @return NULL
         */
        public function setContent($content)
        {
            $this->content = $content;
        }

        /**
         * Set overwrite
         * @param Boolean
         * @return NULL
         */
        public function setOverwrite($mode)
        {
            $this->overwrite = $mode;
        }

        /**
         * Set file offset
         * @param Int
         * @return NULL
         */
        public function setOffset($offset)
        {
            $this->offset = $offset;
        }


        public function setPlatform($platform)
        {
            $this->platform = $platform;
        }

        public function setSource($source)
        {
            $this->is_remote = $source;
        }
        public function setDBPrefix($prefix)
        {
            $this->dbprefix = $prefix;
        }
        public function setNewDBPrefix($prefix)
        {
            $this->newDBprefix = $prefix;
        }
        public function setOldTablesList($oldTablesList)
        {
            $this->oldTablesList = $oldTablesList;
        }
        public function setNewTablesList($newTablesList)
        {
            $this->newTablesList = $newTablesList;
        }
         public function setPHPDump($dump)
        {
            $this->is_phpdump = $dump;
        }
        /**
         * Set directory find mode
         * @param Boolean
         * @return NULL
         */
        public function setDeep($deep)
        {
            $this->deep = $deep;
        }

        /**
         * Set directory find mode
         * @param Boolean
         * @return NULL
         */
        public function setTables($table)
        {
            $this->table = $table;
        }

        /**
         * Set FTP Details
         * @param Boolean
         * @return NULL
         */
        public function setFTP($ftp)
        {
            $this->ftpServer  = $ftp->host;
            $this->ftpPort    = $ftp->port;
            $this->ftpUser    = $ftp->user;
            $this->ftpPass    = $ftp->pass;
            $this->ftpPath    = $ftp->path;
        }

        /**
         * Set Find and Replace values
         * @param Array
         */
        public function setFindAndReplace($findandreplace)
        {
            if(is_array($findandreplace)){
                $findandreplace = (object) $findandreplace;
            }
            $this->findandreplace = $findandreplace;
        }

        /**
         * Set content type
         * @param String [JSON|XML|PLAIN|HTML]
         * @return NULL
         */
        public function setHttpHeaders($contentType = 'PLAIN')
        {
            if(isset($this->response['code']))
            {
            $badCodes = array(99,100,101,102,103,104,105,106,107,108,110,111);
            if(in_array($this->response['code'], $badCodes)){
                //header('HTTP/1.0 400 Forbidden');
            }
            }
            if ($contentType == 'JSON') {
                header('Content-Type: application/json');
            } elseif ($contentType == 'XML') {
                header("Content-type: text/xml");
            } elseif ($contentType == 'HTML') {
                header("Content-type: text/html");
            } else {
                header("Content-type: text/plain");
            }
        }

        /**
         * Build output
         * @param NULL
         * @return string [JSON|XML|PLAIN|HTML]
         */
        public function buildResponse()
        {
            // global $globalCon;
            // if ($globalCon != '') {
            //     mysqli_close($globalCon);
            // }
            $mode = $this->responseMode;
            if ($mode == 'JSON') {
                //$this->setHttpHeaders($mode);//commented because json will wrapped
                echo $response = wpmerge_prepare_response($this->response);
            } elseif ($mode == 'XML') {
                $this->setHttpHeaders($mode);
                echo "<?xml version='1.0' encoding='ISO-8859-1'?>";
                echo "<note>";
                echo "<message>" . $this->lang("Not available at this time, Try JSON responseMode") . "</message>";
                echo "</note>";
            } elseif ($mode == 'HTML') {
                $this->setHttpHeaders($mode);
                echo $this->lang("Not available at this time, Try JSON responseMode");
            } elseif ($mode == 'PLAIN') {
                $this->setHttpHeaders($mode);
                echo $this->lang("Not available at this time, Try JSON responseMode");
            } else {
                $this->setHttpHeaders();
                echo $this->lang("Invalid responseMode");
            }
        }

        public function getResponse(){
            return $this->response;
        }

        /**
         * @param string [What to add the trailing slash to]
         * @return string [With trailing slash added]
         */
        public function trailingslashit($string)
        {
            return $this->untrailingslashit($string) . '/';
        }

        /**
         * @param string [What to remove the trailing slashes from]
         * @return string [without the trailing slashes]
         */
        public function untrailingslashit($string)
        {
            return rtrim($string, '/\\');
        }

        /**
         * @param string
         * @param array|string|null $extensions
         * @param int
         * @param string
         * @return array|false
         */
        private function scandir($path, $extensions = null, $depth = 0, $relative_path = '')
        {
            if (!is_dir($path)) {
                return false;
            }

            if ($extensions) {
                $extensions  = (array) $extensions;
                $_extensions = implode('|', $extensions);
            }

            $relative_path = $this->trailingslashit($relative_path);
            if ('/' == $relative_path) {
                $relative_path = '';
            }

            $results = scandir($path);
            $files   = array();

            $exclusions = array();

            foreach ($results as $result) {
                if ('.' == $result[0] || in_array($result, $exclusions, true)) {
                    continue;
                }
                if (is_dir($path . '/' . $result)) {
                    if (!$depth) {
                        continue;
                    }
                    $found = self::scandir($path . '/' . $result, $extensions, $depth - 1, $relative_path . $result);
                    $files = array_merge_recursive($files, $found);
                } elseif (!$extensions || preg_match('~\.(' . $_extensions . ')$~', $result)) {
                    $files[$relative_path . $result] = $path . '/' . $result;
                }
            }
            return $files;
        }

        /**
         * Get md5 value for directory
         * @param String
         * @return String
         */
        public function hashDirectory($directory)
        {
            $files = array();
            $dir   = dir($directory);
            while (false !== ($file = $dir->read())) {
                if ($file != '.' and $file != '..') {
                    if (is_dir($directory . '/' . $file)) {
                        $files[] = $this->hashDirectory($directory . '/' . $file);
                    } else {
                        $files[] = md5_file($directory . '/' . $file);
                    }
                }
            }
            $dir->close();
            return md5(implode('', $files));
        }

        /**
         * Get md5 value for file
         * @param String
         * @return String
         */
        public function hashFile($file)
        {
            $file_size = filesize($file);
            if ($file_size <= $this->dirHashSizeLimit) {
                if ($file != '.' and $file != '..') {
                    if (is_file($file)) {
                        $file = md5_file($file);
                    }
                }
                return $file;
            } else {
                return false;
            }
        }

        /**
         * Get file meta obj
         * @param String
         * @return Object
         */
        public function getSingleIteratorObj($path, $deep)
        {
            $path   = rtrim($path, '/');
            $source = realpath($path);
            //print_r($deep);
            if ($deep == 'true') {
                $obj = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);
            } else {
                $obj = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::CATCH_GET_CHILD);
            }
            return $obj;
        }

    public function recusiveIteration($iterator,$keyV=false,$depth=0,$seek=false) {
    //echo $path;


        $depth=$depth+1;

        if($seek && $this->fileSeek<count($seek))
        {

            $this->fileSeek++;
            $iterator->seek($seek[$depth-1]);
            if((count($seek) -1) == ($depth-1))
            $iterator->next();

        }
        while ($iterator->valid()) {

            //$iterator->seek(4);

            if($keyV)
            {
                $keyv = $keyV."-".$iterator->key();
            }
            else {
                $keyv=$iterator->key();
            }



        //  echo $seekCount."-".count($seek)."<br>";

        if(!$seek || ($seek && $this->fileSeek>=count($seek))) {
            $file_name= $this->doUtf8($iterator->getFilename());
            $file_path  = $this->doUtf8($iterator->getPathname());

            if ($file_name == '.' || $file_name == '..' || !$iterator->isReadable()) {
                $iterator->next();
                continue;
                        }
            if($iterator->getsize() > $this->fileStreamMaxSize){
                $iterator->next();
                        // echo $file_path;
                        // echo $iterator->getsize();
                        // exit;
                        continue;
            }


            if (is_file($file_path) &&  !in_array($iterator->getPath()."/".$iterator->getFilename(),$this->excludeArray)) {
                $file_hash = $this->hashFile($file_path);
            } else {
                $file_hash = false;
            }
            if (is_dir($file_path)) {
                $is_dir = true;
            } else {
                $is_dir = false;
            }
            //$curr_path = getcwd();

            //$abs_path = str_replace($curr_path, '', $file_path);
            $abs_path = $file_path;
            if(isset($file_name) && $file_name!='' && isset($file_path) && $file_path!='' &&  !in_array($iterator->getPath()."/".$iterator->getFilename(),$this->excludeArray)){
                $this->metaFiles[] = array(
                    //'org_path' => $file_path,
                    'path' => $this->normalizeMac($abs_path),
                    'path_hash'=>md5($this->normalizeMac($abs_path)),
                    'name' => $file_name,
                    'size' => $iterator->getsize(),
                    'mtime' => $iterator->getMTime(),
                    'file_hash' => $file_hash,
                    'is_dir' => $is_dir
                );
            }

        }
        if($this->checkTimeBreak())
        {
            $opt = array(
                    'files' =>  $this->metaFiles,
                    'eof' => false,
                    'offset' => $keyv
                );
            $this->metaOffset=$opt;

            throw new Exception("t");

                  //break;
        }
        if ($iterator->isDir() && !$iterator->isDot()) {

            $niterator = new DirectoryIterator($iterator->getPath()."/".$iterator->getFilename());

            if(!in_array($iterator->getPath()."/".$iterator->getFilename(),$this->excludeArray)){
                $this->recusiveIteration($niterator,$keyv,$depth,$seek);
            }

        }
        $iterator->next();

        }

}
    public function normalizeMac($path)
     {
         $path = $this->normalize_path($path);
         if($this->platform == 'darwin' && !$this->is_remote)
         {
             if(function_exists('normalizer_is_normalized') && function_exists('normalizer_normalize'))
             {
             if (!normalizer_is_normalized($path)) {
             $path = normalizer_normalize($path);

            }
        }
         }
         return $path;
     }

        /**
         * Get file meta values for directory
         * @param String
         * @return Array
         */
        public function metaDirectory($directory, $deep = false)
        {
            $files     = array();
            $files_obj = $this->getSingleIteratorObj($directory, $deep);
            foreach ($files_obj as $key => $file) {
                $file_path  = $file->getPathname();
                $file_name  = basename($file_path);
                $file_size  = $file->getSize();
                $file_mtime = filemtime($file_path) * 1000; // Millisecond

                $curr_path = getcwd();

                $abs_path = str_replace($curr_path, '', $file_path);

                if ($file_name == '.' || $file_name == '..' || !$file->isReadable()) {
                    continue;
                }

                if (is_file($file_path)) {
                    $file_hash = $this->hashFile($file_path);
                } else {
                    $file_hash = false;
                }

                if (is_dir($file_path)) {
                    $is_dir = true;
                } else {
                    $is_dir = false;
                }

                $files[] = array(
                    //'org_path' => $file_path,
                    'path' => $this->normalize_path($abs_path),
                    'name' => $file_name,
                    'size' => $file_size,
                    'mtime' => $file_mtime,
                    'file_hash' => $file_hash,
                    'is_dir' => $is_dir
                );
            }
            return $files;
        }

        /**
         * Set Dynamic Stream Size
         * @param Int
         */
        public function setStreamSize($fileStreamMaxSize)
        {
            $this->fileStreamMaxSize = $fileStreamMaxSize;
        }

        /**
         * Encripct the content
         * @param String
         * @return String
         */
        public function encript($content)
        {
            if ($this->encript) {
                $opt = base64_encode($content);
            } else {
                $opt = $content;
            }
            return $opt;
        }

        /**
         * Encripct the content
         * @param String
         * @return String
         */
        public function decript($content)
        {
            if ($this->encript) {
                $opt = base64_decode($content);
            } else {
                $opt = $content;
            }
            return $opt;
        }

        /**
         * Remove unwanter front slashes
         * @param String
         * @return String
         */
        public function removeSlashes($file)
        {
            if ($file != '/') {
                $count = 1;
                return ltrim($file, "/");
            }
            return $file;
        }

        /**
         * Get file meta values for directory
         * @param String
         * @return String
         */
        public function getFile()
        {
            if (!is_array($this->file)) {
                $file = $this->removeSlashes($this->file);
                $size = filesize($file);

                //echo $this->fileStreamMaxSize;

                if (is_file($file)) {
                    $stream = fopen($file, 'r');
                    $hash   = $this->encript(stream_get_contents($stream, $this->fileStreamMaxSize, $this->offset));
                    if ($size <= ($this->offset + $this->fileStreamMaxSize)) {
                        $eof        = true;
                        $nextOffset = false;
                    } else {
                        $eof        = false;
                        $nextOffset = $this->offset + $this->fileStreamMaxSize;
                    }
                    $opt = array(
                        'stream' => $hash,
                        'eof' => $eof,
                        'offset' => $nextOffset
                    );
                    $this->setResponse(204, $opt);
                } else {
                    $this->setResponse(102);
                }
            } else {
            }
        }

        /**
         * Copy
         * @param  Object $con      File Connection
         * @param  String $source   Source path
         * @param  String $dest     Destination path
         * @return NULL
         */
        public static function copy($con, $source, $dest)
        {
            $d = dir($source);
            while ($file = $d->read()) {
                if ($file != "." && $file != "..") {
                    if (is_dir($source."/".$file)) {
                        if (!@ftp_chdir($con, $dest."/".$file)) {
                            ftp_mkdir($con, $dest."/".$file);
                        }
                        ftp_copy($source."/".$file, $dest."/".$file);
                    } else {
                        $upload = ftp_put($con, $dest."/".$file, $source."/".$file, FTP_BINARY);
                    }
                }
            }
            $d->close();
        }

        /**
         * moveFile description
         * @param  Temp file
         * @param  Destination
         */
        public function moveFile($tmpFile, $destination)
        {
            $mode = "FILE";

            if ($mode == "FILE") {
                $tmp = file_get_contents($tmpFile);
                if (!file_exists(dirname($destination))) {
                    mkdir(dirname($destination), 0777, true);
                }
                $newfile = fopen($destination, "w");
                fwrite($newfile, $tmp);
            } else {
                $conn_id = ftp_connect($this->ftpServer, $this->ftpPort);
                $login_result = ftp_login($conn_id, $this->ftpUser, $this->ftpPass);
                $this->copy($conn_id, $this->tmpFolder, $this->ftpPath);
            }

            //unlink($tmpFile);
        }
        public function putFileGroup()
        {

            $arrayFiles = (array) $this->files;

            foreach($arrayFiles as $files)
            {
                if(!$this->checkTimeBreak())
                {
                $files = (array) $files;
                $offset = $files['current_offset'];
                $originalFilePath = $this->removeSlashes($files['path']);
                $filePath = dirname(__FILE__)."/".$originalFilePath;
                $fileContent = base64_decode($files['stream']);
                if($offset==0)
                {
                    $this->processWrite($originalFilePath,$filePath,$fileContent);


                }
                else
                {
                      $this->processWrite($originalFilePath,$filePath,$fileContent,false);

                }
            }
            }

                 $this->setResponse(202, $this->writeFilesArray);
        }
        /**
         * Create file
         * @param NULL
         * @return NULL
         */
        public function processWrite($originalFilePath,$filePath,$fileContent,$writeMode = true)
        {
            $dirname = dirname($filePath);
            if (!is_dir($dirname))
            {
            @mkdir($dirname, 0755, true);
            }
            if($writeMode == true)
            $fh = @fopen($filePath, 'w');
            else
            $fh = @fopen($filePath, 'a');
            $writeHandle = @fwrite($fh, $fileContent);

            if($writeHandle===false)
            $phpwrite = false;
            else
            $phpwrite = true;
            @fclose($fh);
            $this->writeFilesArray[] = array(

                    'path' => '/'.$originalFilePath,
                    'phpwrite' => $phpwrite
                );

        }
        public function putFile()
        {
            if ($this->file && $this->content) {
                $this->createFile($this->file, $this->content);
            } else {
                $this->setResponse(103);
            }
        }

        /**
         * Create file function
         * @param  String
         * @param  String
         * @param  Boolean
         * @return NULL
         */
        public function createFile($file, $content, $eof = false)
        {
            if (!is_dir($this->tmpFolder)) {
                mkdir($this->tmpFolder, 0777, true);
            }

            $tmpFile = $this->tmpFolder . '/' . $this->tmpFilePrefix . $file;

            if (file_exists($tmpFile)) {
                $fh = fopen($tmpFile, 'a');
                fwrite($fh, $content . "\n");
            } else {
                $fh = fopen($tmpFile, 'w');
                fwrite($fh, $content . "\n");
            }
            fclose($fh);

            if (!$eof) {
                $tmp     = file_get_contents($tmpFile);
                $newfile = fopen($file, "w");
                fwrite($newfile, $tmp);
                unlink($tmpFile);
                $this->setResponse(206);
            } else {
                $this->setResponse(207);
            }
        }


        /**
         * Set API Action for getting directory hash
         * @param NULL
         * @return NULL
         */
        public function getDirectoryHash()
        {
            if ($this->dir) {
                $dir = $this->removeSlashes($this->dir);
                if ($this->scandir($dir)) {
                    $hash = $this->hashDirectory($dir);
                    $this->setResponse(200, $hash);
                } else {
                    $this->setResponse(101);
                }
            } else {
                $this->setResponse(103);
            }
        }

        /**
         * Set API Action for getting file hash
         * @param NULL
         * @return NULL
         */
        public function getFileHash()
        {
            if ($this->file) {
                $file = $this->file;
                if (is_file($file)) {
                    $hash = $this->hashFile($file);
                    $phpwrite = false;
                    $time= microtime(1);
                    $fh = @fopen(dirname(__FILE__)."/testLS-".$time.".php", 'w');
                    $writeHandle = @fwrite($fh, "test");
                    if($writeHandle!==false)
                    $phpwrite = true;
                    @fclose($fh);
                    @unlink(dirname(__FILE__)."/testLS-".$time.".php");
                    $opt = array(
                        'hash' => $hash,
                        'phpwrite' => $phpwrite

                    );
                    $this->setResponse(200, $opt);

                } else {
                    $this->setResponse(102);
                }
            } else {
                $this->setResponse(103);
            }
        }

        /**
         * Set API Action for getting modified time
         * @param NULL
         * @return NULL
         */
    public function getDirMeta()
    {
        $this->setExcludeArrayFromGetExcludeFileList();

        $iterator=new DirectoryIterator(ABSPATH);
        $seekArray = '';
        if($this->offset)
        $seekArray = explode("-",$this->offset);
        try {
            $this->recusiveIteration($iterator,'',0,$seekArray);
            $opt = array(
                            'files' =>  $this->metaFiles,
                            'eof' => true,
                            'offset' => false
                        );
            $meta = $opt;

        }
        catch (Exception $e) {
            $meta = $this->metaOffset;
        }
        $this->setResponse(203, $meta);
    }

        /**
         * Create directory for assigned object
         * @param NULL
         * @return NULL
         */
        public function makeDir()
        {
            if ($this->dir) {
                $dir = $this->dir;
                if (!is_dir($dir)) {
                    mkdir($dir, 0777, true);
                    $this->setResponse(205);
                } else {
                    $this->setResponse(104);
                }
            }
        }

        /**
         * Remove directory for assigned object
         * @param NULL
         * @return NULL
         */
        public function removeDir()
        {
            if ($this->dir) {
                $dir = $this->dir;
                if (is_dir($dir)) {
                    rmdir($dir);
                    $this->setResponse(208);
                } else {
                    $this->setResponse(105);
                }
            }
        }

        /**
         * is_SQL
         * @param  String
         * @return boolean
         */
        public function is_SQL($fileName)
        {
            return true;
        }

        /**
         * Delete SQL
         * @return NULL
         */
        public function deleteSQL()
        {

            if ($this->files) {
                $array = (array) $this->files;
                foreach ($array as $files) {

                    $files = (array) $files;
                    //$fileName = $this->removeSlashes($files['name']);
                    $fileName = trim($files['name']);

                    if (is_file($fileName) && $this->is_SQL($fileName)) {
                        @chmod($fileName,0777);
                        @unlink($fileName);
                        //exit;
                    }
                }
                $this->setResponse(214);
            }
        }

        public function deleteSQLBackupDir()
        {//delete recursive

            $backupDBDir = WPMERGE_TEMP_DIR . '/'.'backup_db';

            require_once WPMERGE_PATH . '/includes/file_iterator.php';

            $common_iterator = new wpmerge_iterator_common();

            $common_iterator->delete($backupDBDir);

            if(file_exists($backupDBDir)){
                $this->setResponse(105);
                return;
            }
            $this->setResponse(214);
        }

        /**
         * Create file array
         * @param NULL
         * @return NULL
         */
        public function getFileGroup()
        {
            $initial = 0;
            if ($this->files) {
                $opt   = array();
                $array = (array) $this->files;

                foreach ($array as $files) {
                   // header('Content-Type: application/json');
                    $files = (array) $files;
                    if (!$this->checkTimeBreak()) {
                        $file = isset($files['name_64']) ? base64_decode($files['name_64']) : $files['name'];//wordfence 403 issue fix (LFI: Local File Inclusion)

                        //$file = $this->removeSlashes($files['name']);
                        $file= $this->doUtf8Decode($file);

                        $is_gz = false;

                        if (file_exists($file . '.gz')) {
                            $file .= '.gz';
                            $is_gz = true;
                        }

                        $this->setOffset($files['current_offset']);
                        if (is_file($file)) {
                            $i = 0;
                            while(!$this->checkTimeBreak()){
                                if($i > 0){
                                    if($eof === true){
                                        break;
                                    }
                                    else{
                                        $this->offset = $nextOffset;
                                    }
                                }
                            
                                $size   = filesize($file);
                                $stream = fopen($file, 'r');
                                if ($is_gz) {
                                    $hash   = $this->encript(file_get_contents('compress.zlib://'.$file, NULL, NULL, $this->offset, $this->fileStreamMaxSize));
                                } else {
                                    $hash   = $this->encript(file_get_contents($file, NULL, NULL, $this->offset, $this->fileStreamMaxSize));
                                }

                                if ($size <= ($this->offset + $this->fileStreamMaxSize)) {
                                    $eof        = true;
                                    $nextOffset = false;
                                } else {
                                    $eof        = false;
                                    $nextOffset = $this->offset + $this->fileStreamMaxSize;
                                }
                                $tempArray = array(
                                    'path' => $this->doUtf8($file),
                                    'stream' => $hash,
                                    'eof' => $eof,
                                    'current_offset' => $this->offset,
                                    'next_offset' => $nextOffset
                                );

                                if($this->is_remote==false){
                                    if(!$initial) {
                                        echo "[";
                                        $initial = 1;
                                    } else{
                                        echo ",";
                                    }
                                    echo json_encode($tempArray);
                                }
                                else{
                                    $_tmp_str = '<wpmerge_file_data>'.json_encode($tempArray).'</wpmerge_file_data>'."**|ls|**"."\n";//this "\n" required to avoid printing a long text without line break which some can cross server buffer limit.

                                    if( !$this->checkMaxOutputStrLimitAndEcho($_tmp_str) ){
                                        break 2;
                                    }
                                }
                                $i++;
                            }//END OF while(!$this->checkTimeBreak())
                        } else {

                            $tempArray =  array(
                                'path' => $file,
                                'error' => $this->lang('Invalid File')
                            );
                            // $sendArray['value'] = $tempArray;
                            if($this->is_remote==false){
                                if(!$initial){
                                    echo "[";
                                    $initial = 1;
                                }
                                else{
                                    echo ",";
                                }
                                echo json_encode($tempArray);
                            }
                            else{
                                $_tmp_str = '<wpmerge_file_data>'.json_encode($tempArray).'</wpmerge_file_data>'."**|ls|**"."\n";
                                if( !$this->checkMaxOutputStrLimitAndEcho($_tmp_str) ){
                                    break;
                                }
                            }

                            /*@flush();
                            @ob_end_flush();*/
                        }

                    }
                }
                if($this->is_remote==false)
                echo "]";
                //$opt['process'] = $this->lang('End');
                wpmerge_debug::log(array('currentOutputStrSize' => $this->currentOutputStrSize), 'this_call_download_end');
                exit;
                //$this->setResponse(209, $opt);
            }
        }

        /**
         * Print all database tables
         * @param NULL
         * @return NULL
         */
        public function listAllSQLTables()
        {
            global $globalCon;
            $exclude = array('wpmerge');
            $table_prefix = $this->getDBPrefix();

            $globalCon = $this->connectDB();
            if ($this->isWordPress()) {
                $prefix = $this->getDBPrefix();
            } else {
                $prefix = '';
            }

            $tables = array();
            //$tables = $this->listSQLTables($prefix);
            $result = $globalCon->get_results("SHOW FULL TABLES", OBJECT_K);//this can show view tables also WARNING improve LATER
            $i=0;
            foreach ($result as $key => $value) {
                //need to improve following exclude logic, now support wp prefix plugin exclude table or exclude table's prefix
                $is_skip = false;
                foreach($exclude as $exclude_table){
                    $exclude_table = $table_prefix.$exclude_table;
                    if(strpos($key, $exclude_table) === 0 ){    
                        $is_skip = true;
                    }
                }
                if(!$is_skip){
                    if($value->Table_type == 'VIEW'){
                        $total_rows = 0;
                    }
                    else{
                        $total_rows = $globalCon->get_var("SELECT COUNT(*) FROM `$key`");
                        if(!is_numeric($total_rows)){
                            //table is crashed
                            $is_skip = true;
                        }
                    }                    
                }
                

                if($is_skip){
                    continue;
                }

                $tables[$i]['name'] = $key;
                $tables[$i]['table_type'] = $value->Table_type;
                $tables[$i]['total_rows'] = (int)$total_rows;
                if(stripos($key,$prefix) === 0)//0 means starting of table name
                    $tables[$i]['is_wp_table'] = true;
                else
                    $tables[$i]['is_wp_table'] = false;
                $i++;
            }  

            //usort($tables, 'wpmerge_sort_table_type');//commented because input order not maintained when two table types are equal due to unstable sorting by php.
            $tables = wpmerge_sort_table_by_type($tables);
            $opt    = array(
                'tables' => $tables
            );
            $this->setResponse(211, $opt);
        }

        /**
         * Create database table list
         * @param String
         * @return Array
         */
        public function listSQLTables($prefix = '')
        {
            global $globalCon;
            $globalCon = $this->connectDB();

            if ($globalCon) {
                $tables = array();
                if ($prefix == '') {
                    $tables = array();
                    $result = $globalCon->get_results("SHOW TABLES", OBJECT_K);
                    foreach ($result as $key => $value) {
                       $tables[] = $key;
                    }
                } else {
                    $tables = array();
                    $sql_espaced_prefix = wpmerge_esc_table_prefix($prefix);
                    $result = $globalCon->get_results("SHOW TABLES LIKE '$sql_espaced_prefix%'", OBJECT_K);
                    foreach ($result as $key => $value) {
                        $tables[] = $key;
                    }
                }
                return $tables;
            } else {
                $this->setResponse(106);
            }
        }
        public function stripallslashes($string) {
        $string = str_ireplace(array('\"',"\'",'\r','\n',"\\\\"),array('"',"'","\r","\n","\\"),$string);
        /*$string = str_ireplace('\"','"',$string);
        $string = str_ireplace("\'","'",$string);
        $string = str_ireplace('\r',"\r",$string);
        $string = str_ireplace('\n',"\n",$string);
        $string = str_ireplace("\\\\","\\",$string);*/

     return $string;
                }

        function build_mysqldump_list() {
        if ('win' == strtolower(substr(PHP_OS, 0, 3)) && function_exists('glob')) {
            $drives = array('C','D','E');

            if (!empty($_SERVER['DOCUMENT_ROOT'])) {
                //Get the drive that this is running on
                $current_drive = strtoupper(substr($_SERVER['DOCUMENT_ROOT'], 0, 1));
                if(!in_array($current_drive, $drives)) array_unshift($drives, $current_drive);
            }

            $directories = array();

            foreach ($drives as $drive_letter) {
                $dir = glob("$drive_letter:\\{Program Files\\MySQL\\{,MySQL*,etc}{,\\bin,\\?},mysqldump}\\mysqldump*", GLOB_BRACE);
                if (is_array($dir)) $directories = array_merge($directories, $dir);
            }

            $drive_string = implode(',', $directories);
            return $drive_string;

        } else return "/usr/bin/mysqldump,/bin/mysqldump,/usr/local/bin/mysqldump,/usr/sfw/bin/mysqldump,/usr/xdg4/bin/mysqldump,/opt/bin/mysqldump";
}

 public function detect_safe_mode() {
        return (@ini_get('safe_mode') && strtolower(@ini_get('safe_mode')) != "off") ? 1 : 0;
    }

public function find_working_sqldump() {
        global $globalCon;
        $globalCon = $this->connectDB();

        // The hosting provider may have explicitly disabled the popen or proc_open functions
        if ($this->detect_safe_mode() || !function_exists('popen') || !function_exists('escapeshellarg')) {

            return false;
        }

        # Theoretically, we could have moved machines, due to a migration
        //if (null !== $existing && (!is_string($existing) || @is_executable($existing))) return $existing;

        $tempDir = WPMERGE_TEMP_DIR;

        $table_name = $this->getDBPrefix().'options';
        //echo $table_name;
      //  $tmp_file = md5(time().rand()).".sqltest.tmp";
        $pfile = md5(time().rand()).".tmp";
        $file_write=file_put_contents($tempDir.'/'.$pfile, "[mysqldump]\npassword=".DB_PASSWORD."\n");
        if(!$file_write)
        {
            $this->setResponse(110);
            $this->buildResponse();
            exit;
        }
        //file_get_conten

        $result = false;
        $mysqlDumpCmd = $this->build_mysqldump_list();
        foreach (explode(',', $mysqlDumpCmd) as $potsql) {
            $originalPotsql = $potsql;//to avoid double nesting with quotes in the next function

            if (!@is_executable($potsql)) continue;



            if (strtolower(substr(PHP_OS, 0, 3)) == 'win') {
                $exec = "cd ".escapeshellarg(str_replace('/', '\\', $tempDir))." & ";
                $siteurl = "'siteurl'";
                if (false !== strpos($potsql, ' ')) $potsql = '"'.$potsql.'"';
            } else {
                $exec = "cd ".escapeshellarg($tempDir)."; ";
                $siteurl = "\\'siteurl\\'";
                if (false !== strpos($potsql, ' ')) $potsql = "'$potsql'";
            }

            $exec .= "$potsql --defaults-file=$pfile --lock-tables=false --max_allowed_packet=1M --quote-names --add-drop-table --skip-comments --skip-set-charset --allow-keywords --dump-date --extended-insert --where=option_name=$siteurl --user=".escapeshellarg(DB_USER)." --host=".escapeshellarg(DB_HOST)." ".DB_NAME." ".escapeshellarg($table_name)."";
            //echo $exec."\r\n";

            $handle = popen($exec, "r");
            $output = '';

            if ($handle) {
                while (!feof($handle)) {
                    $output.= fgets($handle);
                }
                $ret = pclose($handle);
                if($ret==0) {
                    if (stripos($output, 'insert into') !== false) {
                        // $result = $potsql;
                        $result = $originalPotsql;
                        break;
                    }
                }
            }
        }

        @unlink($tempDir.'/'.$pfile);

        return $result;
    }

    public function create_multi_insert_statement($tableName, array $row, array $columns = array())
    {
        $values = $this->create_row_insert_values($row, $columns, $tableName);
        $joined = join(', ', $values);
        $run_previous_sql = false;

        if($this->tempQueryCount > 0){
            $check_sql = ",($joined)";
            if( ($this->tempQuerySize + strlen($check_sql)) > 1048000){//1MB = 1048576 bytes, we will be adding [space], ",", "(" others etc so let be 1048000
                $run_previous_sql = true;

                $this->resetTempQuery();
            }
        }
        
        if($this->tempQueryCount > 0){
            $sql = ",($joined)";
        }else {
            $sql    = "INSERT INTO `$tableName` VALUES ($joined)";//add one row whatever the size is. Handle if required in future.
        }
        $this->tempQueryCount = $this->tempQueryCount + 1;
        $this->tempQuerySize = $this->tempQuerySize + strlen($sql);

        return array('run_previous_sql' => $run_previous_sql, 'sql' => $sql);
    }

    protected function create_row_insert_statement($tableName, array $row, array $columns = array(), $tableCount=0)
        {
            $values = $this->create_row_insert_values($row, $columns, $tableName);
            $joined = join(', ', $values);
            $sql_close_next_line = '';

            if($this->tempQueryCount > 0){
                $check_sql = ",($joined)";
                if( ($this->tempQuerySize + strlen($check_sql)) > 1048000){//1MB = 1048576 bytes, we will be adding [space], ",", "(" others etc so let be 1048000
                    $sql_close_next_line = ";\n";

                    $this->resetTempQuery();
                }
            }
            
            if($this->tempQueryCount > 0){
                $sql = ",($joined)";
            }else {
                $sql    = "INSERT INTO `$tableName` VALUES ($joined)";//add one row whatever the size is. Handle if required in future.
            }
            $this->tempQueryCount = $this->tempQueryCount + 1;
            $this->tempQuerySize = $this->tempQuerySize + strlen($sql);

            return  $sql_close_next_line . $sql;
        }

        public function resetTempQuery($val=0)
        {
            $this->tempQueryCount=$val;
            $this->tempQuerySize=0;
        }

        public function create_row_insert_values($row, $columns,$tableName)
        {
            $values = array();
            $doReplace = FALSE;

            if(
                isset($this->findandreplace->find) && 
                !empty($this->findandreplace->find) &&
                isset($this->findandreplace->replace) && 
                !empty($this->findandreplace->replace)
                ){
                $doReplace = TRUE;
                $from = $this->findandreplace->find;
                $to = $this->findandreplace->replace;
            }
            foreach ($row as $columnName => $value) {

                $type = $columns[$columnName]->Type;

                // If it should not be enclosed
                if ($value === null) {
                    $values[] = 'null';
                } elseif (strpos($type, 'int') !== false
                    || strpos($type, 'float') !== false
                    || strpos($type, 'double') !== false
                    || strpos($type, 'decimal') !== false
                    || strpos($type, 'bool') !== false
                ) {
                    $values[] = $value;
                } elseif (strpos($type, 'blob') !== false) {
                    /*if($doReplace){
                        $value = utf8_encode($value);
                        $value = $this->findAndReplace( $from , $to , $value ,false,$tableName);

                        //MultiSite
                        /*$fromURL = parse_url("http://".$from);
                        $toURL = parse_url("http://".$to);
                        $value = $this->findAndReplace( $fromURL['host'] , $toURL['host'] , $value );
                        $value = $this->findAndReplace( $fromURL['path'] , $toURL['path'] , $value );

                    }*/
                    $values[] = strlen($value) ? '0x'.$value : "''";
                } elseif (strpos($type, 'binary') !== false) {
                    $values[] = strlen($value) ? "UNHEX('".$value."')" : "''";
                }
                else {
                    if($doReplace){
                        $fromURL = parse_url($from);
                        $toURL = parse_url($to);
                        $value = $this->doUtf8($value);
                        $fromURLPort = '';
                        $fromURLPath = '';
                        $toURLPort = '';
                        $toURLPath = '';

                        if(isset($fromURL['port']) && $fromURL['port']!= '')
                            $fromURLPort = ":".$fromURL['port'];
                        if(isset($fromURL['path']) && $fromURL['path']!= '')
                            $fromURLPath = $fromURL['path'];
                        $fromHTTPS = "https://".$fromURL['host'].$fromURLPort.$fromURLPath;
                        $fromHTTP = "http://".$fromURL['host'].$fromURLPort.$fromURLPath;
                        $fromWithoutProtocol = "//".$fromURL['host'].$fromURLPort.$fromURLPath;

                        if(isset($toURL['port']) && $toURL['port']!= '')
                            $toURLPort = ":".$toURL['port'];
                        if(isset($toURL['path']) && $toURL['path']!= '')
                            $toURLPath = $toURL['path'];
                        $toHTTPS = "https://".$toURL['host'].$toURLPort.$toURLPath;
                        $toHTTP = "http://".$toURL['host'].$toURLPort.$toURLPath;
                        $toWithoutProtocol = "//".$toURL['host'].$toURLPort.$toURLPath;

                        $tmpReplacer = '*^|____tmp_replacer____|^*';//this will help, even replacing 100 times before replacing the $tmpReplacer, wont create subset issue find - http://goog.com/wp replace - http://goog.com/wp_2, result - httphttp://goog.com/wp_2_2

                        $findArray = array($fromWithoutProtocol, $tmpReplacer);
                        $toArray = array($tmpReplacer, $toWithoutProtocol);

                        $value = $this->findAndReplace($findArray, $toArray , $value);

                        //MultiSite
                        if ($this->isMultiSite()) {

                            $value = $this->findAndReplace( $fromURL['host'] , $toURL['host'] , $value );
                            $value = $this->findAndReplace( $fromURL['path'] , $toURL['path'] , $value );
                        }
                    }
                   // if(!is_serialized($value))
                    $values[] = "'".$this->esc_sql($value)."'";
                   // else
                    //$values[] = "'".$value."'";
                }
            }
            //file_put_contents(dirname(__FILE__)."/__check.php",var_export($values,1)."\r\n",FILE_APPEND );
            return $values;
        }

        /*
            there is a behavioural change in esc_sql() after WP-v4.8.3
            https://make.wordpress.org/core/2017/10/31/changed-behaviour-of-esc_sql-in-wordpress-4-8-3/
        */
        public function esc_sql($val)
        {
            // return $val;
            global $globalCon;
            $globalCon = $this->connectDB();

            if ( method_exists($globalCon, 'remove_placeholder_escape') ) {
                return  $globalCon->remove_placeholder_escape( $globalCon->_real_escape( $val ) );
            }

            return  $globalCon->_real_escape( $val );
        }

        /**
         * Create SQL file
         * @param NULL
         * @return NULL
         */
        public function replaceLocalSQL($haystack)
        {
             $from = $this->findandreplace->find;
             $to = $this->findandreplace->replace;
              $fromURL = parse_url($from);
        $toURL = parse_url($to);
            $retArray = array();
            if(isset($this->dbprefix) && $this->dbprefix!='')
            {

           if(stripos($haystack,$this->dbprefix . 'user_roles')===false && stripos($haystack,$this->dbprefix . 'usermeta')===false)
           {
            $queryArray = explode(" (",$haystack);
            $queryArray[0] = str_ireplace($this->dbprefix,$this->getDBPrefix(),$queryArray[0]);
            $haystack = implode(" (",$queryArray);    
            }
            
            else
            $haystack=str_ireplace($this->dbprefix,$this->getDBPrefix(),$haystack);
            }
            if(stripos($haystack,"insert into")!==false && stripos($haystack,$fromURL['host'])!==false){

                $match = explode(",'",$haystack);
                $incrementor = 0;
        foreach ($match as $matchDat => $val)
    {

$val=str_ireplace("\',", "**||**||-lcsync,", $val);
 $val = explode("',",$val);
 $val = $val[0];

    $replaceEndQuote = 0;
    $replaceStartQuote = 0;
    $replaceEndBraces = 0;

$val=str_ireplace("**||**||-lcsync,", "\',", $val);


    //echo $val."<br>";

    //if(substr($oldval, -1)=="'")
    //$replaceEndQuote =1;
    //if(substr($oldval, 0,1)=="'")
    //$replaceStartQuote =1;
   // if(substr($oldval, -3)==");\n")
   // $replaceEndBraces = 1;
    $val = trim($val,");\n");
    $val = trim($val,"'");
      $oldval=$val;

    $val = $this->stripallslashes($val);

  //$val = $this->doUtf8($val);


    //var_dump(unserialize($val));
   if ($this->isMultiSite()) {

        $replace = $this->findAndReplace( $fromURL['host'] , $toURL['host'] , $val );
        $replace = $this->findAndReplace( $fromURL['path'] , $toURL['path'] , $replace);
    }
    else
    {
    $urlPort = '';
    $urlPath = '';
    if(isset($fromURL['port']) && $fromURL['port']!= '')
    $urlPort = ":".$fromURL['port'];
    if(isset($fromURL['path']) && $fromURL['path']!= '')
    $urlPath = $fromURL['path'];
    $fromHTTPS = "https://".$fromURL['host'].$urlPort.$urlPath;
    $fromHTTP = "http://".$fromURL['host'].$urlPort.$urlPath;
    $fromWithoutProtocol = "//".$fromURL['host'].$urlPort.$urlPath;
    $replace = $this->findAndReplace(array($fromHTTPS, $fromHTTP, $fromWithoutProtocol), $to,  $val);

    }

    //echo $replace;
    //file_put_contents("_test.php", $val);
    //exit;

    if($incrementor==0 && stripos($replace,"'")!==false)
    {
   
    $replace = str_ireplace("'","**||**||-lcsync",$replace);
    $escapedSQL = $this->esc_sql($replace);
    $escapedSQL = str_ireplace("**||**||-lcsync","'",$escapedSQL);
    }
    else 
        $escapedSQL = $this->esc_sql($replace);

    /*if($replaceEndQuote)
    $escapedSQL = $escapedSQL."'";
    if($replaceStartQuote)
    $escapedSQL = "'".$escapedSQL;
   if($replaceEndBraces)
    $escapedSQL = $escapedSQL."');\n";*/

    $haystack = str_ireplace($oldval,$escapedSQL,$haystack);


$incrementor++;

}
}
            if(stripos($haystack, "insert into")!==false)
    {


            if($this->tempQueryCount>0 )
            {

            if($this->tempQueryCount>1000 || $this->tempQuerySize>100000)
            {

            $sql = ",".$this->replaceInsertQuery($haystack).";\n";
            $retArray['q'] = $sql;
            $retArray['exec'] = 1;

            $this->resetTempQuery(-1);

            }
            else {

               $sql = ",".$this->replaceInsertQuery($haystack);

               $retArray['q'] = $sql;

            }
            }else {

            $sql    = substr($haystack, 0, -2);
            $retArray['q'] = $sql;

            }

            $this->tempQueryCount = $this->tempQueryCount+1;
            $this->tempQuerySize = $this->tempQuerySize+strlen($sql);
}
else
{

$retArray['q'] = $haystack;
$retArray['exec'] = 1;
$retArray['prevExec'] = 1;
$this->resetTempQuery();
}
//$retArray['q'] = $haystack;
//$retArray['exec'] = 1;
return $retArray;
//return $haystack;

}
    //else
    //echo $val."<br>";
 public function replaceInsertQuery($query)
    {
        
        if(stripos($query,"INSERT INTO")!==false)
        {
        $newTable = str_ireplace($this->dbprefix, $this->getDBPrefix(), $this->table);
        $query = str_ireplace("INSERT INTO `".$newTable."` VALUES ", '', $query);
        $query = substr($query, 0, -2);
         }
        return $query;
    }

        public function createSQLLocal()
        {

            global $globalCon;
             $globalCon = $this->connectDB();
            $table_name = $this->table;
            $tempDir = WPMERGE_TEMP_DIR;
            $exec = "cd ".escapeshellarg($tempDir)." ; ";


            $pfile = md5(time().rand()).'.tmp';
            file_put_contents($tempDir.DIRECTORY_SEPARATOR.$pfile, "[mysqldump]\npassword=".DB_PASSWORD."\n");
           //file_put_contents($tempDir."/".$table_name."-local1.sql", '');
           $fp = fopen($tempDir.DIRECTORY_SEPARATOR.$table_name."-db.sql", 'w');
           $socket = $globalCon->get_row('show variables like "socket"',ARRAY_N);
           $tempSplit = explode(":",DB_HOST);
           $db_host = $tempSplit[0];
           $db_port= " ";
           if(isset($tempSplit[1]))
           {
            $db_port = " --port=".$tempSplit[1]." ";
           }
           $sqldumpCmd = "./mysqldump";
           if(PHP_OS=='Linux')
           $sqldumpCmd = "./mysqldump-linux";

           if(PHP_OS == 'Windows' || PHP_OS == 'WIN32' || PHP_OS == 'WINNT')
           {
            $sqldumpCmd = "mysqldump.exe";
            $exec = str_ireplace(";", "&",$exec);
           }
            $exec .= $sqldumpCmd." --defaults-file=$pfile --socket=".$socket[1]." --max_allowed_packet=1M --quote-names --add-drop-table --skip-comments  --allow-keywords --dump-date --extended-insert=FALSE  --user=".escapeshellarg(DB_USER)." --host=".escapeshellarg($db_host).$db_port.DB_NAME." ".escapeshellarg($table_name);

            //echo $exec;
            //exit;

            $handle = popen($exec, "r");
            if ($handle) {
                while(!feof($handle)) {
                    $output = fgets($handle);
                    //echo $output;

                    //$output = $this->replaceLocalSQL($output);
                    fwrite($fp, $output);
                    //file_put_contents($tempDir."/".$table_name."-local1.sql", $output,FILE_APPEND);
                    //exit;

                }
                $ret = pclose($handle);
                $tableFileName = WPMERGE_TEMP_DIR."/".$this->table.'-db.sql';
                 $opt = array(
                    'eof' => true,
                    'offset' => 0,
                    'db_prefix' => $this->getDBPrefix(),
               //     'hash' => $hash,
                    'file' => $tableFileName
                );
                 if($ret!=0) {

                 $opt = array(
                    'eof' => false,
                    'offset' => 0,
               //     'hash' => $hash,
                    'db_prefix' => $this->getDBPrefix(),
                    'file' => $tableFileName,
                    'mysqldumpfailed' => true,
                    'is_phpdump' => true
                );
                  //@unlink($tempDir.'/'.$pfile);
                  //$this->setResponse(210, $opt);

            }
                //$this->createSQLDump();
                @unlink($tempDir."/".$pfile);
                $this->setResponse(210, $opt);

            }
            else {
                @unlink($tempDir."/".$pfile);
                $this->setResponse(106);
            }

        }

        public function createSQLServerDump ($potsql)
        {

            global $globalCon;
            $globalCon = $this->connectDB();
            $tempDir   = WPMERGE_TEMP_DIR;

            $table_name = $this->table;

            $pfile = md5(time().rand()).'.tmp';

            file_put_contents($tempDir.'/'.$pfile, "[mysqldump]\npassword=".DB_PASSWORD."\n");

            $backupDBDir = WPMERGE_TEMP_DIR . '/'.'backup_db';
            if(!file_exists($backupDBDir)){
                $mkDir = mkdir($backupDBDir, 0775, true);
                if(!$mkDir){
                    $this->setResponse(104);
                    $this->buildResponse();
                    exit;
                }
            }

            $tableFileName = $backupDBDir."/".$this->table.'-db.sql';
            $tableFileName = wp_normalize_path($tableFileName);
            $fp = fopen($tableFileName, 'w');

            if (strtolower(substr(PHP_OS, 0, 3)) == 'win') {
                $exec = "cd ".escapeshellarg(str_replace('/', '\\', $tempDir))." & ";
                $siteurl = "'siteurl'";
                if (false !== strpos($potsql, ' ')) $potsql = '"'.$potsql.'"';
            } else {
                $exec = "cd ".escapeshellarg($tempDir)."; ";
                $siteurl = "\\'siteurl\\'";
                if (false !== strpos($potsql, ' ')) $potsql = "'$potsql'";
            }

            $exec .= "$potsql --defaults-file=$pfile --lock-tables=FALSE --skip-lock-tables --single-transaction=TRUE --max_allowed_packet=1M --quote-names --add-drop-table --skip-comments --skip-set-charset --allow-keywords --dump-date --extended-insert=TRUE --skip-add-locks --user=".escapeshellarg(DB_USER)." --host=".escapeshellarg(DB_HOST)." ".DB_NAME." ".escapeshellarg($table_name)."";

            wpmerge_debug::log($exec, '$exec');

            $handle = popen($exec, "r");

            if ($handle) {
                 while(!feof($handle)) {
                    $output = fgets($handle);
                    fwrite($fp, $output);
                }
                $ret = pclose($handle);
            }

            if($ret!=0) {

                 $opt = array(
                    'eof'             => false,
                    'offset'          => 0,
                    'file'            => $tableFileName,
                    'mysqldumpfailed' => true,
                    'is_phpdump'      => true
                );

                @unlink($tempDir.'/'.$pfile);
                $this->setResponse(210, $opt);

            } else {
                $opt = array(
               'eof'         => true,
               'offset'      => 0,
               'db_prefix'   => $this->getDBPrefix(),
               'file'        => $tableFileName
                );

                @unlink($tempDir.'/'.$pfile);
                $this->setResponse(210, $opt);
            }

        }

        public function createSQLPHP()
        {

            global $globalCon;
            $globalCon = $this->connectDB();

            if ($globalCon) {

                $table = $this->table;
                $offset = $this->offset;
                echo "\n=========offset:".$offset;
                $maxQuery = $this->sqlMaxQueryLimit;
                $backupDBDir = WPMERGE_TEMP_DIR . '/'.'backup_db';
                if(!file_exists($backupDBDir)){
                    $mkDir = mkdir($backupDBDir, 0775, true);
                    if(!$mkDir){
                        $this->setResponse(104);
                        $this->buildResponse();
                        exit;
                    }
                }
                $tableFileName = $backupDBDir."/".$table.'-db.sql';
                $tableFileName = wp_normalize_path($tableFileName);
                $return = "";
                $row_count=$offset;

                $total_rows = $globalCon->get_var("SELECT COUNT(*) FROM `$table`");
                if(!is_numeric($total_rows)){
                    $this->setResponse(111);
                    $this->buildResponse();
                    exit;
                }

                //START

                if ($offset == 0) {

                    @unlink($tableFileName);

                    $return .= "\n--\n-- Table structure for table `$table`\n--\n\n";

                    $table_creation_query = '';
                    $table_creation_query .= "DROP TABLE IF EXISTS `$table`;";
                    $table_creation_query .= "
/*!40014 SET FOREIGN_KEY_CHECKS=FALSE */;
/*!40014 SET UNIQUE_CHECKS=FALSE */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;\n";

                    $table_create = $globalCon->get_row("SHOW CREATE TABLE `$table`", ARRAY_N);
                    if ($table_create === false) {
                        throw new Exception($db_error . ' (ERROR_3)');
                    }
                    $table_creation_query .= $table_create[1].";";
                    $table_creation_query .= "\n/*!40101 SET character_set_client = @saved_cs_client */;\n\n";

                    if(!empty($total_rows)){
                        $table_creation_query .= "--\n-- Dumping data for table `$table`\n--\n";

                        $table_creation_query .= "/*!40000 ALTER TABLE `$table` DISABLE KEYS */;";
                    }
                    $return .= $table_creation_query . "\n";
                    $handle = fopen($tableFileName, 'w');
                    fwrite($handle, $return);
                    fclose($handle);
                    $return = '';
                }
                if($offset!=0){
                    $return = ";\n";
                }
                $columnsArr = array();
                $table_count = $globalCon->get_var("SELECT COUNT(*) FROM `$table`");
                $columns = $globalCon->get_results("SHOW COLUMNS IN `$table`", OBJECT_K);
                foreach ($columns as $columnName => $metadata) {
                    if (strpos($metadata->Type, 'blob') !== false || strpos($metadata->Type, 'binary')!==false) {
                        $fullColumnName = "`$table`.`$columnName`";
                        $columnArr[]      = "HEX($fullColumnName) as `$columnName`";
                    } else {
                        $columnArr[] = "`$table`.`$columnName`";
                    }
                }
                $cols = join(', ', $columnArr);
                while(!$this->checkTimeBreak() && $total_rows > $row_count && $total_rows != 0){
                    //for ($i = $offset; $i < $nextOffset; $i = $i + $maxQuery) {
                        //$table_data = $globalCon->get_results("SELECT $cols FROM `$table` LIMIT " . $maxQuery . " OFFSET $offset", ARRAY_A);

                        $select_args = array();
                        $select_args['columns'] = $cols;
                        $select_args['table'] = $table;
                        $select_args['limit'] = $maxQuery;
                        $select_args['offset'] = $offset;
                        $select_args['result_format'] = ARRAY_A;
                        $select_args['total_rows'] = $total_rows;

                        $select_by_memory_limit_obj = new wpmerge_select_by_memory_limit($select_args);
                        $table_data = $select_by_memory_limit_obj->process_and_get_results();

                        //echo "SELECT * FROM $table LIMIT " . $maxQuery . " OFFSET $offset";
                        // if ($table_data === false || !is_array($table_data[0])) {
                        //     echo "error";
                        //     throw new Exception($db_error . ' (ERROR_4)');
                        // }

                        $out = '';
                        //$this->resetTempQuery();
                        $tempProcessLimit = 0;
                        foreach ($table_data as $key => $row) {
                            if($this->checkTimeBreak()){
                                break;
                            }
                            $data_out = $this->create_row_insert_statement($table, $row, $columns,count($table_data));
                            $out .= $data_out;
                            $tempProcessLimit++;
                            $row_count++;
                        }

                        $return .= $out;
                        $out = '';

                        //}
                    if( $this->tempQueryCount == 0){
                        if(substr($return, -3) == ");\n")
                        $return = substr($return, 0, -2);
                    }
                    if ($total_rows <= ($offset + $tempProcessLimit)) {
                        $return .= ";\n/*!40000 ALTER TABLE `$table` ENABLE KEYS */;\n";
                    }

                    //END

                    $handle = fopen($tableFileName, 'a');
                    if(!$handle){
                        $this->setResponse(110);
                        $this->buildResponse();
                        exit;
                    }
                    fwrite($handle, $return);
                    fclose($handle);
                    $offset = $offset + $tempProcessLimit;
                    $row_count = $offset;
                    $return = '';
                }
             //   $hash = $this->hashFile($tableFileName);

                if ($total_rows <= ($offset + 0)) {
                    $eof        = true;
                    $nextOffset = false;
                    // $offset = $offset+($total_rows-$offset);
                } else {
                    $eof        = false;
                    $nextOffset = $offset;
                }


                $opt = array(
                    'eof' => $eof,
                    'offset' => $nextOffset,
                    'is_phpdump' => true,
                    'db_prefix' => $this->getDBPrefix(),
               //     'hash' => $hash,
                    'file' => $tableFileName
                );

                //$this->createSQLDump();

                wpmerge_debug::printr(wpmerge_select_by_memory_limit::get_all_perform_data(), 'all_perform_data');
                

                $this->setResponse(210, $opt);
            } else {
                $this->setResponse(106);
            }
        }
        public function createViewTableSQLPHP()
        {

            global $globalCon;
            $globalCon = $this->connectDB();

            if ($globalCon) {

                $table = $this->table;
                $offset = $this->offset;
                $maxQuery = $this->sqlMaxQueryLimit;
                $backupDBDir = WPMERGE_TEMP_DIR . '/'.'backup_db';
                if(!file_exists($backupDBDir)){
                    $mkDir = mkdir($backupDBDir, 0775, true);
                    if(!$mkDir){
                        $this->setResponse(104);
                        $this->buildResponse();
                        exit;
                    }
                }
                $tableFileName = $backupDBDir."/".$table.'-db.sql';
                $tableFileName = wp_normalize_path($tableFileName);
                $return = "";

                $total_rows = $globalCon->get_var("SELECT COUNT(*) FROM $table");
                if(!is_numeric($total_rows)){
                    $this->setResponse(111);
                    $this->buildResponse();
                    exit;
                }

                //START

                if ($offset == 0) {

                    @unlink($tableFileName);

                    $return .= "\n--\n-- View table structure for view table `$table`\n--\n\n";

                    $table_creation_query = '';
                    $table_creation_query .= "DROP TABLE IF EXISTS `$table`;";
                    $table_creation_query .= "\n";
                    $table_creation_query .= "DROP VIEW IF EXISTS `$table`;";
                    $table_creation_query .= "\n";

                    $table_create = $globalCon->get_row("SHOW CREATE TABLE $table", ARRAY_A);
                    if ($table_create === false) {
                        $this->setResponse(107);
                    }

                    if (!isset($table_create['View'])) {
                        //not a view table
                        $this->setResponse(114);
                    }

                    $create_view_sql = $table_create['Create View'];

                    $create_view_sql = wpmerge_normalise_create_view_sql($create_view_sql, $table);

                    $table_creation_query .= $create_view_sql.";";

                    $return .= $table_creation_query . "\n";
                    $handle = fopen($tableFileName, 'w');
                    fwrite($handle, $return);
                    fclose($handle);
                    $return = '';
                }
                
                $opt = array(
                    'eof' => true,
                    'offset' => 1,
                    'is_phpdump' => true,
                    'db_prefix' => $this->getDBPrefix(),
               //     'hash' => $hash,
                    'file' => $tableFileName
                );
                $this->setResponse(210, $opt);
            } else {
                $this->setResponse(106);
            }
        }
        public function createSQL()
        {
            if(!file_exists(WPMERGE_TEMP_DIR)){
                $mkDir = mkdir(WPMERGE_TEMP_DIR, 0775);
                if(!$mkDir){
                    $this->setResponse(110);
                    $this->buildResponse();
                    exit;
                }
            }
            if(!$this->is_remote){
                if($this->is_phpdump){
                    $this->createSQLPHP();
                }
                else{
                    $this->createSQLLocal();
                }
            }
            else{
                $this->createSQLRemote();
            }
        }

        public function createSQLRemote()
        {
            if($this->is_phpdump)
            $this->createSQLPHP();
            else {
                $checkDump = $this->find_working_sqldump();
                //echo $checkDump;
                //$checkDump = false;

                if(!$checkDump)
                $this->createSQLPHP();
                else
                $this->createSQLServerDump($checkDump);
            }
        }

        private function compressDBBackups()
        {
            if (!$this->files) {
                $this->setResponse(112);
            }

            if (!wpmerge_is_gz_available()) {
                $this->setResponse(113);
            }

            $break = false;
            $response = $this->files;

            foreach ($this->files as $file => $files_meta) {

                if ($this->checkTimeBreak()) {
                    wpmerge_debug::log(array(),'-----------compressDBBackups checkTimeBreak----------------');
                    $break = true;
                    break;
                }

                $file   = $this->doUtf8Decode($file);

                $offset = empty($files_meta['current_offset']) ? 0 : $files_meta['current_offset'];

                if ( ( isset($files_meta['status']) && $files_meta['status'] === 'completed') || $offset === -1) {
                    wpmerge_debug::log($file,'-----------compressDBBackups already finished----------------');
                    $response[$file] = array(
                        'status' => 'completed',
                        'current_offset' => -1,
                    );
                    continue;
                }

                $result = $this->gz_compress_file($file, $offset);

                if ($result === true) {
                    $response[$file] = array(
                        'status' => 'completed',
                        'current_offset' => -1,
                    );
                    continue;
                } else if (isset($result['offset'])) {
                    $response[$file] = array(
                        'status' => 'paused',
                        'current_offset' => $result['offset'],
                    );
                    $break = true;
                    break;
                } else if (isset($result['error'])) {
                    $response = $result['error'];
                    $break = true;
                    break;
                }
            }

            if ($break) {
                $this->setResponse(217, $response);
            }else{
                $this->setResponse(218, array('status' => 'completed'));
            }

        }

        private function gz_compress_file($source, $offset, $level = 9){

             wpmerge_debug::log(func_get_args(),'-----------gz_compress_file----------------');

            if (filesize($source) < 5 ) {
                wpmerge_debug::log(array(),'-----------FILE contains nothing so delete it and skip compression----------------');
                return @unlink($source);
            }

            wpmerge_debug::log(func_get_args(), "--------" . __FUNCTION__ . "--------");
            wpmerge_debug::log(filesize($source),'-----------filesize($source)----------------');

            $dest = $source . '.gz';
            $mode = 'ab' . $level;

            $break = false;

            $fp_out = gzopen($dest, $mode);

            if (empty($fp_out)) {
                return array('error' => 'cannot open gzip file');
            }

            $fp_in = fopen($source,'rb');

            if (empty($fp_in)) {
                return array('error' => 'cannot open backup file');
            }

            fseek($fp_in, $offset);

            while (!feof($fp_in)){

                gzwrite($fp_out, fread($fp_in, 1024 * 1024 * 5)); //read 5MB chunk

                if($this->checkTimeBreak()){
                    $break = true;
                    $offset = ftell($fp_in);
                    break;
                }
            }

            fclose($fp_in);
            gzclose($fp_out);

            if ($break) {
                return array('offset' => $offset);
            }

            @unlink($source);
            return true;
        }

       public function doUtf8($string)
        {
            if (preg_match('!!u', $string))
        {
                 return $string;
        }
            else
            {
                return utf8_encode($string);
            }
        }
    public function doUtf8Decode($string)
    {
          if (preg_match('!!u', $string))
        {
                 return utf8_decode($string);
        }
            else
            {
                return $string;
            }

    }
         /* Run SQL
         * @return NULL
         */
    public function runSQL()
    {
        global $globalCon;
        $globalCon = $this->connectDB();
        $tempQuery = '';
        // $query = file_get_contents("db-backup.sql");
        $this->resetTempQuery();

        $globalCon->query('SET FOREIGN_KEY_CHECKS = 0');//sometime queries will resumed in middle of sql file. it better to have this.        

        if ($this->files && $globalCon) {

            $opt   = array();
            $array = (array) $this->files;

            foreach ($array as $files) {
                $files = (array) $files;

                //$file = $this->removeSlashes($files['name']);
                $file = $files['name'];

                if (is_file($file)) {

                        $this->setOffset($files['current_offset']);

                        $current_query = '';

                        $next_offset = $prev_index = $this->offset;

                        $file = new SplFileObject($file);
                        $file->seek($this->offset);


                        $this_lines_count = 0;
                        //$loop_iteration = 0;
                        $missed_query = array();

                        while (!$file->eof()) {
                            
                            //$loop_iteration++;
                            $line = $file->current();
                            $file->next();
                            $next_offset = $file->key();

                            $lineCheck = substr($line, 0, 2);
                            if ( $lineCheck == '--' || $lineCheck == '' ) {
                                continue; // Skip it if it's a comment
                            }


                            $current_query .= $line;
                            if (substr(trim($line), -1, 1) != ';') {
                                continue;
                            }

                            $result = $this->runQuery($current_query);
                            $GLOBALS['EZSQL_ERROR'] = array();//query error logger, fix for memory leak by wordpress

                            if($result===false)
                            {
                                $last_mysql_errno = mysqli_errno($globalCon->dbh);
                                if(empty($globalCon->last_error)){
                                    $globalCon->last_error = mysqli_error($globalCon->dbh);
                                }
                                // $possible_error_no = null;
                                // if(strpos(trim($globalCon->last_error), 'Duplicate entry') === 0){
                                //     $possible_error_no = 1062;//another possible 1586
                                // }
                                // //$queryError = $this->processSQLError($globalCon->last_error_no);
                                $queryError = $this->processSQLError($last_mysql_errno);
                                if($queryError){
                                    $_current_line_no = $next_offset -1;
                                    $debug_info = array(
                                        'query' => $current_query,
                                        'error' => $globalCon->last_error,
                                        'errno' => $last_mysql_errno,
                                        'file_name' => $files['name'],
                                        'line' => $_current_line_no
                                    );
                                    if(isset($GLOBALS['wpmerge_log_prod_clone_db_import_missed_queries'])){
                                        wpmerge_debug::log_prod_clone_db_import_missed_queries($debug_info, 'missed_query');
                                    }
                                    $debug_info['query'] = substr($debug_info['query'], 0, 100);
                                    $missed_query[]= base64_encode(json_encode($debug_info));
                                }
                            }

                            $current_query = '';

                            if( $this->checkTimeBreak() ){
                                break;
                            }
                        }
                        if(!$file->eof()){
                            wpmerge_debug::log($current_query, '--------runSQL current_query time break--------');
                        }
                        if(!empty($missed_query)){
                            wpmerge_debug::log($missed_query, '--------runSQL missed_query --------');
                        }

                        $this->runQuery('UNLOCK TABLES;');

                        $opt = array(
                            'file'          => $files['name'],
                            'eof'           => $file->eof(),
                            'next_offset'   => $next_offset,
                            //'loopIteration' => $loop_iteration,
                            'missed_query'  => $missed_query
                        );

                        wpmerge_debug::log($opt, '--------runSQL $opt--------');

                        $this->setResponse(212, $opt);

                } else {
                    $this->runQuery('UNLOCK TABLES;');
                    $this->setResponse(102, $opt);
                }
            }
        }
    }

        public function runQuery($query)
        {
            global $globalCon;
            $globalCon = $this->connectDB();
            
            //changing db table prefix and its reference inside the table content in some cases.
            if(isset($this->dbprefix) && $this->dbprefix!='' && !empty($this->newDBprefix)){
                //$query = wpmerge_replace_table_prefix_in_query($this->dbprefix,$this->newDBprefix, $query);
                $query = wpmerge_replace_table_prefix_in_query_use_full_table_names($this->dbprefix,$this->newDBprefix, $this->oldTablesList, $this->newTablesList, $query);
            }

            $result = $globalCon->query($query);
            //if($result===false && $globalCon->last_error_no==1273)

            $last_mysql_errno = mysqli_errno($globalCon->dbh);
            if(empty($globalCon->last_error)){
                $globalCon->last_error = mysqli_error($globalCon->dbh);
            }

            // $possible_error_no = null;
            // if(strpos(trim($globalCon->last_error), 'Unknown collation') === 0){
            //     $possible_error_no = 1273;
            // }
            if($result===false && $last_mysql_errno==1273){
                $query = str_ireplace('utf8mb4_unicode_520_ci','utf8mb4_unicode_ci',$query);
                $result = $globalCon->query($query);
            }
            return $result;

        }

        public function processSQLError($error)
        {
        if($error != 1062)
        return true;
        return false;
        }

        public function findAndReplace( $from = '', $to = '', $data = '', $serialised = false) {

            try {

                if ( is_string( $data ) && ( $unserialized = @unserialize( $data ) ) !== false ) {

                    $data = $this->findAndReplace( $from, $to, $unserialized, true );
                }

                elseif ( is_array( $data ) ) {
                    $_tmp = array( );
                    foreach ( $data as $key => $value ) {
                        $_tmp[ $key ] = $this->findAndReplace( $from, $to, $value, false );
                    }

                    $data = $_tmp;
                    unset( $_tmp );
                }

                elseif ( is_object( $data ) ) {
                    $_tmp = $data;
                    $props = get_object_vars( $data );
                    foreach ( $props as $key => $value ) {
                        $_tmp->$key = $this->findAndReplace( $from, $to, $value, false );
                    }

                    $data = $_tmp;
                    unset( $_tmp );
                }

                else {
                    if ( is_string( $data ) ) {
                        $data = $this->str_replace( $from, $to, $data );
                    }
                }
                //file_put_contents(dirname(__FILE__)."/__debugger1.php",$tableName.'-'.var_export($data,1)."\n<br><br>\n",FILE_APPEND );
                if ( $serialised )
                    return serialize( $data );

            } catch( Exception $error ) {}

            return $data;
        }

        public function str_replace( $search, $replace, $string, &$count = 0 ) {

                return str_ireplace( $search, $replace, $string, $count );

        }

        public static function mb_str_replace( $search, $replace, $subject, &$count = 0 ) {
            if ( ! is_array( $subject ) ) {
                $searches = is_array( $search ) ? array_values( $search ) : array( $search );
                $replacements = is_array( $replace ) ? array_values( $replace ) : array( $replace );
                $replacements = array_pad( $replacements, count( $searches ), '' );

                foreach ( $searches as $key => $search ) {
                    $parts = mb_split( preg_quote( $search ), $subject );
                    $count += count( $parts ) - 1;
                    $subject = implode( $replacements[ $key ], $parts );
                }
            } else {
                foreach ( $subject as $key => $value ) {
                    $subject[ $key ] = self::mb_str_replace( $search, $replace, $value, $count );
                }
            }

            return $subject;
        }

        public function runDBFinalModifications(){
            $result = array();
            $result['dbFixUserRoleTablePrefix'] = $this->dbFixUserRoleTablePrefix();
            $result['dbFixUserMetaTablePrefix'] = $this->dbFixUserMetaTablePrefix();
            $opt    =  $result;
            $this->setResponse(212, $opt);
        }

        private function dbFixUserRoleTablePrefix(){
            global $globalCon;
            $globalCon = $this->connectDB();
            $query = "
            UPDATE {$this->newDBprefix}options
            SET option_name = '{$this->newDBprefix}user_roles'
            WHERE option_name = '{$this->dbprefix}user_roles'
            LIMIT 1";
            $result = $globalCon->query($query);
        }

        private function dbFixUserMetaTablePrefix(){
            global $globalCon;
            $espaced_old_prefix = wpmerge_esc_table_prefix($this->dbprefix);
            $espaced_new_prefix = wpmerge_esc_table_prefix($this->newDBprefix);
            $is_old_prefix_is_subset_of_new_prefix = false;//say wp_ is old and wp_t1_ is new
            if( stripos($this->newDBprefix, $this->dbprefix) !== false ){
                $is_old_prefix_is_subset_of_new_prefix = true;
            }            

            $globalCon = $this->connectDB();
            $query = "
            UPDATE {$this->newDBprefix}usermeta
            SET meta_key = CONCAT('{$this->newDBprefix}', SUBSTR(meta_key, CHAR_LENGTH('{$this->dbprefix}') + 1))
            WHERE meta_key LIKE '{$espaced_old_prefix}%'";
            if($is_old_prefix_is_subset_of_new_prefix){
                $query .= " AND meta_key NOT LIKE '{$espaced_new_prefix}%'";//to avoid more than once replacing "meta_key NOT LIKE '{$espaced_new_prefix}%'" is used.
            }
            $result = $globalCon->query($query);
        }

        /**
         * Create Exclude File List
         * @return Array
         */
        public function getExcludeFileList()
        {
            $opt = array(
                $this->content_dir . "/wpmerge-temp",
                $this->content_dir . "/managewp/backups",
                $this->content_dir . "/" . md5('iwp_mmb-client') . "/iwp_backups",
                $this->content_dir . "/infinitewp",
                $this->content_dir . "/".md5('mmb-worker')."/mwp_backups",
                $this->content_dir . "/backupwordpress",
                $this->content_dir . "/contents/cache",
                $this->content_dir . "/content/cache",
                $this->content_dir . "/cache",
                $this->content_dir . "/logs",
                $this->content_dir . "/old-cache",
                $this->content_dir . "/w3tc",
                $this->content_dir . "/cmscommander/backups",
                $this->content_dir . "/gt-cache",
                $this->content_dir . "/wfcache",
                $this->content_dir . "/widget_cache",
                $this->content_dir . "/bps-backup",
                $this->content_dir . "/old-cache",
                $this->content_dir . "/updraft",
                $this->content_dir . "/nfwlog",
                $this->content_dir . "/upgrade",
                $this->content_dir . "/wflogs",
                $this->content_dir . "/tmp",
                $this->content_dir . "/backups",
                $this->content_dir . "/updraftplus",
                $this->content_dir . "/wishlist-backup",
                $this->content_dir . "/wptouch-data/infinity-cache/",
                $this->content_dir . "/mysql.sql",
                $this->content_dir . "/DE_clTimeTaken.php",
                $this->content_dir . "/DE_cl.php",
                $this->content_dir . "/DE_clMemoryPeak.php",
                $this->content_dir . "/DE_clMemoryUsage.php",
                $this->content_dir . "/DE_clCalledTime.php",
                $this->content_dir . "/DE_cl_func_mem.php",
                $this->content_dir . "/DE_cl_func.php",
                $this->content_dir . "/DE_cl_server_call_log_wptc.php",
                $this->content_dir . "/DE_cl_dev_log_auto_update.php",
                $this->content_dir . "/DE_cl_dev_log_auto_update.txt",
                $this->content_dir . "/debug.log",
                $this->content_dir . "/Dropbox_Backup",
                $this->content_dir . "/backup-db",
                $this->content_dir . "/updraft",
                $this->content_dir . "/w3tc-config",
                $this->content_dir . "/aiowps_backups",
                $this->abspath . "/wp-clone",
                $this->abspath . "/db-backup",
                $this->abspath . "/ithemes-security",
                $this->abspath . "/mainwp/backup",
                $this->abspath . "/backupbuddy_backups",
                $this->abspath . "/vcf",
                $this->abspath . "/pb_backupbuddy",
                $this->abspath . "/sucuri",
                $this->abspath . "/aiowps_backups",
                $this->abspath . "/gravity_forms",
                $this->abspath . "/mainwp",
                $this->abspath . "/snapshots",
                $this->abspath . "/wp-clone",
                $this->abspath . "/wp_system",
                $this->abspath . "/wpcf7_captcha",
                $this->abspath . "/wc-logs",
                $this->abspath . "/siteorigin-widgets",
                $this->abspath . "/wp-hummingbird-cache",
                $this->abspath . "/wp-security-audit-log",
                $this->abspath . "/freshizer",
                $this->abspath . "/report-cache",
                $this->abspath . "/cache",
                $this->abspath . "/et_temp",
                $this->abspath . "/wptc_restore_logs",
                $this->abspath . "/wp-admin/error_log",
                $this->abspath . "/wp-admin/php_errorlog",
                $this->abspath . "/error_log",
                $this->abspath . "/error.log",
                $this->abspath . "/debug.log",
                $this->abspath . "/WS_FTP.LOG",
                $this->abspath . "/security.log",
                $this->abspath . "/wp-tcapsule-bridge.zip",
                $this->abspath . "/dbcache",
                $this->abspath . "/pgcache",
                $this->abspath . "/objectcache",
            );
            $this->setResponse(215, $opt);
        }

        private function setExcludeArrayFromGetExcludeFileList(){
            $this->getExcludeFileList();
            if($this->response['code'] >= 200){
                $this->excludeArray = $this->response['value'];
            }
            $this->response = array();//resetting
        }

        /**
         * Modify WpConfig
         * @param  String
         * @param  String
         * @param  Boolean
         * @param  Object
         * @return Boolean
         */
        public function modifyWpConfig($siteURL, $newSiteURL, $isMultiSite, $wpdb)
        {
            $path = $this->removeSlashes('./wp-config.php');
            $content = file_get_contents($path);
            if ($content) {
                $content = str_replace($siteURL, $newSiteURL, $content);
                $content = str_replace('define( ', 'define(',$content);
                $content = str_replace('define(\'DB_NAME\'', 'define(\'DB_NAME\', \'' . $wpdb->dbname . '\');//', $content);
                $content = str_replace('define(\'DB_USER\'', 'define(\'DB_USER\', \'' . $wpdb->dbuser . '\');//', $content);
                $content = str_replace('define(\'DB_PASSWORD\'', 'define(\'DB_PASSWORD\', \'' . $wpdb->dbpassword . '\');//', $content);
                $content = str_replace('define(\'DB_HOST\'', 'define(\'DB_HOST\', \'' . $wpdb->dbhost . '\');//', $content);
                if ($isMultiSite) {
                    $staging_args    = parse_url($newSiteURL);
                    $staging_path  = rtrim($staging_args['path'], "/"). "/";
                    $content = str_replace('define(\'PATH_CURRENT_SITE\'', 'define(\'PATH_CURRENT_SITE\', \'' . $staging_path . '\');//', $content);
                }
                $content = $this->removeUnwantedDataFromWpConfig($content);
                $content = $this->removeUnwantedCommentLines($content, $is_wp_config = true);
                $newConf = fopen($path, "w");
                $fwrite = fwrite($newConf, $content);
                $this->setConfigMemoryLimit('512M');
                if ($fwrite === false) {
                    return false;
                }
                return true;
            } else {
                return false;
            }
        }

        public function setConfigMemoryLimit($limit = '512M'){
            $path = $this->removeSlashes('./wp-config.php');
            $content = file_get_contents($path);
            $content = str_replace("require_once", "define('WP_MEMORY_LIMIT', '512M'); \n\nrequire_once", $content);
            $newConf = fopen($path, "w");
            $fwrite = fwrite($newConf, $content);
        }
        /**
         * Remove Unwanted Data From WpConfig
         * @param  String
         * @return String
         */
        private function removeUnwantedDataFromWpConfig($content)
        {
            $unwanted_words_match = array("WP_SITEURL", "WP_HOME", "WP_MEMORY_LIMIT","FORCE_SSL_ADMIN");
            foreach ($unwanted_words_match as $words) {
                $replace_match = '/^.*' . $words . '.*$(?:\r\n|\n)?/m';
                $content = preg_replace($replace_match, '', $content);
            }
            return $content;
        }

        /**
         * Remove Unwanted Comment Lines
         * @param  String
         * @param  boolean
         * @return String
         */
        public function removeUnwantedCommentLines($content, $is_wp_config = false)
        {
            $lines = explode("\n", $content);
            if ($is_wp_config) {
                $remove_comment_lines = array('DB_NAME', 'DB_USER', 'DB_PASSWORD', 'DB_HOST', 'PATH_CURRENT_SITE', 'table_prefix');
            }
            foreach ($lines as $key => $line) {
                foreach ($remove_comment_lines as $comment_lines) {
                    if (strpos($line, $comment_lines) !== false) {
                        $strpos = strpos($line, "//");
                        if($strpos === false)
                            continue;
                        $lines[$key] = substr($line, 0, $strpos);
                    }
                }
            }
            return implode("\n", $lines);
        }

        public function createHtaccess($url, $isMulti = false){
            $args    = parse_url($url);
            $string  = rtrim($args['path'], "/");

            if($isMulti){
                $data = "\nRewriteBase ".$string."/\nRewriteRule ^index\.php$ - [L]\n\n ## add a trailing slash to /wp-admin\nRewriteRule ^([_0-9a-zA-Z-]+/)?wp-admin$ $1wp-admin/ [R=301,L]\n\nRewriteCond %{REQUEST_FILENAME} -f [OR]\nRewriteCond %{REQUEST_FILENAME} -d\nRewriteRule ^ - [L]\nRewriteRule ^([_0-9a-zA-Z-]+/)?(wp-(content|admin|includes).*) $2 [L]\nRewriteRule ^([_0-9a-zA-Z-]+/)?(.*\.php)$ $2 [L]\nRewriteRule . index.php [L]";
            }else{
                $data = "# BEGIN WordPress\n<IfModule mod_rewrite.c>\nRewriteEngine On\nRewriteBase ".$string."/\nRewriteRule ^index\.php$ - [L]\nRewriteCond %{REQUEST_FILENAME} !-f\nRewriteCond %{REQUEST_FILENAME} !-d\nRewriteRule . ".$string."/index.php [L]\n</IfModule>\n# END WordPress";
            }
            file_put_contents('.htaccess', $data);
        }
        /**
         * Create WordPress Config action
         * @return NULL
         */
        public function createWordPressConfig()
        {
            $configParams = $this->configParams;
            $configParams->isMultiSite = $this->isMultiSite();
            $modify = $this->modifyWpConfig($configParams->siteURL, $configParams->newSiteURL, $configParams->isMultiSite, $configParams->wpDB);
            $htaccess = $this->createHtaccess($configParams->newSiteURL, $configParams->isMultiSite);
            if ($modify) {
                $this->setResponse(216);
            } else {
                $this->setResponse(108);
            }
        }

        public function isMultiSite(){
            if(function_exists('is_multisite')){
                return is_multisite();
            }
            return false;//currently not supporting multi site, when we sport need to improve this function
        }

        /**
         * Begin API Process
         * @param NULL
         * @return NULL
         */
        public function process()
        {
            if ($this->isValidRequest()) {
                // if($this->action == 'ping'){
                //     $opt = 'pong' ;
                //     $this->setResponse(109,$opt);
                //     $this->buildResponse();
                //     exit;
                // }
                if ($this->auth()) {
                    $action = $this->action;
                    switch ($action) {
                        case "getDirectoryHash":
                            $this->getDirectoryHash();
                            break;

                        case "getFileHash":
                            $this->getFileHash();
                            break;

                        case "getDirMeta":
                            $this->getDirMeta();
                            break;

                        case "getFile":
                            $this->getFile();
                            break;

                        case "getFileGroup":
                            $this->getFileGroup();
                            break;
                        case "putFileGroup":
                            $this->putFileGroup();
                            break;

                        case "putFile":
                            $this->putFile();
                            break;

                        case "makeDir":
                            $this->makeDir();
                            break;

                        case "removeDir":
                            $this->removeDir();
                            break;

                        case "deleteSQL":
                            $this->deleteSQL();
                            break;
                        
                        case "deleteSQLBackupDir":
                            $this->deleteSQLBackupDir();
                            break;

                        case "listTables":
                            $this->listAllSQLTables();
                            break;

                        case "createSQL":
                            $this->createSQL();
                            break;

                        case "createViewTableSQLPHP":
                            $this->createViewTableSQLPHP();
                            break;

                        case "compressDBBackups":
                            $this->compressDBBackups();
                            break;

                        case "runSQL":
                            $this->runSQL();
                            break;

                        case "findAndReplace":
                            $this->findAndReplace();
                            break;
                        
                        case "runDBFinalModifications":
                            $this->runDBFinalModifications();
                            break;

                        case "getExcludeFileList":
                            $this->getExcludeFileList();
                            break;

                        case "createWordPressConfig":
                            $this->createWordPressConfig();
                            break;
                        case "ping":
                                $opt = 'pong' ;
                                $this->setResponse(109,$opt);
                                $this->buildResponse();
                                exit;
                                break;

                        default:
                            $this->invalidAction();
                            break;
                    }
                } else {
                    $this->setResponse(99);
                }
            } else {
                $this->invalidAction();
            }
        }
    }
}
