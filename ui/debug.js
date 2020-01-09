window.debug = (function() {

	var level = prefs.debug_enabled;
	var ignoring = new Array();
	var highlighting = new Array();
	var colours =  new Array();
	var focuson = new Array();
	var stacktrace = false;
	var log_colours = {
		1: "#FF0000",
		2: "#FE6700",
		3: "#FF00FF",
		4: "#00CCFF",
		5: "#000000",
		6: "#AAAAAA",
		7: "#BBBBBB",
		8: "#CCCCCC",
	};
	var log_commands = {
		1: 'error',
		2: 'warn',
		3: 'log',
		4: 'log',
		5: 'log',
		6: 'log',
		7: 'log',
		8: 'log',
	}

	function doTheLogging(loglevel, args) {
		if (loglevel > level) return;
		var module = args.shift();
		if (ignoring[module]) return;
		if (focuson.length > 0 && focuson.indexOf(module) == -1) return;
		var css = (colours[module]) ? 'color:'+colours[module] : 'color:'+log_colours[loglevel];
		if (highlighting[module]) {
			css += ";font-weight:bold";
		}
		var a = new Date();
		var string = "%c"+a.toLocaleTimeString()+' : '+module.padEnd(18, ' ');
		args.unshift(css);
		args.unshift(string);
		if (stacktrace) {
			console.trace();
		}
		console[log_commands[loglevel]](...args.map(v => {
			if (typeof(v) == 'object') {
				// Make sure we pass the value of the variable at the time it was logged and not a reference to its current state
				return JSON.parse(JSON.stringify(v));
			} else {
				return v;
			}
		}));
	}

	return {

		// Level 8 - CORE for continuous running commentary
		// and memory-consuming structure dumps
		core: function() {
			doTheLogging(8, Array.prototype.slice.call(arguments));
		},

		// Level 7 - DEBUG for low level complex info
		debug: function() {
			doTheLogging(7, Array.prototype.slice.call(arguments));
		},

		// Level 6 - TRACE for in-function details
		trace: function() {
			doTheLogging(6, Array.prototype.slice.call(arguments));
		},

		// Level 5 - LOG for following code flow
		log: function() {
			doTheLogging(5, Array.prototype.slice.call(arguments));
		},

		// Level 4 - INFO for information
		info: function() {
			doTheLogging(4, Array.prototype.slice.call(arguments));
		},

		// Level 3 - MARK for important information
		mark: function() {
			doTheLogging(3, Array.prototype.slice.call(arguments));
		},

		// Level 2 - WARN for things that go wrong
		warn: function() {
			doTheLogging(2, Array.prototype.slice.call(arguments));
		},

		// Level 1 - ERROR for serious errors
		error: function() {
			doTheLogging(1, Array.prototype.slice.call(arguments));
		},

		ignore: function(module) {
			ignoring[module] = true;
		},

		ignoreinfopanel: function() {
			ignoring = {
				"LASTFM PLUGIN": true,
				"MBNZ PLUGIN": true ,
				"SPOTIFY PLUGIN": true,
				"DISCOGS PLUGIN": true,
				"INFOBAR": true,
				"NOWPLAYING": true,
				"LASTFM": true,
				"BROWSER": true,
				"TRACKDATA": true,
				"FILE INFO": true,
				"FILE PLUGIN": true,
				"RATINGS PLUGIN": true,
				"COVERSCRAPER": true
			};
			// debug.warn('DEBUG', 'Info panel debug is now ignored by default. Use debug.clearignore to switch it back on');
		},

		clearignore: function() {
			ignoring = [];
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
		},

		stackTrace: function(v) {
			stacktrace = v;
		}

	}

})();
