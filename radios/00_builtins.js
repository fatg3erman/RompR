var starRadios = function() {

	return {

        setup: function() {

            //
            // Tag
            //
            var a = $('<div>', {class: "pluginitem radioplugin_wide"}).appendTo('#pluginplaylists');
            var b = $('<div>', {class: "helpfulalbum fullwidth containerbox", style: "padding-top:4px"}).appendTo(a);
            var c = $('<div>', {class: "containerbox expand spacer dropdown-container"}).
                appendTo(b).makeTagMenu({
                textboxname: 'cynthia',
                labelhtml: '<i class="icon-tags svg-square"></i>',
                populatefunction: populateTagMenu,
                buttontext: language.gettext('button_playradio'),
                buttonfunc: starRadios.tagPopulate
            });
            
            //
            // 1 star etc
            //
            for (var i = 1; i <= 5; i++) {
                $('#pluginplaylists').append(playlist.radioManager.standardBox('starradios', i+'stars', 'icon-'+i+'-stars', language.gettext('playlist_xstar', [i])));
            }

            //
            // All Tracks at random
            //
            $('#pluginplaylists').append(playlist.radioManager.standardBox('starradios', 'allrandom', 'icon-allrandom', language.gettext('label_allrandom')));

            //
            // Never Played Tracks
            //
            $('#pluginplaylists').append(playlist.radioManager.standardBox('starradios', 'neverplayed', 'icon-neverplayed', language.gettext('label_neverplayed')));

            //
            // Recently Played Tracks
            //
            $('#pluginplaylists').append(playlist.radioManager.standardBox('starradios', 'recentlyplayed', 'icon-recentlyplayed', language.gettext('label_recentlyplayed')));

            $('.starradios').on(clickBindType(), function(evt) {
                evt.stopPropagation();
                playlist.radioManager.load('starRadios', $(evt.delegateTarget).attr('name'));
            });
        },

        tagPopulate: function() {
            playlist.radioManager.load('starRadios', $('[name="cynthia"]').val());
        }
	}
}();

var recentlyaddedtracks = function() {

    return {

        setup: function() {

            //
            // Recently Added Tracks
            //
            $('#pluginplaylists').append(playlist.radioManager.standardBox('recentlyaddedradio', 'random', 'icon-recentlyplayed', language.gettext('label_recentlyadded_random')));
            $('#pluginplaylists').append(playlist.radioManager.standardBox('recentlyaddedradio', 'byalbum', 'icon-recentlyplayed', language.gettext('label_recentlyadded_byalbum')));

            $('.recentlyaddedradio').on(clickBindType(), function(evt) {
                evt.stopPropagation();
                playlist.radioManager.load('recentlyaddedtracks', $(evt.delegateTarget).attr('name'));
            });
        }
    }
}();

var mostPlayed = function() {

    return {

        setup: function() {

            //
            // Favourite Tracks
            //
            $('#pluginplaylists').append(playlist.radioManager.standardBox('mostplayedradio', null, 'icon-music', language.gettext('label_mostplayed')));
            $('.mostplayedradio').on(clickBindType(), function(evt) {
                evt.stopPropagation();
                playlist.radioManager.load('mostPlayed', null);
            });
        }
    }
}();

var faveAlbums = function() {

    return {

        setup: function() {

            //
            // Favourite Albums
            //
            $('#pluginplaylists').append(playlist.radioManager.standardBox('favealbumradio', null, 'icon-music', language.gettext('label_favealbums')));
            $('.favealbumradio').on(clickBindType(), function(evt) {
                evt.stopPropagation();
                playlist.radioManager.load('faveAlbums', null);
            });
        }
    }
}();

playlist.radioManager.register("starRadios", starRadios, 'radios/code/starRadios.js');
playlist.radioManager.register("recentlyaddedtracks", recentlyaddedtracks, 'radios/code/recentlyadded.js');
playlist.radioManager.register("mostPlayed", mostPlayed, 'radios/code/mostplayed.js');
playlist.radioManager.register("faveAlbums", faveAlbums, 'radios/code/favealbums.js');
