<?php
require(dirname(__FILE__) . '/App/App.php');
try{
	$calendar = new TideCalendar($config->tidesAPI, $config->tideStations);
}catch(Exception $e){
	die($e->getMessage());
}
require(TEMPLATE_HEADER);
?>
<body id="index">
	<?= $calendar->show(); ?>
<?php require(TEMPLATE_FOOTER); ?>