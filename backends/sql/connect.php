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

function generic_sql_query($qstring) {
	global $mysqlc;
	debuglog($qstring,"SQL_QUERY",9);
	if (($result = $mysqlc->query($qstring)) !== false) {
		debuglog("Done : ".($result->rowCount())." rows affected","SQL_QUERY",9);
	} else {
		debuglog("Command Failed : ".$qstring,"SQL_QUERY",2);
		show_sql_error();
	}
	return $result;
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

function simple_query($select, $from, $where, $item, $default) {
	$retval = $default;
	$qstring = "SELECT ".$select." AS TheThingToFind FROM ".$from;
	if ($where != null) {
		$qstring .= " WHERE ".$where." = ?";
	}
	if ($result = sql_prepare_query($qstring, $item)) {
		 while ($arr = $result->fetch(PDO::FETCH_ASSOC)) {
		 	$retval = $arr['TheThingToFind'];
		 }
	}
	$result = null;
	return $retval;
}

// Variable arguments, first is query
// This doesn't appear to work with MySQL when one of the args has to be an integer
// eg LIMIT ? doesn't work.
function sql_prepare_query() {
	global $mysqlc;
	$numArgs = func_num_args();
	$query = func_get_arg(0);
	$stmt = $mysqlc->prepare($query);
	if ($stmt !== false) {
		$args = array();
		for ($i = 1; $i < $numArgs; $i++) $args[] = func_get_arg($i);
		if ($stmt->execute($args)) {
			return $stmt;
		} else {
			show_sql_error("SQL Statement Error : ",$stmt);
		}
	} else {
		debuglog("Query prep error ".$query,"MYSQL",2);
		debuglog("   numArgs was   ".$numArgs,"MYSQL",2);
		show_sql_error();
	}
	$stmt = null;
	return false;
}

function sql_prepare_query_later($query) {
	global $mysqlc;
	$stmt = $mysqlc->prepare($query);
	if ($stmt === FALSE) {
		show_sql_error("Query Prep Error For ".$query,2);
	}
	return $stmt;
}

function checkCollectionStatus() {
	if ($result = generic_sql_query("SELECT Value FROM Statstable WHERE Item = 'ListVersion'")) {
		$lv = 0;
		while ($obj = $result->fetch(PDO::FETCH_OBJ)) {
			$found = true;
			$lv = $obj->Value;
		}
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
						END;");

	generic_sql_query("CREATE TRIGGER rating_insert_trigger AFTER INSERT ON Ratingtable
						FOR EACH ROW
						BEGIN
						UPDATE Albumtable SET justUpdated = 1 WHERE Albumindex = (SELECT Albumindex FROM Tracktable WHERE TTindex = NEW.TTindex);
						UPDATE Tracktable SET Hidden = 0, justAdded = 1 WHERE Hidden = 1 AND TTindex = NEW.TTindex;
						UPDATE Tracktable SET isSearchResult = 1, LastModified = NULL, justAdded = 1 WHERE isSearchResult > 1 AND TTindex = NEW.TTindex;
						END;");

	generic_sql_query("CREATE TRIGGER tag_delete_trigger AFTER DELETE ON Tagtable
						FOR EACH ROW
						BEGIN
						DELETE FROM TagListtable WHERE Tagindex = OLD.Tagindex;
						END;");

	generic_sql_query("CREATE TRIGGER tag_insert_trigger AFTER INSERT ON TagListtable
						FOR EACH ROW
						BEGIN
						UPDATE Albumtable SET justUpdated = 1 WHERE Albumindex = (SELECT Albumindex FROM Tracktable WHERE TTindex = NEW.TTindex);
						UPDATE Tracktable SET Hidden = 0, justAdded = 1 WHERE Hidden = 1 AND TTindex = NEW.TTindex;
						UPDATE Tracktable SET isSearchResult = 1, LastModified = NULL, justAdded = 1 WHERE isSearchResult > 1 AND TTindex = NEW.TTindex;
						END;");

	generic_sql_query("CREATE TRIGGER tag_remove_trigger AFTER DELETE ON TagListtable
						FOR EACH ROW
						BEGIN
						UPDATE Albumtable SET justUpdated = 1 WHERE Albumindex = (SELECT Albumindex FROM Tracktable WHERE TTindex = OLD.TTindex);
						END;");

	generic_sql_query("CREATE TRIGGER track_delete_trigger AFTER DELETE ON Tracktable
						FOR EACH ROW
						BEGIN
						UPDATE Albumtable SET justUpdated = 1 WHERE Albumindex = OLD.Albumindex;
						END;");

}

?>