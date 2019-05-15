<?php

namespace Eckinox\Nex;

/**
 * @author       Mikael Laforge <mikael.laforge@gmail.com>
 * @version      1.0.11
 * @package      Nex
 * @subpackage   helper
 * @copyright
 *
 * @update (13/11/2009) [Mikael Laforge] - 1.0.1 - Added reset.css, style.css and custom.css to the ckeditor in editor() method
 * @update (21/03/2010) [Mikael Laforge] - 1.0.2 - Added support for multiple selected items in dropdown() method.
 * @update (01/12/2011) [Mikael Laforge] - 1.0.3 - Fixed bug in date() and time() method when using array name
 * 												 - date() now use Y-m-d format
 * @update (07/06/2012) [ML] - 1.0.4 - checkBoxList() method now uses shiftClick jQuery plugin
 * @update (23/07/2012) [ML] - 1.0.5 - fixed a bug in time() method that was causing the hidden value to not update properly when changing minutes
 * @update (08/02/2013) [ML] - 1.0.6 - added datetimePicker(), radios() and checkboxes() methods
 *                                     time() method will now trigger 'change' on its hidden element when values are changed
 * @update (04/07/2013) [ML] - 1.0.7 - Added url(), email(), search() and number() html 5 inputs
 * @update (02/12/2013) [ML] - 1.0.8 - Added selectYear(), selectMonth(), selectDay() and selectRange() methods
 * @update (10/02/2014) [ML] - 1.0.9 - Fixed bug in datePicker() method in IE
 * @update (28/02/2014) [ML] - 1.0.10 - @uses html 1.0.6. datePicker() now uses html::jquerySelector to escape id used by jquery
 * @update (30/07/2014) [ML] - 1.0.11 - hidden() now accept array as value
 *
 * 11/08/2009
 * This class was made to help create forms
 */

abstract class form {

    /**
     * Check form token
     * @param string $name
     * @param array $data
     */
    public static function checkToken($name, $data) {
        return (isset($data[$name], $_SESSION[$name]) && $data[$name] == $_SESSION[$name]) ? true : false;
    }

    /**
     * Generates a normal opening HTML form tag.
     *
     * @param String            $action - form action attribute
     * @param String            $name - Name and if of form
     * @param Array             $attr - extra attributes
     * @return String           Opening form tag
     *
     * @uses url::site()
     */
    public static function open($action = null, $name = 'defaultForm', $attr = []) {
        // Init
        $params = '';

        // Make sure that the method is always set
        empty($attr['method']) and $attr['method'] = 'post';

        $attr['method'] = strtolower($attr['method']);

        if ($attr['method'] !== 'post' AND $attr['method'] !== 'get') {
            // If the method is invalid, use post
            $attr['method'] = 'post';
        }

        $action = url::site($action, true);

        // Set name and id
        $attr['name'] = $name;
        $attr['id'] = $name;

        // Set action
        $attr['action'] = $action;

        // Set onSubmit event if not null
        if (!empty($onSubmit)) {
            $attr['onSubmit'] = $onSubmit;
        }

        // Form opening tag
        $form = '<form' . self::attributes($attr) . '>' . "\n";

        return $form;
    }

    /**
     * Generates an opening HTML form tag that can be used for uploading files.
     *
     * @param String            $action - form action attribute
     * @param String            $name - Name and if of form
     * @param Array             $attr - extra attributes
     * @param String            $onSubmit - Javascript to execute on form submit
     * @return String           Opening form tag
     */
    public static function openFile($action = NULL, $name = 'defaultForm', $attr = [], $onSubmit = null) {
        // Set multi-part form type
        $attr['enctype'] = 'multipart/form-data';
        $attr['method'] = 'post';

        return self::open($action, $name, $attr, $onSubmit);
    }

    /**
     * Generates a fieldset opening tag.
     *
     * @param Array             $attr - html attributes
     * @param String            $xtra - a string to be attached to the end of the attributes
     * @return String           Opening fieldset tag
     */
    public static function open_fieldset($attr = NULL, $xtra = '') {
        return '<fieldset' . html::attr((array) $attr) . ' ' . $xtra . '>' . "\n";
    }

    /**
     * Generates a fieldset closing tag.
     *
     * @return String           Closing fieldset tag
     */
    public static function close_fieldset() {
        return '</fieldset>' . "\n";
    }

    /**
     * Generates a legend tag for use with a fieldset.
     *
     * @param String            $text - legend text
     * @param Array             $attr - HTML attributes
     * @param String            $xtra - a string to be attached to the end of the attributes
     * @return String           legend tag
     */
    public static function legend($text = '', $attr = [], $xtra = '') {
        return '<legend' . self::attributes($attr) . ' ' . $xtra . '>' . $text . '</legend>' . "\n";
    }

    /**
     * Creates an HTML form input tag. Defaults to a text type.
     *
     * @param string|array          $attr - input name or an array of HTML attributes
     * @param string                $value - input value, when using a name
     * @param string                $xtra - a string to be attached to the end of the attributes
     * @return string               Input tag
     */
    public static function input($attr, $value = '', $xtra = '') {
        if (!is_array($attr)) {
            $attr = array('name' => $attr);
        }

        // Type and value are required attributes
        $attr += array
            (
            'type' => 'text',
            'value' => $value
        );

        // For safe form data
        $attr['value'] = html::specialChars($attr['value']);

        return '<input' . self::attributes($attr) . ' ' . $xtra . ' />';
    }

    /**
     * Generates hidden form fields.
     * You can pass a simple key/value string or an associative array with multiple values.
     *
     * @param string|array      $attr - input name (string) or key/value pairs (array)
     * @param string            $value - input value, if using an input name
     * @return string           Hidden html input tag
     */
    public static function hidden($names, $value = '', $extra = []) {
        if (!is_array($names)) {
            $names = array($names => $value);
        }

        $input = '';
        foreach ($names as $name => $value) {
            if (is_array($value)) {
                $attr = array_merge($extra, [
                    'type' => 'hidden',
                    'name' => $name . '[]',
                ]);

                $x = 1;
                foreach ($value as $val) {
                    $attr['id'] = $name . $x;
                    $attr['value'] = $val;
                    $input .= self::input($attr);
                    $x++;
                }
            } else {
                $attr = array_merge($extra, [
                    'type' => 'hidden',
                    'name' => $name,
                    'id' => $name,
                    'value' => $value,
                ]);

                $input .= self::input($attr);
            }
        }

        return $input;
    }

    /**
     * Creates a HTML form password input tag.
     *
     * @param string                $name - input name
     * @param string                $class - class of input
     * @param array                	$attr - xtra atributes
     * @param string				$xtra - xtra string
     * @return string               Password input tag
     */
    public static function password($name, $class = '', $attr = [], $xtra = '') {
        $attr += array(
            'type' => 'password',
            'name' => $name,
            'id' => $name,
            'value' => '',
            'class' => 'input_text',
        );

        if ($class !== '') {
            $attr['class'] .= ' ' . $class;
        }

        return self::input($attr, '', $xtra);
    }

    /**
     * Creates a HTML form text input tag.
     *
     * @param string                $name - input name
     * @param string                $value - value of input
     * @param string                $class - class of input
     * @param array                	$attr  - attributes
     * @param string				$xtra - xtra string
     * @return string               text input tag
     */
    public static function text($name, $value = '', $class = '', $attr = [], $xtra = '') {
        // Make sure attr is array
        $attr = (!is_array($attr)) ? (array) $attr : $attr;

        $attr += array(
            'type' => 'text',
            'name' => $name,
            'id' => str_replace(['[', ']'], [ '_', '' ], $name),
            'class' => 'input_text'
        );

        if (!array_key_exists('maxlength', $attr)) {
            $attr['maxlength'] = '255';
        }

        if ($class !== '') {
            $attr['class'] .= ' ' . $class;
        }

        return self::input($attr, $value);
    }

    /**
     * Creates a clickable text.
     * Transform into HTML text input tag.
     *
     * @param string                $name - input name
     * @param string                $value - value of input and span
     * @param string				$callback - onblur callback
     * @param string                $class - class of span
     * @param array                	$attr  - attributes
     * @param string				$xtra - xtra string
     * @return string               text input tag
     */
    public static function clickText($name, $value = '', $callback = '', $class = '', $attr = [], $xtra = '') {
        if (!empty($value)) {
            if (isset($attr['style'])) {
                $attr['style'] .= (!empty($value)) ? "display:none;" : '';
            } else {
                $attr['style'] = (!empty($value)) ? "display:none;" : '';
            }
        }

        return self::hidden($name, $value) .
                self::text($name . '_text', $value, null, $attr) .
                "<span id=\"" . $name . "_span\" class=\"" . $class . "\" style=\"" . (empty($value) ? "display:none;" : '' ) . "cursor:pointer;\" >" .
                $value .
                "</span>" .
                "<script defer='defer'>" .
                //"$('document').ready(function(){".
                "$('#" . $name . "_span').click(function(){" .
                "$(this).hide();" .
                "$('#" . $name . "_text').val($('#" . $name . "').val());" .
                "$('#" . $name . "_text').show();" .
                //"alert($('#".$name."').val());".
                "setTimeout(\"$('#" . $name . "_text').focus()\",10);" .
                "});" .
                "$('#" . $name . "_text').blur(function(){" .
                "$('#" . $name . "').val(this.value) ;" .
                //"alert($('#".$name."').val());".
                "if(this.value){" .
                "$('#" . $name . "_span').text(this.value);" .
                "$(this).hide();" .
                "$('#" . $name . "_span').show();" .
                "}" .
                $callback .
                "});" .
                //"});".
                "</script>";
    }

    /**
     * Create ajax autocomplete with jQuery-ui widget 'autocomplete'
     * This function won't work without this plugin. Combobox css must defined in a style sheet.
     * Ajax request use GET method with 'q' param.
     *
     * @param string $name field name
     * @param string|array $value field value. When array offset 0 is text value and offset 1 is hidden value
     * @param string $url Source of results. Return as json
     * @param int $minChars minimum number of chars entered before ajax request is sent.
     * @param int $class container div class
     * @param string $callback Js function that will be executed after ajax query
     * @param array $attr input text xtra attributes
     *
     * @uses jquery-ui.js
     * @uses jquery-ui.css
     */
    public static function autocomplete($name, $value, $url, $minChars = 1, $class = '', $callback = '', $attr = []) {
        // Check if text value and hidden are the same
        if (is_array($value)) {
            $text_value = $value[0];
            $hidden_value = (isset($value[1])) ? $value[1] : $value[0];
        } else {
            $text_value = $hidden_value = $value;
        }

        // Build js
        $javascript = "<script type=\"text/javascript\">\n" .
                "$(function(){\n" .
                "document.getElementById('" . $name . "').value = '" . $hidden_value . "';\n" . // Solve caching problem
                "$('#" . $name . "_autocomplete_text').autocomplete({\n" .
                "source : '" . $url . "', \n" .
                "delay : 300, \n" .
                "minLength : " . (int) $minChars . ", \n" .
                "select : function(e, ui) { $('#" . $name . "').val(ui.item.value); }, \n" .
                "open : function(e, ui) { $(this).find('li.ui-menu-item:odd').addClass('ui-menu-item-alternate'); } \n" .
                "})\n" .
                "});" .
                "</script>";

        return "<span class='" . $class . "'>" .
                self::text($name . "_autocomplete_text", $text_value, null, $attr) .
                self::hidden(array($name => $hidden_value, $name . "_autocomplete_default_hidden" => $hidden_value, $name . "_autocomplete_default_text" => $text_value)) .
                "</span>" .
                $javascript;
    }

    /**
     * Input text for phone number.
     *
     * @param string 			$name Name and if of field.
     * @param string 			$value value.
     * @param bool				$ext with extension or not
     * @param string 			$class Css class
     * @param string 			$xtra Extra attributes
     *
     * @return string
     */
    public static function phone($name, $value = '', $ext = false, $class = '', $xtra = '') {
        $javascript = "document.getElementById('$name').value = " .
                "document.getElementById('" . $name . "_part1').value + " .
                "document.getElementById('" . $name . "_part2').value + " .
                "document.getElementById('" . $name . "_part3').value " .
                (($ext == true) ? " + document.getElementById('" . $name . "_part4').value " : '') .
                ";";

        return self::hidden($name, $value) .
                "(" . self::text($name . "_part1", substr($value, 0, 3), $class . ' input-phone1', array("onkeyup" => "javascript:if(this.value.length == 3) focusNext(this.form, this.id);", "onChange" => $javascript, "maxlength" => '3'), $xtra) . ") " .
                self::text($name . "_part2", substr($value, 3, 3), $class . ' input-phone2', array("onkeyup" => "javascript:if(this.value.length == 3) focusNext(this.form, this.id);", "onChange" => $javascript, "maxlength" => '3'), $xtra) . " - " .
                self::text($name . "_part3", substr($value, 6, 4), $class . ' input-phone3', array("onkeyup" => "javascript:if(this.value.length == 4) focusNext(this.form, this.id);", "onChange" => $javascript, "maxlength" => '4'), $xtra) .
                (($ext === true) ? " #" . self::text($name . "_part4", substr($value, 10), $class . ' input-phone4', array("onkeyup" => $javascript, "maxlength" => '10'), $xtra) : '');
    }

    /**
     * Creates an HTML form input text with color picker plugin
     *
     * @param string                $name - input name
     * @param string                $value - value of input
     * @param string                $class - class of input
     * @param array                	$attr  - attributes
     * @return string               text input tag
     */
    public static function colorPicker($name, $value = '', $class = '', $attr = []) {
        if (!empty($value)) {
            $attr['style'] = 'background-color:' . ($value[0] != '#' ? '#' : '') . $value . ';';
        }

        $class .= ' input_color';

        $javascript = "<script defer='defer'>" .
                "$('#" . $name . "').ColorPicker({" .
                "onChange: function (hsb, hex, rgb) {" .
                "$('#" . $name . "').val(hex);" .
                "$('#" . $name . "').css({'background-color' : '#' + hex });" .
                "}," .
                "onBeforeShow: function () {" .
                "$(this).ColorPickerSetColor(this.value);" .
                "}," .
                "livePreview: true" .
                "});" .
                "</script>";

        return self::text($name, $value, $class, $attr) . $javascript;
    }

    /**
     * Creates a HTML form text input tag that accepts Numeric values only
     *
     * @param string                $name - input name
     * @param string                $value - value of input
     * @param string                $class - class of input
     * @param array                	$attr  - attributes
     * @return string               text input tag
     */
    public static function numeric($name, $value = '', $class = '', $attr = []) {
        // Make sure attr is array
        $attr = (!is_array($attr)) ? (array) $attr : $attr;

        $javascript = (isset($attr['onblur']) ? $attr['onblur'] : '');

        $javascript .= "this.value = this.value.replace(',', '.'); if(isNaN(this.value)){this.value = 0};";

        $attr['onblur'] = $javascript;

        return self::text($name, $value, $class, $attr);
    }

    /**
     * Html5 input type number
     */
    public static function number($name, $value = '', $class = '', array $attr = []) {
        $attr['type'] = 'number';
        $class .= ($class ? ' input_number' : '');

        return self::text($name, $value, $class, $attr);
    }

    /**
     * html 5 email input type
     */
    public static function email($name, $value = '', $class = '', array $attr = []) {
        $attr['type'] = 'email';
        $class .= ($class ? ' input_email' : '');

        return self::text($name, $value, $class, $attr);
    }

    /**
     * html 5 url input type
     */
    public static function url($name, $value = '', $class = '', array $attr = []) {
        $attr['type'] = 'url';
        $class .= ($class ? ' input_url' : '');

        return self::text($name, $value, $class, $attr);
    }

    /**
     * html 5 search input type
     */
    public static function search($name, $value = '', $class = '', array $attr = []) {
        $attr['type'] = 'search';
        $class .= ($class ? ' input_search' : '');

        return self::text($name, $value, $class, $attr);
    }

    /**
     * Creates a HTML form text input with datePicker javascript
     *
     * @param string $name input name
     * @param string $value default value
     * @param array	$options other useful options like range. Ex : array('minDate' => '-20', 'maxDate' => '+1M +10D')
     * @param array	$attr input xtra attributes
     */
    public static function datePicker($name, $value = '', $class = '', $options = [], $attr = []) {
        $options = (array) $options;
        $attr['id'] = html::idFromName($name);

        if (empty($options['dateFormat']))
            $options['dateFormat'] = "yy-mm-dd";

        $class .= ' input_date';

        if (!isset($options['constrainInput']))
            $options['constrainInput'] = 'true';
        if (!isset($options['changeMonth']))
            $options['changeMonth'] = 'true'; // Display select for months
        if (!isset($options['changeYear']))
            $options['changeYear'] = 'true';  // Display select for years

        $javascript = "<script>" .
                '$(function(){' .
                '$("#' . html::jquerySelector($attr['id']) . '").datepicker(' . json_encode($options) . ');' .
                '});' .
                "</script>";

        return self::text($name, $value, null, $attr) . $javascript;
    }

    /**
     * Creates a HTML form text input with datetimePicker javascript
     * @param string $name input name
     * @param string $value default value
     * @param array	$options other useful options like range. Ex : array('minDate' => '-20', 'maxDate' => '+1M +10D')
     * @param array	$attr input xtra attributes
     */
    public static function datetimePicker($name, $value = '', $class = '', $options = [], $attr = []) {
        $html = '';
        $html .= self::hidden($name, $value);
        $html .= self::datePicker($name . '_part1', substr($value, 0, 10), $class, $options, $attr);
        $html .= self::time($name . '_part2', substr($value, 11, 5), $class);

        $html .= "<script defer='defer'>
						$('#" . $name . "_part1, #" . $name . "_part2').change(function(e){
							document.getElementById('" . $name . "').value = document.getElementById('" . $name . "_part1').value + ' ' + document.getElementById('" . $name . "_part2').value;
						});
					</script>";

        return $html;
    }

    /**
     * Creates 3 HTML form input used for dates. Year / month / day
     * @param string                $name - input name
     * @param string                $timestamp - timestamp or date of default value
     * @param string                $class - class of input
     *
     * @return string               3 input tag
     */
    public static function date($name, $date = null, $class = '', $attr = []) {
        return static::input([
            'name' => $name,
            'type' => 'date',
            'class' => "$class input_date",
        ] + $attr, $date ? date::timestampToDate($date, 'Y-m-d') : null);
    }

    /**
     * Create 2 HTML combo box. First representing hours and second representing minutes
     *
     * @param string $name - Input name.
     * @param string $timestamp - default timestamp of input
     * @param string $class - default class name
     */
    public static function time($name, $timestamp = null, $class = '') {
        $class .= ' input_time';
        $id = html::idFromName($name);

        if (is_long($timestamp) || strpos($timestamp, ':') !== false) {
            $timestamp = date::dateToTimestamp($timestamp);
            $h_value = date('H', $timestamp);
            $m_value = date('i', $timestamp);
        } elseif (strlen($timestamp) == 4) {
            $h_value = substr($timestamp, 0, 2);
            $m_value = substr($timestamp, 2);
        } elseif (strlen($timestamp) == 3) {
            $h_value = '0' . substr($timestamp, 0, 1);
            $m_value = substr($timestamp, 1);
        } else {
            //$h_value = date('H') ;
            //$m_value = date('i') ;
            $h_value = '';
            $m_value = '';
        }

        $attr1 = array('onchange' => "document.getElementById('" . $id . "').value = " .
            "document.getElementById('hour_" . $id . "').value + ':' + " .
            "document.getElementById('minute_" . $id . "').value ; $('#" . $id . "').trigger('change');");

        // hour combo box
        $options = array(
            '' => '',
            '00' => '0', '01' => '1', '02' => '2', '03' => '3', '04' => '4',
            '05' => '5', '06' => '6', '07' => '7', '08' => '8', '09' => '9',
            '10' => '10', '11' => '11', '12' => '12', '13' => '13', '14' => '14',
            '15' => '15', '16' => '16', '17' => '17', '18' => '18', '19' => '19',
            '20' => '20', '21' => '21', '22' => '22', '23' => '23'
        );

        // Minutes text
        $attr2 = array('maxlength' => 2, 'onclick' => 'this.select();', 'onblur' => "if(this.value < 0){this.value = '0';} if(this.value > 59){this.value = '59';} if(this.value.length == 1){this.value = '0' + this.value;}") + $attr1;

        return self::hidden($name, $h_value . ':' . $m_value) . self::dropdown('hour_' . $name, $options, $h_value, $class, $attr1) . ' : ' . self::text('minute_' . $name, $m_value, $class, $attr2);
    }

    public static function selectYear($name, $range = array(-10, 10), $value = '', $class = '', $attr = []) {
        $curr_year = date('Y');
        $options = array('' => Nex::lang('interface.-select-'));
        for ($x = ($curr_year + $range[0]); $x <= ($curr_year + $range[1]); $x++) {
            $options[$x] = $x;
        }

        return self::select($name, $options, $value, $class, $attr);
    }

    public static function selectMonth($name, $value = '', $class = '', $attr = []) {
        // Create days combo box.
        $options = array('' => Nex::lang('interface.-select-'));
        for ($x = 1; $x <= 12; $x++) {
            if (strlen($x) == 1)
                $x = '0' . $x;

            $options[$x] = date::literalMonth((int) $x);
        }

        return self::select($name, $options, $value, $class, $attr);
    }

    public static function selectDay($name, $value = '', $class = '', $attr = []) {
        $options = array('' => Nex::lang('interface.-select-'));
        for ($x = 1; $x <= 31; $x++) {
            if (strlen($x) == 1)
                $x = '0' . $x;

            $options[$x] = $x;
        }

        return self::select($name, $options, $value = '', $class = '', $attr = []);
    }

    /**
     * Creates an HTML form input text that is used to store an image source.
     * When empty, input text is diplayed, when filled with an url, input transform into an image
     * with the given url
     *
     * @param string				$name - input name
     * @param string				$value - input value
     * @param string				$callback - onblur callback
     * @param string				$class - class of image
     * @param array                	$attr  - attributes of input
     *
     * @return string				input
     */
    public static function imageInput($name, $value = '', $callback = '', $class = '', $attr = []) {
        if (!empty($value)) {
            if (isset($attr['style'])) {
                $attr['style'] .= "display:none;";
            } else {
                $attr['style'] = "display:none;";
            }
        }

        return self::hidden($name, $value) .
                self::text($name . '_text', $value, 'input_text', $attr) .
                html::image($value, null, $class, (empty($value) ? array("style" => "display:none;", "id" => $name . '_img') : array("id" => $name . '_img'))) .
                "<script defer='defer'>" .
                //"$('document').ready(function(){".
                "$('#" . $name . "_img').click(function(){" .
                "$(this).hide();" .
                "$('#" . $name . "_text').css({'background-color' : '#A0CB92'});" .
                "$('#" . $name . "_text').val($('#" . $name . "').val());" .
                "$('#" . $name . "_text').show();" .
                //"alert($('#".$name."').val());".
                "setTimeout(\"$('#" . $name . "_text').focus()\",10);" .
                "});" .
                "$('#" . $name . "_text').blur(function(){" .
                "$('#" . $name . "').val(this.value) ;" .
                //"alert($('#".$name."').val());".
                "if(this.value){" .
                "$('#" . $name . "_img').attr('src', this.value);" .
                "$(this).hide();" .
                "$('#" . $name . "_img').show();" .
                $callback .
                "}" .
                "});" .
                "$('#" . $name . "_img').error(function(){" .
                "$(this).hide();" .
                "$('#" . $name . "_text').css({'background-color' : '#F0D9CC'});" .
                "$('#" . $name . "_text').show();" .
                "});" .
                //"});".
                "</script>";
    }

    /**
     * Creates a slider HTML form input. Value is hidden
     *
     * @param string				$name - input name
     * @param string				$value - input value
     * @param int					$min - minimum value
     * @param int					$max - maximum value
     * @param int					$increment - increments
     * @param string				$class - class of image
     * @param array                	$attr  - attributes of input
     *
     * @return string				input
     */
    public static function horizontalSlider($name, $value = 0, $min = 0, $max = 100, $increment = 1, $class = 'sas-slider', $attr = []) {
        $attr += array(
            'id' => $name . '_slider',
        );

        return
                "<div class=\"" . $class . "\">" .
                self::hidden($name, $value) .
                "<span id=\"" . $name . "_slider_label\">" . $value . (($max == 100) ? '%' : '') . "</span>" .
                "<div " . html::attr($attr) . "></div>" .
                "</div>" .
                "<script defer='defer'>" .
                //"$('document').ready(function(){".
                "$('#" . $name . "_slider').slider({" .
                "range : 'min', " .
                "value : " . (int) $value . ", " .
                "min : " . (int) $min . ", " .
                "max : " . (int) $max . ", " .
                "step : " . (int) $increment . ", " .
                "slide : function(event, ui){ $('#" . $name . "').val(ui.value); $('#" . $name . "_slider_label').text(ui.value" . (($max == 100) ? " + '%'" : '') . "); }" .
                "});" .
                //"})".
                "</script>";
    }

    /**
     * Creates an HTML form upload input tag.
     *
     * @param string    $name - input name
     * @param string    $class - class of input
     * @param array     $attr - xtra atributes
     * @param string    $xtra - xtra string
     * @return string   File input tag generated
     */
    public static function file($name, $class = '', $attr = [], $xtra = '') {
        return self::input([
            'id' => $name,
            'name' => $name,
            'type' => 'file',
            'class' => "input_file $class",
        ] + $attr, '', $xtra);
    }


    /**
     * Creates an HTML form textarea tag.
     *
     * @param string                $name - input name
     * @param string                $value - value of input
     * @param string                $class - class of input
     * @param array                	$attr - xtra atributes
     * @param string				$xtra - xtra string
     * @return string               textarea tags
     */
    public static function textarea($name, $value = '', $class = '', $attr = [], $xtra = '') {
        $attr += array(
            'name' => $name,
            'id' => $name,
            'class' => 'input_textarea',
        );

        $attr['class'] .= ($class != '') ? ' ' . $class : $class;
        return '<textarea' . self::attributes($attr, 'textarea') . ' ' . $xtra . '>' . $value . '</textarea>';
    }

    /**
     * Creates an HTML form select tag, or "dropdown menu".
     *
     * @param string                $name - select name and id
     * @param array                 $options - select options, when using a name
     * @param string|array          $selected - option key that should be selected by default
     * @param string				$class - css class
     * @param string                $attr - other options
     * @param string				$xtra - xtra string
     * @return string               Select Html tags
     */
    public static function select($name, $options = NULL, $selected = NULL, $class = '', $attr = [], $xtra = '') {
        return self::dropdown($name, $options, $selected, $class, $attr, $xtra);
    }

    public static function dropdown($name, $options = NULL, $selected = NULL, $class = '', $attr = [], $xtra = '') {
        $attr += array(
            'name' => $name,
            'id' => $name,
            'class' => 'input_select',
        );

        $attr['class'] .= ($class != '') ? ' ' . $class : $class;

        $input = '<select' . self::attributes($attr, 'select') . ' ' . $xtra . ' >' . "\n";
        foreach ((array) $options as $key => $val) {
            if (is_array($val)) {
                $input .= '<optgroup label="' . $key . '">' . "\n";
                foreach ($val as $inner_key => $inner_val) {
                    // Inner key should always be a string
                    $inner_key = (string) $inner_key;

                    $sel = ((is_array($selected) && in_array($inner_key, $selected)) || $selected == $inner_key) ? ' selected="selected"' : '';
                    $input .= '<option value="' . $inner_key . '"' . $sel . '>' . $inner_val . '</option>' . "\n";
                }
                $input .= '</optgroup>' . "\n";
            } else {
                $sel = ((is_array($selected) && in_array($key, $selected)) || $selected == $key) ? ' selected="selected"' : '';
                $input .= '<option value="' . $key . '"' . $sel . '>' . $val . '</option>' . "\n";
            }
        }
        $input .= '</select>';

        return $input;
    }

    public static function selectRange($name, $min, $max, $selected = '', $class = '', $attr = []) {
        for ($x = $min; $x <= $max; $x++) {
            $options[$x] = $x;
        }

        return self::select($name, $options, $value, $class, $attr);
    }

    /**
     * Creates an HTML form checkbox input tag.
     *
     * @param string                $name - checkbox name and id
     * @param string                $value - value of checkbox
     * @param boolean               $checked - make the checkbox checked or not
     * @param string				$class - class
     * @param array                	$attr - extra attributes
     * @return string               Html checkbox
     */
    public static function checkbox($name, $value = '', $checked = false, $class = '', $attr = [], $xtra = "") {
       return static::input( ( $checked ? ['checked' => "checked"] : []) + [
           'type' => 'checkbox',
           'class' => "input_check $class",
           'name' => $name,
           'id' => $attr['id'] ?? html::idFromName($name),
       ] + $attr, $value, $xtra);
    }

    /**
     * Creates an HTML form radio input tag.
     * @param string $name - radio button name and id
     * @param string $value - value of radio button
     * @param boolean $checked - make the radio button checked or not
     * @param string $class - class of radio button
     * @param array $attr - other attributes
     * @param string $xtra - a string to be attached to the end of the attributes
     * @return string Html radio button
     */
    public static function radio($name, $value = '', $checked = false, $class = '', $attr = [], $xtra = '') {
        return static::input( ( $checked ? ['checked' => "checked"] : []) + [
            'type' => 'radio',
            'class' => "input_radio $class",
            'name' => $name,
            'id' => $attr['id'] ?? html::idFromName($name),
        ] + $attr, $value, $xtra);
    }

    /**
     * Creates an HTML form submit input tag.
     *
     * @param string                $name - submit name and id
     * @param string                $value - value of submit
     * @param string                $class - class of submit
     * @param string                $attr - array of attributes
     * @return string               Html submit
     */
    public static function submit($name, $value = '', $class = 'submit_button', $attr = []) {
        $attr += array(
            'id' => $name,
            'name' => $name,
            'class' => 'input_submit'
        );

        if ($class !== '') {
            $attr['class'] .= ' ' . $class;
        }

        $attr['type'] = 'submit';

        return self::button($name, $value, $class, $attr);
    }

    /**
     * Creates an HTML form button input tag.
     *
     * @param string                $name - button name and id
     * @param string                $value - value of button
     * @param string                $class - class of button
     * @param string				$attr - xtra attributes
     * @return string               Html button
     */
    public static function button($name, $value = '', $class = 'input_button', $attr = []) {
        $attr += array(
            'name' => $name,
            'id' => $name,
            'type' => 'button',
            'class' => 'input_button',
        );

        if ($class !== '') {
            $attr['class'] .= ' ' . $class;
        }

        $html = '<button' . self::attributes($attr) . '><span>' . $value . '</span></button>';

        return $html;
    }

    /**
     * Creates an HTML form image button input tag.
     *
     * @param string                $name - button name and id
     * @param string                $image - source of image
     * @param string                $image_over - source of image roll over
     * @param string                $class - class of button
     * @param string				$attr - xtra attributes
     * @return string               Html image button
     */
    public static function image($name, $image, $image_over = '', $class = '', $attr = []) {
        $image = Nex::skinUrl('image/' . $image);

        $attr = array(
            'name' => $name,
            'id' => $name,
            'type' => 'image',
            'src' => $image,
            'class' => 'input_image',
        );

        if (!empty($image_over)) {
            $attr['onmouseover'] = "this.src='" . Nex::skinUrl('image/' . $image_over) . "';";
            $attr['onmouseout'] = "this.src='" . $image . "';";
        }

        if ($class !== '') {
            $attr['class'] .= ' ' . $class;
        }

        return self::button($attr, '', $attr);
        //return '<button'.self::attributes($attr, 'button').' style="background:transparent none;border:none;">'.html::image($image,$image_over,$class,'',$name).'</button>';
    }

    /**
     * Closes an open form tag.
     *
     * @param string            $xtra - string to be attached after the closing tag
     * @return string
     */
    public static function close($xtra = '') {
        return '</form>' . "\n" . $xtra;
    }

    /**
     * Creates an HTML form label tag.
     *
     * @param string|array  label "for" name or an array of HTML attributes
     * @param string label text or HTML
     * @param string a string to be attached to the end of the attributes
     * @return string
     */
    public static function label($attr = '', $text = '', $xtra = '') {
        if (!is_array($attr)) {
            if (strpos($attr, '[') !== FALSE) {
                $attr = preg_replace('/\[.*\]/', '', $attr);
            }
            $attr = empty($attr) ? [] : array('for' => $attr);
        }

        return '<label' . self::attributes($attr) . ' ' . $xtra . '>' . $text . '</label>';
    }

    /**
     * Sorts a key/value array of HTML attributes, putting form attributes first,
     * and returns an attribute string.
     *
     * @param array             $attr - HTML attributes array
     * @return string           $type - type of html tag
     */
    public static function attributes($attr, $type = NULL) {
        if (empty($attr))
            return '';

        if (isset($attr['name']) AND empty($attr['id'])) { //AND strpos($attr['name'], '[') === FALSE)
            if ($type === NULL AND ! empty($attr['type'])) {
                // Set the type by the attributes
                $type = $attr['type'];
            }

            switch ($type) {
                case 'text':
                case 'textarea':
                case 'password':
                case 'select':
                case 'checkbox':
                case 'file':
                case 'image':
                case 'button':
                case 'submit':
                    // Only specific types of inputs use name to id matching
                    $attr['id'] = $attr['name'];
                    break;
            }
        }

        // Sanatize $attr['id']
        //$attr['id'] = html::idFromName($attr['id']);
        if (isset($attr['id']) && strpos($attr['id'], '[') !== FALSE) {
            $attr['id'] = preg_replace("/^(.*)\[([0-9]*)\]$/", '$1$2', $attr['id']);
        }

        $order = array
            (
            'action',
            'method',
            'type',
            'id',
            'name',
            'value',
            'src',
            'onmouseover',
            'onmouseout',
            'size',
            'maxlength',
            'rows',
            'cols',
            'accept',
            'tabindex',
            'accesskey',
            'align',
            'alt',
            'title',
            'class',
            'style',
            'selected',
            'checked',
            'readonly',
            'disabled'
        );

        $sorted = [];
        foreach ($order as $key) {
            if (isset($attr[$key])) {
                // Move the attribute to the sorted array
                $sorted[$key] = $attr[$key];

                // Remove the attribute from unsorted array
                unset($attr[$key]);
            }
        }

        // Combine the sorted and unsorted attributes and create an HTML string
        return html::attr(array_merge($sorted, $attr));
    }

}
