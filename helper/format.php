<?php

namespace Eckinox\Nex;

/**
 * @author       Mikael Laforge <mikael.laforge@gmail.com>
 * @version      1.1.1
 * @package      Nex
 * @subpackage   core
 *
 * @update (02/02/2010) [ML] - 1.0 - Script creation
 * @update (22/06/2011) [ML] - 1.01 - method phone() now return original string if not able to format
 * @update (15/09/2011) [ML] - 1.02 - improved method time()
 * @update (30/09/2011) [ML] - 1.1.0 - Added method url()
 * @update (15/01/2014) [ML] - 1.1.1 - Added 2nd and 3nd argument to phone() method to better support different phone format.
 *                                     Rely on external "libphonenumber" librairy
 *                                     Fixed uncatched exception from PhoneNumberLib in phone()
 *
 * This class was made to help format strings
 */

abstract class format {

    /**
     * Display phone number to format '(999) 999-9999 #9999'.
     * @param string $str string like '9999999999' with optionnal ext.
     * @param string $country_code 2 char country code
     * @return string
     */
    public static function phone($str, $country_code = null, $international = false) {
        if ($str && $country_code && file_exists(DOC_ROOT . EXT_PATH . 'php/libphonenumber/PhoneNumberUtil.php')) {
            require_once DOC_ROOT . EXT_PATH . 'php/libphonenumber/PhoneNumberUtil.php';

            $phoneUtil = PhoneNumberUtil::getInstance();
            try {
                $proto = $phoneUtil->parse($str, strtoupper($country_code));

                if ($international)
                    return $phoneUtil->format($proto, PhoneNumberFormat::INTERNATIONAL);
                else
                    return $phoneUtil->format($proto, PhoneNumberFormat::NATIONAL);
            } catch (Exception $e) {
                Nex::exception($e);
            }
        }

        $origin = $str;
        $str = text::cleanPhone($str);

        if (strlen($str) >= 10) {
            return "(" . substr($str, 0, 3) . ") " . substr($str, 3, 3) . "-" . substr($str, 6, 4) . ((strlen($str) > 10) ? " #" . substr($str, 10) : "");
        } elseif (strlen($str) == 7) {
            return substr($str, 0, 3) . '-' . substr($str, 3);
        } else {
            return $origin;
        }
    }

    /**
     * Display zip code to format 'X9X 9X9'.
     * @param string $str string like 'X9X9X9'.
     * @param string $separator string used to separ both part
     * @return string
     */
    public static function zipCode($str, $separator = ' ') {
        // Make sure we got 6 char
        if (strlen($str) != 6) {
            return $str;
        }

        return substr($str, 0, 3) . $separator . substr($str, -3);
    }

    /**
     * Display url
     * @param string $str string like 'www.google.com'.
     * @param string $protocol used string as protocol
     * @return string
     */
    public static function url($str, $protocol = 'http://') {
        $str = strpos($str, '://') === false ? $protocol . $str : $str;

        return $str;
    }

    /**
     * Display time in correct format
     * @param string|int $str string like '17', '815', '1404' or '15h30' or '8h00'
     * @param string $separator
     * @return string
     */
    public static function time($str, $separator = ':') {
        switch (strlen($str)) {
            case 4 :
                if (is_numeric($str)) {
                    $str = substr($str, 0, 2) . $separator . substr($str, 2);
                } else {
                    $str = substr($str, 0, 1) . $separator . substr($str, 2);
                }

                break;

            case 3 : $str = substr($str, 0, 1) . $separator . substr($str, 1);
                break;

            case 2:
            case 1: $str = substr($str, 0, 2) . $separator . '00';
        }

        return $str;
    }

}
