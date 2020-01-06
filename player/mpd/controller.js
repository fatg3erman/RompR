async function checkProgress() {
	var AlanPartridge = 0;
	var safetytimer = 250;
	var waittime = 1000;
	while (true) {
		await playlist.is_valid();
		if (player.status.songid !== player.controller.previoussongid) {
			safetytimer = 0;
		}
		var progress = (Date.now()/1000) - player.controller.trackstarttime;
		playlist.setCurrent({progress: progress});
		var duration = playlist.getCurrent('Time') || 0;
		infobar.setProgress(progress,duration);
		if (player.status.state == 'play' && duration > 0 && progress > (duration - 1)) {
			AlanPartridge = 5;
			safetytimer = Math.min(safetytimer + 250, 5000);
			waittime = safetytimer;
		} else {
			AlanPartridge++;
			waittime = 1000;
		}
		if (AlanPartridge >= 5) {
			AlanPartridge = 0;
			await player.controller.do_command_list([]);
			updateStreamInfo();
		}
		await new Promise(t => setTimeout(t, waittime));
	}
}

function updateStreamInfo() {

	// When playing a stream, mpd returns 'Title' in its status field.
	// This usually has the form artist - track. We poll this so we know when
	// the track has changed (note, we rely on radio stations setting their
	// metadata reliably)

	// Note that mopidy doesn't quite work this way. It sets Title and possibly Name
	// - I fixed that bug once but it got broke again

	if (playlist.getCurrent('type') == "stream") {
		// debug.trace('STREAMHANDLER','Playlist:',playlist.getCurrent('Title'),playlist.getCurrent('Album'),playlist.getCurrent('trackartist'));
		var temp = playlist.getCurrentTrack();
		if (player.status.Title) {
			var parts = player.status.Title.split(" - ");
			if (parts[0] && parts[1]) {
				temp.trackartist = parts.shift();
				temp.Title = parts.join(" - ");
				temp.metadata.artists = [{name: temp.trackartist, musicbrainz_id: ""}];
				temp.metadata.track = {name: temp.Title, musicbrainz_id: ""};
			} else if (player.status.Title && player.status.Artist) {
				temp.trackartist = player.status.Artist;
				temp.Title = player.status.Title;
				temp.metadata.artists = [{name: temp.trackartist, musicbrainz_id: ""}];
				temp.metadata.track = {name: temp.Title, musicbrainz_id: ""};
			}
		}
		if (player.status.Name && !player.status.Name.match(/^\//) && temp.Album == rompr_unknown_stream) {
			// NOTE: 'Name' is returned by MPD - it's the station name as read from the station's stream metadata
			debug.shout('STREAMHANDLER',"Checking For Stream Name Update");
			checkForUpdateToUnknownStream(playlist.getCurrent('StreamIndex'), player.status.Name);
			temp.Album = player.status.Name;
			temp.metadata.album = {name: temp.Album, musicbrainz_id: ""};
		}
		// debug.trace('STREAMHANDLER','Current:',temp.Title,temp.Album,temp.trackartist);
		if (playlist.getCurrent('Title') != temp.Title ||
			playlist.getCurrent('Album') != temp.Album ||
			playlist.getCurrent('trackartist') != temp.trackartist)
		{
			debug.shout("STREAMHANDLER","Detected change of track",temp);
			var aa = new albumart_translator('');
			temp.key = aa.getKey('stream', '', temp.Album);
			playlist.setCurrent({Title: temp.Title, Album: temp.Album, trackartist: temp.trackartist });
			nowplaying.newTrack(temp, true);
		}
	}
}

function checkForUpdateToUnknownStream(streamid, name) {
	// If our playlist for this station has 'Unknown Internet Stream' as the
	// station name, let's see if we can update it from the metadata.
	debug.log("STREAMHANDLER","Checking For Update to Stream",streamid,name, name);
	var m = playlist.getCurrent('Album');
	if (m.match(/^Unknown Internet Stream/)) {
		debug.shout("PLAYLIST","Updating Stream",name);
		yourRadioPlugin.updateStreamName(streamid, name, playlist.getCurrent('file'), playlist.repopulate);
	}
}

function playerController() {

	var self = this;
	var plversion = null;
	var oldplname;
	var thenowplayinghack = false;
	var lastsearchcmd = "search";
	var stateChangeCallbacks = new Array();

	this.trackstarttime = 0;
	this.previoussongid = -1;

	this.initialise = async function() {
		debug.shout('PLAYER', 'Initialising');
		try {
			var urischemes = await $.ajax({
				type: 'GET',
				url: 'player/mpd/geturlhandlers.php',
				dataType: 'json'
			});
			for (var i in urischemes) {
				var h = urischemes[i].replace(/\:\/\/$/,'');
				debug.log("PLAYER","URI Handler : ",h);
				player.urischemes[h] = true;
			}
			if (!player.canPlay('spotify')) {
				$('div.textcentre.textunderline:contains("Music From Spotify")').remove();
			}
			checkSearchDomains();
			doMopidyCollectionOptions();
			playlist.radioManager.init();
			self.do_command_list([]);
			debug.mark("MPD","Player is ready");
			infobar.notify(
				"Connected to "+getCookie('currenthost')+" ("
				+prefs.player_backend.capitalize()
				+" at " + player_ip + ")"
			);
			checkProgress();
		} catch(err) {
			debug.error("MPD","Failed to get URL Handlers",err);
			infobar.permerror(language.gettext('error_noplayer'));			
		}
	}

	this.do_command_list = async function(list) {
		debug.debug('PLAYER', 'Command List',list);
		// Prevent checkProgress and radioManager from doing anything while we're doing things
		playlist.invalidate();
		try {
			// Use temp variable in case it errors
			var s = await $.ajax({
				type: 'POST',
				url: 'player/mpd/postcommand.php',
				data: JSON.stringify(list),
				contentType: false,
				dataType: 'json',
				timeout: 30000
			});
			// Clone the object so this thread can exit
			debug.debug('PLAYER', 'Got response for',list);
			player.status = cloneObject(s);
			['radiomode', 'radioparam', 'radiomaster', 'radioconsume'].forEach(function(e) {
				debug.debug('PLAYER', e, player.status[e]);
				prefs[e] = player.status[e];
			});
			if (player.status.songid != self.previoussongid) {
				playlist.trackHasChanged(player.status.songid);
				self.previoussongid = player.status.songid;
			}
			self.trackstarttime = (Date.now()/1000) - player.status.elapsed;
			if (player.status.playlist !== plversion) {
				debug.blurt("PLAYER","Player has marked playlist as changed",plversion,player.status.playlist);
				plversion = player.status.playlist;
				// Repopulate will revalidate the playlist when it completes.
				playlist.repopulate();
			} else {
				playlist.validate();
			}
			checkStateChange();
			infobar.updateWindowValues();
		} catch (err) {
			playlist.validate();
			debug.error('CONTROLLER', 'Command List Failed', err);
			if (list.length > 0) {
				infobar.error(language.gettext('error_sendingcommands', [prefs.player_backend]));
			}
		}
	}

	this.addStateChangeCallback = function(sc) {
		if (player.status.state == sc.state) {
			sc.callback();
		} else {
			stateChangeCallbacks.push(sc);
		}
	}

	function checkStateChange() {
		for (var i = 0; i < stateChangeCallbacks.length ; i++) {
			if (stateChangeCallbacks[i].state == player.status.state) {
				// If we're looking for a state change to play, check that elapsed > 5. This works around Mopidy's
				// buffering issue where playback can take a long time to start with streams and ends up starting a
				// long time after we've started ramping the alarm clock volume
				debug.log('PLAYER', 'State Change Check. State is',player.status.state,'Elapsed is',player.status.elapsed);
				if (player.status.state != 'play' || player.status.elapsed > 5) {
					debug.mark('PLAYER', 'Calling state change callback for state',player.status.state);
					stateChangeCallbacks[i].callback();
					stateChangeCallbacks.splice(i, 1);
					i--;
				}
			}
		}
	}

	this.reloadPlaylists = function() {
		var openplaylists = [];
		$('#storedplaylists').find('i.menu.openmenu.playlist.icon-toggle-open').each(function() {
			openplaylists.push($(this).attr('name'));
		})
		$.get("player/mpd/loadplaylists.php", function(data) {
			$("#storedplaylists").html(data);
			uiHelper.doThingsAfterDisplayingListOfAlbums($('#storedplaylists'));
			$('b:contains("'+language.gettext('button_loadplaylist')+'")').parent('.configtitle').append('<a href="https://fatg3erman.github.io/RompR/Using-Saved-Playlists" target="_blank"><i class="icon-info-circled playlisticonr tright"></i></a>');
			for (var i in openplaylists) {
				$('i.menu.openmenu.playlist.icon-toggle-closed[name="'+openplaylists[i]+'"]').click();
			}
			if (openplaylists.length > 0) {
				infobar.markCurrentTrack();
			}
			$.get('player/mpd/loadplaylists.php?addtoplaylistmenu', function(data) {
				$('#addtoplaylistmenu').empty();
				data.forEach(function(p) {
					var h = $('<div>', {class: "containerbox backhi clickicon menuitem clickaddtoplaylist", name: p.name }).appendTo($('#addtoplaylistmenu'));
					h.append('<i class="fixed collectionicon icon-doc-text"></i>');
					h.append('<div class="expand">'+p.html+'</div>');
				});
				startBackgroundInitTasks.doNextTask();
			});
		});
	}

	this.loadPlaylist = function(name) {
		self.do_command_list([['load', name]]);
		return false;
	}

	this.loadPlaylistURL = function(name) {
		if (name == '') {
			return false;
		}
		var data = {url: encodeURIComponent(name)};
		$.ajax({
			type: "GET",
			url: "utils/getUserPlaylist.php",
			cache: false,
			data: data,
			dataType: "xml"
		})
		.done(function() {
			self.reloadPlaylists();
			self.addTracks([{type: 'remoteplaylist', name: name}], null, null);
		})
		.fail(function(data, status) {
			playlist.repopulate();
			debug.error("MPD","Failed to save user playlist URL");
		});
		return false;
	}

	this.deletePlaylist = function(name) {
		self.do_command_list([['rm',decodeURIComponent(name)]]).then(self.reloadPlaylists);
	}

	this.deleteUserPlaylist = function(name) {
		var data = {del: name};
		$.ajax({
			type: "GET",
			url: "utils/getUserPlaylist.php",
			cache: false,
			data: data,
			dataType: "xml"
		})
		.done(self.reloadPlaylists)
		.fail(function(data, status) {
			debug.error("MPD","Failed to delete user playlist",name);
		});
	}

	this.renamePlaylist = function(name, e, callback) {
		oldplname = decodeURIComponent(name);
		debug.log("MPD","Renaming Playlist",name,e);
		var fnarkle = new popup({
			css: {
				width: 400,
				height: 300
			},
			title: language.gettext("label_renameplaylist"),
			atmousepos: true,
			mousevent: e
		});
		var mywin = fnarkle.create();
		var d = $('<div>',{class: 'containerbox'}).appendTo(mywin);
		var e = $('<div>',{class: 'expand'}).appendTo(d);
		var i = $('<input>',{class: 'enter', id: 'newplname', type: 'text', size: '200'}).appendTo(e);
		var b = $('<button>',{class: 'fixed'}).appendTo(d);
		b.html('Rename');
		fnarkle.useAsCloseButton(b, callback);
		fnarkle.open();
	}

	this.doRenamePlaylist = function() {
		self.do_command_list([["rename", oldplname, $("#newplname").val()]]).then(self.reloadPlaylists);
		return true;
	}

	this.doRenameUserPlaylist = function() {
		var data = {rename: encodeURIComponent(oldplname),
					newname: encodeURIComponent($("#newplname").val())
		};
		$.ajax({
			type: "GET",
			url: "utils/getUserPlaylist.php",
			cache: false,
			data: data,
			dataType: "xml"
		})
		.done(function(data) {
			self.reloadPlaylists();
		})
		.fail(function(data, status) {
			debug.error("MPD","Failed to rename user playlist",name);
		});
		return true;
	}

	this.deletePlaylistTrack = function(playlist,songpos) {
		debug.log('PLAYER', 'Deleting track',songpos,'from playlist',playlist);
		self.do_command_list([['playlistdelete',decodeURIComponent(playlist),songpos]]).then(
			function() {
				playlistManager.loadPlaylistIntoTarget(playlist);
			}
		);
	}

	this.clearPlaylist = function() {
		// Mopidy does not like removing tracks while they're playing
		self.do_command_list([['stop'], ['clear']]);
	}

	this.savePlaylist = function() {
		var name = $("#playlistname").val();
		debug.log("GENERAL","Save Playlist",name);
		if (name == '') {
			return false;
		} else if (name.indexOf("/") >= 0 || name.indexOf("\\") >= 0) {
			infobar.error(language.gettext("error_playlistname"));
		} else {
			self.do_command_list([["save", name]]).then(function() {
				self.reloadPlaylists();
				infobar.notify(language.gettext("label_savedpl", [name]));
				$("#plsaver").slideToggle('fast');
			});
		}
	}

	this.play = function() {
		self.do_command_list([['play']]);
	}

	this.pause = function() {
		self.do_command_list([['pause']]);
	}

	this.stop = function() {
		playlist.checkPodcastProgress();
		self.do_command_list([["stop"]]);
	}

	this.next = function() {
		playlist.checkPodcastProgress();
		self.do_command_list([["next"]]);
	}

	this.previous = function() {
		playlist.checkPodcastProgress();
		self.do_command_list([["previous"]]);
	}

	this.seek = function(seekto) {
		debug.log("PLAYER","Seeking To",seekto);
		self.do_command_list([["seek", player.status.song, parseInt(seekto.toString())]]);
	}

	this.playId = function(id) {
		playlist.checkPodcastProgress();
		self.do_command_list([["playid",id]]);
	}

	this.playByPosition = function(pos) {
		playlist.checkPodcastProgress();
		self.do_command_list([["play",pos.toString()]]);
	}

	this.volume = function(volume, callback) {
		self.do_command_list([["setvol",parseInt(volume.toString())]]).then(function() {
			if (callback) {
				callback();
			}
		});
		return true;
	}

	this.removeId = function(ids) {
		var cmdlist = [];
		$.each(ids, function(i,v) {
			cmdlist.push(["deleteid", v]);
		});
		self.do_command_list(cmdlist);
	}

	this.toggleRandom = function() {
		debug.log('PLAYER', 'Toggling Random');
		var new_value = (player.status.random == 0) ? 1 : 0;
		self.do_command_list([["random",new_value]]).then(playlist.doUpcomingCrap);
	}

	this.toggleCrossfade = function() {
		debug.log('PLAYER', 'Toggling Crossfade');
		var new_value = (player.status.xfade === undefined || player.status.xfade === null ||
			player.status.xfade == 0) ? prefs.crossfade_duration : 0;
		self.do_command_list([["crossfade",new_value]]);
	}

	this.setCrossfade = function(v) {
		self.do_command_list([["crossfade",v]]);
	}

	this.toggleRepeat = function() {
		debug.log('PLAYER', 'Toggling Repeat');
		var new_value = (player.status.repeat == 0) ? 1 : 0;
		self.do_command_list([["repeat",new_value]]);
	}

	this.toggleConsume = function() {
		debug.log('PLAYER', 'Toggling Consume');
		var new_value = (player.status.consume == 0) ? 1 : 0;
		self.do_command_list([["consume",new_value]]);
	}

	this.takeBackControl = async function(v) {
		await self.do_command_list([["repeat",0],["random", 0],["consume", 1]]);
	}

	this.addTracks = async function(tracks, playpos, at_pos, queue) {
		// Call into this to add items to the play queue.
		// tracks  : list of things to add
		// playpos : position to start playback from after adding items or null
		// at_pos  : position to add tracks at (tracks will be added to end then moved to position) or null
		// queue   : true to always queue regardless of CD Player mode

		var abitofahack = true;
		var queue_track = (queue == true) ? true : !prefs.cdplayermode;
		debug.mark("MPD","Adding",tracks.length,"Tracks at",at_pos,"playing from",playpos,"queue is",queue);
		var cmdlist = [];
		if (!queue_track) {
			cmdlist.push(['stop']);
			cmdlist.push(['clear']);
		}
		$.each(tracks, function(i,v) {
			debug.debug('MPD', v);
			switch (v.type) {
				case "uri":
					if (queue_track) {
						cmdlist.push(['add',v.name]);
					} else {
						cmdlist.push(['addtoend', v.name]);
					}
					break;

				case "playlist":
				case "cue":
					cmdlist.push(['load',v.name]);
					break;

				case "item":
					cmdlist.push(['additem',v.name]);
					break;

				case "podcasttrack":
					cmdlist.push(['add',v.name]);
					break;

				case "artist":
					cmdlist.push(['addartist',v.name]);
					break;

				case "stream":
					cmdlist.push(['loadstreamplaylist',v.url,v.image,v.station]);
					break;

				case "playlisttrack":
					if (queue_track) {
						cmdlist.push(['add',v.name]);
					} else {
						cmdlist.push(['playlisttoend',v.playlist,v.frompos]);
					}
					break;

				case "resumepodcast":
					var is_already_in_playlist = playlist.findIdByUri(v.uri);
					if (is_already_in_playlist !== false && queue_track) {
						cmdlist.push(['playid', is_already_in_playlist]);
						cmdlist.push(['seekpodcast', is_already_in_playlist, v.resumefrom]);
					} else {
						var pos = queue_track ? playlist.getfinaltrack()+1 : 0;
						var to_end = queue_track ? 'no' : 'yes';
						cmdlist.push(['resume', v.uri, v.resumefrom, pos, to_end]);
						if (at_pos === null) {
							playlist.waiting();
						}
					}
					// Don't add the play command if we're doing a resume,
					// because postcommand.php will add it and this will override it
					abitofahack = false;
					playpos = null;
					break;

				case 'remoteplaylist':
					cmdlist.push(['addremoteplaylist', v.name]);
					break;
			}
		});
		if (abitofahack && !queue_track) {
			cmdlist.push(['play']);
		} else if (playpos !== null) {
			cmdlist.push(['play', playpos.toString()]);
		}
		if (at_pos === 0 || at_pos) {
			cmdlist.push(['moveallto', at_pos]);
		}
		if (at_pos === null && abitofahack) {
			playlist.waiting();
		}
		await self.do_command_list(cmdlist);
	}

	this.move = function(first, num, moveto) {
		var itemstomove = first.toString();
		if (num > 1) {
			itemstomove = itemstomove + ":" + (parseInt(first)+parseInt(num));
		}
		if (itemstomove == moveto) {
			// This can happen if you drag the final track from one album to a position below the
			// next album's header but before its first track. This doesn't change its position in
			// the playlist but the item in the display will have moved and we need to move it back.
			playlist.repopulate();
		} else {
			debug.log("PLAYER", "Move command is move&arg="+itemstomove+"&arg2="+moveto);
			self.do_command_list([["move",itemstomove,moveto]]);
		}
	}

	this.stopafter = function() {
		var cmds = [];
		if (player.status.repeat == 1) {
			cmds.push(["repeat", 0]);
		}
		cmds.push(["single", 1]);
		self.do_command_list(cmds);
	}

	this.cancelSingle = function() {
		self.do_command_list([["single",0]]);
	}

	this.doOutput = function(id) {
		state = $('#outputbutton_'+id).is(':checked');
		if (state) {
			self.do_command_list([["disableoutput",id]]);
		} else {
			self.do_command_list([["enableoutput",id]]);
		}
	}

	this.doMute = function() {
		debug.log('PLAYER', 'Toggling Mute');
		if (prefs.player_backend == "mopidy") {
			if ($("#mutebutton").hasClass('icon-output-mute')) {
				$("#mutebutton").removeClass('icon-output-mute').addClass('icon-output');
				self.do_command_list([["disableoutput", 0]]);
			} else {
				$("#mutebutton").removeClass('icon-output').addClass('icon-output-mute');
				self.do_command_list([["enableoutput", 0]]);
			}
		} else {
			if ($("#mutebutton").hasClass('icon-output-mute')) {
				$("#mutebutton").removeClass('icon-output-mute').addClass('icon-output');
				self.do_command_list([["enableoutput", 0]]);
			} else {
				$("#mutebutton").removeClass('icon-output').addClass('icon-output-mute');
				self.do_command_list([["disableoutput", 0]]);
			}
		}
	}

	this.search = function(command) {
		if (player.updatingcollection) {
			infobar.notify(language.gettext('error_nosearchnow'));
			return false;
		}
		var terms = {};
		var termcount = 0;
		lastsearchcmd = command;
		$("#collectionsearcher").find('.searchterm').each( function() {
			var key = $(this).attr('name');
			var value = $(this).val();
			if (value != "") {
				debug.trace("PLAYER","Searching for",key, value);
				terms[key] = value.split(',');
				termcount++;
			}
		});
		if ($('[name="searchrating"]').val() != "") {
			terms['rating'] = $('[name="searchrating"]').val();
			termcount++;
		}
		var domains = new Array();
		if (prefs.search_limit_limitsearch && $('#mopidysearchdomains').length > 0) {
			// The second term above is just in case we're swapping between MPD and Mopidy
			// - it prevents an illegal invocation in JQuery if limitsearch is true for MPD
			domains = $("#mopidysearchdomains").makeDomainChooser("getSelection");
		}
		if (termcount > 0) {
			$("#searchresultholder").empty();
			doSomethingUseful('searchresultholder', language.gettext("label_searching"));
			var st = {
				command: command,
				resultstype: prefs.displayresultsas,
				domains: domains,
				dump: collectionHelper.collectionKey('b')
			};
			debug.log("PLAYER","Doing Search:",terms,st);
			if ((termcount == 1 && (terms.tag || terms.rating)) ||
				(termcount == 2 && (terms.tag && terms.rating)) ||
				(prefs.player_backend == 'mopidy' && prefs.searchcollectiononly) ||
				((terms.tag || terms.rating) && !(terms.genre || terms.composer || terms.performer || terms.any))) {
				// Use the sql search engine if we're looking only for things it supports
				debug.log("PLAYER","Searching using database search engine");
				st.terms = terms;
			} else {
				st.mpdsearch = terms;
			}
			$.ajax({
				type: "POST",
				url: "albums.php",
				data: st
			})
			.done(function(data) {
				$("#searchresultholder").html(data);
				collectionHelper.scootTheAlbums($("#searchresultholder"));
				uiHelper.doThingsAfterDisplayingListOfAlbums($("#searchresultholder"));
				data = null;
			});
		}
	}

	this.reSearch = function() {
		player.controller.search(lastsearchcmd);
	}

	this.rawsearch = function(terms, sources, exact, callback, checkdb) {
		if (player.updatingcollection) {
			infobar.notify(language.gettext('error_nosearchnow'));
			callback([]);
		}
		$.ajax({
			type: "POST",
			url: "albums.php",
			dataType: 'json',
			data: {
				rawterms: terms,
				domains: sources,
				command: exact ? "find" : "search",
				checkdb: checkdb
			}
		})
		.done(function(data) {
			callback(data);
			data = null;
		})
		.fail(function() {
			callback([]);
		});
	}

	this.postLoadActions = function() {
		if (thenowplayinghack) {
			// The Now PLaying Hack is so that when we switch the option for
			// 'display composer/performer in nowplaying', we can first reload the
			// playlist (to get the new artist metadata keys from the backend)
			// and then FORCE nowplaying to accept a new track with the same backendid
			// as the previous - this forces the nowplaying info to update
			thenowplayinghack = false;
			nowplaying.newTrack(playlist.getCurrentTrack(), true);
		}
	}

	this.doTheNowPlayingHack = function() {
		debug.log("MPD","Doing the nowplaying hack thing");
		thenowplayinghack = true;
		playlist.repopulate();
	}

	this.replayGain = function(event) {
		var x = $(event.target).attr("id").replace('replaygain_','');
		debug.log("MPD","Setting Replay Gain to",x);
		self.do_command_list([["replay_gain_mode",x]]);
	}

	this.addTracksToPlaylist = function(playlist,tracks,moveto,playlistlength) {
		debug.debug('PLAYER','Tracks is',tracks);
		debug.log("PLAYER","Adding tracks to playlist",playlist,"then moving to",moveto,"playlist length is",playlistlength);
		var cmds = new Array();
		for (var i in tracks) {
			if (tracks[i].uri) {
				debug.trace('PLAYER', 'Adding URI', tracks[i].uri);
				cmds.push(['playlistadd',decodeURIComponent(playlist),tracks[i].uri,
					moveto,playlistlength]);
			} else if (tracks[i].dir) {
				cmds.push(['playlistadddir',decodeURIComponent(playlist),tracks[i].dir,
					moveto,playlistlength]);
			}
		}
		self.do_command_list(cmds).then(
			function() {
				playlistManager.loadPlaylistIntoTarget(playlist);
			}
		);
	}

	this.movePlaylistTracks = function(playlist,from,to) {
		debug.log('CONTROLLER', 'Playlist Move',playlist,from, to);
		var cmds = new Array();
		cmds.push(['playlistmove',decodeURIComponent(playlist),from,to]);
		self.do_command_list(cmds).then(
			function() {
				playlistManager.loadPlaylistIntoTarget(playlist);
			}
		);
	}

}
