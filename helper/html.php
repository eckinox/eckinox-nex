<?php

namespace Eckinox\Nex;

/**
 * @author       Mikael Laforge <mikael.laforge@gmail.com>
 * @version      1.0.9
 * @package      Nex
 * @subpackage   core
 *
 * @update (03/02/2010) [ML] - 1.0.1 - image method now add height and width attributes to image
 * @update (13/06/2010) [ML] - 1.0.2 - New method idFromName() to transform name[] attribute (array) into unused id attribute
 * @update (10/05/2012) [ML] - 1.0.3 - Bugfix in pagination() method not returning the right pages in some situation
 *                                   - Added body_class() method which return css class to be included in the html
 * @update (23/08/2013) [ML] - 1.0.4 - Added img()
 *                                     Deprecated image(), title(), description(), base_meta(), no_robots()
 * @update (05/10/2013) [ML] - 1.0.5 - Added str2sentences()
 * @update (12/02/2014) [ML] - 1.0.6 - Bugfix: str2sentences() when using other tags then <p> and <br/>
 * 									   added toNL() method
 * 									   Added jquerySelector() to escape jquery selectors
 * @update (29/05/2014) [ML] - 1.0.7 - fix str2sentence not spliting on ? and !
 * @update (15/07/2014) [ML] - 1.0.8 - improved methods stylesheet(), script(), image() and img() to support site-root relative urls
 * @update (11/08/2014) [ML] - 1.0.9 - added method meta()
 *
 * 09/08/2009
 * This class was made to help create any generic html
 */
abstract class html {

    protected static $ids = [];

    /**
     * Check if a css sheet exist in the public directory,
     * if it does, return the complete html link to css sheet
     *
     * @return String           html css link
     */
    public static function stylesheet($filename = '') {
        $base = url::site_base();
        if (substr($filename, 0, 7) === 'http://' || substr($filename, 0, 8) === 'https://' || substr($filename, 0, 2) === '//' || substr($filename, 0, strlen($base)) === $base) {
            return "<link type='text/css' href='" . $filename . "' rel='stylesheet'>";
        }

        $filename .= (stripos($filename, '.') == false) ? '.css' : '';

        if (!$file_path = Nex::publicUrl('css/' . $filename)) {
            return false;
        }

        return "<link type='text/css' href='" . $file_path . "' rel='stylesheet'>\n";
    }

    /**
     * Check if a script file exist in the public directory,
     * if it does, return the complete html link to script file
     *
     * @return String           javascript file
     */
    public static function script($filename = '') {
        $base = url::site_base();
        if (substr($filename, 0, 7) === 'http://' || substr($filename, 0, 8) === 'https://' || substr($filename, 0, 2) === '//' || substr($filename, 0, strlen($base)) === $base) {
            return "<script type='text/javascript' src='" . $filename . "'></script>";
        }

        $filename .= (stripos($filename, '.') == false) ? '.js' : '';

        if (!$file_path = Nex::publicUrl('script/' . $filename)) {
            return false;
        }

        return "<script type='text/javascript' src='" . $file_path . "'></script>\n";
    }

    public static function meta($key, $value = null) {
        $arr = is_array($key) ? $key : array($key => $value);
        $html = '';

        $http_equiv = array('expires', 'pragma', 'cache-control', 'refresh', 'imagetoolbar');
        $special = array('name', 'charset');

        foreach ($arr as $key => $value) {
            if ($key == 'title') {
                $html .= '<title>' . $value . '</title>';
            } else {
                $html .= '<meta ';
                if (in_array($key, $http_equiv)) {
                    $html .= 'http-equiv="' . $key . '" content="' . $value . '">';
                } elseif (in_array($key, $special)) {
                    $html .= $key . '="' . $value . '">';
                } elseif (strpos($key, ':') > 0) {
                    $html .= 'property="' . $key . '" content="' . $value . '">';
                } else {
                    $html .= 'name="' . $key . '" content="' . $value . '">';
                }
            }
        }

        return $html;
    }

    /**
     * @deprecated
     * Return html title
     * @param string title - title to put un title tag
     */
    public static function title($subtitle = null) {
        return '<title>' . Nex::config('site.title') . ($subtitle ? ' - ' . $subtitle : '' ) . '</title>';
    }

    /**
     * @deprecated
     * Return html description meta
     */
    public static function description() {
        return '<meta name="Description" content="' . Nex::config('site.description') . '" />';
    }

    /**
     * @deprecated
     * Build a set of base meta generally included in every website
     */
    public static function base_meta() {
        $html = "<meta http-equiv=\"Cache-Control\" content=\"" . Nex::config('system.cache_control') . "\">\n" .
                "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n" .
                "<base href=\"" . url::site_base() . "\" >" .
                "<link rel=\"Shortcut Icon\" href=\"" . Nex::config('site.favicon') . "\">";

        return $html;
    }

    public static function body_class() {
        return request::browser_engine() . ' ' . request::browser() . ' v-' . request::browser_major_version();
    }

    /**
     * @deprecated
     * No seo meta
     */
    public static function no_robots() {
        return '<meta name="robots" content="noindex, nofollow" />';
    }

    /**
     * @deprecated !!
     * Build <a> html tag with the given params
     *
     * @param String                $content - Content to put inside tags
     * @param String                $href - href attribute of link
     * @param String/Array          $attr - attributes of link
     * @param String                $action - Javascript action done on mouse click
     *
     * @return String               <a> html tag
     *
     * @uses url::site()
     * @uses attr()
     */
    public static function link($content, $href = null, $attr = [], $action = null) {
        // Make sure attr is array
        $attr = (is_array($attr)) ? $attr : (array) $attr;

        // add onclick if not null
        if (!is_null($action)) {
            $attr['onclick'] = $action;
        }

        $html = '<a ' .
                ((!is_null($href)) ? "href=\"" . url::site(url::addExt($href)) . "\" " : '') .
                self::attr($attr);

        $html .= '>' . $content . '</a>';

        return $html;
    }

    /**
     * @deprecated !!! uses img()
     * Build html image tag with given params
     * @param string                $src - relative source of image with extension
     * @param string                $ovr - source of mouse over image
     * @param string                $class - class of image
     * @param array                 $attr - other attributes of image
     * @return                      img html tag
     */
    public static function image($src, $ovr = null, $class = '', $attr = []) {
        if (!empty($src)) {
            $base = url::site_base();
            if (substr($src, 0, 7) !== 'http://' && substr($src, 0, 8) !== 'https://' && substr($src, 0, 2) !== '//' && substr($src, 0, strlen($base)) !== $base) {
                $src = Nex::publicUrl('image/' . $src);
            }

            $attr['src'] = $src;

            if (!empty($ovr)) {
                if (substr($ovr, 0, 7) !== 'http://' && substr($ovr, 0, 8) !== 'https://' && substr($ovr, 0, 2) !== '//' && substr($ovr, 0, strlen($base)) !== $base) {
                    $ovr = Nex::publicUrl('image/' . $ovr);
                }
                $attr['onMouseOver'] = "this.src='" . $ovr . "';";
                $attr['onMouseOut'] = "this.src='" . $src . "';";
            }
        }

        if (!empty($class)) {
            $attr['class'] = $class;
        }

        return '<img ' . self::attr($attr) . ' />';
    }

    /**
     * Build html image optimized for SEO
     */
    public static function img($src, $alt = '', $class = '', $attr = []) {
        $base = url::site_base();
        if (substr($src, 0, 7) !== 'http://' && substr($src, 0, 8) !== 'https://' && substr($src, 0, 2) !== '//' && substr($src, 0, strlen($base)) !== $base) {
            $src = Nex::publicUrl('image/' . $src);
        }
        $attr['src'] = $src;

        if (!empty($alt))
            $attr['alt'] = htmlspecialchars($alt);
        if (!empty($class))
            $attr['class'] = $class;

        return '<img ' . self::attr($attr) . '/>';
    }

    /**
     * Close any left opened tags in a string
     * @param string $html
     * @return string
     */
    public static function closeTags($html) {
        // put all opened tags into an array
        preg_match_all("#<([a-z]+)( .*)?(?!/)>#iU", $html, $result);
        $openedtags = $result[1];
        $openedtags = array_diff($openedtags, array("img", "hr", "br"));
        $openedtags = array_values($openedtags);

        // put all closed tags into an array
        preg_match_all("#</([a-z]+)>#iU", $html, $result);
        $closedtags = $result[1];

        $len_opened = count($openedtags);

        // If number of opened and closes are the same, no tag left opened
        if (count($closedtags) == $len_opened) {
            return $html;
        }

        $openedtags = array_reverse($openedtags);
        // Close tags
        for ($i = 0; $i < $len_opened; $i++) {
            if (!in_array($openedtags[$i], $closedtags)) {
                $html .= "</" . $openedtags[$i] . ">";
            } else {
                unset($closedtags[array_search($openedtags[$i], $closedtags)]);
            }
        }

        return $html;
    }

    /**
     * Create a pagination
     * @param int $count - total number of page
     * @param int $limit - limit of item per page
     * @param int $page_limit - limit of page link shown
     * @param int $page - current page
     * @param bool $prev_next - if we add previous and next
     * @return array
     */
    public static function pagination($count, $limit, $page_limit, $page = 1, $prev_next = true) {
        $nbr_page = ceil($count / $limit);

        $start = ($page - floor($page_limit / 2));
        $start = ($start < 1) ? 1 : $start;
        $end = ($page + floor($page_limit / 2));
        $end = ($end > $nbr_page) ? $nbr_page : $end;

        $real_nbr_page = $end + 1 - $start;

        while ($real_nbr_page < $page_limit && $start > 1) {
            $start--;
            $real_nbr_page++;
        }
        while ($real_nbr_page < $page_limit && $end < $nbr_page) {
            $end++;
            $real_nbr_page++;
        }

        $tmp = [];
        for ($x = $start; $x <= $end; $x++) {
            $tmp[$x] = $x;
        }

        if ($prev_next == true) {
            if ($page > 1)
                $tmp['previous'] = $page - 1;
            if ($page < $nbr_page)
                $tmp['next'] = $page + 1;
        }

        return $tmp;
    }

    /**
     * Count the words in a html string. punctuation and double space doesnt count as a word.
     *
     * @param string $html
     */
    public static function countWords($html) {
        $html = strip_tags($html, '<script>');
        $html = preg_replace('#<script(.*)<\/script>#isU', '', $html);

        return text::countWords($html);
    }

    /**
     * Make Html attributes array to string
     *
     * @param String/Array           $attr - array or string of attributes
     * @return String               compiled attributes
     */
    public static function attr($attr) {
        if (empty($attr))
            return '';

        if (is_string($attr))
            return ' ' . $attr;

        $compiled = '';
        foreach ($attr as $key => $val) {
            $compiled .= " $key = \"" . ($val) . "\" ";
        }

        return $compiled;
    }

    /**
     * Return valid unused id from name
     */
    public static function idFromName($name) {
        if (strpos($name, '[]') !== FALSE) {
            $id = str_replace('[]', '', $name);
            self::$ids[$id] = (!isset(self::$ids[$id]) ? 0 : self::$ids[$id] + 1);

            $id = $id . self::$ids[$id];
            return $id;
        } elseif (strpos($name, '[') !== FALSE) {
            $id = preg_replace("/^(.*)\[([0-9]*)\]$/", '$1$2', $name);

            return $id;
        }

        self::$ids[$name] = (!isset(self::$ids[$name]) ? '' : intval(self::$ids[$name]) + 1);
        $name = $name . self::$ids[$name];

        return $name;
    }

    /**
     * Convert special characters to HTML entities
     *
     * @param string            $str - string to convert
     * @param bool              $double_encode - encode existing entities
     * @return string           cleaned String
     */
    public static function specialChars($str, $double_encode = TRUE) {
        // Do encode existing HTML entities (default)
        if ($double_encode === TRUE) {
            $str = htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
        } else {
            // Do not encode existing HTML entities
            // From PHP 5.2.3 this functionality is built-in, otherwise use a regex
            if (version_compare(PHP_VERSION, '5.2.3', '>=')) {
                $str = htmlspecialchars($str, ENT_QUOTES, 'UTF-8', FALSE);
            } else {
                $str = preg_replace('/&(?!(?:#\d++|[a-z]++);)/ui', '&amp;', $str);
                $str = str_replace(array('<', '>', '\'', '"'), array('&lt;', '&gt;', '&#39;', '&quot;'), $str);
            }
        }

        return $str;
    }

    /**
     * Convert string to css friendly name
     */
    public static function cssName($str, $separator = '-') {
        $str = strtolower(trim($str));
        $str = utf8_decode($str);

        $tofind = "àáâãäåòóôõöøèéêëçìíîïùúûüÿñ";
        $replac = "aaaaaaooooooeeeeciiiiuuuuyn";

        $str = strtr($str, utf8_decode($tofind), $replac);
        $str = utf8_encode($str);

        $str = str_replace(array(' ', '_', '-', '/'), array($separator, $separator, $separator, $separator), $str);

        $escaped_separator = preg_quote($separator);

        $str = preg_replace('/[^a-z0-9' . str_replace('/', '\/', $escaped_separator) . ']/', '', $str);
        $str = preg_replace('/(' . $escaped_separator . ')(' . $escaped_separator . ')+/', $separator, $str);

        return $str;
    }

    /**
     * Escape a string to be jquery selector happy
     */
    public static function jquerySelector($str) {
        return str_replace(array('[', ']', '.'), array('\\\\[', '\\\\]', '\\\\.'), $str);
    }

    /**
     * Strips new lines char in a string
     *
     * @param string            $str - string to be striped
     * @param bool              $escape - escape string after strips
     * @return                  string with no line break
     */
    public static function nlStrip($str = '', $escape = false) {
        $str = strtr($str, array("\n" => '', "\r\n" => ''));
        return ($escape == true) ? addslashes($str) : $str;
    }

    /**
     * Return sentences array from string
     */
    public static function str2sentences($str) {
        $boom = preg_split("/(\n|\.|\?|!|<br\/?>|<\/p>)+/", strip_tags($str, '<br><p>'));
        $sentences = [];

        foreach ($boom as $sentence) {
            $sentence = trim(strip_tags($sentence));
            if ($sentence)
                $sentences[] = $sentence;
        }

        return $sentences;
    }

    public static function toNL($str) {
        $str = preg_replace('/<br\/?>/', "\r\n", $str);
        $str = preg_replace('/<\/p>/', "\r\n\r\n", $str);

        return $str;
    }

}
