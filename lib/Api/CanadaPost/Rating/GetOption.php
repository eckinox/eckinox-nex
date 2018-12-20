<?php

namespace Eckinox\Nex\Api\CanadaPost\Rating;

use Eckinox\Nex\Api\CanadaPost;

class GetOption extends CanadaPost {

    private $XML_results_objects = [];

    public function __construct($config, $sandbox = NULL) {

        parent::__construct($config, $sandbox);

        $this->setRequestURLSuffix('rs/ship/option/');
        $this->addCurlHeader(array('Accept:application/vnd.cpc.ship.rate-v2+xml'));

        $this->option = NULL;
    }

//__construct()

    protected function initializeXML() {
        
    }

//initializeXML()

    protected function processResponse() {

        $xml = $this->getResponse();

        if ($xml->{'option'}) {
            $option = $xml->{'option'}->children('http://www.canadapost.ca/ws/ship/rate-v2');
            if ($option->{'option-code'}) {

                $this->XML_results_objects[] = $option;
            }
        }
    }

//processResponse()

    protected function preRequest() {

        $this->request_URL .= $this->option;
    }

//preRequest()

    public function option() {

        return $this->XML_results_objects ? $this->XML_results_objects[0] : NULL;
    }

//option()

    public function setOption($option) {

        $this->option = $option;
    }

//setOption()
}

//Canada_Post_Rating_Get_Option
