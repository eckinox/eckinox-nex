<?php

namespace Eckinox\Nex;

/**
 * @author       Mikael Laforge <mikael.laforge@gmail.com>
 * @version      1.0.5
 * @package      Nex
 * @subpackage   core
 *
 * @update (10/03/2011) [mikael.laforge@gmail.com] - 1.0.1 - Updated to use nex_strftime() in most base literal methods
 * @update (27/07/2012) [ML] - 1.0.2 - bugfix in literalHour() method
 * @update (24/07/2013) [ML] - 1.0.3 - improved dateToTimestamp() and timestampToDate() to work with string timestamp
 * @update (23/04/2014) [ML] - 1.0.4 - bugfix in literalElapsed
 * @update (20/06/2014) [ML] - 1.0.5 - Added setOutputTZ(), date() and shiftTZ() methods
 * @update (14/09/2015) [EB] - 1.0.6 - Fixed date format in other languages.
 *
 * 17/09/2009
 * This class was made to help with dates format
 */
abstract class date {

    protected static $output_tz = null;

    public static function setOutputTZ($tz) {
        self::$output_tz = $tz;
    }

    public static function date($format, $ts = null) {
        return date($format, self::shiftTZ($ts));
    }

    /**
     * Return current unix date/time
     * commonly used in librairies.
     * Its just a shortcut
     * @param int $sec sec to add to time
     */
    public static function dateTime($sec = 0) {
        return date('Y-m-d H:i:s', time() + $sec);
    }

    /**
     * Create timestamp from a Date/time full format (YYYY-MM-DD HH:ii:ss)
     * @param string $date_time full date/time.
     * @return int
     */
    public static function dateToTimestamp($date_time) {
        if (self::isTimestamp($date_time)) {
            return (int) $date_time;
        } else {
            if ($date = date_create($date_time)) {
                return $date->format('U');
            }
        }

        return null;
    }

    /**
     * Create a dateTime from a date or timestamp
     * @param string|int $timestamp timestamp or date (YYYY-MM-DD HH:ii:ss)
     * @param bool $format
     * @return int
     */
    public static function timestampToDate($timestamp, $format = 'Y-m-d H:i:s') {
        if (self::isTimestamp($timestamp)) {
            return date($format, $timestamp);
        } else {
            if ($date = date_create($timestamp)) {
                return $date->format($format);
            }
        }

        return null;
    }

    /**
     * Return a full literal date/time
     * @param date|timestamp $date - date/time or timestamp
     * @param bool $short - short version or long.
     * @param bool $day_week Display day of week or not.
     * @return string
     */
    public static function literalDateTime($date, $short = false, $day_week = false) {
        return self::literalDate($date, $short, $day_week) . ' ' . \Eckinox\Language::get('date.at') . ' ' . self::literalHour($date);
    }

    /**
     * Return literal format date (DD mmmm YYYY).
     * @param date|timestamp $ts - date (YYYY-MM-DD) or timestamp.
     * @param bool $short - short version or long.
     * @param bool $day_week Display day of week or not.
     * @return string
     */
    public static function literalDate($ts, $short = false, $day_week = false) {
        $ts = self::dateToTimestamp($ts);

        //Init var
        $fulldate = "";

        // If we add week day
        if ($day_week == true) {
            $fulldate = self::dayOfWeek($ts, $short) . ", ";
        }

        // Build complete date
        //$fulldate .= self::literalDay($ts)." ".self::literalMonth($ts, $short)." ".self::literalYear($ts, $short);
        $fulldate .= sprintf(\Eckinox\Language::get('date.literalDate'), self::literalDay($ts), self::literalMonth($ts, $short), self::literalYear($ts, $short));

        return $fulldate;
    }

    /**
     * Return date/time to format 00h00.
     * @param date|timestamp $ts date YYYY-MM-DD HH:ii or time HH:mm or timestamp.
     * @return string
     */
    public static function literalHour($date) {
        // If complete date, keep time only
        if (strpos($date, " ") !== false) {
            list(, $date) = explode(" ", $date);
        }

        // If time
        if (strpos($date, ":") !== false) {
            $d = explode(":", $date);
            return sprintf("%2dh%02d", $d[0], $d[1]);
        }
        // If timestamp
        elseif (is_numeric($date) === TRUE) {
            return date("G\hi", $date);
        }
        // Invalid
        else {
            return false;
        }
    }

    /**
     * Return week day's name for a date YYYY-MM-DD|timestamp given
     * @param string $date Complete date.
     * @param bool $short - Short version or long version
     * @return string
     */
    public static function dayOfWeek($date, $short = false) {
        //Execute if date is not a timestamp
        if ($date >= 0 && $date <= 7) {
            $weekday = $date;
        } elseif (!self::isTimestamp($date)) {
            $weekday = date("w", self::dateToTimestamp($date));
        } else {
            $weekday = date("w", $date);
        }

        //Retourne la valeur au format litéraire
        if ($short == false) {
            switch ($weekday) {
                case 7:
                case 0: return \Eckinox\Language::get('date.sun');
                case 1: return \Eckinox\Language::get('date.mon');
                case 2: return \Eckinox\Language::get('date.tues');
                case 3: return \Eckinox\Language::get('date.wed');
                case 4: return \Eckinox\Language::get('date.thur');
                case 5: return \Eckinox\Language::get('date.fri');
                case 6: return \Eckinox\Language::get('date.sat');
            }
        } else {
            switch ($weekday) {
                case 7:
                case 0: return \Eckinox\Language::get('date.s:sun');
                case 1: return \Eckinox\Language::get('date.s:mon');
                case 2: return \Eckinox\Language::get('date.s:tues');
                case 3: return \Eckinox\Language::get('date.s:wed');
                case 4: return \Eckinox\Language::get('date.s:thur');
                case 5: return \Eckinox\Language::get('date.s:fri');
                case 6: return \Eckinox\Language::get('date.s:sat');
            }
        }

        return false;
    }

    /**
     * Return literal day from numeric. In fact, it removes useless zero.
     * @param int|string $date day to convert.
     * @return string
     */
    public static function literalDay($date) {
        if (is_string($date)) {
            $ts = self::dateToTimestamp($date);
        } elseif ($date >= 01 && $date <= 31) {
            $ts = mktime(0, 0, 0, 1, $date, 2000);
        } else {
            $ts = $date;
        }

        $day = nex_strftime('%d', $ts);

        // Remove first zero when $day is lower then 10
        if ($day < 10) {
            if (isset($day{1})) {
                $conv = $day{1};
                return $conv;
            }
        }

        return $day;
    }

    /**
     * Create literal month from numeric.
     * @param int|string $date Datetime|timestamp.
     * @param bool $short Return short version of month or long.
     * @return string
     */
    public static function literalMonth($date, $short = false) {
        if (is_string($date)) {
            $ts = self::dateToTimestamp($date);
        } elseif ($date >= 01 && $date <= 12) {
            $ts = mktime(0, 0, 0, $date, 1, 2000);
        } else {
            $ts = $date;
        }

        $month = date('n', $ts);

        // Check if we return short or long version
        if ($short == true) {
            switch ($month) {
                case 1: return \Eckinox\Language::get('date.s:jan');
                case 2: return \Eckinox\Language::get('date.s:feb');
                case 3: return \Eckinox\Language::get('date.s:march');
                case 4: return \Eckinox\Language::get('date.s:april');
                case 5: return \Eckinox\Language::get('date.s:may');
                case 6: return \Eckinox\Language::get('date.s:june');
                case 7: return \Eckinox\Language::get('date.s:july');
                case 8: return \Eckinox\Language::get('date.s:aug');
                case 9: return \Eckinox\Language::get('date.s:sept');
                case 10: return \Eckinox\Language::get('date.s:oct');
                case 11: return \Eckinox\Language::get('date.s:nov');
                case 12: return \Eckinox\Language::get('date.s:dec');
            }
        } else {
            switch ($month) {
                case 1: return \Eckinox\Language::get('date.jan');
                case 2: return \Eckinox\Language::get('date.feb');
                case 3: return \Eckinox\Language::get('date.march');
                case 4: return \Eckinox\Language::get('date.april');
                case 5: return \Eckinox\Language::get('date.may');
                case 6: return \Eckinox\Language::get('date.june');
                case 7: return \Eckinox\Language::get('date.july');
                case 8: return \Eckinox\Language::get('date.aug');
                case 9: return \Eckinox\Language::get('date.sept');
                case 10: return \Eckinox\Language::get('date.oct');
                case 11: return \Eckinox\Language::get('date.nov');
                case 12: return \Eckinox\Language::get('date.dec');
            }
        }

        return false;
    }

    /**
     * Create literal year from timestamp|date
     * @param int|string $date
     * @param bool $short
     */
    public static function literalYear($date, $short = false) {
        if (is_string($date)) {
            $ts = self::dateToTimestamp($date);
        } else {
            $ts = $date;
        }

        // Check if we return short or long version
        if ($short == true) {
            $year = nex_strftime('%y', $ts);
        } else {
            $year = nex_strftime('%Y', $ts);
        }

        return $year;
    }

    /**
     * Return number of days elapsed between 2 timestamp
     * if second timestamp isnt given, time() will be taken
     * @param int $start
     * @param int $end
     */
    public static function elapsedDays($start, $end = null) {
        $end = ($end == null) ? time() : $end;

        $start = self::dateToTimestamp($start);
        $end = self::dateToTimestamp($end);

        return round(($end - $start) / 86400);
    }

    /**
     * Return time past in human readable format
     * if second timestamp isnt given, time() will be taken
     * @param int $start
     * @param int $end
     */
    public static function literalElapsed($start, $end = null) {
        $end = ($end == null) ? time() : $end;

        $time = self::dateToTimestamp($end) - self::dateToTimestamp($start);

        $tokens = array
            (
            31536000 => 'year',
            2592000 => 'month',
            604800 => 'week',
            86400 => 'day',
            3600 => 'hour',
            60 => 'minute',
            1 => 'second'
        );

        foreach ($tokens as $unit => $text) {
            if ($time < $unit)
                continue;

            $nbr_units = floor($time / $unit);

            $str = sprintf(\Eckinox\Language::get('date.agoUnit'), $nbr_units, inflector::plural(\Eckinox\Language::get('date.' . $text), $nbr_units));

            return $str;
        }
    }

    public static function isTimestamp($timestamp) {
        return is_numeric($timestamp) && $timestamp > 100000000;
    }

    public static function shiftTZ($date) {
        if (!$date)
            $date = date('Y-m-d H:i:s');

        if (self::$output_tz && self::$output_tz != ($sys_tz = date_default_timezone_get())) {
            $dtime = new DateTime($date, new DateTimeZone($sys_tz));
            $dtime->setTimeZone(new DateTimeZone(self::$output_tz));
            $date = $dtime->format('Y-m-d H:i:s');
        }

        return $date;
    }

    public static function date_range($begin, $end, $range, $include_end_date = true, $include_start_date = true) {
        $begin = new DateTime($begin);
        $end = new DateTime($end);

        if ($include_start_date) {
            $begin = $begin->modify('-1 day');
        }

        # include end date into range
        if ($include_end_date) {
            $end = $end->modify('+1 day');
        }

        $interval = new DateInterval($range);
        return new DatePeriod($begin, $interval, $end);
    }

}
