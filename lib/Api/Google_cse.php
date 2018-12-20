<?php
/**
 * Google cse class api
 * @version 1.0.0
 */

class Itremma_Nex_App_Api_Google_cse
{
    protected $lang ;
    protected $region ;

    /**
     * Response received by http call
     * Json decoded
     */
    protected $response = [];

    /**
     * Result model
     * @var Google_map_result_Model
     */
    protected $result ;

    public function __construct()
    {
        $this->setLang(Nex::$lang);
    }

    /**
     * Set map language
     * @param string $lang
     */
    public function setLang( $lang )
    {
        $this->lang = substr($lang, 0, 2);
    }

    /**
     * Set map region
     * The region code, specified as a ccTLD ("top-level domain") two-character value.
     * @param string $code
     */
    public function setRegion( $code )
    {
        $this->region = $code ;
    }

    public static function serviceUrl($type = '')
    {
        $url = Nex::config('api.google.cse.url');

        switch($type)
        {
            case 'server': $url = url::addParam($url, array(
                    'key' => Nex::config('api.google.api.server_key'),
                    'cx' => Nex::config('api.google.cse.id'))
                );
                break;
            case 'client': $url = url::addParam($url, array(
                    'key' => Nex::config('api.google.api.client_key'),
                    'cx' => Nex::config('api.google.cse.id'))
                );
                break;
        }

        return $url ;
    }
}
