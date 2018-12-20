<?php

namespace Eckinox\Nex;

/**
 * @author       Mikael Laforge <mikael.laforge@twikiconcept.com>
 * @version      1.0
 * @package      S.a.S
 * @subpackage   helpers
 * @copyright    Copyright (c) 2009, Twiki Concept (www.twikiconcept.com)
 *
 * @update (03/02/2010) [Mikael Laforge] Script creation
 *
 * 03/02/2010
 * This class was made to help with javascript ajax
 * SaS custom javascript function is used. This custom function uses jQuery
 */
abstract class ajax {

    /**
     * Build <a> html tag with the given params
     *
     * @param String                $content - Content to put inside tags
     * @param String                $href - href attribute of link
     * @param String/Array          $attr - attributes of link
     * @param String                $target - target of ajax link
     * @param String                $callback - Javascript callback
     *
     * @return String               <a> html tag
     */
    public static function link($content, $href = null, $attr = [], $target = 'NEX_center', $callback = '') {
        // Make sure attr is array
        $attr = (is_array($attr)) ? $attr : (array) $attr;

        // Put target in attr
        $attr['target'] = $target;

        // Add ajax call
        if (isset($attr['onclick']))
            $attr['onclick'] .= self::ajaxCall($href, $target, $callback) . " return false;";
        else
            $attr['onclick'] = self::ajaxCall($href, $target, $callback) . " return false;";

        return html::link($content, $href, $attr);
    }

    /**
     * Ajaxify an uri
     *
     * @param string		$uri
     * @param string		$params - extra params to add at the end
     * @return string		ajaxified
     */
    public static function ajaxify($uri, $params = null) {
        return url::site(url::addParam(self::addAjaxParam($uri), $params));
    }

    /**
     * Add a ajax param to uri
     *
     * @param string            $uri
     * @return string           uri with param
     */
    public static function addAjaxParam($uri) {
        return url::addParam($uri, Nex::config('url.ajax'), Nex::$p_serial);
    }

    /**
     * Javascript ajax call
     *
     * @param string				$url - url to call
     * @param string				$target - id of target
     * @param string				$callback - function to call after ajax call, ajax respond will be passed as param
     */
    public static function ajaxCall($url, $target = '', $callback = '') {
        $url = self::addAjaxParam(url::addExt($url));

        return "ajaxCall('" . url::site($url) . "', " . json_encode($target) . (!empty($callback) ? ', ' . $callback : '') . ');';
    }

}
