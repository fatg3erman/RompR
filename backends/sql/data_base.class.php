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
		logger::info("SQL_CONNECT", "Attempting to connect to MYSQL Server");
		$retval = false;
		$mysqlc = null;
		$error = '';
		try {
			if (is_numeric(prefs::get_pref('mysql_port'))) {
				logger::trace("SQL_CONNECT", "Connecting using hostname and port");
				$dsn = "mysql:host=".prefs::get_pref('mysql_host').";port=".prefs::get_pref('mysql_port').";dbname=".prefs::get_pref('mysql_database');
			} else {
				logger::trace("SQL_CONNECT", "Connecting using unix socket");
				$dsn = "mysql:unix_socket=".prefs::get_pref('mysql_port').";dbname=".prefs::get_pref('mysql_database');
			}
			$mysqlc = new PDO($dsn, prefs::get_pref('mysql_user'), prefs::get_pref('mysql_password'));
			logger::mark("SQL_CONNECT", "Connected to MySQL");
			prefs::set_pref(['collection_type' => 'mysql']);
			$retval = true;
		} catch (PDOException $e) {
			logger::warn("SQL_CONNECT", "Couldn't connect to MySQL");
			$error = $e->getMessage();
			$mysqlc = null;
		}
		if ($mysqlc == null) {
			logger::info("SQL_CONNECT", "Attempting to use SQLite Database");
			try {
				$dsn = "sqlite:prefs/collection.sq3";
				$mysqlc = new PDO($dsn);
				logger::log("SQL_CONNECT", "Connected to SQLite");
				prefs::set_pref(['collection_type' => 'sqlite']);
				$retval = true;
			} catch (PDOException $e) {
				logger::warn("SQL_CONNECT", "Couldn't use SQLite Either");
				$error .= '<br />'.$e->getMessage();
			}
		}
		$mysqlc = null;
		return array($retval, $error);
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
		$return_rowcount = false)
	{

		// Handle a statement that does not need preparing.
		// sql_prepare_query() requires parameters. This does not.

		// This function returns default values if the command fails:
		// if $return_value is set, return $value_default
		// else if $return_boolean is true, return false
		// else return an empty array

		// $returan_value overrides
		// $return_boolean, which overrides
		// $return_rowcount, which overrides
		// $return_type - $return_value must be null and $return_boolean and $return_rowcount must be false
		// $return type is ignored in the first 3 cases

		// Parameters after $qstring are the same as for sql_prepare_query()
		// with the addition of $return_rowcount which makes it return the affected row count

		// IMPORTANT if your query does not return a results set you must call this with return_boolean or return_rowcount set to true

		logger::core("GENERIC_SQL", $qstring);
		try {
			$result = $this->mysqlc->query($qstring);
			if ($return_value !== null) {
				//
				// Return a value from the named column $return_value, or return $value_default if no results
				//
				$arr = $result->fetch(PDO::FETCH_ASSOC);
				$retval = (is_array($arr) && array_key_exists($return_value, $arr)) ? $arr[$return_value] : $value_default;
			} else if ($return_boolean) {
				//
				// Return success / fail
				//
				$retval = true;
			} else if ($return_rowcount) {
				//
				// Return the row count
				//
				$retval = $result->rowCount();
			} else {
				//
				// Return the results set
				//
				$retval = $result->fetchAll($return_type);
			}
			$result->closeCursor();
		} catch (PDOException $e) {
			logger::error("GENERIC_SQL", "Command Failed :", $qstring);
			logger::error('GENERIC SQL', 'Code', $e->getCode(), $e->getMessage());
			logger::error('GENERIC SQL', 'Stack Trace',print_r($e->getTrace(), true));
			// Default return value
			if ($return_value !== null) {
				$retval = $value_default;
			} else if ($return_boolean) {
				$retval = false;
			} else {
				$retval = array();
			}
		}
		return $retval;
	}

	protected function sql_get_column($qstring, $column) {
		logger::core("SQL_GET_COLUMN", "Get column",$column,"from",$qstring);
		try {
			$result = $this->mysqlc->query($qstring);
			$retval = $result->fetchAll(PDO::FETCH_COLUMN, $column);
		} catch (PDOException $e) {
			logger::error("GENERIC_SQL", "Command Failed :", $qstring);
			logger::error('GENERIC SQL', 'Code', $e->getCode(), $e->getMessage());
			logger::error('GENERIC SQL', 'Stack Trace',print_r($e->getTrace(), true));
			$retval = [];
		}
		return $retval;
	}

	public function simple_query($select, $from, $where, $item, $default) {
		// This function isn't very useful without a WHERE clause,
		// sql_get_column returns data in a more useful form if you
		// just want a list of values
		$retval = $default;
		$qstring = "SELECT $select AS TheThingToFind FROM $from";
		if ($where != null) {
			$qstring .= " WHERE $where = ?";
			$retval = $this->sql_prepare_query(false, null, 'TheThingToFind', $default, $qstring, $item);
		} else {
			$retval = $this->generic_sql_query($qstring, false, PDO::FETCH_ASSOC);
		}
		return $retval;
	}

	public function test_error_handling() {
		$this->generic_sql_query('SELET poo FROM jesus WHERE arthur = 1');
	}

	public function sql_prepare_query() {
		// Variable arguments

		// sql_prepare_query(return_boolean, return_type, field_name, default, query_string, query_parameters ...)

		// return_boolean: flag for whether to just return a boolean
		// return_type: a PDO:: return type constant
		// field name: a valid column name from the results set
		// default: default value to return if field name is set
		// query_string: the query string
		// query parameters: the parameters for substitution with ? in the query

		// return type of PDO::FETCH_COLUMN returns an array of the values
		// from the column identified by field name, which should be an integeer for column number

		// return_type of FETCH_COLUMN overrides
		// returan_value, which overrides
		// return_boolean, which overrides
		// return_type - return_value must be null and return_boolean must be false
		// return type is ignored in the second two cases

		// query parameters can be multiple arguments or an array of values

		// This function returns default values if the command fails:
		// if return_value is set, return value_default
		// else if return_boolean is true, return false
		// else return an empty array

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

		try {
			$stmt = $this->mysqlc->prepare($query);
			$stmt->execute($args);
			if ($return_type == PDO::FETCH_COLUMN) {
				//
				// For fetching a column (specify column NUMBER as $return_value)
				//
				$retval = $stmt->fetchAll(PDO::FETCH_COLUMN, $return_value);
			} else if ($return_value !== null) {
				//
				// For fetching the first value from a NAMED column $return_value, or return $value_default if no results are returned
				//
				$arr = $stmt->fetch(PDO::FETCH_ASSOC);
				// Note we use fetch() which fetches one row at a time so makes $arr[$return_value] correct.
				// If we used returnAll() we'd need $arr[0][$return_value]
				$retval = (is_array($arr) && array_key_exists($return_value, $arr)) ? $arr[$return_value] : $value_default;
			} else if ($return_boolean) {
				//
				// For returning boolean success/fail
				//
				$retval = true;
			} else {
				//
				// For returning the entire results set
				//
				$retval = $stmt->fetchAll($return_type);
			}
			$stmt->closeCursor();
		} catch (PDOException $e) {
			logger::error("GENERIC_SQL", "Command Failed :", $query);
			logger::error('GENERIC SQL', 'Code', $e->getCode(), $e->getMessage());
			logger::error('GENERIC SQL', 'Stack Trace',print_r($e->getTrace(), true));
			if ($return_value !== null) {
				$retval = $value_default;
			} else if ($return_boolean) {
				$retval = false;
			} else {
				$retval = array();
			}
		}
		return $retval;
	}

	protected function sql_prepare_query_later($query) {
		//
		// Prepare a statement for use later
		//
		try {
			$stmt = $this->mysqlc->prepare($query);
		} catch (PDOException $e) {
			logger::error("GENERIC_SQL", "Prepare Failed :", $query);
			logger::error('GENERIC SQL', 'Code', $e->getCode(), $e->getMessage());
			logger::error('GENERIC SQL', 'Stack Trace',print_r($e->getTrace(), true));
			$stmt = false;
		}
		return $stmt;
	}

	//
	// Transactions.
	//

	public function open_transaction() {
		try {
			if (!$this->transaction_open) {
				$this->mysqlc->beginTransaction();
				$this->transaction_open = true;
				$this->numdone = 0;
			} else {
				logger::warn('DATABASE', 'open_transaction called when transaction already open!');
			}
		} catch (PDOException $e) {
			logger::error('DATABASE', 'Caught PDO exception when opening transaction. Data may be lost.');
			logger::error('GENERIC SQL', 'Code', $e->getCode(), $e->getMessage());
			logger::error('GENERIC SQL', 'Stack Trace',print_r($e->getTrace(), true));
			$this->transaction_open = false;
		}
	}

	public function check_transaction() {
		if ($this->transaction_open) {
			if ($this->numdone++ >= ROMPR_MAX_TRACKS_PER_TRANSACTION) {
				$this->close_transaction();
				$this->open_transaction();
			}
		} else {
			logger::warn("DATABASE", "WARNING! check_transaction called when transaction not open!");
		}
	}

	public function close_transaction() {
		try {
			if ($this->transaction_open) {
				$this->mysqlc->commit();
			} else {
				logger::warn("DATABASE", "WARNING! close_transaction called when transaction not open!");
			}
		} catch (PDOException $e) {
			logger::error('DATABASE', 'Caught PDO exception when closing transaction. Data may be lost.');
			logger::error('GENERIC SQL', 'Code', $e->getCode(), $e->getMessage());
			logger::error('GENERIC SQL', 'Stack Trace',print_r($e->getTrace(), true));
		} finally {
			$this->transaction_open = false;
		}
	}

	public function checkCollectionStatus() {
		$lv = $this->get_admin_value('ListVersion', null);
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
		$oa = $this->sql_prepare_query(false, null, 'NumOldAlbums', 0,
			"SELECT COUNT(ImgVersion) AS NumOldAlbums FROM Albumtable WHERE Image LIKE 'albumart/small/%' AND ImgVersion < ?",
			ROMPR_IMAGE_VERSION
		);
		logger::log("DATABASE", "There are ".$oa." albums with old-style album art");
		return $oa;
	}

	public function collectionUpdateRunning($return = false) {
		$count = 200;
		while ($count > 0) {
			$cur = $this->get_admin_value('Updating', null);
			switch ($cur) {
				case null:
					logger::warn('DATABASE', 'Got null response to update lock check');
				case '0':
					$this->set_admin_value('Updating', 1);
					return false;

				case '1':
					logger::log('DATABASE', 'Collection Is Locked. Waiting...');
					if ($return)
						return true;

					sleep(5);
					$count--;
			}
		}
		logger::warn('DATABASE', 'Collection Was Not Unlocked After 200 Seconds. Giving Up.');
		return true;
	}

	public function clearUpdateLock() {
		logger::log('DATABASE', 'Clearing update lock');
		$this->set_admin_value('Updating', 0);
	}

	public function get_admin_value($item, $default) {
		return $this->simple_query('Value', 'Statstable', 'Item', $item, $default);
	}

	public function set_admin_value($item, $value) {
		$this->sql_prepare_query(true, null, null, null,
			"REPLACE INTO Statstable (Item, Value) VALUES (?, ?)",
			$item,
			$value
		);
	}

}

?>