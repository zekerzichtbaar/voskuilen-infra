<?php
/**
 * WPMerge
 * Copyright (c) 2018 Revmakx LLC
 * revmakx.com
 */

if(!defined('ABSPATH')){ exit; }

class wpmerge_service_auth{

    private static function do_auth_with_action($creds, $action, $data = false){

        if(empty($creds['email']) || empty($creds['password']) || empty($action)){
            throw new wpmerge_exception('invalid_request');
        }

        if(!in_array($action, array('checkValidity', 'addSite'))){
            throw new wpmerge_exception('invalid_request');
        }

        $url = WPMERGE_SERVICE_URL;

        $request_data = array();
        if(!empty($data) && is_array($data)){
            $request_data = $data;
        }

        $request_data['email'] =  base64_encode($creds['email']);
        $request_data['pass'] =  base64_encode($creds['password']);
        $request_data[$action] =  '1';
        $request_data['URL'] =  trailingslashit(get_option('siteurl'));

        $body = $request_data;

        $http_args = array(
            'method' => "POST",
            'timeout' => 30,
            'body' => $body
        );

        try{
            $response = wpmerge_do_call($url, $http_args);
            wpmerge_debug::log($response,'-----------$service_response----------------');
            $response_data = wpmerge_get_response_from_json($response);
        }
        catch(wpmerge_exception $e){
            throw $e;
        }

        // if($response_data['status'] == 'success'){
        // 	wpmerge_save_service_user_creds($creds);
        // }

        if(empty($response_data) || !is_array($response_data)){
            throw new wpmerge_exception('invalid_response'); 
        }

        return $response_data;
    }

    private static function save_creds_info($creds){

        if(empty($creds['email']) || empty($creds['password']) || empty($creds['last_validated'])){
            return false;
        }

        $creds['password'] = base64_encode($creds['password']);

        $whitelist = array('email', 'password', 'last_validated', 'last_checked', 'status');
        $creds = array_intersect_key( $creds, array_flip( $whitelist ) );

        return wpmerge_update_option('service_user_creds_info', $creds);

    }

    public static function get_account_info(){
        $creds = self::get_creds_info();

        $whitelist = array('email', 'status');
        $info = array_intersect_key( $creds, array_flip( $whitelist ) );
        return $info;
    }

    private static function get_creds_info(){

        $creds = wpmerge_get_option('service_user_creds_info');
        if(empty($creds) || empty($creds['email']) || empty($creds['password'])){
            return false;
        }

        $creds['password'] = base64_decode($creds['password']);

        return $creds;
    }


    public static function login($creds){
        $creds['email'] = trim($creds['email']);
        $response_data = self::do_auth_with_action($creds, 'addSite');
        if(isset($response_data['success']) && $response_data['message'] == 'added'){
            $creds['status'] = 'valid';
            $creds['last_checked'] = time();
            $creds['last_validated'] = time();
            self::save_creds_info($creds);
            wpmerge_dev_remove_cron();
            wpmerge_dev_add_cron();
            return true;
        }
        elseif(isset($response_data['error']) && isset($response_data['message'])){
            throw new wpmerge_exception('service_'.$response_data['message']); 
        }
        else{
            throw new wpmerge_exception('service_invalid_response');
        }
    }

    public static function check_validity(){//check the service

        try{
            $creds = self::get_creds_info();
            if($creds === false){
                return false;
            }

            if($creds['status'] != 'valid'){
                return false;
            }

            $response_data = self::do_auth_with_action($creds, 'checkValidity');

            if(isset($response_data['success']) && $response_data['message'] == 'valid'){
                $creds['status'] = 'valid';
                $creds['last_checked'] = time();
                $creds['last_validated'] = time();
                self::save_creds_info($creds);
                return true;
            }
            elseif(isset($response_data['error']) && $response_data['message'] == 'login_error'){
                $creds['status'] = 'error';
                //$creds['error_msg_slug'] = $response_data['message'];
                $creds['last_checked'] = time();
                self::save_creds_info($creds);
                return false;
            }
        }
        catch(wpmerge_exception $e){
            $error = $e->getError();
            $error_msg = $e->getErrorMsg();
        }
    }

    public static function is_valid($cache=false){//check the db

        $creds = self::get_creds_info();
        if(!isset($creds['last_validated']) || !isset($creds['status'])){
            return false;
        }
        if($creds['status'] == 'valid'){
            if($creds['last_validated'] >  time() - (12 * 60 * 60)){
                return true;
            }
            elseif($cache === false){
                self::check_validity();
                return self::is_valid(true);//(true) to avoid recurring
            }            
        }
        return false;
    }

}