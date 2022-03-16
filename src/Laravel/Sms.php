<?php

namespace Hemend\Library\Laravel;

class Sms {
    static public $api_key;
    static public $version;
    static public $is_test;

    /**
     * Gets config parameters for sending request.
     *
     * @param string $api_key   API Key
     * @param string $version   API Version
     * @param string $is_test   Is Test Api

     *
     * @return void
     */
    public function __construct($api_key, $version, $is_test)
    {
        self::$api_key = $api_key;
        self::$version = $version;
        self::$is_test = $is_test;
    }

    /**
     * Gets Api Url.
     *
     * @return string Indicates the Url
     */
    protected function getAPIMessageSendUrl() {
        return 'https://sms.hemend.com/api/'.(self::$is_test ? 'test' : 'main').'/'.self::$version;
    }

    /**
     * Send sms.
     *
     * @param mobile_number $mobile_number mobile number
     * @param string      $message      message
     * @param string      $send_date_time  Send Date Time

     *
     * @return string Indicates the sent sms result
     */
    public function sendMessage(string $mobile_number, string $message, string $send_date_time=null)
    {
        $token = $this->_getToken();

        if ($token) {
            $postData = array(
                'message' => $message,
                'mobile_number' => $mobile_number,
                'send_date_time' => $send_date_time,
            );

            $url = $this->getAPIMessageSendUrl() . '/message.send';
            $response = $this->_execute($postData, $url, $token);
            $result = is_object($response) ? $response : false;
        } else {
            $result = false;
        }

        return $result;
    }

    private function _getCacheTokenKey()
    {
        return 'token_' . self::$is_test;
    }

    /**
     * Gets token key for all web service requests.
     *
     * @return string Indicates the token key

     */
    private function _getToken()
    {
        $cache_token_key = $this->_getCacheTokenKey();
//        cache()->forget($cache_token_key); // Delete cache

        $token = cache()->has($cache_token_key) ? cache($cache_token_key) : false;

        if(!$token) {
            $postData = array(
                'api_key' => self::$api_key,
            );

            $url = $this->getAPIMessageSendUrl() . '/auth.generateToken';
            $token_res = $this->_execute($postData, $url);

            if (is_object($token_res) && $token_res->status_code === 'OK') {
                $token = $token_res->access->token;
                $expire_time = strtotime($token_res->access->expires_in) - time() - 60;
                cache([$cache_token_key => $token], $expire_time);
            }
        }

        return $token;
    }

    /**
     * Executes the main method.
     *
     * @param postData[] $postData array of json data
     * @param string     $url      url
     * @param string     $token    token string
     *
     * @return string Indicates the curl execute result
     */
    private function _execute($postData, $url, $token=null)
    {
        $postString = json_encode($postData);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array_filter([
            'Content-Type: application/json',
            $token ? 'Authorization: Bearer '.$token : null
        ]));
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postString);

        $result = curl_exec($ch);
        curl_close($ch);

        return json_decode($result);
    }
}