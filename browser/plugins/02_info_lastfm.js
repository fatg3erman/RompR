var info_lastfm = function() {

	var me = "lastfm";
	var medebug = "LASTFM PLUGIN";

	function format_lastfm_error(lfmdata, type) {
		if (lfmdata.errorcode() == 6) {
			return language.gettext('label_no'+type+'info');
		} else {
			return lfmdata.error();
		}
	}

	function do_section_header(layout, data) {
		var list = layout.add_sidebar_list(language.gettext("lastfm_listeners"), data.listeners());
		layout.append_to_list(list, language.gettext("lastfm_plays"), data.playcount());
		layout.append_to_list(list, language.gettext("lastfm_yourplays"), data.userplaycount());
		if (data.releasedate() != 'Unknown') {
			layout.append_to_list(list, language.gettext("lastfm_releasedate"), data.releasedate());
		}
	}

	function do_tags(layout, taglist) {
		debug.debug(medebug,"    Doing Tags");
		var list = layout.add_sidebar_list(language.gettext("lastfm_toptags"));
		taglist.forEach(function(tag) {
			if (tag.name) {
				list.append($('<li>').append($('<a>', {href: tag.url, target: '_blank'}).html(tag.name)));
			}
		});
	}

	function do_tag_input(layout, type) {
		var list = layout.add_sidebar_list(language.gettext("lastfm_addtags"));
		list.addClass('holdingcell');
		list.append($('<li>')
			.append($('<input>', {class: 'enter tiny inbrowser', type: 'text', placeholder: unescapeHtml(language.gettext("lastfm_addtagslabel"))}))
			.append($('<button>', {class: 'infoclick clickaddtags tiny'}).html(language.gettext("button_add")))
			.append($('<i>', {class: 'smallicon tright', id: 'tagadd'+type})));
	}

	function do_user_tags(layout, meta) {
		var list = layout.add_sidebar_list(language.gettext("lastfm_yourtags"));
		meta.lastfm.user_tag_table = $('<table>', {class: 'fullwidth'}).appendTo($('<li>').appendTo(list));
	}

	function formatUserTagData(meta) {
		var taglist = meta.lastfm.data.usertags;
		var table = meta.lastfm.user_tag_table;
		table.find("tr").each( function() {
			if (!(findTag($(this).find('a').text(), taglist))) {
				$(this).fadeOut('fast', function() { $(this).remove() });
			}
		});
		for(var i in taglist) {
			if (!(findTag2(taglist[i].name, table))) {
				appendTag(table, taglist[i].name, taglist[i].url);
			}
		}
	}

	function findTag(name, taglist) {
		for(var i in taglist) {
			if (name == taglist[i].name) {
				return true;
			}
		}
		return false;
	}

	function findTag2(name, table) {
		var retval = false;
		table.find('tr').each( function() {
			var n = $(this).find('a').text();
			if (n.toLowerCase() == name.toLowerCase()) {
				retval = true;
			}
		});
		return retval;
	}

	function appendTag(table, name, url) {
		var row = $('<tr>', {class: 'newtag invisible'}).appendTo(table);
		row.append($('<td>').append($('<a>', {href: url, target: '_blank'}).html(name)));
		row.append($('<td>').append($('<i>', {class: 'icon-cancel-circled inline-icon infoclick clickremovetag tooltip', title: language.gettext("lastfm_removetag")})));
		row.fadeIn('fast', function() {
			$(this).removeClass('newtag');
		});
	}

	function getArtistHTML(layout, lfmdata, parent, artistmeta) {
		if (lfmdata.error()) {
			layout.display_error(format_lastfm_error(lfmdata, 'artist'));
			layout.finish(null, null);
			return;
		}

		do_section_header(layout, lfmdata);
		do_tags(layout, lfmdata.tags());

		if (lastfm.isLoggedIn()) {
			 do_tag_input(layout, 'artist');
			 do_user_tags(layout, artistmeta);
		}

		layout.add_profile(lastfm.formatBio(lfmdata.bio(), lfmdata.url()));

		var similies = lfmdata.similar();
		if (similies.length > 0 && typeof similies[0].name != 'undefined') {
			layout.add_flow_box_header({wide: true, title: language.gettext("lastfm_simar")});
			var table = $('<table>', {class: 'padded'}).appendTo(layout.add_flow_box());
			similies.forEach(function(sim) {
				var row = $('<tr>').appendTo(table);
				row.append($('<td>').append($('<a>', {href: sim.url, target: '_blank'}).html(sim.name)));
			});
		}

		layout.finish(lfmdata.url(), lfmdata.name() || artistmeta.name);

	}


	function getAlbumHTML(layout, lfmdata, parent, albummeta) {
		if (lfmdata.error()) {
			layout.display_error(format_lastfm_error(lfmdata, 'album'));
			layout.finish(null, null);
			return;
		}

		do_section_header(layout, lfmdata);
		do_tags(layout, lfmdata.tags());

		if (lastfm.isLoggedIn()) {
			 do_tag_input(layout, 'album');
			 do_user_tags(layout, albummeta);
		}

		var imageurl = lfmdata.image("large");
		var bigurl = lfmdata.image("mega");
		if (bigurl || imageurl) {
			layout.add_main_image(bigurl || imageurl);
		}

		layout.add_profile(lastfm.formatBio(lfmdata.bio()));

		var tracks = lfmdata.tracklisting();
		debug.mark(medebug,"Track Listing",tracks.length,tracks);
		if (tracks && tracks.length > 0 && tracks[0].length > 0) {
			layout.add_flow_box_header({wide: true, title: language.gettext("discogs_tracklisting")});
			var table = $('<table>').appendTo(layout.add_flow_box());
			tracks.forEach(function(track) {
				var row = $('<tr>').appendTo(table);
				row.append($('<td>').html(track['@aatr'] ? track['@attr'].rank+':' : ''));
				row.append($('<td>').html(track.name));
				row.append($('<td>').html(formatTimeString(track.duration)));
				row.append($('<td>', {align: 'right'}).append($('<a>', {target: '_blank', href: track.url})
					.append($('<i>', {class: 'icon-lastfm-1 smallicon tooltip', title: language.gettext("lastfm_viewtrack")}))));
			});
		}

		layout.finish(lfmdata.url(), lfmdata.name() || albummeta.name);
	}

	function getTrackHTML(layout, lfmdata, parent, trackmeta) {
		if (lfmdata.error()) {
			layout.display_error(format_lastfm_error(lfmdata, 'track'));
			layout.finish(null, null);
			return;
		}

		do_section_header(layout, lfmdata);

		trackmeta.lastfm.user_loved = layout.add_sidebar_list(language.gettext("lastfm_loved")+': ', '<i class="icon-heart smallicon infoclick tooltip notloved"></i>');

		do_tags(layout, lfmdata.tags());

		if (lastfm.isLoggedIn()) {
			 do_tag_input(layout, 'track');
			 do_user_tags(layout, trackmeta);
		}

		layout.add_profile(lastfm.formatBio(lfmdata.bio()))
		layout.finish(lfmdata.url(), lfmdata.name() || trackmeta.name);

	}

	return {
		getRequirements: function(parent) {
			return ['musicbrainz'];
		},

		collection: function(parent, artistmeta, albummeta, trackmeta) {

			debug.debug(medebug, "Creating data collection");

			var self = this;

			this.populate = function() {
				debug.debug('LASTFM', 'Asked To Populate');
				parent.updateData({
					lastfm: {done_image: false}
				}, artistmeta);

				parent.updateData({
					lastfm: {}
				}, albummeta);

				parent.updateData({
					lastfm: {}
				}, trackmeta);

				if (typeof artistmeta.lastfm.layout == 'undefined')
					self.artist.populate();

				if (typeof albummeta.lastfm.layout == 'undefined')
					self.album.populate();

				if (typeof trackmeta.lastfm.layout == 'undefined') {
					$('#love').removeClass('notloved').addClass('notloved').makeSpinner();
					self.track.populate();
				}

			}

			this.handleClick = function(source, element, event) {
				debug.trace(medebug,parent.nowplayingindex,source,"is handling a click event");
				if (element.hasClass('clickremovetag')) {
					var tagname = element.parent().prev().children().text();
					debug.trace(medebug,parent.nowplayingindex,source,"wants to remove tag",tagname);
					self[source].removetags(tagname);
					if (prefs.synctags) {
						parent.setMeta('remove', 'Tags', tagname);
					}
				} else if (element.hasClass('clickaddtags')) {
					var tagname = element.prev().val();
					debug.trace(medebug,parent.nowplayingindex,source,"wants to add tags",tagname);
					self[source].addtags(tagname);
					if (prefs.synctags) {
						parent.setMeta('set', 'Tags', tagname.split(','));
					}
				} else if (element.hasClass('clickzoomimage')) {
					imagePopup.create(element, event, element.next().val());
				} else if (element.hasClass('clickunlove')) {
					self[source].unlove();
					if (parseInt(prefs.synclovevalue) != 0) {
						parent.setMeta('set', 'Rating', '0');
					}
				} else if (element.hasClass('clicklove')) {
					self[source].love();
					if (parseInt(prefs.synclovevalue) > 0) {
						parent.setMeta('set', 'Rating', prefs.synclovevalue);
					}
				}
			}

			this.somethingfailed = function(data) {
				debug.warn(medebug,"Something went wrong",data);
			}

			this.justaddedtags = function(type, tags) {
				debug.trace(medebug,parent.nowplayingindex,"Just added or removed tags",tags,"on",type);
				self[type].resetUserTags();
				self[type].getUserTags();
			}

			this.tagAddFailed = function(type, tags) {
				$("#tagadd"+type).stopSpinner();
				infobar.error(language.gettext("lastfm_tagerror"));
				debug.warn(medebug,"Failed to modify tags",type,tags);
			}

			function doUserLoved(flag) {
				debug.debug("LASTFM","Doing UserLoved With Flags at",flag);
				if (parent.isCurrentTrack()) {
					$('#love').stopSpinner();
					if (flag) {
						$('#love').removeClass('notloved').attr('title', language.gettext("lastfm_unlove")).off(prefs.click_event).on(prefs.click_event, nowplaying.unlove);
					} else {
						$('#love').removeClass('notloved').addClass('notloved').attr('title', language.gettext("lastfm_lovethis")).off(prefs.click_event).on(prefs.click_event, nowplaying.love);
					}
				}
				if (trackmeta.user_loved) {
					if (flag) {
						trackmeta.lastfm.user_loved.find('i.icon-heart').removeClass('notloved').removeClass('clicklove').removeClass('clickunlove').addClass('clickunlove').attr('title', language.gettext("lastfm_unlove"));
					} else {
						trackmeta.lastfm.user_loved.find('i.icon-heart').removeClass('notloved').removeClass('clicklove').removeClass('clickunlove').addClass('clicklove').addClass('notloved').attr('title', language.gettext("lastfm_lovethis"));
					}
				}
			}

			function getSearchArtist() {
				return (albummeta.artist && albummeta.artist != "" && parent.playlistinfo.type != 'stream') ? albummeta.artist : trackmeta.artist;
			}

			function sendLastFMCorrections() {
				try {
					var updates = { trackartist: (parent.playlistinfo.metadata.artists.length == 1) ? self.artist.name() : parent.playlistinfo.trackartist,
									album: self.album.name(),
									title: self.track.name(),
									image: self.album.image('mega') ? self.album.image('mega') : self.album.image('medium')
								};
					nowplaying.setLastFMCorrections(parent.currenttrack, updates);
				} catch(err) {
					debug.info(medebug,"Not enough information to send corrections");
				}
			}

			function sendMetadataUpdates(de) {
				var lfmdata = new lfmDataExtractor(trackmeta.lastfm.data.track);
				nowplaying.setMetadataFromLastFM(parent.nowplayingindex, {Playcount: lfmdata.userplaycount()});
			}

			this.artist = function() {

				return {

					populate: function() {
						if (artistmeta.name == '') {
							artistmeta.lastfm.layout = new info_layout_empty();
						} else {
							artistmeta.lastfm.layout = new info_sidebar_layout({title: artistmeta.name, type: 'artist', source: me});
							debug.debug(medebug,parent.nowplayingindex,"artist is populating",artistmeta.name);
							lastfm.artist.getInfo( {artist: artistmeta.name},
													this.lfmResponseHandler,
													this.lfmResponseHandler
							);
							parent.updateData({
								triggers: {
									allmusic: {
										link: self.artist.tryForAllmusicImage
									}
								}
							}, artistmeta);
						}
					},

					lfmResponseHandler: function(data) {
						debug.debug(medebug,parent.nowplayingindex,"got artist data for",artistmeta.name);
						debug.debug(medebug,data);
						var de = new lfmDataExtractor(data);
						artistmeta.lastfm.data = de.getCheckedData('artist');
						var mbid = null;
						try {
							mbid = data.artist.mbid || null;
						} catch(err) {
							mbid = null;
						}
						debug.trace(medebug,parent.nowplayingindex,"setting musicbrainz artist ID to",mbid);
						parent.updateData({
								lastfm: {
									musicbrainz_id: mbid
								}
							},
							artistmeta
						);
						self.artist.doBrowserUpdate();
					},

					tryForAllmusicImage: function() {
						if (artistmeta.lastfm.done_image || artistmeta.allmusic.link == null || artistmeta.allmusic.link == '') {
							return;
						}
						try {
							parent.updateData({
									lastfm: {
										done_image: true
									}
								},
								artistmeta
							);
							debug.log(medebug,"Getting allmusic bio from",artistmeta.allmusic.link);
							$.post('browser/backends/getamimage.php', {url: artistmeta.allmusic.link})
							 .done( function(data) {
								debug.debug(medebug,"Got Allmusic Image", data);
								artistmeta.lastfm.layout.add_main_image(data);
							 })
							 .fail( function() {
								debug.log(medebug,"Didn't Get Allmusic Image");
								// Causes too much discogs traffic
								// parent.get_random_discogs_artist_image(artistmeta.lastfm.layout);
							 });
						} catch (err) {
							debug.log(medebug, 'Unable to find AllMusic image link');
							// Causes too much discogs traffic
							// parent.get_random_discogs_artist_image(artistmeta.lastfm.layout);
						}
					},

					doBrowserUpdate: function() {
						var lfmdata = new lfmDataExtractor(artistmeta.lastfm.data.artist);
						getArtistHTML(artistmeta.lastfm.layout, lfmdata, parent, artistmeta);
						self.artist.tryForAllmusicImage();
						if (lastfm.isLoggedIn() && !lfmdata.error()) {
							self.artist.getUserTags();
						}
					},

					name: function() {
						try {
							return artistmeta.lastfm.data.artist.name || artistmeta.name;
						} catch(err) {
							return artistmeta.name;
						}
					},

					resetUserTags: function() {
						artistmeta.lastfm.data.usertags = null;
					},

					getUserTags: function() {
						debug.debug(medebug,parent.nowplayingindex,"Getting Artist User Tags");
						if (artistmeta.lastfm.data.usertags) {
							formatUserTagData(artistmeta);
						} else {
							var options = { artist: self.artist.name() };
							if (artistmeta.musicbrainz_id != "") {
								options.mbid = artistmeta.musicbrainz_id;
							}
							lastfm.artist.getTags(
								options,
								self.artist.gotUserTags,
								self.artist.somethingfailed
							);
						}

					},

					somethingfailed: function(data) {
						$("#tagaddartist").stopSpinner();
						debug.warn(medebug,"Something went wrong getting artist user tags",data);
					},

					gotUserTags: function(data) {
						$("#tagaddartist").stopSpinner();
						var de = new lfmDataExtractor(data);
						artistmeta.lastfm.data.usertags = de.tags();
						formatUserTagData(artistmeta);
					},

					addtags: function(tags) {
						$("#tagaddartist").makeSpinner();
						lastfm.artist.addTags({ artist: self.artist.name(),
												tags: tags},
												self.justaddedtags,
												self.tagAddFailed
						);
					},

					removetags: function(tags) {
						$("#tagaddartist").makeSpinner();
						lastfm.artist.removeTag({artist: self.artist.name(),
												tag: tags},
												self.justaddedtags,
												self.tagAddFailed
						);
					}
				}
			}();

			this.album = function() {

				return {

					populate: function() {
						albummeta.lastfm.layout = new info_sidebar_layout({title: albummeta.name, type: 'album', source: me});
						if (parent.playlistinfo.type == 'stream') {
							lastfm.artist.getInfo({  artist: albummeta.name },
												this.lfmArtistResponseHandler,
												this.lfmArtistResponseHandler );

						} else {
							lastfm.album.getInfo({  artist: getSearchArtist(),
													album: albummeta.name},
												this.lfmResponseHandler,
												this.lfmResponseHandler );
						}
					},

					lfmResponseHandler: function(data) {
						debug.debug(medebug,"Got Album Info for",albummeta.name);
						debug.debug(medebug, data);
						var de = new lfmDataExtractor(data);
						albummeta.lastfm.data = de.getCheckedData('album');
						self.album.doBrowserUpdate();
					},

					lfmArtistResponseHandler: function(data) {
						debug.trace(medebug,"Got Album/Artist Info for",albummeta.name);
						debug.debug(medebug, data);
						var de = new lfmDataExtractor(data);
						albummeta.lastfm.data = de.getCheckedData('artist');
						albummeta.musicbrainz_id = null;
						self.album.doBrowserUpdate();
					},

					doBrowserUpdate: function() {
						if (parent.playlistinfo.type == 'stream') {
							var lfmdata = new lfmDataExtractor(albummeta.lastfm.data.artist);
							getArtistHTML(albummeta.lastfm.layout, lfmdata, parent, albummeta);
						} else {
							var lfmdata = new lfmDataExtractor(albummeta.lastfm.data.album);
							getAlbumHTML(albummeta.lastfm.layout, lfmdata, parent, albummeta);
						}
						if (lastfm.isLoggedIn() && !lfmdata.error()) {
							self.album.getUserTags();
						}
					},

					name: function() {
						try {
							return albummeta.lastfm.data.album.name || albummeta.name;
						} catch(err) {
							return albummeta.name;
						}
					},

					image: function(size) {
						if (albummeta.lastfm.data.album) {
							var lfmdata = new lfmDataExtractor(albummeta.lastfm.data.album);
							return lfmdata.image(size);
						}
						return "";
					},

					resetUserTags: function() {
						albummeta.lastfm.data.usertags = null;
					},

					getUserTags: function() {
						debug.debug(medebug,parent.nowplayingindex,"Getting Album User Tags");
						if (albummeta.lastfm.data.usertags) {
							formatUserTagData(albummeta);
						} else {
							var options = { artist: getSearchArtist(), album: self.album.name() };
							if (albummeta.musicbrainz_id != "" && albummeta.musicbrainz_id != null) {
								options.mbid = albummeta.musicbrainz_id;
							}
							lastfm.album.getTags(
								options,
								self.album.gotUserTags,
								self.album.somethingfailed
							);
						}

					},

					somethingfailed: function(data) {
						// $("#tagaddalbum").stopSpinner();
						debug.warn(medebug,"Something went wrong getting album user tags",data);
					},

					gotUserTags: function(data) {
						$("#tagaddalbum").stopSpinner();
						var de = new lfmDataExtractor(data);
						albummeta.lastfm.data.usertags = de.tags();
						formatUserTagData(albummeta);
					},

					addtags: function(tags) {
						$("#tagaddalbum").makeSpinner();
						lastfm.album.addTags({  artist: getSearchArtist(),
												album: self.album.name(),
												tags: tags},
											self.justaddedtags,
											self.tagAddFailed
						);
					},

					removetags: function(tags) {
						$("#tagaddalbum").makeSpinner();
						lastfm.album.removeTag({    artist: getSearchArtist(),
													album: self.album.name(),
													tag: tags},
											self.justaddedtags,
											self.tagAddFailed
						);
					}
				}
			}();

			this.track = function() {

				return {

					populate: function() {
						if (trackmeta.name == '') {
							trackmeta.lastfm.layout = new info_layout_empty();
						} else {
							trackmeta.lastfm.layout = new info_sidebar_layout({title: trackmeta.name, type: 'track', source: me});
							debug.debug(medebug,parent.nowplayingindex,"Getting last.fm data for track",trackmeta.name);
							lastfm.track.getInfo( { artist: getSearchArtist(), track: trackmeta.name },
													this.lfmResponseHandler,
													this.lfmResponseHandler );
						}
					},

					lfmResponseHandler: function(data) {
						debug.debug(medebug,parent.nowplayingindex,"Got Track Info for",trackmeta.name);
						debug.debug(medebug, data);
						var de = new lfmDataExtractor(data);
						trackmeta.lastfm.data = de.getCheckedData('track');
						sendLastFMCorrections();
						sendMetadataUpdates();
						self.track.doBrowserUpdate();
					},

					doBrowserUpdate: function() {
						var lfmdata = new lfmDataExtractor(trackmeta.lastfm.data.track);
						getTrackHTML(trackmeta.lastfm.layout, lfmdata, parent, trackmeta);
						doUserLoved(lfmdata.userloved());
						if (lastfm.isLoggedIn() && !lfmdata.error()) {
							self.track.getUserTags();
						}
					},

					name: function() {
						try {
							return trackmeta.lastfm.data.track.name || trackmeta.name;
						} catch(err) {
							return trackmeta.name;
						}
					},

					resetUserTags: function() {
						trackmeta.lastfm.data.usertags = null;
					},

					getUserTags: function() {
						debug.debug(medebug,parent.nowplayingindex,"Getting Track User Tags");
						if (trackmeta.lastfm.data.usertags) {
							formatUserTagData(trackmeta);
						} else {
							var options = { artist: self.artist.name(), track: self.track.name() };
							if (trackmeta.musicbrainz_id != "" && trackmeta.musicbrainz_id != null) {
								options.mbid = trackmeta.musicbrainz_id;
							}
							lastfm.track.getTags(
								options,
								self.track.gotUserTags,
								self.track.somethingfailed,
							);
						}
					},

					somethingfailed: function(data) {
						$("#tagaddtrack").stopSpinner();
						debug.warn(medebug,"Something went wrong getting track user tags",data);
					},

					gotUserTags: function(data) {
						$("#tagaddtrack").stopSpinner();
						var de = new lfmDataExtractor(data);
						trackmeta.lastfm.data.usertags = de.tags();
						formatUserTagData(trackmeta);
					},

					addtags: function(tags) {
						$("#tagaddtrack").makeSpinner();
						lastfm.track.addTags({  artist: self.artist.name(),
												track: self.track.name(),
												tags: tags},
											self.justaddedtags,
											self.tagAddFailed
						);
					},

					removetags: function(tags) {
						if (findTag2(tags, trackmeta.lastfm.user_tag_table)) {
							$("#tagaddtrack").makeSpinner();
							lastfm.track.removeTag({    artist: self.artist.name(),
														track: self.track.name(),
														tag: tags},
												self.justaddedtags,
												self.tagAddFailed
							);
						} else {
							debug.warn(medebug, "Tag",tags,"not found on track");
						}
					},

					love: function() {
						lastfm.track.love({ track: self.track.name(), artist: self.artist.name() }, self.track.donelove);
					},

					unlove: function(callback) {
						lastfm.track.unlove({ track: self.track.name(), artist: self.artist.name() }, self.track.donelove);
					},

					unloveifloved: function() {
						if (trackmeta.lastfm.data.track.userloved == 1) {
							self.track.unlove();
						}
					},

					donelove: function(loved) {
						if (loved) {
							// Rather than re-get all the details, we can just edit the track data directly.
							trackmeta.lastfm.data.track.userloved = 1;
							if (prefs.autotagname != '') {
								self.track.addtags(prefs.autotagname);
								if (prefs.synctags && parseInt(prefs.synclovevalue) > 0) {
									parent.setMeta('set', 'Tags', [prefs.autotagname]);
								}
							}
							doUserLoved(true)
						} else {
							trackmeta.lastfm.data.track.userloved = 0;
							if (prefs.autotagname != '') {
								self.track.removetags(prefs.autotagname);
								if (prefs.synctags && parseInt(prefs.synclovevalue) > 0) {
									parent.setMeta('remove', 'Tags', prefs.autotagname);
								}
							}
							doUserLoved(false)
						}
					}

				}
			}();
		}
	}

}();

nowplaying.registerPlugin("lastfm", info_lastfm, "icon-lastfm-1", "button_infolastfm");
