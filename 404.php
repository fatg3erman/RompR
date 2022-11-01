<?php
include('includes/vars.php');
include("includes/functions.php");
$base_url = get_base_url();
$request = $_SERVER['REQUEST_URI'];
logger::info('REDIRECT','Uri is',$_SERVER['REQUEST_URI']);
if (preg_match('#prefs/userstreams/.*\.jpg#', $request) || preg_match('#prefs/userstreams/.*\.png#', $request)) {
	$redirect = $base_url.'/newimages/broadcast.svg';
	logger::log("404", "Request for missing userstream image. Redirecting to ".$redirect);
	header("HTTP/1.1 307 Temporary Redirect");
	header("Location: ".$redirect);
} else if (preg_match('#prefs/podcasts/.*\.jpg#', $request) || preg_match('#prefs/podcasts/.*\.png#', $request)) {
	$redirect = $base_url.'/newimages/podcast-logo.svg';
	logger::log("404", "Request for missing podcast image. Redirecting to ".$redirect);
	header("HTTP/1.1 307 Temporary Redirect");
	header("Location: ".$redirect);
} else if (preg_match('#themes/.*\.js#', $request)) {
	logger::trace('404', 'Request for nonexistent theme manager script. This is normal');
} else if (preg_match('#albumart/.*?\.[jpg|png|svg|gif|webp]#', $request)) {
	$redirect = $base_url.'/newimages/vinyl_record.svg';
	header("HTTP/1.1 307 Temporary Redirect");
	header("Location: ".$redirect);
} else {
	header("HTTP/1.1 404 Not Found");
	?>
	<html>
	<head>
	<link rel="stylesheet" type="text/css" href="css/layout-january.css" />
	<link rel="stylesheet" type="text/css" href="themes/Darkness.css" />
	<title>Badgers!</title>
	</head>
	<body>
	<br><br><br>
	<table align="center"><tr><td><img src="newimages/favicon-196.png"></td></tr></table>
	<h2 align="center">404 Error!</h2>
	<br><br>
	<h2 align="center">It's all gone horribly wrong</h2>
	<br><br>
	<?php
	print '<h3 align="center">The document &quot;'.$request."&quot; doesn't exist. Are you sure you know what you're doing?</h3>";
	?>
	</body>
	</html>
<?php
}
?>
