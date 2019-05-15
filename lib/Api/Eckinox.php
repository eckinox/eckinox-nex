<?php

namespace Eckinox\Nex\Api;

use Eckinox\{
    config
};

use Eckinox\Nex\{
    arr,
    Model\Api\Eckinox_response
};

class Eckinox {
    use config;

    protected $api_config;

    /**
     *  Used as a debugging purpose, will store latest calls made
     * @var string
     */
    protected static $last_call = [];

    public static function make($config = []) {
        return new self($config);
    }

    public function __construct($config = []) {
        $this->api_config = array_merge($config, $this->config('Nex.api.Eckinox'));
    }


    /**
     * Performs the underlying HTTP request. Not very exciting
     * @param  string $method The API method to be called
     * @param  array  $args   Assoc array of parameters to be passed
     * @return array          Assoc array of decoded result
     */
    protected function call($method, $args=[], $user = 'cms', $timeout = 10)
    {
        $url = implode('', array($this->_get_path('url'), $this->_get_path('base'), $method));
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            "Authorization: $user ".$this->_get_config_var('auth.cms')
        ));
        curl_setopt($ch, CURLOPT_USERAGENT, $this->api_config['useragent']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_POST, true);
        # curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->verify_ssl);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($args));
        $result = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        # Checking if return result is an error or valid!
        if ( $result === null ) {
            Log::instance()->system('Eckinox_api_error.log', $result);
        }

        $result = $result ? ( json_decode($result, true) ?: [] ) : [];

        switch ($httpcode) {
            case 401:
            case 403:
                throw new Exception($result['error']);

            case 200:
                static::$last_call[] = array(
                    'url' => $url,
                    'result' => $result
                );

                return ( new Eckinox_response() )->load_from( $result ) ;
        }

        return null;
    }

    public function call_facebook($call, $page_id, $param = []) {
        return $this->call(implode('/', array(
            '', $this->_get_path('facebook'), $call, $page_id, json_encode($param)
        )));
    }

    public function call_domain($call, $param = []) {
        return $this->call(implode('/', array(
            '', $this->_get_path('domain'), $call, json_encode($param)
        )));
    }

    /**
     * Debug helper, will output every calls made from this API wrapper (for this request)
     * @return array
     */
    public function last_call() {
        return static::$last_call;
    }

    protected function _get_path($path) {
        return $this->_get_config_var('path.'.$path);
    }

    protected function _get_config_var($var) {
        return arr::get($this->api_config, $var);
    }

}
