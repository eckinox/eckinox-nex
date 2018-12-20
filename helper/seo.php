<?php

namespace Eckinox\Nex;

/**
 * @author       Mikael Laforge <mikael.laforge@twikiconcept.com>
 * @version      1.0
 * @package      S.a.S
 * @subpackage   core
 * @copyright    Copyright (c) 2010, Twiki Concept (www.twikiconcept.com)
 *
 * @update (21/01/2010) [Mikael Laforge] - 1.0 - Script creation
 *
 * 21/01/2010
 * Class to help with seo tasks
 * Be smart using this class to avoid spam
 * Result can be slow using this class, set timer config to your preferences
 * We use url::get_urlContent() This method try to use 'curl' librairie if its available to create a human like call.
 * Will use file_get_content() otherwise
 */
abstract class seo {

    // Remote helper
    protected $remote;
    // Url being analysed
    protected $url = null;
    // Content received from request to google
    protected $content = null;
    // Internal sleep timer to be a bit more human like
    protected $sleep = 0.5; // Seconds
    // Page details
    //public			$page_rank = null; // Using google class may cause ip ban
    //public			$nbr_indexed = null; // Using google class may cause ip ban
    public $title = null;
    public $description = null;
    public $keywords = null;
    public $robots = null;
    public $h1 = null;
    public $h2 = [];
    public $h3 = [];
    public $nbr_word = null;
    public $nbr_link = null;
    public $nbr_ilink = null;
    public $nbr_xlink = null;
    public $ilinks = [];
    public $xlinks = [];

    /**
     * Static constructor for chaining
     *
     * @param string $url The site URL to check for PageRank
     */
    public static function factory($url) {
        return new self($url);
    }

    /**
     * Constructor.
     *
     * @param string $url
     */
    public function __construct($url) {
        $this->url = $url;
        $this->remote = new remote();
        $this->content = $this->remote->getContent($url);
    }

    /**
     * Full analysis of an url
     * @return bool
     *
     * @uses google
     */
    public function analyse() {
        // init
        $info = parse_url($this->url);

        preg_match_all("/<h1([^>]*)>(.*)<\/h1>/isU", $this->content, $parse);
        $this->h1 = $parse[2];
        preg_match_all("/<h2([^>]*)>(.*)<\/h2>/isU", $this->content, $parse);
        $this->h2 = $parse[2];
        preg_match_all("/<h3([^>]*)>(.*)<\/h2>/isU", $this->content, $parse);
        $this->h3 = $parse[2];

        // Title
        preg_match("/<title>(.*)<\/title>/isU", $this->content, $parse);
        $this->title = $parse[1];

        // Meta
        $meta = $this->get_meta();
        $this->description = isset($meta['description']) ? $meta['description'] : null;
        $this->keywords = isset($meta['keywords']) ? $meta['keywords'] : null;
        $this->robots = isset($meta['robots']) ? $meta['robots'] : null;

        // Number of words
        $this->nbr_word = html::countWords($this->content);

        // Number of links
        $ilinks = $xlinks = [];
        $nbr = $nbr_xlink = $nbr_ilink = 0;
        preg_match_all('/<a(.+)href *= *("|\')?([^" \']+)("|\'| )/isU', $this->content, $link_found);
        foreach ($link_found[3] as $link) {
            if (stripos($link, 'javascript:') === false) {
                if (preg_match('/^\//', $link))
                    $link = "http://" . $info["host"] . $link;
                if (preg_match('/^\#/', $link))
                    $link = $this->url . $link;
                if (!preg_match('/^(http|ftp)/iU', $link)) {
                    if (substr($this->url, -1, 1) != "/") {
                        $pos = strrpos($this->url, '/');

                        if ($pos === false || $pos < 7) {
                            $link = $this->url . '/' . $link;
                        } else {
                            $link = substr($this->url, 0, $pos) . '/' . $link;
                        }
                    } else {
                        $link = $this->url . $link;
                    }
                }

                if (substr($link, 0, strlen("http://" . $info["host"])) != "http://" . $info["host"]) {
                    $nbr_xlink++;
                    $xlinks[] = $link;
                } else {
                    $nbr_ilink++;
                    $ilinks[] = $link;
                }
                $nbr++;
            }
        }
        $this->nbr_link = $nbr;
        $this->nbr_xlink = $nbr_xlink;
        $this->nbr_ilink = $nbr_ilink;
        $this->xlinks = $xlinks;
        $this->ilinks = $ilinks;

        return true;
    }

    /**
     * Get meta of html content
     */
    public function get_meta() {
        // Init
        $content = $this->content;
        $array = [];

        $content = preg_replace("'<style[^>]*>.*</style>'siU", '', $content);  // strip js
        $content = preg_replace("'<script[^>]*>.*</script>'siU", '', $content); // strip css
        $boom = explode("\n", $content);

        foreach ($boom as $key => $line) {
            $expression = "/<meta[^>]+(http\-equiv|name)=\"([^\"]*)\"[^>]content=\"([^\"]+)\"[^>]*>/isU";
            if (preg_match_all($expression, '<meta' . $line, $tmp, PREG_SET_ORDER)) {
                foreach ($tmp as $arr) {
                    // $arr[1] -> attribut name ou http-equiv
                    // $arr[2] -> value of $arr[1]
                    // $arr[3] -> value of 'content' attribute
                    if (in_array(strtolower($arr[2]), array('description', 'keywords', 'robots'))) {
                        $array[strtolower($arr[2])] = $arr[3];
                    }
                }
            }
        }

        return $array;
    }

    /**
     * Return an array of change frequency for sitemap.xml
     */
    public static function sitemapFrequency() {
        $array = array(
            'always' => Nex::lang('global.sitemap:always', true),
            'hourly' => Nex::lang('global.sitemap:hourly', true),
            'daily' => Nex::lang('global.sitemap:daily', true),
            'weekly' => Nex::lang('global.sitemap:weekly', true),
            'monthly' => Nex::lang('global.sitemap:monthly', true),
            'yearly' => Nex::lang('global.sitemap:yearly', true),
            'never' => Nex::lang('global.sitemap:never', true)
        );

        return $array;
    }

}
