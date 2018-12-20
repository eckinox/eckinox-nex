<?php

namespace Eckinox\Nex\Api\CanadaPost\Rating;

use Eckinox\Nex\Api\CanadaPost;

class Discover extends CanadaPost {
    private $XML_results_objects = [];

    public function __construct($config, $sandbox = NULL) {
        parent::__construct($config, $sandbox);

        $this->setRequestURLSuffix('rs/ship/service');
        $this->addCurlHeader(array('Accept:application/vnd.cpc.ship.rate-v2+xml'));
    }

    protected function initializeXML() {
        
    }

    protected function processResponse() {
        $xml = $this->getResponse();

        if ($xml->{'services'}) {

            $services = $xml->{'services'}->children('http://www.canadapost.ca/ws/ship/rate-v2');

            if ($services->{'service'}) {
                foreach ($services->{'service'} as $service) {
                    $this->XML_results_objects[] = $service;
                }
            }
        }
    }

    public function services() {
        return $this->XML_results_objects;
    }

    public function setCountry($country) {
        $this->addQueryStringParameter('country', $country);
    }

}