<?php

namespace Eckinox\Nex;

/**
 * @author       Mikael Laforge <mikael.laforge@gmail.com>
 * @version      1.1.0
 * @package      Nex
 * @subpackage   core
 * @copyright    Copyright (c) 2012
 *
 * @update (12/12/2011) [Mikael Laforge] 1.0 - Script creation
 * @update (15/10/2013) [ML] 1.0.1 - Now support more configs, for SMTP
 * @update (29/03/2016) [DM] 1.1.0 - Updated to match latest version of PHPMailer (5.x)
 *
 * @todo should uses drivers
 *
 * This class is used to wrap the external librairie PHPMailer
 */

include(EXT_PATH . 'php' . DIRECTORY_SEPARATOR . 'PHPMailer' . DIRECTORY_SEPARATOR . 'class.phpmailer.php');
require_once(EXT_PATH . 'php' . DIRECTORY_SEPARATOR . 'PHPMailer' . DIRECTORY_SEPARATOR . 'class.smtp.php');

class Mailer extends PHPMailer
{
    /**
     * Constructor
     * @param boolean $exceptions Should we throw external exceptions?
     */
    public function __construct($exceptions = false)
    {
        parent::__construct($exceptions);
        $mail = Nex::config('mail');

        // Set defaults
        $this->CharSet = 'UTF-8';
        $this->WordWrap = 50;
        $this->From = Nex::config('mail._default.from');
        $this->FromName = Nex::config('mail._default.from_name');

        $config = Nex::config('mailer');
        if ($config) {
            $this->Mailer = $config['method'];

            if ($this->Mailer == 'smtp') {
                $this->Host = $config['host'];
                $this->Port = $config['port'];
                $this->Username = $config['user'];
                $this->Password = $config['password'];
                $this->SMTPSecure = $config['secure_protocol'];

                if ($this->Username) {
                    $this->SMTPAuth = true;
                }
            }
        }

        // Adding BCCs
        ($bcc = Nex::config('mail.debug')) && Nex::config('system.debug.email') && $this->addBccList(explode(';', $bcc));
    }

    public function addBccList($list)
    {
        foreach ($list as $item) {
            $this->addBcc($item);
        }
    }
}