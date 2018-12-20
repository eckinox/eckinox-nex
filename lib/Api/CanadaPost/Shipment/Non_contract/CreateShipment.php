<?php

namespace Eckinox\Nex\Api\CanadaPost\Shipment\Non_contract;

use Eckinox\Nex\Api\CanadaPost;

class CreateShipment extends CanadaPost {

    private $XML_request_body = "";
    private $XML_results_objects = [];

    public function __construct($config, $mode = NULL) {
        parent::__construct($config, $mode);
        $this->setRequestURLSuffix('rs/');
        $this->setRequestMethod('POST');
        $this->addCurlHeader(array('Content-Type: application/vnd.cpc.ncshipment-v3+xml', 'Accept: application/vnd.cpc.ncshipment-v3+xml'));
    }

    public function initializeXML() {

        $xml = '<?xml version="1.0" encoding="UTF-8"?>
                    <non-contract-shipment xmlns="http://www.canadapost.ca/ws/ncshipment-v3">
                            <delivery-spec>
                                    <sender>
                                            <address-details></address-details>
                                    </sender>
                                    <destination>
                                            <address-details></address-details>
                                    </destination>
                                    <options>
                                            <option>
                                                    <option-code>DC</option-code>
                                            </option>
                                    </options>
                                    <parcel-characteristics></parcel-characteristics>
                                    <preferences></preferences>
                                    <references></references>
                            </delivery-spec>
                    </non-contract-shipment>';

        $this->XML_request_body = new SimpleXMLElement($xml);
    }

    public function preRequest() {
        $this->request_URL .= $this->customer_number . '/ncshipment';
        $this->setRequestBody($this->XML_request_body->asXML());
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

    public function setRequestedShippingPoint($zipcode) {
        /*
         * Required if:
         *
         * Your shipment is not picked up by Canada Post at the postal code specified in the sender 
         * structure below (i.e., it is picked up at another location), or
         *
         * You deposit your shipment at a Canada Post location, in which case you must enter the postal code of 
         * that location.  
         */

        $this->XML_request_body->addChild('requested-shipping-point', $zipcode);
    }

    public function setServiceCode($serviceCode) {
        $this->XML_request_body->{'delivery-spec'}->addChild('service-code', $serviceCode);
    }

    public function setSender($senderInfos) {

        /*

          $senderInfos is an array with these keys

          name = Contact Name of the corresponding sender. 		Optional
          company = Company name of the corresponding sender. 	Required
          contact-phone = Phone number of the sender.				Required
          address-line-1 = Address line 1 of sender. 				Required
          address-line-2 = Address line 2 of sender.				Optional
          city = City of sender.									Required
          prov-state = Province of sender. 						Required
          postal-zip-code  = Postal Code of sender. 				Conditionally Required

          Example sender XML:
          <name>John Doe</name>
          <company>Capsule Corp.</company>
          <contact-phone>1 (613) 450-5345</contact-phone>
          <address-details>
          <address-line-1>502 MAIN ST N</address-line-1>
          <city>MONTREAL</city>
          <prov-state>QC</prov-state>
          <postal-zip-code>H2B1A0</postal-zip-code>
          </address-details>
         */

        if (isset($senderInfos['name'])) {
            $name = $senderInfos['name'];
            unset($senderInfos['name']);

            $this->XML_request_body->{'delivery-spec'}->{'sender'}->addChild('name', $name);
        }

        if (isset($senderInfos['company'])) {
            $company = $senderInfos['company'];
            unset($senderInfos['company']);

            $this->XML_request_body->{'delivery-spec'}->{'sender'}->addChild('company', $company);
        }

        if (isset($senderInfos['contact-phone'])) {
            $contactPhone = $senderInfos['contact-phone'];
            unset($senderInfos['contact-phone']);

            $this->XML_request_body->{'delivery-spec'}->{'sender'}->addChild('contact-phone', $contactPhone);
        }

        $addressDetails = $this->XML_request_body->{'delivery-spec'}->{'sender'}->{'address-details'};
        foreach ($senderInfos as $k => $v) {
            $addressDetails->addChild($k, $v);
        }
    }
    
    public function setDestination($destinationInfos) {

        /*

          $senderInfos is an array with these keys

          name = Contact Name of the corresponding sender. 							Optional
          company = Company name of the corresponding sender. 						Required
          additional-address-info = Additional Address Info for the destination.		Optional
          client-voice-number = Phone number of the recipient. 						Not required for domestic
          address-line-1 = Address line 1 of sender. 									Required
          address-line-2 = Address line 2 of sender.									Optional
          city = City of sender.														Required
          prov-state = Province of sender. 											Required
          country-code = Country code of destination.									Required
          postal-zip-code  = Postal Code of sender. 									Conditionally Required

          Example sender XML:
          <name>John Doe</name>
          <company>ACME Corp</company>
          <address-details>
          <address-line-1>123 Postal Drive</address-line-1>
          <city>Ottawa</city>
          <prov-state>ON</prov-state>
          <country-code>CA</country-code>
          <postal-zip-code>K1P5Z9</postal-zip-code>
          </address-details>
         */

        if (isset($destinationInfos['name'])) {
            $name = $destinationInfos['name'];
            unset($destinationInfos['name']);

            $this->XML_request_body->{'delivery-spec'}->{'destination'}->addChild('name', $name);
        }

        if (isset($destinationInfos['company'])) {
            $company = $destinationInfos['company'];
            unset($destinationInfos['company']);

            $this->XML_request_body->{'delivery-spec'}->{'destination'}->addChild('company', $company);
        }

        if (isset($destinationInfos['additional-address-info'])) {
            $additionalAddressInfo = $destinationInfos['additional-address-info'];
            unset($destinationInfos['additional-address-info']);

            $this->XML_request_body->{'delivery-spec'}->{'destination'}->addChild('additional-address-info', $additionalAddressInfo);
        }

        if (isset($destinationInfos['client-voice-number'])) {
            $clientVoiceNumber = $destinationInfos['client-voice-number'];
            unset($destinationInfos['client-voice-number']);

            $this->XML_request_body->{'delivery-spec'}->{'destination'}->addChild('client-voice-number', $clientVoiceNumber);
        }

        $addressDetails = $this->XML_request_body->{'delivery-spec'}->{'destination'}->{'address-details'};
        foreach ($destinationInfos as $k => $v) {
            $addressDetails->addChild($k, $v);
        }
    }

    public function setShowPackingInstructions($value) {
        //This element indicates whether packing instructions are to be rendered on the label or not. (true | false)

        $this->setPreferences('show-packing-instructions', $value);
    }

    public function setPreferences($xmlkey, $value) {

        $element = $this->XML_request_body->{'delivery-spec'}->{'preferences'};

        switch ($xmlkey) {

            case 'show-packing-instructions':
                $element->addChild('show-packing-instructions', $value);
                break;

            case 'show-postage-rate':
                $element->addChild('show-postage-rate', $value);
                break;

            case 'show-insured-value':
                $element->addChild('show-insured-value', $value);
                break;
        }
    }

    public function setReferences($xmlkey, $value) {
        /*
         * This is a user-defined value available for use by your applications. 
         * (e.g. you could use this field as an internal "order id"). 
         * The value you enter here will appear on the shipping label
         */

        $element = $this->XML_request_body->{'delivery-spec'}->{'references'};

        switch ($xmlkey) {

            case 'cost-centre':
                $element->addChild('cost-centre', $value);
                break;

            case 'customer-ref-1':
                $element->addChild('customer-ref-1', $value);
                break;

            case 'customer-ref-2':
                $element->addChild('customer-ref-2', $value);
                break;
        }
    }

    public function setWeight($weight) {
        /*
         * The weight of the parcel in kilograms
         */

        $this->XML_request_body->{'delivery-spec'}->{'parcel-characteristics'}->addChild('weight', $weight);
    }

    public function setDocument($value) {
        /*
         * Indicates whether the shipment is a document or not. 
         * (If omitted or “false”, dimensions are required).
         * true | false
         */

        $this->XML_request_body->{'delivery-spec'}->{'parcel-characteristics'}->addChild('document', $value);
    }

    public function setDimensions($length, $width, $height) {
        /*
         * Details of the parcel dimensions in centimeters.
         *
         * $length = Longest dimension.
         * $width = Second longest dimension
         * $height = Shortest dimension
         */

        $dimensions = $this->XML_request_body->{'delivery-spec'}->{'parcel-characteristics'}->addChild('dimensions');

        $dimensions->addChild('length', $length);
        $dimensions->addChild('width', $width);
        $dimensions->addChild('height', $height);
    }

    public function shipment() {

        return $this->XML_results_objects ? $this->XML_results_objects[0] : NULL;
    }
}