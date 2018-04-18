var faveAlbums = function() {

    var running = false;
    var populating = false;
    var tracks = new Array();
    var tracksneeded = 0;

    function getTracks() {
        if (populating) {
            debug.warn("FAVEALBUMS", "Asked to populate but already doing so!")
            return false;
        }
        populating = true;
        $.ajax({
            type: "POST",
            dataType: "json",
            url: "radios/favealbums.php",
            success: function(data) {
                if (data && data.length > 0) {
                    debug.trace("FAVEALBUMS","Got tracks",data);
                    running = true;
                    populating = false;
                    tracks = data;
                    addTracks();
                } else {
                    infobar.notify(infobar.NOTIFY,language.gettext('label_gotnotracks'));
                    playlist.radioManager.stop();
                }
            },
            fail: function() {
                infobar.notify(infobar.NOTIFY,language.gettext('label_gotnotracks'));
                playlist.radioManager.stop();
                populating = false;
            }
        });

    }

    function addTracks() {
        if (running) {
            var t = new Array();
            while (tracksneeded > 0 && tracks.length > 0) {
                t.push({type: 'uri', name: tracks.shift()});
                tracksneeded--;
            }
            if (t.length > 0) {
                player.controller.addTracks(t, playlist.radioManager.playbackStartPos(), null);
            } else {
                playlist.radioManager.stop();
            }
        }
    }

	return {

		populate: function(s, numtracks) {
            tracksneeded += (numtracks - tracksneeded);
            debug.shout("FAVEALBUMS", "Populating");
            if (tracks.length == 0) {
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
            tracks = new Array();
        }
        
	}

}();

playlist.radioManager.register("faveAlbums", faveAlbums, null);