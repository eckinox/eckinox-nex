<?php

namespace Eckinox\Nex;

trait inputs {
    use sessions, cookies;

    public static function get(...$args) {
        switch ( count($args) ) {
            case 2:
                return arr::set($_GET, $args[0], $args[1]);

            case 1:
                return Input::instance()->get($args[0]);

            case 0:
            default:
                return $_GET;
        }
    }

    public static function post(...$args) {
        switch ( count($args) ) {
            case 2:
                return arr::set($_POST, $args[0], $args[1]);

            case 1:
                return Input::instance()->post($args[0]);

            case 0:
            default:
                return $_POST;
        }
    }

    public static function server(...$args) {
        switch ( count($args) ) {
            case 2:
                return arr::set($_SERVER, $args[0], $args[1]);

            case 1:
                return Input::instance()->server($args[0]);

            case 0:
            default:
                return $_SERVER;
        }
    }

    public static function request(...$args) {
        switch ( count($args) ) {
            case 2:
            return arr::set($_REQUEST, $args[0], $args[1]);

            case 1:
                return Input::instance()->request($args[0]);

            case 0:
            default:
                return $_REQUEST;
        }
    }

    public static function files(...$args) {
        switch ( count($args) ) {
            case 2:
                return arr::set($_FILES, $args[0], $args[1]);

            case 1:
                return Input::instance()->files($args[0]);

            case 0:
            default:
                return $_FILES;
        }
    }
}
