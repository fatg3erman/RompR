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

var lastFMArtistRadio = function() {

    return {

        setup: function() {
            if (lastfm.isLoggedIn && (player.canPlay('spotify') || player.canPlay('gmusic'))) {
                // This isn't really spotify radio but it doesn't make sense unless spotify is available
                // gmusic will wrok too but only with a subscription
                //
                // Last.FM Lucky Dip (Music from Everywhere)
                //
                var header = playlist.radioManager.dropdownHeader('lastFMArtistRadio', '7day', 'icon-lastfm-1', language.gettext('label_lastfm_mix'), 'lastfm_mix').appendTo("#pluginplaylists_everywhere");
                var holder = playlist.radioManager.dropdownHolder('lastfm_mix').appendTo(header);
                holder.append(playlist.radioManager.standardBox('lastFMArtistRadio', '7day', 'icon-lastfm-1', 'Weekly Dip').addClass('dropdown'));
                holder.append(playlist.radioManager.standardBox('lastFMArtistRadio', '1month', 'icon-lastfm-1', 'Monthly Dip').addClass('dropdown'));
                holder.append(playlist.radioManager.standardBox('lastFMArtistRadio', '12month', 'icon-lastfm-1', 'Yearly Dip').addClass('dropdown'));
                holder.append(playlist.radioManager.standardBox('lastFMArtistRadio', 'overall', 'icon-lastfm-1', 'All Time Dip').addClass('dropdown'));
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
                var header = playlist.radioManager.dropdownHeader('lastFMTrackRadio', '7day', 'icon-lastfm-1', language.gettext('label_lastfm_track'), 'lastfm_track').appendTo("#pluginplaylists_everywhere");
                var holder = playlist.radioManager.dropdownHolder('lastfm_track').appendTo(header);
                holder.append(playlist.radioManager.standardBox('lastFMTrackRadio', '7day', 'icon-lastfm-1', 'Daily Mix').addClass('dropdown'));
                holder.append(playlist.radioManager.standardBox('lastFMTrackRadio', '1month', 'icon-lastfm-1', 'Monthly Mix').addClass('dropdown'));
                holder.append(playlist.radioManager.standardBox('lastFMTrackRadio', '12month', 'icon-lastfm-1', 'Yearly Mix').addClass('dropdown'));
                holder.append(playlist.radioManager.standardBox('lastFMTrackRadio', 'overall', 'icon-lastfm-1', 'All Time Mix').addClass('dropdown'));
            }
        }
    }
}();

playlist.radioManager.register("lastFMArtistRadio", lastFMArtistRadio, 'radios/code/lastfmartistradio.js');
playlist.radioManager.register("lastFMTrackRadio", lastFMTrackRadio, 'radios/code/lastfmtrackradio.js');
playlist.radioManager.register("faveArtistRadio", faveArtistRadio, 'radios/code/faveartistradio.js');
playlist.radioManager.register("genreRadio", genreRadio,'radios/code/genreradio.js');
playlist.radioManager.register("singleArtistRadio", singleArtistRadio, 'radios/code/singleartistradio.js');
