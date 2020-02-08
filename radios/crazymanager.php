<?php

switch ($_REQUEST['action']) {

	case 'get':
		$files = glob('../prefs/crazyplaylists/*.json');
		$results = array();
		foreach ($files as $file) {
			$json = json_decode(file_get_contents($file));
			array_push($results, $json);
		}
		print json_encode($results);
		break;

	case 'remove':
		$index = $_REQUEST['index'];
		$files = glob('../prefs/crazyplaylists/*.json');
		unlink($files[$index]);
		print '<html></html>';
		break;

	case 'save':
		$json = file_get_contents("php://input");
		$d = json_decode($json);
		$filename = md5($d->{'playlistname'}).'.json';
		file_put_contents('../prefs/crazyplaylists/'.$filename, $json);
		print '<html></html>';
		break;

}

?>
