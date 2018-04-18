var player = function() {

    return {

        // These are all the mpd status fields the program currently cares about.
        // We don't need to initialise them here; this is for reference
        status: {
        	file: null,
        	bitrate: null,
        	audio: null,
        	state: null,
        	volume: -1,
        	song: -1,
        	elapsed: 0,
        	songid: 0,
        	consume: 0,
        	xfade: 0,
        	repeat: 0,
        	random: 0,
        	error: null,
        	Date: null,
        	Genre: null,
        	Title: null,
        },

        urischemes: new Object(),

        collectionLoaded: false,
        updatingcollection:false,

        controller: new playerController(),

        canPlay: function(urischeme) {
            if (this.urischemes.hasOwnProperty(urischeme)) {
                return true;
            } else {
                return false;
            }
        },

        skip: function(sec) {
            if (this.status.state == "play") {
                var p = infobar.progress();
                var to = p + sec;
                if (p < 0) p = 0;
                this.controller.seek(to);
            }
        }

    }

}();