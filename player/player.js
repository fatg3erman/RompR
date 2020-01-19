var player = function() {

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

		function addNewPlayerRow() {
			$("#playertable").append('<tr class="hostdef" name="New">'+
				'<td><input type="text" size="30" name="name" class="notspecial" value="New"/></td>'+
				'<td><input type="text" size="30" name="host" value=""/></td>'+
				'<td><input type="text" size="30" name="port" value=""/></td>'+
				'<td><input type="text" size="30" name="password" value=""/></td>'+
				'<td><input type="text" size="30" name="socket" value=""/></td>'+
				'<td align="center"><div class="styledinputs"><input type="checkbox" name="mopidy_slave" id="mopidy_slave_'+numhosts+'" /><label for="mopidy_slave_'+numhosts+'">&nbsp;</label></div></td>'+
				'<td><i class="icon-cancel-circled smallicon clickicon clickremhost"></i></td>'+
				'</tr>'
			);
			numhosts++;
			$('.clickremhost').off('click').on('click', removePlayerDef);
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
						debug.mark("Current Player renamed to "+newname,"PLAYERS");
						reloadNeeded = newname;
					}
					if (temp.host != prefs.multihosts[prefs.currenthost].host ||
						temp.port != prefs.multihosts[prefs.currenthost].port||
						temp.socket != prefs.multihosts[prefs.currenthost].socket ||
						temp.password != prefs.multihosts[prefs.currenthost].password) {
						debug.mark("Current Player connection details changed","PLAYERS");
						reloadNeeded = newname;
					}
				}
			});
			for (var i in newhosts) {
				if (prefs.multihosts.hasOwnProperty(i) && prefs.multihosts[i].hasOwnProperty('radioparams')) {
					newhosts[i].radioparams = prefs.multihosts[i].radioparams;
				} else {
					newhosts[i].radioparams = {
						radiomode: '',
						radioparam: '',
						radiomaster: '',
						radioconsume: 0
					}
				}
			}
			if (error) {
				return false;
			}
			debug.log("PLAYERS",newhosts);
			if (reloadNeeded !== false) {
				setCookie('player_backend', '', 0);
				prefs.save({currenthost: reloadNeeded}).then(function() {
					prefs.save({multihosts: newhosts}).then(function() {
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

		this.edit = function() {
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
			mywin.append('<table align="center" cellpadding="2" id="playertable" width="96%"></table>');
			$("#playertable").append('<tr><th>NAME</th><th>HOST</th><th>PORT</th><th>PASSWORD</th><th>UNIX SOCKET</th><th>SLAVE</th></tr>');
			for (var i in prefs.multihosts) {
				$("#playertable").append('<tr class="hostdef" name="'+escape(i)+'">'+
					'<td><input type="text" size="30" name="name" class="notspecial" value="'+i+'"/></td>'+
					'<td><input type="text" size="30" name="host" value="'+prefs.multihosts[i]['host']+'"/></td>'+
					'<td><input type="text" size="30" name="port" value="'+prefs.multihosts[i]['port']+'"/></td>'+
					'<td><input type="text" size="30" name="password" value="'+prefs.multihosts[i]['password']+'"/></td>'+
					'<td><input type="text" size="30" name="socket" value="'+prefs.multihosts[i]['socket']+'"/></td>'+
					'<td align="center"><div class="styledinputs"><input type="checkbox" name="mopidy_slave" id="mopidy_slave_'+numhosts+'" /><label for="mopidy_slave_'+numhosts+'">&nbsp;</label></div></td>'+
					'<td><i class="icon-cancel-circled smallicon clickicon clickremhost"></i></td>'+
					'</tr>'
				);
				$('#mopidy_slave_'+numhosts).prop('checked', prefs.multihosts[i]['mopidy_slave']);
				numhosts++;
			}
			var buttons = $('<div>',{class: "pref clearfix"}).appendTo(mywin);
			var add = $('<i>',{class: "icon-plus smallicon clickicon tleft"}).appendTo(buttons);
			add.on('click', function() {
				addNewPlayerRow();
				playerpu.setWindowToContentsSize();
			});
			var c = $('<button>',{class: "tright"}).appendTo(buttons);
			c.html(language.gettext('button_cancel'));
			playerpu.useAsCloseButton(c, false);

			var d = $('<button>',{class: "tright"}).appendTo(buttons);
			d.html(language.gettext('button_OK'));
			playerpu.useAsCloseButton(d, updatePlayerChoices);

			$('.clickremhost').off('click');
			$('.clickremhost').on('click', removePlayerDef);

			playerpu.open();
		}

		this.replacePlayerOptions = function() {
			$('[name="playerdefs"]').each(function(index) {
				$(this).empty();
				for (var i in prefs.multihosts) {
					$(this).append('<input type="radio" class="topcheck savulon" name="currenthost_duplicate'+index+'" value="'+
						i+'" id="host_'+escape(i)+index+'">'+
						'<label for="host_'+escape(i)+index+'">'+i+'</label><br/>');
				}
			});
			$('[name="playerdefs"] > .savulon').off('click').on('click', prefs.toggleRadio);
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
				var p = infobar.progress();
				var to = p + sec;
				if (p < 0) p = 0;
				this.controller.seek(to);
			}
		}

	}

}();
