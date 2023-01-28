<?php
class player extends base_mpd_player {

	private $monitor;

	private const WEBSOCKET_SUFFIX = '/mopidy/ws';

	private const SPECIFIC_SEARCH_TERMS = [
		'any' => 'any',
		'title' => 'track_name',
		'album' => 'album',
		'artist' => 'artist',
		'albumartist' => 'albumartist',
		'composer' => 'composer',
		'performer' => 'performer',
		'genre' => 'genre'
	];

	public function check_track_load_command($uri) {
		return 'add';
	}

	public function musicCollectionUpdate() {
		// Note, using lsinfo is way better than using Mopidy's HTTP interface.
		// For one thing it's faster.
		// For another thing, some directores only return Mopidy's retarded Ref models
		// (a name and nothing else) but if you lsinfo them you get all the info
		// the backend has, which is usually a lot more than that.
		logger::mark("MOPIDY", "Starting Music Collection Update");
		if (prefs::get_pref('use_mopidy_scan')) {
			logger::mark('MOPIDY', 'Using mopidy local scan');
			$dir = getcwd();
			exec('sudo mopidyctl local scan >> '.$dir.'/prefs/monitor 2>&1');
			logger::mark('MOPIDY', 'Mopidy local scan finished');
		}
		$this->monitor = fopen('prefs/monitor','w');
		$dirs = prefs::get_pref('mopidy_collection_folders');
		logger::log('MOPIDY', 'Collection Folders Are', print_r($dirs, true));
		while (count($dirs) > 0) {
			$dir = array_shift($dirs);
			logger::log('MOPIDY', 'Scanning', $dir);
			fwrite($this->monitor, "\n<b>".language::gettext('label_scanningf', array($dir))."</b><br />".language::gettext('label_fremaining', array(count($dirs)))."\n");
			foreach ($this->parse_list_output('lsinfo "'.format_for_mpd($this->local_media_check($dir)).'"', $dirs, false) as $filedata) {
				yield $filedata;
			}
		}
		fwrite($this->monitor, "\nUpdating Database\n");
	}

	public function collectionUpdateDone() {
		saveCollectionPlayer('mopidy');
		fwrite($this->monitor, "\nRompR Is Done\n");
		fclose($this->monitor);
	}

	private function local_media_check($dir) {
		if ($dir == "Local media") {
			// Mopidy-Local-SQlite contains a virtual tree sorting things by various keys
			// If we scan the whole thing we scan every file about 8 times. This is stoopid.
			// Check to see if 'Local media/Albums' is browseable and use that instead if it is.
			// Using Local media/Folders causes every file to be re-scanned every time we update
			// the collection, which takes ages and also includes m3u and pls stuff that we don't want
			$r = $this->do_mpd_command('lsinfo "'.$dir.'/Albums"', false, false);
			if ($r === false) {
				return $dir;
			} else {
				return $dir.'/Albums';
			}
		}
		return $dir;
	}

	public function has_specific_search_function($mpdsearch, $domains) {

		if (prefs::get_pref('use_mopidy_search') == false) {
			logger::log('MOPIDY', 'Mopidy Search is disabled');
			return false;
		}

		if (count($mpdsearch) == 0) {
			logger::log('MOPIDY', 'Command is not search, not using websocket');
			return false;
		}

		if (prefs::get_player_param('websocket') === false) {
			logger::log('MOPIDY', 'Websocket not available, using standard search');
			return false;
		}

		if (count($domains) == 0) {
			logger::log('MOPIDY', 'No search domains in use, using standard search');
			return false;
		}

		$allowed_terms = array_keys(self::SPECIFIC_SEARCH_TERMS);
		$used_terms = array_keys($mpdsearch);

		$diff = array_diff($used_terms, $allowed_terms);
		if (count($diff) > 0) {
			logger::log('MOPIDY', 'Mopidy Search not permitted due to disallowed search terms');
			return false;
		}
		return true;
	}

	public function search_function($terms, $domains) {
		$search = [];
		$domains = array_map(function($a) {return $a.':'; }, $domains);
		logger::debug('MOPIDY', 'Search Backends are',$domains);
		foreach ($terms as $term => $value) {
			$search[self::SPECIFIC_SEARCH_TERMS[$term]] = $value;
		}
		logger::log('MOPIDY', 'Search Params are',$search);
		$result = $this->mopidy_http_request(
			$this->strip_http_port(),
			array(
				'method' => 'core.library.search',
				'params' => [
					'query' => $search,
					'uris' => $domains
				]
			)
		);

		if ($result === false) {
			logger::warn('MOPIDY', 'HTTP search request did not work');
			return;
		}

		$json = json_decode($result, true);
		if (array_key_exists('error', $json)) {
			logger::error('MOPIDY', 'HTTP request error', $json['error']['data']['message']);
			return;
		}

		if (!array_key_exists('result', $json) || !is_array($json['result'])) {
			logger::warn('MOPIDY', 'Did not get array result for search!');
			return;
		}

		foreach ($json['result'] as $uri_search) {
			$domain = getDomain($uri_search['uri']);
			if (array_key_exists('tracks', $uri_search)) {
				foreach ($uri_search['tracks'] as $track) {
					$filedata = $this->parse_mopidy_track($track, $domain);
					if ($this->sanitize_data($filedata)) {
						yield $filedata;
					}
				}
			}
			if (array_key_exists('albums', $uri_search)) {
				foreach ($uri_search['albums'] as $track) {
					$filedata = $this->parse_mopidy_album($track, $domain);
					if ($this->sanitize_data($filedata)) {
						yield $filedata;
					}
				}
			}
		}
	}

	// (
	//     [jsonrpc] => 2.0
	//     [id] => 1
	//     [result] => Array
	//         (
	//             [0] => Array
	//                 (
	//                     [__model__] => SearchResult
	//                     [uri] => ytmusic:search
	//                     [tracks] => Array
	//                         (
	//                             [0] => Array
	//                                 (
	//                                     [__model__] => Track
	//                                     [uri] => ytmusic:track:LTBeAXo9iBc
	//                                     [name] => A Galaxy Of Scars
	//                                     [artists] => Array
	//                                         (
	//                                             [0] => Array
	//                                                 (
	//                                                     [__model__] => Artist
	//                                                     [uri] => ytmusic:artist:UCSdpLrznEqa8XUES6qHtWig
	//                                                     [name] => The Third Eye Foundation
	//                                                     [sortname] => The Third Eye Foundation
	//                                                     [musicbrainz_id] =>
	//                                                 )

	//                                         )

	//                                     [album] => Array
	//                                         (
	//                                             [__model__] => Album
	//                                             [uri] => ytmusic:album:MPREb_cthDNAOfKAL
	//                                             [name] => You Guys Kill Me
	//                                             [artists] => Array
	//                                                 (
	//                                                     [0] => Array
	//                                                         (
	//                                                             [__model__] => Artist
	//                                                             [uri] => ytmusic:artist:UCSdpLrznEqa8XUES6qHtWig
	//                                                             [name] => The Third Eye Foundation
	//                                                             [sortname] => The Third Eye Foundation
	//                                                             [musicbrainz_id] =>
	//                                                         )

	//                                                 )

	//                                             [date] => 1998
	//                                             [musicbrainz_id] =>
	//                                         )

	//                                     [genre] =>
	//                                     [date] => 0000
	//                                     [bitrate] => 0
	//                                     [comment] =>
	//                                     [musicbrainz_id] =>
	//                                 )

	private function parse_mopidy_track(&$track, $domain) {
		$filedata = MPD_FILE_MODEL;
		$candidate_date = $filedata['Date'];
		// file
		$filedata['file'] = $track['uri'];
		// domain
		$filedata['domain'] = $domain;
		// Title
		$filedata['Title'] = $track['name'];
		// Artist and MUSICBRAINZ_ARTISTID
		if (array_key_exists('artists', $track)) {
			list($artists, $amb) = $this->parse_mopidy_artists($track['artists']);
			if (count($artists) > 0)
				$filedata['Artist'] = $artists;
			if (count($amb) > 0)
				$filedata['MUSICBRAINZ_ARTISTID'] = $amb;
		}
		// Album, X-AlbumUri, AlbumArtist, MUSICBRAINZ_ALBUMID, and MUSICBrAINZ_ALBUMARTISTID
		if (array_key_exists('album', $track)) {
			if (array_key_exists('name', $track['album']) && $track['album']['name'])
				$filedata['Album'] = $track['album']['name'];
			if (array_key_exists('uri', $track['album']) && $track['album']['uri'])
				$filedata['X-AlbumUri'] = $track['album']['uri'];
			if (array_key_exists('date', $track['album']) && $track['album']['date'] && $track['album']['date'] != '0000')
				$candidate_date = $track['album']['date'];
			if (array_key_exists('musicbrainz_id', $track['album']) && $track['album']['musicbrainz_id'])
				$filedata['MUSICBRAINZ_ALBUMID'] = $track['album']['musicbrainz_id'];
			if (array_key_exists('artists', $track['album'])) {
				list($artists, $amb) = $this->parse_mopidy_artists($track['album']['artists']);
				if (count($artists) > 0)
					$filedata['AlbumArtist'] = $artists;
				if (count($amb) > 0)
					$filedata['MUSICBRAINZ_ALBUMARTISTID'] = $amb[0];
			}
		}
		// Track
		if (array_key_exists('track_no', $track) && $track['track_no'])
			$filedata['Track'] = $track['track_no'];
		// Time
		if (array_key_exists('length', $track) && $track['length'])
			$filedata['Time'] = intval($track['length'] / 1000);
		// Date - prioritise album date as this is often set when track data isn't
		if ($candidate_date !== null && array_key_exists('date', $track) && $track['date'] && $track['date'] != '0000')
			$candidate_date = $track['date'];
		// Last-Modified
		if (array_key_exists('last_modified', $track) && $track['last_modified'])
			$filedata['Last-Modified'] = $track['last_modified'];
		// Disc
		if (array_key_exists('disc_no', $track) && $track['disc_no'])
			$filedata['Disc'] = $track['disc_no'];
		// Comment
		if (array_key_exists('comment', $track) && $track['comment'])
			$filedata['Comment'] = $track['comment'];
		// Composer
		if (array_key_exists('composers', $track)) {
			list($artists, $amb) = $this->parse_mopidy_artists($track['composers']);
			if (count($artists) > 0)
				$filedata['Composer'] = $artists;
		}
		// Performer
		if (array_key_exists('performers', $track)) {
			list($artists, $amb) = $this->parse_mopidy_artists($track['performers']);
			if (count($artists) > 0)
				$filedata['Performer'] = $artists;
		}
		// Genre
		if (array_key_exists('genre', $track) && $track['genre'])
			$filedata['Genre'] = $track['genre'];
		// MUSICBRAINZ_TRACKID
		if (array_key_exists('musicbrainz_id', $track) && $track['musicbrainz_id'])
			$filedata['MUSICBRAINZ_TRACKID'] = $track['musicbrainz_id'];

		$filedata['Date'] = $candidate_date;

		return $filedata;

	}

	private function parse_mopidy_artists(&$data) {
		$artists = [];
		$amb = [];
		foreach ($data as $artist) {
			if ($artist['name'])
				$artists[] = $artist['name'];
			if ($artist['musicbrainz_id'])
				$amb[] = $artist['musicbrainz_id'];
		}
		return array($artists, $amb);
	}

	// [albums] => Array
	//     (
	//         [0] => Array
	//             (
	//                 [__model__] => Album
	//                 [uri] => ytmusic:album:MPREb_OVwt7A2TE3t
	//                 [name] => The Mess We Made
	//                 [artists] => Array
	//                     (
	//                         [0] => Array
	//                             (
	//                                 [__model__] => Artist
	//                                 [uri] => ytmusic:artist:UCSI2myt7tP_R8RJKw6bbNig
	//                                 [name] => Matt Elliott
	//                                 [sortname] => Matt Elliott
	//                                 [musicbrainz_id] =>
	//                             )

	//                     )

	//                 [date] => 2003
	//                 [musicbrainz_id] =>
	//             )


	private function parse_mopidy_album(&$track, $domain) {
		$filedata = MPD_FILE_MODEL;
		// file
		$filedata['file'] = $track['uri'];
		// domain
		$filedata['domain'] = $domain;
		// Title
		$filedata['Title'] = 'Album: '.$track['name'];
		// Artist and MUSICBRAINZ_ARTISTID
		// We don't set AlbumArtist for albums, that's consistent with what Mopidy-MPD does
		if (array_key_exists('artists', $track)) {
			list($artists, $amb) = $this->parse_mopidy_artists($track['artists']);
			if (count($artists) > 0)
				$filedata['Artist'] = $artists;
			if (count($amb) > 0)
				$filedata['MUSICBRAINZ_ALBUMARTISTID'] = $amb[0];
		}
		// Album
		$filedata['Album'] = $track['name'];
		// X-AlbumUri
		$filedata['X-AlbumUri'] = $track['uri'];
		// Date
		if (array_key_exists('date', $track) && $track['date'] && $track['date'] != '0000')
			$filedata['Date'] = $track['date'];

		return $filedata;

	}


	protected function player_specific_fixups(&$filedata) {
		if (strpos($filedata['file'], 'spotify:artist:') !== false) {
			$this->to_browse[] = [
				'Uri' => $filedata['file'],
				'Name' => preg_replace('/Artist: /', '', $filedata['Title'])
			];
			logger::log('MOPIDY', 'Marking',$filedata['Title'],$filedata['file'],'as browse artist');
			return false;
		} else if (strpos($filedata['file'], ':album:') !== false) {
			$filedata['X-AlbumUri'] = $filedata['file'];
			$filedata['Disc'] = 0;
			$filedata['Track'] = 0;
		}

		switch($filedata['domain']) {
			case 'local':
				$this->preprocess_local($filedata);
				break;

			case "soundcloud":
				$this->preprocess_soundcloud($filedata);
				break;

			case "youtube":
				$this->preprocess_youtube($filedata);
				break;

			case "ytmusic":
				$this->preprocess_ytmusic($filedata);
				break;

			case "spotify":
				$filedata['folder'] = $filedata['X-AlbumUri'];
				break;

			case "internetarchive":
				$this->preprocess_internetarchive($filedata);
				break;

			case "podcast":
				$this->preprocess_podcast($filedata);
				break;

			case 'http':
			case 'https':
			case 'mms':
			case 'mmsh':
			case 'mmst':
			case 'mmsu':
			case 'gopher':
			case 'rtp':
			case 'rtsp':
			case 'rtmp':
			case 'rtmpt':
			case 'rtmps':
			case 'dirble':
			case 'tunein':
			case 'radio-de':
			case 'audioaddict':
			case 'oe1':
			case 'bassdrive':
				$this->preprocess_stream($filedata);
				break;

			default:
				$this->check_undefined_tags($filedata);
				$filedata['folder'] = dirname($filedata['unmopfile']);
				break;
		}

		return true;

	}

	private function preprocess_local(&$filedata) {
		// mopidy-local sets album URIs for local albums, but sometimes it gets it very wrong.
		// We don't need Album URIs for local tracks, since we can already add an entire album
		$filedata['X-AlbumUri'] = null;
		$this->check_undefined_tags($filedata);
		$filedata['folder'] = dirname($filedata['unmopfile']);
		if (prefs::get_pref('audiobook_directory') != '') {
			$f = rawurldecode($filedata['folder']);
			if (strpos($f, prefs::get_pref('audiobook_directory')) === 0) {
				$filedata['type'] = 'audiobook';
			}
		}
	}

	private function preprocess_internetarchive(&$filedata) {
		$this->check_undefined_tags($filedata);
		$filedata['X-AlbumUri'] = $filedata['file'];
		$filedata['folder'] = $filedata['file'];
		$filedata['AlbumArtist'] = "Internet Archive";
	}

	private function preprocess_podcast(&$filedata) {
		$filedata['folder'] = $filedata['X-AlbumUri'];
		if ($filedata['Artist'] !== null) {
			$filedata['AlbumArtist'] = $filedata['Artist'];
		}
		if ($filedata['AlbumArtist'] === null) {
			$filedata['AlbumArtist'] = array("Podcasts");
		}
		if (is_array($filedata['Artist']) &&
			($filedata['Artist'][0] == "http" ||
			$filedata['Artist'][0] == "https" ||
			$filedata['Artist'][0] == "ftp" ||
			$filedata['Artist'][0] == "file" ||
			substr($filedata['Artist'][0],0,7) == "podcast")) {
			$filedata['Artist'] = $filedata['AlbumArtist'];
		}
		$filedata['type'] = 'podcast';
	}

	private function preprocess_stream(&$filedata) {

		$filedata['Track'] = null;

		list (  $filedata['Title'],
				$filedata['Time'],
				$filedata['Artist'],
				$filedata['Album'],
				$filedata['folder'],
				$filedata['type'],
				$filedata['X-AlbumImage'],
				$filedata['station'],
				$filedata['stream'],
				$filedata['AlbumArtist'],
				$filedata['StreamIndex'],
				$filedata['Comment'],
				$filedata['ImgKey']) = $this->check_radio_and_podcasts($filedata);

		if (strrpos($filedata['file'], '#') !== false) {
			# Fave radio stations added by Cantata/MPDroid
			$filedata['Album'] = substr($filedata['file'], strrpos($filedata['file'], '#')+1, strlen($filedata['file']));
		}

		if (strpos($filedata['file'], 'bassdrive.com') !== false) {
			$filedata['Album'] = 'Bassdrive';
		}

		// Mopidy's podcast backend
		if ($filedata['Genre'] == "Podcast") {
			$filedata['type'] = "podcast";
		}

	}

	private function preprocess_soundcloud(&$filedata) {
		$filedata['folder'] = concatenate_artist_names($filedata['Artist']);
		if (!$filedata['AlbumArtist'])
			$filedata['AlbumArtist'] = $filedata['Artist'];

		if (!$filedata['X-AlbumUri'])
			$filedata['X-AlbumUri'] = $filedata['file'];

		if ($filedata['Title'] && !$filedata['Album'])
			$filedata['Album'] = $filedata['Title'];

		if ($filedata['X-AlbumImage'])
			$filedata['X-AlbumImage'] = 'getRemoteImage.php?url='.rawurlencode($filedata['X-AlbumImage']);

	}

	private function preprocess_youtube(&$filedata) {

		// These settings make Mopidy-Youtube work best in Youtube Video mode
		// They're not so good in Youtube Music mode. I recommend Mopidy-YTMusic
		// (my fork) for Youtube Music

		$filedata['folder'] = hash('md2', $filedata['X-AlbumUri'], false);
		if (!$filedata['AlbumArtist'])
			$filedata['AlbumArtist'] = $filedata['Artist'];

		if (!$filedata['X-AlbumUri'])
			$filedata['X-AlbumUri'] = $filedata['file'];

		// This is definitely a good idea for YouTube videos since none of them
		// have an album but they don't all want to appear under the same nameless album.
		if ($filedata['Title'] && (!$filedata['Album'] || $filedata['Album'] == 'YouTube Playlist'))
			$filedata['Album'] = $filedata['Title'];

		// if (strpos($filedata['Artist'][0], 'YouTube Playlist') !== false) {
		// 	$filedata['Artist'] = ['YouTube Playlists'];
		// }

		if ($filedata['X-AlbumImage'])
			$filedata['X-AlbumImage'] = 'getRemoteImage.php?url='.rawurlencode($filedata['X-AlbumImage']);

	}

	private function preprocess_ytmusic(&$filedata) {
		$filedata['folder'] = hash('md2', $filedata['X-AlbumUri'], false);
		// I think this is a good idea. I'm not really sure, but if we're building the collection
		// from YTMusic Liked Songs, none of them have an album so they all appear under a nameless
		// album under Various Artists.
		// I think these ones that come back without Album info are Youtube Videos, as for Youtube.
		if ($filedata['Title'] && !$filedata['Album'])
			$filedata['Album'] = $filedata['Title'];

		// Don't try to to set X-AlbumUri for YTMusic, as might get it from
		// another track or from an Album: search results for that album.

		if ($filedata['X-AlbumImage'])
			$filedata['X-AlbumImage'] = 'getRemoteImage.php?url='.rawurlencode($filedata['X-AlbumImage']);

	}

	private function check_radio_and_podcasts($filedata) {

		$url = $filedata['file'];

		// Check for any http files added to the collection or downloaded youtube tracks
		$result = prefs::$database->check_stream_in_collection($url);
		foreach ($result as $obj) {
			logger::log("STREAMHANDLER", "Found Track in collection!",$obj->title);
			return array(
				$obj->title,
				$obj->duration,
				array($obj->artist),
				$obj->album,
				md5($obj->album),
				'local',
				$obj->image,
				null,
				'',
				array($obj->albumartist),
				null,
				'',
				$obj->imgkey
			);
		}

		// Do podcasts first. Podcasts played fro TuneIn get added as radio stations, and then if we play that track again
		// via podcasts we want to make sure we pick up the details.

		$result = prefs::$database->find_podcast_track_from_url($url);
		foreach ($result as $obj) {
			logger::log("STREAMHANDLER", "Found Podcast",$obj->title);
			return array(
				($obj->title == '') ? $filedata['Title'] : $obj->title,
				// Mopidy's estimate of the duration is frequently more accurate than that supplied in the RSS
				(array_key_exists('Time', $filedata) && $filedata['Time'] > 0) ? $filedata['Time'] : $obj->duration,
				($obj->artist == '') ? $filedata['Artist'] : array($obj->artist),
				($obj->album == '') ? $filedata['Album'] : $obj->album,
				md5($obj->album),
				'podcast',
				$obj->image,
				null,
				'',
				($obj->albumartist == '') ? $filedata['AlbumArtist'] : array($obj->albumartist),
				null,
				format_podcast_text($obj->comment),
				null
			);
		}

		$result = prefs::$database->find_radio_track_from_url($url);
		foreach ($result as $obj) {
			logger::log("STREAMHANDLER", "Found Radio Station ".$obj->StationName);
			// Munge munge munge to make it looks pretty
			if ($obj->StationName != '') {
				logger::trace("STREAMHANDLER", "  Setting Album name from database ".$obj->StationName);
				$album = $obj->StationName;
			} else if ($filedata['Name'] && $filedata['Name'] != 'no name' && strpos($filedata['Name'], ' ') !== false) {
				logger::trace("STREAMHANDLER", "  Setting Album from Name ".$filedata['Name']);
				$album = $filedata['Name'];
			} else if ($filedata['Name'] == null && $filedata['Title'] != null && $filedata['Title'] != 'no name' &&
				$filedata['Artist'] == null && $filedata['Album'] == null && strpos($filedata['Title'], ' ') !== false) {
				logger::trace("STREAMHANDLER", "  Setting Album from Title ".$filedata['Title']);
				$album = $filedata['Title'];
				$filedata['Title'] = null;
			} else {
				logger::warn("STREAMHANDLER", "  No information to set Album field");
				$album = ROMPR_UNKNOWN_STREAM;
			}
			return array (
				$filedata['Title'] === null ? '' : $filedata['Title'],
				0,
				$filedata['Artist'],
				$album,
				$obj->PlaylistUrl,
				"stream",
				($obj->Image == '') ? $filedata['X-AlbumImage'] : $obj->Image,
				$this->getDummyStation($url),
				$obj->PrettyStream,
				$filedata['AlbumArtist'],
				$obj->Stationindex,
				array_key_exists('Comment', $filedata) ? $filedata['Comment'] : '',
				null
			);
		}

		logger::warn("STREAMHANDLER", "Stream Track",$filedata['file'],"from",$filedata['domain'],"was not found in database");

		if ($filedata['Album']) {
			$album = $filedata['Album'];
		} else if ($filedata['Name']) {
			logger::trace("STREAMHANDLER", "  Setting Album from Name ".$filedata['Name']);
			$album = $filedata['Name'];
			if ($filedata['Pos'] !== null) {
				prefs::$database->update_radio_station_name(array('streamid' => null,'uri' => $filedata['file'], 'name' => $album));
			}
		} else if ($filedata['Name'] == null && $filedata['Title'] != null && $filedata['Artist'] == null && $filedata['Album'] == null) {
			logger::trace("STREAMHANDLER", "  Setting Album from Title ".$filedata['Title']);
			$album = $filedata['Title'];
			$filedata['Title'] = null;
			if ($filedata['Pos'] !== null) {
				prefs::$database->update_radio_station_name(array('streamid' => null,'uri' => $filedata['file'], 'name' => $album));
			}
		} else {
			logger::warn("STREAMHANDLER", "  No information to set Album field");
			$album = ROMPR_UNKNOWN_STREAM;
		}
		return array(
			$filedata['Title'],
			0,
			$filedata['Artist'],
			$album,
			$this->getStreamFolder(unwanted_array($url)),
			"stream",
			($filedata['X-AlbumImage'] == null) ? '' : $filedata['X-AlbumImage'],
			$this->getDummyStation(unwanted_array($url)),
			null,
			$filedata['AlbumArtist'],
			null,
			array_key_exists('Comment', $filedata) ? $filedata['Comment'] : '',
			null
		);

	}

	public function get_checked_url($url) {
		return array('clicktrack', $url);
	}

	public function get_replay_gain_state() {
		return array();
	}

	public function toggle_consume($value) {
		if ($value == 0) {
			logger::info('MOPIDY', 'Disabling RompR consume');
			prefs::set_player_param(['do_consume' => false]);
		} else {
			logger::info('POSTCOMMAND', 'Enabling RompR consume');
			prefs::set_player_param(['do_consume' => true]);
		}
		return false;
	}

	public function get_consume($value) {
		$pd = prefs::get_player_def();
		return $pd['do_consume'] ? 1 : 0;
	}

	// This is here to allow us to force consume to Off when we connect to Mopidy
	// so that our local consume can take over;
	public function set_consume_state() {
		$this->do_command_list(['consume 0']);
	}

	public function force_consume_state($state) {
		$this->toggle_consume($state);
	}

	public static function is_personal_playlist($playlist) {
		if (strpos($playlist, '(by ') !== false) {
			return false;
		}
		return true;
	}

	public function probe_websocket() {
		if ($this->websocket_port != '') {
			logger::info('MOPIDYHTTP', 'Probing HTTP API for',prefs::currenthost());
			$result = $this->mopidy_http_request(
				$this->ip.':'.$this->websocket_port,
				array(
					'method' => 'core.get_version'
				)
			);
			if ($result === false) {
				logger::log('MOPIDYHTTP', 'Mopidy HTTP API Not Available for',prefs::currenthost());
				prefs::set_player_param(['websocket' => false]);
			} else {
				logger::log('MOPIDYHTTP', 'Connected to Mopidy HTTP API Successfully on',prefs::currenthost());
				logger::trace('MOPIDYHTTP', $result);
				// $r = json_decode($result, true);
				// logger::log('MOPIDY', print_r($r, true));
				$http_server = nice_server_address($this->ip);
				prefs::set_player_param(['websocket' => $http_server.':'.$this->websocket_port.self::WEBSOCKET_SUFFIX]);
				logger::log('MOPIDYHTTP', 'Using',prefs::get_player_param('websocket'),'for Mopidy HTTP on',prefs::currenthost());
			}
		} else {
			logger::log('MOPIDYHTTP', 'Mopidy HTTP API Not Configured for',prefs::currenthost());
			prefs::set_player_param(['websocket' => false]);
		}
	}

	public function api_test() {
		$result = $this->mopidy_http_request(
			$this->ip.':'.$this->websocket_port,
			array(
				'method' => 'core.library.browse',
				'params' => [
					'uri' => 'ytmusic:liked'

				]
			)
		);
		if ($result === false) {
			return ['error' => 'Mopidy HTTP API Not Available'];
		} else {
			logger::log('MOPIDYHTTP', 'Connected to Mopidy HTTP API Successfully');
			logger::trace('MOPIDYHTTP', $result);
			return json_decode($result, true);
		}
	}

	private function mopidy_http_request($port, $data) {
		$url = 'http://'.$port.'/mopidy/rpc';

		$data['jsonrpc'] = '2.0';
		$data['id'] = 1;

		$options = array(
		    'http' => array(
		        'header'  => "Content-Type: application/json\r\n",
		        'method'  => 'POST',
		        'content' => json_encode($data)
		    )
		);
		$context  = stream_context_create($options);
		// Disable reporting of warnings for this call otherwise it spaffs into the error log
		// if the connection doesn't work.
		error_reporting(E_ERROR);
		$cheese = file_get_contents($url, false, $context);
		error_reporting();
		return $cheese;
	}

	public function search_for_album_image($albumimage) {
		$retval = '';
		// yt:playlist just hangs every time and locks Mopidy completely
		if ($albumimage->albumuri && strpos($albumimage->albumuri, ':playlist') === false) {
			logger::log('GETALBUMCOVER', 'Trying Mopidy-Images. AlbumURI is', $albumimage->albumuri);
			$retval = $this->find_album_image($albumimage->albumuri);
		} else if ($albumimage->trackuri && strpos($albumimage->trackuri, ':playlist') === false) {
			logger::log('GETALBUMCOVER', 'Trying Mopidy-Images. TrackURI is', $albumimage->trackuri);
			$retval = $this->find_album_image($albumimage->trackuri);
		}
		return $retval;
	}

	// So that checklocalcover.php doesn't crash when we're using Mopidy
	public function albumart($uri, $embedded) {
		return '';
	}

	private function strip_http_port() {
		return str_replace(self::WEBSOCKET_SUFFIX, '', prefs::get_player_param('websocket'));
	}

	public function find_album_image($uri) {
		if (prefs::get_player_param('websocket') === false)
			return '';

		$retval = '';
		$result = $this->mopidy_http_request(
			$this->strip_http_port(),
			array(
				'method' => 'core.library.get_images',
				"params" => array(
					"uris" => array($uri)
				)
			)
		);
		if ($result !== false) {
			$biggest = 0;
			logger::log('MOPIDYHTTP', 'Connected to Mopidy HTTP API Successfully');
			logger::debug('MOPIDYHTTP', $result);
			$json = json_decode($result, true);
			if (array_key_exists('error', $json)) {
				logger::warn('MOPIDYHTTP', 'Summit went awry', $json);
			} else if (array_key_exists($uri, $json['result']) && is_array($json['result'][$uri])) {
				foreach ($json['result'][$uri] as $image) {
					if (!array_key_exists('width', $image)) {
						$retval = ($retval == '') ? $image['uri'] : $this->compare_images($retval, $image['uri']);
					} else if ($image['width'] > $biggest) {
						$retval = $image['uri'];
						$biggest = $image['width'];
					}
				}
			}
		}
		if (strpos($retval, '/local/') === 0) {
			$retval = 'http://'.$this->strip_http_port().$retval;
		}
		if (basename($retval) == 'default.jpg' && strpos($retval, 'ytimg.com') !== false) {
			logger::log('MOPIDYHTTP', 'Mopidy-Youtube only returned youtube default image. Checking for hqdefault');
			$new_url = dirname($retval).'/hqdefault.jpg';
			$mrchunks = new url_downloader(['url' => $new_url]);
			if ($mrchunks->get_data_to_string()) {
				$retval = $new_url;
			}
		}
		logger::log('MOPIDYHTTP', 'Returning', $retval);
		return $retval;
	}

	private function compare_images($current, $candidate) {
		$retval = $current;
		$ours = strtolower(pathinfo($current, PATHINFO_FILENAME));
		$theirs = strtolower(pathinfo($candidate, PATHINFO_FILENAME));
		if ($ours == 'default' && ($theirs == 'mqdefault' || $theirs == 'hqdefault')) {
			$retval = $candidate;
		}
		if ($ours == 'mqdefault' && $theirs == 'hqdefault') {
			$retval = $candidate;
		}
		return $retval;
	}

}

?>