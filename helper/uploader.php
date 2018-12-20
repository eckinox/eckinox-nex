<?php

namespace Eckinox\Nex;

/**
 * @author       Mikael Laforge <mikael.laforge@twikiconcept.com>
 * @version      1.0
 * @package      S.a.S
 * @subpackage   core
 * @copyright    Copyright (c) 2009, Twiki Concept (www.twikiconcept.com)
 *
 * @update 20/10/09 [ML] - Renamed class name 'File_Help' > 'upload'
 *
 * 08/09/2009
 * This class was made to help create file uploader
 */

abstract class uploader {

    /**
     * Generates a file uploader HTML form tag.
     *
     * @param String            $action - form action attribute
     * @param String            $name - Name and if of form
     * @param String            $onSubmit - Javascript to execute on form submit
     * @param Array             $attr - extra attribute
     * @return String           Opening form tag
     *
     * @uses form::openFile()
     */
    public static function open($action = NULL, $name = 'default_form', $onSubmit = null, $attr = []) {
        $attr['target'] = $name . "_frame";
        return form::openFile($action, $name, $attr, $onSubmit) . "<iframe id=\"" . $name . "_frame\" name=\"" . $name . "_frame\" src=\"\" style='width:0;height:0;border:none;' ></iframe>";
    }

    /**
     * Create a quich ajax uploader form
     *
     * @param string			$action - url to call when uploading a file
     * @param string			$name -  name and id of button
     * @param string			$title - value of submit button
     * @param array				$attr - array of attributes
     * @param string			$class - class of button
     */
    public static function quickUploader($action, $name, $title, $attr = [], $hiddens = []) {
        return self::open($action, $name . '_form', null, $attr) . form::file($name, 'input_file') . form::submit($name . '_submit', $title, 'submit_button') . form::hidden($hiddens) . form::close();
    }

}
