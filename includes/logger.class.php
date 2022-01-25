<?php
	class logger {

		private static $outfile;
		private static $loglevel = 8;
		private static $debug_colours = array(
			# light red
			0 => 91,
			# red
			1 => 31,
			# yellow
			2 => 33,
			# magenta
			3 => 35,
			# cyan
			4 => 36,
			# light blue
			5 => 94,
			# light yellow
			6 => 93,
			# green
			7 => 32,
			# light grey
			8 => 37,
			# white
			9 => 97
		);

		private static $debug_names = array(
			0 => 'Disabled',
			1 => 'ERROR',
			2 => 'WARN ',
			3 => 'MARK ',
			4 => 'INFO ',
			5 => 'LOG  ',
			6 => 'TRACE',
			7 => 'DEBUG',
			8 => 'CORE '
		);

		private const FUNCTION_LENGTH = 25;

		public static function setLevel($level) {
			self::$loglevel = intval($level);
		}

		public static function setOutfile($file) {
			self::$outfile  = $file;
		}

		public static function getLevelName($level) {
			return self::$debug_names[$level];
		}

		private static function dothelogging($level, $parms) {
			if ($level > self::$loglevel || $level < 1) return;
			$module = array_shift($parms);
			if (strlen($module) > 18) {
				$in = ' ';
			} else {
				$in = str_repeat(" ", 18 - strlen($module));
			}
			$pid = getmypid();
			array_walk($parms, 'logger::un_array');
			$out = implode(' ', $parms);
			if (self::$outfile != "") {
				// Two options here - either colour by level
				// $col = self::$debug_colours[$level];
				// or attempt to have different processes in different colours.
				// This helps to keep track of things when multiple concurrent things are happening at once.
				$col = self::$debug_colours[$pid % 10];
				error_log("\033[90m".date('M d H:i:s').' ['.$pid.'] '.self::$debug_names[$level]." : \033[".$col."m".$module.$in.$out."\033[0m\n",3,self::$outfile);
			} else {
				error_log(self::$debug_names[$level].' : '.$module.$in.": ".$out,0);
			}
		}

		public static function un_array(&$a, $i) {
			if (is_array($a)) {
				$a = multi_implode($a);
			} else if (is_object($a)) {
				$a = print_r($a, true);
			}
		}

		private static function format_function($dbt) {
	        $caller = isset($dbt[1]['function']) ? '('.$dbt[1]['function'].')' : '';
	        if (strlen($caller) < self::FUNCTION_LENGTH)
	        	$caller = $caller.str_repeat(' ', self::FUNCTION_LENGTH - strlen($caller));
	        return $caller.' : ';
		}

		// Level 8 - CORE for continuous running commentary
		public static function core() {
			$parms = func_get_args();
			if (!is_array($parms[1])) {
				$dbt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,2);
				$caller = logger::format_function($dbt);
		        $parms[1] = $caller.$parms[1];
		    }
			logger::dothelogging(8, $parms);
		}

		// Level 7 - DEBUG for low level complex info
		public static function debug() {
			$parms = func_get_args();
			if (!is_array($parms[1])) {
				$dbt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,2);
				$caller = logger::format_function($dbt);
		        $parms[1] = $caller.$parms[1];
		    }
			logger::dothelogging(7, $parms);
		}

		// Level 6 - TRACE for in-function details
		public static function trace() {
			$parms = func_get_args();
			if (!is_array($parms[1])) {
				$dbt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,2);
				$caller = logger::format_function($dbt);
		        $parms[1] = $caller.$parms[1];
		    }
			logger::dothelogging(6, $parms);
		}

		// Level 5 - LOG for following code flow
		public static function log() {
			$parms = func_get_args();
			if (!is_array($parms[1])) {
				$dbt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,2);
				$caller = logger::format_function($dbt);
		        $parms[1] = $caller.$parms[1];
		    }
			logger::dothelogging(5, $parms);
		}

		// Level 4 - INFO for information
		public static function info() {
			$parms = func_get_args();
			$parms[1] = str_repeat(' ', self::FUNCTION_LENGTH).' : '.$parms[1];
			logger::dothelogging(4, $parms);
		}

		// Level 3 - MARK for important information
		public static function mark() {
			$parms = func_get_args();
			$parms[1] = str_repeat(' ', self::FUNCTION_LENGTH).' : '.$parms[1];
			logger::dothelogging(3, $parms);
		}

		// Level 2 - WARN for things that go wrong
		public static function warn() {
			$parms = func_get_args();
			$parms[1] = str_repeat(' ', self::FUNCTION_LENGTH).' : '.$parms[1];
			logger::dothelogging(2, $parms);
		}

		// Level 1 - ERROR for serious errors
		public static function error() {
			$parms = func_get_args();
			$parms[1] = str_repeat(' ', self::FUNCTION_LENGTH).' : '.$parms[1];
			logger::dothelogging(1, $parms);
		}

	}
?>