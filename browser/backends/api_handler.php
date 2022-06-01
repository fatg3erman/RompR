<?php

chdir('../..');
include ("includes/vars.php");
include ("includes/functions.php");

$params = json_decode(file_get_contents("php://input"), true);

if (
	is_array($params)
	 && array_key_exists('module', $params)
	 && array_key_exists('method', $params)
	 && array_key_exists('params', $params)
) {
	$module = $params['module'];
	$method = $params['method'];

	$module::$method($params['params'], true);

} else {
	logger::warn('APIHANDLER', 'Bad Request', print_r($params, true));
	header('HTTP/1.1 400 Bad Request');
}


?>