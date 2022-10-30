<?php
$currenthost = prefs::currenthost();
$pdef = prefs::get_player_def();
logger::log('SETUP', 'Initial currenthost is', $currenthost);
// Calling set_pref(['currenthost' => null]) sets currenthost back to the Default value
// of Default. This means that when we load and do check_setup_values() it doesn't
// get changed to what we set it to here. We need to actually clear the cookie completely
// and this is the only way we can do that.
setcookie('currenthost', '', ['expires' => 1, 'path' => '/', 'SameSite' => 'Lax']);
prefs::set_pref(['player_backend' => null]);
prefs::set_pref(['skin' => 'desktop']);
logger::log("SETUP", "Displaying Setup Screen");
print '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" '.
'"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<title>RompЯ</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="viewport" content="width=100%, initial-scale=1.0, maximum-scale=1.0, '.
'minimum-scale=1.0, user-scalable=0" />
<meta name="apple-mobile-web-app-capable" content="yes" />';
// Use a ?setupversion on the Uri to prevent vars.php from setting currenthost
print '<link rel="stylesheet" type="text/css" href="get_css.php?setupversion='.time()."&skin=".prefs::skin().'" />'."\n";
print '<link rel="stylesheet" type="text/css" href="gettheme.php?setupversion='.time().'" />'."\n";
print '<link rel="shortcut icon" sizes="196x196" href="newimages/favicon-196.png" />
<link rel="shortcut icon" sizes="128x128" href="newimages/favicon-128.png" />
<link rel="shortcut icon" sizes="64x64" href="newimages/favicon-64.png" />
<link rel="shortcut icon" sizes="48x48" href="newimages/favicon-48.png" />
<link rel="shortcut icon" sizes="32x32" href="newimages/favicon-32.png" />
<link rel="shortcut icon" sizes="16x16" href="newimages/favicon-16.png" />
<script type="text/javascript" src="jquery/jquery-3.6.0.min.js"></script>
<script type="text/javascript" src="jquery/jquery-migrate-3.3.2.min.js"></script>
<script type="text/javascript" src="ui/setupbits.js"></script>
<style>
input[type=text] { width: 50% }
input[type=submit] { width: 40% }
.styledinputs input[type="radio"] + label { display: inline !important }
</style>';
print '<script language="javascript">'."\n";
print 'var multihosts = '.json_encode(prefs::get_pref('multihosts')).";\n";
print '</script>';
print '</head>
<body class="setup" style="overflow-y:scroll">';

print '<div class="bordered setup_screen_options">
	<br /><h2>';
print $title;
print '</h2>';
if ($setup_error !== null)
		print $setup_error;
print '<p>'.language::gettext("setup_labeladdresses").'</p>';
print '<p class="tiny">'.language::gettext("setup_addressnote").'</p>';
print '<form name="mpdetails" action="index.php?force_restart=1" method="post">';
print '<hr class="setup_screen_options" />';
print '<h3>'.language::gettext("setup_mpd").'</h3>';
print '<p>Choose or edit a player</p>';
$c = 0;
foreach (prefs::get_pref('multihosts') as $host => $def) {
	print '<div class="styledinputs">';
	print '<input id="host'.$c.'" type="radio" name="currenthost" value="'.$host.'" onclick="displaySettings(event)"';
	if ($host == $currenthost) {
		print ' checked';
	}
	print '><label for="host'.$c.'">'.$host.'</label></div>';
	$c++;
}

print '<p>'.language::gettext("setup_ipaddress").'<br>';
print '<input type="text" name="mpd_host" value="'.$pdef['host'].'" /></p>';
print '<p>'.language::gettext("setup_port").'<br>';
print '<input type="text" name="mpd_port" value="'.$pdef['port'].'" /></p>';
print '<p>'.language::gettext("setup_password").'<br>';
print '<input type="text" name="mpd_password" value="'.$pdef['password'].'" /></p>';
print '<p>'.language::gettext("setup_unixsocket").'<br>';
print '<input type="text" name="unix_socket" value="'.$pdef['socket'].'" /></p>';

print '<hr class="setup_screen_options" />';
print '<h3>'.language::gettext("setup_mopidy_scan_title").'</h3>';

print '<p>'.language::gettext("label_mopidy_http").'</p>';
print '<p class="tiny">'.language::gettext("info_mopidy_http").'</p>';
print '<input type="text" name="http_port_for_mopidy" value="'.prefs::get_pref('http_port_for_mopidy').'" /></p>';

print '<div class="styledinputs"><input id="mopscan" type="checkbox" name="use_mopidy_scan" ';
if (prefs::get_pref('use_mopidy_scan')) {
	print " checked";
}
print '><label for="mopscan">'.language::gettext('setup_mopidy_scan').'</label></div>';
print '<p><a href="https://fatg3erman.github.io/RompR/Rompr-And-Mopidy#scanning-local-files" target="_blank">'.language::gettext('config_read_the_docs').'</a></p>';

print '<div class="styledinputs"><input id="spotifyunplayable" type="checkbox" name="spotify_mark_unplayable" ';
print '><label for="spotifyunplayable">Mark All Spotify Tracks as Unplayable and add them to Your Wishlist</label></div>';

print '<div class="styledinputs"><input id="mopidysearch" type="checkbox" name="use_mopidy_search" ';
if (prefs::get_pref('use_mopidy_search')) {
	print " checked";
}
print '><label for="mopidysearch">Use Mopidy HTTP interface for search</label></div>';

print '<hr class="setup_screen_options" />';
print '<h3>'.language::gettext("setup_mpd_special").'</h3>';

print '<p>'.language::gettext("label_mpd_websocket").'</p>';
print '<p class="tiny">'.language::gettext("info_mpd_websocket").'</p>';
print '<input type="text" name="mpd_websocket_port" value="'.prefs::get_pref('mpd_websocket_port').'" /></p>';
print '<p><a href="https://fatg3erman.github.io/RompR/Rompr-And-MPD" target="_blank">'.language::gettext('config_read_the_docs').'</a></p>';


print '<hr class="setup_screen_options" />';
print '<h3>'.language::gettext("label_generalsettings").'</h3>';
print '<div class="styledinputs"><input id="cli" type="checkbox" name="cleanalbumimages" ';
if (prefs::get_pref('cleanalbumimages')) {
	print " checked";
}
print '><label for="cli">Clean ununsed album art at regular intervals</label></div>';
print '<p class="tiny">You almost certainly want to keep this enabled</p>';

print '<div class="styledinputs"><input id="dsp" type="checkbox" name="do_not_show_prefs" ';
if (prefs::get_pref('do_not_show_prefs')) {
	print " checked";
}
print '><label for="dsp">Do not show preferences panel on the interface</label></div>';
print '<p class="tiny">This will stop people messing with your configuration, but also with theirs</p>';

print '<hr class="setup_screen_options" />';
print '<h3>'.language::gettext('config_google_credentials').'</h3>';
print '<p class="tiny">To use Bing Image Search you need to create an API key</p>';
print '<p><a href="https://fatg3erman.github.io/RompR/Album-Art-Manager" target="_blank">'.language::gettext('config_read_the_docs').'</a></p>';
print '<p>Bing API Key<br/>';
print '<input type="text" name="bing_api_key" value="'.prefs::get_pref('bing_api_key').'" /></p>'."\n";

print '<hr class="setup_screen_options" />';
print '<h3>Collection Settings</h3>';
print '<div class="styledinputs"><input id="dblite" type="radio" name="collection_type" value="sqlite"';
if (prefs::get_pref('collection_type') == "sqlite") {
	print " checked";
}
print '><label for="dblite">Lite Database Collection</label></div>';
print '<div class="styledinputs"><input id="dbsql" type="radio" name="collection_type" value="mysql"';
if (prefs::get_pref('collection_type') == "mysql") {
	print " checked";
}
print '><label for="dbsql">Full Database Collection</input></label>';
print '<p class="tiny">Requires MySQL Server:</p>';
print '<p>Server<br><input type="text" name="mysql_host" value="'.
	prefs::get_pref('mysql_host').'" /></p>'."\n";
print '<p>Port or UNIX Socket<br><input type="text" name="mysql_port" value="'.
	prefs::get_pref('mysql_port').'" /></p>'."\n";
print '<p>Database<br><input type="text" name="mysql_database" value="'.
	prefs::get_pref('mysql_database').'" /></p>'."\n";
print '<p>Username<br><input type="text" name="mysql_user" value="'.
	prefs::get_pref('mysql_user').'" /></p>'."\n";
print '<p>Password<br><input type="text" name="mysql_password" value="'.
	prefs::get_pref('mysql_password').'" /></p>'."\n";
print '<hr class="setup_screen_options" />';
print '<h3>Proxy Settings</h3>';
print '<p>Proxy Server (eg 192.168.3.4:8800)<br><input type="text" name="proxy_host" value="'.
	prefs::get_pref('proxy_host').'" /></p>'."\n";
print '<p>Proxy Username<br><input type="text" name="proxy_user" value="'.
	prefs::get_pref('proxy_user').'" /></p>'."\n";
print '<p>Proxy Password<br><input type="text" name="proxy_password" value="'.
	prefs::get_pref('proxy_password').'" /></p>'."\n";
print '<hr class="setup_screen_options" />';
print '<h3>Debug Logging</h3>';

print '<div class="pref containerbox drodown-container">';
print '<div class="selectholder" style="margin:auto"><select name="debug_enabled">';
foreach(array(0,1,2,3,4,5,6,7,8) as $level) {
	print '<option value="'.$level.'"';
	if ($level == prefs::get_pref('debug_enabled')) {
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
	prefs::get_pref('custom_logfile').'" /></p>';
print '<p><button style="width:50%" type="submit">OK</button></p>';
print'    </form>
	</div>
	<br />
</body>
</html>';
print "\n";
?>
