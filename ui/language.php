<?php
header('Content-Type: text/javascript; charset=utf-8');
?>

var language = function() {

<?php

	chdir('..');
	include("includes/vars.php");
	include("includes/functions.php");
	include("international.php");
	print 'const tags = '.json_encode($translations)."\r\n";

?>

	return {

		gettext: function(key, args) {
			if (key === null || key == '') {
				return "";
			}
			if (tags[key] === undefined) {
				debug.error("LANGUAGE","Unknown key",key);
				return "UNKNOWN TRANSLATION "+key;
			} else {
				var s = tags[key];
				if (typeof(s) == 'string') {
					while (s.match(/\%s/)) {
						s = s.replace(/\%s/, args.shift());
					}
					return escapeHtml(s);
				} else {
					return s;
				}
			}
		},

		getUCtext: function(key, args) {
			return language.gettext(key, args).toUpperCase();
		}
	}

}();