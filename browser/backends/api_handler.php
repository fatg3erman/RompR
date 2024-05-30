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
	logger::debug('APIHANDLER', print_r($params, true));
	$module::$method($params['params'], true);

} else {
	logger::warn('APIHANDLER', 'Bad Request', print_r($params, true));
	http_response_code(400);
}


?>