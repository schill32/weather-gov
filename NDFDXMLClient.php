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
	private $weatherElements = array('maxt', 'mint', 'appt', 'qpf', 'snow', 'sky', 'wdpd', 'wdir', 'wgust');
	
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
			throw new Exception('There was an error processing the XML request.', 1);
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
			$elementArray[$element->getName()] = array(
				'name' => (string) $element->name,
				'value' => (string) $element->value,
				'units' => (string) $element['units']
			);
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
