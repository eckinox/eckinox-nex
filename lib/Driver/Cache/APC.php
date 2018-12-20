<?php

namespace Eckinox\Nex\Driver\Cache;

/**
 * @author       Mikael Laforge <mikael.laforge@gmail.com>
 * @version      1.0.0
 * @package      Nex
 * @subpackage   core
 * @copyright    Copyright (c) 2013
 *
 * @update (18/04/2014) [ML] - 1.0.0 - Script creation
 * Cache using Apc extension
 */
if (!extension_loaded('apc') || !ini_get('apc.enabled')) {
    throw new Exception('APC extension needs to be installed to use the APC driver.');
}

class APC extends \Eckinox\Nex\Driver\Cache {

    protected $prefix = 'nex_cache';

    public function getPath($key) {
        return $this->config['dir'] . $this->prefix . $key;
    }

    public function flush($key) {
        apc_delete($key);
    }

    public function flush_all() {
        apc_clear_cache();
    }

    public function isset_key($key) {
        return apc_exists($key);
    }

    public function get($key) {
        return apc_fetch($key);
    }

    public function set($key, $value, $ttl = 0) {
        apc_store($key, $value, $ttl);
    }

    public function is_cacheable($value) {
        return true;
    }

}
