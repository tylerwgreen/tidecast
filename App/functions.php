<?php
function baseUrl(){
	return 'http://' . $_SERVER['SERVER_NAME'] . '/';
}

function loadConfig(){
	$config = file_get_contents(DIR_APP . '.config');
	$config = json_decode($config);
	return $config;
}

function errorAsExceptionHandler(int $errno, string $errstr, string $errfile, int $errline, array $errcontext){
	throw new Exception('Error (' . $errno . '): ' . $errstr . ' in ' . $errfile . ' on line ' . $errline);
}

/*
function tidesDataGet(object $credentials, string $stationID, string $dateBegin, string $dateEnd){
	$tidesApi = new TidesApi($credentials);
	$data = $tidesApi->getTides($stationID, $dateBegin, $dateEnd);
	return $data;
}

function weatherDataGet(object $credentials, string $stateZone){
	$weatherData = array();
	$zones = new Zones($credentials, $stateZone);
	foreach($zones->zones as $zoneID => $zone){
		$forecast = $zone->getForecast();
		$weatherData[$zoneID] = array(
			'forecast'				=> $forecast,
			'properties'			=> $zone->getProperties(),
			'coordinates'			=> $zone->geometryCoordinates,
			'coordinatesCentral'	=> $zone->geometryCoordinatesCentral,
		);
	}
	return $weatherData;
}

function weatherDataGetCached(){
	$stateZones = weatherDataGetStateZones();
	$weatherData = array();
	foreach($stateZones as $stateZoneID => $stateZone){
		$zoneData = json_decode(file_get_contents(WEATHER_CACHE_FILE_BASE . $stateZoneID . '.json'));
		foreach($zoneData as $zoneID => $zone){
			$weatherData[$zoneID] = $zone;
		}
	}
	return $weatherData;
}

function weatherDataCache(string $stateZone, array $data){
	file_put_contents(WEATHER_CACHE_FILE_BASE . $stateZone . '.json', json_encode($data));
}

function weatherDataGetStateZone(){
	$stateZone = !empty($_GET['state-zone']) ? strtoupper(trim($_GET['state-zone'])) : 'OR';
	$stateZones = weatherDataGetStateZones();
	if(false === array_key_exists($stateZone, $stateZones))
		throw new Exception('Invalid stateZone: ' . $stateZone);
	return $stateZone;
}

function weatherDataGetStateZones(){
	return [
		'OR' => null,
		'WA' => null,
		'ID' => null,
	];
}
*/