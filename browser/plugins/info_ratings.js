var info_ratings = function() {

	var me = "ratings";

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
				debug.debug("RATING PLUGIN","Success", rdata);
				if (rdata) {
					trackmeta.usermeta = rdata.metadata;
					doThingsWithData();
					collectionHelper.updateCollectionDisplay(rdata);
					if (!rdata.hasOwnProperty('addedtracks')) {
						infobar.error(language.gettext('error_trackexists'));
					}
					if (rdata.addedtracks && rdata.addedtracks[0] && rdata.addedtracks[0]['trackuri'] == '') {
						infobar.notify(language.gettext("label_addtow"));
						if (typeof(wishlistViewer) != 'undefined')
							wishlistViewer.update();
					}
				}
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
					metaHandlers.fromPlaylistInfo.findAndSet(
						parent.playlistinfo,
						action,
						[{attribute: type, value: value}],
						findSuccess,
						setFail
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

			this.ban = function() {
				this.setMeta('ban', 'dummy', 'baby');
				infobar.notify('Banned '+parent.playlistinfo.trackartist+' - '+parent.playlistinfo.Title);
			}
		}
	}
}();

nowplaying.registerPlugin("ratings", info_ratings, null, null);
