<?php
chdir('..');
include("includes/vars.php");
include("includes/functions.php");
include("player/mpd/sockets.php");
set_version_string();
print '<table id="debuginfotable" width="100%">';
print '<tr><th colspan="2">Backend Info</th></tr>';
print '<tr><td>Version</td><td>'.$version_string.'</td></tr>';
foreach ($private_prefs as $p) {
    if (array_key_exists($p, $prefs) && $prefs[$p] != '') {
        $prefs[$p] = '[Redacted]';
    }
    print '<tr><td>'.$p.'</td><td>'.$prefs[$p].'</td></tr>';
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

print '<tr><th colspan="2">Player Information</th></tr>';
@open_mpd_connection();
if ($is_connected) {
    print '<tr><td>Connection Status</td><td>Connection Successful</td></tr>';
    if ($prefs['player_backend'] == 'mpd') {
        if ($prefs['unix_socket'] != '') {
            $config = do_mpd_command('config', true);
            if (is_array($config)) {
                foreach ($config as $c => $v) {
                    print '<tr><td>'.$c.'</td><td>'.multi_implode($v).'</td></tr>';
                }
            } else {
                // print '<tr><td>Config</td><td>'.$config.'</td></tr>';
            }
        }
        $tagtypes = do_mpd_command('tagtypes', true);
        if (is_array($tagtypes)) {
            foreach ($tagtypes as $c => $v) {
                print '<tr><td>'.$c.'</td><td>'.implode(', ', $v).'</td></tr>';
            }
        } else {
            // print '<tr><td>Tagtypes</td><td>'.$tagtypes.'</td></tr>';
        }
    }
    $commands = do_mpd_command('commands', true);
    if (is_array($commands)) {
        foreach ($commands as $c => $v) {
            print '<tr><td>Commands</td><td>'.implode(', ', $v).'</td></tr>';
        }
    } else {
        // print '<tr><td>Commands</td><td>'.$commands.'</td></tr>';
    }
    $commands = do_mpd_command('notcommands', true);
    if (is_array($commands)) {
        foreach ($commands as $c => $v) {
            print '<tr><td>Not Commands</td><td>'.implode(', ', $v).'</td></tr>';
        }
    } else {
        // print '<tr><td>Not Commands</td><td>'.$commands.'</td></tr>';
    }
    $commands = do_mpd_command('urlhandlers', true);
    if (is_array($commands)) {
        foreach ($commands as $c => $v) {
            print '<tr><td>URL Handlers</td><td>'.implode(', ', $v).'</td></tr>';
        }
    } else {
        // print '<tr><td>URL Handlers</td><td>'.$commands.'</td></tr>';
    }
    $commands = do_mpd_command('decoders', true);
    if (is_array($commands)) {
        foreach ($commands as $c => $v) {
            print '<tr><td>'.$c.'</td><td>'.implode(', ', $v).'</td></tr>';
        }
    } else {
        // print '<tr><td>Decoders</td><td>'.$commands.'</td></tr>';
    }
} else {
    print '<tr><td>Connection Status</td><td>Connection Failed</td></tr>';
}

print '</table>';
?>
