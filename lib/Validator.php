<?php

namespace Eckinox\Nex;

/**
 * @author       Mikael Laforge <mikael.laforge@twikiconcept.com>
 * @version      1.0
 * @package      S.a.S
 * @subpackage   core
 * @copyright    Copyright (c) 2010, Twiki Concept (www.twikiconcept.com)
 *
 * @update (05/10/2010) [Mikael Laforge] - v1.0 - Script creation
 *
 * @uses valid_Core
 *
 * This librairie can be used to apply filters, rules and callbacks on data.
 * Data can be accessed like normal array. Ex: $post = Validator::factory($_POST); echo $post['username'] ;
 * Filters are applied before validation check().
 * Rules are verified during validation check(). Only 1 error can occur by field name.
 * Callbacks are called if no error is found during the validation check().
 * Filters, Rules and Callbacks are applied in the order they are given.
 */

class Validator implements \ArrayAccess
{
    // Data to validate
    protected   $data = [] ;
    protected   $data_error = [];

    // Rules to process
    protected   $rules = [] ;

    // Callbacks that will be processed after check()
    protected   $callbacks = [] ;

    // Error messages
    protected   $errors = [] ;
    protected   $errors_by_key = [] ;
    protected   $error_key = '_global' ; // Key used to save error messages
    public      $has_error = false ;

    // Native callbacks that can be used with rule() method
    protected   $native_rules = array
    (
        'required'          => 'valid::not_empty',
        'email'             => 'valid::email',
        'email_domain'      => 'valid::email_domain',
        'phone'             => 'valid::phone',
        'zip'               => 'valid::zip',
        'len'               => 'valid::len',
        'username'          => 'valid::username',
        'password'          => 'valid::password',
        'credit_card'       => 'valid::credit_card',
        'routing_number'    => 'valid::routing_number',
        'url'               => 'valid::url',
        'ip'                => 'valid::ip',
        'alpha'             => 'valid::alpha',
        'alpha_numeric'     => 'valid::alpha_numeric',
        'alpha_dash'        => 'valid::alpha_dash',
        'digit'             => 'valid::digit',
        'text'              => 'valid::standard_text',
        'numeric'           => 'valid::numeric',
        'decimal'           => 'valid::decimal',
        'equal'             => 'valid::equal',
        'not_equal'         => 'valid::not_equal',

        // Special rules are added by constructor.
        // They are specified here for reference.
        //'equal_to_field'         => array($this, 'equal_field'),
    );

    /**
     * Static constructor
     * @param mixed $data
     * @param string $error_key default error_key used
     */
    public static function factory ( $data, $error_key = '_global' ) { return new self($data, $error_key); }

    /**
     * Constructor
     * @param mixed $data
     * @param string $error_key default error_key used
     */
    public function __construct ( $data, $error_key = '_global'  )
    {
        $this->data = (array) $data ;

        $this->error_key = $error_key ;

        // Add special native rules
        $this->native_rules['equal_to_field'] = array($this, 'equal_field');
    }

    /**
     * Filter data before validation
     * @param string|bool|array $name field name | array of field name | true will apply filter on all fields
     * @param string $callback function name
     * @param array $params
     */
    public function filter ( $name, $callback, $params = [] )
    {
        $names = ( $name === true ) ? array_keys($this->data) : (array) $name ;

        foreach ( $names as $name ) {
            if ( array_key_exists($name, $this->data) ) {
                $tmp = $params ; array_unshift($tmp, $this->data[$name]);
                $this->data[$name] = call_user_func_array($callback, $tmp);
            }
        }

        return $this ;
    }

    /**
     * Change key used to store error msg keys
     * It must be used before setting rules
     * @param string $str
     */
    public function error_key ( $str ) { $this->error_key = $str; return $this; }

    /**
     * Rules to be added
     * @param string $e_lang lang key used as error message
     * @param string|bool|array $name field name | array of field name | true will apply filter on all fields
     * @param string $callback function name
     * @param array $params
     */
    public function rule ($e_lang, $name, $callback, $params = [] )
    {
        $names = ( $name === true ) ? array_keys($this->data) : (array) $name ;

        foreach ( $names as $name )
        {
            if ( ! array_key_exists($name, $this->data) ) $this->data[$name] = null ;

            $this->rules[$e_lang][$name] = array
            (
                'error_key' => $this->error_key,
                'callback' => $callback,
                'params' => $params
            );
        }

        return $this ;
    }

    /**
     * Callbacks that will be called right after successful check
     * @param string|bool|array $name field name | array of field name | true will apply filter on all fields
     * @param string $callback function name
     * @param array $params
     */
    public function callback ( $name, $callback, $params = [] )
    {
        $names = ( $name === true ) ? array_keys($this->data) : (array) $name ;

        foreach ( $names as $name ) {
            if ( array_key_exists($name, $this->data) ) {
                $this->callbacks[$name][] = array
                (
                    'callback' => $callback,
                    'params' => $params
                );
            }
        }

        return $this ;
    }

    /**
     * Process validations by checking for rules
     * Callbacks are called for each data piece that is valid
     */
    public function check()
    {
        foreach ( $this->rules as $lk => $names )
        {
            $error = false ;

            foreach ( $names as $name => $arr )
            {
                // Only 1 error by field
                if ( isset($this->data_error[$name]) && $this->data_error == true ) continue ;

                // No error for this rule yet, keep checking
                if ( ! $error )
                {
                    $callback = isset($this->native_rules[$arr['callback']]) ? $this->native_rules[$arr['callback']] : $arr['callback'] ;
                    $params = isset($arr['params']) ? $arr['params'] : [] ;
                    array_unshift($params, (isset($this->data[$name]) ? $this->data[$name] : null));

                    // Error found
                    if ( ! call_user_func_array($callback, $params) )
                    {
                        $error = $this->has_error = true ;
                        $this->errors_by_key[$arr['error_key']][] = Nex::lang($lk) ;
                        $this->errors[] = Nex::lang($lk) ;

                        $this->data_error[$name] = true ;
                    }
                }
                // Error found already, just apply it to all fields with the same rule
                else {
                    $this->data_error[$name] = true ;
                }
            }
        }

        // Callbacks processing
        if ( $this->has_error == false )
        {
            foreach ( $this->callbacks as $name => $callbacks )
            {
                foreach ( $callbacks as $arr )
                {
                    $callback = $arr['callback'] ;
                    $params = isset($arr['params']) ? $arr['params'] : [] ;
                    array_unshift($params, (isset($this->data[$name]) ? $this->data[$name] : null));

                    $this->data[$k] = call_user_func_array($callback, $params);
                }
            }
        }

        return $this->has_error ? false : true ;
    }

    /**
     * Return errors
     * @param bool $by_error_key return errors as simple error list or multi-dimensional array classed by error key
     */
    public function errors( $by_error_key = false )
    {
        $errors = [];
        if ( $by_error_key ) {
            foreach ( $this->errors_by_key as $k => $arr ) {
                foreach ( $arr as $v ) {
                    $errors[$k][] = $v ;
                }
            }
        } else {
            foreach ( $this->errors as $v ) {
                $errors[] = $v ;
            }
        }

        return $errors ;
    }

    /**
     * Check if data has error
     * @param string $field
     */
    public function has_error ( $field = null )
    {
        if ( $field && isset($this->data[$field]) ) {
            return isset($this->data_error[$field]) ? $this->data_error[$field] : false ;
        }
        else {
            return $this->has_error ;
        }
    }

    /**
     * Fill object with validator data
     * @param Object $obj by reference
     */
    public function fill ( & $obj )
    {
        foreach ( $this->data as $k => $v ) {
            $obj->$k = $v ;
        }
    }

    /**
     * Return data
     */
    public function get_data () { return $this->data; }

    //
    //
    // Extra validation methods
    // -------------------------------------------------------------------------

    /**
     * Compare 2 values. If they are not the same, return false
     * @param string $value1 value to compare
     * @param string $field2 name of field that contains the second value to compare
     * @return bool
     */
    public function equal_field ( $value1, $field2, $strict = false )
    {
        if ( !isset($this->data[$field2]) ) return false ;

        return valid::equal($value1, $this->data[$field2], $strict);
    }

    //
    //
    // Implementation rules
    // -------------------------------------------------------------------------

    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }

    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    public function offsetGet($offset)
    {
        return isset($this->data[$offset]) ? $this->data[$offset] : null;
    }
}
