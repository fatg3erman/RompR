<?php

chdir('..');
require_once ("includes/vars.php");
require_once ("includes/functions.php");

class snapcast {

	private $connection;
	private $ip;
	private $port;

	public function __construct($ip, $port) {
		$this->port = $port;
		$this->ip = $ip;
	}

	public function __destruct() {
		$this->close_connection();
	}

	private function open_connection() {
		if ($this->is_connected()) {
			return true;
		}
		logger::trace("SNAPCAST", "Connecting to ".$this->ip.':'.$this->port);
		$this->connection = @stream_socket_client('tcp://'.$this->ip.':'.$this->port);
		if ($this->is_connected()) {
			stream_set_timeout($this->connection, 65535);
			stream_set_blocking($this->connection, true);
			return true;
		}
		logger::warn("SNAPCAST", "Snapcast connection failed");
		return false;
	}

	public function close_connection() {
		if ($this->is_connected()) {
			stream_socket_shutdown($this->connection, STREAM_SHUT_RDWR);
		}
	}

	private function is_connected() {
		return (isset($this->connection) && is_resource($this->connection));
	}

	public function do_command($json) {
		if ($this->open_connection()) {
			logger::trace("SNAPCAST", "Sending ",$json);
			fputs($this->connection, $json."\r\n");
			$got = fgets($this->connection);
			return $got;
		} else {
			return $this->errorjson('Could not connect to snapcast server');
		}
	}

	private function errorjson($msg) {
		$retval = array('error' => $msg);
		return json_encode($retval);
	}
}

$server = new snapcast($prefs['snapcast_server'], $prefs['snapcast_port']);
$json = file_get_contents("php://input");
$output = $server->do_command($json);
logger::debug("SNAPCAST", "Output is",$output);
header('Content-Type: application/json; charset=utf-8');
print $output;
$server->close_connection();
?>