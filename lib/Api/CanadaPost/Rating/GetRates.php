<?php

namespace Eckinox\Nex\Api\CanadaPost\Rating;

use Eckinox\Nex\Api\CanadaPost;

class GetRates extends CanadaPost {

    private $XML_request_body = "";
    private $XML_results_objects = [];

    public function __construct($config, $sandbox = NULL) {

        parent::__construct($config, $sandbox);

        $this->setRequestURLSuffix('rs/ship/price');
        $this->setRequestMethod('POST');
        $this->addCurlHeader(array('Content-Type: application/vnd.cpc.ship.rate-v2+xml', 'Accept: application/vnd.cpc.ship.rate-v2+xml'));
    }

//__construct()

    public function initializeXML() {

        $xml = '<mailing-scenario xmlns="http://www.canadapost.ca/ws/ship/rate-v2">
					<parcel-characteristics>
					</parcel-characteristics>
					<destination></destination>
				</mailing-scenario>';

        $this->XML_request_body = new SimpleXMLElement($xml);
    }

//initializeXML()

    public function preRequest() {

        $this->setRequestBody($this->XML_request_body->asXML());
    }

//preRequest()

    public function processResponse() {

        $xml = $this->getResponse();

        if ($xml->{'price-quotes'}) {

            $priceQuotes = $xml->{'price-quotes'}->children('http://www.canadapost.ca/ws/ship/rate-v2');

            if ($priceQuotes->{'price-quote'}) {
                foreach ($priceQuotes as $priceQuote) {
                    $this->XML_results_objects[] = $priceQuote;
                }
            }
        }
    }

//processResponse()

    public function setCustomerNumber($customerNumber) {

        $this->XML_request_body->addChild('customer-number', $customerNumber);
    }

//setCustomerNumber()

    public function setOriginPostalCode($zipcode) {

        $this->XML_request_body->addChild('origin-postal-code', $zipcode);
    }

//setOriginPostalCode()

    public function setDestinationPostalCode($code, $type = 'domestic') {

        switch ($type) {

            case 'united-states':
                $element = $this->XML_request_body->{'destination'}->addChild('united-states');
                $element->addChild('zip-code', $code);
                break;

            case 'international':
                $element = $this->XML_request_body->{'destination'}->addChild('international');
                $element->addChild('country-code', $code);
                break;

            case 'domestic':
            default:
                $element = $this->XML_request_body->{'destination'}->addChild('domestic');
                $element->addChild('postal-code', $code);
                break;
        }
    }

//setDestinationPostalCode()

    public function setWeight($weight) {
        /*
         * The weight of the parcel in kilograms
         */

        $this->XML_request_body->{'parcel-characteristics'}->addChild('weight', $weight);
    }

//setWeight()

    public function setDimensions($length, $width, $height) {
        /*
         * Details of the parcel dimensions in centimeters.
         *
         * $length = Longest dimension.
         * $width = Second longest dimension
         * $height = Shortest dimension
         */

        $dimensions = $this->XML_request_body->{'parcel-characteristics'}->addChild('dimensions');

        $dimensions->addChild('length', $length);
        $dimensions->addChild('width', $width);
        $dimensions->addChild('height', $height);
    }

//setDimensions()

    public function rates() {

        return $this->XML_results_objects;
    }

//rates()
}

//Canada_Post_Rating_Get_Rates
