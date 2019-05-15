<?php
/**
 * Google map class api
 * @version 1.0.0
 *
 * @update (06/12/11) [Mikael Laforge] - 1.0.0 - Script creation
 * @update (06/12/12) [ML] - 1.0.1 - Added quickStaticUrl() method
 * @update (20/05/14) [ML] - GOOGLE_MAP_API_STATICMAP is now https to fix a weird bug in mobile browser where static maps would not appear correctly
 */

define("GOOGLE_MAP_API_GEOCODE", "http://maps.googleapis.com/maps/api/geocode/json"); // Format will be replaced by xml or json
define("GOOGLE_MAP_API_STATICMAP", "https://maps.googleapis.com/maps/api/staticmap");

class Itremma_Nex_App_Api_Google_map
{
    protected $address ;
    protected $lat, $lng ;

    protected $lang ;
    protected $region ;

    /**
     * Response received by http call
     * Json decoded
     */
    protected $response = [];

    /**
     * Result model
     * @var Google_map_result_Model
     */
    protected $result ;

    public function __construct()
    {
        $this->setLang(Nex::$lang);
    }

    /**
     * Set address in class instance
     * @param string $address
     */
    public function setAddress( $address )
    {
        $this->address = $address ;
    }

    /**
     * Set map language
     * @param string $lang
     */
    public function setLang( $lang )
    {
        $this->lang = substr($lang, 0, 2);
    }

    /**
     * Set map region
     * The region code, specified as a ccTLD ("top-level domain") two-character value.
     * @param string $code
     */
    public function setRegion( $code )
    {
        $this->region = $code ;
    }

    /**
     * Latitude
     */
    public function getLatitude() { return $this->lat; }
    public function setLatitude($lat) { $this->lat = $lat; }

    /**
     * Longitude
     */
    public function getLongitude() { return $this->lng; }
    public function setLongitude($lng) { $this->lng = $lng; }

    /**
     * Form a valid address str from components
     * @param array $components valid components are name | city | address | country | zip_code | region
     */
    public function components2address( $components )
    {
        $ordered = [];
        if ( !empty($components['name']) ) $ordered[] = $components['name'] ;
        if ( !empty($components['address']) ) $ordered[] = $components['address'] ;
        if ( !empty($components['city']) ) $ordered[] = $components['city'] ;
        if ( !empty($components['region']) ) $ordered[] = $components['region'] ;
        if ( !empty($components['zip_code']) ) $ordered[] = $components['zip_code'] ;
        if ( !empty($components['country']) ) $ordered[] = $components['country'] ;

        return implode(', ', $ordered);
    }

    /**
     * Geocode address
     * see http://code.google.com/intl/fr/apis/maps/documentation/geocoding/#GeocodingRequests
     */
    public function geocode()
    {
        if ( $this->address ) {
            $url = GOOGLE_MAP_API_GEOCODE.'?address='.urlencode($this->address).
                '&language='.urlencode($this->lang).
                '&region='.urlencode($this->region).
                '&sensor=false';
        }

        $this->response = json_decode(file_get_contents($url), true);

        $this->parseGeocodeResponse($this->response);

        return $this->result ;
    }

    /**
     * Parse geocode response
     * @param array $response
     */
    public function parseGeocodeResponse($response)
    {
        $this->result = Model::factory('Api_Google_map_result');

        if ( $response['status'] == 'OK' ) {
            $this->result->load_from($response['results']);
            $this->lat = $this->result['geometry']['location']['lat'];
            $this->lng = $this->result['geometry']['location']['lng'];
        }
    }

    /**
     * Create a quick google map html
     */
    public function quickMap($height = '350px', $width = '100%')
    {
        return '<iframe style="width:'.$width.';height:'.$height.';" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" '.
                'src="http://maps.google.ca/maps?f=q&amp;source=s_q&amp;hl=fr&amp;geocode=&amp;q='.urlencode($this->address).'&amp;aq=1&amp;sll='.$this->lat.','.$this->lng.'&amp;sspn=0.008486,0.022724&amp;vpsrc=0&amp;ie=UTF8&amp;t=m&amp;spn=0.005682,0.034289&amp;output=embed&iwloc=false"></iframe>';
    }
    //<iframe width="425" height="350" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="http://maps.google.ca/maps?f=q&amp;source=s_q&amp;hl=fr&amp;geocode=&amp;q=Resto+Roberto,+Alma,+Qu%C3%A9bec&amp;aq=1&amp;sll=46.357132,-72.587479&amp;sspn=0.008486,0.022724&amp;vpsrc=0&amp;ie=UTF8&amp;hq=Resto+Roberto,&amp;hnear=Alma,+Lac-Saint-Jean-Est,+Qu%C3%A9bec&amp;t=m&amp;ll=48.543865,-71.644036&amp;spn=0.014511,0.019216&amp;output=embed"></iframe><br /><small><a href="http://maps.google.ca/maps?f=q&amp;source=embed&amp;hl=fr&amp;geocode=&amp;q=Resto+Roberto,+Alma,+Qu%C3%A9bec&amp;aq=1&amp;sll=46.357132,-72.587479&amp;sspn=0.008486,0.022724&amp;vpsrc=0&amp;ie=UTF8&amp;hq=Resto+Roberto,&amp;hnear=Alma,+Lac-Saint-Jean-Est,+Qu%C3%A9bec&amp;t=m&amp;ll=48.543865,-71.644036&amp;spn=0.014511,0.019216" style="color:#0000FF;text-align:left">Agrandir le plan</a></small>

    /**
     * Create a quick static map html
     */
    public function quickStaticMap($height = 100, $width = 100, $zoom = 14)
    {
        return html::image(Api_Google_map::quickStaticUrl($height, $width, $zoom));
    }

    public function quickStaticUrl($height = 100, $width = 100, $zoom = 14)
    {
        $url = GOOGLE_MAP_API_STATICMAP.'?zoom='.$zoom.'&size='.$width.'x'.$height.'&maptype=roadmap'.
                '&markers=color:red%7Clabel:C%7C'.$this->lat.','.$this->lng.'&sensor=false';

        return $url ;
    }
}
