<?php

namespace Eckinox\Nex;

use Eckinox\{
	config
};

class Cache {
    use config;
	
    protected $config;
    protected $drivers;

    public static function factory($driver = null) {
        return new self($driver);
    }

    public function __construct($driver = null) {
        $this->config = $this->config('Nex.cache');

        $drivers = ( !$driver ? explode(',', $this->config['driver']) : explode(',', $driver) );
        
        foreach ($drivers as $driver) {
            $classname = $this->config['drivers'][$driver]['class'];
            $this->drivers[] = new $classname($this->config);
        }
    }
    
    public function handle($key, $callable, $time = 43200) {
		static $inline_cache = [];
		
		$key = md5($key);
		
		if ( isset($inline_cache[$key]) )
			return $inline_cache[$key];
		 
		if ( $retval = $this->get($key) )
			return $retval;
		
		$retval = $callable();
        
		$this->set($key, $retval, $time);
		
		return $inline_cache[$key] = $retval;
    }

    public function setDir($dir) {
        foreach ($this->drivers as $driver) {
            $driver->setDir($dir);
        }
    }

    public function setExt($ext) {
        foreach ($this->drivers as $driver) {
            $driver->setExt($exét);
        }
    }

    public function getPath() {
        return $this->drivers[0]->getPath();
    }

    /**
     * Return value for specified key
     * @param string $key
     */
    public function get($key) {
        foreach ($this->drivers as $driver) {
            if (($value = $driver->get($key)) !== null) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Sets a given key with a given value
     * @param string $key
     * @param mixed $value
     * @param int $ttl time to live (seconds)
     */
    public function set($key, $value, $ttl = 0) {
		
        foreach ($this->drivers as $driver) {
			$driver->set($key, $value, $ttl);
        }
    }

    /**
     * Adds to the cache, only if key doesnt already exist
     * @param string $key
     * @param mixed $value
     * @param int $ttl time to live (seconds)
     */
    public function add($key, $value, $ttl = 0) {
        foreach ($this->drivers as $driver) {
            if ( ! $driver->isset_key($key) ) {
                $driver->set($key, $value, $ttl);
            }
        }
    }

    /**
     * Adds to the cache, only if key already exist
     * @param string $key
     * @param mixed $value
     * @param int $ttl time to live (seconds)
     */
    public function replace($key, $value, $ttl = 0) {
        foreach ($this->drivers as $driver) {
            if ($driver->isset_key($key)) {
                $driver->set($key, $value, $ttl);
            }
        }
    }

    /**
     * Flush cache specified by key
     * @param string $key
     */
    public function flush($key) {
        foreach ($this->drivers as $driver) {
            $driver->flush($key);
        }
    }

    /**
     * Removes all keys and cached data
     */
    public function flush_all() {
        foreach ($this->drivers as $driver) {
            $driver->flush_all();
        }
    }

    /**
     * Check if cache key exist
     * @param string $key
     * @return bool
     */
    public function exists($key) {
        return $this->isset_key($key);
    }

// Retrocompatibility

    public function issetKey($key) {
        return $this->isset_key($key);
    }

// Retrocompatibility

    public function isset_key($key) {
        foreach ($this->drivers as $driver) {
            if ($driver->isset_key($key)) {
                return true;
            }
        }

        return false;
    }

    //
    // utility
    // --------------------------------------------------------------------------------

    /**
     * Basic Key hashing algorithm
     * @param string str
     * @return string
     */
    public static function hash($str) {
        $max = 16;
        $boom = str_split($str, 3);
        $count = count($boom);
        $keys = [];

        foreach ($boom as $seg) {
            $keys[] = ord(strtoupper(substr($seg, 0, 1))) - 64;
        }

        $sum = array_sum($keys) * 0.6180339887;
        $key = round(($sum - floor($sum)) * $max);

        return $key;
    }

}
