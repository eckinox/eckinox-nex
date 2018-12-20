<?php

namespace Eckinox\Nex\Driver\Cache;

/**
 * @author       Mikael Laforge <mikael.laforge@gmail.com>
 * @version      1.0.0
 * @package      Nex
 * @subpackage   core
 * @copyright    Copyright (c) 2012
 *
 * @update (14/09/2012) [Mikael Laforge] - 1.0.0 - Script creation
 *
 * Cache using session
 */

class Session extends \Eckinox\Nex\Driver\Cache {
    protected $session_prefix = 'nex_cache';

    public function getPath($key) {
        return $this->config['dir'] . $this->session_prefix;
    }

    /**
     * Flush cache specified by key
     * @param string $key
     */
    public function flush($key) {
        unset($_SESSION[$this->getPath($key)][$key]);
    }

    /**
     * Removes all keys and cached data
     */
    public function flush_all() {
        
    }

    /**
     * Check if cache key exist
     * @param string $key
     * @return bool
     */
    public function isset_key($key) {
        if (isset($_SESSION[$this->getPath($key)][$key])) {
            $data = $_SESSION[$this->getPath($key)][$key];
            if (isset($data['expire']) && ($data['expire'] == 0 || $data['expire'] >= time())) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get value from file
     * @param string $key
     */
    public function get($key) {
        if (!isset($_SESSION[$this->getPath($key)][$key])) {
            return null;
        }

        $data = $_SESSION[$this->getPath($key)][$key];

        if (isset($data['expire']) && $data['expire'] != 0 && $data['expire'] < time()) {
            $this->flush($key);
            return null;
        }

        return $data['value'];
    }

    /**
     * Set file cache value
     * @param string $key
     * @param mixed $value int, string, 1 or 2 dimensionnal array, boolean
     * @param int $ttl in seconds. 0 means no expiration
     */
    public function set($key, $value, $ttl = 0) {
        if (!$this->is_cacheable($value))
            return false;

        $_SESSION[$this->getPath($key)][$key] = array
            (
            'expire' => ($ttl > 0 ? time() + $ttl : 0),
            'value' => $value
        );
    }

    public function is_cacheable($value) {
        return true;
    }

}
