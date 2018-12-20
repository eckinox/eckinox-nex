<?php

namespace Eckinox\Nex;

trait errorhandler_output {
    
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
    
    public function level($error_code) {
        switch($error_code) {
            case \E_COMPILE_ERROR:
            case \E_ERROR:
            case \E_CORE_ERROR:
            case \E_USER_ERROR:
            case \E_PARSE:
                return ERRORHANDLER_LEVEL_ERROR;
            
            case \E_WARNING:
            case \E_CORE_WARNING:
            case \E_USER_WARNING:
            case \E_COMPILE_WARNING:
                return ERRORHANDLER_LEVEL_WARNING;
            
            case \E_NOTICE:
            case \E_RECOVERABLE_ERROR:
                return ERRORHANDLER_LEVEL_NOTICE;
            
            default:
            case \E_STRICT:
            case \E_DEPRECATED:
            case \E_USER_DEPRECATED:
                return ERRORHANDLER_LEVEL_DEBUG;
                
        }
        
    }
    
    public function clean_stacktrace($stacktrace) {
        $retval = [];
        
        foreach($stacktrace as $item) {
            $retval[] = [
                'file'      => isset($item['file']) ? $item['file'] : null,
                'line'      => isset($item['line']) ? $item['line'] : null,
                'function'  => isset($item['function']) ? $item['function'] : null,
                'type'      => isset($item['type']) ? $item['type'] : null,
                'class'     => isset($item['class']) ? $item['class'] : null,
                'args'      => [],
            ];
        }
        
        return $retval;
    }
}
