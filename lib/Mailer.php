<?php

namespace Eckinox\Nex;
use Eckinox\config;

include(VENDOR_DIR . 'PHPMailer' . DIRECTORY_SEPARATOR . 'PHPMailerAutoload.php');

class Mailer extends \PHPMailer
{
    use config;

    /**
     * Constructor
     * @param boolean $exceptions Should we throw external exceptions?
     */
    public function __construct($exceptions = false)
    {
        parent::__construct($exceptions);
        $mail = $this->config('Nex.mail');

        // Set defaults
        $this->CharSet = 'UTF-8';
        $this->WordWrap = 50;
        $this->From = $this->config('Nex.mail._default.from');
        $this->FromName = $this->config('Nex.mail._default.from_name');

        $config = $this->config('Nex.mailer');
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
        ($bcc = $this->config('Nex.mail.debug')) && $this->config('Nex.system.debug.email') && $this->addBccList(explode(';', $bcc));
    }

    public function addBccList($list)
    {
        foreach ($list as $item) {
            $this->addBcc($item);
        }
    }
}
