<?php namespace Eckinox\Nex;

use Eckinox\{
    Event,
    Reflection
};

trait url_function {

    protected $url_default = "index";

    protected $url_type = "function";

    public $url_current = "";

    public function url_route(...$uri) {
        # Trying to parse a function name from given URI (which would be $uri[1] before the array_shift)
        if ( ( $latest = end($uri) ) && ( in_array($latest, $this->_url_grab_routes()) ) ) {
            $this->url_current = array_pop($uri);
        }
        else {
            $this->url_current = $this->url_default;
        }

        Event::instance()->trigger('Nex.url_route', $this, [ $this->url_current, $uri ]);

        return $this->{$this->url_current}($uri);
    }

    protected function _url_grab_routes() {
        # There should be a reflection check here or something to protected
        return Reflection::instance()->functions($this);
    }
}
