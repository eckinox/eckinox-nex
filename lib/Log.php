<?php

namespace Eckinox\Nex;

use Eckinox\Eckinox,
    Eckinox\config;

/**
 * @author       Mikael Laforge <mikael.laforge@twikiconcept.com>
 * @version      1.3.1
 * @package      Nex
 *
 * @update (03/10/2011) - 1.1.0 - Updated class to use config system
 *                                File prefix can now be used with config
 * @update (26/10/2011) - 1.2.0 - Changed log format to be more readable and more standard with unix log style
 * @update (12/01/2012) - 1.3.0 - Strip new lines instead of exploding them into multiple logs
 * @update (02/04/2012) - 1.3.1 - toFile() now set log file to 0775 permissions (using log configs)
 * 									Added $use_prefix argument to toFile() method
 */
class Log {
    use config;

    // Object instance of self
    protected static $instance = null;
    // Internal cache
    protected static $internal_cache = [];

    /**
     * Retrieve a singleton instance of self. This will always be the first
     * created instance of this class.
     * @return  object
     */
    public static function instance() {
        if (self::$instance === null) {
            // Create a new instance
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Constructor. Get and set log config
     * @uses Nex::config()
     */
    public function __construct() {

    }

    /**
     * Log systeme message in file
     * @param string $file name of log file
     * @param string $message
     */
    public function system($file, $message) {
        $message = trim(text::stripEOL($message, ' '));
        $content = date('Y-m-d H:i:s') . ' ' .
                'URI: ' . Router::request_uri() . ' ' .
                $message;

        $this->toFile($file, $content, 'a+');

        return true;
    }

    /**
     * Log system message in file
     * @param string $file name of log file
     * @param string $message
     */
    public function toFile($file, $content, $mode = 'a+', $use_prefix = false) {

        $dir_path = dirname(Eckinox::path_var() . Nex::config('log.dir') . $file);
        $file = ($use_prefix ? $this->config('Nex.log.file_prefix') : '') . basename($file);

        #$chmod = octdec($this->config('Nex.log.chmod'));

        // Create log directory if it doesnt exist
        if (!file_exists($dir_path)) {
            mkdir($dir_path, 0775, true);
        }

        $path = $dir_path . DIRECTORY_SEPARATOR . $file;
        $file_existed = file_exists($path);

        // Create file if it doesnt exist, pointer at the end of the file
        $handle = fopen($path, $mode);
        fwrite($handle, $content . NEX_EOL);
        fclose($handle);

        if (!$file_existed)
            chmod($path, 0775);

        return true;
    }

}
