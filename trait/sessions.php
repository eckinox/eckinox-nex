<?php

namespace Eckinox\Nex;

trait sessions {

    public function session(...$args) {
        switch ( count($args) ) {
            case 2:
                return Session::instance()->set($args[0], $args[1]);

            case 1:
                return Session::instance()->get($args[0]);

            case 0:
            default:
                return Session::instance();
        }
    }

    public function session_push($key, ...$arrays) {
        $val = $this->session($key) ?: [];
        $this->session($key, array_merge_recursive(...$arrays));
    }

}
