<?php

class url_downloader {

	//--------------------------------------------------------------------------------------------------
	//
	// Class for downloading arbitrary internet content
	//
	// options:
	//		usergant 			User Agent string
	//		timeout 			download timeout
	//		connection_timeout 	try for this long to connect
	//		url 				URL to download
	//		header 				HTTP header fields, array('Header Name: Value', 'Header Name: Value' ... )
	//		postfields 			HTTP POST data
	//		cache 				Which cache dir to use under prefs/jsoncache
	//
	//--------------------------------------------------------------------------------------------------

	private $default_options = array(
		'useragent' => ROMPR_IDSTRING,
		'timeout' => 120,
		'connection_timeout' => 60,
		'url' => '',
		'header' => null,
		'postfields' => null,
		'cache' => null,
	);

	private $ch;
	private $headerarray = array();
	private $headerlen = 0;
	private $content;
	private $content_type;
	private $info;
	private $status;
	private $file;
	public $from_cache = false;
	protected $options;
	private $cookies = [];

	public function __construct($options) {
		$this->options = array_merge($this->default_options, $options);
		$this->ch = curl_init();
		curl_setopt($this->ch, CURLOPT_URL, $this->options['url']);
		curl_setopt($this->ch, CURLOPT_ENCODING, '');
		if ($this->options['useragent']) {
			curl_setopt($this->ch, CURLOPT_USERAGENT, $this->options['useragent']);
		}
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($this->ch, CURLOPT_TIMEOUT, $this->options['timeout']);
		curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, $this->options['connection_timeout']);
		if (prefs::get_pref('proxy_host') != "") {
			curl_setopt($this->ch, CURLOPT_PROXY, prefs::get_pref('proxy_host'));
		}
		if (prefs::get_pref('proxy_user') != "" && prefs::get_pref('proxy_password') != "") {
			curl_setopt($this->ch, CURLOPT_PROXYUSERPWD, prefs::get_pref('proxy_user').':'.prefs::get_pref('proxy_password'));
		}
		curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);
		if ($this->options['header']) {
			curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->options['header']);
		}
		if ($this->options['postfields'] !== null) {
			$fields_string = http_build_query($this->options['postfields']);
			curl_setopt($this->ch, CURLOPT_POST, count($this->options['postfields']));
			curl_setopt($this->ch, CURLOPT_POSTFIELDS, $fields_string);
		}
		// WARNING. Don't put the _HEADER stuff in here, for some reason I can't be arsed
		// to figure out, it breaks get_data_to_file.
	}

	public function get_data_to_string() {

		//
		// get_data_to_string will always ignore the cache.
		// This function returns a boolean true if the download succeeded, false otherwise
		// It sets $this->content to the contents of the file, which can be retrievd by calling $this->get_data()
		//

		logger::debug("URL_DOWNLOADER", "Downloading",$this->options['url'],'to string');
		curl_setopt($this->ch, CURLOPT_HEADER, true);
		curl_setopt($this->ch, CURLOPT_HEADERFUNCTION, function($curl, $header) {
			$len = strlen($header);
			$this->headerlen += $len;
			$header = explode(':', $header, 2);
			if (count($header) < 2) // ignore invalid headers
				return $len;

			$name = ($header[0]);
			$this->headerarray[$name] = trim($header[1]);
			if (preg_match('/^Set-Cookie:\s*([^;]*)/mi', $name, $cookie) == 1) {
				logger::log('URL_DOWNLOADER', 'Found Cookie', $cookie);
        		$this->cookies[] = $cookie;
			}
			return $len;
		});
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
		$this->content = curl_exec($this->ch);
		return $this->get_final_info();
	}

	protected function get_cache_file($cache, $url){
		return 'prefs/jsoncache/'.$cache.'/'.md5($url);
	}

	public function get_data_to_file($file = null, $binary = false) {

		//
		// This function downloads a remote URL and stores the result in a file
		// If cache is set in the options when the class is instantiated, the file will be
		// a file in the cache. Otherwise it will be $file, if $file is set.
		// If neither cache nor $file are set, this behaves like get_data_to_string, for some reason.
		// If downloading to the cache and the cache file already exists, nothing will be downloaded
		// $this->from_cache will be set to true if the data was already in the cache
		// This function returns a boolean true if the download succeeded, false otherwise
		//

		$this->file = $file;
		$this->from_cache = false;
		if ($this->file === null && $this->options['cache'] === null) {
			return $this->get_data_to_string();
		} else if ($this->options['cache'] !== null) {
			logger::debug("URL_DOWNLOADER", "Downloading",$this->options['url'],'to file', $this->file);
			$this->file = $this->get_cache_file($this->options['cache'], $this->options['url']);
			if ($this->check_cache($this->file))
				return true;
		}
		logger::core("URL_DOWNLOADER", "Downloading to",$this->file);
		if (file_exists($this->file))
			unlink ($this->file);

		$open_mode = $binary ? 'wb' : 'w';
		$fp = fopen($this->file, $open_mode);
		curl_setopt($this->ch, CURLOPT_FILE, $fp);
		curl_exec($this->ch);
		fclose($fp);
		if (curl_getinfo($this->ch,CURLINFO_RESPONSE_CODE) != '200')
			unlink($this->file);

		return $this->get_final_info();
	}

	private function check_cache($file) {
		if (is_file($file)) {
			logger::debug("URL_DOWNLOADER", "Returning cached data ".$file);
			$this->from_cache = true;
			return true;
		} else {
			return false;
		}
	}

	private function get_final_info() {
		$this->status = curl_getinfo($this->ch, CURLINFO_RESPONSE_CODE);
		$this->content_type = curl_getinfo($this->ch, CURLINFO_CONTENT_TYPE);
		$this->info = curl_getinfo($this->ch);
		curl_close($this->ch);
		if ($this->get_status() == '200') {
			logger::core("URL_DOWNLOADER", "  ..  Download Success");
			return true;
		} else {
			logger::warn("URL_DOWNLOADER", "  ..  Download Failed With Status Code",$this->get_status());
			return false;
		}
	}

	public function get_data() {

		//
		// get_data_to_string sets $this->content when it downloads the data
		// but get_data_to_file doesn't
		//

		if ($this->file && !$this->content && file_exists($this->file)) {
			$this->content = file_get_contents($this->file);
		}
		return substr($this->content, $this->headerlen);
	}

	public function get_headers() {
		return $this->headerarray;
	}

	public function get_cookies() {
		return $this->cookies;
	}

	public function get_header($h) {
		if (array_key_exists($h, $this->headerarray)) {
			return $this->headerarray[$h];
		} else {
			return false;
		}
	}

	public function get_status() {
		return $this->status;
	}

	public function get_info() {
		return $this->info;
	}

	public function get_content_type() {
		return $this->content_type;
	}

}

?>