var player = function() {

    const default_player = data_from_source('default_player');
    const player_connection_params = data_from_source('player_connection_params');

	function playerEditor() {

		var self = this;
		var playerpu;
		var numhosts;
		var playerholder;

		function removePlayerDef(event) {
			if (decodeURIComponent($(event.target).attr('name')) == prefs.currenthost) {
				infobar.error(language.gettext('error_cantdeleteplayer'));
			} else {
				// Don't remove the element by name, in case we've got another
				// element with the same name
				$(event.target).parent().parent().remove();
				playerpu.setCSS(true, true);
			}
		}

		function addNewPlayerRow(name, def, is_new) {
			name = encodeURIComponent(name);
			let holder = $('<div>', {class: 'single-player', name: name}).appendTo(playerholder);
			let title = $('<div>', {class: 'containerbox snapgrouptitle player-def vertical-centre'}).appendTo(holder);
			$('<input>', {type: 'text', class: 'expand tag', name: 'name'}).val(name).appendTo(title);
			$.each(player_connection_params, function(i,v) {
				let row = $('<div>', {class: 'pref containerbox vertical-centre'}).appendTo(holder);
				$('<div>', {class: 'divlabel fixed'}).html(i).appendTo(row);
				if (typeof(default_player[v]) == 'string') {
					$('<input>', {type: 'text', class: 'expand', name: v}).val(def[v]).appendTo(row);
				} else {
					let ping = $('<div>', {class: 'styledinputs expand'}).appendTo(row);
					$('<input>', {type: 'checkbox', name: v, id: v+'_'+numhosts}).appendTo(ping);
					$('<label>', {for: v+'_'+numhosts}).html('&nbsp;').appendTo(ping);
					$('#'+v+'_'+numhosts).prop('checked', def[v]);
				}
			});
			let remove_row = $('<div>', {class: 'pref containerbox vertical-centre'}).appendTo(holder);
			$('<div>', {class: 'expand'}).appendTo(remove_row);
			$('<i>', {class: 'fixed icon-cancel-circled smallicon clickicon clickremhost', name: name}).on('click', removePlayerDef).appendTo(remove_row);
			numhosts++;
			if (is_new) {
				playerpu.adjustCSS(true, true);
				playerpu.scrollTo(holder);
			}
		}

		function updatePlayerChoices() {
			var newhosts = new Object();
			var reloadNeeded = false;
			var error = false;
			playerholder.find('div.single-player').each(function() {
				var currentname = decodeURIComponent($(this).attr('name'));
				var newname = "";
				var temp = new Object();
				$(this).find('input').each(function() {
					if ($(this).attr('name') == 'name') {
						newname = $(this).val();
					} else {
						if ($(this).attr('type') == 'checkbox') {
							temp[$(this).attr('name')] = $(this).is(':checked');
						} else {
							temp[$(this).attr('name')] = $(this).val();
						}
					}
				});

				if (newhosts.hasOwnProperty(newname)) {
					infobar.error(language.gettext('error_duplicateplayer'));
					error = true;
				}

				newhosts[newname] = temp;
				if (currentname == prefs.currenthost) {
					if (newname != currentname) {
						debug.mark('PLAYERS', "Current Player renamed to "+newname);
						reloadNeeded = newname;
					}
					$.each(player_connection_params, function(i, val) {
						debug.log('PLAYERS', 'Checking Player', i, val, temp[val], prefs.multihosts[prefs.currenthost][val]);
						if (temp[val] != prefs.multihosts[prefs.currenthost][val]) {
							debug.mark('PLAYERS', "Current Player connection details changed");
							reloadNeeded = newname;
						}
					});
				}

			});

			// If we've updated an existing one, overwrite the properties we've changed
			// but keep the ones we don't reference in the table. Don't modify the existing
			// definition, prefs will do that.
			for (var i in newhosts) {
				if (prefs.multihosts.hasOwnProperty(i)) {
					newhosts[i] = $.extend({}, prefs.multihosts[i], newhosts[i]);
				} else {
					newhosts[i] = $.extend({}, default_player, newhosts[i]);
				}
			}
			if (error) {
				return false;
			}
			debug.log("PLAYERS",newhosts);
			if (reloadNeeded !== false) {
				prefs.save({
					multihosts: newhosts
				}).then(function() {
					prefs.save({
						currenthost: reloadNeeded,
						player_backend: ''
					}).then(function() {
						reloadWindow();
					});
				});
			} else {
				prefs.save({multihosts: newhosts});
				self.replacePlayerOptions();
				prefs.setPrefs();
			}
			return true;
		}

		this.edit = async function() {
			// Need to make sure we refresh any values that the backed has changed (eg do_consume
			// and radioparams)
			await prefs.loadPrefs();
			$("#configpanel").slideToggle('fast');
			playerpu = new popup({
				width: 500,
				title: language.gettext('config_players'),
				helplink: "https://fatg3erman.github.io/RompR/Using-Multiple-Players"});
			playerholder = playerpu.create();
			numhosts = 0;

			for (var i in prefs.multihosts) {
				addNewPlayerRow(i, prefs.multihosts[i], false);
			}

			var add = playerpu.add_button('left', 'button_add');
			add.on('click', function() {
				addNewPlayerRow('New', default_player, true);
			});

			var c = playerpu.add_button('right', 'button_cancel');
			playerpu.useAsCloseButton(c, false);

			var d = playerpu.add_button('right', 'button_OK');
			playerpu.useAsCloseButton(d, updatePlayerChoices);

			$('.clickremhost').off('click');
			$('.clickremhost').on('click', removePlayerDef);

			playerpu.open();
		}

		this.replacePlayerOptions = function() {
			var numhosts;
			$('[name="playerdefs"]').each(function(index) {
				$(this).empty();
				numhosts = 0;
				for (var i in prefs.multihosts) {
					numhosts++;
					$(this).append('<input type="radio" class="topcheck savulon" name="currenthost_duplicate'+index+'" value="'+
						i+'" id="host_'+escape(i)+index+'">'+
						'<label for="host_'+escape(i)+index+'">'+i+'</label>');
				}
			});
			$('[name="playerdefs"] > .savulon').off('click').on('click', prefs.toggleRadio);
			if (numhosts == 1) {
				$('[name="playerdefs"]').hide();
				$('.player-title').hide();
			} else {
				$('[name="playerdefs"]').show();
				$('.player-title').show();
			}
		}
	}

	var sources_not_to_choose = {
		file: 1,
		http: 1,
		https: 1,
		mms: 1,
		rtsp: 1,
		somafm: 1,
		spotifytunigo: 1,
		rtmp: 1,
		rtmps: 1,
		sc: 1,
		yt: 1,
		m3u: 1,
		spotifyweb: 1,
		'podcast+http': 1,
		'podcast+https': 1,
		'podcast+ftp': 1,
		'podcast+file': 1,
		'podcast+itunes': 1,
		'podcast+gpodder.net': 1,
		'podcast+gpodder': 1
	}

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
			progress: null
		},

		genreseeds: [],
		urischemes: new Object(),
		collectionLoaded: false,
		updatingcollection: false,
		ready: false,

		controller: new playerController(),
		defs: new playerEditor(),

		canPlay: function(urischeme) {
			return this.urischemes.hasOwnProperty(urischeme);
		},

		skip: function(sec) {
			if (this.status.state == "play") {
				this.controller.seekcur(sec > 0 ? "+"+sec : sec);
			}
		},

		not_updating: async function() {
			while (player.updatingcollection) {
				await new Promise(t => setTimeout(t, 500));
			}
		},

		get_search_uri_schemes: function() {
			if (prefs.player_backend == 'mpd')
				return ['local'];

			let schemes = [];
			for (var i in player.urischemes) {
				if (!sources_not_to_choose.hasOwnProperty(i)) {
					schemes.push(i);
				}
			}
			return schemes;
		}

	}

}();
