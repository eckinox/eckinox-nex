<?php

namespace Eckinox\Nex;

/**
 * @author       Mikael Laforge <mikael.laforge@gmail.com>
 * @version      1.0.3
 * @package      Nex
 * @subpackage   core
 *
 * @update (10/12/2009) [Mikael Laforge] - 1.0.0 - Script creation
 * @update (26/10/2010) [Mikael Laforge] - 1.0.1 - Added textify() and untextify() for lighter and faster serialization or 2 dimensionnal arrays. These methods are based on the CSV system
 * @update (10/07/2012) [Mikael Laforge] - 1.0.2 - Added sumField() method
 * @update (15/08/2012) [Mikael Laforge] - 1.0.3 - unserialize() will return argument if its not a string
 *
 * This class was made to help manipulate arrays
 */

abstract class arr {

    /**
     * Set value to array with a given path using recursion
     * @param array	$array - array to set passed by reference !
     * @param string $path - path like 'person.name.first'
     * @param string $value - value to set
     */
    public static function set(& $array, $path, $value) {
        $path_arr = explode('.', $path);

        // Go to next node
        if (isset($path_arr[1])) {
            self::set($array[array_shift($path_arr)], implode('.', $path_arr), $value);
        }
        // We are at the end of the path, set value
        else {
            $array[$path_arr[0]] = $value;
        }
    }

    /*     * té
     * Get value from an array with a given path
     * @param array			$array
     * @param string		$path - path like 'person.name.first'
     */

    public static function get($array, $path, $default = null) {
        $path_arr = explode('.', $path);

        // Go to next node
        if (isset($array[$path_arr[0]])) {
            if (isset($path_arr[1])) {
                return self::get($array[array_shift($path_arr)], implode('.', $path_arr), $default);
            }
            // We are at the end of the path, return value
            else {
                return $array[$path_arr[0]];
            }
        } else {
            return $default;
        }
    }

    /**
     * Merge array recursively with overwrites. (array_merge_recursive() doesn't overwrite, it appends)
     * @param array
     * @param array
     */
    public static function merge_recursive($arr1, $arr2) {
        foreach ($arr2 as $key => $value) {
            if (array_key_exists($key, $arr1) && is_array($value)) {
                $arr1[$key] = self::merge_recursive($arr1[$key], $arr2[$key]);
            } else {
                $arr1[$key] = $value;
            }
        }

        return $arr1;
    }

    /**
     * Sum all array values with the same index name
     * and return result. This can be used for report summary.
     * @param array $arr1
     * @param array $arr2
     * @param array ...
     * @return array
     */
    public static function sumFields($arr1, $arr2 /* ... */) {
        $sum = [];

        $args = func_get_args();

        // For each arguments
        foreach ($args as $arr) {
            if (!is_array($arr))
                continue;

            // For each index in array
            foreach ($arr as $i => $val) {
                if (!isset($sum[$i]))
                    $sum[$i] = 0;

                $sum[$i] += $val;
            }
        }

        return $sum;
    }

    /**
     * Sum 2 dimension array column and return result
     * This can be used for report summary.
     * @param array $arr
     * @param array $field
     * @return int
     */
    public static function sumField($arr, $field = '') {
        $result = 0;

        // For each arguments
        foreach ($arr as $r) {
            if (is_array($r)) {
                $result += (isset($r[$field]) ? $r[$field] : 0);
            } elseif (is_numeric($r)) {
                $result += $r;
            }
        }

        return $result;
    }

    /**
     * Implode for assoc arrays
     * @param string $separ
     * @param string $key_separ
     * @param array $array
     * @param array $exceptions
     * @return string
     */
    public static function implode($separ, $key_separ, $array, $exceptions = []) {
        $str = '';
        $exceptions = (array) $exceptions;

        foreach ($array as $key => $value) {
            $str .= (!in_array($key, $exceptions) ? $key . $key_separ . $value . $separ : '');
        }

        return (string) substr($str, 0, -(strlen($separ)));
    }

    /**
     * Explode for assoc arrays
     * @param string $separ
     * @param string $key_separ
     * @param string $string
     * @return array
     */
    public static function explode($separ, $key_separ, $str) {
        $tmp = [];

        $array = explode($separ, $str);

        foreach ($array as $key => $value) {
            $inner_array = explode($key_separ, $value);

            if (count($inner_array) == 2) {
                $key = $inner_array[0];
                $value = $inner_array[1];
            }

            $tmp[$key] = $value;
        }

        return $tmp;
    }

    /**
     * Striplashes for arrays
     * @param array | string $var
     */
    public static function stripslashes(&$var) {
        if (is_array($var)) {
            $tmp = [];
            foreach ($var as $key => $value) {
                $tmp[$key] = self::stripslashes($value);
            }
            $var = $tmp;
        } else {
            $var = stripslashes($var);
        }

        return $var;
    }

    /**
     * Sort an array by one value in its sub array
     * by a path given by $subkey.
     * When an array is given in the path of subkey, it means alternative. array(array('food','water')) would mean sort by $array['food'] if exist, if not sort by $array['water']
     * @param array $array array to sort
     * @param array $subkey path to key in the sub array. Ex: array('food','fruit','lemon') would mean sort by $array['food']['fruit']['lemon'] value
     * @param string $sort php built-in sort to use
     */
    public static function subSort(array & $array, $subkey, $sort = 'asort') {
        // Init
        $a = null;
        $tmp = [];

        $subkey = (array) $subkey;

        foreach ($array as $key => $val) {
            $a = $val;
            foreach ($subkey as $k) {
                if (is_array($k)) { // If $k is array, we got alternative in the path of the $array
                    foreach ($k as $_k) {
                        if (isset($a[$_k]))
                            $a = $a[$_k];
                    }
                    continue;
                }

                if (isset($a[$k]))
                    $a = $a[$k];
            }
            $tmp[$key] = $a;
        }

        // Sort
        $sort($tmp);
        $a = [];

        foreach ($tmp as $key => $val) {
            $a[$key] = $array[$key];
        }

        $array = $a;
    }

    /**
     * Serialize an array with safe escaping method with serialize() or json_encode() the lastest being the fastest.
     * @param array $array array to serialize
     * @param bool $force_serialize force method to use serialize and not json_encode().
     * @return string
     */
    public static function serialize($array, $force_serialize = false) {
        // json_encode is faster but when it decode an array, it creates stdClass instead of Assoc array
        //$function = (function_exists('json_encode') && $force_serialize !== true) ? 'json_encode' : 'serialize' ;
        $function = 'serialize';
        return $function($array);
    }

    /**
     * Unserialize an array that has been serialized with this class serialize method
     * @param string $str string to unserialize
     * @return array
     */
    public static function unserialize($str) {
        if (!is_string($str))
            return $str;

        //$function = (function_exists('json_decode') && ! self::is_serialized($str)) ? 'json_decode' : 'serialize' ;
        $function = 'unserialize';
        return $function($str);
    }

    /**
     * Verify if a string is serialized
     * @param string $str string to check
     * @return bool
     */
    public static function is_serialized($str) {
        //if (trim($str) == "") { return false; }
        //if (preg_match("/^(i|s|a|o|d):(.*);/si", $str) != false) { return true ; }
        // Faster optimized version
        if (strlen($data) > 1 && strpbrk($str, 'adObis') == $str && $str[1] == ':') {
            return true;
        }

        return false;
    }

    /**
     * Convert an array to stdclass
     * @param array $array
     * @param bool $recursive
     * @param stdclass
     */
    public static function array2object(array $array, $recursive = false) {
        $obj = new stdClass();

        foreach ($array as $key => $value) {
            if (is_array($value) && $recursive == true) {
                $obj->$key = self::array2object($value);
            } else {
                $obj->$key = $value;
            }
        }

        return $obj;
    }

    /**
     * Convert stdclass to array
     * @param stdclass $obj
     * @param bool $recursive
     * @param array
     */
    public static function object2array($obj, $recursive = false) {
        $array = [];

        foreach ($obj as $key => $value) {
            if (is_object($value) && $recursive == true) {
                $array[$key] = self::object2array($value);
            } else {
                $array[$key] = $value;
            }
        }

        return $array;
    }

    /**
     * Returns the values from a single column of the input array, identified by
     * the $columnKey.
     *
     * Optionally, you may provide an $indexKey to index the values in the returned
     * array by the values from the $indexKey column in the input array.
     *
     * @param array $input A multi-dimensional array (record set) from which to pull
     *                     a column of values.
     * @param mixed $columnKey The column of values to return. This value may be the
     *                         integer key of the column you wish to retrieve, or it
     *                         may be the string key name for an associative array.
     * @param mixed $indexKey (Optional.) The column to use as the index/keys for
     *                        the returned array. This value may be the integer key
     *                        of the column, or it may be the string key name.
     * @return array
     */
    public static function array_column($input = null, $columnKey = null, $indexKey = null) {
        // Using func_get_args() in order to check for proper number of
        // parameters and trigger errors exactly as the built-in array_column()
        // does in PHP 5.5.
        $argc = func_num_args();
        $params = func_get_args();

        if ($argc < 2) {
            trigger_error("array_column() expects at least 2 parameters, {$argc} given", E_USER_WARNING);
            return null;
        }

        if (!is_array($params[0])) {
            trigger_error('array_column() expects parameter 1 to be array, ' . gettype($params[0]) . ' given', E_USER_WARNING);
            return null;
        }

        if (!is_int($params[1]) && !is_float($params[1]) && !is_string($params[1]) && $params[1] !== null && !(is_object($params[1]) && method_exists($params[1], '__toString'))
        ) {
            trigger_error('array_column(): The column key should be either a string or an integer', E_USER_WARNING);
            return false;
        }

        if (isset($params[2]) && !is_int($params[2]) && !is_float($params[2]) && !is_string($params[2]) && !(is_object($params[2]) && method_exists($params[2], '__toString'))
        ) {
            trigger_error('array_column(): The index key should be either a string or an integer', E_USER_WARNING);
            return false;
        }

        $paramsInput = $params[0];
        $paramsColumnKey = ($params[1] !== null) ? (string) $params[1] : null;

        $paramsIndexKey = null;
        if (isset($params[2])) {
            if (is_float($params[2]) || is_int($params[2])) {
                $paramsIndexKey = (int) $params[2];
            } else {
                $paramsIndexKey = (string) $params[2];
            }
        }

        $resultArray = [];

        foreach ($paramsInput as $row) {

            $key = $value = null;
            $keySet = $valueSet = false;

            if ($paramsIndexKey !== null && array_key_exists($paramsIndexKey, $row)) {
                $keySet = true;
                $key = (string) $row[$paramsIndexKey];
            }

            if ($paramsColumnKey === null) {
                $valueSet = true;
                $value = $row;
            } elseif (is_array($row) && array_key_exists($paramsColumnKey, $row)) {
                $valueSet = true;
                $value = $row[$paramsColumnKey];
            }

            if ($valueSet) {
                if ($keySet) {
                    $resultArray[$key] = $value;
                } else {
                    $resultArray[] = $value;
                }
            }
        }

        return $resultArray;
    }

    public static function isIterable($array) {
        return (is_array($array) || $array instanceof Traversable);
    }

    public static function transform($object) {
        return array_filter(json_decode(json_encode($object), true));
    }

}
