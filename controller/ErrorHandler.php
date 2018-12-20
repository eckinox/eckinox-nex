<?php namespace Eckinox\Nex\Controller;

use Eckinox;
use Eckinox\Nex;

class ErrorHandler {
    
    public function json_log() {
        $logstack = [];
        
        $block = new Nex\Block\ErrorHandler();
        
        foreach(Eckinox\iterate::files(Nex\Nex::path_log(), "log") as $item) {
            foreach(explode(PHP_EOL, file_get_contents($item)) as $line) {
                $logstack[] = json_decode($line, true) ?: [];
            }
        }
        
        return $block->assign([
            'error_list' => $logstack
        ])->render_jsonlog();
    }
}