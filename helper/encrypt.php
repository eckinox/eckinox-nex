<?php

namespace Eckinox\Nex;

/**
 * @author       Mikael Laforge <mikael.laforge@twikiconcept.com>
 * @version      1.0
 * @package      S.a.S
 * @subpackage   helpers
 * @copyright    Copyright (c) 2009, Twiki Concept (www.twikiconcept.com)
 *
 * @update (03/02/2010) [Mikael Laforge] Script creation
 *
 * 03/02/2010
 * This class was made to help with encryption
 */

class encrypt {

    /**
     * This function is used to encode javascript with low security level
     * @param string $js javascript to encode
     * @return string
     */
    public static function js($js) {
        $tmp = '';
        $len = strlen($js);
        for ($i = 0; $i < $len; $i++)
            $tmp .= '%' . bin2hex($js[$i]);
        return "eval(\"$tmp\");";
    }

    /**
     * Encode/decode string with for low security level
     */
    public static function encode($str, $key = 'sastatik', $encrypt = true) {
        $str = ($encrypt == true) ? base64_encode(strrev($key . $str)) : substr(strrev(base64_decode($str)), strlen($key));
        return $str;
    }

    /**
     * Hashing function, can't be decrypted
     * @param string $str
     * @param string $method hashing function used. Will use config if not given
     * @param string $salt_pattern Ex: 2/5/8/11/15. Will use config if not given
     * @param bool $separator place separator between hashes
     * @param bool $raw_output raw_output param for hashes
     * @todo test
     */
    public static function hash($str, $method = null, $salt_pattern = null, $separator = '', $raw_output = false) {
        $salt_pattern = ($salt_pattern == null) ? Nex::config('encryption.salt_pattern') : $salt_pattern;
        $method = ($method = null || !in_array($method, hash_algos()) ) ? Nex::config('encryption.hash_method') : $method;

        $salt_index = explode('/', $salt_pattern);
        $str_len = strlen($str);
        $str1 = $str2 = '';

        for ($x = 0; $x < $str_len; $x++) {
            if (in_array($x, $salt_index)) {
                $str1 .= $str[$x];
            } else {
                $str2 .= $str[$x];
            }
        }

        return hash($method, $str1, $raw_output) . $separator . hash($method, $str2, $raw_output);
    }

    /**
     * Better crypt function
     */
    function crypt($clear, $hashed = NULL) {
        $salt_len = 100;
        if (empty($hashed))
            for ($salt = '', $x = 0; $x++ < $salt_len; $salt .= bin2hex(chr(mt_rand(0, 255))))
                ;   // make a new salt
        else
            $salt = substr($hashed, 0, $salt_len * 2);  //  extract existing salt

        return hash('whirlpool', $salt . $clear);
    }

    /**
     * Encrypts some data with a given key
     * mcrypt extension must be available
     * @data
     * @key
     * @returns		the encrypted data in binary hex format
     */
    public static function encrypt($data, $key, $alg = "blowfish", $mode = "ecb", $iv = "00000000") {
        if (false === ($td = @mcrypt_module_open($alg, "", $mode, ""))) {
            throw new NEX_Exception(array('error_msg' => "Can not initialize the encryption module!", 'error_code' => ENCRYPTION_ERROR));
        }

        $iv_size = @mcrypt_get_iv_size($alg, $mode);
        if (strlen($iv) != $iv_size) {
            throw new NEX_Exception(array('error_msg' => "The IV size should be $iv_size!", 'error_code' => ENCRYPTION_ERROR));
        }

        if (@mcrypt_generic_init($td, $key, $iv)) {
            throw new NEX_Exception(array('error_msg' => "Encryption error!", 'error_code' => ENCRYPTION_ERROR));
        }

        $data = @mcrypt_generic($td, $data);

        @mcrypt_generic_deinit($td);
        @mcrypt_module_close($td);

        $data = @unpack("H*", $data);

        return $data[1];
    }

    /**
     * Decrypts some data with a given key
     * mcrypt extension must be available
     * @data	in hex encrypted format
     * @key
     * @returns	the decrypted data
     */
    public static function decrypt($data, $key, $alg, $mode, $iv) {
        if (false === ($td = @mcrypt_module_open($alg, "", $mode, ""))) {
            throw new NEX_Exception(array('error_msg' => "Decryption error!", 'error_code' => DECRYPTION_ERROR));
        }

        $iv_size = @mcrypt_get_iv_size($alg, $mode);
        if (strlen($iv) != $iv_size) {
            throw new NEX_Exception(array('error_msg' => "The IV size should be $iv_size!", 'error_code' => DECRYPTION_ERROR));
        }

        $r = @mcrypt_generic_init($td, $key, $iv);
        if (false === $r || 0 > $r) {
            throw new NEX_Exception(array('error_msg' => "Decryption error!", 'error_code' => DECRYPTION_ERROR));
        }

        $data = @mdecrypt_generic($td, @pack("H*", $data));

        @mcrypt_generic_deinit($td);
        @mcrypt_module_close($td);

        // remove the padded "\0"
        return str_replace("\0", "", $data);
    }

}
