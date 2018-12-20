<?php

namespace Eckinox\Nex\Api\Amazon;

/**
 *  Simple Cloudfront API to create-share protected cookie.
 * 
 */

class Cloudfront {
    protected $keypair = null;
    protected $keycontent = null;
    protected $keyfile = null;
    
    /**
     * Create a new instance
     * @param string $api_key Your Cloudfront API key
     */
    public function __construct($keypair = null, $keyfile = null) {
        $this->keypair = $keypair ?: Nex::config('api.amazon.cloudfront.keypair');
        
        # If no cloudfront keyfile is given (in project's root directory), we load content from CDATA in config.
        if ( !$this->keyfile  = $keyfile ?: Nex::config('api.amazon.cloudfront.keyfile') ) {
            $this->keycontent = Nex::config('api.amazon.cloudfront.keycontent');
        }
        else {
            // Read Cloudfront Private Key Pair
            $fp = fopen(DOC_ROOT.$this->keyfile,"r"); 
            $this->keycontent = fread($fp, 8192); 
            fclose($fp); 
        }
    }
    
    public function getSignedURL($resource, $timeout) {
        $expires = time() + $timeout; //Time out in seconds
        $json = '{"Statement":[{"Resource":"'.$resource.'","Condition":{"DateLessThan":{"AWS:EpochTime":'.$expires.'}}}]}';		

        //Create the private key
        $key = openssl_get_privatekey($this->keycontent);
        
        if ( !$key ) {
            echo "<p>Failed to load Amazon's Cloudfront private key!</p>";
            return;
        }
        
        //Sign the policy with the private key
        if ( !openssl_sign($json, $signed_policy, $key, OPENSSL_ALGO_SHA1) ) {
            echo '<p>Failed to sign policy: '.openssl_error_string().'</p>';
            return;
        }
        
        //Create url safe signed policy
        $base64_signed_policy = base64_encode($signed_policy);
        $signature = str_replace(array('+','=','/'), array('-','_','~'), $base64_signed_policy);

        return $resource.'?Expires='.$expires.'&Signature='.$signature.'&Key-Pair-Id='.$this->keypair;;
    }
}
