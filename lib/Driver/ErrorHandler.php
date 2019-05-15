<?php

namespace Eckinox\Nex\Driver;

use Eckinox\Nex;

use Eckinox\singleton,
    Eckinox\config,
    Eckinox\Arrayobj;

class ErrorHandler {
    use singleton, config;
    
    protected $outputs = [];
    
    public function register($output, $priority = 100) {
        $this->outputs[] = [ 
            'output'   => $output,
            'priority' => $priority
        ];
        
        Nex\arr::subSort($this->outputs, 'priority');
    }
    
    public function output($errno, $msg, $file, $line, $backtrace = null) {
        foreach($this->outputs as $item) {
            $item['output']->error($errno, $msg, $file, $line, $backtrace);
        }
        
        return $this;
    }
    
}