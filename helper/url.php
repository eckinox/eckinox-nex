<?php

namespace Eckinox\Nex;

use Eckinox\config;

abstract class url {
    use config;
    /**
     * Sends a page redirect header.
     * @param mixed $uri string site URI or URL to redirect to, or array of strings if method is 300
     * @param string $method HTTP method of redirect
     */
    public static function redirect($uri = '', $method = '302') {
        $uri = (array) $uri;

        // Make sure browser doesn't cache
        header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');

        // Fix session bug that happens with IE sometimes
        //session_write_close(); // Is handled by session close method

        $codes = array(
            '301' => 'Moved Permanently',
            '302' => 'Found',
            '303' => 'See Other',
            '304' => 'Not Modified',
            '305' => 'Use Proxy',
            '307' => 'Temporary Redirect'
        );

        if ($method == '300') {
            header('HTTP/1.1 300 Multiple Choices');
            header('Location: ' . $uri[0]);

            $choices = '';
            foreach ($uri as $href) {
                $choices .= '<li><a href="' . $href . '">' . $href . '</a></li>';
            }

            exit('<h1>301 - Multiple Choices:</h1><ul>' . $choices . '</ul>');
        } else {
            $uri = $uri[0];

            if ($method == 'refresh') {
                header('Refresh: 0; url=' . $uri);
                exit(0);
            } else {
                $method = isset($codes[$method]) ? $method : '302';

                header('HTTP/1.1 ' . $method . ' ' . $codes[$method]);
                header('Location: ' . $uri);
            }

            exit('<h1>' . $method . ' - ' . $codes[$method] . '</h1><p><a href="' . $uri . '">' . $uri . '</a></p>');
        }
    }

    /**
     * Refresh current route
     */
    public static function refresh() {
        static::redirect(static::current_url(), 'refresh');
    }

    public static function current_url() {
        return static::site(ltrim(Router::instance()->uri(), '/'));
    }

    /**
     * Create and return a full absolute url based on relative
     * @param string $url
     * @param bool $rewrite_rule take care of rewritting or not
     * @return string valid uri
     */
    public static function site($url = null, $rewrite_rule = true) {
        // Add index.php or not - url rewriting
#        $index = (static::config('Eckinox.system.url.rewrite') == false && $rewrite_rule == true && strpos($url, NEX . '/') === false && $url != NEX) ? NEX . '/' : '';
        $index = "";

        // If url is null, return current uri
        if ( $url === null ) {
            return static::site(ltrim(Router::instance()->uri(), '/'), $rewrite_rule);
        }

        $url = ($rewrite_rule == true) ? static::addExt($url) : $url;

        $url = ((strpos($url, '://') === false && substr($url, 0, 2) !== '//') ? static::site_root() . $index : '') . $url;

        return $url;
    }

    public static function relative($url = null, $rewrite_rule = true) {
        // Add index.php or not - url rewriting
        $index = (static::config('Eckinox.system.url.rewrite') == false && $rewrite_rule == true && strpos($url, NEX . '/') === false && $url != NEX) ? NEX . '/' : '';

        // If url is null, return current uri
        if ($url === null) {
            return static::site(Router::uri(), $rewrite_rule);
        }

        $url = ($rewrite_rule == true) ? static::addExt($url) : $url;

        $base = static::site_base();
        if (substr($url, 0, 7) !== 'http://' && substr($url, 0, 8) !== 'https://' && substr($url, 0, 2) !== '//' && substr($url, 0, strlen($base)) !== $base) {
            $url = $base . $index . $url;
        }

        return $url;
    }

    public static function resource($url) {
        $base = static::site_base();
        if (substr($url, 0, 7) !== 'http://' && substr($url, 0, 8) !== 'https://' && substr($url, 0, 2) !== '//' && substr($url, 0, strlen($base)) !== $base) {
            $url = $base . $url;
        }

        //$url = ((strpos($url, '://') === false && substr($url, 0, 2) !== '//') ? static::static_root() : '') . $url;

        return $url;
    }

    /**
     * Media url
     */
    public static function media($url) {
        return Nex::mediaUrl($url);
    }

    /**
     * Skin url
     * second argument should be the sub directory to use like "image/" or "css/"
     */
    public static function skin($url, $dir) {
        return Nex::skinUrl($dir . $url);
    }

    /**
     * Return base system url
     * @return string
     */
    public static function site_base() {
        return static::config('Eckinox.system.url.base');
    }

    /**
     * Return root system url
     * @return string
     */
    public static function site_root() {
        $root = Router::instance()->domain();

        # var_dump($root);

        if ( request::is_https() && static::config('Eckinox.system.url.force_https') ) {
            $root = 'https://'.$root;
        }
        else {
            $root = "//".$root;
        }

        return $root . ( substr($root, -1, 1) !== '/' ? '/' : '' );
    }

    /**
     * Return system root url used to share pages and shit
     * @return string
     */
    public static function share_root() {
        $root = static::config('Eckinox.system.url.share_root');

        if (!$root) {
            $root = static::config('Eckinox.system.url.root');
        }

        return $root;
    }

    /**
     * Return system root url used serve static resources
     * @return string
     */
    public static function static_root() {
        $root = static::config('Eckinox.system.url.static_root');

        if (!$root) {
            $root = static::config('Eckinox.system.url.root');
        }

        return $root;
    }

    /**
     * Return only host part of url
     * @param string $url
     */
    public static function host($url) {
        if (!$parseUrl = parse_url($url))
            return $url; // Important to return $url for double parsing case. parse_url() only works for full urls

        if (!empty($parseUrl['host'])) {
            $host = $parseUrl['host'];
        } else {
            $boom = explode('/', $parseUrl['path'], 2);
            $host = array_shift($boom);
        }

        return $host;
    }

    /**
     * Get domain and subdomain from url
     * @param string $url
     * @param string $part domain | subdomain
     * @return array
     */
    public static function domain($url, $part = null) {
        $return = array('subdomain' => '', 'domain' => '');

        $host = static::host($url);

        // Check if ip
        if (valid::ip($host)) {
            $return['domain'] = $host;
        }
        // Check for invalid Host or Localhost
        if (strpos($host, '.') === false) {
            $return['domain'] = $host;
        } else {
            $boom = explode('.', $host);
            $count = count($boom);

            // Generic tlds as of 27/08/2007 (source: http://en.wikipedia.org/wiki/Generic_top-level_domain)
            $gtld = array(
                'biz', 'com', 'edu', 'gov', 'info', 'int', 'mil', 'name', 'net', 'org',
                'aero', 'asia', 'cat', 'coop', 'jobs', 'mobi', 'museum', 'pro', 'tel', 'travel',
                'arpa', 'root',
                'berlin', 'bzh', 'cym', 'gal', 'geo', 'kid', 'kids', 'lat', 'mail', 'nyc', 'post', 'sco', 'web', 'xxx',
                'nato',
                'example', 'invalid', 'localhost', 'test',
                'bitnet', 'csnet', 'ip', 'local', 'onion', 'uucp',
                'co' // note: not technically, but used in things like co.uk
            );

            // country tlds as of 27/08/2007 (source: http://en.wikipedia.org/wiki/Country_code_top-level_domain)
            $ctld = array(
                // active
                'ac', 'ad', 'ae', 'af', 'ag', 'ai', 'al', 'am', 'an', 'ao', 'aq', 'ar', 'as', 'at', 'au', 'aw', 'ax', 'az',
                'ba', 'bb', 'bd', 'be', 'bf', 'bg', 'bh', 'bi', 'bj', 'bm', 'bn', 'bo', 'br', 'bs', 'bt', 'bw', 'by', 'bz',
                'ca', 'cc', 'cd', 'cf', 'cg', 'ch', 'ci', 'ck', 'cl', 'cm', 'cn', 'co', 'cr', 'cu', 'cv', 'cx', 'cy', 'cz',
                'de', 'dj', 'dk', 'dm', 'do', 'dz', 'ec', 'ee', 'eg', 'er', 'es', 'et', 'eu', 'fi', 'fj', 'fk', 'fm', 'fo',
                'fr', 'ga', 'gd', 'ge', 'gf', 'gg', 'gh', 'gi', 'gl', 'gm', 'gn', 'gp', 'gq', 'gr', 'gs', 'gt', 'gu', 'gw',
                'gy', 'hk', 'hm', 'hn', 'hr', 'ht', 'hu', 'id', 'ie', 'il', 'im', 'in', 'io', 'iq', 'ir', 'is', 'it', 'je',
                'jm', 'jo', 'jp', 'ke', 'kg', 'kh', 'ki', 'km', 'kn', 'kr', 'kw', 'ky', 'kz', 'la', 'lb', 'lc', 'li', 'lk',
                'lr', 'ls', 'lt', 'lu', 'lv', 'ly', 'ma', 'mc', 'md', 'mg', 'mh', 'mk', 'ml', 'mm', 'mn', 'mo', 'mp', 'mq',
                'mr', 'ms', 'mt', 'mu', 'mv', 'mw', 'mx', 'my', 'mz', 'na', 'nc', 'ne', 'nf', 'ng', 'ni', 'nl', 'no', 'np',
                'nr', 'nu', 'nz', 'om', 'pa', 'pe', 'pf', 'pg', 'ph', 'pk', 'pl', 'pn', 'pr', 'ps', 'pt', 'pw', 'py', 'qa',
                're', 'ro', 'ru', 'rw', 'sa', 'sb', 'sc', 'sd', 'se', 'sg', 'sh', 'si', 'sk', 'sl', 'sm', 'sn', 'sr', 'st',
                'sv', 'sy', 'sz', 'tc', 'td', 'tf', 'tg', 'th', 'tj', 'tk', 'tl', 'tm', 'tn', 'to', 'tr', 'tt', 'tv', 'tw',
                'tz', 'ua', 'ug', 'uk', 'us', 'uy', 'uz', 'va', 'vc', 've', 'vg', 'vi', 'vn', 'vu', 'wf', 'ws', 'ye', 'yu',
                'za', 'zm', 'zw',
                // inactive
                'eh', 'kp', 'me', 'rs', 'um', 'bv', 'gb', 'pm', 'sj', 'so', 'yt', 'su', 'tp', 'bu', 'cs', 'dd', 'zr'
            );

            if ($count === 2) {
                $return['domain'] = implode('.', $boom);
            } // 3 segments or more
            elseif (in_array($boom[$count - 1], $gtld)) {
                if ($count === 3) {
                    $return['domain'] = $boom[$count - 2] . '.' . $boom[$count - 1];
                    $return['subdomain'] = $boom[0];
                } else {
                    $return['domain'] = $boom[$count - 2] . '.' . $boom[$count - 1];
                    $boom = array_slice($boom, -2);
                    $return['subdomain'] = implode('.', $boom);
                }
            } elseif (in_array($boom[$count - 1], $ctld)) {
                if (strlen($boom[$count - 2]) == 2) {
                    $return['domain'] = $boom[$count - 3] . '.' . $boom[$count - 2] . '.' . $boom[$count - 1];
                    $boom = array_slice($boom, -3);
                    $return['subdomain'] = implode('.', $boom);
                } else {
                    $return['domain'] = $boom[$count - 2] . '.' . $boom[$count - 1];
                    $boom = array_slice($boom, -2);
                    $return['subdomain'] = implode('.', $boom);
                }
            }
        }

        return $part === null ? $return : $return[$part];
    }

    /**
     * Improved parse url function
     * @author theoriginalmarksimpson at gmail dot com
     */
    public static function parse_url($url) {
        $r = "(?:([a-z0-9+-._]+)://)?";
        $r .= "(?:";
        $r .= "(?:((?:[a-z0-9-._~!$&'()*+,;=:]|%[0-9a-f]{2})*)@)?";
        $r .= "(?:\[((?:[a-z0-9:])*)\])?";
        $r .= "((?:[a-z0-9-._~!$&'()*+,;=]|%[0-9a-f]{2})*)";
        $r .= "(?::(\d*))?";
        $r .= "(/(?:[a-z0-9-._~!$&'()*+,;=:@/]|%[0-9a-f]{2})*)?";
        $r .= "|";
        $r .= "(/?";
        $r .= "(?:[a-z0-9-._~!$&'()*+,;=:@]|%[0-9a-f]{2})+";
        $r .= "(?:[a-z0-9-._~!$&'()*+,;=:@\/]|%[0-9a-f]{2})*";
        $r .= ")?";
        $r .= ")";
        $r .= "(?:\?((?:[a-z0-9-._~!$&'()*+,;=:\/?@]|%[0-9a-f]{2})*))?";
        $r .= "(?:#((?:[a-z0-9-._~!$&'()*+,;=:\/?@]|%[0-9a-f]{2})*))?";
        preg_match("`$r`i", $url, $match);

        $parts = array(
            "scheme" => '',
            "userinfo" => '',
            "authority" => '',
            "host" => '',
            "port" => '',
            "path" => '',
            "query" => '',
            "fragment" => ''
        );

        switch (count($match)) {
            case 10:
                $parts['fragment'] = $match[9];
            case 9:
                $parts['query'] = $match[8];
            case 8:
                $parts['path'] = $match[7];
            case 7:
                $parts['path'] = $match[6] . $parts['path'];
            case 6:
                $parts['port'] = $match[5];
            case 5:
                $parts['host'] = $match[3] ? "[" . $match[3] . "]" : $match[4];
            case 4:
                $parts['userinfo'] = $match[2];
            case 3:
                $parts['scheme'] = $match[1];
        }

        $parts['authority'] = ($parts['userinfo'] ? $parts['userinfo'] . "@" : "") .
                $parts['host'] .
                ($parts['port'] ? ":" . $parts['port'] : "");
        return $parts;
    }

    /**
     * Add a string in uri before params
     * @param string $uri
     * @param string $str - string to add before param
     * @return string           return uri
     */
    public static function addBeforeParam($uri, $str) {
        // init
        $params = '';

        // Split $url at '?'
        if (($pos = strpos($uri, '?')) !== false) {
            $params = substr($uri, $pos + 1);
            $uri = substr($uri, 0, $pos);
        }

        return static::stripLastSlash($uri . $str) . static::config('Eckinox.system.url.ext') . '?' . $params;
    }

    /**
     * Create a clean real url by replacing ../ et ./
     * Add a trailing slasg at the end
     * @param string $url
     */
    public static function resolve($url) {
        $url = explode('/', $url);
        $keys = array_keys($url, '..');

        foreach ($keys AS $keypos => $key) {
            array_splice($url, $key - ($keypos * 2 + 1), 2);
        }

        $url = implode('/', $url);
        $url = str_replace('./', '', $url);

        return $url;
    }

    /**
     * Add segment to url
     * @param string $url
     * @param string $segment
     * @return string
     */
    public static function addSegment($url, $segment) {
        // Init
        $ext = '';
        $param = '';

        // Split $url at '?'
        if (($pos = strpos($url, '?')) !== false) {
            $param = substr($url, $pos);
            $url = substr($url, 0, $pos);
        }

        $segments = explode('/', $url);
        $count = count($segments);

        // Split last segment on ext
        if ($pos = strrpos($segments[$count - 1], '.')) {
            $ext = substr($segments[$count - 1], $pos);
            $segments[$count - 1] = substr($segments[$count - 1], 0, $pos);
        }

        // Remove last empty segment
        if ($segments[$count - 1] == '') {
            $count--;
            array_pop($segments);
        }

        $segments = array_merge($segments, (array) $segment);

        $url = implode('/', $segments) . $ext . $param;

        return $url;
    }

    /**
     * Add extension to url
     * @param string $url
     * @param string $ext - extension to add, will use Config's ext if none specified
     * @return string
     */
    public static function addExt($url, $ext = null) {
        $url_origin = $url;
        list($url, $query) = static::splitOnQuery($url);
        list($url, $anchor) = static::splitOnAnchor($url);

        // Don't add extension on index
        if ($url == static::site_base()) {
            return $url_origin;
        }

        $ext = ($ext === null) ? static::config('Eckinox.system.url.ext') : $ext;

        $url = static::stripLastSlash($url);
        $url .= ((substr($url, -strlen($ext)) != $ext) ? $ext : '') . ($query != '' ? '?' . $query : '') . ($anchor != '' ? '#' . $anchor : '');

        return $url;
    }

    /**
     * Add protocol to url
     *
     * @param string $uri
     * @param string $protocol - protocol to add, will use Config's if none specified
     * @return string
     */
    public static function addProtocol($uri, $protocol = null) {
        $protocol = ($protocol === null) ? 'http://' : $protocol;

        return (stripos($uri, '://') === false) ? $protocol . $uri : $uri;
    }

    /**
     * Add a param to uri
     * @param string $uri
     * @param string|array $name - name of param
     * @param string $value - value of param
     * @return string uri with param
     */
    public static function addParam($uri, $name, $value = '') {
        if (empty($name)) {
            return $uri;
        }

        if (is_string($name)) {
            $param = ($name[0] == '?' ? arr::explode('&', '=', substr($name, 1)) : array($name => $value));
        } else {
            $param = (array) $name;
        }

        list($uri, $fragment) = static::splitOnFragment($uri);

        $uri = url::removeParam($uri, array_keys($param));

        $uri .= (strpos($uri, '?') !== false) ? '&' : '?';
        $uri .= http_build_query($param);

        return rtrim($uri . ($fragment ? '#' . $fragment : ''), '&');
    }

    public static function addFragment($url, $fragment) {
        return static::addAnchor($url, $fragment);
    }

    public static function addAnchor($url, $fragment) {
        $fragment = substr($fragment, 0, 1) == '#' ? $fragment : '#' . $fragment;
        $url = static::stripFragment($url);

        return $url . $fragment;
    }

    /**
     * Remove param from uri
     * @param string $uri
     * @param string|array $name
     */
    public static function removeParam($uri, $keys) {
        $keys = (array) $keys;

        list($url, $query) = static::splitOnQuery($uri);

        $boom = explode('&', $query);
        foreach ($boom as $i => $param) {
            $posSepar = strpos($param, '=');
            $param = substr($param, 0, $posSepar);
            if (in_array($param, $keys)) {
                unset($boom[$i]);
            }
        }
        $query = implode('&', $boom);

        return $url . ($query ? '?' . $query : '');
    }

    /**
     * Split url on query '?'
     * @param string $url
     */
    public static function splitOnQuery($url) {
        $split = [];

        // Split $url at '?'
        if (($pos = strpos($url, '?')) !== false) {
            $split[] = substr($url, 0, $pos);
            $split[] = substr($url, $pos + 1);
        } else {
            $split[] = $url;
            $split[] = '';
        }

        return $split;
    }

    /**
     * Split url on anchor '#'
     * @param string $url
     */
    public static function splitOnFragment($url) {
        return static::splitOnAnchor($url);
    }

    public static function splitOnAnchor($url) {
        $split = [];

        // Split $url at '?'
        if (($pos = strrpos($url, '#')) !== false) {
            $split[] = substr($url, 0, $pos);
            $split[] = substr($url, $pos + 1);
        } else {
            $split[] = $url;
            $split[] = '';
        }

        return $split;
    }

    /**
     * strip query part of url
     * @param string $url
     */
    public static function stripQuery($url) {
        $split = static::splitOnQuery($url);
        return $split[0];
    }

    /**
     * strip anchor part of url
     * @param string $url
     */
    public static function stripFragment($url) {
        return static::stripAnchor($url);
    }

    public static function stripAnchor($url) {
        $split = static::splitOnAnchor($url);
        return $split[0];
    }

    /**
     * Remove last slash of string if it exist
     *
     * @param string $str
     * @return string
     */
    public static function stripLastSlash($str) {
        if (substr($str, -1, 1) == '/') {
            return substr($str, 0, -1);
        }

        return $str;
    }

    /**
     * add finishing slash to a string if one isnt there already
     * @param string $str
     * @return string
     */
    public static function addLastSlash($str) {
        if (substr($str, -1, 1) == '/') {
            return $str;
        }

        return $str . '/';
    }

    /**
     * Urlify a string
     * @param string $str
     * @return string urlified
     */
    public static function urlify($str) {
        return static::slug($str, '_');
    }

    /**
     * Light urlify version
     * Doesnt convert dots.
     * @param string $str
     * @return string urlified
     */
    public static function lightUrlify($str) {
        return static::slug($str, '_', '.');
    }

    /**
     * Remove useless char at the end and beginning of a string
     * Will remove '-' | '_'
     */
    public static function polish($str) {
        $str = preg_replace("/^([-_]?)(.*)([-_]?)$/U", '$2', $str);
        $str = preg_replace('/__+/', '_', $str);
        return $str;
    }

    /**
     * Convert string to slug
     * url::slug('My Blog Post'); // "my-blog-post"
     * url::slug('My - Blog - Post'); // "my-blog-post"
     * url::slug('My_Blog_Post'); // "my-blog-post"
     * @param string $str
     * @param string $separator used to separate words
     * @param string $accept additional chars that will be accepted in the regex
     */
    public static function slug($str, $separator = '-', $accept = '') {
        $str = text::normalize($str);
        $str = strtolower($str);

        $str = str_replace(array(' ', '_', '-'), array($separator, $separator, $separator), $str);

        $escaped_separator = preg_quote($separator);
        $accept = preg_quote($accept);
        $str = preg_replace('/[^a-z0-9' . str_replace('/', '\/', $accept . $escaped_separator) . ']/', '', $str);
        $str = preg_replace('/(' . $escaped_separator . ')(' . $escaped_separator . ')+/', $separator, $str);

        return $str;
    }

}
