<?php

namespace Eckinox\Nex;

use Eckinox\{
    lang,
    Event,
    Configuration
};

trait views {
    use lang;

    protected $view_lang = "";

    protected $view_obj = null;

    protected $view_base_dir = "";

    public function view($path, $vars = [], $source_only = false) {
        #return Event::on('Nex.view.render', function() {

        if ( ! $this->view_obj ) {
            # Parent view creation
            $this->view_obj = new View($path = $this->_view_path($path), $this);
            $view_obj = $this->view_obj;
        }
        else {
            # Children views creation
            $view_obj = new View($path = $this->view_obj->relative_path($path, $this->_view_path($path)), $this);
            $this->view_obj->share($view_obj);
            extract($this->view_obj->vars(), EXTR_SKIP);
        }

        $view_obj->setLangFile( $this->view_lang );

        if ( $vars ) {
            $view_obj->assign( $vars );
            extract($vars, EXTR_OVERWRITE);
        }

        if ( Configuration::get('Eckinox.system.debug.view') )
            echo "<script>console.log('{$path}')</script>";

        $view_obj->compile();

        if ( $source_only ) {
            $output = file_get_contents( $view_obj->getIncludePath() );
        }
        else {
            ob_start();
            include( $view_obj->getIncludePath() );
            $output = ob_get_clean();
        }

        return $output;
        #})->trigger('Nex.view.render');
    }

    public function inlineView($path, $vars = []) {
        return $this->view($path, $vars);
    }

    public function _($key, $vars = []) {
        return $this->view_obj->_($key, $vars);
    }

    public function set_lang($key, $args = null) {
        if ( $args !== null ) {
            $this->view_obj->setLangVars($args);
        }

        return $this->view_obj->setLangFile( $key );
    }

    protected function _view_path($path) {
        return $this->view_base_dir.ltrim($path, "/");
    }
}
