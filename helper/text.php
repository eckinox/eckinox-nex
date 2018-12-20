<?php

namespace Eckinox\Nex;

/**
 * @author       Mikael Laforge <mikael.laforge@gmail.com>
 * @version      1.1.6
 * @package      Nex
 * @copyright    Mikael Laforge
 *
 * @update (11/08/2009) [Mikael Laforge] - 1.0.0 - Script creation
 * @update (04/11/2011) [Mikael Laforge] - 1.0.1 - Fixed bug in limitLen() method
 * @update (29/11/2011) [Mikael Laforge] - 1.0.2 - Added limitLine() method
 * @update (30/11/2011) [Mikael Laforge] - 1.0.3 - Added cleanSlug() method
 * @update (01/12/2011) [Mikael Laforge] - 1.0.4 - Added alternate2() method
 * @update (12/01/2012) [Mikael Laforge] - 1.0.5 - Added stripEOL() method
 * @update (11/07/2012) [ML] - 1.0.6 - method finsStrLen() now normalize strings before searching giving more versatility
 * 										emphasis() method is now case insensitive
 * @update (15/11/2012) [ML] - 1.0.7 - Added hasHtml() method
 * @update (18/12/2012) [ML] - 1.0.8 - Added cleanNumber() method
 * @update (08/04/2013) [ML] - 1.1.0 - methods are now using mb_ string manipulation variants
 *                                     rebuilt normalize() method
 *                                     added methods underscore(), camelize(), humanize(), str2words()
 * @update (03/05/2013) [ML] - 1.1.1 - Added link() method to transform textual links in html <a>
 * @update (13/06/2013) [ML] - 1.1.2 - Added valFromStrLen() method
 * @update (26/09/2013) [ML] - 1.1.3 - Bufix in normalize() method when using utf8 str
 * 										Added toUtf8() method
 * @update (07/03/2014) [ML] - 1.1.4 - added cleanSpaces() method
 * 									 - Added str_pad() method which is a multibytes str_pad()
 * @update (29/05/2014) [ML] - 1.1.5 - added $include_end to limitLen() and limitLen2() methods
 * 									   fix limitLen() and limitLen2() exceeding limit to avoid breaking words.
 * 									   fix str2sentence not spliting on ? and !
 * 									   fixed a multibytes bug in limitLen2() which was still using strlen()
 * @update (28/07/2014) [ML] - 1.1.6 - Added startWithVowel() method
 *
 * 11/08/2009
 * This class was made to help with text manipulation
 */
abstract class text {

    /**
     * Alternate text passed as arguments
     * @param string		$text1
     * @param string		$text2
     * @return $text1 | $text2
     */
    public static function alternate($text1, $text2 = '') {
        // Déclaration du booléen
        if (!isset($alternate_bool)) {
            static $alternate_bool = 1;
        }

        if ($alternate_bool == 1) {
            $alternate_bool = 2;
            return $text1;
        } else {
            $alternate_bool = 1;
            return $text2;
        }
    }

    /**
     * Alternate text passed as arguments
     * This version use unique identifier to avoid colisions
     * @param string $id
     * @param string $text1
     * @param ...
     * @return $text1 | $text2
     */
    public static function alternate2(/* $id, $text1, ... */) {
        static $alternates = [];

        $args = func_get_args();
        $id = array_shift($args);

        // Déclaration du booléen
        if (!isset($alternates[$id])) {
            $alternates[$id] = $args;
        }

        $return = array_shift($alternates[$id]);
        array_push($alternates[$id], $return);

        return $return;
    }

    /**
     * Count the words in a string. punctuation and double space doesnt count as a word.
     * @param string $str
     */
    public static function countWords($str) {
        $words = 0;
        $str = preg_replace("/ +/", " ", $str);
        $array = explode(" ", $str);
        for ($i = 0; $i < count($array); $i++) {
            if (preg_match("/[0-9A-Za-zÀ-ÖØ-öø-ÿ]/", $array[$i]))
                $words++;
        }
        return $words;
    }

    /**
     * Return a certain number of word in a string.
     * Double space ( or more ) are removed
     *
     * @param string 		$str
     * @param int			$limit
     * @param string		$end string to attach at the end of paragraph when reduced
     * @param bool			$html if string is html or pure text
     * @return string
     */
    public static function limitWords($str, $limit, $end = '', $html = TRUE) {
        $str = preg_replace("/ +/", " ", $str);
        $word_count = self::countWords($str);
        $array = explode(' ', $str);

        $str = (($word_count > $limit) ? implode(' ', array_slice($array, 0, $limit)) . $end : $str);
        return ($html == TRUE) ? html::closeTags($str) : $str;
    }

    /**
     * Return a certain number of word in a string.
     * Double space ( or more ) are removed
     *
     * @param string 		$str
     * @param int			$limit
     * @param string		$end string to attach at the end of paragraph when reduced
     * @param int           $start
     * @param bool			$html if string is html or pure text
     * @return string
     */
    public static function limitWords2($str, $limit, $end = '', $start = 0, $html = TRUE) {
        $start = $start > 0 ? $start : 0;

        $str = preg_replace("/ +/", " ", $str);
        $array = explode(' ', $str);
        $array = array_slice($array, $start);
        $word_count = count($array);

        $str = (($word_count > $limit) ? implode(' ', array_slice($array, 0, $limit)) . $end : $str);
        return ($html == TRUE) ? html::closeTags($str) : $str;
    }

    /**
     * Return a certain number of char in string.
     * This function does not cut words.
     *
     * @param string 		$str
     * @param int			$limit
     * @param string		$end string to attach at the end of paragraph when reduced
     * @param bool			$html if string is html or pure text
     * @param bool			$include_end if $end arg should be included in char limit
     * @return string
     */
    public static function limitLen($str, $limit, $end = '', $html = true, $include_end = false) {
        if (mb_strlen($str) <= $limit)
            return $str;

        if ($include_end && $end)
            $limit -= mb_strlen($end);

        $pos = mb_strrpos(mb_substr($str, 0, $limit), ' ');
        if (false !== $pos) {
            $str = mb_substr($str, 0, $pos) . $end;
        }

        return ($html == TRUE) ? html::closeTags($str) : $str;
    }

    /**
     * Return a certain number of char in string.
     * This function does not cut words.
     *
     * @param string 		$str
     * @param int			$limit
     * @param string		$end string to attach at the end of paragraph when reduced
     * @param int           $start
     * @param bool			$html if string is html or pure text
     * @param bool			$include_end if $end arg should be included in char limit
     * @return string
     */
    public static function limitLen2($str, $limit, $end = '', $start = 0, $html = true, $include_end = false) {
        $start = $start > 0 ? $start : 0;

        // Cut beginning
        if ($start > 0) {
            if (($pos = mb_strpos($str, ' ', $start)) !== false) {
                $str = mb_substr($str, $pos + 1);
            }
        }

        if (mb_strlen($str) <= $limit)
            return $str;

        if ($include_end && $end)
            $limit -= mb_strlen($end);

        // Cut end
        $pos = mb_strrpos(mb_substr($str, 0, $limit), ' ');
        if (false !== $pos) {
            $str = mb_substr($str, 0, $pos) . $end;
        }

        return ($html == TRUE) ? html::closeTags($str) : $str;
    }

    /**
     * Return a certain number of char in string.
     * This function does not cut words.
     *
     * @param string 		$str
     * @param int			$limit
     * @param string		$end string to attach at the end of paragraph when reduced
     * @param int           $start
     * @param bool			$html if string is html or pure text
     * @return string
     */
    public static function limitLine($str, $limit, $end = '', $start = 0, $html = false) {
        $start = $start > 0 ? $start : 0;

        $separ = $html ? '<br/>' : "\n";

        $array = explode($separ, $str);
        $array = array_slice($array, $start);
        $line_count = count($array);

        $str = (($line_count > $limit) ? implode($separ, array_slice($array, 0, $limit)) . $end : $str);
        return $str;
    }

    /**
     * Find a string and return it with words after and before
     * @param string $text full text
     * @param string $str string to find
     * @param int $before number of char to return before
     * @param int $after number of char to return after
     * @param string $method used
     */
    public static function findStrLen($text, $str, $before = 100, $after = 100, $func = 'strpos') {
        $content = '';
        $qtext = self::normalize($text, 'strtolower');
        $qstr = self::normalize($str, 'strtolower');
        if (($pos = $func($qtext, $qstr)) !== false) {
            $content = self::limitLen2($text, $after + $before + mb_strlen($str), '...', $pos - $before);
            $content = self::emphasis($content, $str);
        }

        return $content;
    }

    /**
     * Put emphase on $str
     */
    public static function emphasis($text, $str, $class = '', $tag = 'em') {
        $text = str_ireplace($str, '<' . $tag . ($class ? ' class="' . $class . '"' : '') . '>' . $str . '</' . $tag . '>', $text);
        return $text;
    }

    /**
     * Clean phone number string
     * @param string $str
     */
    public static function cleanPhone($str) {
        $str = str_replace(array('(', ')', '-', '.', ' ', '_', '#', '+'), array('', '', '', '', '', '', '', ''), $str);
        return $str;
    }

    /**
     * Clean zip code string
     * @param string $str
     */
    public static function cleanZipCode($str) {
        $str = str_replace(array('(', ')', '-', '.', ' ', '_'), array('', '', '', '', '', ''), $str);
        return $str;
    }

    /**
     * Clean time string
     */
    public static function cleanTime($str) {
        $str = preg_replace('/[^0-9]/', '', $str);
        return $str;
    }

    /**
     * Clean slug string
     */
    public static function cleanSlug($str, $separ) {
        return url::slug($str, $separ);
    }

    public static function cleanNumber($str) {
        $str = preg_replace('/[^0-9]/', '', $str);

        return $str;
    }

    /**
     * Turn all emails into mailto-link
     * @param string $str
     */
    public static function mailto($str) {
        $str = preg_replace('/([._a-z0-9-]+[._a-z0-9-]*)@(([a-z0-9-]+\.)*([a-z0-9-]+)(\.[a-z]{2,4}))/', '<a href="mailto:$0">$0</a>', $str);
        return $str;
    }

    /**
     * Turn all URLs into links
     * @param string $str
     */
    public static function link($str, $attr = []) {
        $pattern = "/(?![^<>]*>)(https?:\/\/[^\s\n\r<\"']*)/i";
        $replace = '<a href="$1" ' . html::attr($attr) . '>$1</a>';
        $str = preg_replace($pattern, $replace, $str);

        return $str;
    }

    /**
     * Transform dashes into <lu> <li> form
     * @param string $str should not contain any html tags in dashes
     * @param array $attr
     */
    public static function dash2li($str, $ul_attr = []) {
        // Dash to li
        $str = preg_replace('/^-\s?([^\n<]+)(\n|<br\/?>)?/ium', '<li>$1</li>', $str);

        // wrap <li> with <ul>
        $str = preg_replace('/(<li>[^<]+<\/li>\n?)+/iu', '<ul ' . html::attr($ul_attr) . '>$0</ul>', $str);

        return $str;
    }

    /**
     * Normalize a string by removing accents and maj
     * @param string $str
     * @param string $filter @deprecated
     */
    public static function normalize($str, $filter = null) {
        $str = trim($str);
        $str = $filter ? $filter($str) : $str;

        $search = array('À', 'Á', 'Â', 'Å', 'Ã', 'Ä', 'à', 'á', 'â', 'ã', 'ä', 'å',
            'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ø', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø',
            'È', 'É', 'Ê', 'Ë', 'è', 'é', 'ê', 'ë', 'Ç', 'ç',
            'Ì', 'Í', 'Î', 'Ï', 'ì', 'í', 'î', 'ï',
            'Ù', 'Ú', 'Û', 'Ü', 'ù', 'ú', 'û', 'ü',
            'Ÿ', 'ÿ', 'Ñ', 'ñ');
        $replace = array('A', 'A', 'A', 'A', 'A', 'A', 'a', 'a', 'a', 'a', 'a', 'a',
            'O', 'O', 'O', 'O', 'O', 'O', 'o', 'o', 'o', 'o', 'o', 'o',
            'E', 'E', 'E', 'E', 'e', 'e', 'e', 'e', 'C', 'c',
            'I', 'I', 'I', 'I', 'i', 'i', 'i', 'i',
            'U', 'U', 'U', 'U', 'u', 'u', 'u', 'u',
            'Y', 'y', 'N', 'n');

        $str = str_replace($search, $replace, $str);

        return $str;
    }

    public static function toUtf8($str) {
        if (!mb_detect_encoding($str, 'UTF-8', true))
            $str = utf8_encode($str);

        return $str;
    }

    /**
     * Transform search engine expression to words
     * @param string $expression
     */
    public static function expression2words($str) {
        $words = preg_split("/[\s,]*\\\"([^\\\"]+)\\\"[\s,]*|" . "[\s,]*'([^']+)'[\s,]*|" . "[\s,]+/", $str, 0, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

        return $words;
    }

    /**
     * Return words array from str
     */
    public static function str2words($str) {
        $words = preg_split('/[\s,]+/', $str);

        return $words;
    }

    /**
     * Return sentences array from string
     */
    public static function str2sentences($str) {
        $sentences = preg_split('/[\.|\n|\?|!]+/', $str);

        return $sentences;
    }

    /**
     * Strips new lines char in a string
     * @param string $str string to be striped
     * @param string $replace string that will replace
     * @return string with no line break
     */
    public static function stripEOL($str, $replace = '') {
        $str = strtr($str, array("\n" => $replace, "\r\n" => $replace));

        return $str;
    }

    /**
     * Check if text has html in it
     * @param string $str
     */
    public static function hasHtml($str) {
        if (mb_strlen($str) != mb_strlen(strip_tags($str)))
            return true;

        return false;
    }

    /**
     * Makes a phrase camel case.
     * @param string phrase to camelize
     * @return string
     */
    public static function camelize($str) {
        $str = 'x' . strtolower(trim($str));
        $str = ucwords(preg_replace('/[\s_]+/', ' ', $str));

        return substr(str_replace(' ', '', $str), 1);
    }

    /**
     * Makes a phrase underscored instead of spaced.
     * @param string phrase to underscore
     * @return string
     */
    public static function underscore($str) {
        return preg_replace('/\s+/', '_', trim($str));
    }

    /**
     * Makes an underscored or dashed phrase human-reable.
     * @param string phrase to make human-reable
     * @return string
     */
    public static function humanize($str) {
        return preg_replace('/[_-]+/', ' ', trim($str));
    }

    /**
     * Clean double space
     */
    public static function cleanSpaces($str) {
        return preg_replace('/\s+/', ' ', $str);
    }

    /**
     * Return a string depending of argument length
     * @param string $str text
     * @param Array $values associate array: array(18 => 'small', 50 => 'medium')
     */
    public static function valFromStrLen($str, $values) {
        $textlen = mb_strlen($str);
        krsort($values);
        foreach ($values as $len => $val) {
            if ($textlen >= $len)
                return $val;
        }

        return '';
    }

    /**
     * Multibytes str_pad
     * Made possible by comparing strlen vs mb_strlen
     */
    public static function str_pad($input, $pad_length, $pad_string = ' ', $pad_type = STR_PAD_RIGHT) {
        $diff = strlen($input) - mb_strlen($input);

        return str_pad($input, $pad_length + $diff, $pad_string, $pad_type);
    }

    public static function startWithVowel($str) {
        $search = array('a', 'à', 'á', 'â', 'ã', 'ä', 'å',
            'o', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø',
            'e', 'è', 'é', 'ê', 'ë',
            'i', 'ì', 'í', 'î', 'ï',
            'u', 'ù', 'ú', 'û', 'ü',
            'Ÿ', 'ÿ');

        return in_array(mb_strtolower(mb_substr($str, 0, 1)), $search);
    }

}
