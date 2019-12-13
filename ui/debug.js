window.debug = (function() {

	var level = prefs.debug_enabled;
	var ignoring = new Array();
	var highlighting = new Array();
	var colours =  new Array();
	var focuson = new Array();
	var log_colours = {
		1: "#FF0000",
		2: "#FFDD00",
		3: "#FF00FF",
		4: "#00CCFF",
		5: "#00CC00",
		6: "#0000FF",
		7: "#000000",
		8: "#CCCCCC",
		9: "#DEDEDE"
	};
	var log_commands = {
		1: 'error',
		2: 'warn',
		3: 'warn',
		4: 'log',
		5: 'log',
		6: 'log',
		7: 'log',
		8: 'log',
		9: 'log'
	}

	function doTheLogging(loglevel, args) {

		if (loglevel > level) return;
		var module = args.shift();
		if (ignoring[module]) return;
		if (focuson.length > 0 && focuson.indexOf(module) == -1) return;
		var css = (colours[module]) ? 'color:'+colours[module] : 'color:'+log_colours[loglevel];
		if (highlighting[module]) {
			css += ";font-weight:bold";
		} else if (Object.keys(highlighting).length > 0) {
			css = "color:#eeeeee";
		}

		var string = module;
		while (string.length < 18) {
			string = string + " ";
		}
		string = string + ": ";
		var a = new Date();
		string = a.toLocaleTimeString()+" : "+string

		for (var i in args) {
			if (typeof(args[i]) != "object" || args[i] === null || args[i] === undefined) {
				string = string + " " + args[i];
			}
		}

		console[log_commands[loglevel]]("%c"+string,css);

		var sex = false;
		for (var i in args) {
			if (typeof(args[i]) == "object" && args[i] !== null && args[i] !== undefined) {
				console.log(args[i]);
				sex = true;
			}
		}
		if (sex) console.log("    ");

	}

	return {

		// Level 9
		debug: function() {
			doTheLogging(9, Array.prototype.slice.call(arguments));
		},

		// Level 8
		trace: function() {
			doTheLogging(8, Array.prototype.slice.call(arguments));
		},

		// Level 7
		log: function() {
			doTheLogging(7, Array.prototype.slice.call(arguments));
		},

		// Level 6
		mark: function() {
			doTheLogging(6, Array.prototype.slice.call(arguments));
		},

		// Level 5
		shout: function() {
			doTheLogging(5, Array.prototype.slice.call(arguments));
		},

		// Level 4
		blurt: function() {
			doTheLogging(4, Array.prototype.slice.call(arguments));
		},

		// Level 3
		fail: function() {
			doTheLogging(3, Array.prototype.slice.call(arguments));
		},

		// Level 2
		warn: function() {
			doTheLogging(2, Array.prototype.slice.call(arguments));
		},

		// Level 1
		error: function() {
			doTheLogging(1, Array.prototype.slice.call(arguments));
		},

		ignore: function(module) {
			ignoring[module] = true;
		},

		ignoreinfopanel: function() {
			ignoring = ["LASTFM PLUGIN", "MBNZ PLUGIN", "SPOTIFY PLUGIN", "DISCOGS PLUGIN"];
		},

		highlight: function(module) {
			highlighting[module] = true;
		},

		focuson: function(module) {
			if (focuson.indexOf(module) == -1) {
				focuson.push(module);
			}
		},

		focusoff: function(module) {
			var index = focuson.indexOf(module);
			if (index > -1) {
				focuson.splice(index, 1);
			}
		},

		setcolour: function(module, colour) {
			colours[module] = colour;
		},

		setLevel: function(l) {
			if (l == level) {
				console.log("Debugging is already set to Level "+l+". Duh.");
				return false;
			}
			level = l;
			prefs.save({debug_enabled: l});
			console.log("Debugging set to level "+l+". Aren't you clever?");
			return true;
		},

		getLevel: function() {
			return level;
		}

	}

})();
