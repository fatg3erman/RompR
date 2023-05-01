<?php

class cache_handler extends url_downloader {

	//---------------------------------------------------------------------------------------------------------
	//
	// Class for handling locally cached JSON data. options are a subset from url_downloader
	// THIS CLASS RETURNS JSON-ENCODED STRINGS. not arrays. not xml. not HTML. Use it for JSON only.
	//
	// options:
	//		timeout 			download timeout (optional)
	//		connection_timeout 	try for this long to connect (optional)
	//		url 				URL to download
	//		header 				HTTP header fields, array('Header Name: Value', 'Header Name: Value' ... ) (optional)
	//		postfields 			HTTP POST data (optional)
	//		cache 				Which cache dir to use under prefs/jsoncache. Set to null to not use the cache
	//
	//		return_value 		true to return the downloaded data, false to print it to STDOUT. Defaults to false
	//
	//---------------------------------------------------------------------------------------------------------

	private $defaults = ['return_value' => false];

	public function __construct($options) {
		$options = array_merge($this->defaults, $options);
		parent::__construct($options);
	}

	public function get_cache_data() {
		$retval = '';
		$header = '';
		if ($this->get_data_to_file()) {
			// If the download was successful, return the data that was downloaded
			$retval = $this->get_data();
		} else {
			logger::warn("CACHE HANDLER", "There was an HTTP error");
			// Else set the HTTP header to the status code returned, or to 500 if none
			if ($this->get_status() > 0) {
				$header = $this->get_status().' '.http_status_code_string($this->get_status());
			} else {
				$header = '500 '.http_status_code_string(500);
			}
			logger::warn("CACHE HANDLER",$header);
			// Sometimes we do get data returned by APIs even if there was an error
			// If that data exists, return it else return our standard array('error' => value)
			if ($this->get_data() != '') {
				$retval = $this->get_data();
				logger::core('CACHE HANDLER', $retval);
			} else {
				$retval =  json_encode(array('error' => $header));
			}
		}
		if ($this->options['return_value']) {
			// return_value should be true if we want to do something else with this data...
			return $retval;
		} else {
			// ...otherwise we just print it to stdout after first sending the headers
			if ($header != '') {
				header('HTTP/1.1 '.$header);
			} else if ($this->from_cache) {
				header("Pragma: From Cache");
			} else {
				header("Pragma: Not Cached");
			}
			print $retval;
		}
	}

	public function check_cache_file($cache, $url) {
		return file_exists($this->get_cache_file($cache, $url));
	}

}

?>