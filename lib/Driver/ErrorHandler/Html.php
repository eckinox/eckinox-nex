<?php

namespace Eckinox\Nex\Driver\ErrorHandler;

use Eckinox\Nex;
use Eckinox\config;

class Html {
    use Nex\errorhandler_output, Nex\views, config;

    protected $config = [];

    protected $rendered_once = false;

    protected $type = [
        Nex\ERRORHANDLER_LEVEL_ERROR   => 'error',
        Nex\ERRORHANDLER_LEVEL_WARNING => 'warn',
        Nex\ERRORHANDLER_LEVEL_NOTICE  => 'info',
        Nex\ERRORHANDLER_LEVEL_DEBUG   => 'debug'
    ];

    public function __construct($config = []) {
        $this->config = array_merge($config, $this->config);

        register_shutdown_function(function() {
            if ( $this->rendered() ) {
                echo $this->view('/errorhandler/drivers/html_css' );
            }
        });
    }

    public function error($errno, $msg, $file, $line, $backtrace = null) {
        $this->rendered(true);

        $type = $this->type[$this->level($errno)];
        $slice  = $this->config['slice'];

        $stack  = $this->clean_stacktrace( $backtrace !== null ? $backtrace : \debug_backtrace(false, $this->config['stacktrace']) );
        $stack  = array_slice($stack, $slice);

        $get  = json_encode($_GET, JSON_PRETTY_PRINT);
        $post = json_encode($_POST, JSON_PRETTY_PRINT);
        $server = json_encode($_SERVER, JSON_PRETTY_PRINT);
        $session = json_encode($_SESSION, JSON_PRETTY_PRINT);
        $rawstack = json_encode($stack, \JSON_PRETTY_PRINT);

        $datetime = date('Y-m-d H:i:s');

        echo $this->view('/errorhandler/drivers/html', get_defined_vars() );
    }

    public function from_array($content) {
        extract($content, EXTR_OVERWRITE);

        $type   = $this->type[$this->level($errno)];

        $get  = json_encode($content['get'], JSON_PRETTY_PRINT);
        $post = json_encode($content['post'], JSON_PRETTY_PRINT);
        $server = json_encode($content['server'], JSON_PRETTY_PRINT);
        $session = json_encode($content['session'], JSON_PRETTY_PRINT);
        $rawstack = json_encode($stack = $backtrace, \JSON_PRETTY_PRINT);

        return $this->view('/errorhandler/drivers/html', get_defined_vars() );
    }

    public function rendered($set = null) {
        return $set === null ? $this->rendered_once : $this->rendered_once = $set;
    }

    protected function _function_name($stack_item) {

        if ( strpos($stack_item['function'], '{closure}') !== false ) {
            return $this->lang("Nex.errorhandler.html.function.closure");
        }

        return $stack_item['function'];
    }

    protected function _filepath($stack_item) {
        if ( strpos( $stack_item['file'], \SRC_DIR ) !== false ) {
            return ".".substr($stack_item['file'], strlen(\SRC_DIR));
        }
        else {
           return $stack_item['file'];
        }
    }

    protected function _filesource($stack_item) {
        if ( !file_exists($stack_item['file']) ) {
            return [ "Given file not found : {$stack_item['file']}" ];
        }

        ini_set('highlight.default', "#0072bc");
        ini_set('highlight.string' , "#bc0000");
        ini_set('highlight.comment', "#b69832");

        # Apply syntax coloring
        $file = highlight_file($stack_item['file'], true);

        # Removing <code> tag
        $src = explode( '<br />', substr($file, 6, strlen($file) - 13) );

        if ( $stack_item['line'] >= 10 ) {
            $src = array_slice($src,  $stack_item['line'] - 10, 20);
            $src = $this->_filesource_line($src, $stack_item['line'] - 10);
        }
        else {
            $src = $this->_filesource_line($src, 1);
        }

        return $src;
    }

    protected function _filesource_line($line, $index) {
        $index++;
        return array_combine(range($index, count($line) - 1 + $index), $line);
    }

}
