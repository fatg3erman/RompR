<?php

chdir('../..');
include ("includes/vars.php");
include ("includes/functions.php");

$params = json_decode(file_get_contents("php://input"), true);

$module = $params['module'];
$method = $params['method'];

$module::$method($params['params'], true);

?>