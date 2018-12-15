var mostPlayed = function() {

	var running = false;
    var populating = false;
    var started = false;

    function getSmartPlaylistTracks(action, numtracks) {
        if (populating) {
            debug.warn("MOST PLAYED", "Asked to populate but already doing so!")
            return false;
        }
        populating = true;
        metaHandlers.genericAction(
            [{ action: action, playlist: "mostplayed", numtracks: numtracks }],
            function(data) {
                if (data.length > 0) {
                    debug.trace("SMARTPLAYLIST","Got tracks",data);
                    running = true;
                    populating = false;
                    player.controller.addTracks(data, playlist.radioManager.playbackStartPos(), null);
                } else {
                    playlist.radioManager.stop(null);
                }
            },
            function() {
                debug.error("MOST PLAYED","Database fail");
                infobar.notify(infobar.NOTIFY,language.gettext('label_gotnotracks'));
                playlist.radioManager.stop(null);
                populating = false;
            }
        );
    }

	return {

		populate: function(s, numtracks) {
            debug.shout("MOST PLAYED", "Populating");
			getSmartPlaylistTracks(started ? "repopulate" : "getplaylist", numtracks);
            started = true;
		},

        modeHtml: function(p) {
            return '<i class="icon-music modeimg"></i><span class="modespan">'+
                language.gettext("label_mostplayed")+'</span>&nbsp;';
        },

        stop: function() {
            running = false;
            started = false;
        }

	}

}();

playlist.radioManager.register("mostPlayed", mostPlayed, null);