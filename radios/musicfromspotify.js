var spotiMixRadio = function() {

    return {
        setup: function() {
            if (player.canPlay('spotify')) {

                //
                // Spotify Weekly Mix
                // Spotify Swim
                // Spotify Surprise!
                //
                $('#pluginplaylists_spotify').append(playlist.radioManager.standardBox('spotimixradio', '7day', 'icon-spotify-circled', language.gettext('label_spotify_mix')));
                $('#pluginplaylists_spotify').append(playlist.radioManager.standardBox('spotimixradio', '1year', 'icon-spotify-circled', language.gettext('label_spotify_dj')));
                $('#pluginplaylists_spotify').append(playlist.radioManager.standardBox('spotimixradio', 'surprise', 'icon-spotify-circled', language.gettext('label_spottery_lottery')));

                $('.spotimixradio').on(clickBindType(), function(evt) {
                    evt.stopPropagation();
                    playlist.radioManager.load('spotiMixRadio', $(evt.delegateTarget).attr('name'));
                });
            }
        }

    }

}();

var mixRadio = function() {

    return {

        setup: function() {

            if (player.canPlay('spotify')) {
                //
                // Favourite Artists and Related Artists (Music from Spotify)
                //
                $('#pluginplaylists_spotify').append(playlist.radioManager.standardBox('mixradio', null, 'icon-artist', language.gettext('label_radio_mix')));
                $('.mixradio').on(clickBindType(), function(evt) {
                    evt.stopPropagation();
                    playlist.radioManager.load('mixRadio', null);
                });

            }
        }
    }
}();


var artistRadio = function() {

    return {

        setup: function() {
            if (player.canPlay('spotify')) {
                //
                // Artists Similar To (Music From Spotify)
                //
                var a = $('<div>', {class: "pluginitem radioplugin_wide"}).appendTo('#pluginplaylists_spotify');
                var b = $('<div>', {class: "helpfulalbum fullwidth containerbox", style: "padding-top:4px"}).appendTo(a);
                var c = $('<div>', {class: "containerbox expand spacer dropdown-container"}).appendTo(b);
                c.append('<div class="fixed"><i class="icon-spotify-circled svg-square"></i></div>'+
                        '<div class="fixed padright"><span class="alignmid">'+language.gettext('label_simar_radio')+'</span></div>'+
                        '<div class="expand dropdown-holder"><input class="enter" id="bubbles" type="text" onkeyup="onKeyUp(event)" /></div>'+
                        '<button class="fixed alignmid" onclick="playlist.radioManager.load(\'artistRadio\', $(\'#bubbles\').val())">'+language.gettext('button_playradio')+'</button>');
            }
        }
    }
}();

var spotiTrackRadio = function() {
    return {
        setup: function() {
            debug.trace("SPTR","Nothing to see here");
        }
    }
}();


playlist.radioManager.register("spotiMixRadio", spotiMixRadio, 'radios/code/spotimixradio.js');
playlist.radioManager.register("mixRadio", mixRadio, 'radios/code/mixradio.js');
playlist.radioManager.register("artistRadio", artistRadio, 'radios/code/artistradio.js');
playlist.radioManager.register("spotiTrackRadio", spotiTrackRadio, 'radios/code/spotiTrackRadio.js');
