<?php

namespace Eckinox\Nex\Driver\ErrorHandler;

use Eckinox\Nex;

class JsonLog {
    use Nex\errorhandler_output;
    
    protected $config = [];
    
    protected $save_path = "";
    
    protected $type = [
        Nex\ERRORHANDLER_LEVEL_ERROR   => 'Error',
        Nex\ERRORHANDLER_LEVEL_WARNING => 'Warning',
        Nex\ERRORHANDLER_LEVEL_NOTICE  => 'Notice',
        Nex\ERRORHANDLER_LEVEL_DEBUG   => 'Debug'
    ];
    
    public function __construct($config = []) {
        $this->config = array_merge($config, $this->config);
        
        if ( ! $this->config['path'] ) {
            $this->save_path = Nex\Nex::path_log();
        }
    }
    
    public function error($errno, $msg, $file, $line, $backtrace = null) {
        $type  = $this->type[$this->level($errno)];
        $slice = $this->config['slice'];
        $stack = $this->clean_stacktrace( $backtrace !== null ? $backtrace : \debug_backtrace(false, $this->config['stacktrace']) );
        
        $content = json_encode([
            'errno'     => $errno,
            'msg'       => $msg,
            'file'      => $file,
            'line'      => $line,
            'backtrace' => array_slice($stack, $slice),
            'server'    => $_SERVER,
            'session'   => $_SESSION,
            'get'       => $_GET,
            'post'      => $_POST,
            'datetime'  => date('Y-m-d H:i:s')
        ]);
        
        $content && file_put_contents($this->_get_filename($this->save_path), $content.PHP_EOL, \FILE_APPEND);
    }
    
    protected function _get_filename($path) {
        if (!file_exists($path)) {
            mkdir($path, 0775, true);
        }
        
        return $path."test.log";
    }
    
}