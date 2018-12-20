<?php

namespace Eckinox\Nex;

/*
 * The MIT License
 *
 * Copyright 2015 Dave Mc Nicoll.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

class Stringobj {

    public static $booltrue = array("true", "1", "yes", "oui");
    public static $boolfalse = array("false", "0", "no", "oui");
    protected $encoding;
    protected $content;

    public static function create($content, $encoding = null) {
        return new self($content, $encoding);
    }

    public function __construct($content, $encoding = null) {
        // If we are given another string object, we ask for the content only
        $this->content = (is_object($content) && get_class($content) == "string") ? $content->toString() : $content;

        if (!$encoding) {
            $this->encoding = mb_internal_encoding();
        } else {
            $this->set_encoding($encoding);
        }
    }

    public function copy() {
        return new self($this->content, $this->encoding);
    }

    public function toString() {
        return $this->__toString();
    }

    public function __toString() {
        return $this->content ?: "";
    }

    public function set_encoding($encoding) {
        $this->encoding = $encoding;
    }

    public function implode($delimiter, $source) {
        $this->content = implode($delimiter, $source);
        return $this;
    }

    public function explode($delimiter, $limit = null) {
        return explode($delimiter, $this->content, $limit);
    }

    public function reencode($encoding) {
        $this->content = mb_convert_encoding($this->content, $encoding, $this->encoding);
    }

    public function trim($char_mask = " \t\n\r\0\x0B") {
        $this->content = trim($this->content, $char_mask);
        return $this;
    }

    public function ltrim($char_mask = " \t\n\r\0\x0B") {
        $this->content = ltrim($this->content, $char_mask);
        return $this;
    }

    public function rtrim($char_mask = " \t\n\r\0\x0B") {
        $this->content = rtrim($this->content, $char_mask);
        return $this;
    }

    public function str_replace($search, $replace, $count = null) {
        $this->content = str_ireplace($search, $replace, $this->content, $count);
        return $this;
    }

    public function strtolower() {
        $this->content = mb_convert_case($this->content, MB_CASE_LOWER, $this->encoding);
        return $this;
    }

    public function strtoupper() {
        $this->content = mb_convert_case($this->content, MB_CASE_UPPER, $this->encoding);
        return $this;
    }

    public function append($str) {
        $this->content .= $str;
        return $this;
    }

    public function prepend($str) {
        $this->content = $str . $this->content;
        return $this;
    }

    public function capitalize() {
        $this->content = mb_convert_case($this->content, MB_CASE_TITLE, $this->encoding);
        return $this;
    }

    public function ucfirst_only() {
        $this->content = $this->strtolower()->ucfirst()->toString();
        return $this;
    }

    public function ucfirst() {
        $this->content = mb_strtoupper(mb_substr($this->content, 0, 1)) . mb_substr($this->content, 1);
        return $this;
    }

    public function contains($str) {
        return $this->search($str) !== 0;
    }

    public function strcasecmp($str) {
        return $this->strtoupper()->toString() === self::create($str)->strtoupper()->toString();
    }

    #public function strcmp($str) {
    #    return $this->uppercase()->toString() === self::create($str)->uppercase()->toString();
    #}

    public function strcmp($str) {
        return strcmp(iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $this->content), iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $str));
    }

    public static function str_pad($pad_length, $pad_string = ' ', $pad_type = STR_PAD_RIGHT) {
        #$diff = strlen($this->content) - mb_strlen($this->content);
        $this->content = str_pad($this->content, $pad_length/* +$diff */, $pad_string, $pad_type);
        return $this;
    }

    public function search($str, $offset = 0) {
        if (($retval = mb_stripos($this->content, $str, $offset, $this->encoding)) === false) {
            return 0;
        } else {
            return $retval;
        }
    }

    public function search_reversed($str, $offset) {
        if (($retval = mb_strripos($this->content, $str, $offset, $this->encoding)) === false) {
            return 0;
        } else {
            return $retval;
        }
    }

    public function strafter($search) {
        return mb_stristr($this->content, $search, false, $this->encoding);
    }

    public function strbefore($search) {
        return mb_stristr($this->content, $search, true, $this->encoding);
    }

    public function reversed_strafter($needle, $start = 0) {
        return mb_substr($this->content, mb_strripos($this->content, $needle, $start, $this->encoding) + 1, $this->length(), $this->encoding);
    }

    public function reversed_strbefore($needle, $start = 0) {
        return mb_substr($this->content, 0, mb_strripos($this->content, $needle, $start, $this->encoding), $this->length(), $this->encoding);
    }

    public function length() {
        return $this->strlen();
    }

    public function strlen() {
        return mb_strlen($this->content, $this->encoding);
    }

    public function split($pattern, $limit = -1) {
        return mb_split($pattern, $this->content, $limit);
    }

    public function splitClass($pattern, $limit = -1) {
        $retval = mb_split($pattern, $this->content, $limit);

        foreach ($retval as &$item) {
            $item = self::create($item);
        }

        return $retval;
    }

    public function substr($start, $length = null) {
        return mb_substr($this->content, $start, $length, $this->encoding);
    }

    public function count_occurence($search) {
        return mb_substr_count($this->content, $search, $this->encoding);
    }

    public function get_between($leftpart, $rightpart, $leftoffset = 0, $rightoffset = 0) {
        $pos_s = mb_stripos($this->content, $leftpart, $leftoffset, $this->encoding) + 1;
        return mb_substr($this->content, $pos_s, mb_stripos($this->content, $rightpart, $rightoffset, $this->encoding) - $pos_s, $this->encoding);
    }

    public function get_betweenrev($leftpart, $rightpart, $leftoffset = 0, $rightoffset = 0) {
        $pos_s = mb_stripos($this->content, $leftpart, $leftoffset, $this->encoding) + 1;
        return mb_substr($this->content, $pos_s, mb_strripos($this->content, $rightpart, $rightoffset) - $pos_s, $this->encoding);
    }

    public function striphtml($allowedtags = null) {
        $this->content = strip_tags($this->content, $allowedtags);
        return $this;
    }

    public function startsWith($needle) {
        return ($needle === "") || strpos($this->content, $needle) === 0;
    }

    public function endsWith($needle) {
        return ($needle === "") || $this->substr(-strlen($needle)) === $needle;
    }

    public function mustEndsWith($needle) {
        return $this->endsWith($needle) ? $this : $this->append($needle);
    }

    public function mustStartsWith($needle) {
        return $this->startsWith($needle) ? $this : $this->prepend($needle);
    }

    public function isCapitalized() {
        return $this->content == $this->_capitalize();
    }

    public function firstCharIs($char) {
        if ($this->length() >= 1) {
            return $this->substring(0, 1) === $char;
        } else {
            return false;
        }
    }

    public function lastCharIs($char) {
        $len = $this->length();

        if ($len) {
            return $this->substring($len - 1, 1) === $char;
        } else {
            return false;
        }
    }

}
