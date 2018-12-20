<?php

namespace Eckinox\Nex\Driver\Cache;

/**
 * @author       Mikael Laforge <mikael.laforge@gmail.com>
 * @version      1.0.0
 * @package      Nex
 * @subpackage   core
 * @copyright    Copyright (c) 2013
 *
 * @update (13/12/2013) [ML] - 1.0.0 - Script creation
 * Cache using memcache
 */

class Memcache extends \Eckinox\Nex\Driver\Cache {
    protected $prefix = '';
    protected $memcache_config;
    protected static $memcache = null;

    public function __construct() {
        parent::__construct();

        $this->prefix = $this->config['prefix'];
        $this->memcache_config = $this->config['drivers']['Memcache'];

        if (!self::$memcache) {
            self::$memcache = new Memcache();
            $this->connect();
        }
    }

    public function connect() {
        foreach ($this->memcache_config['servers'] as $server) {
            self::$memcache->addServer($server['host'], $server['port'], false);
        }
    }

    public function getPath($key) {
        return $this->config['dir'] . $this->prefix . $key;
    }

    public function flush($key) {
        self::$memcache->delete($this->getPath($key));
    }

    public function flush_all() {
        self::$memcache->flush();
    }

    public function isset_key($key) {
        if ($this->get($this->getPath($key))) {
            return true;
        }

        return false;
    }

    public function get($key) {
        return self::$memcache->get($this->getPath($key), ($this->config['compression'] ? MEMCACHE_COMPRESSED : null));
    }

    public function set($key, $value, $ttl = 0) {
        self::$memcache->set($this->getPath($key), $value, ($this->config['compression'] ? MEMCACHE_COMPRESSED : null), $ttl);
    }

    public function is_cacheable($value) {
        return true;
    }

}
