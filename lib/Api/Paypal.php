<?php

class Itremma_Nex_App_Api_Paypal
{
    public static function serviceUrl()
    {
        if ( Nex::config('api.Paypal.use_ssl') ) {
            $uri = 'https://'.Api_Paypal::host().'/cgi-bin/webscr';
        } else {
            $uri = 'http://'.Api_Paypal::host().'/cgi-bin/webscr';
        }

        return $uri ;
    }

    public static function host()
    {
        if ( Nex::config('api.Paypal.sandbox') ) return Nex::config('api.Paypal.sandbox_host');
        else return Nex::config('api.Paypal.host');
    }
}
