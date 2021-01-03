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
										debug.debug("RATINGS PLUGIN","Not using update for",i,"as",v,"is less than",trackmeta.usermeta[i]);
									}
									break;
							}
						});
					}
					var playtext = '';
					if (trackmeta.usermeta.Playcount && trackmeta.usermeta.Playcount > 0) {
						playtext = '<span class="playspan"><b>PLAYS </b>&nbsp;'+trackmeta.usermeta.Playcount+'</span>';
						if (uiHelper.showTagButton()) {
							$("#playcount").html(playtext);
						}
						if (typeof charts != 'undefined') {
							charts.reloadAll();
						}
						if (typeof recentlyPlayed != 'undefined') {
							recentlyPlayed.reloadAll();
						}
					} else {
						$("#playcount").empty();
					}
					displayRating("#ratingimage", trackmeta.usermeta.Rating);
					if (uiHelper.showTagButton()) {
						$("#dbtags").html('<span><b>'+language.gettext("musicbrainz_tags")+
							'</b></span><i class="icon-plus clickicon playlisticon" '+
							'onclick="tagAdder.show(event)" style="margin-left:2px;margin-top:0px;margin-right:1em;"></i>');
					} else {
						$('#dbtags').html(playtext);
						if (trackmeta.usermeta.Tags.length > 0) {
							debug.log('RATINGS PLUGIN', 'Tags are',trackmeta.usermeta.Tags.length);
							$('#dbtags').append('<span><b>'+language.gettext("musicbrainz_tags")+' &nbsp;</b></span>');
						}
					}
					for(var i = 0; i < trackmeta.usermeta.Tags.length; i++) {
						$("#dbtags").append('<span class="tag">'+trackmeta.usermeta.Tags[i]+
							'<i class="icon-cancel-circled clickicon tagremover playlisticon" style="display:none"></i></span> ');
					}
					uiHelper.adjustLayout();
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

			function setFail(rdata) {
				debug.warn("RATING PLUGIN","Failure", rdata);
				// infobar.error("Failed! Have you read the Wiki?");
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
					// Prioritize - local, beetslocal, beets, spotify - in that order
					// There's currently no way to change these for tracks that are rated from radio stations
					// which means that these are the only domains that will be searched, but this is better
					// than including podcasts and radio stations, which we'll never want
					// I'm also not including SoundCloud because it produces far too many false positives
					if (prefs.player_backend == 'mopidy') {
						trackFinder.setPriorities(["spotify", "beets", "beetslocal", "local"]);
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
