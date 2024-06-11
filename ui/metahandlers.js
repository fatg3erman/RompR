var metaHandlers = function() {

	function addedATrack(rdata,d2,d3) {
		debug.log("ADD ALBUM","Success");
		if (rdata)
			collectionHelper.updateCollectionDisplay(rdata);

	}

	function didntAddATrack(rdata) {
		debug.error("ADD ALBUM","Failure",rdata,JSON.parse(rdata.responseText));
		infobar.error(language.gettext('label_general_error'));
	}

	function getPostData(playlistinfo) {
		var data = {
			albumartist: (playlistinfo.Album == 'SoundCloud' || playlistinfo.type == 'stream') ? playlistinfo.trackartist : playlistinfo.albumartist,
			metadata: null,
			Album: (playlistinfo.type == 'local' || playlistinfo.type == 'podcast' || playlistinfo.type == 'audiobook') ? playlistinfo.Album : null,
			// These are only used in the case where we're adding a track to the wishlist
			streamname: playlistinfo.Album,
			streamimage: playlistinfo['X-AlbumImage'],
		}
		if (prefs.player_backend == 'mpd' && playlistinfo.file && playlistinfo.file.match(/api\.soundcloud\.com\/tracks\/(\d+)\//)) {
			data.file = 'soundcloud://track/'+playlistinfo.file.match(/api\.soundcloud\.com\/tracks\/(\d+)\//)[1];
		}
		if (playlistinfo.type == 'stream') {
			data.streamuri = playlistinfo.file;
			data.file = null;
		}
		return {...playlistinfo, ...data};
	}

	function youtubeDownloadMonitor(uri, track_name, trackui) {
		var self = this;
		var notify = infobar.permnotify('Downloading Youtube Track '+track_name);
		var timer;
		var running = true;
		var key = hex_md5(uri);

		this.checkProgress = function() {
			fetch(
				"utils/checkyoutubedownload.php?key="+key,
				{
					cache: 'no-store',
					priority: 'low'
				}
			)
			.then(response => {
				if (response.ok) {
					return response.json();
				} else {
					throw new Error(response.statusText)
				}
			})
			.then(data => {
				if (data.info) {
					infobar.updatenotify(notify, 'Youtube Download : '+track_name+'<br />'+data.info);
				} else if (data.result) {
					debug.log('YOUTUBE DOWNLOAD', 'Result is',data.result);
					infobar.updatenotify(notify, 'Youtube Download : '+track_name+'<br />Success');
					self.stop();
					collectionHelper.updateCollectionDisplay(data.result);
				} else if (data.error) {
					debug.log('YOUTUBE DOWNLOAD', 'Error is',data.error);
					infobar.updatenotify(notify, 'Youtube Download : '+track_name+'<br />'+data.error);
					self.stop();
				}
				if (running) {
					timer = setTimeout(self.checkProgress, 1000);
				}
			})
			.catch(err => {
				debug.warn('YOUTUBEDL', err);
				infobar.error(language.gettext('error_dlpfail')+'<br />'+err.message);
				self.stop();
			});
		}

		this.un_notify = function() {
			infobar.removenotify(notify);
		}

		this.stop = function() {
			debug.log('YOUTUBEDL', 'Stopping Monitor');
			running = false;
			clearTimeout(timer);
			trackui.find('.clicktrackmenu').stopSpinner();
			setTimeout(self.un_notify, 2500);
		}

		timer = setTimeout(self.checkProgress, 1000);
	}

	return {

		fromUiElement: {

			doMeta: function(action, name, attributes, fn) {
				var tracks = new Array();
				debug.debug('DROPPLUGIN', 'In doMeta');
				$.each($('.selected').filter(removeOpenItems), function (index, element) {
					tracks.push({
						file: unescapeHtml(decodeURIComponent($(element).attr("name"))),
						trackartist: 'dummy',
						Title: 'dummy',
						urionly: '1',
						action: action,
						attributes: attributes
					});
				});
				if (tracks.length > 0) {
					dbQueue.request(tracks,
						function(rdata) {
							collectionHelper.updateCollectionDisplay(rdata, function() {
								if (fn) fn(name);
							});
						},
						metaHandlers.genericFailPopup
					);
				}
			},

			uiWakeup: function() {
				var albumids = new Array();
				$('[id^="aalbum"]').each(function() {
					albumids.push($(this).prop('id'));
				});
				$('[id^="balbum"]').each(function() {
					albumids.push($(this).prop('id'));
				});
				$('[id^="zalbum"]').each(function() {
					albumids.push($(this).prop('id'));
				});
				if (albumids.length > 0) {
					debug.log('WAKEUP', 'UI refreshing open albums', albumids);
					dbQueue.request(
						[{action: 'ui_wakeup_refresh', albums: albumids}],
						function(rdata) {
							collectionHelper.updateCollectionDisplay(rdata);
						},
						metaHandlers.genericFail
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
					trackstogo.push({action: 'delete', file: decodeURIComponent($(this).attr('name'))});
				});
				$('.clicktrack.selected').fadeOut('fast');
				debug.debug("DB_TRACKS","Remove tracks from database",trackstogo);
				dbQueue.request(
					trackstogo,
					collectionHelper.updateCollectionDisplay,
					metaHandlers.genericFailPopup
				);
			},

			resetResumePosition: function(element, callback) {
				metaHandlers.fromUiElement.doMeta('set', 'Bookmark', [{attribute: 'Bookmark', value: [0, element.next().val()]}], callback);
			},

			downloadYoutubeTrack: async function(event, element) {
				debug.log('YOUTUBEDL',event.element);
				var tracks = $('.clicktrack.selected');
				tracks.removeClass('selected');
				tracks.each(function() {
					$(this).find('.clicktrackmenu').makeSpinner();
				});
				tracks.each(async function() {
					var uri = decodeURIComponent($(this).attr('name'));
					var track_name = $(this).find('div.tracktitle').html();
					debug.log('YOUTUBEDL', uri, name);
					var monitor = new youtubeDownloadMonitor(uri, track_name, $(this));
					try {
						var response = await fetch(
							"api/metadata/",
							{
								method: 'POST',
								cache: 'no-store',
								priority: 'low',
								body: JSON.stringify([{action: 'youtubedl', urilist: [uri] }])
							}
						);
						if (!response.ok) {
							var t = await response.text();
							var msg = t ? t : response.statusText;
							throw new Error(msg);
						}
					} catch (err) {
						debug.error('BUMBLETREE', err);
						infobar.error('Failed to download YouTube track - '+err.message);
						monitor.stop();
					}
				});
			},

			downloadAllYoutubeTracks: async function(event, element) {
				debug.log('YTDLALL',event, element);
				var albumname = decodeURIComponent(element.attr('aname'));
				debug.log('YTDLALL', 'Downloading album', albumname);
				var monitor = new youtubeDownloadMonitor(element.attr('who'), albumname, $(this));
				try {
					var response = await fetch(
						"api/metadata/",
						{
							method: 'POST',
							cache: 'no-store',
							priority: 'low',
							body: JSON.stringify([{action: 'youtubedl_album', why: element.attr('why'), who: element.attr('who') }])
						}
					);
					if (!response.ok) {
						var t = await response.text();
						var msg = t ? t : response.statusText;
						throw new Error(msg);
					}
				} catch (err) {
					debug.error('BUMBLETREE', err);
					infobar.error('Failed to download YouTube album - '+err.message);
					monitor.stop();
				}
			},

			removeAlbumFromDb: function(event, element) {
				var albumToGo = element.attr("name");
				dbQueue.request(
					[{action: 'deletealbum', album_index: albumToGo}],
					collectionHelper.updateCollectionDisplay,
					metaHandlers.genericFailPopup
				);
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

			findAndSet: function(playlistinfo, action, attributes, success, fail) {
				if (player.updatingcollection) {
					infobar.notify(language.gettext('error_nosearchnow'));
					return;
				}
				var data = getPostData(playlistinfo);
				data.originalaction = action;
				data.action = 'findandset';
				if (attributes)
					data.attributes = attributes;
				dbQueue.request([data], success, fail);
			},

			mapData: function(playlistinfo, action, attributes) {
				var data = getPostData(playlistinfo);
				data.action = action;
				if (attributes)
					data.attributes = attributes;

				return data;
			}
		},

		fromBasicSpotifyInfo: {

			importTrack: function (albumdata, track_index, attributes, success) {
				// Don't send the albumimage. Either it has already been cached in which case we'll find it
				// or it'll be a getremoteimage obtained through Mopidy, which we now want to swap for a
				// cached one.
				var data = {
					action: 'set',
					attributes: attributes,
					Album: albumdata.name,
					albumartist: combine_spotify_artists(albumdata.artists),
					domain: albumdata.domain,
					'X-AlbumUri': albumdata.uri,
					file: albumdata.tracks.items[track_index].uri,
					Title: albumdata.tracks.items[track_index].name,
					Time: albumdata.tracks.items[track_index].duration_ms/1000,
					Track: albumdata.tracks.items[track_index].track_number,
					trackartist: combine_spotify_artists(albumdata.tracks.items[track_index].artists)
				};
				dbQueue.request([data], success, metaHandlers.genericFail);
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
				return structuredClone(tracks);
			},

			mapData: function(data, action, attributes) {
				var track = {action: action};
				track.Title = data.name;
				if (data.album)
					track.Album = data.album['#text'];

				if (data.artist) {
					// We have in the past tried to be clever about this, since
					// mpdscribble and mopidy-scrobbler don't always join artist names
					// the same way we do. Unfortunately it just ain't possible to do.
					// This works well if you have last.fm autocorrect disabled on last.fm
					// and you use Rompr to scrobble and have autocorrect turned off there too.
					// Otherwise I'm afraid it can be a bit shit where there are multiple artist names
					// And it's fuckin shocking if you're playing back podcasts and not scrobbling from Rompr,
					// 'cos the metadata the players use is nothing like what comes out of the RSS which is what we use
					track.trackartist = data.artist.name;
					track.albumartist = track.trackartist;
				}
				if (data.date)
					track.lastplayed = data.date.uts;

				if (attributes)
					track.attributes = attributes;

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

		check_for_db_updates: function() {
			dbQueue.request(
				[{action: 'getreturninfo'}],
				collectionHelper.updateCollectionDisplay,
				metaHandlers.genericFail
			);
		},

		get_artist_albums_as_spoti: async function(artist) {
			debug.log("JOHN", "Getting albums info for artist", artist);
			try {
				var response = await fetch(
					"api/metadata/query/",
					{
						method: 'POST',
						body: JSON.stringify({action: 'getalbumsasspoti', artist: artist}),
						priority: 'low'
					}
				);
				if (response.ok) {
					var data = await response.json();
					return data;
				} else {
					return null;
				}
			} catch (err) {
				return null;
			}
		},

		genericQuery: function(action, success, fail) {
			if (typeof action == "object") {
				var request = action;
			} else {
				var request = {action: action};
			}
			fetch(
				"api/metadata/query/",
				{
					method: 'POST',
					body: JSON.stringify(request),
					cache: 'no-store',
					priority: 'low'
				}
			)
			.then(response => {
				if (response.ok) {
					if (response.status == 200) {
						return response.json();
					} else {
						return true;
					}
				} else {
					throw new Error(response.statusText);
				}
			})
			.then(data => { success(data) })
			.catch(err => { fail(err) });
		},

		addAlbumUriToCollection(albumuri) {
			debug.log("METADATA","Adding album to collection", albumuri);
			metaHandlers.genericAction(
				[{
					action: 'addalbumtocollection',
					albumuri: albumuri
				}],
				collectionHelper.updateCollectionDisplay,
				metaHandlers.genericFailPopup
			);
		},

		browseSearchResult(albumindex) {
			metaHandlers.genericAction(
				[{
					action: 'browsesearchresult',
					albumindex: albumindex
				}],
				collectionHelper.updateCollectionDisplay,
				metaHandlers.genericFailPopup
			);
		},

		// data should be either {action :'addtolistenlater', json: {spotify album.getInfo}}
		// OR
		// {action: 'browsetoll', uri: an album uri that Mpodiy can find file}
		addToListenLater: function(data) {
			metaHandlers.genericQuery(
				data,
				function() {
					debug.log("METAHANDLERS","Album Added To Listen Later");
					infobar.notify(language.gettext('label_addedtolistenlater'));
					if (typeof(albumstolistento) != 'undefined') {
						albumstolistento.update();
					}
				},
				metaHandlers.genericFail
			)
		},

		resetSyncCounts: function() {
			metaHandlers.genericQuery('resetallsyncdata', metaHandlers.genericSuccess, metaHandlers.genericFail);
		},

		genericSuccess: function() {

		},

		genericFail: function(err) {
			debug.error('METAHANDLERS', err);
		},

		genericFailPopup: function(err) {
			debug.error('METAHANDLERS', err);
			infobar.error(language.gettext('label_generic_error')+'<br />'+err);
		}

	}

}();

var dbQueue = function() {

	// This is a queueing mechanism for the local database in order to avoid deadlocks.

	var queue = new Array();
	var current_req;
	var cleanuptimer = null;
	var cleanuprequired = false;

	// Cleanup cleans the database but it also updates the track stats
	var actions_requiring_cleanup = [
		'set', 'remove', 'amendalbum', 'delete', 'deletewl', 'clearwishlist', 'setasaudiobook'
	];

	async function process_request(req) {
		var err, request, data;
		try {
			response = await fetch(
				'api/metadata/',
				{
					signal: AbortSignal.timeout(60000),
					body: JSON.stringify(req.data),
					cache: 'no-store',
					method: 'POST',
					priority: 'low'
				}
			);
			if (response.ok) {
				if (response.status == 200) {
					data = await response.json();
				} else {
					data = true;
				}
				debug.debug('DB QUEUE', 'Request Success', req, data);
				for (var i in req.data) {
					if (actions_requiring_cleanup.indexOf(req.data[i].action) > -1) {
						debug.debug("DB QUEUE","Setting cleanup flag for",req.data[i].action,"request");
						cleanuprequired = true;
					}
				}
				if (req.success) {
					req.success(data);
				}
			} else {
				// response.json() doesn't work when there's an error response
				var t = await response.text();
				var msg = t ? t : response.statusText;
				if (msg.match(/\{.+\}/)) {
					var j = JSON.parse(msg);
					if (j.error) {
						msg = j.error;
					}
				}
				throw new Error(msg);
			}
		} catch (err) {
			debug.warn("DB QUEUE","Request Failed",err);
			if (req.fail) {
				req.fail(err.message);
			}
		}
	}

	return {

		request: function(data, success, fail) {
			debug.debug("DB QUEUE","New request",data);
			queue.push( {data: data, success: success, fail: fail } );
			if (typeof current_req == 'undefined')
				dbQueue.dorequest();

		},

		queuelength: function() {
			return queue.length;
		},

		dorequest: async function() {
			while (current_req = queue.shift()) {
				clearTimeout(cleanuptimer);
				await player.not_updating();
				debug.debug('DB QUEUE', 'Handling Request', current_req);
				await process_request(current_req);
			}
			clearTimeout(cleanuptimer);
			if (cleanuprequired) {
				cleanuptimer = setTimeout(dbQueue.doCleanup, 500);
			}
		},

		doCleanup: function() {
			// We do these out-of-band to improve the responsiveness of the GUI.
			clearTimeout(cleanuptimer);
			debug.info("DB QUEUE", "Doing backend Cleanup");
			dbQueue.request([{action: 'cleanup'}], dbQueue.cleanupComplete, dbQueue.cleanupFailed);
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

