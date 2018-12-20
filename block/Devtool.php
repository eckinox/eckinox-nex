<?php

namespace Eckinox\Nex\Block;

use Eckinox\Nex,
    Eckinox\Nex\Driver\ErrorHandler\Html;

class Devtool extends Nex\Block {
    
    protected $base_dir = "/devtool/block/";
    
    protected $html_instance;
    
    protected $views = [
        'find_str' => 'find_str'
    ];
    
    public function __construct($view_path = "") {
        parent::__construct($view_path);
        
        $this->view_lang = "Nex.Eckinox.Devtool";
    }
}