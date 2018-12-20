<?php

namespace Eckinox\Nex;

use Eckinox\Eckinox;

trait caches {

    # Default driver used is files
    protected $cache_driver = "File";

    # Default TTL is set to 1 hour
    protected $cache_ttl = 3600;

    protected $cache_force_in_debug = true;

    public function cache(...$arguments /* $key, $value, $ttl */ ) {
        static $instance = null;

        $instance || $instance = new Cache($this->cache_driver);

        switch ( count($arguments) ) {
            case 3:
            case 2:
                return $instance->set($arguments[0], $arguments[1], isset($arguments[2]) ? $arguments[2] : $this->cache_ttl);

            case 1:
                return $instance->get($arguments[0]);

            case 0:
            default:
                return $instance;
        }
    }

    /**
     * Handles cache entries based on the existence or not of given key.
     *
     * If we have to set it's value, we simply run the callback function, and
     * send it's value to the cache handler.
     *
     * @param unknown $key      Key matching the cache entry
     * @param unknown $callback Function returning cache's value to be set
     * @param unknown $ttl      Time-to-live (defaulted to 3600 seconds)
     *
     * @return Type    Description
     */
    public function cache_handle($key, $callback, $ttl = null) {
        # Key exists in cache, returning it's value

        if ( ($value = $this->cache($key)) === null || (Eckinox::debug() && $this->cache_force_in_debug)) {
            return ( new Cache($this->cache_driver) )->handle($key, $callback, $ttl ?: $this->cache_ttl);
        }

        return $value;
    }
}