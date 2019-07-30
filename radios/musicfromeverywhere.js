var genreRadio = function() {

    return {

        setup: function() {
            //
            // Genre (Music from Everywhere)
            //
            $('#pluginplaylists_everywhere').append(playlist.radioManager.textEntry('icon-wifi', language.gettext('label_genre'), 'genre_radio'));
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
            $('#pluginplaylists_everywhere').append(playlist.radioManager.standardBox('faveArtistRadio', null, 'icon-artist', language.gettext('label_radio_fartist')));
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
            $('button[name="singart_radio"]').on('click', function() {
                var v = $('#singart_radio').val();
                if (v != '') {
                    playlist.radioManager.load('singleArtistRadio', v);
                }
            });
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
                // Last.FM Mix Radio (Music from Everywhere). Uses favourite tracks to get recommendations
                //
                var holder = $("#pluginplaylists_everywhere");
                ['7day', '1month', '12month', 'overall'].forEach(function(l) {
                    holder.append(playlist.radioManager.standardBox('lastFMTrackRadio', l, 'icon-lastfm-1', language.gettext('label_lastfm_mix_'+l)));
                });
            }
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
                // Last.FM Lucky Dip (Music from Everywhere). Uses favourite artists to get recommendations
                //
                var holder = $("#pluginplaylists_everywhere");
                ['7day', '1month', '12month', 'overall'].forEach(function(l) {
                    holder.append(playlist.radioManager.standardBox('lastFMArtistRadio', l, 'icon-lastfm-1', language.gettext('label_lastfm_dip_'+l)));
                });
            }
        }
    }
}();

playlist.radioManager.register("lastFMTrackRadio", lastFMTrackRadio, 'radios/code/lastfmtrackradio.js');
playlist.radioManager.register("lastFMArtistRadio", lastFMArtistRadio, 'radios/code/lastfmartistradio.js');
playlist.radioManager.register("faveArtistRadio", faveArtistRadio, 'radios/code/faveartistradio.js');
playlist.radioManager.register("genreRadio", genreRadio,'radios/code/genreradio.js');
playlist.radioManager.register("singleArtistRadio", singleArtistRadio, 'radios/code/singleartistradio.js');
