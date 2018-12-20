<?php

namespace Eckinox\Nex;

/**
 * @author       Mikael Laforge <mikael.laforge@twikiconcept.com>
 * @version      1.0
 * @package      S.a.S
 * @subpackage   core
 * @copyright    Copyright (c) 2010, Twiki Concept (www.twikiconcept.com)
 *
 * @update
 *
 * 21/01/2010
 * Class to get page ranks with google
 * WARNING: Use this class smartly because google can ban IPs if you look like a bot too much
 * Result can be slow using this class, set timer config to your preferences
 * We use url::get_urlContent() This method try to use 'curl' librairie if its available to create a human like call.
 * Will use file_get_content() otherwise
 */

abstract class google {

    // Url being analysed
    protected $url = null;
    // Google domain - com | ca | fr ...
    protected $google_domain = 'com';
    // Google lang
    protected $google_lang = 'fr';
    // Content received from request to google
    protected $content = null;
    // Internal sleep timer to be a bit more human like
    protected $sleep = 3; // Seconds
    // Page rank
    public $page_rank = null;
    // Position
    public $position = null;
    // Real position
    public $real_position = null;
    // Nbr of page indexed
    public $nbr_indexed = null;

    /**
     * Static constructor for chaining
     * @param string $domain google domain ( com | ca | fr )
     * @param string $lang lang used. fr | en   etc
     */
    public static function factory($domain = 'com', $lang = 'fr') {
        return new self($domain, $lang);
    }

    /**
     * Constructor.
     * @param string $domain google domain ( com | ca | fr )
     * @param string $lang lang used. fr | en   etc
     */
    public function __construct($domain = 'com', $lang = 'fr') {
        $this->google_domain = $domain;
        $this->google_lang = $lang;
    }

    /**
     * Get number of pages indexed by google
     * @param string $url The site URL to check for indexed pages
     * @return int
     */
    public function nbrIndexed($url) {
        $url = "http://www.google." . $this->google_domain . "/search?hl=" . $this->google_lang . "&q=site%3A" . $url . "&filter=0";
        $remote = new remote();
        $page = $remote->get_content($url);

        // This may need to change if google change its html
        // Made 21/01/2010
        preg_match("/<p id=[\"']?resultStats[\"']?>.+(<b>[0-9]+<\/b>.+){2}<b>([&nbsp;0-9]+)<\/b>/iU", $page, $parse); // Ungreedy 25/01/2010
        $this->nbrIndexed = isset($parse[2]) ? (int) str_replace('&nbsp;', '', $parse[2]) : null;
        return $this->nbrIndexed;
    }

    /**
     * Process with page rank calculation
     * @param string $url The site URL to check for PageRank
     */
    public function pageRank($url) {
        // Calculated variables
        $info = 'info:' . urldecode($url);
        $checksum = $this->checksum($this->strToASCII($info));
        $remote = new remote();

        $full_url = "http://www.google.com/search?client=navclient-auto&ch=6$checksum&features=Rank&q=$info";

        // Get content
        $this->content = trim($remote->get_content($full_url));

        // Parse results
        preg_match('/Rank_[0-9]:[0-9]:(.*)/', $this->content, $parse);
        if (!isset($parse[1])) {
            $this->page_rank = 0;
            return false;
        } else {
            $this->page_rank = $parse[1];
            return $this->page_rank;
        }
    }

    /**
     * Get position of a site in google result when looking
     * for a given query
     *
     * @param string $url The site URL to check for position
     * @param string $query any query like "Cats" or "Html 5"
     * @param int $per_page number of result per page
     * @param int $max maximum number of result that will be checked
     *
     * @version 1.0
     */
    public function position($url, $query, $per_page = 10, $max = 100) {
        // Important because query can take longer then default 30 seconds
        @set_time_limit(0);

        $query = str_replace(" ", "+", $query);
        $query = urlencode($query);
        $query = str_replace("%26", "&", $query);

        $remote = new remote();

        if (!$search_domain = url::domain($url)) {
            return false;
        }

        $found = false;
        $lastDomain = null;
        $position = 0;
        $real_position = 0;

        for ($i = 0; $i < $max && $found == false; $i += $per_page) {
            $url = "http://www.google." . $this->google_domain . "/search?hl=" . $this->google_lang .
                    "&ie=UTF-8&btnG=Rechercher&q=" . $query . "&start=$i&num=$per_page";

            if (!$content = $remote->get_content($url)) {
                return false;
            }

            // Separate natural result and commercial
            $content = explode('<div id=res', $content);
            $content = isset($content[1]) ? $content[1] : '';

            // Made 30/01/2010 - might need to be changed if google change its html
            preg_match_all("/<cite>(.*)<\/cite>/U", $content, $parse, PREG_PATTERN_ORDER);

            foreach ($parse[0] as $line) {
                $position++;

                // Strip <B> tags
                $str = strtolower(strip_tags($line));

                // Domain is right before the first / (slash)
                $pos = strpos($str, "/");
                $domain = substr($str, 0, $pos);

                // If the last result process is the same as this one, it
                // is a nest or internal domain result, so don't count it
                // on $real_position
                if (strcmp($lastDomain, $domain) <> 0)
                    $real_position++;

                $lastDomain = $domain;

                // Found it
                if (stripos($domain, $search_domain) !== false) {
                    $this->position = $position;
                    $this->real_position = $real_position;
                    $found = true;
                    return $this->real_position;
                    break;
                }
            }

            // We don't want to spam
            sleep($this->sleep);
        }

        return $this->real_position;
    }

    /**
     * This function get images from google
     * by passing key words ($query)
     * @param string $query key words
     * @param int $page page to look
     * @param int $position position of the image we want to get
     * @param bool $safe google safe param
     *
     * @todo TEST IT
     */
    public function image($query, $page = 1, $position = 1, $safe = false) {
        // Important because query can take longer then default 30 seconds
        @set_time_limit(0);

        $remote = new remote();

        $url = sprintf("http://images.google.%s/images?q=%s&gbv=2&start=%d&hl=%s&ie=UTF-8&safe=%s&sa=N", $this->google_domain, urlencode($query), $page * $per_page, $this->google_lang, ($safe == false ? 'off' : 'on')
        );

        if (!$content = $remote->get_content($url)) {
            return false;
        }

        if (!preg_match_all('/dyn.Img\((.+)\);/Uis', $html, $matches, PREG_SET_ORDER))
            return [];

        $results = [];
        foreach ($matches as $match) {
            if (!preg_match_all('/"([^"]*)",/i', $match[1], $parts))
                continue;
            if (!preg_match('/(.+?)&h=(\d+)&w=(\d+)&sz=(\d+)&hl=[^&]*&start=(\d+)(?:.*)/', $parts[1][0], $url_parts))
                continue;

            $refUrl = urldecode($url_parts[1]);
            $height = intval($url_parts[2]);
            $width = intval($url_parts[3]);
            $rank = intval($url_parts[5]);

            //check if we've already passed the last page of results
            if ($rank < ($page * $perpage + 1))
                break;

            $imgUrl = urldecode($parts[1][3]);
            $refDomain = $parts[1][11];
            $imgText = $parts[1][6];
            $imgText = preg_replace('/\\\x(\w\w)/', '&#x\1;', $imgText);
            $imgText = strip_tags(html_entity_decode($imgText));
            $thumbUrl = $parts[1][14] . '?q=tbn:' . $parts[1][2] . $imgUrl;

            $one_result = array(
                'Rank' => $rank,
                'RefUrl' => $refUrl,
                'ImgText' => $imgText,
                'ImgUrl' => $imgUrl,
                'Height' => $height,
                'Width' => $width,
                'Host' => $refDomain,
                'ThumbUrl' => $thumbUrl,
            );
            array_push($results, $one_result);
        }
        return $results;
    }

    /**
     * Set sleep timer
     * @param int $timer seconds
     */
    public function set_sleep($timer) {
        $this->sleep = (int) $timer;
    }

    //
    //
	// Private functions
    // -------------------------------------------------------------------------

    /**
     * Converts number to int 32
     * (Required for pagerank hash)
     */
    private function int32(&$x) {
        $z = hexdec(80000000);
        $y = (int) $x;
        if ($y == - $z && $x < - $z) {
            $y = (int) ((-1) * $x);
            $y = (-1) * $y;
        }
        $x = $y;
    }

    /**
     * Fills in zeros on a number
     * (Required for pagerank hash)
     */
    private function zeroFill($a, $b) {
        $z = hexdec(80000000);
        if ($z & $a) {
            $a = ($a >> 1);
            $a &= (~$z);
            $a |= 0x40000000;
            $a = ($a >> ($b - 1));
        } else {
            $a = ($a >> $b);
        }
        return $a;
    }

    /**
     * Pagerank hash prerequisites
     */
    private function mix($a, $b, $c) {
        $a -= $b;
        $a -= $c;
        $this->int32($a);
        $a = (int) ($a ^ ($this->zeroFill($c, 13)));
        $b -= $c;
        $b -= $a;
        $this->int32($b);
        $b = (int) ($b ^ ($a << 8));
        $c -= $a;
        $c -= $b;
        $this->int32($c);
        $c = (int) ($c ^ ($this->zeroFill($b, 13)));
        $a -= $b;
        $a -= $c;
        $this->int32($a);
        $a = (int) ($a ^ ($this->zeroFill($c, 12)));
        $b -= $c;
        $b -= $a;
        $this->int32($b);
        $b = (int) ($b ^ ($a << 16));
        $c -= $a;
        $c -= $b;
        $this->int32($c);
        $c = (int) ($c ^ ($this->zeroFill($b, 5)));
        $a -= $b;
        $a -= $c;
        $this->int32($a);
        $a = (int) ($a ^ ($this->zeroFill($c, 3)));
        $b -= $c;
        $b -= $a;
        $this->int32($b);
        $b = (int) ($b ^ ($a << 10));
        $c -= $a;
        $c -= $b;
        $this->int32($c);
        $c = (int) ($c ^ ($this->zeroFill($b, 15)));

        return array($a, $b, $c);
    }

    /**
     * Pagerank checksum hash emulator
     */
    private function checksum($url, $length = null, $init = 0xE6359A60) {
        if (is_null($length)) {
            $length = sizeof($url);
        }
        $a = $b = 0x9E3779B9;
        $c = $init;
        $k = 0;
        $len = $length;
        while ($len >= 12) {
            $a += ($url[$k + 0] + ($url[$k + 1] << 8) + ($url[$k + 2] << 16) + ($url[$k + 3] << 24));
            $b += ($url[$k + 4] + ($url[$k + 5] << 8) + ($url[$k + 6] << 16) + ($url[$k + 7] << 24));
            $c += ($url[$k + 8] + ($url[$k + 9] << 8) + ($url[$k + 10] << 16) + ($url[$k + 11] << 24));
            $mix = $this->mix($a, $b, $c);
            $a = $mix[0];
            $b = $mix[1];
            $c = $mix[2];
            $k += 12;
            $len -= 12;
        }
        $c += $length;
        switch ($len) {
            case 11: $c += ($url[$k + 10] << 24);
            case 10: $c += ($url[$k + 9] << 16);
            case 9: $c += ($url[$k + 8] << 8);
            case 8: $b += ($url[$k + 7] << 24);
            case 7: $b += ($url[$k + 6] << 16);
            case 6: $b += ($url[$k + 5] << 8);
            case 5: $b += ($url[$k + 4]);
            case 4: $a += ($url[$k + 3] << 24);
            case 3: $a += ($url[$k + 2] << 16);
            case 2: $a += ($url[$k + 1] << 8);
            case 1: $a += ($url[$k + 0]);
        }
        $mix = $this->mix($a, $b, $c);
        return $mix[2];
    }

    /**
     * ASCII conversion of a string
     *
     * @param string $str string to convert
     */
    private function strToASCII($str) {
        for ($i = 0; $i < strlen($str); $i++) {
            $result[$i] = ord($str{$i});
        }
        return $result;
    }

    /**
     * Number formatting to use with pagerank hash
     */
    private function formatNumber($number = '', $divchar = ',', $divat = 3) {
        $decimals = '';
        $formatted = '';

        if (strstr($number, '.')) {
            $pieces = explode('.', $number);
            $number = $pieces[0];
            $decimals = '.' . $pieces[1];
        } else {
            $number = (string) $number;
        }

        if (strlen($number) <= $divat)
            return $number;

        $j = 0;
        for ($i = strlen($number) - 1; $i >= 0; $i--) {
            if ($j == $divat) {
                $formatted = $divchar . $formatted;
                $j = 0;
            }
            $formatted = $number[$i] . $formatted;
            $j++;
        }

        return $formatted . $decimals;
    }

}
