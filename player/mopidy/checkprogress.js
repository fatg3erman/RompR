function disable_player_events() {
	mopidysocket.ignoreThings();
}

function enable_player_events() {
	mopidysocket.reactToThings();
}

// Start this at 25 so there's a short delay before we do our first poll
// This helps if there's a stream already playing because we might not have
// retrieved the playlist by this point and os updateStreamInfo won't do anything.
var AlanPartridge = 29;

// This gives us an event-driven response to Mopidy that works fine alongside our polling-driven
// update methods. Essentially, thi'll pick up any changes that happen that aren't a result of
// interaction with out UI. do_command_list() instructs us to ignore events when it is doing something
// so that the standard mechanism can be used and we don't react twice to everything.

var mopidysocket = function() {

	var socket = null;
	var connected = false;
	var reconnect_timer;
	var react = true;
	var react_timer;
	var error_win = null;
	var error_timer;

	function socket_closed() {
		debug.warn('MOPISOCKET', 'Socket was closed');
		if (connected) {
			socket_error();
		}
		socket = null;
	}

	function show_connection_error() {
		if (error_win == null) {
			error_win = infobar.permerror(language.gettext('error_playergone'));
		}
	}

	function socket_error() {
		connected = false;
		mopidysocket.close();
		clearTimeout(reconnect_timer);
		reconnect_timer = setTimeout(mopidysocket.initialise, 10000);
		clearTimeout(error_timer);
		error_timer = setTimeout(show_connection_error, 3000);
	}

	function socket_open() {
		debug.mark('MOPISOCKET', 'Socket is open');
		clearTimeout(reconnect_timer);
		clearTimeout(error_timer);
		connected = true;
		if (error_win !== null) {
			infobar.removenotify(error_win);
			error_win = null;
		}
	}

	function socket_message(message) {
		debug.log('MOPISOCKET', message);
		var json = JSON.parse(message.data);
		if (json.event) {
			if (react || (!react && json.event != 'tracklist_changed')) {
				// Don't respond to tracklist changed messages if we're currently doing something
				// because what we're doing might be getting the tracklist.
				// Look it's complicated OK?
				clearTimeout(react_timer);
				react_timer = setTimeout(update_player, 100);
			}
		}
	}

	async function update_player() {
		debug.log('MOPISOCKET', 'Reacting to message');
		await playlist.is_valid();
		await player.controller.do_command_list([]);
		updateStreamInfo();
	}

	return {
		initialise: async function() {
			if (!socket || socket.readyState > WebSocket.OPEN) {
				debug.mark('MOPISOCKET', 'Connecting Socket to',prefs.mopidy_http_port);
				socket = new WebSocket('ws://'+prefs.mopidy_http_port+'/mopidy/ws');
				socket.onopen = socket_open;
				socket.onerror = socket_error;
				socket.onclose = socket_closed;
				socket.onmessage = socket_message;
			}
			while (socket && socket.readyState == WebSocket.CONNECTING) {
				await new Promise(t => setTimeout(t, 100));
			}
			if (!socket || socket.readyState > WebSocket.OPEN) {
				socket_error();
			}
			return connected;
		},

		close: function() {
			connected = false;
			if (socket) {
				socket.close();
			}
		},

		send: async function(data) {
			if (await mopidysocket.initialise()) {
				try {
					socket.send(JSON.stringify(data));
				} catch (err) {
					debug.warn('MOPIDYSOCKET', 'Send Failed');
					socket_error();
				}
			}
		},

		reactToThings: function() {
			react = true;
		},

		ignoreThings: function() {
			react = false;
		}

	}

}();

async function update_on_wake() {
	AlanPartridge = 30;
}

async function checkProgress() {
	await mopidysocket.initialise();
	sleepHelper.addSleepHelper(mopidysocket.close);
	sleepHelper.addWakeHelper(mopidysocket.initialise);
	sleepHelper.addWakeHelper(update_on_wake);
	while (true) {
		if (sleepHelper.isVisible()) {
			if (AlanPartridge >= 30) {
				await playlist.is_valid();
				AlanPartridge = 0;
				debug.core('MOPIDY', 'Doing poll');
				await player.controller.do_command_list([]);
				updateStreamInfo();
			}
			if (player.status.state == 'play') {
				player.status.progress = (Date.now()/1000) - player.controller.trackstarttime;
			} else {
				player.status.progress = player.status.elapsed;
			}
			var duration = playlist.getCurrent('Time') || 0;
			infobar.setProgress(player.status.progress, duration);
			AlanPartridge++;
		}
		await new Promise(t => setTimeout(t, 1000));
	}
}
