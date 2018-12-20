<?php

class Itremma_Nex_App_Api_Recaptcha2
{
    private $google_verify_url = "https://www.google.com/recaptcha/api/siteverify";
    private $google_script_url = "https://www.google.com/recaptcha/api.js";
    private $config ; // Defined in XMLs

    protected $lang = ''; // Empty = auto
    protected $htmlOutput ;

    public function __construct()
    {
        $this->config = Nex::config('api.google.recaptcha2');
    }

    public function setLang($lang)
    {
        $this->lang = substr($lang, 0, 2);
    }

    public function isValid($response)
    {
        $url = $this->google_verify_url."?secret=".$this->config['secret']."&response=".$response;

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 5);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        $curlData = curl_exec($curl);

        curl_close($curl);

        $res = json_decode($curlData, true);

    if( $res['success'] === true || $res['success'] === 'true' )
            return true;
        else
            return false;
    }

    public function getResponseFromPost()
    {
        return isset($_POST['g-recaptcha-response']) ? $_POST['g-recaptcha-response'] : null ;
    }

    public function renderScript()
    {
        return '<script src="'.$this->google_script_url.($this->lang ? '?hl='.$this->lang : '').'" async defer></script>';
    }

    // Themes: light | dark
    public function render($theme = 'light')
    {
        $this->initRender();
        $this->htmlOutput .= '<div class="g-recaptcha" data-theme="'.$theme.'" data-sitekey="'.$this->config['site_key'].'"></div>';

        return $this->htmlOutput;
    }

    public function flushOutput()
    {
        $this->htmlOutput = '';
    }

    protected function initRender()
    {
        static $init;

        if ( !$init )
        {
            $this->htmlOutput = $this->renderScript();
        }
    }
}