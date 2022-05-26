<?php

class data_base {

	protected $mysqlc;
	protected $transaction_open = false;
	private $numdone = 0;

	public static function probe_database() {
		// In the case where collection_type is not set, probe to see which type of DB to use
		// This keeps the behaviour the same as previous versions which auto-detected
		// the database type. This does mean we get some duplicate code but this is
		// so much better for the user.
		logger::mark("SQL_CONNECT", "Probing Database Type");
		logger::log("SQL_CONNECT", "Attempting to connect to MYSQL Server");
		$retval = false;
		$mysqlc = null;
		try {
			if (is_numeric(prefs::$prefs['mysql_port'])) {
				logger::trace("SQL_CONNECT", "Connecting using hostname and port");
				$dsn = "mysql:host=".prefs::$prefs['mysql_host'].";port=".prefs::$prefs['mysql_port'].";dbname=".prefs::$prefs['mysql_database'];
			} else {
				logger::trace("SQL_CONNECT", "Connecting using unix socket");
				$dsn = "mysql:unix_socket=".prefs::$prefs['mysql_port'].";dbname=".prefs::$prefs['mysql_database'];
			}
			$mysqlc = new PDO($dsn, prefs::$prefs['mysql_user'], prefs::$prefs['mysql_password']);
			logger::mark("SQL_CONNECT", "Connected to MySQL");
			prefs::$prefs['collection_type'] = 'mysql';
			$retval = true;
			$mysqlc = null;
		} catch (Exception $e) {
			logger::warn("SQL_CONNECT", "Couldn't connect to MySQL - ".$e);
			$mysqlc = null;
		}
		if ($mysqlc == null) {
			logger::log("SQL_CONNECT", "Attempting to use SQLite Database");
			try {
				$dsn = "sqlite:prefs/collection.sq3";
				$mysqlc = new PDO($dsn);
				logger::log("SQL_CONNECT", "Connected to SQLite");
				prefs::$prefs['collection_type'] = 'sqlite';
				$retval = true;
				$mysqlc = null;
			} catch (Exception $e) {
				logger::warn("SQL_CONNECT", "Couldn't use SQLite Either - ".$e);
				$mysqlc = null;
			}
		}
		return $retval;
	}

	public function close_database() {
		$this->mysqlc = null;
	}

	//
	// Queries
	//

	public function generic_sql_query(
		$qstring,
		$return_boolean = false,
		$return_type = PDO::FETCH_ASSOC,
		$return_value = null,
		$value_default = null,
		$return_rowcount = false )
	{
		logger::core("GENERIC_SQL", $qstring);
		$retval = true;
		if (($result = @$this->mysqlc->query($qstring)) !== false) {
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
			$this->show_sql_error();
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

	protected function sql_get_column($qstring, $column) {
		logger::core("SQL_GET_COLUMN", "Get column",$column,"from",$qstring);
		$retval = array();
		if (($result = $this->mysqlc->query($qstring)) !== false) {
			$retval = $result->fetchAll(PDO::FETCH_COLUMN, $column);
		}
		return $retval;
	}

	public function simple_query($select, $from, $where, $item, $default) {
		$retval = $default;
		$qstring = "SELECT ".$select." AS TheThingToFind FROM ".$from;
		if ($where != null) {
			$qstring .= " WHERE ".$where." = ?";
		}
		$retval = $this->sql_prepare_query(false, null, 'TheThingToFind', $default, $qstring, $item);
		return $retval;
	}

	protected function sql_prepare_query() {
		// Variable arguments but at least 5 are required:
		// 1. flag for whether to just return a boolean
		// 2. return type
		// 3. field name
		// 4. default value for field name
		// 5. query string
		// ... parameters for query
		// return type of PDO::FETCH_COLUMN returns an array of the values
		//  from the column identified by field name, which should be an integeer for column number
		// --**-- NO PARAMETER CHECKING IS DONE BY THIS FUNCTION! --**--
		//   because we want to make it fast, so make sure you call it right!

		// This doesn't appear to work with MySQL when one of the args has to be an integer
		// eg LIMIT ? doesn't work.

		$allargs = func_get_args();
		logger::core("SQL_PREPARE",$allargs);
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

		if (($stmt = $this->sql_prepare_query_later($query)) !== false) {
			try {
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
					$this->show_sql_error("SQL Statement Error", $stmt);
				}
			} catch (Exception $e) {
				$this->show_sql_error("SQL Statement Error", $stmt);
				logger::log('SQL','PDO rasied exception', $e);
			}
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

	protected function sql_prepare_query_later($query) {
		//
		// Prepare a statement for use later
		//
		$stmt = $this->mysqlc->prepare($query);
		if ($stmt === false) {
			$this->show_sql_error();
		}
		return $stmt;
	}

	protected function show_sql_error($text = "", $stmt = null) {
		logger::error("MYSQL ERROR", $text,":",$this->mysqlc->errorInfo()[1],":",$this->mysqlc->errorInfo()[2]);
		if ($stmt) {
			logger::error("STMT ERROR", $text,":",$stmt->errorInfo()[1],":",$stmt->errorInfo()[2]);
		}
	}

	//
	// Transactions.
	// For some reason I cannot be bothered to divine, transactions aren't working
	// in mysql / mariadb and attempting to commit an empty one causes an error
	// that produces no error message but does appear to make something, somewhere
	// just quit. So I'm not doing transactions for those databases. They were already
	// slower. This will make them slower still. Maybe a good thing will happen and
	// people will stop using them so I can just use SQLite?
	//

	public function open_transaction() {
		if (prefs::$prefs['collection_type'] == 'sqlite' && !$this->transaction_open) {
			if ($this->mysqlc->beginTransaction()) {
				$this->transaction_open = true;
				$this->numdone = 0;
			}
		}
	}

	public function check_transaction() {
		if (prefs::$prefs['collection_type'] == 'sqlite') {
			if ($this->transaction_open) {
				if ($this->numdone++ >= ROMPR_MAX_TRACKS_PER_TRANSACTION) {
					logger::trace('DATABASE', 'Need to commit transaction');
					$this->close_transaction();
					$this->open_transaction();
				}
			} else {
				logger::warn("DATABASE", "WARNING! check_transaction called when transaction not open!");
			}
		}
	}

	public function close_transaction() {
		if (prefs::$prefs['collection_type'] == 'sqlite') {
			if ($this->transaction_open) {
				if ($this->mysqlc->commit()) {
					$this->transaction_open = false;
				} else {
					logger::warn('DATABASE', "WARNING! Transaction commit failed!");
					$this->show_sql_error();
				}
			} else {
				logger::warn("DATABASE", "WARNING! close_transaction called when transaction not open!");
			}
		}
	}

	public function checkCollectionStatus() {
		$lv = $this->generic_sql_query("SELECT Value FROM Statstable WHERE Item = 'ListVersion'", false, null, 'Value', null);
		if ($lv == ROMPR_COLLECTION_VERSION) {
			logger::log("DATABASE", "Collection version is correct");
			return "0";
		} else {
			if ($lv > 0) {
				logger::warn("DATABASE", "Collection version is outdated - ".$lv);
				return "1";
			} else {
				logger::mark("DATABASE", "Collection has not been built".$lv);
				return "2";
			}
		}
	}

	public function checkAlbumArt() {
		$oa = $this->generic_sql_query("SELECT COUNT(ImgVersion) AS NumOldAlbums FROM Albumtable WHERE Image LIKE 'albumart/small/%' AND ImgVersion < ".ROMPR_IMAGE_VERSION, false, null, 'NumOldAlbums', 0);
		logger::log("DATABASE", "There are ".$oa." albums with old-style album art");
		return $oa;
	}

	public function collectionUpdateRunning() {
		$count = 200;
		while ($count > 0) {
			$cur = $this->simple_query('Value', 'Statstable', 'Item', 'Updating', null);
			switch ($cur) {
				case null:
					logger::warn('DATABASE', 'Got null response to update lock check');
				case '0':
					$this->generic_sql_query("UPDATE Statstable SET Value = 1 WHERE Item = 'Updating'", true);
					return false;

				case '1':
					logger::mark('DATABASE', 'Collection Is Locked. Waiting...');
					sleep(5);
					$count--;
			}
		}
		logger::warn('DATABASE', 'Collection Was Not Unlocked After 1000 Seconds. Giving Up.');
		return true;
	}

	public function clearUpdateLock() {
		logger::log('DATABASE', 'Clearing update lock');
		$this->generic_sql_query("UPDATE Statstable SET Value = 0 WHERE Item = 'Updating'", true);
	}


}

?>