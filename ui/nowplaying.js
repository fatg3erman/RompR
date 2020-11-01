function trackDataCollection(currenttrack, nowplayingindex, artistindex, playlistinfo) {

	var self = this;
	var collections = new Array();
	this.playlistinfo = playlistinfo;
	this.currenttrack = currenttrack;
	this.nowplayingindex = nowplayingindex;
	this.artistindex = artistindex;
	this.populated = false;
	this.hasbeenstarted = new Array();
	this.special_layouts = new Array();

	this.isCurrentTrack = function() {
		return nowplaying.isThisCurrent(self.currenttrack);
	}

	this.updateProgress = function(percent) {
		if (collections['soundcloud'] !== undefined) {
			collections['soundcloud'].progressUpdate(percent);
		}
	}

	function startSource(source) {
		debug.log("TRACKDATA",self.nowplayingindex,"Starting collection",source);
		// Prevent infinite recursion when plugins depend on each other
		if (self.hasbeenstarted.indexOf(source) > -1) {
			debug.trace('TRACKDATA',source,'has already been started');
			return;
		}
		self.hasbeenstarted.push(source);
		var requirements = (nowplaying.getPlugin(source)).getRequirements(self);
		for (var i in requirements) {
			debug.debug("TRACKDATA",self.nowplayingindex,"Starting collection",source,"requirement",requirements[i]);
			startSource(requirements[i]);
		}
		collections[source] = new (nowplaying.getPlugin(source)).collection(
			self,
			self.playlistinfo.metadata.artists[self.artistindex],
			self.playlistinfo.metadata.album,
			self.playlistinfo.metadata.track
		);
		collections[source].populate();
	}

	this.add_special_layout = function(layout) {
		self.special_layouts.push(layout);
		return self.special_layouts.length - 1;
	}

	this.get_special = function(index) {
		return self.special_layouts[index];
	}

	this.get_a_thing = function(type, thing) {
		switch (type) {
			case 'track':
			case 'album':
				return self.playlistinfo.metadata[type][thing];

			case 'artist':
				return self.playlistinfo.metadata.artists[self.artistindex][thing];
		}
	}

	this.get_source_thing = function(type, source, thing) {
		switch (type) {
			case 'track':
			case 'album':
				return self.playlistinfo.metadata[type][source][thing];

			case 'artist':
				return self.playlistinfo.metadata.artists[self.artistindex][source][thing];
		}
	}

	this.re_display= function(source) {
		if (collections[source]) {
			collections[source].re_display();
		}
	}

	this.get_random_discogs_artist_image = async function(layout) {
		// The problem with this is it populates the discogs collection which does its masonry layout
		// while the panel is not visible, which fucks up the masonry layout
		// Need to mend browser.rePoint before we do this
		// if (typeof collections['discogs'] == 'undefined') {
		// 	startSource('discogs');
		// }
		// debug.mark('TRACKDATA', "Getting random discogs image");
		// var image = await collections['discogs'].artist.get_random_image();
		// debug.mark('TRACKDATA', "Got random discogs image", image);
		// if (image)
		// 	layout.add_main_image(image);
	}

	this.doArtistChoices = function() {
		debug.debug("TRACKDATA",self.nowplayingindex,"Doing Artist Choices",self.artistindex);
		// Need to put the html in the div even if we're hiding it. because nowplaying uses it
		// to see which artist is currently being displayed
		var htmlarr = new Array();;
		for (var i in playlistinfo.metadata.artists) {
			var html = '<span class="infoclick clickartistchoose bleft';
			if (playlistinfo.metadata.artists[i].nowplayingindex == self.nowplayingindex) {
				html += ' bsel'
			}
			html += '">'+playlistinfo.metadata.artists[i].name+'</span>'+
			'<input type="hidden" value="'+playlistinfo.metadata.artists[i].nowplayingindex+'" />';
			htmlarr.push(html);
		}
		$("#artistchooser").html(htmlarr.join('&nbsp;<font color="#ff4800">|</font>&nbsp;'));
		if (playlistinfo.metadata.artists.length > 1) {
			$("#artistchooser").slideDown('fast');
		} else {
			if ($("#artistchooser").is(':visible')) {
				$("#artistchooser").slideUp('fast');
			}
		}
	}

	this.handleClick = function(source, panel, element, event) {
		debug.debug("NOWPLAYING","Collection handling click in",self.source,panel);
		collections[source].handleClick(panel, element, event);
	}

	this.updateData = function(data, start) {
		if (start === undefined || start === null) {
			start = self.playlistinfo;
		}
		for (var i in data) {
			if (typeof data[i] == "object" && data[i] !== null) {
				if (start[i] === undefined) {
					start[i] = {};
				}
				self.updateData(data[i], start[i]);
			} else {
				if (start[i] === undefined || start[i] == "" || start[i] == null) {
					start[i] = data[i];
				}
			}
		}
	}

	this.love = function() {
		if (collections['lastfm'] === undefined) {
			debug.error("TRACKDATA","Asked to Love but there is no lastfm collection!");
		} else {
			collections['lastfm'].track.love();
		}
	}

	this.unlove = function() {
		if (collections['lastfm'] === undefined) {
			debug.error("TRACKDATA","Asked to Love but there is no lastfm collection!");
		} else {
			collections['lastfm'].track.unlove();
		}
	}

	this.unloveifloved = function() {
		if (collections['lastfm'] === undefined) {
			debug.error("TRACKDATA","Asked to Love but there is no lastfm collection!");
		} else {
			collections['lastfm'].track.unloveifloved();
		}
	}

	this.addlfmtags = function(tags) {
		collections['lastfm'].track.addtags(tags);
	}

	this.remlfmtags = function(tags) {
		debug.debug("NOWPLAYING","Removing Last.FM tags",tags);
		collections['lastfm'].track.removetags(tags);
	}

	this.setMeta = function(action, type, value) {
		collections['ratings'].setMeta(action, type, value);
	}

	this.setAlbumMBID = function(mbid) {
		collections['ratings'].setAlbumMBID(mbid);
	}

	this.getMeta = function(meta) {
		return collections['ratings'].getMeta(meta);
	}

	this.refreshUserMeta = function() {
		debug.info("NOWPLAYING",self.nowplayingindex," is refreshing all user metadata");
		collections['ratings'].refresh();
	}

	this.lastFMMetadata = function(updates) {
		debug.trace("NOWPLAYING",self.nowplayingindex," got updates from Last.FM",JSON.stringify(updates));
		collections['ratings'].updateMeta(updates);
	}

	this.getSource = function() {
		return self.source;
	}

	// Create the data collections we need

	this.populate = function(source, browser_showing_current, force) {
		self.populated = true;
		if (collections['ratings'] === undefined)
			startSource('ratings');
		// Last.FM needs to be running as it's used for love, ban, and autocorrect
		if (collections['lastfm'] === undefined && (prefs.lastfm_autocorrect || lastfm.isLoggedIn()))
			startSource('lastfm');

		if (collections[source] === undefined)
			startSource(source);

		browser.dataIsComing(null, {nowplayingindex: self.nowplayingindex, source: source, special: {}}, browser_showing_current, force, false, false);
	};
}

var nowplaying = function() {

	var tracks_played = new Array();
	var plugins = new Array();
	var to_notify = new Array();
	var currenttrack = 0;
	var nowplayingindex = 0;
	var currentbackendid = -2;
	var deferred = new Array();
	var deftimer = null;

	function findCurrentTrack() {
		for (var i in tracks_played) {
			if (tracks_played[i] !== undefined && tracks_played[i].currenttrack == currenttrack && tracks_played[i].populated == true) {
				return i;
			}
		}
		debug.error("NOWPLAYING","Failed to find current track!");
	}

	function isCurrentDisplayedArtist(name) {
		if (name == unescapeHtml($("#artistchooser").find(".bsel").html())) {
			return true;
		}
		return false;
	}

	return {

		registerPlugin: function(name, fn, icon, text) {
			debug.log("NOWPLAYING", "Plugin is regsistering - ",name);
			plugins[name] = { 	createfunction: fn,
								icon: icon,
								text: text
							};
		},

		getPlugin: function(name) {
			if (plugins[name]) {
				return plugins[name].createfunction;
			} else {
				debug.warn("NOWPLAYING","Something asked for nonexistent plugin",name);
				// Return something just to stop whatever asked for this from crashing.
				// We might get here if, as I just did, I ran it connected to Mopidy with SoundCloud plugin
				// as prefs.infosource, then connected to mpd, where soundcloud plugin is not.
				return plugins.file.createfunction;
			}
		},

		getAllPlugins: function() {
			return plugins;
		},

		newTrack: function(playlistinfo, force, source) {

			debug.debug("NOWPLAYING","New Info",playlistinfo);
			if (currentbackendid == playlistinfo.Id && force !== true) {
				return;
			}
			infobar.setNowPlayingInfo(playlistinfo);
			if (playlistinfo.Id== -1) {
				debug.log("NOWPLAYING","Empty Track");
				return;
			}
			currentbackendid = playlistinfo.Id;
			debug.debug("NOWPLAYING","New Track:",playlistinfo);

			// Repeatedly querying online databases for the same data is a bad idea -
			// it's slow, it means we have to store the same data multiple times,
			// and some databases (musicbrainz) will start to severely rate-limit you
			// if you do it. Hence we see if we've got stuff we can copy.
			// (Note Javascript is a by-reference language, so all we're copying is
			// references to one pot of data).

			// See if we can copy artist data
			for (var i in playlistinfo.metadata.artists) {
				acheck: {
					for (var j = nowplayingindex; j > 0; j--) {
						if (tracks_played[j] !== undefined) {
							for (var k in tracks_played[j].playlistinfo.metadata.artists) {
								if (!tracks_played[j].playlistinfo.metadata.artists[k].special &&
									playlistinfo.metadata.artists[i].name == tracks_played[j].playlistinfo.metadata.artists[k].name) {
									debug.debug("NOWPLAYING","Using artist info from",j,k,"for",i);
									playlistinfo.metadata.artists[i] = tracks_played[j].playlistinfo.metadata.artists[k];
									break acheck;
								}
							}
						}
					}
				}
			}

			// See if we can copy album and track data
			var fa = false;
			var ft = false;
			tcheck: {
				for (var j = nowplayingindex; j > 0; j--) {
					if (tracks_played[j] !== undefined) {
						var newalbumartist = (playlistinfo.albumartist == "") ? playlistinfo.trackartist : playlistinfo.albumartist;
						var albumartist = (tracks_played[j].playlistinfo.albumartist == "") ? tracks_played[j].playlistinfo.trackartist : tracks_played[j].playlistinfo.albumartist;
						if (newalbumartist == albumartist) {
							if (!tracks_played[j].playlistinfo.metadata.album.special &&
								playlistinfo.metadata.album.name == tracks_played[j].playlistinfo.metadata.album.name) {
								if (!fa) {
									debug.debug("NOWPLAYING","Using album info from",j);
									playlistinfo.metadata.album = tracks_played[j].playlistinfo.metadata.album;
									fa = true;
								}
								if (!ft && !tracks_played[j].playlistinfo.metadata.track.special &&
									playlistinfo.metadata.track.name == tracks_played[j].playlistinfo.metadata.track.name &&
									playlistinfo.Title == tracks_played[j].playlistinfo.Title) {
									debug.debug("NOWPLAYING","Using track info from",j);
									playlistinfo.metadata.track = tracks_played[j].playlistinfo.metadata.track;
									ft = true;
								}
								if (fa && ft) {
									break tcheck;
								}
							}
						}
					}
				}
			}

			currenttrack++;
			var to_populate = null;
			force_artist = true;
			for (var i in playlistinfo.metadata.artists) {
				nowplayingindex++;
				playlistinfo.metadata.artists[i].nowplayingindex = nowplayingindex;
				debug.debug("NOWPLAYING","Setting Artist",playlistinfo.metadata.artists[i].name,"index",i,"to nowplayingindex",nowplayingindex);
				tracks_played[nowplayingindex] = new trackDataCollection(currenttrack, nowplayingindex, i, playlistinfo);
				// IF there are multiple artists we will be creating multiple trackdatacollections.
				// BUT we only tell the first one (or the one that's the current displayed artist) to populate - this prevents the others from trying to
				// populate the album and track info which is shared between them. However we must wait until
				// we've initialised all the metadata before we start to populate the first artist, otherwise
				// there's danger of a race resulting in the artistchooser being populated before all the nowplayingindices
				// have been assigned, resulting in the html containing an undefined value.
				if (i == 0) to_populate = nowplayingindex;
				if (isCurrentDisplayedArtist(playlistinfo.metadata.artists[i].name)) {
					debug.debug("NOWPLAYING","Telling nowplaying index",nowplayingindex,"to populate");
					to_populate = nowplayingindex;
					force_artist = false;
				}
			}
			// backendid will not change if we're listening to the radio
			var force_track = (playlistinfo.type == 'stream');
			tracks_played[to_populate].populate(source ? source : prefs.infosource, browser.is_displaying_current_track(), {artist: force_artist, album: false, track: force_track});
		},

		switchArtist: function(index, artistindex) {
			debug.log("NOWPLAYING","Asked to switch artist for nowplayingindex",index,artistindex);
			if (tracks_played[artistindex] !== undefined) {
				tracks_played[artistindex].populate(prefs.infosource, true, {artist: true, album: false, track: false});
			} else {
				infobar.notify(language.gettext('error_truncated'));
			}
		},

		setLastFMCorrections: function(index, updates) {
			debug.debug("NOWPLAYING","Recieved last.fm corrections for index",index);
			if (index == currenttrack) {
				var t = tracks_played[findCurrentTrack()].playlistinfo.file;
				if (t.substring(0,11) == 'soundcloud:') {
					debug.debug("NOWPLAYING","Not sending LastFM Updates because this track is from soundcloud");
				} else {
					infobar.setLastFMCorrections(updates);
				}
			}
		},

		setMetadataFromLastFM: function(index, updates) {
			tracks_played[index].lastFMMetadata(updates);
		},

		progressUpdate: function(percent) {
			if (currenttrack > 0) {
				tracks_played[findCurrentTrack()].updateProgress(percent);
			}
		},

		setRating: function(evt) {
			if (typeof evt == "number") {
				var rating = evt;
				var index = findCurrentTrack();
			} else {
				var elem = $(evt.target);
				var rating = ratingCalc(elem, evt);
				var index = elem.next().val();
				if (index == -1) index = findCurrentTrack();
				displayRating(evt.target, rating);
			}
			if (index > 0) {
				debug.log("NOWPLAYING", "Setting Rating to",rating,"on index",index);
				tracks_played[index].setMeta('set', 'Rating', rating.toString());
				if (prefs.synclove && lastfm.isLoggedIn()) {
					if (rating >= prefs.synclovevalue) {
						tracks_played[index].love();
					} else {
						tracks_played[index].unloveifloved();
					}
				}
			}
		},

		storePlaybackProgress: function(progress, index) {
			if (index === null) {
				index = findCurrentTrack();
			}
			debug.log("NOWPLAYING","Setting Playback Progress on",index,"to", progress);
			tracks_played[index].setMeta('set', 'Progress', progress);
		},

		addTrackToCollection: function(evt, index) {
			tracks_played[index].setMeta('set', 'Rating', '0');
		},

		addTags: function(index, tags) {
			if (!index) index = findCurrentTrack();
			var tagarr = tags.split(',');
			if (index > 0) {
				debug.log("NOWPLAYING", "Adding tags",tags,"to index",index);
				tracks_played[index].setMeta('set', 'Tags', tagarr);
				if (lastfm.isLoggedIn() && prefs.synctags) {
					tracks_played[index].addlfmtags(tags);
				}
			}
		},

		removeTag: function(event, index) {
			if (!index) index = findCurrentTrack();
			var tag = $(event.target).parent().text();
			tag = tag.replace(/x$/,'');
			if (index > 0) {
				debug.log("NOWPLAYING", "Removing tag",tag,"from index",index);
				tracks_played[index].setMeta('remove', 'Tags', tag);
				if (lastfm.isLoggedIn() && prefs.synctags) {
					tracks_played[index].remlfmtags(tag);
				}
			}
		},

		incPlaycount: function(index) {
			if (!index) index = findCurrentTrack();
			if (tracks_played[index].playlistinfo.metadata.track && tracks_played[index].playlistinfo.metadata.track.usermeta) {
				var p = parseInt(tracks_played[index].getMeta("Playcount"));
				tracks_played[index].setMeta('inc', 'Playcount', p+1);
			} else {
				clearTimeout(deftimer);
				debug.warn("NOWPLAYING","Trying to incremment Playcount on index",index,"before metadata has populated. Deferring request");
				deferred.push({action: nowplaying.incPlaycount, index: index});
				deftimer = setTimeout(nowplaying.doDeferredRequests, 1000);
			}
		},

		love: function() {
			if (lastfm.isLoggedIn()) {
				tracks_played[findCurrentTrack()].love();
				if (prefs.synclove) {
					tracks_played[findCurrentTrack()].setMeta('set', 'Rating', prefs.synclovevalue);
				}
			}
		},

		unlove: function() {
			if (lastfm.isLoggedIn()) {
				tracks_played[findCurrentTrack()].unlove();
				if (prefs.synclove) {
					tracks_played[findCurrentTrack()].setMeta('set', 'Rating', 0);
				}
			}
		},

		isThisCurrent: function(index) {
			return (index == currenttrack);
		},

		updateAlbumMBID: function(index, mbid) {
			tracks_played[index].setAlbumMBID(mbid);
		},

		refreshUserMeta: function() {
			for (var i in tracks_played) {
				tracks_played[i].refreshUserMeta();
			}
		},

		doDeferredRequests: function() {
			clearTimeout(deftimer);
			if (deferred.length > 0) {
				var req = deferred.shift();
				debug.log("NOWPLAYING", "Doing Deferred Request On",req.index);
				req.action(req.index);
			}
			if (deferred.length > 0) {
				deftimer = setTimeout(nowplaying.doDeferredRequests, 1000);
			}
		},

		getCurrentCollection: function(index) {
			if (!index) index = findCurrentTrack();
			debug.debug("NOWPLAYING", tracks_played[index]);
		},

		compare_ids: function(type, theirs, ours) {
			return tracks_played[theirs].get_a_thing(type, 'backendid') != tracks_played[ours].get_a_thing(type, 'backendid');
		},

		getLayout: function(type, source, index, special_index) {
			debug.log('NOWPLAYING', "Getting layout", type, source, index, special_index);
			if (special_index >= 0) {
				return tracks_played[index].get_special(special_index).get_contents();
			} else {
				return tracks_played[index].get_source_thing(type, source, 'layout').get_contents();
			}
		},

		detachLayout: function(type, source, index, special_index) {
			debug.log('NOWPLAYING', 'Detaching', type, source, index, special_index);
			if (special_index >= 0) {
				return tracks_played[index].get_special(special_index).detach_contents();
			} else if (index in tracks_played) {
				return tracks_played[index].get_source_thing(type, source, 'layout').detach_contents();
			}
		},

		getTitle: function(type, source, index, special_index) {
			if (special_index >= 0) {
				return tracks_played[index].get_special(special_index).get_title();
			} else {
				return tracks_played[index].get_source_thing(type, source, 'layout').get_title();
			}
		},

		doArtistChoices: function(index) {
			tracks_played[index].doArtistChoices();
		},

		reDo: function(index, source) {
			tracks_played[index].re_display(source);
		},

		handleClick: function(index, source, panel, element, event) {
			tracks_played[index].handleClick(source, panel, element, event);
		},

		switch_source: function(index, source) {
			tracks_played[index].populate(source, true, {artist: false, album: false, track: false});
		},

		special_update: function(source, panel, layout) {
			debug.log('NOWPLAYING', 'Special Update', source, panel);
			var index = browser.get_current_displayed_track();
			var special_index = tracks_played[index].add_special_layout(layout);
			var force = {artist: false, album: false, track: false};
			force[panel] = true;
			var history = {nowplayingindex: index, source: source, special: {}};
			history.special[panel] = special_index;
			browser.dataIsComing(null,
				history,
				true, force, panel, true
			);
		}

	}

}();
