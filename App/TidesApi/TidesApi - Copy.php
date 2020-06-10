<?php
class TidesApi {

	private $credentials = null;
	
	// private $baseUrl = 'https://tidesandcurrents.noaa.gov/api/datagetter';
	private $baseUrl = 'http://tidesandcurrents.noaa.gov/mdapi/v1.0/webapi/stations.json';
	
	private $dateFormat = 'Ymd H:i'; // yyyyMMdd HH:mm
	
	private $baseParams = array(
		'type'			=> 'tidepredictions',
		// 'units'			=> 'english',
		// 'datum'			=> 'STND', // Station Datum
		// 'datum'			=> 'MHW', // Station Datum
		// 'time_zone'		=> 'gmt',
		// 'format'		=> 'json',
		// 'begin_date'	=> null,
		// 'end_date'		=> null,
		// 'station'		=> null,
		// 'product'		=> 'predictions',
		// 'product'		=> 'datums',
		// 'application'	=> null,
		// 'interval'		=> 'h', // Hourly Met data and harmonic predictions will be returned
		// 'interval'		=> 'hilo', // High/Low tide predictions for all stations
		// 'interval'		=> '1, 6, 20, 30, 60', // Time series data will be returned
	);
	
	public function __construct(object $credentials){
		$this->credentials = $credentials;
	}
	
	// protected function request(string $path, array $params = []){
	protected function request(array $params = []){
		$ch = curl_init();
		$curlConfig = [
			// CURLOPT_URL				=> $this->baseUrl . '?' . http_build_query(array_merge($this->baseParams, $params, array('application' => $this->credentials->domain))),
			// CURLOPT_URL				=> $this->baseUrl . '?' . http_build_query(array_merge($this->baseParams, $params)),
			// CURLOPT_URL				=> 'http://tidesandcurrents.noaa.gov/mdapi/v1.0/webapi/stations.json?type=currents',
			// CURLOPT_URL				=> 'https://tidesandcurrents.noaa.gov/mdapi/latest/webapi/stations.json?type=tidepredictions',
			CURLOPT_URL				=> 'https://tidesandcurrents.noaa.gov/mdapi/latest/webapi/stations/9432373.json?type=tidepredictions',
			// CURLOPT_POST           => true,
			CURLOPT_RETURNTRANSFER	=> true,
			// CURLOPT_POSTFIELDS     => array(
				// 'field1' => 'some date',
				// 'field2' => 'some other data',
			// )
			CURLOPT_HTTPHEADER		=> [
				'Accept: application/json',
				'Content-Type: text/xml; charset=utf-8',
				'Access-Control-Allow-*',
				'User-Agent: (' . $this->credentials->domain . ', ' . $this->credentials->email . ')',
			],
		];
// var_dump($curlConfig);
		curl_setopt_array($ch, $curlConfig);
		$result = curl_exec($ch);
// var_dump($result);
print_r($result);
die();
		if(curl_errno($ch))
			throw new Exception(curl_error($ch));
		curl_close($ch);
		return json_decode($result);
	}
	
	public function getTides(string $stationID){
		$result = $this->request(array(
			// 'station'		=> $stationID,
			// 'begin_date'	=> date($this->dateFormat),
			// 'end_date'		=> date($this->dateFormat, strtotime('tomorrow')),
		));
	}
	
}

/*
class Zones extends WeatherApi {

	private $credentials = null;
	private $area = null;
	public $zones = [];

	public function __construct(object $credentials, string $area){
		parent::__construct($credentials);
		$this->credentials = $credentials;
		$this->area = $area;
		$this->getZonesForArea();
	}
	
	private function getZonesForArea(){
		$zones = $this->request('zones', [
			'area'				=> $this->area,
			'type'				=> 'forecast',
			'include_geometry'	=> 'false',
		]);
		$zonesSimple = [];
		foreach($zones->features as $feature){
			$this->zones[$feature->properties->id] = new Zone($this->credentials, $feature);
		}
	}

}

class Zone extends WeatherApi {

	private $credentials = null;
	public $geometryCoordinates = null;
	public $geometryCoordinatesCentral = null;
	public $geometryCoordinatesFirst = null;
	public $hasFog = false;
	public $hasThunder = false;
	public $hasSnow = false;
	public $hasRain = false;
	public $feature = null;
	private $forecast = null;

	public function __construct(object $credentials, object $feature){
		parent::__construct($credentials);
		$this->credentials = $credentials;
		$this->feature = $feature;
		$this->setGeometryCoordinates();
		$this->setGeometryCoordinatesCentral();
		$this->setGeometryCoordinatesFirst();
		$this->setForecast();
	}

	private function setGeometryCoordinates(){
		$coordinates = array();
		foreach($this->feature->geometry->coordinates as $a){
			$coordinates[] = $a[0];
		}
		$this->geometryCoordinates = $coordinates;
	}
	
	private function setGeometryCoordinatesCentral(){
		foreach($this->feature->geometry->coordinates as $a){
			foreach($a[0] as $b){
				$centralCoordinates['x'][] = $b[1];
				$centralCoordinates['y'][] = $b[0];
			}
		}
		$centralCoordinates['x'] = array_sum($centralCoordinates['x']) / count($centralCoordinates['x']);
		$centralCoordinates['y'] = array_sum($centralCoordinates['y']) / count($centralCoordinates['y']);
		$this->geometryCoordinatesCentral = (object) $centralCoordinates;
	}

	private function setGeometryCoordinatesFirst(){
		$this->geometryCoordinatesFirst = (object) [
			'x' => $this->feature->geometry->coordinates[0][0][0][1],
			'y' => $this->feature->geometry->coordinates[0][0][0][0],
		];
	}
	
	private function setForecast(){
		$this->forecast = $this->request('zones/forecast/' . $this->feature->properties->id . '/forecast');
	}
	
	public function getForecast(){
		$forecast = [
			'updated' => strtotime($this->forecast->updated),
			'updatedReadable' => date('D, M j \a\t g:i A', strtotime($this->forecast->updated)),
			'periods' => [],
		];
		foreach($this->forecast->periods as $period){
			$forecast['periods'][] = (object) [
				'name'		=> $period->name,
				'fog'		=> $this->forecastHasFog($period->detailedForecast),
				'thunder'	=> $this->forecastHasThunder($period->detailedForecast),
				'snow'		=> $this->forecastHasSnow($period->detailedForecast),
				'rain'		=> $this->forecastHasRain($period->detailedForecast),
				'forecast'	=> $period->detailedForecast,
			];
		}
		$forecast['fog']		= $this->hasFog;
		$forecast['thunder']	= $this->hasThunder;
		$forecast['snow']		= $this->hasSnow;
		$forecast['rain']		= $this->hasRain;
		return (object) $forecast;
	}
	
	public function getProperties(){
		$properties = $this->feature->properties;
		unset($properties->{'@id'});
		unset($properties->{'@type'});
		unset($properties->id);
		// unset($properties->name);
		unset($properties->type);
		// unset($properties->state);
		unset($properties->cwa);
		unset($properties->forecastOffices);
		$properties->timeZone = $properties->timeZone[0];
		return $properties;
	}
	
	private function forecastHasFog(string $forecast){
		$hasFog = stristr($forecast, 'fog') ? true : false;
		if(true === $hasFog)
			$this->hasFog = true;
		return $hasFog;
	}
	
	private function forecastHasThunder(string $forecast){
		$hasThunder = stristr($forecast, 'thunder') ? true : false;
		if(true === $hasThunder)
			$this->hasThunder = true;
		return $hasThunder;
	}
	
	private function forecastHasSnow(string $forecast){
		$hasSnow = stristr($forecast, 'snow') ? true : false;
		if(true === $hasSnow)
			$this->hasSnow = true;
		return $hasSnow;
	}
	
	private function forecastHasRain(string $forecast){
		$hasRain = stristr($forecast, 'rain') ? true : false;
		if(true === $hasRain)
			$this->hasRain = true;
		return $hasRain;
	}

}
*/