<?php

namespace Eckinox\Nex;

trait cookies {
    public static function cookie(...$args) {
        switch ( count($args) ) {
            case 2:
                return Input::instance()->cookie($args[0], $args[1]);

            case 1:
                return Input::instance()->cookie($args[0]);

            case 0:
            default:
                return $_COOKIE;
        }
    }
}
