<?php

namespace Eckinox\Nex\Api;

if (!function_exists('curl_init')) {
    throw new Exception('Canada Post SDK needs the CURL PHP extension.');
}

define('CANADA_POST_SSL_CERTIFICATE', dirname(__FILE__) . '/CanadaPost/cacert.pem');

class Canada_Post_API_Exception extends Exception {

    public function __construct($message, $code = 0, Exception $previous = null) {

        parent::__construct($message, (int) $code, $previous);
    }

    /**
     * To make debugging easier.
     *
     * @return string The string representation of the error
     */
    public function __toString() {

        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}

abstract class CanadaPost {
    
    CONST CURL_ERROR_PROCESSING = 1000;
    CONST CURL_ERROR_INVALID_XML_RESPONSE = 1001;
    CONST CURL_ERROR_MISSING_POSTFIELDS = 1002;

    /**
     * Default HTTP Method
     */
    private static $DEFAULT_REQUEST_METHOD = 'GET';

    /**
     * Canada Post Web Service URL
     */
    private static $WEB_SERVICE_URL = array(
        'development' => 'https://ct.soa-gw.canadapost.ca/',
        'production' => 'https://soa-gw.canadapost.ca/'
    );

    /**
     * Default options for curl.
     */
    protected static $DEFAULT_CURL_OPTS = array(
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_CAINFO => CANADA_POST_SSL_CERTIFICATE,
        CURLOPT_HTTPAUTH => CURLAUTH_BASIC
    );

    public function __construct($config, $sandbox = null) {
        $sandbox = $sandbox === null ? Nex::config('api.CanadaPost.sandbox') : $sandbox;
        $this->mode = $sandbox ? 'development' : 'production';
        $this->request_method = self::$DEFAULT_REQUEST_METHOD;
        $this->XML_response = NULL;
        $this->curl_response = NULL;
        $this->query_string_parameters = [];
        $this->curl_options = [];

        $this->setCredentials($config['username'], $config['password']);

        $this->initializeXML();
    }

    private function prepareCurlOptions() {
        $curl_options = self::$DEFAULT_CURL_OPTS + $this->curl_options;

        foreach ($curl_options as $key => $value) {
            curl_setopt($this->curl, $key, $value);
        }
    }

    private function getCurlInfo($opt) {
        return curl_getinfo($this->curl, $opt);
    }

    private function validateCurlOptions() {
        if ($this->request_method == 'POST') {

            if (!isset($this->curl_options[CURLOPT_POSTFIELDS])) {
                throw new Canada_Post_API_Exception("Missing mandatory 'CURLOPT_POSTFIELDS' CURL option!", self::CURL_ERROR_MISSING_POSTFIELDS);
            }
        }
    }

    private function validateResponse() {
        $allowed_content = array(
            'application/pdf',
                #'application/octet-stream'
        );

        $contentType = $this->getContentType();

        if (in_array($contentType, $allowed_content)) {

            $this->response = $this->curl_response;
        } elseif (strpos($contentType, 'xml') > -1) {

            libxml_use_internal_errors(true);

            $xml = simplexml_load_string('<root>' . preg_replace('/<\?xml.*\?>/', '', $this->curl_response) . '</root>');

            if (!$xml) {

                $msg = '';

                foreach (libxml_get_errors() as $error) {
                    $msg .= "\t" . $error->message;
                }

                $msg .= "Invalid XML response" . "\n" . $msg;

                throw new Canada_Post_API_Exception($msg, self::CURL_ERROR_INVALID_XML_RESPONSE);
            } else {

                if ($xml->{'messages'}) {

                    $messages = $xml->{'messages'}->children('http://www.canadapost.ca/ws/messages');

                    foreach ($messages as $message) {
                        /*
                          echo 'Error Code: ' . $message->code . "\n";
                          echo 'Error Msg: ' . $message->description . "\n\n";
                         */

                        $code = $message->code == 'Server' ? 9999 : $message->code;

                        throw new Canada_Post_API_Exception($message->description, $code);
                    }
                }

                $this->response = $xml;
            }
        }
    }

    private function appendURLQueryString() {
        $query_string = [];

        foreach ($this->query_string_parameters as $key => $value) {
            $query_string[] = $key . "=" . urlencode($value);
        }

        if ($query_string) {
            $this->request_URL .= '?' . implode('&', $query_string);
        }
    }
    
    protected function setRequestMethod($method = null) {
        if (!is_null($method) && in_array($method, array('GET', 'POST'))) {
            if ($method == 'POST') {
                $this->request_method = 'POST';
                $this->addCurlOption(CURLOPT_POST, true);
            }
        }
    }

    protected function setCredentials($username, $password) {
        $this->addCurlOption(CURLOPT_USERPWD, $username . ':' . $password);
    }

    protected function addCurlOption($opt, $value) {
        $this->curl_options[$opt] = $value;
    }

    protected function addCurlHeader($headers = []) {
        if (is_string($headers)) {
            $headers = (array) $headers;
        }

        if (is_array($headers) && count($headers) > 0) {
            $this->addCurlOption(CURLOPT_HTTPHEADER, $headers);
        }
    }

    protected function setRequestURLSuffix($url) {
        $this->request_URL = self::$WEB_SERVICE_URL[$this->mode] . $url;
    }

    protected function setRequestBody($requestBody) {
        if (!is_null($requestBody)) {

            if ($this->request_method == 'POST') {
                $this->addCurlOption(CURLOPT_POSTFIELDS, $requestBody);
            }
        }
    }
    
    protected function addQueryStringParameter($key, $value) {
        $this->query_string_parameters[$key] = $value;
    }

    public function executeRequest() {
        if (method_exists($this, 'preRequest')) {
            $this->preRequest();
        }

        $this->appendURLQueryString();
        $this->curl = curl_init($this->request_URL);
        $this->prepareCurlOptions();
        $this->validateCurlOptions();
        $this->curl_response = curl_exec($this->curl); // Execute REST Request

        if ($errorNumber = curl_errno($this->curl)) {
            throw new Canada_Post_API_Exception('CURL processing error !', $errorNumber);
        }

        $this->validateResponse();

        if (method_exists($this, 'postRequest')) {
            $this->postRequest();
        }

        curl_close($this->curl);
        $this->processResponse();
    }

    public function getHTTPCode() {
        return $this->getCURLInfo(CURLINFO_HTTP_CODE);
    }

    public function getContentType() {
        return $this->getCURLInfo(CURLINFO_CONTENT_TYPE);
    }

    public function getResponse() {
        return $this->response;
    }

    public function getCurlResponse() {
        return $this->curl_response;
    }

    abstract protected function initializeXML();

    abstract protected function processResponse();
}