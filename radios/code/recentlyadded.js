var recentlyaddedtracks = function() {

	var running = false;
	var populated = false;
	var tracksneeded = 0;
	var mode;

	function getTracks() {
		$.ajax({
			type: "POST",
			dataType: "json",
			contentType: false,
			url: "radios/recentlyadded.php?mode="+mode
		})
		.done(function(data) {
			if (data && data.total > 0) {
				debug.trace("SMARTPLAYLIST","Got tracks",data);
				populated = true;
				running = true;
				addTracks();
			} else {
				infobar.notify(language.gettext('label_gotnotracks'));
				playlist.radioManager.stop(null);
			}
		})
		.fail(function() {
			infobar.notify(language.gettext('label_gotnotracks'));
			playlist.radioManager.stop(null);
			populated = false;
		});

	}

	function addTracks() {
		if (running) {
			metaHandlers.genericAction(
				[{ action: 'repopulate', playlist: mode, numtracks: tracksneeded }],
				recentlyaddedtracks.gotTracks,
				recentlyaddedtracks.Fail
			);
		}
	}

	return {

		populate: function(param, numtracks) {
			if (param && param != mode) {
				mode = param;
			}
			tracksneeded += (numtracks - tracksneeded);
			debug.shout("RECENTLY ADDED", "Populating",param,numtracks);
			if (!populated) {
				getTracks();
			} else {
				addTracks();
			}
		},

		modeHtml: function(param) {
			return '<i class="icon-recentlyplayed modeimg"></i><span class="modespan">'+language.gettext("label_"+param)+'</span>&nbsp;';
		},

		stop: function() {
			running = false;
			populated = false;
		},

		gotTracks: function(data) {
			if (data.length > 0) {
				debug.log("SMARTPLAYLIST","Got tracks",data);
				player.controller.addTracks(data,  playlist.radioManager.playbackStartPos(), null);
			} else {
				debug.warn("SMARTPLAYLIST","Got NO tracks",data);
				infobar.notify(language.gettext('label_gotnotracks'));
				playlist.radioManager.stop(null);
				running = false;
			}
		},

		Fail: function() {
			infobar.notify(language.gettext('label_gotnotracks'));
			playlist.radioManager.stop(null);
			populated = false;
			running = false;
		}

	}

}();

playlist.radioManager.register("recentlyaddedtracks", recentlyaddedtracks, null);
