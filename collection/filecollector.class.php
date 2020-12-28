<?php
class fileCollector extends base_mpd_player {

	private $dbterms;

	public function __construct($dbterms) {
		$this->dbterms = $dbterms;
		parent::__construct();
	}

	public function doFileBrowse($path, $prefix) {
		logger::mark("DIRBROWSER", "Browsing",$path);
		$parts = true;
		$foundfile = false;
		$filedata = array();
		$dircount = 0;
		$this->send_command('lsinfo "'.format_for_mpd($path).'"');
		while(!feof($this->connection) && $parts) {
			$parts = $this->getline();
			if (is_array($parts)) {
				$s = trim($parts[1]);
				if (substr($s,0,1) != ".") {
					switch ($parts[0]) {
						case "file":
							if (!$foundfile) {
								$foundfile = true;
							} else {
								if (!array_key_exists('Title', $filedata) || substr($filedata['Title'], 0, 12) != "[unplayable]") {
									printFileItem($this->getFormatName($filedata), $filedata['file'], $filedata['Time']);
								}
								$filedata = array();
							}
							$filedata[$parts[0]] = $parts[1];
							break;

						case "playlist":
							if ($path != "") {
								// Ignore playlists located at the root. This is cleaner and makes more sense
								printPlaylistItem(basename($parts[1]), $parts[1]);
							}
							break;

						case "directory":
							uibits::printDirectoryItem($parts[1], basename($parts[1]), $prefix, $dircount, false);
							$dircount++;
							break;

						case "Title":
						case "Time":
						case "Artist":
						case "Album":
							$filedata[$parts[0]] = $parts[1];
							break;

					}
				}
			}
		}

		if (array_key_exists('file', $filedata)) {
			if (!array_key_exists('Title', $filedata) || substr($filedata['Title'], 0, 12) != "[unplayable]") {
				printFileItem($this->getFormatName($filedata), $filedata['file'], $filedata['Time']);
			}
		}
	}

	private function getFormatName($filedata) {
		if ($this->player_type == "mopidy" && !preg_match('/local:track:/', $filedata['file'])) {
			if (array_key_exists('Title', $filedata) && array_key_exists('Artist', $filedata)) {
				return concatenate_artist_names(array_unique(explode(';',$filedata['Artist']))).' - '.$filedata['Title'];
			}
			if (array_key_exists('Title', $filedata)) {
				return $filedata['Title'];
			}
			if (array_key_exists('Album', $filedata)) {
				return "Album: ".$filedata['Album'];
			}
		}
		return basename(rawurldecode($filedata['file']));
	}

	// Semi-Hacky way of doing a search and displaying the results as a file tree
	// It's not neat, but then I don't understand why anyone would want to display
	// search results this way in RompR.

	public function doFileSearch($cmd, $domains) {
		$tree = new mpdlistthing(null);
		$parts = true;
		$filedata = array();
		$foundfile = false;
		if ($domains === false || count($domains) == 0) {
			$domains = null;
		}
		$this->send_command($cmd);
		while(!feof($this->connection) && $parts) {
			$parts = $this->getline();
			if (is_array($parts)) {
				switch($parts[0]) {
					case "file":
						if (!$foundfile) {
							$foundfile = true;
						} else {
							if ($this->dbterms['tag'] !== null || $this->dbterms['rating'] !== null) {
								// If this is a search and we have tags or ratings to search for, check them here.
								if (prefs::$database->check_url_against_database($filedata['file'], $this->dbterms['tag'], $this->dbterms['rating']) == true) {
									if (!is_array($domains) || in_array(getDomain($filedata['file']),$domains)) {
										$tree->newItem($filedata);
									}
								}
							}  else {
								if (!is_array($domains) || in_array(getDomain($filedata['file']),$domains)) {
									$tree->newItem($filedata);
								}
							}
							$filedata = array();
						}
						$filedata[$parts[0]] = trim($parts[1]);
						break;

					case "playlist":
						$filedata[$parts[0]] = trim($parts[1]);
						if ($this->dbterms['tag'] === null && $this->dbterms['rating'] === null) {
							$tree->newItem($filedata);
						}
						$filedata = array();
						break;

					case "Title":
					case "Time":
					case "AlbumArtist":
					case "Album":
					case "Artist":
						if (in_array($parts[0], MPD_ARRAY_PARAMS)) {
							$filedata[$parts[0]] = array_unique(explode(';',trim($parts[1])));
						} else {
							$filedata[$parts[0]] = explode(';',trim($parts[1]))[0];
						}
						break;
				}
			}
		}

		if (array_key_exists('file', $filedata)) {
			if (!is_array($domains) || in_array(getDomain($filedata['file']),$domains)) {
				$tree->newItem($filedata);
			}
		}
		printFileSearch($tree);
	}
}
?>