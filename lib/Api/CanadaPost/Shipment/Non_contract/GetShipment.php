<?php

namespace Eckinox\Nex\Api\CanadaPost\Shipment\Non_contract;

use Eckinox\Nex\Api\CanadaPost;

class GetShipment extends CanadaPost {

    private $XML_results_objects = [];

    public function __construct($config, $mode = NULL) {
        parent::__construct($config, $mode);
        $this->setRequestURLSuffix('rs/');
        $this->addCurlHeader(array('Accept:application/vnd.cpc.ncshipment-v3+xml'));
    }

    public function initializeXML() {}

    public function preRequest() {
        $this->request_URL .= $this->customer_number . '/ncshipment/' . $this->shipment_id;
    }

    public function processResponse() {
        $xml = $this->getResponse();

        if ($xml->{'non-contract-shipment-info'}) {

            $shipment = $xml->{'non-contract-shipment-info'}->children('http://www.canadapost.ca/ws/ncshipment-v3');

            if ($shipment->{'shipment-id'}) {
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

    public function shipment() {
        return $this->XML_results_objects ? $this->XML_results_objects[0] : NULL;
    }
}