<?php

define('ROMPR_MAX_TRACKS_PER_TRANSACTION', 1000);
define('ROMPR_COLLECTION_VERSION', 6);
define('ROMPR_IMAGE_VERSION', 4);
define('ROMPR_SCHEMA_VERSION', 71);
define('ROMPR_VERSION', '1.52');
define('ROMPR_IDSTRING', 'RompR Music Player '.ROMPR_VERSION);
define('ROMPR_MOPIDY_MIN_VERSION', 1.1);
define('ROMPR_UNKNOWN_STREAM', "Unknown Internet Stream");
define('ROMPR_MIN_SQLITE_VERSION', '3.24');
define('ROMPR_MIN_MYSQL_VERSION', '8.0.19');
define('ROMPR_MIN_MARIADB_VERSION', '10.5.0');
define('ROMPR_MIN_PHP_VERSION', '7.0.0');

define('ROMPR_MIN_TRACKS_TO_DETERMINE_COMPILATION', 3);
define('ROMPR_MIN_NOT_COMPILATION_THRESHOLD', 0.6);

define('REFRESHOPTION_NEVER', 0);
define('REFRESHOPTION_HOURLY', 1);
define('REFRESHOPTION_DAILY', 2);
define('REFRESHOPTION_WEEKLY', 3);
define('REFRESHOPTION_MONTHLY', 4);

define('SORTMODE_NEWESTFIRST', 0);
define('SORTMODE_OLDESTFIRST', 1);

define('DISPLAYMODE_ALL', 0);
define('DISPLAYMODE_NEW', 1);
define('DISPLAYMODE_UNLISTENED', 2);
define('DISPLAYMODE_DOWNLOADEDNEW', 3);
define('DISPLAYMODE_DOWNLOADED', 4);

define('ROMPR_PODCAST_TABLE_VERSION', 4);

define('ADDED_ALL_TIME', 0);
define('ADDED_TODAY', 1);
define('ADDED_THIS_WEEK', 2);
define('ADDED_THIS_MONTH', 3);
define('ADDED_THIS_YEAR', 4);

// Safe definitions for setups that do not have a full set of image support built in,
// Otherwise we spam the server logs with udefined constant errors.
// These are the MIME types that make it compatible with imagemagick
if (!defined('IMAGETYPE_JPEG')) {
	define('IMAGETYPE_JPEG', 'image/jpeg');
}
if (!defined('IMAGETYPE_GIF')) {
	define('IMAGETYPE_GIF', 'image/gif');
}
if (!defined('IMAGETYPE_PNG')) {
	define('IMAGETYPE_PNG', 'image/png');
}
if (!defined('IMAGETYPE_WBMP')) {
	define('IMAGETYPE_WBMP', 'image/wbmp');
}
if (!defined('IMAGETYPE_XBM')) {
	define('IMAGETYPE_XBM', 'image/xbm');
}
if (!defined('IMAGETYPE_WEBP')) {
	define('IMAGETYPE_WEBP', 'image/webp');
}
if (!defined('IMAGETYPE_BMP')) {
	define('IMAGETYPE_BMP', 'image/bmp');
}
if (!defined('IMAGETYPE_SVG')) {
	define('IMAGETYPE_SVG', 'image/svg+xml');
}

define('IMAGESIZE_SMALL', 100);
define('IMAGESIZE_SMALLISH', 250);
define('IMAGESIZE_MEDIUM', 400);

define('IMAGEQUALITY_SMALL', 75);
define('IMAGEQUALITY_SMALLISH', 70);
define('IMAGEQUALITY_MEDIUM', 70);
define('IMAGEQUALITY_ASDOWNLOADED', 90);

define('ORIENTATION_PORTRAIT', 0);
define('ORIENTATION_LANDSCAPE', 1);

define('MPD_FILE_MODEL', array(
		'file' => null,
		'domain' => 'local',
		'type' => 'local',
		'station' => null,
		'stream' => null,
		'folder' => null,
		'Title' => null,
		'Album' => null,
		'Artist' => null,
		'Track' => 0,
		'Name' => null,
		'AlbumArtist' => null,
		'Time' => 0,
		'X-AlbumUri' => null,
		'playlist' => '',
		'X-AlbumImage' => null,
		'Date' => null,
		'OriginalDate' => null,
		'Last-Modified' => '0',
		'Disc' => null,
		'Composer' => null,
		'Performer' => null,
		'Genre' => 'None',
		'ImgKey' => null,
		'StreamIndex' => null,
		'Searched' => 0,
		'Playcount' => 0,
		'Comment' => '',
		// Never send null in any musicbrainz id as it prevents plugins from
		// waiting on lastfm to find one
		'MUSICBRAINZ_ALBUMID' => '',
		'MUSICBRAINZ_ARTISTID' => array(''),
		'MUSICBRAINZ_ALBUMARTISTID' => '',
		'MUSICBRAINZ_TRACKID' => '',
		'Id' => null,
		'Pos' => null,
		'ttindex' => null,
		'trackartist_index' => null,
		'albumartist_index' => null,
		'album_index' => null,
		'hidden' => 0,
		"year" => null,
		'isaudiobook' => 0
	)
);

define('MPD_ARRAY_PARAMS', array(
		"Artist",
		"AlbumArtist",
		"Composer",
		"Performer",
		"MUSICBRAINZ_ARTISTID",
	)
);

// Rompr's internal file model used in the Javascript side is a merge of the MPD_FILE_MODEL and ROMPR_FILE_MODEL
// it is created in class playlistCollection

define('ROMPR_FILE_MODEL', array(
		"progress" => 0,
		"albumartist" => '',
		"trackartist" => '',
		"lastplayed" => null,
		"streamuri" => null,
		"images" => '',
		"urionly" => 0,
		'hidden' => 0,
		"attributes" => null,
		"metadata" => array(
			"iscomposer" => 'false',
			"artists" => array(),
			"album" => array(
				"name" => '',
				"artist" => '',
				"musicbrainz_id" => '',
				"uri" => null
			),
			"track" => array(
				"name" => '',
				"musicbrainz_id" => '',
			),
		)
	)
);

//
// Mapping of collection/sortby classes to labels
//

const COLLECTION_SORT_MODES = array(
	'artist' 		=> 'label_artists',
	'album' 		=> 'label_albums',
	'albumbyartist' => 'label_albumsbyartist',
	'genre' 		=> 'label_genre',
	'rating' 		=> 'label_rating',
	'tag' 			=> 'label_tag'
);

//
// Constants For Custom Smart Radio Stations
//

// These indices don't start at zero, to make sure json_encode includes them when we encode CUSTOM_RADIO_ITEMS
// Also there are gaps so we can insert new ones
define('RADIO_RULE_OPTIONS_STRING_IS', 10);
define('RADIO_RULE_OPTIONS_STRING_IS_NOT', 20);
define('RADIO_RULE_OPTIONS_STRING_CONTAINS', 30);
define('RADIO_RULE_OPTIONS_STRING_NOT_CONTAINS', 40);
define('RADIO_RULE_OPTIONS_STRING_EXISTS', 45);
define('RADIO_RULE_OPTIONS_INTEGER_LESSTHAN', 50);
define('RADIO_RULE_OPTIONS_INTEGER_EQUALS', 60);
define('RADIO_RULE_OPTIONS_INTEGER_GREATERTHAN', 70);

const RADIO_OPTIONS_STRING = array(
	RADIO_RULE_OPTIONS_STRING_IS 			=> 'label_is',
	RADIO_RULE_OPTIONS_STRING_IS_NOT		=> 'label_is_not',
	RADIO_RULE_OPTIONS_STRING_CONTAINS		=> 'label_contains',
	RADIO_RULE_OPTIONS_STRING_NOT_CONTAINS	=> 'label_does_not_contain'
);

const RADIO_OPTIONS_TAG = array(
	RADIO_RULE_OPTIONS_STRING_IS 			=> 'label_is',
	RADIO_RULE_OPTIONS_STRING_IS_NOT		=> 'label_is_not',
	RADIO_RULE_OPTIONS_STRING_CONTAINS		=> 'label_contains',
	RADIO_RULE_OPTIONS_STRING_NOT_CONTAINS	=> 'label_does_not_contain',
	RADIO_RULE_OPTIONS_STRING_EXISTS		=> 'label_exists'
);

const RADIO_OPTIONS_INTEGER = array(
	RADIO_RULE_OPTIONS_INTEGER_LESSTHAN		=> 'label_lessthan',
	RADIO_RULE_OPTIONS_INTEGER_EQUALS		=> 'label_equals',
	RADIO_RULE_OPTIONS_INTEGER_GREATERTHAN	=> 'label_greaterthan'
);

const RADIO_COMBINE_OPTIONS = array(
	' OR '	=> 'label_any_rule',
	' AND '	=> 'label_all_rules'
);

// NOTE: STRING options need a specific handler in customradio.js::make_value_box()
define('CUSTOM_RADIO_ITEMS', array(
	array(
		'name'		=> 'label_artist',
		'db_key'	=> 'ta.Artistname',
		'options'	=> RADIO_OPTIONS_STRING
	),
	array(
		'name'		=> 'label_albumartist',
		'db_key'	=> 'aa.Artistname',
		'options'	=> RADIO_OPTIONS_STRING
	),
	array(
		'name'		=> 'label_tracktitle',
		'db_key'	=> 'Title',
		'options'	=> RADIO_OPTIONS_STRING
	),
	array(
		'name'		=> 'label_albumtitle',
		'db_key'	=> 'Albumname',
		'options'	=> RADIO_OPTIONS_STRING
	),
	array(
		'name'		=> 'label_genre',
		'db_key'	=> 'Genre',
		'options'	=> RADIO_OPTIONS_STRING
	),
	array(
		'name'		=> 'label_tag',
		'db_key'	=> 'Tagtable.Name',
		'options'	=> RADIO_OPTIONS_TAG
	),
	array(
		'name'		=> 'label_rating',
		'db_key'	=> 'Rating',
		'options'	=> RADIO_OPTIONS_INTEGER
	),
	array(
		'name'		=> 'label_playcount',
		'db_key'	=> 'Playcount',
		'options'	=> RADIO_OPTIONS_INTEGER
	),
	array(
		'name'		=> 'label_duration_seconds',
		'db_key'	=> 'Duration',
		'options'	=> RADIO_OPTIONS_INTEGER
	),
	array(
		'name'		=> 'label_year',
		'db_key'	=> 'TYear',
		'options'	=> RADIO_OPTIONS_INTEGER
	),
	array(
		'name'		=> 'label_tracknumber',
		'db_key'	=> 'TrackNo',
		'options'	=> RADIO_OPTIONS_INTEGER
	),
	array(
		'name'		=> 'label_disc',
		'db_key'	=> 'Disc',
		'options'	=> RADIO_OPTIONS_INTEGER
	),
	array(
		'name'		=> 'label_dayssince',
		'db_key'	=> 'db_function_tracks_played_since',
		'options'	=> RADIO_OPTIONS_INTEGER
	)
));

?>