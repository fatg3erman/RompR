<?php
class minotaur {

	private $filename;

	public function __construct($filename) {
		$this->filename = $filename;
	}

	public function get_last_line_of_file() {

		//
		// Return the last line of $this->filename
		// without reading the whole file into memory
		//

		if (file_exists($this->filename)) {
			$LastLine = '';
			if ($fp = fopen($this->filename, 'r')) {
				fseek($fp, -1, SEEK_END);
				$pos = ftell($fp);
				// Loop backward util "\n" is found.
				if ($pos > 0) {
					fseek($fp, $pos--);
				}
				while((($C = fgetc($fp)) != "\n") && ($pos > 0)) {
					$LastLine = $C.$LastLine;
					fseek($fp, $pos--);
				}
				fclose($fp);
			}
			return $LastLine;
		}
		return false;
	}
}
?>