<?php

namespace Eckinox\Nex;

/**
 * @author       Mikael Laforge <mikael.laforge@twikiconcept.com>
 * @version      1.0.2
 * @package      Nex
 * @subpackage   core
 *
 * @update (26/07/2012) [ML] - 1.0.1 - Updated jsVal() method to escape single quotes. json_encode will be used if it exist or if php is version >= 5.3.0 when using options
 * @update (05/03/2013) [ML] - 1.0.2 - added minToHour() method
 * 									   added hexToRGB() method
 * @update (06/06/2014) [DM] - 1.0.3 - added longToRGB() method
 * 
 *
 * 17/10/2009
 * This class was made to help convert units and values.
 */

abstract class converter {

    const DPI_72_CM = 28.346456692913385826771653543307;

    /**
     * Convert 2 geo locations (latitude and longitude) to distance
     * @param array $geo1 require 'lat' and 'lng' key
     * @param array $geo2 require 'lat' and 'lng' key
     * @param string $unit 'm' or 'k' or 'n'
     */
    public static function geoToDistance($geo1, $geo2, $unit) {
        $theta = $geo1['lng'] - $geo2['lng'];
        $dist = sin(deg2rad($geo1['lat'])) * sin(deg2rad($geo2['lat'])) + cos(deg2rad($geo1['lat'])) * cos(deg2rad($geo2['lat'])) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        $unit = strtoupper($unit);

        if ($unit == "k") {
            return ($miles * 1.609344);
        } else if ($unit == "n") {
            return ($miles * 0.8684);
        } else {
            return $miles;
        }
    }

    public static function hexToRGB($hex) {
        $hex = str_replace("#", "", $hex);

        if (strlen($hex) == 3) {
            $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
            $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
            $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }

        return array($r, $g, $b);
    }

    public static function longToRGB($value) {
        return array(($value >> 16) & 0xFF, ($value >> 8) & 0xFF, $value & 0xFF);
    }

    /**
     * Convert centimeters to pixel using standard resolution which is 72 pixel/inch
     * This is based on web resolution
     *
     * @param float			$cm
     * @return int			pixels
     */
    public static function mmToPixel($cm) {
        return (int) (self::DPI_72_CM * $cm * 10);
    }

    public static function pixelToMm($pixel) {
        return (int) ($pixel / self::DPI_72_CM * 10);
    }

    public static function cmToPixel($cm) {
        return (int) (self::DPI_72_CM * $cm);
    }

    public static function pixelToCm($pixel) {
        return (int) ($pixel / self::DPI_72_CM);
    }

    /**
     * Convert inches to pixel using standard resolution which is 72 pixel/inch
     * This is based on web resolution
     *
     * @param float			$cm
     * @return int			pixels
     */
    public static function inchToPixel($inch, $dpi = 72) {
        return (int) ($dpi * $inch);
    }

    public static function pixelToInch($pixel, $dpi = 72) {
        return (int) ($pixel / $dpi);
    }

    public static function minToHour($minutes) {
        $hours = floor($minutes / 60);
        $minutes = $minutes % 60;
        $minutes = $minutes / 60;

        return $hours + $minutes;
    }

    /**
     * convert a string from one UTF-16 char to one UTF-8 char
     *
     * Normally should be handled by mb_convert_encoding, but
     * provides a slower PHP-only method for installations
     * that lack the multibye string extension.
     *
     * @param    string  $utf16  UTF-16 character
     * @return   string  UTF-8 character
     */
    public static function utf16ToUtf8($utf16) {
        // oh please oh please oh please oh please oh please
        if (function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($utf16, 'UTF-8', 'UTF-16');
        }

        $bytes = (ord($utf16{0}) << 8) | ord($utf16{1});

        switch (true) {
            case ((0x7F & $bytes) == $bytes):
                // this case should never be reached, because we are in ASCII range
                // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                return chr(0x7F & $bytes);

            case (0x07FF & $bytes) == $bytes:
                // return a 2-byte UTF-8 character
                // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                return chr(0xC0 | (($bytes >> 6) & 0x1F))
                        . chr(0x80 | ($bytes & 0x3F));

            case (0xFFFF & $bytes) == $bytes:
                // return a 3-byte UTF-8 character
                // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                return chr(0xE0 | (($bytes >> 12) & 0x0F))
                        . chr(0x80 | (($bytes >> 6) & 0x3F))
                        . chr(0x80 | ($bytes & 0x3F));
        }

        // ignoring UTF-32 for now, sorry
        return '';
    }

    /**
     * convert a string from one UTF-8 char to one UTF-16 char
     *
     * Normally should be handled by mb_convert_encoding, but
     * provides a slower PHP-only method for installations
     * that lack the multibye string extension.
     *
     * @param    string  $utf8   UTF-8 character
     * @return   string  UTF-16 character
     */
    public static function utf8ToUtf16($utf8) {
        // oh please oh please oh please oh please oh please
        if (function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($utf8, 'UTF-16', 'UTF-8');
        }

        switch (strlen($utf8)) {
            case 1:
                // this case should never be reached, because we are in ASCII range
                // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                return $utf8;

            case 2:
                // return a UTF-16 character from a 2-byte UTF-8 char
                // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                return chr(0x07 & (ord($utf8{0}) >> 2))
                        . chr((0xC0 & (ord($utf8{0}) << 6)) | (0x3F & ord($utf8{1})));

            case 3:
                // return a UTF-16 character from a 3-byte UTF-8 char
                // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                return chr((0xF0 & (ord($utf8{0}) << 4)) | (0x0F & (ord($utf8{1}) >> 2)))
                        . chr((0xC0 & (ord($utf8{1}) << 6)) | (0x7F & ord($utf8{2})));
        }

        // ignoring UTF-32 for now, sorry
        return '';
    }

    /**
     * Convert php value to Javascript value, to use when json_encode doesnt exist
     * @param mixed $val
     * @param int $options used only if json_encode and PHP version >= 5.3.0
     * @return string
     */
    public static function jsVal($val, $options = null) {
        // Use native php function if exists
        if (function_exists("json_encode")) {
            if ($options = null) {
                return json_encode($val);
            } elseif (version_compare(PHP_VERSION, '5.3.0', '>=')) {
                return json_encode($val, $options);
            }
        }

        if (is_null($val)) {
            return 'null';
        }
        if ($val === false) {
            return 'false';
        }
        if ($val === true) {
            return 'true';
        }

        if (is_scalar($val)) {
            if (is_float($val)) {
                // Always use "." for floats.
                $val = str_replace(",", ".", strval($val));
            }

            // Use @@ to not use quotes when outputting string value
            if (strpos($val, '@@') === 0) {
                return substr($val, 2);
            } else {
                // All scalars are converted to strings to avoid indeterminism.
                // PHP's "1" and 1 are equal for all PHP operators, but
                // JS's "1" and 1 are not. So if we pass "1" or 1 from the PHP backend,
                // we should get the same result in the JS frontend (string).
                // Character replacements for JSON.
                $toReplace = array("\\", "/", "\n", "\t", "\r", "\b", "\f", '"', "'");
                $replaceWith = array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"', '\\u0027');

                $val = str_replace($toReplace, $replaceWith, $val);

                return '"' . $val . '"';
            }
        }
        $isList = true;
        for ($i = 0, reset($val); $i < count($val); $i++, next($val)) {
            if (key($val) !== $i) {
                $isList = false;
                break;
            }
        }
        $result = [];
        if ($isList) {
            foreach ($val as $v)
                $result[] = self::jsVal($v);
            return '[ ' . join(', ', $result) . ' ]';
        } else {
            foreach ($val as $k => $v)
                $result[] = self::jsVal($k) . ': ' . self::jsVal($v);
            return '{ ' . join(', ', $result) . ' }';
        }
    }

    /**
     * Outputs a CSV file working in Excel
     * 
     * @param type $data        Array of data to convert
     * @param type $delimiter   Delimiter used to separate cells
     * @param type $enclosure   String enclosure
     * @param type $download    Force download. If set to false, will simply output to screen. 
     *                          If true, will output with current datetime stamp. If a string is given,
     *                          it's gonna be used as it's filename too.
     */
    public static function arrayToCsv($data, $delimiter = ';', $enclosure = '"', $download = true) {
        if ($download) {
            header("Content-type: text/csv");
            header("Content-Disposition: attachment; filename=" . ( is_string($download) ? $download : date('Y-m-d h-i-s') . ".csv" ));
            header("Pragma: no-cache");
            header("Expires: 0");
        }

        /* temp/maxmemory : will start output to file if data is > 5mb */
        $output = fopen($download ? "php://output" : "php://temp/maxmemory:5242880", $download ? "w" : "r+");
        fwrite($output, "\xEF\xBB\xBF"); // UTF-8 BOM

        foreach ($data as $row) {
            fputcsv($output, (array) $row, $delimiter ?: ';', $enclosure ?: '"');
        }

        if ($download) {
            fclose($output);
        } else {
            rewind($output);
            return stream_get_contents($output);
        }
    }
    
    public static function csvToArray($filename, $delimiter = null, $enclosure = '"', $header_as_key = false) {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', 0);
        ini_set('auto_detect_line_endings', true);
        
        # Detect delimiter automatically
        if (!$delimiter && file_exists($filename)) {
            $content = file_get_contents($filename);
            $excerpt = substr($content, 0, 1000);
            
            $possible_delimiters = array(',', ';');
            $delimiters_count = array();
            foreach ($possible_delimiters as $delimiter)
                $delimiters_count[$delimiter] = substr_count($excerpt, $delimiter);
            
            $detected_delimiter = array_keys($delimiters_count, max($delimiters_count));
            
            $delimiter = array_pop($detected_delimiter);
        }
        
        # Convert to array
        $array = $keys = array();
    
        if (is_file($filename) && ( ($handle = fopen($filename, 'r')) !== false )) {
            $line = 1;
    
            while (( ($value = fgetcsv($handle, 0, $delimiter, $enclosure))) !== false) {
    
                if ( $header_as_key && ( $line === 1 ) ) {
                    $keys = $value;
                    $value = [];
                }
    
                array_filter($value) && ($array[] = $value);
                $line++;
            }
    
            fclose($handle);
        }
    
        if ($header_as_key) {
            foreach($array as $item) {
                $retval[] = array_combine($keys, $item);
            }
        }
    
        return isset($retval) ? $retval : $array;
    }

}
