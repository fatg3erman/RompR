<?php
class mpdlistthing {

	// Note: This is for displaying SEARCH RESULTS ONLY as a file tree.
	// Directory clicking only works on this when the entire results set
	// is loaded into the browser at once. Don't fuck with it, it's got teeth.

	public function __construct($name, $parent = null, $filedata = null) {
		$this->children = array();
		$this->name = $name;
		$this->parent = $parent;
		$this->filedata = $filedata;
		$this->dircount = 0;
	}

	public function newItem($filedata) {

		// This should only be called from outside the tree.
		// This is the root object's pre-parser

		if (array_key_exists('playlist', $filedata)) {
			$decodedpath = $filedata['playlist'];
			$filedata['file_display_name'] = basename($decodedpath);
		} else {
			$decodedpath = rawurldecode($filedata['file']);
		}

		if (substr($decodedpath, 0, 12) == "[unplayable]") {
			return;
		}

		// All the different fixups for all the different mopidy backends
		// and their various random ways of doing things.
		if (preg_match('/podcast\+http:\/\//', $decodedpath)) {
			$filedata['file_display_name'] = (array_key_exists('Title', $filedata)) ?
				$filedata['Title'] : basename($decodedpath);
			$filedata['file_display_name'] =
				preg_replace('/Album: /','',$filedata['file_display_name']);
			$decodedpath = preg_replace('/podcast\+http:\/\//','podcast/',$decodedpath);

		} else if (preg_match('/:artist:/', $decodedpath)) {
			$filedata['file_display_name'] = concatenate_artist_names($filedata['Artist']);
			$decodedpath = preg_replace('/(.+?):(.+?):/','$1/$2/',$decodedpath);

		} else if (preg_match('/:album:/', $decodedpath)) {
			$matches = array();
			$a = preg_match('/(.*?):(.*?):(.*)/',$decodedpath,$matches);
			if ($filedata['AlbumArtist'] === null) {
				$filedata['AlbumArtist'] = $filedata['Artist'] ? $filedata['Artist'] : "Unknown";
			}
			$decodedpath = $matches[1]."/".$matches[2]."/".
				concatenate_artist_names($filedata['AlbumArtist'])."/".$matches[3];
			$filedata['file_display_name'] = $filedata['Album'];

		} else if (preg_match('/local:track:/', $decodedpath)) {
			$filedata['file_display_name'] = basename($decodedpath);
			$decodedpath = preg_replace('/:track:/','/',$decodedpath);

		} else if (preg_match('/:track:/', $decodedpath)) {
			$matches = array();
			$a = preg_match('/(.*?):(.*?):(.*)/',$decodedpath,$matches);
			$decodedpath = $matches[1]."/".$matches[2]."/".
				concatenate_artist_names($filedata['Artist'])."/".
				$filedata['Album']."/".$matches[3];
			$filedata['file_display_name'] = $filedata['Title'];

		} else if (preg_match('/soundcloud:song\//', $decodedpath)) {
			$filedata['file_display_name'] = (array_key_exists('Title', $filedata)) ?
				$filedata['Title'] : basename($decodedpath);
			$decodedpath = preg_replace('/soundcloud:song/','soundcloud/'.
				concatenate_artist_names($filedata['Artist']),$decodedpath);

		} else if (preg_match('/^internetarchive:/', $decodedpath)) {
			$filedata['file_display_name'] = $filedata['Album'];
			$decodedpath = preg_replace('/internetarchive:/','internetarchive/',$decodedpath);

		} else if (preg_match('/youtube:video\//', $decodedpath)) {
			$filedata['file_display_name'] = (array_key_exists('Title', $filedata)) ?
				$filedata['Title'] : basename($decodedpath);
			$decodedpath = preg_replace('/youtube:video/','youtube',$decodedpath);

		} else if (preg_match('/tunein:station/', $decodedpath)) {
			$filedata['file_display_name'] = (array_key_exists('Album', $filedata)) ?
				$filedata['Album'] : basename($decodedpath);
			$decodedpath = 'tunein/';
			if (array_key_exists('Artist', $filedata)) {
				$decodedpath .= concatenate_artist_names($filedata['Artist']).'/';
			}

		} else {
			if (prefs::get_pref('player_backend') == "mopidy") {
				$filedata['file_display_name'] = (array_key_exists('Title', $filedata)) ?
					$filedata['Title'] : basename($decodedpath);
			} else {
				$filedata['file_display_name'] = basename($filedata['file']);
			}
		}

		$pathbits = explode('/', $decodedpath);
		$name = array_shift($pathbits);

		if (!array_key_exists($name, $this->children)) {
			$this->children[$name] = new mpdlistthing($name, $this);
		}

		$this->children[$name]->newChild($pathbits, $filedata);
	}

	public function newChild($pathbits, $filedata) {
		$name = array_shift($pathbits);
		if (count($pathbits) == 0) {
			$this->children[$name] = new mpdlistthing($filedata['file_display_name'], $this, $filedata);
		} else {
			if (!array_key_exists($name, $this->children)) {
				$this->children[$name] = new mpdlistthing($name, $this);
			}
			$this->children[$name]->newChild($pathbits, $filedata);
		}
	}

	public function getHTML($prefix) {
		if ($this->name !== null) {
			if (count($this->children) > 0) {
				// Must be a directory
				uibits::printDirectoryItem($this->parent->getName($this->name), $this->name, $prefix, $this->dircount, true);
				$this->parent->dircount++;
				foreach ($this->children as $child) {
					$child->getHTML($prefix.$this->dircount.'_');
				}
				print '</div>';
			} else {
				if (array_key_exists('playlist', $this->filedata)) {
					printPlaylistItem($this->filedata['file_display_name'],$this->filedata['file']);
				} else {
					printFileItem($this->filedata['file_display_name'], $this->filedata['file'], $this->filedata['Time']);
				}
			}
		} else {
			foreach ($this->children as $child) {
				$child->getHTML($prefix.$this->dircount.'_');
			}
		}
	}

	public function getName($name) {
		if ($this->name !== null) {
			$name = $this->name."/".$name;
		}
		if ($this->parent !== null) {
			$name = $this->parent->getName($name);
		}
		return $name;
	}

}
?>
