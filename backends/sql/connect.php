<?php

$mysqlc = null;
if (array_key_exists('collection_type', $prefs)) {
	include("backends/sql/".$prefs['collection_type']."/specifics.php");
}

function probe_database() {
	// In the case where collection_type is not set, probe to see which type of DB to use
	// This keeps the behaviour the same as previous versions which auto-detected
	// the database type. This does mean we get some duplicate code but this is
	// so much better for the user.
	global $mysqlc, $prefs;
	logger::mark("SQL_CONNECT", "Probing Database Type");
	logger::log("SQL_CONNECT", "Attempting to connect to MYSQL Server");
	try {
		if (is_numeric($prefs['mysql_port'])) {
			logger::trace("SQL_CONNECT", "Connecting using hostname and port");
			$dsn = "mysql:host=".$prefs['mysql_host'].";port=".$prefs['mysql_port'].";dbname=".$prefs['mysql_database'];
		} else {
			logger::trace("SQL_CONNECT", "Connecting using unix socket");
			$dsn = "mysql:unix_socket=".$prefs['mysql_port'].";dbname=".$prefs['mysql_database'];
		}
		$mysqlc = new PDO($dsn, $prefs['mysql_user'], $prefs['mysql_password']);
		logger::mark("SQL_CONNECT", "Connected to MySQL");
		$prefs['collection_type'] = 'mysql';
	} catch (Exception $e) {
		logger::warn("SQL_CONNECT", "Couldn't connect to MySQL - ".$e);
		$mysqlc = null;
	}
	if ($mysqlc == null) {
		logger::log("SQL_CONNECT", "Attempting to use SQLite Database");
		try {
			$dsn = "sqlite:prefs/collection.sq3";
			$mysqlc = new PDO($dsn);
			logger::mark("MYSQL", "Connected to SQLite");
			$prefs['collection_type'] = 'sqlite';
		} catch (Exception $e) {
			logger::fail("MYSQL", "Couldn't use SQLite Either - ".$e);
			$mysqlc = null;
		}
	}
}

//
// Initialisation
//

function show_sql_error($text = "", $stmt = null) {
	global $mysqlc;
	logger::error("MYSQL ERROR", $text,":",$mysqlc->errorInfo()[1],":",$mysqlc->errorInfo()[2]);
	if ($stmt !== null) {
		logger::error("STMT ERROR", $text,":",$stmt->errorInfo()[1],":",$stmt->errorInfo()[2]);
	}
}

//
// Queries
//

function generic_sql_query($qstring, $return_boolean = false, $return_type = PDO::FETCH_ASSOC, $return_value = null, $value_default = null, $return_rowcount = false ) {
	global $mysqlc;
	logger::debug("GENERIC_SQL", $qstring);
	$retval = true;
	if (($result = @$mysqlc->query($qstring)) !== false) {
		// logger::debug("GENERIC_SQL", "Done : ".($result->rowCount())." rows affected");
		if ($return_value !== null) {
			$arr = $result->fetch(PDO::FETCH_ASSOC);
			$retval = ($arr) ? $arr[$return_value] : $value_default;
		} else if ($return_boolean) {
			$retval = true;
		} else if ($return_rowcount) {
			$retval = $result->rowCount();
		} else {
			$retval = $result->fetchAll($return_type);
		}
	} else {
		logger::warn("GENERIC_SQL", "Command Failed :",$qstring);
		show_sql_error();
		if ($return_value !== null) {
			$retval = $value_default;
		} else if ($return_boolean) {
			$retval = false;
		} else {
			$retval = array();
		}
	}
	$result = null;
	return $retval;
}

function sql_get_column($qstring, $column) {
	global $mysqlc;
	logger::debug("SQL_GET_COLUMN", "Get column",$column,"from",$qstring);
	$retval = array();
	if (($result = $mysqlc->query($qstring)) !== false) {
		$retval = $result->fetchAll(PDO::FETCH_COLUMN, $column);
	}
	return $retval;
}

function simple_query($select, $from, $where, $item, $default) {
	$retval = $default;
	$qstring = "SELECT ".$select." AS TheThingToFind FROM ".$from;
	if ($where != null) {
		$qstring .= " WHERE ".$where." = ?";
	}
	$retval = sql_prepare_query(false, null, 'TheThingToFind', $default, $qstring, $item);
	return $retval;
}

function sql_prepare_query() {
	// Variable arguments but at least 5 are required:
	// 1. flag for whether to just return a boolean
	// 2. return type
	// 3. field name
	// 4. default value for field name
	// 5. query string
	// ... parameters for query
	// return type of PDO::FETCH_COLUMN returns an array of the values
	//  from the column identified by field name
	// --**-- NO PARAMETER CHECKING IS DONE BY THIS FUNCTION! --**--
	//   because we want to make it fast, so make sure you call it right!

	// This doesn't appear to work with MySQL when one of the args has to be an integer
	// eg LIMIT ? doesn't work.

	global $mysqlc;
	$allargs = func_get_args();
	logger::debug("SQL_PREPARE",$allargs);
	$return_boolean = $allargs[0];
	$return_type = $allargs[1];
	$return_value = $allargs[2];
	$value_default = $allargs[3];
	$query = $allargs[4];
	if (is_array($allargs[5])) {
		$args = $allargs[5];
	} else {
		$args = array_slice($allargs, 5);
	}

	$stmt = $mysqlc->prepare($query);
	if ($stmt !== false) {
		if ($stmt->execute($args)) {
			if ($return_type == PDO::FETCH_COLUMN) {
				$retval = $stmt->fetchAll(PDO::FETCH_COLUMN, $return_value);
			} else if ($return_value !== null) {
				$arr = $stmt->fetch(PDO::FETCH_ASSOC);
				$retval = ($arr) ? $arr[$return_value] : $value_default;
			} else if ($return_boolean) {
				$retval = true;
			} else {
				$retval = $stmt->fetchAll($return_type);
			}
			$stmt = null;
			return $retval;
		} else {
			show_sql_error("SQL Statement Error for",$stmt);
		}
	} else {
		show_sql_error();
	}
	if ($return_value !== null) {
		$retval = $value_default;
	} else if ($return_boolean) {
		$retval = false;
	} else {
		$retval = array();
	}
	$stmt = null;
	return $retval;
}

function sql_prepare_query_later($query) {
	global $mysqlc;
	$stmt = $mysqlc->prepare($query);
	if ($stmt === FALSE) {
		show_sql_error();
	}
	return $stmt;
}

// Debug function for prepared statement
function dbg_params($string,$data) {
	$indexed = $data==array_values($data);

	foreach($data as $k=>$v) {
		if (is_string($v)) {
			$v = "'$v'";
		}
		if($indexed) {
			$string = preg_replace('/\?/', $v, $string, 1);
		} else {
			$string=str_replace(":$k", $v, $string);
		}
	}
	return $string;
}

function checkCollectionStatus() {
	$lv = generic_sql_query("SELECT Value FROM Statstable WHERE Item = 'ListVersion'", false, null, 'Value', null);
	if ($lv == ROMPR_COLLECTION_VERSION) {
		logger::log("MYSQL", "Collection version is correct");
		return "0";
	} else {
		if ($lv > 0) {
			logger::warn("MYSQL", "Collection version is outdated - ".$lv);
			return "1";
		} else {
			logger::shout("MYSQL", "Collection has not been built".$lv);
			return "2";
		}
	}
}

function checkAlbumArt() {
	$oa =  generic_sql_query("SELECT COUNT(ImgVersion) AS NumOldAlbums FROM Albumtable WHERE Image LIKE 'albumart/small/%' AND ImgVersion < ".ROMPR_IMAGE_VERSION, false, null, 'NumOldAlbums', 0);
	logger::log("INIT", "There are ".$oa." albums with old-style album art");
	return $oa;
}

function open_transaction() {
	global $transaction_open, $mysqlc;
	if (!$transaction_open) {
		if ($mysqlc->beginTransaction()) {
			$transaction_open = true;
		}
	}
}

function check_transaction() {
	global $numdone, $transaction_open;
	if ($transaction_open) {
		if ($numdone >= ROMPR_MAX_TRACKS_PER_TRANSACTION) {
			close_transaction();
			open_transaction();
		}
	} else {
		logger::warn("BACKEND", "WARNING! check_transaction called when transaction not open!");
	}
}

function close_transaction() {
	global $transaction_open, $numdone, $mysqlc;
	if ($transaction_open) {
		if ($mysqlc->commit()) {
			$transaction_open = false;
			$numdone = 0;
		}
	} else {
		logger::warn("BACKEND", "WARNING! close_transaction called when transaction not open!");
	}
}

function saveCollectionPlayer($type) {
	global $prefs;
	logger::mark("COLLECTION", "Setting Collection Type to",$type);
	switch ($type) {
		case 'mopidy':
			sql_prepare_query(true, null, null, null,
				"UPDATE Statstable SET Value = ? WHERE Item = 'CollType'", 1);
			$prefs['collection_player'] = 'mopidy';
			break;

		case 'mpd':
			sql_prepare_query(true, null, null, null,
				"UPDATE Statstable SET Value = ? WHERE Item = 'CollType'", 0);
			$prefs['collection_player'] = 'mpd';
			break;
	}
	savePrefs();
}

function readCollectionPlayer($sp = treu) {
	global $prefs;
	$c = simple_query('Value', 'Statstable', 'Item', 'CollType', 999);
	switch ($c) {
		case 999:
			logger::trace("COLLECTION", "Collection type from database is not set");
			logger::trace("COLLECTION", "Prefs collection_player is currently",$prefs['collection_player']);
			$prefs['collection_player'] = null;
			break;

		case 1:
			logger::debug("COLLECTION", "Collection type from database is mopidy");
			$prefs['collection_player'] = 'mopidy';
			break;

		case 0:
		logger::debug("COLLECTION", "Collection type from database is mpd");
			$prefs['collection_player'] = 'mpd';
			break;
	}
	if ($sp) {
		savePrefs();
	}
	return $c;
}


?>
