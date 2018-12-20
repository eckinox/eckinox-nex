<?php

namespace Eckinox\Nex;


use Eckinox\Arrayobj,
    Eckinox\config,
    Eckinox\Exception;

use Eckinox\Nex\views;

class Block extends Arrayobj {
    use views, config;

    protected $input;
    protected $views = [];
    
    protected $base_dir = "block/";
    
    public static function factory($block, $view_path = '') {
        $classname = $block . Nex::BLOCK_SUFFIX;
        return new $classname($view_path);
    }

    public function __construct($view_path = '') {
        parent::__construct([]);
        
        $this->_view_path = $view_path;
        $this->input = Input::instance();
    }

    public function inlineBlock($block, $view_path) {
        return ( new Block($block, $view_path) )->render();
    }
    
    public function render($now = false) {
        $this->view_base_dir = $this->base_dir;
        $view = $this->view($this->_view_path, $this->container);
        
        if ( $now ) {
            echo $view;
            return true;
        }
        else {
            return $view;
        }
    }
    
    public function varExist($name) {
        return isset( $this[$name] );
    }

    public function setLangFile($file) {
        $this->view_obj->setLangFile($file);
    }

    public function set($name, $val) {
        $this[$name] = $val;
    }
    
    public function assign($varbag) {
        $this->container = array_merge($this->container, $varbag);
        return $this;
    }
    
    public function __call($name, $args) {
        if ( substr($name, 0, 3) == 'get' ) {
            $name = lcfirst(substr($name, 3));
            return $this[$name];
        }
        elseif (substr($name, 0, 7) == 'render_') {
            $name = substr($name, -( strlen($name) - 7 ) );
            $this->_view_path = $this->views[$name];
            return $this->render();   
        }
        else {
            throw new Exception('Block method "' . $name . '" doesn\'t exist.', NEX_E_METHOD_EXIST);
        }
    }
}