var info_ratings = function() {

	var me = "ratings";
	var trackFinder = new faveFinder(false);
	var update_wishlist = false;

	return {
		getRequirements: function(parent) {
			return [];
		},

		collection: function(parent, artistmeta, albummeta, trackmeta) {

			debug.debug("RATINGS PLUGIN", "Creating data collection");

			var self = this;
			var displaying = false;
			var lfmupdates = null;

			function doThingsWithData() {
				if (parent.isCurrentTrack() && trackmeta.usermeta) {
					if (prefs.sync_lastfm_playcounts && lfmupdates !== null) {
						$.each(lfmupdates, function(i, v) {
							switch (i) {
								case 'Playcount':
									if (parseInt(trackmeta.usermeta[i]) < parseInt(v)) {
										debug.log("RATINGS PLUGIN","Update :",i,"is now",v);
										trackmeta.usermeta[i] = v;
										metaHandlers.fromPlaylistInfo.setMeta(
											parent.playlistinfo,
											'inc',
											[{attribute: 'Playcount', value: parseInt(v)}],
											function() { debug.mark('PLAYCOUNT', 'Backend playcount updated') },
											function() { debug.warn('PLAYCOUNT', 'Failed to update backend playcount') }
										);
									} else {
										debug.debug("RATINGS PLUGIN","Not using lfm update for",i,"as",v,"is less than",trackmeta.usermeta[i]);
									}
									break;
							}
						});
					}
					var playtext = '';
					if (trackmeta.usermeta.Playcount && trackmeta.usermeta.Playcount > 0) {
						playtext = '<span class="playspan">'+language.gettext('label_numplays', trackmeta.usermeta.Playcount.toString())+'</span>';
						$("#playcount").html(playtext);
						if (typeof charts != 'undefined')
							charts.reloadAll();

						if (typeof recentlyPlayed != 'undefined')
							recentlyPlayed.reloadAll();

					} else {
						$("#playcount").empty();
					}
					displayRating("#ratingimage", trackmeta.usermeta.Rating);
					$('#dbtags').empty();
					for(var i = 0; i < trackmeta.usermeta.Tags.length; i++) {
						$("#dbtags").append('<span class="tag">'+trackmeta.usermeta.Tags[i]+
							'<i class="icon-cancel-circled clickicon tagremover inline-icon" style="display:none"></i></span> ');
					}
					infobar.rejigTheText();
					// uiHelper.adjustLayout();
				}
				// Make sure the browser updates the file info display
				nowplaying.reDo(parent.nowplayingindex, 'file');
			}

			function hideTheInputs() {
				if (parent.isCurrentTrack()) {
					displayRating("#ratingimage", false);
					$("#dbtags").html('');
					$("#playcount").html('');
				}
			}

			function setSuccess(rdata) {
				debug.debug("RATING PLUGIN","Success");
				if (rdata) {
					trackmeta.usermeta = rdata.metadata;
					doThingsWithData();
					collectionHelper.updateCollectionDisplay(rdata);
				}
			}

			function findSuccess(rdata) {
				debug.debug("RATING PLUGIN","Success");
				if (rdata) {
					trackmeta.usermeta = rdata.metadata;
					doThingsWithData();
					collectionHelper.updateCollectionDisplay(rdata);
					if (!rdata.hasOwnProperty('addedtracks')) {
						infobar.error(language.gettext('error_trackexists'));
					}
				}
				if (update_wishlist && typeof(wishlistViewer) != 'undefined') {
					wishlistViewer.update();
				}
				update_wishlist = false;
			}

			function setFail(rdata, err) {
				debug.warn("RATING PLUGIN","Failure", rdata);
				try {
					debug.mark('RATING PLUGIN', err.responseJSON);
					infobar.error(err.responseJSON.error);
				} catch(e) {

				}
				doThingsWithData();
			}

			this.displayData = function() {
				debug.error("RATINGS PLUGIN", "Was asked to display data!");
			}

			this.stopDisplaying = function() {
			}

			this.updateMeta = function(updates) {
				lfmupdates = updates;
				doThingsWithData();
			}

			this.refresh = function() {
				trackmeta.usermeta = undefined;
				self.populate();
			}

			this.populate = function() {
				if (trackmeta.usermeta === undefined) {
					metaHandlers.fromPlaylistInfo.getMeta(
						parent.playlistinfo,
						function(data) {
							trackmeta.usermeta = data;
							doThingsWithData();
						},
						function(data) {
							trackmeta.usermeta = null;
							hideTheInputs();
						}
					);
				} else {
					debug.trace("RATINGS PLUGIN",parent.nowplayingindex,"is already populated");
					doThingsWithData();
				}
			}

			this.setMeta = function(action, type, value) {
				debug.log("RATINGS PLUGIN",parent.nowplayingindex,"Doing",action,type,value);
				if (parent.playlistinfo.type == 'stream') {
					infobar.notify(language.gettext('label_searching'));
					// Prioritize - local, beetslocal, beets, ytmusic, spotify - in that order
					// There's currently no way to change these for tracks that are rated from radio stations
					// which means that these are the only domains that will be searched, but this is better
					// than including podcasts and radio stations, which we'll never want.
					// I'm also not including SoundCloud because it produces far too many false positives
					// Also having to remove ytmuisc as the URLs are not reuseable

					//
					// NOTE isArtistorAlbum is currently "hacked" to ignore ytmusic: tracks as well as :artist: and :album:

					if (prefs.player_backend == 'mopidy') {
						// trackFinder.setPriorities(["spotify", "ytmusic", "beets", "beetslocal", "local"]);
						trackFinder.setPriorities(["youtube", "spotify", "beets", "beetslocal", "local"]);
					}
					trackFinder.findThisOne(
						metaHandlers.fromPlaylistInfo.mapData(parent.playlistinfo, action, [{attribute: type, value: value}]),
						self.updateDatabase
					);
				} else {
					metaHandlers.fromPlaylistInfo.setMeta(
						parent.playlistinfo,
						action,
						[{attribute: type, value: value}],
						setSuccess,
						setFail
					);
				}
			}

			this.setAlbumMBID = function(mbid) {
				debug.log("RATINGS PLUGIN",parent.nowplayingindex," Updating backend album MBID");
				metaHandlers.fromPlaylistInfo.setMeta(parent.playlistinfo, 'setalbummbid', mbid, false, false);
			}

			this.getMeta = function(meta) {
				if (trackmeta.usermeta) {
					if (trackmeta.usermeta[meta]) {
						return trackmeta.usermeta[meta];
					} else {
						return 0;
					}
				} else {
					return 0;
				}
			}

			this.updateDatabase = function(data) {
				debug.debug("RATINGS","Update Database Function Called",data);
				if (!data.uri) {
					infobar.notify(language.gettext("label_addtow"));
					update_wishlist = true;
				}
				dbQueue.request([data], findSuccess, setFail);
			}
		}
	}
}();

nowplaying.registerPlugin("ratings", info_ratings, null, null);
