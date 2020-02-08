var crazyRadioManager = function() {

	return {

		loadSavedCrazies: function() {
			$('.crazyradio').remove();
			$.get('radios/crazymanager.php?action=get', function(data) {
				debug.debug("CRAZY RADIO","Saved Data",data);
				var crazySettings = data;
				for (var i in crazySettings) {
					var crazy = playlist.radioManager.standardBox('spotiCrazyRadio', JSON.stringify(crazySettings[i]), 'icon-spotify-circled', crazySettings[i].playlistname).appendTo("#pluginplaylists_spotify");
					crazy.append(
						'<div class="fixed">'+
						'<i class="icon-cancel-circled collectionicon clickable crazyradio clickremcrazy" name="'+i+'"></i>'+
						'</div>'
					);
				}
				uiHelper.adjustLayout();
			}, 'json');
		},

		refreshCrazyList: function() {
			$('.crazyradio').each(function() {
				$(this).parent().parent().remove();
			});
			crazyRadioManager.loadSavedCrazies();
		},

		go: function() {
			var params = {
				playlistname: null,
				genres: $('[name="spotigenres"]').val()
			};
			$('.spotiradioslider').each(function() {
				var attribute = $(this).attr('name');
				var range = $(this).rangechooser("getRange");
				params['max_'+attribute] = range.max;
				params['min_'+attribute] = range.min;
			});
			playlist.radioManager.load('spotiCrazyRadio', JSON.stringify(params));
		},

		load: function(settings) {
			for (let i in settings) {
				switch (i) {
					case 'genres':
						$('[name="spotigenres"]').val(settings[i]);
						break;
					case 'playlistname':
						break;
					default:
						$('.spotiradioslider[name="'+i+'"]').rangechooser('setRange', settings[i]);
						break;
				}
			}
		},

		handleClick: function(event, clickedElement) {
			if (clickedElement.hasClass('clickremcrazy')) {
				var i = clickedElement.attr('name');
				debug.log("CRAZY BUGGER","Removing",i);
				$('.crazyradio[name="'+i+'"]').parent().parent().remove();
				$.get('radios/crazymanager.php?action=remove&index='+i, crazyRadioManager.refreshCrazyList);
			}
		},

		actuallySaveCrazyRadioSettings: function() {
			var settings = {
				playlistname: $('#scplname').val(),
				genres: $('[name="spotigenres"]').val()
			}
			$('.spotiradioslider').each(function() {
				var attribute = $(this).attr('name');
				var range = $(this).rangechooser("getRange");
				settings[attribute] = {min: range.min, max: range.max};
			});
			$.ajax({
				type: 'POST',
				url: 'radios/crazymanager.php?action=save',
				data: JSON.stringify(settings)
			})
			.done(crazyRadioManager.refreshCrazyList)
			.fail(function() {
				infobar.error(language.gettext('label_general_error'));
			});
			return true;
		},

		saveCrazyRadioSettings: function(e) {
			var fnarkle = new popup({
				css: {
					width: 400,
					height: 300
				},
				title: language.gettext("button_createplaylist"),
				atmousepos: true,
				mousevent: e
			});
			var mywin = fnarkle.create();
			var d = $('<div>',{class: 'containerbox'}).appendTo(mywin);
			var e = $('<div>',{class: 'expand'}).appendTo(d);
			var i = $('<input>',{class: 'enter', id: 'scplname', type: 'text', size: '200'}).appendTo(e);
			var b = $('<button>',{class: 'fixed'}).appendTo(d);
			b.html(language.gettext('button_save'));
			fnarkle.useAsCloseButton(b, crazyRadioManager.actuallySaveCrazyRadioSettings);
			fnarkle.open();
		}

	}

}();

var spotiCrazyRadio = function() {

	function addParameter(name, table) {
		var row = $('<tr>', {class: 'ucfirst'}).appendTo(table);
		row.append('<td><b>'+name+'</b></td>');
		var h = $('<td>', {width: '100%'}).appendTo(row);
		switch (name) {
			case 'tempo':
				var options = {startmax: 1, range: 300};
				break;
			case 'popularity':
				var options = {startmax: 1, range: 100};
				break;
			default:
				var options = {startmax: 1, range: 1}
				break;
		}
		var s = $('<div>', {name: name, class: 'spotiradioslider'}).appendTo(h).rangechooser(options);
	}

	return {

		setup: function() {
			if (player.canPlay('spotify')) {

				//
				// Spotify Playlist Generator
				//
				$("#pluginplaylists_crazy").append('<div class="textcentre textunderline"><b>Create Your Own Spotify Playlist Generator</b></div>');
				$("#pluginplaylists_crazy").append('<div class="textcentre tiny">Enter some Genres, set the parameters, and click Play.<br>You can drag both ends of the sliders to set a range.</div>');

				var a = $('<div>', {class: "menuitem spacer", style: "margin-right:8px"}).appendTo("#pluginplaylists_crazy");
				var c = $('<div>', {class: "containerbox expand spacer dropdown-container"}).
					appendTo(a).makeTagMenu({
					textboxname: 'spotigenres',
					placeholder: 'Enter Genres',
					populatefunction: populateSpotiTagMenu
				});

				var table = $('<table>').appendTo('#pluginplaylists_crazy');
				['energy', 'danceability', 'valence', 'instrumentalness', 'acousticness', 'tempo', 'liveness', 'popularity'].forEach(function(i) {
					addParameter(i, table)
				});

				html = '<div class="containerbox dropdown-container bacon">';
				html += '<div class="expand"></div>';
				html += '<button class="fixed iconbutton savebutton" '+
					'onclick="crazyRadioManager.saveCrazyRadioSettings(event)"></button>';
				html += '<button class="fixed iconbutton icon-no-response-playbutton" '+
					'onclick="crazyRadioManager.go()"></button>';
				html += '</div>';
				$("#pluginplaylists_crazy").append(html);

				crazyRadioManager.loadSavedCrazies();
			}
		}
	}
}();

playlist.radioManager.register("spotiCrazyRadio", spotiCrazyRadio, 'radios/code/spotiCrazyRadio.js');
clickRegistry.addClickHandlers('crazyradio', crazyRadioManager.handleClick);
