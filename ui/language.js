var language = function() {
	
	const jsonNode = document.querySelector("script[name='translations']");
  	const jsonText = jsonNode.textContent;
  	const tags = JSON.parse(jsonText);
    
    return {
        gettext: function(key, args) {
            if (key === null) {
                return "";
            }
            if (tags[key] === undefined) {
                debug.error("LANGUAGE","Unknown key",key);
                return "UNKNOWN TRANSLATION KEY";
            } else {
                var s = tags[key];
                while (s.match(/\%s/)) {
                    s = s.replace(/\%s/, args.shift());
                }
                return escapeHtml(s);
            }
        },

        getUCtext: function(key, args) {
            return language.gettext(key, args).toUpperCase();
        }
	}

}();