<?php
header('Content-Type: text/javascript; charset=utf-8');
?>

var language = function() {

<?php

	chdir('..');
	include("includes/vars.php");
	include("includes/functions.php");
	print '    const tags = '.json_encode(language::get_all_translations())."\r\n";

?>

	return {

		gettext: function(key, args) {
			if (key === null || key == '') {
				return "";
			}
			args = getArray(args);
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