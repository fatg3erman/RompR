var language = function() {

	const jsonNode = document.querySelector("script[name='translations']");
	const jsonText = jsonNode.textContent;
	const tags = JSON.parse(jsonText);

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