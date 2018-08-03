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
	debuglog("Attempting to connect to MYSQL Server","SQL_CONNECT",4);
	try {
		if (is_numeric($prefs['mysql_port'])) {
			debuglog("Connecting using hostname and port","SQL_CONNECT",5);
			$dsn = "mysql:host=".$prefs['mysql_host'].";port=".$prefs['mysql_port'].";dbname=".$prefs['mysql_database'];
		} else {
			debuglog("Connecting using unix socket","SQL_CONNECT",5);
			$dsn = "mysql:unix_socket=".$prefs['mysql_port'].";dbname=".$prefs['mysql_database'];
		}
		$mysqlc = new PDO($dsn, $prefs['mysql_user'], $prefs['mysql_password']);
		debuglog("Connected to MySQL","SQL_CONNECT",5);
		$prefs['collection_type'] = 'mysql';
	} catch (Exception $e) {
		debuglog("Couldn't connect to MySQL - ".$e,"SQL_CONNECT",3);
		$mysqlc = null;
	}
	if ($mysqlc == null) {
		debuglog("Attempting to use SQLite Database",4);
		try {
			$dsn = "sqlite:prefs/collection_mpd.sq3";
			$mysqlc = new PDO($dsn);
			debuglog("Connected to SQLite","MYSQL");
			$prefs['collection_type'] = 'sqlite';
		} catch (Exception $e) {
			debuglog("Couldn't use SQLite Either - ".$e,"MYSQL",3);
			$mysqlc = null;
		}
	}
}

//
// Initialisation
//

function show_sql_error($text = "    MYSQL Error: ", $stmt = null) {
	global $mysqlc;
	debuglog($text." : ".$mysqlc->errorInfo()[1]." : ".$mysqlc->errorInfo()[2],"MYSQL",1);
	if ($stmt !== null) {
		debuglog($text." : ".$stmt->errorInfo()[1]." : ".$stmt->errorInfo()[2],"MYSQL",1);
	}
}

//
// Queries
//

function generic_sql_query($qstring, $return_boolean = false, $return_type = PDO::FETCH_ASSOC, $return_value = null, $value_default = null ) {
	global $mysqlc;
	debuglog($qstring,"SQL_QUERY",9);
	$retval = true;
	if (($result = $mysqlc->query($qstring)) !== false) {
		debuglog("Done : ".($result->rowCount())." rows affected","SQL_QUERY",9);
		if ($return_value !== null) {
			$arr = $result->fetch(PDO::FETCH_ASSOC);
			$retval = ($arr) ? $arr[$return_value] : $value_default;
		} else if ($return_boolean) {
			$retval = true;
		} else {
			$retval = $result->fetchAll($return_type);
		}
	} else {
		debuglog("Command Failed : ".$qstring,"SQL_QUERY",2);
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
	debuglog("Get column ".$column." from ".$qstring,"SQL_QUERY",9);
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
			show_sql_error("SQL Statement Error : ",$stmt);
		}
	} else {
		debuglog("Query prep error ".$query,"MYSQL",2);
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
		show_sql_error("Query Prep Error For ".$query,2);
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
		debuglog("Collection version is correct","MYSQL",8);
		return "0";
	} else {
		if ($lv > 0) {
			debuglog("Collection version is outdated - ".$lv, "MYSQL",4);
			return "1";
		} else {
			debuglog("Collection has not been built".$lv, "MYSQL",7);
			return "2";
		}
	}
}

function checkAlbumArt() {
	$oa =  generic_sql_query("SELECT COUNT(ImgVersion) AS NumOldAlbums FROM Albumtable WHERE Image LIKE 'albumart/small/%' AND ImgVersion < ".ROMPR_IMAGE_VERSION, false, null, 'NumOldAlbums', 0);
	debuglog("There are ".$oa." albums with old-style album art","INIT");
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
		debuglog("WARNING! check_transaction called when transaction not open!","BACKEND",3);
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
		debuglog("WARNING! close_transaction called when transaction not open!","BACKEND",3);
    }
}

function create_update_triggers() {

	debuglog("Creating Triggers for update operation","SQLITE",6);

	generic_sql_query("CREATE TRIGGER rating_update_trigger AFTER UPDATE ON Ratingtable
						FOR EACH ROW
						BEGIN
						UPDATE Albumtable SET justUpdated = 1 WHERE Albumindex = (SELECT Albumindex FROM Tracktable WHERE TTindex = NEW.TTindex);
						UPDATE Tracktable SET Hidden = 0, justAdded = 1 WHERE Hidden = 1 AND TTindex = NEW.TTindex;
						UPDATE Tracktable SET isSearchResult = 1, LastModified = NULL, justAdded = 1 WHERE isSearchResult > 1 AND TTindex = NEW.TTindex;
						END;", true);

	generic_sql_query("CREATE TRIGGER rating_insert_trigger AFTER INSERT ON Ratingtable
						FOR EACH ROW
						BEGIN
						UPDATE Albumtable SET justUpdated = 1 WHERE Albumindex = (SELECT Albumindex FROM Tracktable WHERE TTindex = NEW.TTindex);
						UPDATE Tracktable SET Hidden = 0, justAdded = 1 WHERE Hidden = 1 AND TTindex = NEW.TTindex;
						UPDATE Tracktable SET isSearchResult = 1, LastModified = NULL, justAdded = 1 WHERE isSearchResult > 1 AND TTindex = NEW.TTindex;
						END;", true);

	generic_sql_query("CREATE TRIGGER tag_delete_trigger AFTER DELETE ON Tagtable
						FOR EACH ROW
						BEGIN
						DELETE FROM TagListtable WHERE Tagindex = OLD.Tagindex;
						END;", true);

	generic_sql_query("CREATE TRIGGER tag_insert_trigger AFTER INSERT ON TagListtable
						FOR EACH ROW
						BEGIN
						UPDATE Albumtable SET justUpdated = 1 WHERE Albumindex = (SELECT Albumindex FROM Tracktable WHERE TTindex = NEW.TTindex);
						UPDATE Tracktable SET Hidden = 0, justAdded = 1 WHERE Hidden = 1 AND TTindex = NEW.TTindex;
						UPDATE Tracktable SET isSearchResult = 1, LastModified = NULL, justAdded = 1 WHERE isSearchResult > 1 AND TTindex = NEW.TTindex;
						END;", true);

	generic_sql_query("CREATE TRIGGER tag_remove_trigger AFTER DELETE ON TagListtable
						FOR EACH ROW
						BEGIN
						UPDATE Albumtable SET justUpdated = 1 WHERE Albumindex = (SELECT Albumindex FROM Tracktable WHERE TTindex = OLD.TTindex);
						END;", true);

	generic_sql_query("CREATE TRIGGER track_delete_trigger AFTER DELETE ON Tracktable
						FOR EACH ROW
						BEGIN
						UPDATE Albumtable SET justUpdated = 1 WHERE Albumindex = OLD.Albumindex;
						END;", true);

}

function get_collection_type() {
	$c = simple_query('Value', 'Statstable', 'Item', 'CollType', null);
	if ($c == COLLECTION_TYPE_MPD) {
		return 'mpd';
	} else if ($c == COLLECTION_TYPE_MOPIDY) {
		return 'mopidy';
	} else {
		return 'unknown';
	}
}

?>
