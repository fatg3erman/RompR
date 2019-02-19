var faveAlbums = function() {

    var running = false;
    var populated = false;
    var tracksneeded = 0;

    function getTracks() {
        $.ajax({
            type: "POST",
            dataType: "json",
            url: "radios/favealbums.php",
            success: function(data) {
                if (data && data.total > 0) {
                    debug.trace("FAVEALBUMS","Got tracks",data);
                    populated = true;
                    running = true;
                    addTracks();
                } else {
                    infobar.notify(language.gettext('label_gotnotracks'));
                    playlist.radioManager.stop(null);
                }
            },
            fail: function() {
                infobar.notify(language.gettext('label_gotnotracks'));
                playlist.radioManager.stop(null);
                populated = false;
            }
        });

    }

    function addTracks() {
        if (running) {
            metaHandlers.genericAction(
                [{ action: 'repopulate', playlist: 'favealbums', numtracks: tracksneeded }],
                faveAlbums.gotTracks,
                faveAlbums.Fail
            );
        }
    }

	return {

		populate: function(s, numtracks) {
            tracksneeded += (numtracks - tracksneeded);
            debug.shout("FAVEALBUMS", "Populating");
            if (!populated) {
                getTracks();
            } else {
                addTracks();
            }
		},

        modeHtml: function(p) {
            return '<i class="icon-music modeimg"></i><span class="modespan">'+
                language.gettext("label_favealbums")+'</span>&nbsp;';
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

playlist.radioManager.register("faveAlbums", faveAlbums, null);