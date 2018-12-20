<?php

namespace Eckinox\Nex;

/**
 * @author Mikael Laforge <mikael.laforge@gmail.com>
 * @version 1.0.3
 * @package Nex
 * @update (27/10/2011) [Mikael Laforge] - 1.0.0 - Script creation
 * @update (07/09/2012) [Mikael Laforge] - 1.0.1 - getArg() method in Nex_EventT class now returns a reference
 * @update (25/09/2012) [Mikael Laforge] - 1.0.2 - Added fireArgs() to fire events in a different manner
 * @update (04/10/2012) [Mikael Laforge] - 1.0.3 - Events will now be considered triggered even if no callback were called
 * 												Added getTrigger() static method to get the object that triggered an event
 */
class Event {

    // Event listeners
    private static $listeners = [];
    // Cache of events that have been run
    private static $has_run = [];
    public static $data;

    /**
     * Add a callback to an event queue.
     *
     * @param string $name event name
     * @param mixed $callback http://php.net/callback
     * @param mixed $callback_args arguments that will be passed to callback
     * @return boolean
     */
    public static function register($name, $callback, $callback_args = []) {
        return self::addListener($name, $callback, $callback_args);
    }

    public static function addListener($name, $callback, $callback_args = []) {
        // Create empty event list if its not yet defined
        if (!isset(self::$listeners[$name])) {
            self::$listeners[$name] = [];
        }
        // Check if this event already exist
        elseif (in_array(array($callback, $callback_args), self::$listeners[$name], true)) {
            return FALSE;
        }

        // Add the event
        self::$listeners[$name][] = array($callback, $callback_args);

        return TRUE;
    }

    /**
     * Remove some or all callbacks from an event.
     * @param string $name event name
     * @param array $callback specific callback to remove, false for all callbacks
     * @return void
     */
    public static function removeListener($name, $callback = false, $callback_args = false) {
        if ($callback === false) {
            self::$listeners[$name] = [];
        } elseif (isset(self::$listeners[$name])) {
            // Loop through each of the event callbacks and compare it to the
            // callback requested for removal. The callback is removed if it
            // matches.
            foreach (self::$listeners[$name] as $i => $event_callback) {
                if ($callback === $event_callback[0] && $callback_args === false || $callback === $event_callback[0] && $callback_args === $event_callback[1]) {
                    unset(self::$listeners[$name][$i]);
                }
            }
        }
    }

    /**
     * Get all callbacks for an event.
     * @param string $name event name
     * @return array
     */
    public static function get($name) {
        return empty(self::$listeners[$name]) ? [] : self::$listeners[$name];
    }

    /**
     * Execute all of the callbacks attached to an event.
     * @param string $name event name
     * @param object $data
     * @param array $args
     * @return void
     */
    public static function fire($name, & $obj = NULL, $args = []) {
        $return = [];

        if (!empty(self::$listeners[$name])) {
            $callbacks = self::get($name);

            foreach ($callbacks as $callback) {
                $listener_args = (is_array($callback[1])) ? $callback[1] : array($callback[1]);
                $args = array_merge($listener_args, $args);

                $eventT = new Nex_EventT($name, $args, $obj);

                $return[] = call_user_func($callback[0], $eventT);
            }
        }

        // The event has been run!
        self::$has_run[$name] = array('obj' => &$obj, 'args' => $args);

        return $return;
    }

    public static function fireArgs($name, $args = []) {
        if (!empty(self::$listeners[$name])) {
            $callbacks = self::get($name);

            foreach ($callbacks as $callback) {
                call_user_func_array($callback[0], $args);
            }

            // The event has been run!
            self::$has_run[$name] = $name;
        }
    }

    // Depreciated
    public static function trigger($name, & $data = NULL) {
        if (!empty(self::$listeners[$name])) {
            // So callbacks can access Event::$data
            self::$data = & $data;
            $callbacks = self::get($name);

            foreach ($callbacks as $callback) {
                $callback[1] = (is_array($callback[1])) ? $callback[1] : array($callback[1]);
                call_user_func_array($callback[0], $callback[1]);

                //throw new Exception('Error while executing Event callback. "'.var_export($callback[0], true).'" : "'.var_export($callback[1], true).'" ', NEX_E_EVENT_CALLBACK);

                /* try {
                  call_user_func_array($callback[0], $callback[1]);
                  }
                  catch ( Nex_Exception $e ) {
                  echo $e ;
                  } */
            }

            // Do this to prevent data from getting 'stuck'
            $clear_data = '';
            self::$data = & $clear_data;
        }

        // The event has been run!
        self::$has_run[$name] = $name;
    }

    /**
     * Check if a given event has been run.
     * @param string $name event name
     * @return boolean
     */
    public static function triggered($name) {
        return isset(self::$has_run[$name]);
    }

    /**
     * Return object for element that has been triggered
     */
    public static function & getTrigger($name) {
        if (isset(self::$has_run[$name]['obj']))
            return self::$has_run[$name]['obj'];

        return null;
    }

}

final class Nex_EventT {

    private $args;
    private $event_type;
    private $obj;

    public function __construct($event_type, $args, & $obj = null) {
        $this->event_type = $event_type;
        $this->args = $args;

        $this->obj = & $obj;
    }

    public function getType() {
        return $this->event_type;
    }

    public function getArgs() {
        return $this->args;
    }

    public function & getArg($index) {
        return $this->args[$index];
    }

    public function & getTrigger() {
        return $this->obj;
    }

}
