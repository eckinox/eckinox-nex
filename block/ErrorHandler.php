<?php

namespace Eckinox\Nex\Block;

use Eckinox\Nex,
    Eckinox\Nex\Driver\ErrorHandler\Html;

class ErrorHandler extends Nex\Block {
    
    protected $base_dir = "/errorhandler/block/";
    
    protected $html_instance;
    
    protected $views = [
        'jsonlog' => 'jsonlog'
    ];
    
    public function __construct($view_path = "") {
        parent::__construct($view_path);
        
        $this->view_lang = "Nex.Eckinox.ErrorHandler";
        $this->html_instance = new Html( $this->config('Nex.errorhandler.html') );
    }
    
    protected function _show_error($array) {
        return empty($array) ? "" : $this->html_instance->from_array($array);
    }
}