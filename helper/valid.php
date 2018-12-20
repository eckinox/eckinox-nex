<?php

namespace Eckinox\Nex;

/**
 * @author       Mikael Laforge <mikael.laforge@gmail.com>
 * @version      1.2.12
 * @package      Nex
 * @subpackage   core
 *
 * @update (11/08/2009) [Mikael Laforge] - 1.0.0 - Script creation
 * @update (17/01/2010) [Mikael Laforge] - 1.1.0 - Added methods username() and password()
 * @update (06/10/2010) [Mikael Laforge] - 1.2.0 - Added methods not_empty(), regex(), len(), creditCard(), routingNumber()
 * @update (11/05/2011) [Mikael Laforge] - 1.2.1 - Added methods token() and hexcolor()
 * @update (22/06/2011) [Mikael Laforge] - 1.2.2 - phone() method now accept phone number without area code
 * @update (19/10/2011) [Mikael Laforge] - 1.2.3 - Added url_segment() method
 * @update (30/11/2011) [Mikael Laforge] - 1.2.4 - Added slug() method
 * @update (04/05/2012) [Mikael Laforge] - 1.2.5 - Added emails() method
 * @update (04/06/2012) [Mikael Laforge] - 1.2.6 - password() now requires at least 1 number and 1 letter
 * @update (03/07/2012) [ML] - 1.2.7 - password() now requires at least 1 number and 1 letter
 *                                     Bugfix for password() method
 * @update (30/08/2012) [ML] - 1.2.8 - username() method now accept dots (.) for valid username
 * @update (27/11/2012) [ML] - 1.2.9 - uppercase letters are now accepted in email() method
 * @update (28/01/2013) [ML] - 1.2.10 - revised methods digit(), alpha(), alpha_dash(), alpha_numeric()
 * @update (03/09/2013) [ML] - 1.2.11 - username() method will now accept any letter, any number in any language
 * @update (15/11/2013) [ML] - 1.2.12 - Now support labels with + in emails (Gmail feature)
 * @update (20/05/2015) [ML] - 1.2.13 - Fixed bug in username() regex
 *
 * 11/08/2009
 * This class was made to help with validation
 * Methods alway return true when valid and false when not valid
 */

abstract class valid {

    /**
     * Not empty validation
     */
    public static function not_empty($str) {
        return !empty($str);
    }

    /**
     * Check if string is valid email format
     * @param string			$email - email
     * @return bool				false if email format isnt valid
     */
    public static function email($email) {
        # old regex -> $regex = '/^([._a-z0-9-]+[._a-z0-9-]*(\+[._a-z0-9-]+)?)@(([a-z0-9-]+\.)*([a-z0-9-]+)(\.[a-z]{2,4}))$/i';
        return (bool) preg_match("/^[^@\s]+@[^@\s]+\.[^@\s]+$/", $email);
    }

    /**
     * Check if string or array contains valid emails
     * @param string|array
     */
    public static function emails($emails, $separ = ',') {
        if (is_string($emails))
            $emails = explode($separ, $emails);

        foreach ($emails as $email) {
            if (!self::email(trim($email)))
                return false;
        }

        return true;
    }

    /**
     * Validate email domain's dnx record
     */
    public static function email_domain($email) {
        // Get Domain
        list(, $domain) = explode("@", $email);

        // Note: checkdnsrr() is not implemented on Windows platforms
        if (function_exists('checkdnsrr')) {
            return (bool) checkdnsrr($domain, 'MX');
        }

        // Valid domain
        if (@getmxrr($domain, $mxhost) === true) {
            return true;
        } else {
            $GLOBALS['ERROR_EXCEPTION'] = true;
            try {
                $fp = @fsockopen($domain, 25, $errno, $errstr, 3);
                unset($GLOBALS['ERROR_EXCEPTION']);
                if ($fp !== false) {
                    return true;
                } else {
                    return false;
                }
            } catch (Exception $e) {
                unset($GLOBALS['ERROR_EXCEPTION']);
                return false;
            }
            return false;
        }

        return true;
    }

    /**
     * Validate date
     * @param string $str
     * @return bool
     */
    public static function date($str) {
        //match the format of the date yyyy-mm-dd
        if (preg_match("/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/", $str, $boom)) {
            //check weather the date is valid of not
            if (checkdate($boom[2], $boom[3], $boom[1]))
                return true;
            else
                return false;
        }
        else {
            return false;
        }
    }

    /**
     * Validate phone number format
     * @param $str string to validate
     */
    public static function phone($str) {
        $regex = '/^(\(?[0-9]{3}\)?(-| )?)?[0-9]{3}(-| )?[0-9]{4}$/';

        return (bool) preg_match($regex, $str);
    }

    /**
     * Validate Zip code format
     * @param $str strin to validate
     */
    public static function zip($str) {
        $regex = '/^[a-z][0-9][a-z](-| )?[0-9][a-z][0-9]$/i';
        return (bool) preg_match($regex, $str);
    }

    /**
     * Validate string length
     * @param string $str
     * @param int $min_len
     * @param int $max_len
     */
    public static function len($str, $min_len = null, $max_len = null) {
        $len = strlen($str);

        if ($min_len !== null && $len < $min_len)
            return false;
        if ($max_len !== null && $len > $max_len)
            return false;

        return true;
    }

    /**
     * Check if a username is valid
     * @param string $username username to validate
     * @param int $min_char minimum number of char - 0 unlimited
     * @param int $max_char maximum number of char - 0 unlimited
     */
    public static function username($username, $min_char = 3, $max_char = 75) {
        // Check len
        $len = strlen($username);
        if ($len < $min_char || ($max_char != 0 && $len > $max_char)) {
            return false;
        }

        // Accept only letters, numbers, -, _ and .
        $regex = '/^[\pL\pN\._-]+$/u';

        return (bool) preg_match($regex, $username);
    }

    /**
     * Check if a password is valid
     * @param string $password password to validate
     * @param int $min_char minimum number of char - 0 unlimited
     * @param int $max_char maximum number of char - 0 unlimited
     */
     public static function password($password, $min_char = 5, $max_char = 0) {
         $len = strlen($password);
         return $len >= $min_char && ( ( $max_char == 0 ) || ( $len <= $max_char ) );
     }

    /**
     * Algorithme Luhn for credit card validation.
     * @param int $number credit card number (w/o space).
     * @return bool
     */
    public static function credit_card($number) {
        // If is not numeric
        if (!is_numeric($number)) {
            return false;
        }

        //Init vars
        $odd = true;
        $sum = 0;
        $str = array_reverse(str_split((string) $number));

        foreach ($str as $digit) {
            if ($odd === false) {
                (int) $digit *= 2;

                if ($digit > 9) {
                    $digit -= 9;
                }
            }

            $sum += (int) $digit;

            $odd = !$odd;
        }

        // If $sum can't be divided by 10, wrong number
        return ($sum % 10 == 0) ? true : false;
    }

    /**
     * Algorithme for routing number validation.
     * @param int $number routing number.
     * @return bool
     */
    public static function routing_number($number) {
        if (strlen($number) != 9 || !ctype_digit($number))
            return false;

        $result = (7 * ($number[0] + $number[3] + $number[6]) + 3 * ($number[1] + $number[4] + $number[7]) + 9 * ($number[2] + $number[5])) % 10 == $number[8] ? true : false;

        return $result;
    }

    /**
     * Validate URL
     * @param string $url
     * @return bool
     */
    public static function url($url) {
        return (bool) filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_HOST_REQUIRED);
    }

    /**
     * Validate URL segment
     * @param string $segment
     * @param int $min_char minimum number of char - 0 unlimited
     * @param int $max_char maximum number of char - 0 unlimited
     */
    public static function url_segment($segment, $min_char = 3, $max_char = 45) {
        // Check len
        $len = strlen($segment);
        if ($len < $min_char || ($max_char != 0 && $len > $max_char)) {
            return false;
        }

        // Accept letters, digits, underscore, trait, point
        // At least one letter required
        $regex = "/^[a-z0-9\._-]*[a-z][a-z0-9\._-]*$/i";

        return (bool) preg_match($regex, $segment);
    }

    /**
     * Validate Slug (Like url segment but more strict)
     * @param string $segment
     * @param string $separ
     */
    public static function slug($str, $separ = '-') {
        $regex = "/^[a-z0-9" . preg_quote($separ) . "]+$/i";

        return (bool) preg_match($regex, $str);
    }

    /**
     * Validate IP
     * @param string IP address
     * @param boolean allow IPv6 addresses
     * @return boolean
     */
    public static function ip($ip, $ipv6 = FALSE) {
        // Do not allow private and reserved range IPs
        $flags = FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;

        if ($ipv6 === TRUE)
            return (bool) filter_var($ip, FILTER_VALIDATE_IP, $flags);

        return (bool) filter_var($ip, FILTER_VALIDATE_IP, $flags | FILTER_FLAG_IPV4);
    }

    /**
     * Checks whether a string consists of alphabetical characters only.
     * @param string input string
     * @return bool
     */
    public static function alpha($str, array $allowed_chars = [ ' ', '-' ]) {
        return ctype_alpha(str_replace($allowed_chars, "", $str));
    }

    /**
     * Checks whether a string consists of alphabetical characters and numbers only
     * @param string input string
     * @return bool
     */
    public static function alpha_numeric($str, array $allowed_chars = [ ' ', '-' ]) {
        return ctype_alnum(str_replace($allowed_chars, "", $str));
    }

    /**
     * Checks whether a string consists of alphabetical characters, numbers, underscores and dashes only.
     * @param string input string
     * @return bool
     */
    public static function alpha_dash($str) {
        return (bool) preg_match('/^[-a-z0-9_]++$/i', $str);
    }

    /**
     * Checks whether a string consists of digits only (no dots or dashes).
     * @param string input string
     * @return bool
     */
    public static function digit($str) {
        return ctype_digit($str);
    }

    /**
     * Checks whether a string is a valid number (negative and decimal numbers allowed).
     * @param string input string
     * @return boolean
     */
    public static function numeric($str) {
        return (is_numeric($str) && preg_match('/^[-0-9.]++$/D', (string) $str));
    }

    /**
     * Checks whether a string is a valid text. Letters, numbers, whitespace,
     * dashes, periods, and underscores are allowed.
     * @param string $str
     * @return bool
     */
    public static function standard_text($str) {
        return (bool) preg_match('/^[-\pL\pN\pZ_.]++$/uD', (string) $str);
    }

    /**
     * Checks if a string is a proper decimal format. The format array can be
     * used to specify a decimal length, or a number and decimal length, eg:
     * array(2) would force the number to have 2 decimal places, array(4,2)
     * would force the number to have 4 digits and 2 decimal places.
     * @param string input string
     * @param array decimal format: y or x,y
     * @return boolean
     */
    public static function decimal($str, $format = NULL) {
        // Create the pattern
        $pattern = '/^[0-9]%s\.[0-9]%s$/';

        if (!empty($format)) {
            if (count($format) > 1) {
                // Use the format for number and decimal length
                $pattern = sprintf($pattern, '{' . $format[0] . '}', '{' . $format[1] . '}');
            } elseif (count($format) > 0) {
                // Use the format as decimal length
                $pattern = sprintf($pattern, '+', '{' . $format[0] . '}');
            }
        } else {
            // No format
            $pattern = sprintf($pattern, '+', '+');
        }

        return (bool) preg_match($pattern, (string) $str);
    }

    /**
     * Equal to
     */
    public static function equal($val, $to, $strict = false) {
        return ($strict ? ($val === $to ? true : false) : ($val == $to ? true : false));
    }

    /**
     * Not equal to
     */
    public static function not_equal($val, $to, $strict = false) {
        return ($strict ? ($val !== $to ? true : false) : ($val != $to ? true : false));
    }

    /**
     * Regex
     */
    public static function regex($str, $regex) {
        return (bool) preg_match($regex, $str);
    }

    /**
     * Valid hex color
     */
    public static function hexcolor($str) {
        return (bool) preg_match('/^#?[a-f0-9]{6}$/i', $str);
    }

    /**
     * Check token in data compared in session
     * @param string $name
     * @param array $data
     */
    public static function token($name, $data) {
        return (isset($data[$name], $_SESSION[$name]) && $data[$name] == $_SESSION[$name]) ? true : false;
    }

}
