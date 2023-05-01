<?php
include ("includes/vars.php");
include ("includes/functions.php");

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<title>RompЯ</title>
<link rel="shortcut icon" sizes="196x196" href="newimages/favicon-196.png" />
<link rel="shortcut icon" sizes="128x128" href="newimages/favicon-128.png" />
<link rel="shortcut icon" sizes="64x64" href="newimages/favicon-64.png" />
<link rel="shortcut icon" sizes="48x48" href="newimages/favicon-48.png" />
<link rel="shortcut icon" sizes="32x32" href="newimages/favicon-32.png" />
<link rel="shortcut icon" sizes="16x16" href="newimages/favicon-16.png" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=0" />
<meta name="apple-mobile-web-app-capable" content="yes" />
<meta name="mobile-web-app-capable" content="yes" />
<link rel="stylesheet" id="theme" type="text/css" />
<?php
print '<link rel="stylesheet" type="text/css" href="get_css.php?version='.time().'&skin=desktop" />'."\n";
print '</head>';
print '<body>';

$player = new player();
// Always probe the websocket every time we load. This is a saved preference
// and it might have changed since last time we opened the page
$player->probe_websocket();


// print '<pre>';
$result = $player->api_test();
print_r($result);
// print '</pre>';


print '</body>';
print '</html>';
?>