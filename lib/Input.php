<?php

namespace Eckinox\Nex;

use Eckinox\{
    singleton
};

class Input {
    use singleton;
    
    // IP address of current user
    protected $ip_address = null;

    // Input singleton
    private static $_instance = null;
    
    protected function __construct() {
        if (is_array($_GET)) {
            foreach ($_GET as $key => $val) {
                // Sanitize $_GET
                $_GET[$key] = $this->cleanInputData($val);
            }
        } else {
            $_GET = [];
        }

        if (is_array($_POST)) {
            foreach ($_POST as $key => $val) {
                // Sanitize $_POST
                $_POST[$key] = $this->cleanInputData($val);
            }
        } else {
            $_POST = [];
        }

        if (is_array($_COOKIE)) {
            foreach ($_COOKIE as $key => $val) {
                // Sanitize $_COOKIE
                $_COOKIE[$key] = $this->cleanInputData($val);
            }
        } else {
            $_COOKIE = [];
        }
    }
    
    /**
     * Fetch an item from the $_REQUEST array.
     *
     * @param string $key key to find
     * @param bool $xss_clean clean data from html tags or not
     * @param mixed $default default value to return when key doesnt exist
     *
     * @return  mixed
     */
    public function request($key = [], $xss_clean = FALSE, $default = '') {
        return $this->searchArray($_REQUEST, $key, $xss_clean, $default);
    }

    /**
     * Fetch an item from the $_GET array.
     *
     * @param string $key key to find
     * @param bool $xss_clean clean data from html tags or not
     * @param mixed $default default value to return when key doesnt exist
     *
     * @return  mixed
     */
    public function get($key = [], $xss_clean = FALSE, $default = '') {
        return $this->searchArray($_GET, $key, $xss_clean, $default);
    }

    /**
     * Fetch an item from the $_POST array.
     *
     * @param string $key key to find
     * @param bool $xss_clean clean data from html tags or not
     * @param mixed $default default value to return when key doesnt exist
     *
     * @return  mixed
     */
    public function post($key = [], $xss_clean = FALSE, $default = '') {
        return $this->searchArray($_POST, $key, $xss_clean, $default);
    }

    /**
     * Fetch an item from the $_COOKIE array.
     *
     * @param string $key key to find
     * @param bool $xss_clean clean data from html tags or not
     * @param mixed $default default value to return when key doesnt exist
     *
     * @return  mixed
     */
    public function cookie($key = [], $xss_clean = FALSE, $default = '') {
        return $this->searchArray($_COOKIE, $key, $xss_clean, $default);
    }

    /**
     * Fetch an item from the $_SERVER array.
     *
     * @param string $key key to find
     * @param bool $xss_clean clean data from html tags or not
     * @param mixed $default default value to return when key doesnt exist
     *
     * @return  mixed
     */
    public function server($key = [], $xss_clean = FALSE, $default = '') {
        return $this->searchArray($_SERVER, $key, $xss_clean, $default);
    }

    /**
     * Fetch an item from the $_FILES array.
     *
     * @param string $key key to find
     * @param bool $xss_clean clean data from html tags or not
     * @param mixed $default default value to return when key doesnt exist
     *
     * @return  mixed
     */
    public function files($key = [], $xss_clean = FALSE, $default = '') {
        return $this->searchArray($_FILES, $key, $xss_clean, $default);
    }

    /**
     * Fetch an item from a global array.
     *
     * @param string $key key to find
     * @param bool $xss_clean clean data from html tags or not
     * @param mixed $default default value to return when key doesnt exist
     *
     * @return  mixed
     */
    public function searchArray($array, $key, $xss_clean = FALSE, $default = '') {
        // if array is empty
        if (empty($key))
            return $array;

        // if key doesn't exist
        if ( null === $value = arr::get($array, $key) )
            return $default;

        if ($xss_clean == TRUE) {
            // XSS clean the value
            $value = $this->xssClean($value);
        }

        return $value;
    }

    /**
     * return user IP Address.
     * @return string
     */
    public function ipAddress() {
        if ($this->ip_address !== NULL)
            return $this->ip_address;

        if ($ip = $this->server('REMOTE_ADDR')) {
            $this->ip_address = $ip;
        } elseif ($ip = $this->server('HTTP_CLIENT_IP')) {
            $this->ip_address = $ip;
        } elseif ($ip = $this->server('HTTP_X_FORWARDED_FOR')) {
            $this->ip_address = $ip;
        }

        if ($comma = strrpos($this->ip_address, ',') !== FALSE) {
            $this->ip_address = substr($this->ip_address, $comma + 1);
        }
        
        return $this->ip_address;
    }

    /**
     * Clean cross site scripting exploits from string.
     *
     * @param   string  data to clean
     * @return  string  purified data
     */
    public function xssClean($data) {

        $data = str_replace(array('&amp;', '&lt;', '&gt;'), array('&amp;amp;', '&amp;lt;', '&amp;gt;'), $data);
        $data = preg_replace('/(&#*\w+)[\x00-\x20]+;/u', '$1;', $data);
        $data = preg_replace('/(&#x*[0-9A-F]+);*/iu', '$1;', $data);
        //$data = html_entity_decode($data, ENT_COMPAT, 'UTF-8');
        // Remove any attribute starting with "on" or xmlns
        $data = preg_replace('#(<[^>]+?[\x00-\x20"\'])(?:on|xmlns)[^>]*+>#iu', '$1>', $data);

        // Remove javascript: and vbscript: protocols
        $data = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([`\'"]*)[\x00-\x20]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2nojavascript...', $data);
        $data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2novbscript...', $data);
        $data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*-moz-binding[\x00-\x20]*:#u', '$1=$2nomozbinding...', $data);

        // Remove namespaced elements (we do not need them)
        $data = preg_replace('#</*\w+:\w[^>]*+>#i', '', $data);

        do {
            // Remove really unwanted tags
            $old_data = $data;
            $data = preg_replace('#</*(?:applet|b(?:ase|gsound|link)|embed|frame(?:set)?|i(?:frame|layer)|l(?:ayer|ink)|meta|object|s(?:cript|tyle)|title|xml)[^>]*+>#i', '', $data);
        } while ($old_data !== $data);



        return $data;
    }

    /**
     * This is a helper method. It escapes data and forces all newline
     * characters to "\n".
     *
     * @param   unknown_type  string to clean
     * @return  string
     */
    public function cleanInputData($str) {
        if (is_array($str)) {
            $new_array = [];
            foreach ($str as $key => $val) {
                // Recursion!
                $new_array[$key] = $this->cleanInputData($val);
            }
            return $new_array;
        }

        if (strpos($str, "\r") !== false) {
            // Standardize newlines
            $str = str_replace(array("\r\n", "\r"), "\n", $str);
        }

        return $str;
    }

}
