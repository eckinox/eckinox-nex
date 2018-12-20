<?php

namespace Eckinox\Nex;

use Eckinox\{
    Annotation,
    Eckinox,
    config,
    Event
};

trait controller {
    use views, config;

    protected $opened_section = false;

    protected $view_function = null;

    protected $title = "";

    /**
     *  This function allows us to preload our layout and delay
     *  it's rendering.
     */
    public function render($view, $vars = []) {
        $this->breadcrumb()->set(false, $vars);

        if ( ! $this->view_function ) {
            # This library handles view's tags {% function_name $arg1, $arg2 %}
            $this->view_function = new View_function($this);
        }

        $content = $this->view($view, $vars);

        Event::instance()->trigger('Nex.controller.render', $view, $vars);

        return $content;
    }

    public function nav($name) {
        echo "given key '$name'";
    }

    public function zone($name) {
        echo "echoing zone '$name'";
    }

    public function block($name) {
        echo "block '$name'";
    }

    public function meta() {
       # return Meta::instance();
    }

    public function stylesheet() {
        return [];
    }

    public function script() {
        return [];
    }

    public function block_header() {
        return "block header";
    }

    public function block_footer() {
        return "block footer";
    }

    public function title($set = null, $echo = true) {
        if ($echo && ($set === null)) {
            echo $this->title;
        }

        return $set === null ? $this->title : $this->title = $set;
    }

    public function image($name) {
        echo $name;
    }

    public function url($url = "", $param = []) {
        return url::addParam(url::site($url), $param);
    }

    public function current_url($param = []) {
        return url::addParam(url::current_url(), $param);
    }

    public function redirect($url) {
        url::redirect($url);
    }

    public function url_from_name($name, $param = []) {
        static $routes = null;

        if ( $routes === null ) {
            $routes = [];

            foreach(Annotation::instance()->get_methods_list() as $class => $annotation) {
                foreach($annotation['methods'] ?? [] as $method => $definition) {
                    if ( ( $definition['name'] ?? false) && ($definition['route'] ?? false) ) {
                        $routes[ $definition['name'] ] = $definition['route'];
                    }
                }
            }
        }

        if ( empty($routes[$name]) ) {
            trigger_error("Unknown route '$name' was provided", \E_USER_ERROR);
        }

        return $this->url(ltrim($routes[$name], '/ '), $param);
    }

    public function breadcrumb() {
        static $breadcrumb = null;
        return $breadcrumb ?? $breadcrumb = new Ui\Breadcrumb();
    }

    public function message() {
        static $message = null;
        return $message ?? $message = new Ui\Message();
    }

    public function form_csrf($field, $value) {
        $values = $this->session("Nex.view.form.csrf.$field") ?: [];

        # keeps 20 (from config) latest CSRF key for this form into session,
        # allowing more than one tab opened and preventing information loss
        if ( count($values) >= $this->config('Nex.view.form.csrf.keep_latest') ?: 20 ) {
            array_pop($values);
        }

        $values[] = $value;

        $this->session("Nex.view.form.csrf.$field", $values);

        return $value;
    }

    public function form_sent($method = INPUT_POST) {
       $method = $method === INPUT_POST ? "post" : "get";

        if ( $valid = request::{"is_".$method}() ) {
            if ( $this->config('Nex.view.form.security.csrf') ) {
                $csrf = $this->$method( $this->config('Nex.view.form.fields.csrf') );

                if ( ! is_array($csrf) ) {
                    trigger_error("Found no CSRF token within $method data. Have you closed your tag using the {% endform %} function ?", \E_USER_ERROR);
                }

                $key = key($csrf);
                $keylist = (array) $this->session("Nex.view.form.csrf.$key");

                if ( ! in_array($csrf[$key], $keylist)) {
                    $valid = false;

                    if ( Eckinox::debug() ) {
                        trigger_error('Found a mismatching CSRF token; session data couldn\'t match required token', \E_USER_WARNING);
                    }
                }
                else {
                    $this->session("Nex.view.form.csrf.$key", array_diff($keylist, $csrf));
                }
            }

            if ( $this->config('Nex.view.form.security.honeypot') ) {
                if ( $this->$method( $this->config('Nex.view.form.fields.honeypot') )) {
                    $valid = false;

                    if ( Eckinox::debug() ) {
                        trigger_error('Honeypot field was found filled; ignoring current sent form', \E_USER_WARNING);
                    }
                }
            }
        }

        return $valid;
    }
}
