var player = function() {

    var jsonNode = document.querySelector("script[name='default_player']");
    var jsonText = jsonNode.textContent;
    const default_player = JSON.parse(jsonText);

    jsonNode = document.querySelector("script[name='player_connection_params']");
    jsonText = jsonNode.textContent;
    const player_connection_params = JSON.parse(jsonText);

	function playerEditor() {

		var self = this;
		var playerpu;
		var numhosts;

		function removePlayerDef(event) {
			if (decodeURIComponent($(event.target).parent().parent().attr('name')) == prefs.currenthost) {
				infobar.error(language.gettext('error_cantdeleteplayer'));
			} else {
				$(event.target).parent().parent().remove();
				playerpu.setWindowToContentsSize();
			}
		}

		function addNewPlayerRow(name, def) {
			let row = $('<tr>', {class: 'hostdef'}).appendTo($('#playertable'));
			let td = $('<td>').appendTo(row);
			$('<input>', {type: 'text', size: '30', name: 'name', class: 'notspecial'}).val(name).appendTo(td);
			$.each(player_connection_params, function(i,v) {
				td = $('<td>').appendTo(row);
				if (typeof(default_player[v]) == 'string') {
					$('<input>', {type: 'text', size: '30', name: v}).val(def[v]).appendTo(td);
				} else {
					td.addClass('styledinputs textcentre');
					$('<input>', {type: 'checkbox', name: v, id: v+'_'+numhosts}).appendTo(td);
					$('<label>', {for: v+'_'+numhosts}).html('&nbsp;').appendTo(td);
					$('#'+v+'_'+numhosts).prop('checked', def[v]);
				}
			});
			td = $('<td>').appendTo(row);
			$('<i>', {class: 'icon-cancel-circled smallicon clickicon clickremhost'}).on('click', removePlayerDef).appendTo(td);
			numhosts++;
		}

		function updatePlayerChoices() {
			var newhosts = new Object();
			var reloadNeeded = false;
			var error = false;
			$("#playertable").find('tr.hostdef').each(function() {
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
				setCookie('player_backend', '', 1);
				prefs.save({multihosts: newhosts}).then(function() {
					prefs.save({currenthost: reloadNeeded}).then(function() {
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
				css: {
					width: 900,
					height: 800
				},
				fitheight: true,
				title: language.gettext('config_players'),
				helplink: "https://fatg3erman.github.io/RompR/Using-Multiple-Players"});
			var mywin = playerpu.create();
			numhosts = 0;
			mywin.append('<table align="center" cellpadding="2" id="playertable" width="100%"></table>');
			let titlerow = $('<tr>').appendTo($('#playertable'));
			$('<th>').html('NAME').appendTo(titlerow);
			$.each(player_connection_params, function(i, v) {
				$('<th>').html(i).appendTo(titlerow);
			})
			for (var i in prefs.multihosts) {
				addNewPlayerRow(i, prefs.multihosts[i]);
			}

			var add = playerpu.add_button('left', 'button_add');
			add.on('click', function() {
				addNewPlayerRow('New', default_player);
				playerpu.setWindowToContentsSize();
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
		}

	}

}();
