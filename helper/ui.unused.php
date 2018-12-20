<?php

namespace Eckinox\Nex;

/**
 * @author       Mikael Laforge <mikael.laforge@twikiconcept.com>
 * @version      1.0
 * @package      S.a.S
 * @subpackage   core
 * @copyright    Copyright (c) 2009, Twiki Concept (www.twikiconcept.com)
 *
 * @update
 *
 * 12/11/2009
 * This class was made to help create beautiful ui widget using javascript
 * Js should be included in the xCore
 */
abstract class ui {

    /**
     * Create a context menu
     * Need jQuery and contextMenu plugin
     *
     * @param string			$bindto_selector - elements that will open context menu
     * @param string			$url - url to call on menu click, action will passed as param
     * @param string			$param - name of param used
     * @param string			$id - id of the context menu
     * @param string			$options - array of options in the menu  ($key => $lang)
     * @param string			$target - div that will receive content of the ajax call
     * @param string			$class - xtra class of the context menu
     */
    public static function contextMenu($bindto_selector, $url, $param, $id, $options, $target = 'NEX_center', $class = 'sas-context-menu') {
        $html = "<ul id=\"" . $id . "\" class=\"contextMenu $class\" >\n";
        foreach ($options as $key => $lang) {
            $html .= "<li class=\"" . str_replace('ajax_', '', $key) . "\"><a href=\"#" . str_replace(' separator', '', $key) . "\">" . $lang . "</a></li>\n";
        }
        $html .= "</ul>";

        $js = "<script>" .
                //"$(function() {".
                "$(\"" . $bindto_selector . "\").contextMenu({" .
                "menu: \"" . $id . "\"" .
                "}," .
                "function(action, el, pos) {" .
                "if( action.substr(0, 5) == 'ajax_') {" .
                "ajaxCall(\"" . html::ajaxify($url, $param) . "\" + action.substr(5) + '&id=' + parseInt($(el).attr('id')), \"" . $target . "\");" .
                "}" .
                "else {" .
                "window.location.href = \"" . url::site(url::addParam($url, $param)) . "\" + action + '&id=' + $(el).attr('id') ;" .
                "}" .
                "});" .
                //"});".
                "</script>";

        return $html . $js;
    }

}
