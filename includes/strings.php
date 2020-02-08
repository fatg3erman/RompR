<?php

$my_piece_of_cheese = array(
	'k' => "15f7532dff0b8d84635c757f9f18aaa3",
	's' => "3ddf4cb9937e015ca4f30296a918a2b0"
);

if (array_key_exists('getcheese', $_REQUEST)) {
	header('Content-Type: application/json; charset=utf-8');
	print json_encode($my_piece_of_cheese);
}

?>