function trackDataCollection(currenttrack, nowplayingindex, artistindex, playlistinfo) {

	var self = this;
	var collections = new Array();
	this.playlistinfo = playlistinfo;
	this.currenttrack = currenttrack;
	this.nowplayingindex = nowplayingindex;
	this.artistindex = artistindex;
	this.populated = false;

	this.isCurrentTrack = function() {
		return nowplaying.isThisCurrent(self.currenttrack);
	}

	this.updateProgress = function(percent) {
		if (collections['soundcloud'] !== undefined) {
			collections['soundcloud'].progressUpdate(percent);
		}
	}

	function startSource(source) {
		debug.trace("TRACKDATA",self.nowplayingindex,"Starting collection",source);
		var requirements = (nowplaying.getPlugin(source)).getRequirements(self);
		for (var i in requirements) {
			if (collections[requirements[i]] === undefined) {
				debug.trace("TRACKDATA",self.nowplayingindex,"Starting collection",source,"requirement",requirements[i]);
				startSource(requirements[i]);
			}
		}
		collections[source] = new (nowplaying.getPlugin(source)).collection(
			self,
			self.playlistinfo.metadata.artists[self.artistindex],
			self.playlistinfo.metadata.album,
			self.playlistinfo.metadata.track
		);
		collections[source].populate();
	}

	this.doArtistChoices = function() {
		debug.log("TRACKDATA",self.nowplayingindex,"Doing Artist Choices",self.artistindex);
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
		debug.log("NOWPLAYING","Collection handling click in",source,panel);
		collections[source].handleClick(panel, element, event);
	}

	this.sendDataToBrowser = function(waitingon) {
		debug.log("TRACKDATA",self.nowplayingindex,"Telling",waitingon.source,"to start displaying");
		collections[waitingon.source].displayData();
	}

	this.stopDisplaying = function(waitingon) {
		for (var coll in collections) {
			debug.trace("TRACKDATA",self.nowplayingindex,"Telling",coll,"to stop displaying");
			collections[coll].stopDisplaying(waitingon);
		}
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

	this.addlfmtags = function(tags) {
		collections['lastfm'].track.addtags(tags);
	}

	this.remlfmtags = function(tags) {
		debug.log("NOWPLAYING","Removing Last.FM tags",tags);
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
		debug.mark("NOWPLAYING",self.nowplayingindex," is refreshing all user metadata");
		collections['ratings'].refresh();
	}

	// Create the data collections we need

	this.populate = function(source, isartistswitch) {
		self.populated = true;
		if (collections['ratings'] === undefined) startSource('ratings');
		// Last.FM needs to be running as it's used for love, ban, and autocorrect
		if (collections['lastfm'] === undefined && (prefs.lastfm_autocorrect || lastfm.isLoggedIn())) {
			startSource('lastfm');
		}
		if (collections[source] === undefined) startSource(source);
		browser.dataIsComing(
			self,
			isartistswitch,
			self.nowplayingindex,
			source,
			self.playlistinfo.trackartist,
			self.playlistinfo.metadata.artists[artistindex].name,
			(self.playlistinfo.albumartist && self.playlistinfo.albumartist != "") ? self.playlistinfo.albumartist : self.playlistinfo.trackartist,
			self.playlistinfo.metadata.album.name,
			self.playlistinfo.metadata.track.name
		);
	};

}

var nowplaying = function() {

	var history = new Array();
	var plugins = new Array();
	var to_notify = new Array();
    var currenttrack = 0;
    var nowplayingindex = 0;
    var currentbackendid = -2;
	var deferred = new Array();
	var deftimer = null;

    function findCurrentTrack() {
    	for (var i in history) {
    		if (history[i] !== undefined && history[i].currenttrack == currenttrack && history[i].populated == true) {
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

		notifyTrackChanges: function(name, callback) {
			to_notify[name] = callback;
		},

		newTrack: function(playlistinfo, force) {

			debug.debug("NOWPLAYING","New Info",playlistinfo);
			if (currentbackendid == playlistinfo.backendid && force !== true) {
				return;
			}
			infobar.setNowPlayingInfo(playlistinfo);
			if (playlistinfo.backendid == -1) {
				debug.log("NOWPLAYING","Empty Track");
				return;
			}
			currentbackendid = playlistinfo.backendid;
	        debug.mark("NOWPLAYING","New Track:",playlistinfo);
	        for (var i in to_notify) {
	        	if (to_notify[i] !== null) {
	        		debug.log("NOWPLAYING","Notifying",i);
	        		to_notify[i](playlistinfo);
	        	}
	        }

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
						if (history[j] 	!== undefined) {
							for (var k in history[j].playlistinfo.metadata.artists) {
								if (playlistinfo.metadata.artists[i].name == history[j].playlistinfo.metadata.artists[k].name) {
									debug.trace("NOWPLAYING","Using artist info from",j,k,"for",i);
									playlistinfo.metadata.artists[i] = history[j].playlistinfo.metadata.artists[k];
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
					if (history[j] !== undefined) {
			            var newalbumartist = (playlistinfo.albumartist == "") ? playlistinfo.trackartist : playlistinfo.albumartist;
			            var albumartist = (history[j].playlistinfo.albumartist == "") ? history[j].playlistinfo.trackartist : history[j].playlistinfo.albumartist;
			            if (newalbumartist == albumartist) {
			            	if (playlistinfo.metadata.album.name == history[j].playlistinfo.metadata.album.name) {
			            		if (!fa) {
				            		debug.trace("NOWPLAYING","Using album info from",j);
				            		playlistinfo.metadata.album = history[j].playlistinfo.metadata.album;
				            		fa = true;
				            	}
			            		if (!ft && playlistinfo.metadata.track.name == history[j].playlistinfo.metadata.track.name &&
			            			playlistinfo.tracknumber == history[j].playlistinfo.tracknumber) {
				            		debug.trace("NOWPLAYING","Using track info from",j);
			            			playlistinfo.metadata.track = history[j].playlistinfo.metadata.track;
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
	        // isartistswitch makes sure the browser switches away from an artist if that artist is not present on this track.
	        // It gets set to false only if this track contains the currently displayed artist. In that case that's also the
	        // data collection we tell to populate.
	        var isartistswitch = browser.areweatfront();
	        for (var i in playlistinfo.metadata.artists) {
	        	nowplayingindex++;
	        	playlistinfo.metadata.artists[i].nowplayingindex = nowplayingindex;
	        	debug.log("NOWPLAYING","Setting Artist",playlistinfo.metadata.artists[i].name,"index",i,"to nowplayingindex",nowplayingindex);
				history[nowplayingindex] = new trackDataCollection(currenttrack, nowplayingindex, i, playlistinfo);
				// IF there are multiple artists we will be creating multiple trackdatacollections.
				// BUT we only tell the first one (or the one that's the current displayed artist) to populate - this prevents the others from trying to
				// populate the album and track info which is shared between them. However we must wait until
				// we've initialised all the metadata before we start to populate the first artist, otherwise
				// there's danger of a race resulting in the artistchooser being populated before all the nowplayingindices
				// have been assigned, resulting in the html containing an undefined value.
				if (i == 0) to_populate = nowplayingindex;
				if (isCurrentDisplayedArtist(playlistinfo.metadata.artists[i].name)) {
					debug.log("NOWPLAYING","Telling nowplaying index",nowplayingindex,"to populate");
					to_populate = nowplayingindex;
					isartistswitch = false;
				}
			}
			history[to_populate].populate(prefs.infosource, isartistswitch);
		},

		remove: function(npindex) {
			// Browser has truncated its history, so we no longer need to hold on to
			// an item. Rather than splice the array and go through a whole lot of rigmarole,
			// just set the entry to undefined. This will permit garbage collection in the
			// browser to tidy it up.
			history[npindex] = undefined;
		},

		switchArtist: function(source, npindex) {
			debug.log("NOWPLAYING","Asked to switch artist for nowplayingindex",npindex);
			if (history[npindex] !== undefined) {
				history[npindex].populate(source, true);
			} else {
				infobar.notify(infobar.NOTIFY, "Browser history has been truncated - artist cannot be displayed");
			}
		},

		setLastFMCorrections: function(index, updates) {
			debug.log("NOWPLAYING","Recieved last.fm corrections for index",index);
			if (index == currenttrack) {
		    	var t = history[findCurrentTrack()].playlistinfo.location;
            	if (t.substring(0,11) == 'soundcloud:') {
            		debug.log("NOWPLAYING","Not sending LastFM Updates because this track is from soundcloud");
            	} else {
					infobar.setLastFMCorrections(updates);
				}
			}
		},

		progressUpdate: function(percent) {
			if (currenttrack > 0) {
				history[findCurrentTrack()].updateProgress(percent);
			}
		},

        setRating: function(evt) {
        	if (typeof evt == "number") {
        		debug.trace("NOWPLAYING","Button Press Rating Set",evt);
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
				history[index].setMeta('set', 'Rating', rating.toString());
				if (prefs.synclove && lastfm.isLoggedIn() && rating >= prefs.synclovevalue) {
					history[index].love();
					if (index == currenttrack) {
		            	$("#love").makeFlasher({flashtime:2, repeats: 1});
		            }
				}
			}
        },

        addTrackToCollection: function(evt, index) {
        	history[index].setMeta('set', 'Rating', '0');
        },

		addTags: function(index, tags) {
			if (!index) index = findCurrentTrack();
            var tagarr = tags.split(',');
			if (index > 0) {
	            debug.log("NOWPLAYING", "Adding tags",tags,"to index",index);
				history[index].setMeta('set', 'Tags', tagarr);
				if (lastfm.isLoggedIn() && prefs.synctags) {
					history[index].addlfmtags(tags);
				}
			}
		},

		removeTag: function(event, index) {
			if (!index) index = findCurrentTrack();
            var tag = $(event.target).parent().text();
            tag = tag.replace(/x$/,'');
			if (index > 0) {
	            debug.log("NOWPLAYING", "Removing tag",tag,"from index",index);
				history[index].setMeta('remove', 'Tags', tag);
				if (lastfm.isLoggedIn() && prefs.synctags) {
					history[index].remlfmtags(tag);
				}
			}
		},

		incPlaycount: function(index) {
			if (!index) index = findCurrentTrack();
			if (history[index].playlistinfo.metadata.track && history[index].playlistinfo.metadata.track.usermeta) {
				var p = parseInt(history[index].getMeta("Playcount"));
				history[index].setMeta('inc', 'Playcount', p+1);
			} else {
				clearTimeout(deftimer);
				debug.warn("NOWPLAYING","Trying to incremment Playcount on index",index,"before metadata has populated. Deferring request");
				deferred.push({action: nowplaying.incPlaycount, index: index});
				deftimer = setTimeout(nowplaying.doDeferredRequests, 1000);
			}
		},

		love: function() {
			if (lastfm.isLoggedIn()) {
				history[findCurrentTrack()].love();
	            $("#love").makeFlasher({flashtime:2, repeats: 1});
			}
			if (prefs.synclove) {
				history[findCurrentTrack()].setMeta('set', 'Rating', prefs.synclovevalue);
			}
		},

		isThisCurrent: function(index) {
			return (index == currenttrack);
		},

		updateAlbumMBID: function(index, mbid) {
			history[index].setAlbumMBID(mbid);
		},

		refreshUserMeta: function() {
			for (var i in history) {
				history[i].refreshUserMeta();
			}
		},

		doDeferredRequests: function() {
			clearTimeout(deftimer);
			if (deferred.length > 0) {
				var req = deferred.shift();
				debug.shout("NOWPLAYING", "Doing Deferred Request On",req.index);
				req.action(req.index);
			}
			if (deferred.length > 0) {
				deftimer = setTimeout(nowplaying.doDeferredRequests, 1000);
			}
		},

		getCurrentCollection(index) {
			if (!index) index = findCurrentTrack();
			debug.log("NOWPLAYING", history[index]);
		}

	}

}();
