<?php
/*
	ezEngage (C)2011  http://ezengage.com
*/

include_once realpath(dirname(__FILE__)). '/httpclient.class.php';

class EzEngageApiClient {
    public $timeout = 30; 
    public $connecttimeout = 30;  
    public $ssl_verifypeer = FALSE; 
 
    protected $api_base_url = 'http://api.ezengage.com/api/v1/';
    protected $format = 'json';
    protected $last_response = array();

    function EzEngageApiClient($app_key){
        $this->app_key = $app_key;
    }

    function getProfile($token){
        $url = $this->api_base_url . 'profile.' . $this->format . 
               '?app_key=' . urlencode($this->app_key) . '&token='. urlencode($token); 
        list($status_code, $content) = $this->http($url, 'GET');
        if($status_code == 200){
            $profile = json_decode($content, true);     
            $this->last_response =  array($status_code, $content); 
            return $profile;
        }
        else{
            $this->last_response =  array($status_code, $content);
            return false;
        }
    } 

    function updateStatus($identity, $status){
        $url = $this->api_base_url . 'status.' . $this->format;
        $payload = 'app_key=' . urlencode($this->app_key) . '&identity='. urlencode($identity) . '&status=' . urlencode($status); 
        list($status_code, $content) = $this->http($url, 'POST', $payload);
        $this->last_response =  array($status_code, $content);
        return $status_code == 201;
    }

    function getLastResponse(){
        return $this->last_response;
    }

    /** 
     * Make an HTTP request 
     * 
     * @return array(int, string) status_code and response body
     */ 
    function http($url, $method, $payload = NULL) { 
        #TODO open curl
        if(false && function_exists('curl_init')){
            return $this->http_curl($url, $method, $payload);
        }        
        else{
            return $this->http_fsock($url, $method, $payload);
        }
    }

    function http_fsock($url, $method, $payload){
        $bits = parse_url($url);
        $host = $bits['host'];
        $port = isset($bits['port']) ? $bits['port'] : 80;
        $path = isset($bits['path']) ? $bits['path'] : '/';
        if (isset($bits['query'])) {
            $path .= '?'.$bits['query'];
        }
        $client = new HttpClient($host, $port);
        if($method == 'GET'){
            $client->get($path);
        }
        else{
            $client->post($path, $payload);
        }
        return array($client->getStatus(), $client->getContent());
    }

    function http_curl($url, $method, $payload){
        $ci = curl_init(); 
        curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, $this->connecttimeout); 
        curl_setopt($ci, CURLOPT_TIMEOUT, $this->timeout); 
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, TRUE); 
        curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, $this->ssl_verifypeer); 
        curl_setopt($ci, CURLOPT_HEADER, FALSE); 

        switch ($method) { 
            case 'POST': 
                curl_setopt($ci, CURLOPT_POST, TRUE); 
                if (!empty($payload)) { 
                    curl_setopt($ci, CURLOPT_POSTFIELDS, $payload); 
                } 
                break; 
        } 

        curl_setopt($ci, CURLOPT_URL, $url); 

        $response = curl_exec($ci); 
        $http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE); 

        curl_close ($ci); 
        return array($http_code, $response); 
    } 


}



function __client_test(){
    $client = new EzEngageApiClient('tapp-key');
    $identity = 'http://t.sina.com.cn/1322895797';
    $status = 'wp_test ' . time();
    $ret = $client->updateStatus($identity, $status);
    var_dump($ret);
}

//__client_test();
