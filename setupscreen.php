<?php
setcookie('player_backend','',1,'/');
$skin = 'desktop';
logger::log("SETUP", "Displaying Setup Screen");
print '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" '.
'"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<title>RompЯ</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="viewport" content="width=100%, initial-scale=1.0, maximum-scale=1.0, '.
'minimum-scale=1.0, user-scalable=0" />
<meta name="apple-mobile-web-app-capable" content="yes" />
<link rel="stylesheet" type="text/css" href="css/layout-january.css" />
<link rel="stylesheet" type="text/css" href="skins/'.$skin.'/skin.css?version='.ROMPR_VERSION.'" />
<link rel="shortcut icon" href="newimages/favicon.ico" />
<link rel="stylesheet" type="text/css" href="gettheme.php" />
<script type="text/javascript" src="jquery/jquery-3.6.0.min.js"></script>
<script type="text/javascript" src="jquery/jquery-migrate-3.3.2.min.js"></script>
<script type="text/javascript" src="ui/setupbits.js"></script>
<style>
input[type=text] { width: 50% }
input[type=submit] { width: 40% }
</style>';
print '<script language="javascript">'."\n";
print 'var multihosts = '.json_encode(prefs::$prefs['multihosts']).";\n";
print '</script>';
print '</head>
<body class="setup" style="overflow-y:scroll">';

print '<div class="bordered dingleberry setupdiv">
	<br /><h2>';
print $title;
print '</h2>';
if ($setup_error !== null)
		print $setup_error;
print '<p>'.language::gettext("setup_labeladdresses").'</p>';
print '<p class="tiny">'.language::gettext("setup_addressnote").'</p>';
print '<form name="mpdetails" action="index.php" method="post">';
print '<hr class="dingleberry" />';
print '<h3>'.language::gettext("setup_mpd").'</h3>';
print '<p>Choose or edit a player</p>';
$c = 0;
foreach (prefs::$prefs['multihosts'] as $host => $def) {
	print '<div class="styledinputs">';
	print '<input id="host'.$c.'" type="radio" name="currenthost" value="'.$host.'" onclick="displaySettings(event)"';
	if ($host == prefs::$prefs['currenthost']) {
		print ' checked';
	}
	print '><label for="host'.$c.'">'.$host.'</label></div>';
	$c++;
}

print '<p>'.language::gettext("setup_ipaddress").'<br>';
print '<input type="text" name="mpd_host" value="'.prefs::$prefs['multihosts'][prefs::$prefs['currenthost']]['host'].'" /></p>';
print '<p>'.language::gettext("setup_port").'<br>';
print '<input type="text" name="mpd_port" value="'.prefs::$prefs['multihosts'][prefs::$prefs['currenthost']]['port'].'" /></p>';
print '<p>'.language::gettext("setup_password").'<br>';
print '<input type="text" name="mpd_password" value="'.prefs::$prefs['multihosts'][prefs::$prefs['currenthost']]['password'].'" /></p>';
print '<p>'.language::gettext("setup_unixsocket").'<br>';
print '<input type="text" name="unix_socket" value="'.prefs::$prefs['multihosts'][prefs::$prefs['currenthost']]['socket'].'" /></p>';

print '<hr class="dingleberry" />';
print '<h3>'.language::gettext("label_mopidy_http").'</h3>';
print '<p class="tiny">'.language::gettext("info_mopidy_http").'</p>';
print '<input type="text" name="http_port_for_mopidy" value="'.prefs::$prefs['http_port_for_mopidy'].'" /></p>';

print '<hr class="dingleberry" />';
print '<h3>'.language::gettext("setup_mopidy_scan_title").'</h3>';
print '<div class="styledinputs"><input id="mopscan" type="checkbox" name="use_mopidy_scan" ';
if (prefs::$prefs['use_mopidy_scan']) {
	print " checked";
}
print '><label for="mopscan">'.language::gettext('setup_mopidy_scan').'</label></div>';
print '<p><a href="https://fatg3erman.github.io/RompR/Rompr-And-Mopidy#scanning-local-files" target="_blank">'.language::gettext('config_read_the_docs').'</a></p>';

print '<hr class="dingleberry" />';
print '<h3>'.language::gettext("label_generalsettings").'</h3>';
print '<div class="styledinputs"><input id="cli" type="checkbox" name="cleanalbumimages" ';
if (prefs::$prefs['cleanalbumimages']) {
	print " checked";
}
print '><label for="cli">Clean ununsed album art on startup</label></div>';
print '<p class="tiny">You almost certainly want to keep this enabled</p>';

print '<div class="styledinputs"><input id="dsp" type="checkbox" name="do_not_show_prefs" ';
if (prefs::$prefs['do_not_show_prefs']) {
	print " checked";
}
print '><label for="dsp">Do not show preferences panel on the interface</label></div>';
print '<p class="tiny">This will stop people messing with your configuration, but also with theirs</p>';

print '<hr class="dingleberry" />';
print '<h3>'.language::gettext('config_google_credentials').'</h3>';
print '<p class="tiny">To use Bing Image Search you need to create an API key</p>';
print '<p><a href="https://fatg3erman.github.io/RompR/Album-Art-Manager" target="_blank">'.language::gettext('config_read_the_docs').'</a></p>';
print '<p>Bing API Key<br/>';
print '<input type="text" name="bing_api_key" value="'.prefs::$prefs['bing_api_key'].'" /></p>'."\n";

print '<hr class="dingleberry" />';
print '<h3>Collection Settings</h3>';
print '<div class="styledinputs"><input id="dblite" type="radio" name="collection_type" value="sqlite"';
if (array_key_exists('collection_type', prefs::$prefs) && prefs::$prefs['collection_type'] == "sqlite") {
	print " checked";
}
print '><label for="dblite">Lite Database Collection</label></div>';
print '<div class="styledinputs"><input id="dbsql" type="radio" name="collection_type" value="mysql"';
if (array_key_exists('collection_type', prefs::$prefs) && prefs::$prefs['collection_type'] == "mysql") {
	print " checked";
}
print '><label for="dbsql">Full Database Collection</input></label>';
print '<p class="tiny">Requires MySQL Server:</p>';
print '<p>Server<br><input type="text" name="mysql_host" value="'.
	prefs::$prefs['mysql_host'].'" /></p>'."\n";
print '<p>Port or UNIX Socket<br><input type="text" name="mysql_port" value="'.
	prefs::$prefs['mysql_port'].'" /></p>'."\n";
print '<p>Database<br><input type="text" name="mysql_database" value="'.
	prefs::$prefs['mysql_database'].'" /></p>'."\n";
print '<p>Username<br><input type="text" name="mysql_user" value="'.
	prefs::$prefs['mysql_user'].'" /></p>'."\n";
print '<p>Password<br><input type="text" name="mysql_password" value="'.
	prefs::$prefs['mysql_password'].'" /></p>'."\n";
print '<hr class="dingleberry" />';
print '<h3>Proxy Settings</h3>';
print '<p>Proxy Server (eg 192.168.3.4:8800)<br><input type="text" name="proxy_host" value="'.
	prefs::$prefs['proxy_host'].'" /></p>'."\n";
print '<p>Proxy Username<br><input type="text" name="proxy_user" value="'.
	prefs::$prefs['proxy_user'].'" /></p>'."\n";
print '<p>Proxy Password<br><input type="text" name="proxy_password" value="'.
	prefs::$prefs['proxy_password'].'" /></p>'."\n";
print '<hr class="dingleberry" />';
print '<h3>Debug Logging</h3>';

print '<div class="pref containerbox drodown-container">';
print '<div class="selectholder" style="margin:auto"><select name="debug_enabled">';
foreach(array(0,1,2,3,4,5,6,7,8) as $level) {
	print '<option value="'.$level.'"';
	if ($level == prefs::$prefs['debug_enabled']) {
		print ' selected="selected"';
	}
	print '>Level '.$level.' ('.trim(logger::getLevelName($level)).')</option>';
}
print '</select></div></div>';

print '<p>Custom Log File</p>';
print '<p class=tiny>Rompr debug output will be sent to this file, but PHP error messages will
 still go to the web server error log. The web server needs write access to this file, it must
 already exist, and you should ensure it gets rotated as it will get large</p>';
print '<p><b>Development Use Only. Do not use this option when submitting bug reports</b></p>';
print '<p><input type="text" style="width:90%" name="custom_logfile" value="'.
	prefs::$prefs['custom_logfile'].'" /></p>';
print '<p><button style="width:50%" type="submit">OK</button></p>';
print'    </form>
	</div>
</body>
</html>';
print "\n";
?>
