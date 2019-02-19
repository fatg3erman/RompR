var starRadios = function() {

	var running = false;
    var populating = false;
    var selected;

    function getSmartPlaylistTracks(action, playlist, numtracks) {
        if (populating) {
            debug.warn("STARRADIOS", "Asked to populate but already doing so!")
            return false;
        }
        populating = true;
        debug.shout("STAR RADIOS", action, playlist, numtracks);
        metaHandlers.genericAction(
            [{ action: action, playlist: playlist, numtracks: numtracks }],
            starRadios.gotTracks,
            starRadios.Fail
        );
    }

	return {

		populate: function(param, numtracks) {
            debug.log("STARRADIOS","Populate Called with",param,numtracks,"selected is",selected);
            var whattodo = "repopulate";
            if (param !== selected ) {
                whattodo = "getplaylist";
				selected = param;
            }
            running = true;
			getSmartPlaylistTracks(whattodo, selected, numtracks);
		},

        modeHtml: function(param) {
            if (param.match(/^\dstars/)) {
                var cn = param.replace(/(\d)/, 'icon-$1-');
                return '<i class="'+cn+' rating-icon-small"></i>';
            } else if (param == "neverplayed" || param == "allrandom" || param == "recentlyplayed") {
                return '<i class="icon-'+param+' modeimg"/></i><span class="modespan">'+
                    language.gettext('label_'+param)+'</span>';
            } else {
                return '<i class="icon-tags modeimg"/><span class="modespan">'+param.replace(/^tag\+/, '')+'</span>';
            }
        },

        stop: function() {
            running = false;
            selected = null;
        },

        gotTracks: function(data) {
            populating = false;
            if (data.length > 0) {
                debug.log("SMARTPLAYLIST","Got tracks",data);
                if (running) {
					player.controller.addTracks(data,  playlist.radioManager.playbackStartPos(), null);
				}
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
            populating = false;
            running = false;
        },

        tagPopulate: function(tags) {
            playlist.radioManager.load('starRadios', tags);
        }
    }
}();

debug.log("STARRADIOS","Real script Loaded");

playlist.radioManager.register("starRadios", starRadios, null);
