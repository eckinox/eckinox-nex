<?php

namespace Eckinox\Nex\Api\CanadaPost\Shipment\Non_contract;

use Eckinox\Nex\Api\CanadaPost;

class GetArtifact extends CanadaPost {

    private $pdfFile;

    public function __construct($config, $mode = NULL) {
        parent::__construct($config, $mode);
        $this->setRequestURLSuffix('ers/artifact/' . $config['username'] . '/');
        $this->addCurlHeader(array('Accept:application/pdf'));
    }

    public function initializeXML() {}

    public function preRequest() {
        $this->request_URL .= $this->artifact;
    }

    public function processResponse() {
        $this->pdfFile = $this->getResponse();
    }

    public function setArtifact($number) {
        /*
         * Last 2 segments of the link returned from a prior call where the href contains "/ers/artifact"
         * 
         * Example : 
         * https://ct.soa-gw.canadapost.ca/ers/artifact/01474dd86fdd7f34/46048/0
         * 46048/0
         */

        $this->artifact = $number;
    }

    public function artifact() {

        return $this->pdfFile;
    }
}