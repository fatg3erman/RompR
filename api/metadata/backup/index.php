<?php
chdir('../../..');
require_once ("includes/vars.php");
require_once ("includes/functions.php");
$p = json_decode(file_get_contents('php://input'), true);
prefs::$database = new metabackup();
switch ($p['action']) {

	case 'metabackup':
		prefs::$database->backup_unrecoverable_data();
		header('HTTP/1.1 204 No Content');
		break;

	case 'getbackupdata':
		print json_encode(prefs::$database->analyse_backups());
		break;

	case 'backupremove':
		prefs::$database->removeBackup($p['which']);
		header('HTTP/1.1 204 No Content');
		break;

	case 'backuprestore':
		prefs::$database->restoreBackup($p['which']);
		header('HTTP/1.1 204 No Content');
		break;

	default:
		logger::warn("USERRATINGS", "Unknown Request",$p['action']);
		header('HTTP/1.1 400 Bad Request');
		break;

}
?>