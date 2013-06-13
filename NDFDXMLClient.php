<?php

namespace Weather\Library;

/**
 * Nation Digital Forecast Database client, which parses various
 * XML feeds provided via their REST API web service.
 * 
 * Forecast data elements: http://graphical.weather.gov/xml/docs/elementInputNames.php
 */
class NDFDXMLClient
{
	private $baseURL = 'http://graphical.weather.gov/xml/sample_products/browser_interface/ndfdXMLclient.php';
	
        private $weatherElements = array(
            'maxt'  => true,        // max temp
            'mint'  => true,        // low temp
            'temp'  => true,        // current temp
            'dew'   => false,       // dew point
            'pop12' => true,        // 12 hr probability of precipitation
            'qpf'   => true,        // liquid precipitation amount
            'sky'   => true,        // cloud cover amount
            'snow'  => false,       // snowfall amount
            'wspd'  => true,        // wind speed
            'wdir'  => true,        // wind direction
            'wx'    => true,        // weather
            'waveh' => false,       // wave height
            'icons' => true,        // weather icons
            'rh'    => true,        // relative humidity
            'appt'  => true,        // apparent temperature
            'incw34'=> false,       // Probabilistic Tropical Cyclone Wind Speed >34 Knots (Incremental) 
            'incw50'=> false,       // Probabilistic Tropical Cyclone Wind Speed >50 Knots (Incremental) 
            'incw64'=> false,       // Probabilistic Tropical Cyclone Wind Speed >64 Knots (Incremental)
            'cumw34'=> false,       // Probabilistic Tropical Cyclone Wind Speed >34 Knots (Cumulative) 
            'cumw50'=> false,       // Probabilistic Tropical Cyclone Wind Speed >50 Knots (Cumulative) 
            'cumw64'=> false,       // Probabilistic Tropical Cyclone Wind Speed >64 Knots (Cumulative) 
            'critfireo' => false,   // Fire Weather from Wind and Relative Humidity 
            'dryfireo' => false,    // Fire Weather from Dry Thunderstorms 
            'conhazo' => true,      // Convective Hazard Outlook 
            'ptornado' => true,     // Probability of Tornadoes 
            'phail' => true,        // Probability of Hail 
            'ptstmwinds' => true,   // Probability of Damaging Thunderstorm Winds 
            'pxtornado' => true,    // Probability of Extreme Tornadoes 
            'pxhail' => true,       // Probability of Extreme Hail 
            'pxtstmwinds'=> true,   // Probability of Extreme Thunderstorm Winds 
            'ptotsvrtstm'=> true,   // Probability of Severe Thunderstorms 
            'pxtotsvrtstm'=>true,   // Probability of Extreme Severe Thunderstorms 
            'tmpabv14d' => false,   // Probability of 8- To 14-Day Average Temperature Above Normal
            'tmpblw14d' => false,   // Probability of 8- To 14-Day Average Temperature Below Normal 
            'tmpabv30d' => false,   // Probability of One-Month Average Temperature Above Normal 
            'tmpblw30d' => false,   // Probability of One-Month Average Temperature Below Normal 
            'tmpabv90d' => false,   // Probability of Three-Month Average Temperature Above Normal 
            'tmpblw90d' => false,   // Probability of Three-Month Average Temperature Below Normal 
            'prcpabv14d'=> false,   // Probability of 8- To 14-Day Total Precipitation Above Median 
            'prcpblw14d'=> false,   // Probability of 8- To 14-Day Total Precipitation Below Median 
            'prcpabv30d'=> false,   // Probability of One-Month Total Precipitation Above Median 
            'prcpblw30d'=> false,   // Probability of One-Month Total Precipitation Below Median 
            'prcpabv90d'=> false,   // Probability of Three-Month Total Precipitation Above Median 
            'prcpblw90d'=> false,   // Probability of Three-Month Total Precipitation Below Median 
            'precipa_r'=> false,    // Real-time Mesoscale Analysis Precipitation 
            'sky_r' => false,       // Real-time Mesoscale Analysis GOES Effective Cloud Amount 
            'td_r' => false,        // Real-time Mesoscale Analysis Dewpoint Temperature 
            'temp_r' => false,      // Real-time Mesoscale Analysis Temperature 
            'wdir_r' => false,      // Real-time Mesoscale Analysis Wind Direction 
            'wspd_r' => false,      // Real-time Mesoscale Analysis Wind Speed 
            'wwa' => true,          // Watches, Warnings, and Advisories 
            'tstmprb' => false,     // Unknown
            'tstmcat' => false,     // Unknown
            'wgust' => true,        // Wind Gust
            'iceaccum' => false,    // Ice Accumulation
        );
	
	private $zipcode, $time;
	
	private $lat, $lon;
	
	private $startTime, $endTime;
	
	private $format;
	
	/**
	 * @param $zipcode int 5 digit zipcode
	 * @param $time int Unix timestamp
	 * @param $options mixed
	 */
	public function __construct($zipcode, $time, $options = array())
	{	
		if (preg_match('/[0-9]{5}/', $zipcode)) {
			$this->zipcode = $zipcode;
			$this->time = $time;
			
			$this->startTime = $time;
			$this->endTime = $time+3600;
			
			self::setLatLon();
			
			if (!empty($options['format'])) {
				$this->format = $options['format'];
			}
				
		} else {
			throw new Exception('Invalid zipcode', 1);
		}
	}
	
	/**
	 * Set lat/lon points, which are derived from the user's zipcode.
	 */
	public function setLatLon()
	{
		$query = 'listZipCodeList='.$this->zipcode;
		
		$url = $this->baseURL.'?'.$query;
		
		$xml = self::retrieveXML($url);
		
		$points = preg_split('[,]', $xml->latLonList);
					
		$this->lat = $points[0];
		$this->lon = $points[1];		
	}
	
	/**
	 * Retrieve weather information on a single set of points
	 */
	public function getSinglePoint()
	{
		// Build weather element part of query
		$dataArray = array();
		
		foreach ($this->weatherElements as $value) {
			$dataArray[$value] = $value;
		}
	
		// Add in lat/lon
		$dataArray['lat'] = $this->lat;
		$dataArray['lon'] = $this->lon;
		
		// Build time-series
		$dataArray['product'] = 'time-series';
		$dataArray['begin'] = gmdate("c", $this->startTime);
		$dataArray['end'] = gmdate("c", $this->endTime);
	
		$query = http_build_query($dataArray);
		
		$url = $this->baseURL.'?'.$query;
		$xml = self::retrieveXML($url);
		
		$format = strtoupper($this->format);
		$method = 'get'.$format.'Format';
		
		if (method_exists($this, $method)) {
			$data = call_user_func('self::'.$method, $xml);
		} else {
			$data = self::getJSONFormat($xml);
		}
		
		return $data;
	}	
	
	/**
	 * Retrieve XML from weather.gov
	 * //TODO Check for XML error message codes
	 */
	protected function retrieveXML($url)
	{
		$xml = @simplexml_load_file($url);
		
		if ($xml) {
			return $xml;
		} else {
			throw new \Exception('There was an error processing the XML request.', 1);
		}
	}

	/**
	 * Return weather information in JSON format
	 */
	protected function getJSONFormat($xml)
	{
		$elementArray = array();
		$elementArray['lat-lon'] = $this->lat.','.$this->lon;
		foreach ($xml->data->parameters[0] as $element) {
                    // Reviewed schema for non-standard types
                    // http://graphical.weather.gov/xml/DWMLgen/schema/parameters.xsd
                    if ($element->getName() == "convective-hazard") {
                        $sevComponent = $element->{'severe-component'};
                        if ($sevComponent->getName() == '') continue;
                        $elementArray[$element->getName()][(string)$sevComponent['type']] = array(
                            "name" => (string) $sevComponent->name,
                            "value" => (string) $sevComponent->value,
                            "units" => (string) $sevComponent['units']
                        );
                    }
                    elseif ($element->getName() == "climate-anomaly") {
                        $children = $element->children();
                        $child = $children[0];
                        $elementArray[$element->getName()][$child->getName()][(string)$child['type']] = array(
                            "name" => (string) $child->name,
                            "value" => (string) $child->value,
                            "units" => (string) $child['units']
                        );
                    }
                    else {
                        $newElem = array();
                        $newElem['name'] = (string) $element->name;

                        if ($element->getName() == "hazards") {
                            $conditions = $element->{'hazard-conditions'};
                            foreach ($conditions as $hazardCondition) {
                                if ($hazardCondition->count() == 0)
                                    continue;
                                $hazCondition = array(
                                    "hazardCode" => (string)$hazardCondition->hazard['hazardCode'],
                                    "phenomena" => (string)$hazardCondition->hazard['phenomena'],
                                    "significance" => (string)$hazardCondition->hazard['significance'],
                                    "hazardType" => (string)$hazardCondition->hazard['hazardType'],
//                                    "eventTrackingNumber" => (int) $hazardCondition['eventTrackingNumber'],
//                                    "headline" => (string)$hazardCondition['headline'],
                                    "hazardTextURL" => (string)$hazardCondition->hazard->hazardTextURL,
                                    "hazardIcon" => (string)$hazardCondition->hazard->hazardIcon
                                );
                                $newElem[$hazardCondition->getName()][] = $hazCondition;
                            }
                        }
                        elseif ($element->getName() == "conditions-icon") {
                            $newElem['icon-link'] = $element->{'icon-link'};
                        }
                        else {
                            $newElem['value'] = (string) $element->value;
                            if (is_array($element) && in_array('units', $element))
                                $newElem['units'] = (string) $element['units'];
                        }
                        $elementArray[$element->getName()] = $newElem;
                    }
		}
		
		return json_encode($elementArray);
	}
	
	/**
	 * Return weather information in XML format
	 */
	protected function getXMLFormat($xml)
	{
	}		
}
