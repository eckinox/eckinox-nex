<?php

namespace Eckinox\Nex\Api\CanadaPost\Rating;

use Eckinox\Nex\Api\CanadaPost;

class GetService extends CanadaPost {

    private $XML_results_objects = [];

    public function __construct($config, $sandbox = NULL) {

        parent::__construct($config, $sandbox);

        $this->setRequestURLSuffix('rs/ship/service/');
        $this->addCurlHeader(array('Accept:application/vnd.cpc.ship.rate-v2+xml'));

        $this->service = NULL;
    }

//__construct()

    protected function initializeXML() {
        
    }

//initializeXML()

    protected function processResponse() {

        $xml = $this->getResponse();

        if ($xml->{'service'}) {

            $service = $xml->{'service'}->children('http://www.canadapost.ca/ws/ship/rate-v2');

            if ($service->{'service-code'}) {
                $this->XML_results_objects[] = $service;
            }
        }
    }

//processResponse()

    protected function preRequest() {

        $this->request_URL .= $this->service;
    }

//preRequest()

    public function service() {

        return $this->XML_results_objects ? $this->XML_results_objects[0] : NULL;
    }

//service()

    public function setService($service) {

        $this->service = $service;
    }

//setService()

    public function setCountry($country) {

        $this->addQueryStringParameter('country', $country);
    }

//setQSCountry()
}

//Canada_Post_Rating_Get_Service
