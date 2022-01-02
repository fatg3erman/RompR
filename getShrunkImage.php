<?php
include ("includes/vars.php");
include ("includes/functions.php");

$url = $_REQUEST['url'];
$size = $_REQUEST['rompr_resize_size'];

$imagehandler = new imageHandler($url);
$imagehandler->output_thumbnail($size);

?>
