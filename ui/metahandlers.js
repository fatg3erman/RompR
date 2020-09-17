var metaHandlers = function() {

	function addedATrack(rdata,d2,d3) {
		debug.log("ADD ALBUM","Success");
		if (rdata) {
			collectionHelper.updateCollectionDisplay(rdata);
		}
	}

	function didntAddATrack(rdata) {
		debug.error("ADD ALBUM","Failure",rdata,JSON.parse(rdata.responseText));
		infobar.error(language.gettext('label_general_error'));
	}

	function getPostData(playlistinfo) {
		var data = {};
		if (playlistinfo.Title) {
			data.title = playlistinfo.Title;
		}
		if (playlistinfo.trackartist) {
			data.artist = playlistinfo.trackartist;
		}
		if (playlistinfo.Track) {
			data.trackno = playlistinfo.Track;
		}
		if (playlistinfo.Time) {
			data.duration = playlistinfo.Time;
		} else {
			data.duration = 0;
		}
		if (playlistinfo.type) {
			data.type = playlistinfo.type;
		}
		if (playlistinfo.Disc) {
			data.disc = playlistinfo.Disc;
		}
		if (playlistinfo.Genre) {
			data.genre = playlistinfo.Genre;
		}
		if (playlistinfo.albumartist
			&& playlistinfo.Album != "SoundCloud"
			&& playlistinfo.type != "stream") {
			data.albumartist = playlistinfo.albumartist;
		} else {
			if (playlistinfo.trackartist) {
				data.albumartist = playlistinfo.trackartist;
			}
		}
		if (playlistinfo.metadata && playlistinfo.metadata.album.uri) {
			data.albumuri = playlistinfo.metadata.album.uri;
		}
		if (playlistinfo.type != "stream" && playlistinfo.images && playlistinfo.images.small) {
			data.image = playlistinfo.images.small;
		}
		if ((playlistinfo.type == "local" || playlistinfo.type == "podcast") && playlistinfo.Album) {
			data.album = playlistinfo.Album;
		}
		if (playlistinfo.file) {
			if (playlistinfo.type == "local" || playlistinfo.type == "podcast") {
				if (playlistinfo.file.match(/api\.soundcloud\.com\/tracks\/(\d+)\//) && prefs.player_backend == "mpd") {
					var sc = playlistinfo.file.match(/api\.soundcloud\.com\/tracks\/(\d+)\//);
					data.uri = "soundcloud://track/"+sc[1];
				} else {
					data.uri = playlistinfo.file;
				}
			} else if (playlistinfo.type == "stream") {
				data.streamname = playlistinfo.Album;
				data.streamimage = playlistinfo.images.small;
				data.streamuri = playlistinfo.file;
			}
		}
		if (playlistinfo.year) {
			data.date = playlistinfo.year;
		} else {
			data.date = 0;
		}
		return data;
	}

	function youtubeDownloadMonitor() {
		var self = this;
		var notify = infobar.permnotify('Downloading Youtube Track');
		var timer;
		var running = true;

		this.checkProgress = function() {
			$.ajax( {
				type: "GET",
				url: "utils/checkyoutubedownload.php",
				cache: false,
				dataType: "json"
			})
			.done(function(data) {
				debug.debug("YOUTUBE DOWNLOAD","Download status is",data);
				if (data.info) {
					infobar.updatenotify(notify, 'Youtube Download : '+data.info);
				}
				if (running) {
					timer = setTimeout(self.checkProgress, 500);
				}
			})
			.fail(function() {
				debug.warn(language.gettext('error_dlpfail'));
				if (running) {
					timer = setTimeout(self.checkProgress, 1000);
				}
			});
		}

		this.stop = function() {
			running = false;
			clearTimeout(timer);
			infobar.removenotify(notify);
		}

		timer = setTimeout(self.checkProgress, 1000);
	}

	return {

		fromUiElement: {

			doMeta: function(action, name, attributes, fn) {
				var tracks = new Array();
				debug.debug('DROPPLUGIN', 'In doMeta');
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
					} else if ($(element).hasClass('playlisttrack') || $(element).hasClass('clickloadplaylist') || $(element).hasClass('clickloaduserplaylist')) {
						infobar.notify("Sorry, you can't add tracks from playlists");
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
				if (tracks.length > 0) {
					dbQueue.request(tracks,
						function(rdata) {
							collectionHelper.updateCollectionDisplay(rdata, function() {
								if (fn) fn(name);
							});
						},
						function(data) {
							debug.warn("DROPPLUGIN","Failed to set attributes for",data);
							infobar.error(language.gettext('label_general_error'));
						}
					);
				}
			},

			rateTrack: function(element, callback) {
				var value;
				switch (true) {
					case element.hasClass('rate_0'):
						value = 0;
						break;
					case element.hasClass('rate_1'):
						value = 1;
						break;
					case element.hasClass('rate_2'):
						value = 2;
						break;
					case element.hasClass('rate_3'):
						value = 3;
						break;
					case element.hasClass('rate_4'):
						value = 4;
						break;
					case element.hasClass('rate_5'):
						value = 5;
						break;
				}
				metaHandlers.fromUiElement.doMeta('set', 'Rating', [{attribute: 'Rating', value: value}], callback);
			},

			tagTrack: function(element, callback) {
				metaHandlers.fromUiElement.doMeta('set', 'Tag', [{attribute: 'Tags', value: [element.children('span').html()]}], callback);
			},

			untagTrack: function(element, callback) {
				metaHandlers.fromUiElement.doMeta('remove', 'Tag', [{attribute: 'Tags', value: [element.children('span').html()]}], callback);
			},

			tracksToPlaylist: function(event, element) {
				var playlist = element.attr('name');
				var tracks = new Array();
				$.each($('.selected').filter(removeOpenItems), function (index, element) {
					tracks.push({uri: decodeURIComponent($(this).attr('name'))});
				});
				playlistManager.addTracksToPlaylist(
					playlist,
					tracks
				);
			},

			removeTrackFromDb: function(event, element) {
				var trackstogo = new Array();
				$('.clicktrack.selected').each(function() {
					trackstogo.push({action: 'delete', uri: decodeURIComponent($(this).attr('name'))});
				});
				$('.clicktrack.selected').fadeOut('fast');
				debug.debug("DB_TRACKS","Remove tracks from database",trackstogo);
				dbQueue.request(
					trackstogo,
					collectionHelper.updateCollectionDisplay,
					function(data) {
						debug.warn("Failed to remove track! Possibly duplicate request?");
					}
				);
			},

			resetResumePosition: function(event, element) {
				metaHandlers.fromUiElement.doMeta('set', 'Progress', [{attribute: 'Progress', value: 0}]);
			},

			downloadYoutubeTrack: function(event, element) {
				var trackstogo = new Array();
				$('.clicktrack.selected').each(function() {
					var uri = decodeURIComponent($(this).attr('name'));
					$(this).find('.clicktrackmenu').makeSpinner();
					trackstogo.push({action: 'youtubedl', uri: uri });
				});
				var monitor = new youtubeDownloadMonitor();
				// Don't use the queue for this, it takes too long and holds up other stuff
				$.ajax({
					url: "backends/sql/userRatings.php",
					type: "POST",
					contentType: false,
					data: JSON.stringify(trackstogo),
					dataType: 'json'
				})
				.done(function(data) {
					collectionHelper.updateCollectionDisplay(data),
					monitor.stop();
				})
				.fail(function(data) {
					debug.warn("FUCK!", 'Why did that not work?',data);
					monitor.stop();
					if (data.responseJSON && data.responseJSON.error) {
						infobar.error('Failed to download YouTube track - '+data.responseJSON.error);
					}
				});
			},

			removeAlbumFromDb: function(event, element) {
				var albumToGo = element.attr("name");
				dbQueue.request(
					[{action: 'deletealbum', albumindex: albumToGo}],
					collectionHelper.updateCollectionDisplay,
					function(data) {
						debug.warn("Failed to remove album! Possibly duplicate request?");
					}
				);
			}
		},

		fromSpotifyData: {

			addAlbumTracksToCollection: function(data, albumartist) {
				debug.info('AAGH','Adding an album');
				var thisIsMessy = new Array();
				if (data.tracks && data.tracks.items) {
					debug.debug("AAAGH","Adding Album From",data);
					infobar.notify(language.gettext('label_addingalbum'));
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
									track.image = "getRemoteImage.php?url="+rawurlencode(data.images[j].url);
									break;
								}
							}
						}
						if (data.genres && data.genres.length > 0) {
							track.genre = data.genres[0];
						} else {
							track.genre = 'None';
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
					debug.warn("SPOTIFY","Failed to add album - no tracks",data);
					infobar.error(language.gettext('label_general_error'));
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

		fromLastFMData: {
				getMeta: function(data, success, fail) {
					var track = metaHandlers.fromLastFMData.mapData(data, 'get', false);
					dbQueue.request([track], success, fail);
				},

				setMeta: function(data, action, attributes, success, fail) {
					var tracks = [];
					data.forEach(function(track) {
						tracks.push(metaHandlers.fromLastFMData.mapData(track, action, attributes));
					});
					dbQueue.request(tracks, success, fail);
					return cloneObject(tracks);
				},

				mapData: function(data, action, attributes) {
					var track = {action: action};
					track.title = data.name;
					if (data.album) {
						track.album = data.album['#text'];
					}
					if (data.artist) {
						// We have in the past tried to be clever about this, since
						// mpdscribble and mopidy-scrobbler don't always join artist names
						// the same way we do. Unfortunately it just ain't possible to do.
						// This works well if you have last.fm autocorrect disabled on last.fm
						// and you use Rompr to scrobble and have autocorrect turned off there too.
						// Otherwise I'm afraid it can be a bit shit where there are multiple artist names
						// And it's fuckin shocking if you're playing back podcasts and not scrobbling from Rompr,
						// 'cos the metadata the players use is nothing like what comes out of the RSS which is what we use
						track.artist = data.artist.name;
						track.albumartist = track.artist;
					}
					if (data.date) {
						track.lastplayed = data.date.uts;
					}
					if (attributes) {
						track.attributes = attributes;
					}
					debug.debug("DBQUEUE", "LFM Mapped Data is",track);
					return track;
				}
		},

		genericAction: function(action, success, fail) {
			if (typeof action == "object") {
				dbQueue.request(action, success, fail);
			} else {
				dbQueue.request([{action: action}], success, fail);
			}
		},

		addToListenLater: function(album) {
			var data = {
				action: 'addtolistenlater',
				json: album
			}
			dbQueue.request(
				[data],
				function() {
					debug.log("METAHANDLERS","Album Added To Listen Later");
					infobar.notify(language.gettext('label_addedtolistenlater'));
					if (typeof(albumstolistento) != 'undefined') {
						albumstolistento.update();
					}
				},
				function() {
					debug.error("METAHANDLERS","Tailed To Add Album To Listen Later");
				}
			)
		},

		resetSyncCounts: function() {
			metaHandlers.genericAction('resetallsyncdata', metaHandlers.genericSuccess, metaHandlers.genericFail);
		},

		genericSuccess: function() {

		},

		genericFail: function() {

		}

	}

}();

var dbQueue = function() {

	// This is a queueing mechanism for the local database in order to avoid deadlocks.

	var queue = new Array();
	var throttle = null;
	var cleanuptimer = null;
	var cleanuprequired = false;

	// Cleanup cleans the database but it also updates the track stats
	var actions_requiring_cleanup = [
		'add', 'set', 'remove', 'amendalbum', 'deletetag', 'delete', 'deletewl', 'clearwishlist', 'setasaudiobook'
	];

	return {

		request: function(data, success, fail) {

			queue.push( {flag: false, data: data, success: success, fail: fail } );
			debug.debug("DB QUEUE","New request",data);
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

			if (req && player.updatingcollection) {
				debug.info("DB QUEUE","Deferring",req.data[0].action,"request because collection is being updated");
				throttle = setTimeout(dbQueue.dorequest, 1000);
			} else {
				if (req) {
					if (req.flag) {
						debug.warn("DB QUEUE","Request just pulled from queue is already being handled");
						return;
					}
					queue[0].flag = true;
					debug.debug("DB QUEUE","Taking next request from queue",req);
					$.ajax({
						url: "backends/sql/userRatings.php",
						type: "POST",
						contentType: false,
						data: JSON.stringify(req.data),
						dataType: 'json'
					})
					.done(function(data) {
						req = queue.shift();
						debug.debug("DB QUEUE","Request Success",req,data);
						for (var i in req.data) {
							if (actions_requiring_cleanup.indexOf(req.data[i].action) > -1) {
								debug.log("DB QUEUE","Setting cleanup flag for",req.data[i].action,"request");
								cleanuprequired = true;
							}
						}
						if (req.success) {
							req.success(data);
						}
						throttle = setTimeout(dbQueue.dorequest, 1);
					})
					.fail(function(data) {
						req = queue.shift();
						debug.warn("DB QUEUE","Request Failed",req,data);
						if (req.fail) {
							req.fail(data);
						}
						throttle = setTimeout(dbQueue.dorequest, 1);
					});
				} else {
					throttle = null;
					cleanuptimer = setTimeout(dbQueue.doCleanup, 1000);
				}
			}
		},

		doCleanup: function() {
			// We do these out-of-band to improve the responsiveness of the GUI.
			clearTimeout(cleanuptimer);
			if (cleanuprequired) {
				debug.info("DB QUEUE", "Doing backend Cleanup");
				dbQueue.request([{action: 'cleanup'}], dbQueue.cleanupComplete, dbQueue.cleanupFailed);
			}
		},

		cleanupComplete: function(data) {
			collectionHelper.updateCollectionDisplay(data);
			cleanuprequired = false;
		},

		cleanupFailed: function(data) {
			debug.warn("DB QUEUE","Cleanup Failed");
		}

	}
}();
