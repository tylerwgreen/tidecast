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