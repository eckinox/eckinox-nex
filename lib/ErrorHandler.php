<?php

namespace Eckinox\Nex;

use Eckinox\config,
    Eckinox\lang,
    Eckinox\singleton;

class ErrorHandler extends Driver\ErrorHandler {

    protected $error_lang_key = [
        \E_ERROR => 'error',
        \E_WARNING => 'warning',
        \E_PARSE => 'parse',
        \E_NOTICE => 'notice',
        \E_CORE_ERROR => 'core-error',
        \E_CORE_WARNING => 'core-warning',
        \E_COMPILE_ERROR => 'compile-error',
        \E_CORE_WARNING => 'core-warning',
        \E_USER_ERROR => 'user-error',
        \E_USER_WARNING => 'user-warning',
        \E_USER_NOTICE => 'user-notice',
        \E_STRICT => 'strict',
        \E_RECOVERABLE_ERROR => 'recoverable-error',
        \E_DEPRECATED => 'deprecated',
        \E_USER_DEPRECATED => 'user-deprecated'
    ];

    protected function __construct() {
        $this->init();
    }

    public function init() {
        set_error_handler([ $this , 'handle_error' ]);
        set_exception_handler([ $this, 'handle_exception' ]);

        ini_set('display_errors','off');

        register_shutdown_function(function() {
            if( ( $error = error_get_last() ) && $this->fatal_error($error['type']) ) {
                $this->handle_error($error['type'], $error['message'], $error['file'], $error['line']);
            }
        });

        return $this;
    }

    public function error_500() {
        header($this->input->server('SERVER_PROTOCOL') . ' 404 Not Found');
        $view = new View('error/500');
        $view->render(true);
        return $this->view('error/500');
    }

    public function error_404() {
        header($this->input->server('SERVER_PROTOCOL') . ' 404 Not Found');
        return $this->view('error/404');
    }

    public function error_403() {
        header($this->input->server('SERVER_PROTOCOL') . ' 403 Forbidden');
        return $this->view('error/403');
    }

    public static function fatal_error($error_no) {
        return (bool)( $error_no & ( \E_ERROR | \E_PARSE | \E_CORE_ERROR | \E_COMPILE_ERROR | \E_RECOVERABLE_ERROR ) );
    }

    public function handle_exception($ex) {
        return $this->handle_error( $ex->getCode(), $ex->getMessage(), $ex->getFile(), $ex->getLine(), [], $ex->getTrace() );
    }

    public function handle_error( $errno, $errstr, $errfile, $errline, $context = [], $backtrace = null ) {
        if ( $errno & error_reporting() ) {
            $this->output($errno, $errstr, $errfile, $errline, $backtrace);
            #return true;
        }

        return false;
    }

}
