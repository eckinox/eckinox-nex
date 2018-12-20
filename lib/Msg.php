<?php

namespace Eckinox\Nex;

/**
 * @author       Mikael Laforge <mikael.laforge@twikiconcept.com>
 * @version      1.0.6
 * @package      Nex
 * @subpackage   core
 * @copyright    Copyright (c) 2012
 *
 * @update (05/12/2011) [Mikael Laforge] 1.0.0 - Script creation
 * @update (19/12/2011) [Mikael Laforge] 1.0.1 - Added setClass() method
 * @update (28/06/2012) [ML] - 1.0.2 - Added getMsgs() method
 * @update (18/07/2012) [ML] - 1.0.3 - Added getWildcardHtml() method
 *                                     getHtml() method now support 'key.*' wildcard calls
 * @update (06/09/2012) [ML] - 1.0.4 - Added has() method to check if key exist or part of it does
 * @update (13/02/2013) [ML] - 1.0.5 - Bugfix: getHtml() now pass its 2nd argument to to getWildcardHtml()
 * @update (23/03/2013) [ML] - 1.0.6 - added buildHtml(), addClass(), removeClass() method
 */
class Msg {

    protected $msg = [];
    protected $session_key = 'nex_msg';
    public $classname = 'msg';
    public $classname_prefix = '';

    public function __construct($session_key = 'nex_msg') {
        $this->session_key = $session_key;

        if (!isset($_SESSION[$this->session_key])) {
            $_SESSION[$this->session_key] = [];
        }
    }

    public function setClass($class) {
        $this->classname = $class;
    }

    public function addClass($class) {
        $this->classname .= ' ' . $class;
    }

    public function removeClass($class) {
        $classes = explode(' ', $class);
        foreach ($classes as $class) {
            str_replace($class, '', $this->classname);
        }
        $this->classname = preg_replace('/\s+/', ' ', $this->classname);
    }

    public function add($key, $msg, $persist = false) {
        if ($persist) {
            $_SESSION[$this->session_key][$key][] = $msg;
        } else {
            $this->msg[$key][] = $msg;
        }
    }

    public function set($key, $mixed, $persist = false) {
        if ($persist) {
            $_SESSION[$this->session_key][$key] = $mixed;
        } else {
            $this->msg[$key] = $mixed;
        }
    }

    public function has($key) {
        foreach ($_SESSION[$this->session_key] as $k => $arr) {
            if (strpos($k, $key) === 0) {
                return true;
            }
        }

        foreach ($this->msg as $k => $arr) {
            if (strpos($k, $key) === 0) {
                return true;
            }
        }

        return false;
    }

    public function getHtml($key, $separ = '<br/>') {
        if (substr($key, -2) == '.*')
            return $this->getWildcardHtml(substr($key, 0, -2), $separ);

        if (!isset($this->msg[$key]) && !isset($_SESSION[$this->session_key][$key]))
            return '';

        $persist_val = [];
        if (isset($_SESSION[$this->session_key][$key])) {
            $persist_val = (array) $_SESSION[$this->session_key][$key];
            unset($_SESSION[$this->session_key][$key]);
        }

        $vals = [];
        if (isset($this->msg[$key])) {
            $vals = (array) $this->msg[$key];
        }

        $vals = array_merge($persist_val, $vals);

        $segments = explode('.', $key);
        $class = array_shift($segments);

        return $this->buildHtml(implode($separ, $vals), $class);
    }

    public function getWildcardHtml($key, $separ = '<br/>') {
        $vals = [];

        foreach ($_SESSION[$this->session_key] as $k => $arr) {
            if (strpos($k, $key) === 0) {
                $vals = array_merge($vals, $arr);
                //unset($_SESSION[$this->session_key][$key]);
            }
        }

        foreach ($this->msg as $k => $arr) {
            if (strpos($k, $key) === 0) {
                $vals = array_merge($vals, $arr);
            }
        }

        if (!count($vals))
            return '';

        $segments = explode('.', $key);
        $class = array_shift($segments);

        return $this->buildHtml(implode($separ, $vals), $class . ' global');
    }

    public function getMsgs($key) {
        if (!isset($this->msg[$key]) && !isset($_SESSION[$this->session_key][$key]))
            return null;

        $persist_val = [];
        if (isset($_SESSION[$this->session_key][$key])) {
            $persist_val = (array) $_SESSION[$this->session_key][$key];
            unset($_SESSION[$this->session_key][$key]);
        }

        $val = [];
        if (isset($this->msg[$key])) {
            $val = (array) $this->msg[$key];
        }

        $val = array_merge($persist_val, $val);

        return $val;
    }

    public function buildHtml($msg, $class = '') {
        $boom = explode(' ', $class);
        $class = '';
        foreach ($boom as $c) {
            $class .= $this->classname_prefix . $c . ' ';
        }

        return '<p class="' . $this->classname . ' ' . $class . '">' . $msg . '</p>';
    }

}
