var metaHandlers = function() {

	function addedATrack(rdata,d2,d3) {
	    debug.log("ADD ALBUM","Success",rdata);
	    if (rdata) {
	        collectionHelper.updateCollectionDisplay(rdata);
	    }
	}

	function didntAddATrack(rdata) {
	    debug.error("ADD ALBUM","Failure",rdata,JSON.parse(rdata.responseText));
	    infobar.notify(infobar.ERROR,"Failed To Add Track!");
	}
	
	function getPostData(playlistinfo) {
	    var data = {};
	    if (playlistinfo.title) {
	        data.title = playlistinfo.title;
	    }
	    if (playlistinfo.trackartist) {
	        data.artist = playlistinfo.trackartist;
	    }
	    if (playlistinfo.tracknumber) {
	        data.trackno = playlistinfo.tracknumber;
	    }
	    if (playlistinfo.duration) {
	        data.duration = playlistinfo.duration;
	    } else {
	        data.duration = 0;
	    }
	    if (playlistinfo.disc) {
	        data.disc = playlistinfo.disc;
	    }
	    if (playlistinfo.albumartist
	        && playlistinfo.album != "SoundCloud"
	        && playlistinfo.type != "stream") {
	        data.albumartist = playlistinfo.albumartist;
	    } else {
	        if (playlistinfo.trackartist) {
	            data.albumartist = playlistinfo.trackartist;
	        }
	    }
	    if (playlistinfo.metadata.album.uri) {
	        data.albumuri = playlistinfo.metadata.album.uri;
	    }
	    if (playlistinfo.type != "stream" && playlistinfo.image) {
	        data.image = playlistinfo.image;
	    }
	    if ((playlistinfo.type == "local" || playlistinfo.type == "podcast") && playlistinfo.album) {
	        data.album = playlistinfo.album;
	    }
	    if (playlistinfo.type == "local" || playlistinfo.type == "podcast") {
	        if (playlistinfo.location.match(/api\.soundcloud\.com\/tracks\/(\d+)\//)
	            && prefs.player_backend == "mpd") {
	            var sc = playlistinfo.location.match(/api\.soundcloud\.com\/tracks\/(\d+)\//);
	            data.uri = "soundcloud://track/"+sc[1];
	        } else {
	            data.uri = playlistinfo.location;
	        }
	    }
	    if (playlistinfo.date) {
	        data.date = playlistinfo.date;
	    } else {
	        data.date = 0;
	    }
	    return data;
	}

	return {

		fromUiElement: {

			doMeta: function(action, name, attributes, fn) {
			    var tracks = new Array();
			    $.each($('.selected').filter(removeOpenItems), function (index, element) {
			        var uri = unescapeHtml(decodeURIComponent($(element).attr("name")));
			        debug.log("DROPPLUGIN","Dragged",uri,"to",name);
			        if ($(element).hasClass('directory')) {
			            tracks.push({
			                uri: decodeURIComponent($(element).children('input').first().attr('name')),
			                artist: 'geturisfordir',
			                title: 'dummy',
			                urionly: '1',
			                action: action,
			                attributes: attributes
			            });
			        } else if ($(element).hasClass('clickalbum')) {
			            tracks.push({
			                uri: uri,
			                artist: 'geturis',
			                title: 'dummy',
			                urionly: '1',
			                action: action,
			                attributes: attributes
			            });
			        } else if ($(element).hasClass('playlistalbum')) {
			            var tits = playlist.getAlbum($(element).attr('name'));
			            for (var i in tits) {
			                var t = getPostData(tits[i]);
			                t.urionly = 1;
			                t.action = action;
			                t.attributes = attributes;
			                tracks.push(t);
			            }
			            $(element).removeClass('selected');
			        } else if (element.hasAttribute('romprid')) {
			            var t = getPostData(playlist.getId($(element).attr('romprid')));
			            t.urionly = 1;
			            t.action = action;
			            t.attributes = attributes;
			            tracks.push(t);
			            $(element).removeClass('selected');
			        } else {
			            tracks.push({
			                uri: uri,
			                artist: 'dummy',
			                title: 'dummy',
			                urionly: '1',
			                action: action,
			                attributes: attributes
			            });
			        }
			    });
				dbQueue.request(tracks,
					function(rdata) {
			            collectionHelper.updateCollectionDisplay(rdata);
			            fn(name);
			        },
			        function(data) {
			            debug.warn("DROPPLUGIN","Failed to set attributes for",track,data);
			            infobar.notify(infobar.ERROR, "Failed To Set Attributes");
			        }
			    );
			},

			removeTrackFromDb: function(element) {
			    var trackDiv = element.parent();
			    if (!trackDiv.hasClass('clicktrack')) {
			        trackDiv = trackDiv.parent();
			    }
			    var trackToGo = trackDiv.attr("name");
			    debug.log("DB_TRACKS","Remove track from database",trackToGo);
			    trackDiv.fadeOut('fast');
			    dbQueue.request(
			        [{action: 'delete', uri: decodeURIComponent(trackToGo)}],
			        collectionHelper.updateCollectionDisplay,
			        function() {
			            infobar.notify(infobar.ERROR, "Failed to remove track!");
			        }
			    );
			}

		},

		fromSpotifyData: {

			addAlbumTracksToCollection: function(data, albumartist) {
			    var thisIsMessy = new Array();
			    if (data.tracks && data.tracks.items) {
			        debug.log("AAAGH","Adding Album From",data);
			        infobar.notify(infobar.NOTIFY, "Adding Album To Collection");
			        for (var i in data.tracks.items) {
			            var track = {};
			            track.title = data.tracks.items[i].name;
			            track.artist = joinartists(data.tracks.items[i].artists);
			            track.trackno = data.tracks.items[i].track_number;
			            track.duration = data.tracks.items[i].duration_ms/1000;
			            track.disc = data.tracks.items[i].disc_number;
			            track.albumartist = albumartist;
			            track.albumuri = data.uri;
			            if (data.images) {
			                for (var j in data.images) {
			                    if (data.images[j].url) {
			                        track.image = "getRemoteImage.php?url="+data.images[j].url;
			                        break;
			                    }
			                }
			            }
			            track.album = data.name;
			            track.uri = data.tracks.items[i].uri;
			            track.date = data.release_date;
			            track.action = 'add';
			            thisIsMessy.push(track);
			        }
			        if (thisIsMessy.length > 0) {
			        	dbQueue.request(thisIsMessy, addedATrack, didntAddATrack);
			        }
			    } else {
			        debug.fail("SPOTIFY","Failed to add album - no tracks",data);
			        infobar.notify(infobar.ERROR, "Failed To Add Album To Collection");
			    }
			}

		},

		fromPlaylistInfo: {

			getMeta: function(playlistinfo, success, fail) {
				var data = metaHandlers.fromPlaylistInfo.mapData(playlistinfo, 'get', false);
				dbQueue.request([data], success, fail);
			},

			setMeta: function(playlistinfo, action, attributes, success, fail) {
				var data = metaHandlers.fromPlaylistInfo.mapData(playlistinfo, action, attributes);
				dbQueue.request([data], success, fail);
			},

			mapData: function(playlistinfo, action, attributes) {
				var data = getPostData(playlistinfo);
				data.action = action;
				if (attributes) {
					data.attributes = attributes;
				}
				return data;
			}
		},

		genericAction: function(action, success, fail) {
			if (typeof action == "object") {
				dbQueue.request(action, success, fail);
			} else {
				dbQueue.request([{action: action}], success, fail);
			}
		}

	}

}();

var dbQueue = function() {

	// This is a queueing mechanism for the local database in order to avoid deadlocks.

	var queue = new Array();
	var throttle = null;
	var cleanuptimer = null;

	return {

		request: function(data, success, fail) {

			queue.push( {flag: false, data: data, success: success, fail: fail } );
			debug.trace("DB QUEUE","New request",data);
			if (throttle == null && queue.length == 1) {
				dbQueue.dorequest();
			}

		},

		queuelength: function() {
			return queue.length;
		},

		dorequest: function() {

			clearTimeout(throttle);
			clearTimeout(cleanuptimer);
			var req = queue[0];

            if (req) {
            	if (req.flag) {
            		debug.debug("DB QUEUE","Request just pulled from queue is already being handled");
            		return;
            	}
				queue[0].flag = true;
				debug.trace("DB QUEUE","Taking next request from queue",req);
			    $.ajax({
			        url: "backends/sql/userRatings.php",
			        type: "POST",
			        data: JSON.stringify(req.data),
			        dataType: 'json',
			        success: function(data) {
	                	req = queue.shift();
			        	debug.trace("DB QUEUE","Request Success",req,data);
			        	if (req.success) {
			        		req.success(data);
			        	}
			        	throttle = setTimeout(dbQueue.dorequest, 1);
			        },
			        error: function(data) {
	                	req = queue.shift();
			        	debug.fail("DB QUEUE","Request Failed",req,data.responseText);
			        	if (req.fail) {
			        		req.fail(data);
			        	}
			        	throttle = setTimeout(dbQueue.dorequest, 1);
			        }
			    });
	        } else {
            	throttle = null;
				cleanuptimer = setTimeout(dbQueue.doCleanup, 1000);
	        }
		},
		
		doCleanup: function() {
			debug.log("DB QUEUE", "Doing backend Cleanup");
			// We do these out-of-band to improve the responsiveness of the GUI.
			$.ajax({
				url: "backends/sql/userRatings.php",
				type: "POST",
				data: JSON.stringify([{action: 'cleanup'}]),
				dataType: 'json',
				success: function(data) {
					collectionHelper.updateCollectionDisplay(data);
				},
				error: function(data) {
					debug.fail("DB QUEUE","Cleanup Failed");
				}
			});
			
		},
		
		outstandingRequests: function() {
			return queue.length;
		}
	}
}();
