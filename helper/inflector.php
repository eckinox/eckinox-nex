<?php

namespace Eckinox\Nex;

/**
 * @author       Mikael Laforge <mikael.laforge@gmail.com>
 * @version      1.2.1
 * @package      Nex
 * @subpackage   core
 *
 * @update (10/10/2010) [ML] - 1.0.0 - script creation
 * @update (09/04/2013) [ML] - 1.1.0 - Rebuild class to using i18n language system
 *                                                 can manage multiple words at once
 *                                                 now use multi-bytes string manipulation functions
 *                                                 @uses inflector language file
 * @update (12/11/2013) [ML] - 1.2.0 - added suffixOrdinal() method
 * @update (28/07/2014) [ML] - 1.2.1 - fixed bug in plural() method returning singular when using $count = null
 *
 * Word inflector
 */


abstract class inflector {

    // Internal cache
    protected static $cache = [];
    // Uncountable and irregular words
    protected static $singulars = [];
    protected static $plurals = [];

    /**
     * Checks if a word is defined as uncountable.
     * @param string $str word to check
     * @param string $lang force a language. Current system language used by default
     * @return boolean
     */
    public static function uncountable($str, $lang = null) {
        $lang = !$lang ? Nex::$lang : $lang;
        $str = strtolower(trim($str));

        self::load_exceptions($lang);

        // Is uncountable
        if (isset(self::$singulars[$lang][$str]) && self::$singulars[$lang][$str] = $str) {
            return true;
        }

        return false;
    }

    /**
     * Makes a plural word singular.
     * @param string word to singularize
     * @param integer number of things
     * @param string $lang force a language. Current system language used by default
     * @return string
     */
    public static function singular($str, $count = null, $lang = null) {
        $lang = !$lang ? Nex::$lang : $lang;
        $str = mb_strtolower(trim($str));

        // Do nothing with a single count
        if ($count > 1)
            return $str;

        // Cache key name
        $key = 'singular_' . $str . $lang;

        if (isset(self::$cache[$key]))
            return self::$cache[$key];

        self::load_exceptions($lang);

        $slang = substr($lang, 0, 2);
        $words = explode(' ', $str);
        $return = [];

        foreach ($words as $word) {
            // Uncountable or irregular
            if (isset(self::$plurals[$lang][$word])) {
                $return[] = self::$plurals[$lang][$word];
                continue;
            }

            if (method_exists(__CLASS__, $slang . '_transform')) {
                $method = $slang . '_transform';
                $return[] = self::$method($word, 'singular');
            }
            // Minimal default check, exposed to mistakes
            elseif (mb_substr($word, -1) === 's' && mb_substr($word, -2) !== 'ss') {
                $return[] = mb_substr($word, 0, -1);
            }
        }

        return self::$cache[$key] = implode(' ', $return);
    }

    /**
     * Makes a singular word plural.
     * @param   string  word to pluralize
     * @param string $lang force a language. Current system language used by default
     * @return  string
     */
    public static function plural($str, $count = null, $lang = null) {
        $lang = !$lang ? Nex::$lang : $lang;
        $str = mb_strtolower(trim($str));

        // Do nothing with singular
        if ($count < 2 && $count !== null)
            return $str;

        // Cache key name
        $key = 'plural_' . $str . $lang;

        if (isset(self::$cache[$key]))
            return self::$cache[$key];

        self::load_exceptions($lang);

        $slang = substr($lang, 0, 2);
        $words = explode(' ', $str);
        $return = [];

        foreach ($words as $word) {
            // Uncountable or irregular
            if (isset(self::$singulars[$lang][$word])) {
                $return[] = self::$singulars[$lang][$word];
                continue;
            }

            if (method_exists(__CLASS__, $slang . '_transform')) {
                $method = $slang . '_transform';
                $return[] = self::$method($word, 'plural');
            }
            // Minimal default check, exposed to mistakes
            elseif (mb_substr($word, -1) !== 's') {
                $return[] = $word . 's';
            }
        }

        // Set the cache and return
        return self::$cache[$key] = implode(' ', $return);
    }

    /**
     * suffix an ordinal number
     */
    public static function suffixOrdinal($number, $lang = null) {
        $lang = !$lang ? Nex::$lang : $lang;
        $slang = substr($lang, 0, 2);

        if (method_exists(__CLASS__, $slang . '_transform')) {
            $method = $slang . '_transform';
            $return = self::$method($number, 'ordinal');
        } else {
            $return = $number;
        }

        return $return;
    }

    //
    //
    // Internal methods
    // -------------------------------------------------------------------------

    /**
     * en transformer
     * @param string $str
     * @param string $transform 'singular' | 'plural'
     */
    protected static function en_transform($str, $transform) {
        switch ($transform) {
            case 'singular' :
                if (preg_match('/[sxz]es$/', $str) || preg_match('/[^aeioudgkprt]hes$/', $str)) {
                    // Remove "es"
                    $str = mb_substr($str, 0, -2);
                } elseif (preg_match('/[^aeiou]ies$/', $str)) {
                    $str = mb_substr($str, 0, -3) . 'y';
                } elseif (substr($str, -1) === 's' && substr($str, -2) !== 'ss') {
                    $str = mb_substr($str, 0, -1);
                }
                break;

            case 'plural' :
                if (preg_match('/[sxz]$/', $str) || preg_match('/[^aeioudgkprt]h$/', $str)) {
                    $str .= 'es';
                } elseif (preg_match('/[^aeiou]y$/', $str)) {
                    // Change "y" to "ies"
                    $str = substr_replace($str, 'ies', -1);
                } else {
                    $str .= 's';
                }
                break;

            case 'ordinal':
                $str = (int) $str;
                if (!in_array(($str % 100), array(11, 12, 13))) {
                    switch ($str % 10) {
                        case 1: $str .= 'st';
                            break;
                        case 2: $str .= 'nd';
                            break;
                        case 3: $str .= 'rd';
                            break;
                    }
                } else {
                    $str .= 'th';
                }
                break;
        }

        return $str;
    }

    /**
     * fr transformer
     * @param string $str
     * @param string $transform 'singular' | 'plural'
     */
    protected static function fr_transform($str, $transform) {
        switch ($transform) {
            case 'singular' :
                if (mb_substr($str, -4) === 'eaux' || mb_substr($str, -3) == 'eux') {
                    $str = mb_substr($str, 0, -1);
                } elseif (mb_substr($str, -3) == 'aux') {
                    $str = mb_substr($str, 0, -3) . 'al';
                } elseif (mb_substr($str, -4) == 'ails') {
                    $str = mb_substr($str, 0, -4) . 'ail';
                } elseif (mb_substr($str, -1) == 's' && mb_substr($str, -2) != 'ss') {
                    $str = mb_substr($str, 0, -1);
                }
                break;

            case 'plural' :
                if (mb_substr($str, -3) === 'eau' || in_array(mb_substr($str, -2), array('au', 'eu'))) {
                    $str .= 'x';
                } elseif (mb_substr($str, -2) == 'al') {
                    $str = mb_substr($str, 0, -2) . 'aux';
                } elseif (mb_substr($str, -3) == 'ail') {
                    $str = mb_substr($str, 0, -3) . 'ails';
                } elseif (mb_substr($str, -2) == 'ou' || !in_array(mb_substr($str, -1), array('z', 'x', 's'))) {
                    $str .= 's';
                }
                break;

            case 'ordinal':
                $str = (int) $str;
                if ($str === 1) {
                    $str .= 'er';
                } else {
                    $str .= 'e';
                }
                break;
        }

        return $str;
    }

    protected static function load_exceptions($lang) {
        if (!isset(self::$singulars[$lang])) {
            if (self::$singulars[$lang] = Nex::lang(array($lang => 'inflector'))) {
                // Make exceptions mirroed
                self::$plurals[$lang] = array_flip(self::$singulars[$lang]);
            }
        }
    }

}
