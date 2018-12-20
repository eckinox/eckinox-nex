<?php

namespace Eckinox\Nex;

/**
 * @author       Dave Mc Nicoll <davem@eckinoxmedia.com>
 * @version      1.0.0
 * @package      Nex
 * @subpackage   core
 * @copyright    Copyright (c) 2014
 *
 * @update (12/11/2014) [DM] 1.0 - Script creation
 *
 *
 * This class is used to wrap the external library TCPDF
 */

include(EXT_PATH . 'php' . DIRECTORY_SEPARATOR . 'tcpdf' . DIRECTORY_SEPARATOR . 'tcpdf.php');

class Pdf extends TCPDF {
    # HTML header

    protected $header = null;

    # HTML footer
    protected $footer = null;

    /**
     * This is the class constructor.
     * It allows to set up the page format, the orientation and the measure unit used in all the methods (except for the font sizes).
     * 
     * IMPORTANT: Please note that this method sets the mb_internal_encoding to ASCII, so if you are using the mbstring module 
     *            functions with TCPDF you need to correctly set/unset the mb_internal_encoding when needed.
     */
    public function __construct($config = []) {
        $config = array_replace_recursive(Nex::config('pdf'), $config);

        parent::__construct(
                $config['orientation'], $config['unit'], $config['page_format'], $config['unicode'], $config['encoding'], $config['disk_cache'], $config['pdfa_mode']
        );

        $this->SetMargins($config['margin']['left'], $config['margin']['top'], $config['margin']['right']);
        $this->setHeaderMargin($config['margin']['header']);
        $this->setFooterMargin($config['margin']['footer']);
        $this->SetAutoPageBreak(true, $config['margin']['bottom']);
        $this->SetCreator(PDF_CREATOR);

        $this->SetFont($config['font']['name'], '', $config['font']['size']);
    }

    public function setHtmlHeader($content, $w = 0, $h = 0, $x = '', $y = '', $border = 0, $ln = 1, $fill = 0, $reseth = true, $align = 'top', $autopadding = true) {
        $this->header = $this->setHtmlContent($content, $w, $h, $x, $y, $border, $ln, $fill, $reseth, $align, $autopadding);
    }

    public function setHtmlFooter($content, $w = 0, $h = 0, $x = '', $y = '', $border = 0, $ln = 1, $fill = 0, $reseth = true, $align = 'top', $autopadding = true) {
        $this->footer = $this->setHtmlContent($content, $w, $h, $x, $y, $border, $ln, $fill, $reseth, $align, $autopadding);
    }

    protected function setHtmlContent($content, $w = 0, $h = 0, $x = '', $y = '', $border = 0, $ln = 1, $fill = 0, $reseth = true, $align = 'top', $autopadding = true) {
        return array(
            'content' => $content,
            'width' => $w,
            'height' => $h,
            'x' => $x,
            'y' => $y,
            'border' => $border,
            'ln' => $ln,
            'fill' => $fill,
            'reseth' => $reseth,
            'align' => $align,
            'autopadding' => $autopadding
        );
    }

    protected function writeHtmlContent($content) {
        $this->writeHTMLCell($content['width'], $content['height'], $content['x'], $content['y'], $content['content'], $content['border'], $content['ln'], $content['fill'], $content['reseth'], $content['align'], $content['autopadding']);
    }

    /**
     * Override default function
     */
    public function Header() {
        $this->header && $this->writeHtmlContent($this->header);
    }

    /**
     * Override default function
     */
    public function Footer() {
        $this->footer && $this->writeHtmlContent($this->footer);
    }

}
