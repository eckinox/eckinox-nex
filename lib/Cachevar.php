<?php

namespace Eckinox\Nex;


class Cachevar {

    // Directory that will be used to save data
    protected $dir = '';
    
    // File extension
    protected $ext = '.tmp';
    
    // Session prefix
    protected $session_prefix = '_CacheCore';
    
    // Support used for caching. (file|session)
    protected $support = array('file');
    
    // Compression level used
    protected $compression_level = null;

    /**
     * Constructor
     */
    public static function factory() {
        return new self();
    }

    public function __construct() {
        
    }

    public function setDir($dir) {
        $this->dir = $dir;
    }

    public function getPath($key) {
        return TMP_PATH . Nex::CACHE_DIR . $this->dir . $this->hash($key) . DIRECTORY_SEPARATOR . $key . $this->ext;
    }

    /**
     * Specify support used for this instance
     * @param mixed $support
     * @param ...
     */
    public function support($supports) {
        if (func_num_args() > 1) {
            $supports = func_get_args();
        } elseif (is_string($supports)) {
            $supports = str_replace(' ', ',', $supports);
            $supports = explode(',', $supports);
        } else {
            $supports = (array) $supports;
        }

        $this->support = $supports;

        return $this;
    }

    /**
     * Specify compression level
     * @param int $level 0 to 9
     */
    public function compression($level) {
        if ($level < 1 OR $level > 9) {
            // Normalize the level to be an integer between 1 and 9. This
            // step must be done to prevent gzencode from triggering an error
            $level = max(1, min($level, 9));
        }

        $this->compression_level = $level;

        return $this;
    }

    /**
     * Return value for specified key
     * @param string $key
     * @param int $offset
     * @param int $limit
     */
    public function get($key, $offset = 0, $limit = null) {
        // Check Session, No need to call session support since this is just a simple check
        // This is the fastest caching method
        $value = $this->get_session($key);

        if ($value === null) {
            $value = $this->get_file($key);
        }

        if (is_array($value)) {
            $value = ($limit === null ? array_slice($value, $offset) : array_slice($value, $offset, $limit));
        }

        return $value;
    }

    /**
     * Sets a given key with a given value
     * @param string $key
     * @param mixed $value
     * @param int $duration in seconds. 0 means no expiration
     */
    public function set($key, $value, $duration = 0) {
        // Check if session is supported
        if (in_array('session', $this->support)) {
            $this->set_session($key, $value, $duration);
        }

        // Check if we add value to file
        if (in_array('file', $this->support)) {
            $this->set_file($key, $value, $duration);
        }

        return true;
    }

    /**
     * Adds to the cache, only if key doesnt already exist
     * @param string $key
     * @param mixed $value
     * @param int $duration in seconds. 0 means no expiration
     */
    public function add($key, $value, $duration = 0) {
        if (!$this->isset_key($key)) {
            return $this->set($key, $value, $duration);
        }

        return false;
    }

    /**
     * Adds to the cache, only if key already exist
     * @param string $key
     * @param mixed $value
     * @param int $duration in seconds. 0 means no expiration
     */
    public function replace($key, $value, $duration = 0) {
        if ($this->isset_key($key)) {
            return $this->set($key, $value, $duration);
        }

        return false;
    }

    /**
     * Flush cache specified by key
     * @param string $key
     */
    public function flush($key) {
        unset($_SESSION[$this->session_prefix][$key]);

        $path = $this->getPath($key);
        if (is_file($path))
            unlink($path);
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
        if (isset($_SESSION[$this->session_prefix][$key])) {
            return true;
        }

        if (file_exists(DOC_ROOT . $this->getPath($key))) {
            return true;
        }

        return false;
    }

    //
    //
    // Internal functions
    // -------------------------------------------------------------------------

    /**
     * Return session value
     * @param string $key
     */
    protected function get_session($key) {
        if (!isset($_SESSION[$this->session_prefix][$key])) {
            return null;
        }

        $data = $_SESSION[$this->session_prefix][$key];

        if (isset($data['expire']) && $data['expire'] != 0 && $data['expire'] < time()) {
            $this->flush($key);
            return null;
        }

        return $data['value'];
    }

    /**
     * Set session cache value
     * @param string $key
     * @param mixed $value int, string, 1 or 2 dimensionnal array, boolean
     * @param int $duration in seconds. 0 means no expiration
     */
    protected function set_session($key, $value, $duration) {
        $_SESSION[$this->session_prefix][$key] = array
            (
            'expire' => ($duration > 0 ? time() + $duration : 0),
            'value' => $value
        );
    }

    /**
     * Get value from file
     * @param string $key
     */
    protected function get_file($key) {
        // Check for cache file
        if (!file_exists($filepath = DOC_ROOT . $this->getPath($key))) {
            return null;
        }

        $handle = fopen($filepath, 'r');

        // Get file info, info is separated by .
        // offset 0 = compression level
        // offset 1 = duration (it's a timestamp)
        // offset 2 = etag (Not used yet)
        $fileinfo = fgets($handle);
        $fileinfo = explode('.', $fileinfo);

        // Check duration
        if ($fileinfo[1] != 0 && $fileinfo[1] < time()) {
            fclose($handle);
            $this->flush($key);
            return null;
        }

        $value = '';
        while ($line = fgets($handle)) {
            $value .= $line;
        }

        fclose($handle);

        // Check if we need decompression
        if ($fileinfo[0] > 0) {
            $value = gzinflate($value);
        }

        return arr::unserialize($value);
    }

    /**
     * Set file cache value
     * @param string $key
     * @param mixed $value int, string, 1 or 2 dimensionnal array, boolean
     * @param int $duration in seconds. 0 means no expiration
     */
    protected function set_file($key, $value, $duration) {
        // prepare value for file
        $content = arr::serialize($value);

        $header = (int) $this->compression_level; // Compression level
        $header .= '.' . ($duration > 0 ? time() + $duration : 0); // Expiration
        $header .= '.' . md5($content); // Etag
        // Check if we need to compress $content
        if ($this->compression_level > 0) {
            $content = gzdeflate($content, $this->compression_level);
        }

        // Final content
        $content = $header . NEX_EOL . $content;

        // Path
        $path = DOC_ROOT . TMP_PATH . Nex::CACHE_DIR . $this->dir . $this->hash($key) . DIRECTORY_SEPARATOR;

        // Make sure cache dir exist
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }

        file_put_contents($path . $key . $this->ext, $content);
    }

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
