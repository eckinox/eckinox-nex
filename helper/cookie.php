<?php

namespace Eckinox\Nex;

use Eckinox\Configuration;

abstract class cookie {

    /**
     * Sets a cookie with the given parameters.
     * @param   string   cookie name or array of config options
     * @param   string   cookie value
     * @param   integer  number of seconds before the cookie expires
     * @param   string   URL path to allow
     * @param   string   URL domain to allow
     * @param   boolean  HTTPS only
     * @param   boolean  HTTP only (requires PHP 5.2 or higher)
     * @return  boolean
     */
    public static function set($name, $value = NULL, $expire = NULL, $path = NULL, $domain = NULL, $secure = NULL, $httponly = NULL) {
        if (headers_sent()) {
            // @todo add logs
            return false;
        }

        // If the name param is an array, we import it
        is_array($name) and extract($name, EXTR_OVERWRITE);

        // Fetch default options
        $config = Configuration::get('Nex.cookie');
        
        // Default config
        foreach (array('value', 'expire', 'domain', 'path', 'secure', 'httponly') as $item) {
            if ($$item === NULL AND isset($config[$item])) {
                $$item = $config[$item];
            }
        }

        // Expiration timestamp
        $expire = ($expire == 0) ? 0 : time() + (int) $expire;

        if ($expire > 0)
            $_COOKIE[$name] = $value;

        return setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
    }

    /**
     * Fetch a cookie value, using the Input library.
     * @param   string   cookie name
     * @param   mixed    default value
     * @param   boolean  use XSS cleaning on the value
     * @return  string
     */
    public static function get($name, $default = NULL, $xss_clean = FALSE) {
        return Input::instance()->cookie($name, $default, $xss_clean);
    }

    /**
     * Nullify and unset a cookie.
     * @param   string   cookie name
     * @param   string   URL path
     * @param   string   URL domain
     * @return  boolean
     */
    public static function delete($name, $path = NULL, $domain = NULL) {
        if (!isset($_COOKIE[$name]))
            return false;

        // Delete the cookie from globals
        unset($_COOKIE[$name]);

        // Sets the cookie value to an empty string, and the expiration to 24 hours ago
        return cookie::set($name, '', -86400, $path, $domain, FALSE, FALSE);
    }

}
