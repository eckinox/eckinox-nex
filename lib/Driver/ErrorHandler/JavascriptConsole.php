<?php

namespace Eckinox\Nex\Driver\ErrorHandler;

use Eckinox\config;
use Eckinox\Nex;

class JavascriptConsole {
    use Nex\errorhandler_output, Nex\views, config;

    protected $config = [];

    protected $shutdown_level = 0;

    public function __construct($config = []) {
        $this->config = array_merge($config, $this->config);
    }

    public function error($errno, $msg, $file, $line, $backtrace = null) {
        $func = "log";

        if ( ! $this->shutdown_level ) {
            register_shutdown_function(function() {
                switch($this->shutdown_level) {
                    case Nex\ERRORHANDLER_LEVEL_ERROR:
                        $this->show_error();
                        break;

                    case Nex\ERRORHANDLER_LEVEL_WARNING:
                        $this->show_warning();
                        break;

                    case Nex\ERRORHANDLER_LEVEL_NOTICE:
                        $this->show_notice();
                        break;
                }
            });
        }

        switch( $this->level($errno) ) {
            case Nex\ERRORHANDLER_LEVEL_ERROR:
                $this->shutdown_level = max($this->shutdown_level, Nex\ERRORHANDLER_LEVEL_ERROR);
                $func = "error";
                break;

            case Nex\ERRORHANDLER_LEVEL_WARNING:
                $this->shutdown_level = max($this->shutdown_level, Nex\ERRORHANDLER_LEVEL_WARNING);
                $func = "warn";
                break;

            case Nex\ERRORHANDLER_LEVEL_NOTICE:
                $this->shutdown_level = max($this->shutdown_level, Nex\ERRORHANDLER_LEVEL_NOTICE);
                $func = "info";
                break;

            default:
                $this->shutdown_level = max($this->shutdown_level, Nex\ERRORHANDLER_LEVEL_DEBUG);
        }

        $slice  = $this->config['slice'];
        $stack  = $backtrace ?: \debug_backtrace(false, $this->config['stacktrace']);

        $count = [
            'server' => count($_SERVER),
            'get' => count($_GET),
            'post' => count($_POST),
            'stack' => count($stack)
        ];

        $stack  = json_encode($stack); #array_slice($stack, $slice));
        $get    = json_encode($_GET);
        $post   = json_encode($_POST);
        $server = json_encode($_SERVER);

        $content = strtoupper(isset($this->error_lang_key[$errno]) ? $this->error_lang_key[$errno] : "UNKNOWN") . ': '.$msg.' in '.$file.' on line '.$line;
        $content = str_replace(["\n", "'"], " ", $content);

        echo $this->view('/errorhandler/drivers/javascript', get_defined_vars() );
    }

    public function show_error() {
        echo $this->show_toolbar('error', '#bc0000');
    }

    public function show_warning() {
        echo $this->show_toolbar('warning', '#bc9900');
    }

    public function show_notice() {
        echo $this->show_toolbar('notice', '#0072bc');
    }

    public function show_toolbar($lang_key, $color) {
        return "<div style='position:fixed;text-shadow:1px 1px 1px #000;text-align:center;opacity:0.77;bottom:0;left:0;right:0;font-size:11px;padding:4px;z-index:1000000;background:$color;color:#fff;font-weight:bold;font-family: Helvetica;text-transform: uppercase;line-height: 17px;'>".
            $this->lang("Nex.errorhandler.javascript.alert.$lang_key").
        "</div>";
    }
}
