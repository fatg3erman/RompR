<?php
class track {

	public $tags;

	public function __construct(&$filedata) {
		$this->tags = $filedata;
		// On creation, we set the 'albumartist' tag according to the user's
		// Album Artist sort preference. Note that this will be null
		// if the AlbumArtist tag is not set (or if we are using Composer adn that is null too)
		// We do this simply for speed otherwise we end up loking it up twice for every track
		// in the case where this remains as null we add it to the pile and
		// sort it out later.
		$this->tags['albumartist'] = format_sortartist($this->tags);
		$this->tags['trackartist'] = format_artist($this->tags['Artist'], '');
	}

	public function get_sort_artist() {
		// Used when albumartist is not set (bevause there's either no
		// composer or albumartist tags
		$sortartist = null;
		if ($this->tags['Artist'] !== null) {
			$sortartist = $this->tags['Artist'];
		} else if ($this->tags['station'] !== null) {
			$sortartist = $this->tags['station'];
		}
		return concatenate_artist_names($sortartist);
	}

}
?>