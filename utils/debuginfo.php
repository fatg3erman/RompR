<?php
chdir('..');
require_once ("includes/vars.php");
require_once ("includes/functions.php");
set_version_string();
print '<table id="debuginfotable" width="100%">';
print '<tr><th colspan="2">Backend Info</th></tr>';
print '<tr><td>Version</td><td>'.$version_string.'</td></tr>';
print '<tr><td>System</td><td>'.php_uname().'</td></tr>';
$confidential = prefs::redact_private();
foreach ($confidential as $k => $v) {
	print '<tr><td>'.$k.'</td><td>'.$v.'</td></tr>';
}

print '<tr><th colspan="2">Server Info</th></tr>';
foreach (array('PHP_SELF', 'SERVER_ADDR', 'SERVER_NAME', 'SERVER_SOFTWARE', 'DOCUMENT_ROOT',
	'HTTP_HOST', 'HTTP_USER_AGENT', 'REMOTE_ADDR', 'REMOTE_HOST', 'SERVER_PORT', 'REQUEST_URI',
) as $k) {
	if (array_key_exists($k, $_SERVER)) {
		print '<tr><td>'.$k.'</td><td>'.$_SERVER[$k].'</td></tr>';
	}
}

print '<tr><th colspan="2">Cookies</th></tr>';
foreach ($_COOKIE as $i => $v) {
	print '<tr><td>'.$i.'</td><td>'.$v.'</td></tr>';
}

print '<tr><th colspan="2">PHP Info</th></tr>';
print '<tr><td>Version</td><td>'.phpversion().'</td></tr>';
print '<tr><td>mbstring</td><td>'.phpversion('mbstring').'</td></tr>';
print '<tr><td>PDO</td><td>'.phpversion('PDO').'</td></tr>';
print '<tr><td>pdo_mysql</td><td>'.phpversion('pdo_mysql').'</td></tr>';
print '<tr><td>pdo_sqlite</td><td>'.phpversion('pdo_sqlite').'</td></tr>';
print '<tr><td>curl</td><td>'.phpversion('curl').'</td></tr>';
print '<tr><td>date</td><td>'.phpversion('date').'</td></tr>';
print '<tr><td>fileinfo</td><td>'.phpversion('fileinfo').'</td></tr>';
print '<tr><td>json</td><td>'.phpversion('json').'</td></tr>';
print '<tr><td>SimpleXML</td><td>'.phpversion('SimpleXML').'</td></tr>';
print '<tr><td>GD</td><td>'.phpversion('GD').'</td></tr>';
if (extension_loaded('gd')) {
	$gdinfo = gd_info();
	print '<tr><td>GD Info</td><td>'.multi_implode($gdinfo).'</td></tr>';
}
$convert_path = find_executable('convert');
if ($convert_path === false) {
	print '<tr><td>ImageMagick</td><td>Not Installed</td></tr>';
} else {
	print '<tr><td>ImageMagick</td><td>Installed</td></tr>';
}

$php_values = array(
	'date.timezone',
	'default_charset',
	'default_socket_timeout',
	'display_errors',
	'error_log',
	'error_reporting',
	'file_uploads',
	'log_errors',
	'max_execution_time',
	'mbstring.language',
	'memory_limit',
	'pdo_mysql.default_socket',
	'session.use_cookies',
	'upload_tmp_dir'
);

$pi = ini_get_all();

foreach ($php_values as $v) {
	$t = '[NOT SET]';
	if (array_key_exists($v, $pi)) {
		$t = $pi[$v];
	}
	print '<tr><td>'.$v.'</td><td>'.multi_implode($t).'</td></tr>';
}

// exec('ps aux | grep php | grep -v grep', $output);
$pwd = getcwd();
exec('ps aux | grep '.$pwd.' | grep -v grep', $output);
print '<tr><th colspan="2">Relevant Running Processes</th></tr>';
print'<tr><td></td><td class="code">';
foreach ($output as $line) {
	print $line."\n";
}
print '</td></tr>';

$player = new player();
print '<tr><th colspan="2">Player Information</th></tr>';
if ($player->is_connected()) {
	print '<tr><td>Connection Status</td><td>Connection Successful</td></tr>';

	print '<tr><td>MPD Interface Version</td><td>'.$player->get_mpd_version().'</td></tr>';

	$config = $player->get_config();
	print '<tr><td>config</td><td>'.prepare_info($config).'</td></tr>';

	$tagtypes = $player->get_tagtypes();
	print '<tr><td>tagtypes</td><td>'.prepare_info($tagtypes).'</td></tr>';

	$commands = $player->get_commands();
	print '<tr><td>commands</td><td>'.prepare_info($commands).'</td></tr>';

	$commands = $player->get_notcommands();
	print '<tr><td>notcommands</td><td>'.prepare_info($commands).'</td></tr>';

	$commands = $player->get_uri_handlers();
	print '<tr><td>urlhandlers</td><td>'.prepare_info($commands).'</td></tr>';

	$commands = $player->get_decoders();
	print '<tr><td>decoders</td><td>'.prepare_info($commands).'</td></tr>';
} else {
	print '<tr><td>Connection Status</td><td>Connection Failed</td></tr>';
}

print '</table>';

function prepare_info($info) {
	$a = json_encode($info);
	return preg_replace('/,/', ', ', $a);
}

?>

