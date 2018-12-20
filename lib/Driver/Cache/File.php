<?php

namespace Eckinox\Nex\Driver\Cache;


use Eckinox;
use Eckinox\Nex;

class File extends \Eckinox\Nex\Driver\Cache {

    protected $file_handle = [];
    
    public function __destruct() {
        foreach($this->file_handle as $path => $item) {
            $this->_fileclose($item);
        }
    }

    /**
     * Flush cache specified by key
     * @param string $key
     */
    public function flush($key) {
        $path = $this->getPath($key);

        if ( is_file($path) ) {
            $this->_fileclose($path);
            unlink($path);
        }
        
        return $this;
    }

    /**
     * Removes all keys and cached data
     */
    public function flush_all() {
        
        return $this;
    }

    /**
     * Check if cache key exist
     * @param string $key
     * @return bool
     */
    public function isset_key($key) {
        $filepath = $this->getPath($key);

        if ( file_exists($filepath) ) {
            $fileinfo = $this->_fileinfo($path);
            
            # Validate TTL
            return $fileinfo['ttl'] == 0 || $fileinfo['ttl'] >= time();   
        }

        return false;
    }

    /**
     * Get value from file
     * @param string $key
     */
    public function get($key) {
        if ( !file_exists($filepath = $this->getPath($key)) ) {
            return null;
        }

        $fileinfo = $this->_fileinfo($filepath);
        
        if ( $fileinfo['ttl'] && ($fileinfo['ttl'] < time())) {
            $this->flush($key);
            return null;
        }

        $value = '';
        
        while ( ($line = fgets($this->file_handle[$filepath])) !== false ) {
            $value .= $line;
        }
        
        if ( ! feof($this->file_handle[$filepath]) ) {
            throw new Eckinox\Exception("An error occured while reading file $filepath");
        }

        if ( $fileinfo['compression'] > 0 ) {
            $value = gzinflate($value);
        }

        return unserialize($value);
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

        $content = serialize($value);

        $header['compression'] = (int)$this->config['compression']; // Compression level
        $header['ttl'] = $ttl > 0 ? time() + $ttl : 0; // Expiration

        if ( $this->config['compression'] > 0 ) {
            $content = gzdeflate($content, $this->config['compression']);
        }

        $content = json_encode($header) . PHP_EOL . $content;

        $path = dirname($this->getPath($key));

        if ( ! is_dir($path) ) {
            mkdir($path, 0755, true);
        }
        
        file_put_contents($this->getPath($key), $content);
    }


    public function is_cacheable($value) {
        return ! is_resource($value);
    }
    
    public function getPath($key) {
        return Eckinox\Eckinox::path_cache(). $this->config['dir'] . Nex\Cache::hash($key) . DIRECTORY_SEPARATOR . $key . $this->config['ext'];
    }
    
    /**
     * Returns file info from file's first line.
     * 
     * @param unknown $path Description
     * 
     * @return array    0 = compression level
     *                  1 = duration (it's a timestamp)
     */
    protected function _fileinfo($path) {
        static $fileinfo = [];
        return isset($fileinfo[$path]) ? $fileinfo[$path] : $fileinfo[$path] = json_decode(fgets($this->_filehandle($path)), true);
    }
    
    /**
     * Opening and returning file handle
     * 
     * @param unknown $path Description
     * 
     * @return Type    Description
     */
    protected function _filehandle($path) {
        if ( !file_exists($path) ) {
            throw new Eckinox\Exception("Given filepath $path is nonexistent.");
        }
        
        return isset($this->file_handle[$path]) ? $this->file_handle[$path] : $this->file_handle[$path] = fopen($path, 'r');
    }
    
    protected function _fileclose($handle) {
        if (isset($this->file_handle[$handle])) {
            fclose($this->file_handle[$handle]);
            return true;
        }
        
        return false;
    }
}
