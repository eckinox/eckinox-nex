<?php

namespace Eckinox\Nex;

/**
 * @author       Mikael Laforge <mikael.laforge@twikiconcept.com>
 * @version      1.0
 * @package      S.a.S
 * @subpackage   core
 * @copyright    Copyright (c) 2010, Twiki Concept (www.twikiconcept.com)
 *
 * @update (25/05/2010) [Mikael Laforge] - 1.0 - Script Creation
 *
 * @uses Curl librairie
 *
 * This class was made to help with remote request - Get & Post
 */

abstract class remote {

    // Url that will be called
    protected $url = null;
    // Request method
    protected $method = 'GET';
    // Content received
    protected $content = null;
    // Cookie string from content
    protected $cookies = null;

    /**
     * Constructor of this class
     */
    public function __construct() {
        
    }

    /**
     * Get contents of a given url
     * @param string $url
     * @return string
     */
    public function get_content($url, $method = 'GET') {
        $this->url = $url;
        $this->method = strtoupper($method);

        // Try using curl librairie
        if (function_exists('curl_init')) {
            $curl = curl_init();

            $useragent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'SaS Framework ' . NEX_VERSION;
            $headers = array(
                "Accept-Language: " . strtolower(str_replace('_', '-', Nex::$lang)),
                "User-Agent: " . $useragent,
                "Connection: Keep-Alive",
                "Cache-Control: no-cache"
            );
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

            // Check if POST
            if ($this->method == 'POST') {
                $split = url::splitOnQuery($url);
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $split[1]);
            }

            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_HEADER, true);

            curl_setopt($curl, CURLOPT_USERAGENT, $useragent);
            curl_setopt($curl, CURLOPT_AUTOREFERER, true);
            curl_setopt($curl, CURLOPT_FAILONERROR, true);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
            //curl_setopt($curl, CURLOPT_TIMEOUT, 10);
            // Handle cookie with the curl's jar
            curl_setopt($curl, CURLOPT_COOKIEJAR, 'cookie.txt');
            curl_setopt($curl, CURLOPT_COOKIEFILE, 'cookie.txt');
            curl_setopt($curl, CURLOPT_COOKIE, $this->cookies);
            curl_setopt($curl, CURLOPT_COOKIESESSION, true);

            if (($this->content = curl_exec($curl)) === false) {
                return false;
            }

            $this->cookies = self::get_cookie($this->content);

            curl_close($curl);
        }
        // Else, normal call.
        else {
            if (!$fp = fsockopen($url, 80, $errno, $errstr, 30)) {
                return false;
            }

            $this->content = stream_get_contents($fp);

            fclose($fp);
        }

        return $content;
    }

    /**
     * Retrieve cookies from http headers
     * @param string $content
     */
    public static function get_cookie($content) {
        /* $pattern = "/Set-Cookie: (.*?; path=.*?;.*?)\n/";
          preg_match_all($pattern, $content, $matches);
          array_shift($matches);
          $cookie = implode("\n", $matches[0]); */

        $pattern = "/Set-Cookie: *(.+);.*path=/U";
        preg_match_all($pattern, $content, $matches);
        $cookie = implode("\n", $matches[1]);

        return $cookie;
    }

}
