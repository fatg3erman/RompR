var starRadios = function() {

	return {

		setup: function() {

			//
			// 1 star etc
			//
			for (var i = 1; i <= 5; i++) {
				$('#pluginplaylists').append(playlist.radioManager.standardBox('starRadios', i+'stars', 'icon-'+i+'-stars', language.gettext('playlist_xstar', [i])));
			}

			//
			// All Tracks at random
			//
			$('#pluginplaylists').append(playlist.radioManager.standardBox('starRadios', 'allrandom', 'icon-allrandom', language.gettext('label_allrandom')));

			//
			// All Albums at random
			//
			$('#pluginplaylists').append(playlist.radioManager.standardBox('starRadios', 'randomalbums', 'icon-allrandom', language.gettext('label_randomalbums')));

			//
			// Never Played Tracks
			//
			$('#pluginplaylists').append(playlist.radioManager.standardBox('starRadios', 'neverplayed', 'icon-neverplayed', language.gettext('label_neverplayed')));

			//
			// Recently Played Tracks
			//
			$('#pluginplaylists').append(playlist.radioManager.standardBox('starRadios', 'recentlyplayed', 'icon-recentlyplayed', language.gettext('label_recentlyplayed')));

			//
			// Favourite Tracks
			//
			$('#pluginplaylists').append(playlist.radioManager.standardBox('starRadios', 'mostplayed', 'icon-music', language.gettext('label_mostplayed')));

			//
			// Favourite Albums
			//
			$('#pluginplaylists').append(playlist.radioManager.standardBox('starRadios', 'favealbums', 'icon-music', language.gettext('label_favealbums')));

			//
			// Recently Added Tracks
			//
			$('#pluginplaylists').append(playlist.radioManager.standardBox('starRadios', 'recentlyadded_random', 'icon-recentlyplayed', language.gettext('label_recentlyadded_random')));
			$('#pluginplaylists').append(playlist.radioManager.standardBox('starRadios', 'recentlyadded_byalbum', 'icon-recentlyplayed', language.gettext('label_recentlyadded_byalbum')));

			//
			// Tag
			//
			var a = $('<div>', {class: "menuitem fullwidth"}).appendTo('#pluginplaylists');
			var c = $('<div>', {class: "containerbox expand spacer vertical-centre radio-dropdown"}).
				appendTo(a).makeTagMenu({
				textboxname: 'cynthia',
				placeholder: 'Tag',
				labelhtml: '<i class="icon-tags svg-square"></i>',
				populatefunction: tagAdder.populateTagMenu,
				buttontext: language.gettext('button_playradio'),
				buttonfunc: starHelpers.tagPopulate
			});

			//
			// Genre
			//
			var a = $('<div>', {class: "menuitem fullwidth"}).appendTo('#pluginplaylists');
			var c = $('<div>', {class: "containerbox expand spacer vertical-centre radio-dropdown"}).
				appendTo(a).makeTagMenu({
				textboxname: 'farrago',
				placeholder: 'Genre',
				labelhtml: '<i class="icon-music svg-square"></i>',
				populatefunction: starHelpers.populateGenreMenu,
				buttontext: language.gettext('button_playradio'),
				buttonfunc: starHelpers.genrePopulate
			});

			//
			// Artist
			//
			var a = $('<div>', {class: "menuitem fullwidth"}).appendTo('#pluginplaylists');
			var c = $('<div>', {class: "containerbox expand spacer vertical-centre radio-dropdown"}).
				appendTo(a).makeTagMenu({
				textboxname: 'bobblehat',
				placeholder: 'Tracks By Artist',
				labelhtml: '<i class="icon-artist svg-square"></i>',
				populatefunction: starHelpers.populateArtistMenu,
				buttontext: language.gettext('button_playradio'),
				buttonfunc: starHelpers.artistPopulate
			});

		}

	}
}();

var starHelpers = function() {
	return {

		tagPopulate: function() {
			playlist.radioManager.load('starRadios', 'tag+'+$('[name="cynthia"]').val());
		},

		genrePopulate: function() {
			playlist.radioManager.load('starRadios', 'genre+'+$('[name="farrago"]').val());
		},


		artistPopulate: function() {
			playlist.radioManager.load('starRadios', 'artist+'+$('[name="bobblehat"]').val());
		},

		populateGenreMenu: function(callback) {
			metaHandlers.genericQuery(
				'getgenres',
				callback,
				metaHandlers.genericFail
			);
		},

		populateArtistMenu: function(callback) {
			metaHandlers.genericQuery(
				'getartists',
				callback,
				metaHandlers.genericFail
			);
		},

		populateAlbumArtistMenu: function(callback) {
			metaHandlers.genericQuery(
				'getalbumartists',
				callback,
				metaHandlers.genericFail
			);
		}

	}
}();

playlist.radioManager.register("starRadios", starRadios, 'radios/code/starRadios.js');
