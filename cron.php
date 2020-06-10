<?php
require(dirname(__FILE__) . '/App/App.php');
$message = 'Success!';
try{
	// var_dump($config);
	// var_dump($config->tideStations->Bandon);
	// $stationID = $config->tideStations->OR->Garibaldi;
	$stationID = $config->tideStations->OR->Bandon;
	$dateBegin = date('Ymd');
	$dateEnd = date('Ymd', strtotime('next month'));
// var_dump($dateBegin);
// var_dump($dateEnd);
	$data = tidesDataGet($config->tidesAPI, $stationID, $dateBegin, $dateEnd);
var_dump($data);
die();
	$stateZone = weatherDataGetStateZone();
	$weatherData = weatherDataGet(
		$config->weatherAPI,
		$stateZone
	);
	weatherDataCache($stateZone, $weatherData);
}catch(Exception $e){
	$message = $e->getMessage();
}
require(TEMPLATE_HEADER);
?>
<body id="cron">
	<p><?= $message; ?></p>
<?php require(TEMPLATE_FOOTER); ?>
