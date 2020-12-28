<?php
class cantata_importer extends database {
	public function cantata_import() {
		$this->open_transaction()
		$count = 0;
		foreach (import_cantata_track() as $filedata) {
			$uri = $filedata['file'];
			$cockend = $filedata['sticker'];
			$bellend = explode('=', $cockend);
			$rating = ceil($bellend[1]/2);
			logger::log('CANIMPORTER', 'Uri :',$uri,', Rating',$rating);

			$ttindex = $this->simple_query('TTindex', 'Tracktable', 'Uri', $uri, null);
			if ($ttindex) {
				logger::log('CANIMPORTER','  TTindex is',$ttindex);
				$this->sql_prepare_query(true, null, null, null,
					"REPLACE INTO Ratingtable (TTindex, Rating) VALUES (?, ?)",
					$ttindex,
					$rating
				);
				$this->check_transaction();
			} else {
				logger::log('CANIMPORTER', '  Could not find TTindex');
			}

			$count++;
			$output = array('done' => $count, 'total' => $total, 'message' => 'Done '.$count.' of '.$total);
			file_put_contents('prefs/canmonitor', json_encode($output));
		}
		$this->close_transaction();
	}
}
?>