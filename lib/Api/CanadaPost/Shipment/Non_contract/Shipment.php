<?php

namespace Eckinox\Nex\Api\CanadaPost\Shipment\Non_contract;

use Eckinox\Nex\Api\CanadaPost;

class Shipment extends CanadaPost {

    private $XML_results_objects = [];

    public function __construct($config, $mode = NULL) {
        parent::__construct($config, $mode);

        $this->setRequestURLSuffix('rs/');
        $this->addCurlHeader(array('Accept:application/vnd.cpc.ncshipment-v3+xml'));
    }
    
    public function initializeXML() {}
    

    public function preRequest() {
        $this->request_URL .= $this->customer_number . '/ncshipment/';
    }

    public function processResponse() {
        $xml = $this->getResponse();

        if ($xml->{'non-contract-shipments'}) {

            $shipments = $xml->{'non-contract-shipments'}->children('http://www.canadapost.ca/ws/ncshipment-v3');

            if ($shipments->{'link'}) {
                $this->XML_results_objects[] = $shipment;
            }
        }
    }
    
    public function setCustomerNumber($customerNumber) {
        $this->customer_number = $customerNumber;
    }

    public function setShipmentID($shipmentID) {
        $this->shipment_id = $shipmentID;
    }

    public function setFrom($from) {
        $this->addQueryStringParameter('from', $from);
    }

    public function setTo($to) {
        $this->addQueryStringParameter('to', $to);
    }

    public function shipment() {
        return $this->XML_results_objects ? $this->XML_results_objects[0] : NULL;
    }
}