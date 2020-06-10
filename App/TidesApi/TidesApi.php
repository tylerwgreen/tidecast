<?php
class TidesApi {

	private $credentials = null;
	
	private $baseUrl = 'https://tidesandcurrents.noaa.gov/api/datagetter';
	
	private $dateFormat = 'Ymd H:i'; // yyyyMMdd HH:mm
	
	private $baseParams = array(
		'units'			=> 'english',
		// 'datum'			=> 'CRD',	// Columbia River Datum
		// 'datum'			=> 'IGLD',	// International Great Lakes Datum
		// 'datum'			=> 'LWD',	// Great Lakes Low Water Datum (Chart Datum)
		// 'datum'			=> 'MHHW',	// Mean Higher High Water
		// 'datum'			=> 'MHW',	// Mean High Water
		// 'datum'			=> 'MTL',	// Mean Tide Level
		// 'datum'			=> 'MSL',	// Mean Sea Level
		// 'datum'			=> 'MLW',	// Mean Low Water
		'datum'			=> 'MLLW',	// Mean Lower Low Water
		// 'datum'			=> 'NAVD',	// North American Vertical Datum
		// 'datum'			=> 'STND',	// Station Datum
		'time_zone'		=> 'gmt',
		'format'		=> 'json',
		'begin_date'	=> null,
		'end_date'		=> null,
		'station'		=> null,
		'product'		=> 'predictions',
		// 'product'		=> 'datums',
		'application'	=> null,
		// 'interval'		=> 'h', // Hourly Met data and harmonic predictions will be returned
		'interval'		=> 'hilo', // High/Low tide predictions for all stations
		// 'interval'		=> '1, 6, 20, 30, 60', // Time series data will be returned
	);
	
	public function __construct(object $credentials){
		$this->credentials = $credentials;
	}
	
	// protected function request(string $path, array $params = []){
	protected function request(array $params = []){
		$ch = curl_init();
		$curlConfig = [
			CURLOPT_URL				=> $this->baseUrl . '?' . http_build_query(array_merge($this->baseParams, $params, array('application' => $this->credentials->domain))),
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
		curl_setopt_array($ch, $curlConfig);
		$result = curl_exec($ch);
		if(curl_errno($ch))
			throw new Exception(curl_error($ch));
		curl_close($ch);
		return json_decode($result);
	}
	
	public function getTides(string $stationID, string $dateBegin, string $dateEnd){
		$result = $this->request(array(
			'station'		=> $stationID,
			'begin_date'	=> date($this->dateFormat, strtotime($dateBegin)),
			'end_date'		=> date($this->dateFormat, strtotime($dateEnd)),
		));
		return $result;
	}
	
}