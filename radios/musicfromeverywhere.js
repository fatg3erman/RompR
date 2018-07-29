var genreRadio = function() {

    return {

        setup: function() {
            //
            // Genre (Music from Everywhere)
            //
            $('#pluginplaylists_everywhere').append(playlist.radioManager.textEntry('icon-wifi', language.gettext('label_genre'), 'genre_radio'));
            // $('#genre_radio').on('keyup', onKeyUp);
            $('button[name="genre_radio"]').on('click', function() {
                var v = $('#genre_radio').val();
                if (v != '') {
                    playlist.radioManager.load('genreRadio', v);
                }
            });
        }
    }
}();

var faveArtistRadio = function() {

    return {

        setup: function() {
            //
            // Favourite Artists (Music from Everywhere)
            //
            $('#pluginplaylists_everywhere').append(playlist.radioManager.standardBox('fartistradio', null, 'icon-artist', language.gettext('label_radio_fartist')));
            $('.fartistradio').on(prefs.clickBindType(), function(evt) {
                evt.stopPropagation();
                playlist.radioManager.load('faveArtistRadio', null);
            });
        }
    }
}();

var singleArtistRadio = function() {

    var tuner;
    var artist;

    return {

        setup: function() {
            //
            // Tracks By Artist (Music from Everywhere)
            //
            $('#pluginplaylists_everywhere').append(playlist.radioManager.textEntry('icon-artist', language.gettext('label_singleartistradio'), 'singart_radio'));
            // $('#singart_radio').on('keyup', onKeyUp);
            $('button[name="singart_radio"]').on('click', function() {
                var v = $('#singart_radio').val();
                if (v != '') {
                    playlist.radioManager.load('singleArtistRadio', v);
                }
            });
        }
    }
}();

var lastFMArtistRadio = function() {

    return {

        setup: function() {
            if (lastfm.isLoggedIn && (player.canPlay('spotify') || player.canPlay('gmusic'))) {
                // This isn't really spotify radio but it doesn't make sense unless spotify is available
                // gmusic will wrok too but only with a subscription
                //
                // Last.FM Lucky Dip (Music from Everywhere)
                //
                $("#pluginplaylists_everywhere").append(playlist.radioManager.dropdownBox('lfmartistradio', '7day', 'icon-lastfm-1', language.gettext('label_lastfm_mix'), 'lastfm_mix'));
                $('#lastfm_mix').append(playlist.radioManager.standardBox('lfmartistradio dropdown', '7day', 'icon-lastfm-1', 'Weekly Dip'));
                $('#lastfm_mix').append(playlist.radioManager.standardBox('lfmartistradio dropdown', '1month', 'icon-lastfm-1', 'Monthly Dip'));
                $('#lastfm_mix').append(playlist.radioManager.standardBox('lfmartistradio dropdown', '12month', 'icon-lastfm-1', 'Yearly Dip'));
                $('#lastfm_mix').append(playlist.radioManager.standardBox('lfmartistradio dropdown', 'overall', 'icon-lastfm-1', 'All Time Dip'));
                $('.lfmartistradio').on(prefs.clickBindType(), function(evt) {
                    evt.stopPropagation();
                    playlist.radioManager.load('lastFMArtistRadio', $(evt.delegateTarget).attr('name'));
                });
            }
        }
    }
}();

var lastFMTrackRadio = function() {

    return {
        setup: function() {
            if (lastfm.isLoggedIn && (player.canPlay('spotify') || player.canPlay('gmusic'))) {
                // This isn't really spotify radio but it doesn't make sense unless spotify is available
                // gmusic will wrok too but only with a subscription
                //
                // Last.FM Mix Radio (Music from Everywhere)
                //
                $("#pluginplaylists_everywhere").append(playlist.radioManager.dropdownBox('lfmtrackradio', '7day', 'icon-lastfm-1', language.gettext('label_lastfm_track'), 'lastfm_track'));
                $('#lastfm_track').append(playlist.radioManager.standardBox('lfmtrackradio dropdown', '7day', 'icon-lastfm-1', 'Daily Mix'));
                $('#lastfm_track').append(playlist.radioManager.standardBox('lfmtrackradio dropdown', '1month', 'icon-lastfm-1', 'Monthly Mix'));
                $('#lastfm_track').append(playlist.radioManager.standardBox('lfmtrackradio dropdown', '12month', 'icon-lastfm-1', 'Yearly Mix'));
                $('#lastfm_track').append(playlist.radioManager.standardBox('lfmtrackradio dropdown', 'overall', 'icon-lastfm-1', 'All Time Mix'));
                $('.lfmtrackradio').on(prefs.clickBindType(), function(evt) {
                    evt.stopPropagation();
                    playlist.radioManager.load('lastFMTrackRadio', $(evt.delegateTarget).attr('name'));
                });
            }
        }
    }
}();

playlist.radioManager.register("lastFMArtistRadio", lastFMArtistRadio, 'radios/code/lastfmartistradio.js');
playlist.radioManager.register("lastFMTrackRadio", lastFMTrackRadio, 'radios/code/lastfmtrackradio.js');
playlist.radioManager.register("faveArtistRadio", faveArtistRadio, 'radios/code/faveartistradio.js');
playlist.radioManager.register("genreRadio", genreRadio,'radios/code/genreradio.js');
playlist.radioManager.register("singleArtistRadio", singleArtistRadio, 'radios/code/singleartistradio.js');
