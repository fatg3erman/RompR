var info_spotify = function() {

	var me = "spotify";
	var medebug = "SPOTIFY PLUGIN";
	var maxwidth = 300;

	// function makePersonalRadio(target, cls, label) {
	// 	target.append($('<div>', {class: 'containerbox menuitem infoclick '+cls, style: 'padding-left: 0px'})
	// 		.append($('<i>', {class: 'icon-wifi smallicon fixed alignmid'}))
	// 		.append($('<div>', {class: 'expand'}).html(label))
	// 	);
	// }

	function do_genres(layout, u, genres) {
		if (player.canPlay('spotify')) {
			for (var g of genres) {
				// We get a list of acceptable genre seeds from spotify and only make those playable
				// And we loop through twice so the playable ones come first otherwise it looks messy
				if (player.genreseeds.indexOf(g) > -1) {
					add_coll_button(u, 'clickstartgenreradio', 'icon-spotify-circled', language.gettext('label_genre')+': '+g, g);
				}
			}
			for (var g of genres) {
				if (player.genreseeds.indexOf(g) < 0) {
					layout.append_to_list(u, language.gettext('label_genre')+': ', g);
				}
			}
		} else {
			for (var g of genres) {
				layout.append_to_list(u, language.gettext('label_genre')+': ', g);
			}
		}
	}

	function getTrackHTML(trackobj, trackmeta, layout) {

		var data = trackmeta.spotify.track;
		debug.debug(medebug,"Making Track Info From",data);
		if (data.error) {
			layout.display_error(data.error);
			layout.finish(null, null);
			return;
		}

		layout.add_sidebar_list(language.gettext("label_pop"), data.popularity);
		if (player.canPlay('spotify') && trackmeta.spotify.id) {
			let u = layout.add_sidebar_list(language.gettext("label_pluginplaylists"));
			add_coll_button(u, 'clickstarttrackradio', 'icon-spotify-circled', language.gettext('label_radio_recommend',[language.gettext('label_track')]));
		}

		if (data.explicit)
			layout.add_sidebar_list('', '<i class="icon-explicit stright standout"></i>');

		if (trackmeta.spotify.recommendations) {
			doRecommendations(layout, trackmeta);
		} else {
			let params = { limit: 15, seed_tracks: trackmeta.spotify.id }
			spotify.recommendations.getRecommendations(params, trackobj.gotRecommendations, trackobj.spotifyRecError);
		}

		layout.finish(data.external_urls.spotify, data.name);

	}

	function doRecommendations(layout, trackmeta) {
		layout.add_non_flow_box_header({wide: true, title: language.gettext('discover_now', [trackmeta.spotify.track.name])});
		layout.add_playable_images(trackmeta.spotify.recommendations);
	}

	function add_coll_button(list, clarss, icon, label, input) {
		let holder = $('<div>', {class: 'containerbox menuitem infoclick '+clarss}).appendTo(list);
		holder.append($('<div>', {class: 'fixed alignmid'}).append($('<i>', {class: 'smallicon '+icon})));
		holder.append($('<div>', {class: 'expand'}).html(label));
		if (input) {
			list.append($('<input>', {'type': 'hidden', 'value': input}));
		}
	}

	function getAlbumHTML(albumobj, albummeta, layout) {

		var data = albummeta.spotify.album;
		debug.debug(medebug,"Making Album Info From",data);
		if (data.error) {
			layout.display_error(data.error);
			layout.finish(null, null);
			return;
		}
		let u = layout.add_sidebar_list(language.gettext("label_pop"), data.popularity);
		layout.append_to_list(u, language.gettext("lastfm_releasedate"), data.release_date);
		if (data.genres && data.genres.length > 0) {
			do_genres(layout, u, data,genres);
		}
		if (player.canPlay('spotify')) {
			add_coll_button(u, 'clickaddtolistenlater', 'icon-headphones', language.gettext('label_addtolistenlater'));
			add_coll_button(u, 'clickaddtocollection', 'icon-music', language.gettext('label_addtocollection'));
		}

		if (data.images && data.images[0]) {
			layout.add_main_image(data.images[0].url);
		}

		layout.add_flow_box_header({wide: true, title: language.gettext("discogs_tracklisting")});
		var div = $('<div>', {class: 'selecotron'}).appendTo(layout.add_flow_box());
		div.html(spotifyTrackListing(data, true));

		layout.finish(data.external_urls.spotify, data.name);

	}


	return {

		getRequirements: function(parent) {
			return ["musicbrainz"];
		},

		collection: function(parent, artistmeta, albummeta, trackmeta) {

			debug.debug(medebug, "Creating data collection");

			var self = this;

			this.findDisplayPanel = function(element, artistobj) {
				var c = element;
				while (!c.hasClass('nobwobbler')) {
					c = c.parent();
				}
				if (c.hasClass('nobalbum')) {
					debug.trace(medebug,"Opening Album Panel Via Widget");
					artistmeta.spotify.spotiwidget.spotifyAlbumThing('handleClick', element);
					return true;
				} else if (c.hasClass('nobartist')) {
					debug.trace(medebug,"Opening Artist Panel Via Widget");
					artistmeta.spotify.spotiwidget.spotifyArtistThing('handleClick', element);
					return true;
				} else {
					debug.warn(medebug,"Click On Unknown Element!",element);
					return false;
				}
			}

			this.populate = function() {

				// We need the extra 'populated' flags because the normal course of action is for
				// the track to populate the album and arist once it has worked out their spotify ids -
				// if we just blindly populate them every time this results in is filling the layout
				// multiple times if we're playing successive tracks by the same artist
				// (and we can't just check if the id field is populated because there are other routes by which that can happen)

				parent.updateData({
					spotify: {
						showing: 'albums',
						populated: false,
						done_bio: false,
						id: ''
					},
					triggers: {
						allmusic: {
							link: self.artist.tryForAllMusicBio
						},
						spotify: {
							id: self.artist.populate
						}
					}
				}, artistmeta);

				parent.updateData({
					spotify: { }
				}, trackmeta);

				parent.updateData({
					spotify: {
						populated: false
					}
				}, albummeta);

				// This is a bit of a hack.
				// When switching artists on a Spotify track we get here and trackmeta.spotify.layout is not undefined
				// (because it's the same track) so we never populate. This gets set to true if the artist layout is undefined
				// which makes sure we populate via the normal flow instead of having some extra code path where we only
				// populate the artist. The same problem does not occur for local tracks because we populate the artist for those
				var is_probably_artist_switch = false;

				// We need to create all the layouts immediately because the browser expccts them to exist.
				// Additionally, when switching artists using switchArtist, the track and album layouts
				// will already exist but the artist one won't so belt and braces, check everything.

				if (parent.playlistinfo.file.substring(0,8) !== 'spotify:') {
					// Not a spotify track. We don't search, because I can't be arsed
					if (typeof trackmeta.spotify.layout == 'undefined')
						trackmeta.spotify.layout = new info_layout_empty();

					if (typeof albummeta.spotify.layout == 'undefined')
						albummeta.spotify.layout = new info_layout_empty();

					if (typeof artistmeta.spotify.layout == 'undefined') {
						artistmeta.spotify.layout = new info_sidebar_layout({title: artistmeta.name, type: 'artist', source: me});
						self.artist.populate();
					}

				} else {

					if (typeof artistmeta.spotify.layout == 'undefined') {
						artistmeta.spotify.layout = new info_sidebar_layout({title: artistmeta.name, type: 'artist', source: me});
						is_probably_artist_switch = true;
					}

					if (typeof albummeta.spotify.layout == 'undefined')
						albummeta.spotify.layout = new info_sidebar_layout({title: albummeta.name, type: 'album', source: me});

					if (typeof trackmeta.spotify.layout == 'undefined' || is_probably_artist_switch)
						self.track.populate();
				}
			}

			this.handleClick = function(source, element, event) {
				debug.debug(medebug,parent.nowplayingindex,source,"is handling a click event");
				if (element.hasClass('clickzoomimage')) {
					imagePopup.create(element, event, element.attr("src"));
				} else if (element.hasClass('clickspotifywidget')) {
					self.findDisplayPanel(element, self.artist);
				} else if (element.hasClass('clickchooseposs')) {
					var poss = element.attr("name");
					if (poss != artistmeta.spotify.currentposs) {
						artistmeta.spotify.currentposs = poss;
						artistmeta.spotify.id = artistmeta.spotify.possibilities[poss].id;
						artistmeta.spotify.showing = 'albums';
						artistmeta.spotify.layout.clear_out();
						artistmeta.spotify.albums = null;
						artistmeta.spotify.related = null;
						artistmeta.spotify.populated = false;
						// browser.panel_updating(parent.nowplayingindex,'artist', {name: element.find('.spotpossname').html()});
						self.artist.populate();
					}
				} else if (element.hasClass('clickshowalbums') && artistmeta.spotify.showing != "albums") {
					artistmeta.spotify.spotiwidget.removeClass('masonry-initialised');
					artistmeta.spotify.spotiwidget.spotifyArtistThing('destroy');
					artistmeta.spotify.showing = "albums";
					getAlbums();
				} else if (element.hasClass('clickshowartists') && artistmeta.spotify.showing != "artists") {
					artistmeta.spotify.spotiwidget.removeClass('masonry-initialised');
					artistmeta.spotify.spotiwidget.spotifyAlbumThing('destroy');
					artistmeta.spotify.showing = "artists";
					element.addClass("bsel");
					getArtists();
				} else if (element.hasClass('clickaddtolistenlater')) {
					metaHandlers.addToListenLater(albummeta.spotify.album);
				} else if (element.hasClass('clickaddtocollection')) {
					metaHandlers.addAlbumUriToCollection(albummeta.spotify.album.uri);
				}  else if (element.hasClass('clickstartsingleradio')) {
					playlist.radioManager.load("singleArtistRadio", artistmeta.name);
				} else if (element.hasClass('clickstarttrackradio')) {
					debug.log("SPOTIFY","Starting Track Recommendations With",trackmeta.spotify.id);
					playlist.radioManager.load("spotiRecRadio", {seed_tracks: trackmeta.spotify.id, name: trackmeta.spotify.track.name});
				} else if (element.hasClass('clickstartartistradio')) {
					debug.log("SPOTIFY","Starting Artist Recommendations With",artistmeta.spotify.id);
					playlist.radioManager.load("spotiRecRadio", {seed_artists: artistmeta.spotify.id, name: artistmeta.spotify.artist.name});
				} else if (element.hasClass('clickstartgenreradio')) {
					let genre = element.next().val();
					debug.log("SPOTIFY","Starting Genre Radio With",genre);
					playlist.radioManager.load("spotiRecRadio", {seed_genres: genre, name: genre});
				}
			}

			function getAlbums() {
				artistmeta.spotify.spotichooser.find('.bsel').removeClass("bsel");
				artistmeta.spotify.spotichooser.find('.clickshowalbums').addClass("bsel");
				artistmeta.spotify.spinnerthing.fadeIn(100).addClass('wafflebanger-moving');
				if (!artistmeta.spotify.albums) {
					debug.mark("SPOTIFY PLUGIN", "Getting albums for Artist");
					spotify.artist.getAlbums(artistmeta.spotify.id, 'album,single', storeAlbums, self.artist.spotifyError, true)
				} else {
					doAlbums(artistmeta.spotify.albums);
				}
			}

			function getArtists() {
				artistmeta.spotify.spotichooser.find('.bsel').removeClass("bsel");
				artistmeta.spotify.spotichooser.find('.clickshowartists').addClass("bsel");
				artistmeta.spotify.spinnerthing.fadeIn(100).addClass('wafflebanger-moving');
				if (!artistmeta.spotify.related) {
					spotify.artist.getRelatedArtists(artistmeta.spotify.id, storeArtists, self.artist.spotifyError, true)
				} else {
					doArtists(artistmeta.spotify.related);
				}
			}

			function storeAlbums(data) {
				debug.mark("SPOTIFY PLUGIN", "Got albums for artist");
				artistmeta.spotify.albums = data;
				doAlbums(data);
			}

			function storeArtists(data) {
				artistmeta.spotify.related = data;
				doArtists(data);
			}

			function doAlbums(data) {
				if (artistmeta.spotify.showing == "albums" && data) {
					debug.mark(medebug,"Doing Albums For Artist",data);
					artistmeta.spotify.spotiwidget.removeClass('masonry-initialised');
					artistmeta.spotify.spotiwidget.spotifyAlbumThing({
						classes: 'nobwobbler nobalbum spotify_album_masonry selecotron',
						itemselector: 'nobwobbler',
						sub: null,
						showbiogs: false,
						layoutcallback: function() { artistmeta.spotify.spinnerthing.removeClass('wafflebanger-moving').hide(); browser.rePoint() },
						maxwidth: maxwidth,
						is_plugin: false,
						imageclass: 'spotify_album_image',
						data: data.items
					});
				}
			}

			function doArtists(data) {
				if (artistmeta.spotify.showing == "artists" && data) {
					debug.debug(medebug,"Doing Related Artists",data);
					artistmeta.spotify.spotiwidget.removeClass('masonry-initialised');
					artistmeta.spotify.spotiwidget.spotifyArtistThing({
						classes: 'nobwobbler nobartist spotify_album_masonry',
						itemselector: 'nobwobbler',
						sub: null,
						layoutcallback: function() { artistmeta.spotify.spinnerthing.removeClass('wafflebanger-moving').hide(); browser.rePoint() },
						is_plugin: false,
						imageclass: 'jalopy',
						maxalbumwidth: maxwidth,
						data: data.artists
					});

				}
			}

			function getArtistHTML(data, layout, artistmeta, artistobj) {

				debug.debug(medebug,"Making Artist Info From",data);
				if (data.error) {
					layout.display_error(data.error);
					layout.finish(null, null);
					return;
				}

				layout.make_possibility_chooser(artistmeta.spotify.possibilities, artistmeta.spotify.currentposs, artistmeta.name);

				let u = layout.add_sidebar_list(language.gettext("label_pop"), data.popularity);
				add_coll_button(u, 'clickstartsingleradio', 'icon-wifi', language.gettext('label_singleartistradio'));
				if (player.canPlay('spotify') && artistmeta.spotify.id) {
					add_coll_button(u, 'clickstartartistradio', 'icon-spotify-circled', language.gettext('label_radio_recommend',['Artist']));
				}
				if (data.genres && data.genres.length > 0) {
					do_genres(layout, u, data.genres);
				}

				if (data.images && data.images[0]) {
					layout.add_main_image(data.images[0].url);
				}

				artistobj.tryForAllMusicBio();

				artistmeta.spotify.spotichooser = layout.add_non_flow_box();
				let holderbox = $('<div>', {class: 'containerbox textunderline'}).appendTo(artistmeta.spotify.spotichooser);
				holderbox.append($('<div>', {class: 'fixed infoclick clickshowalbums bleft'}).html(language.gettext('label_albumsby')));
				holderbox.append($('<div>', {class: 'fixed infoclick clickshowartists bleft bmid'}).html(language.gettext('lastfm_simar')));
				artistmeta.spotify.spinnerthing = $('<div>', {class: 'wafflything wafflebanger invisible'}).appendTo(artistmeta.spotify.spotichooser);

				artistmeta.spotify.spotiwidget = layout.add_non_flow_box();
				artistmeta.spotify.spotiwidget.addClass('fullwidth medium_masonry_holder');

				layout.finish(data.external_urls.spotify, data.name);

			}

			this.track = function() {

				function spotifyResponse(data) {
					debug.debug(medebug, "Got Spotify Track Data",data);
					trackmeta.spotify.track = data;

					if (!albummeta.spotify.populated) {
						parent.updateData({
							spotify: {
								id: data.album.id,
								populated: true
							}
						}, albummeta);
						self.album.populate();
					}

					if (!artistmeta.spotify.populated) {
						for(var i in data.artists) {
							if (data.artists[i].name == artistmeta.name) {
								debug.debug(medebug,parent.nowplayingindex,"Found Spotify ID for", artistmeta.name);
								parent.updateData({
									spotify: {
										id: data.artists[i].id,
										populated: true
									}
								}, artistmeta);
								break;
							}
						}
						self.artist.populate();
					}

					debug.debug(medebug,"Spotify Data now looks like",artistmeta, albummeta, trackmeta);
					self.track.doBrowserUpdate();
				}

				return {

					populate: function() {
						trackmeta.spotify.layout = new info_sidebar_layout({title: trackmeta.name, type: 'track', source: me});
						trackmeta.spotify.id = parent.playlistinfo.file.substr(14, parent.playlistinfo.file.length);
						spotify.track.getInfo(trackmeta.spotify.id, spotifyResponse, self.track.spotifyError, true);
					},

					spotifyError: function(data) {
						debug.warn(medebug, "Spotify Error!", data);
						data.name = parent.playlistinfo.Title;
						data.external_urls = {spotify: ''};
						trackmeta.spotify.track = data;
						self.track.doBrowserUpdate()
						albummeta.spotify.layout = new info_layout_empty();
						self.artist.populate();
					},

					gotRecommendations: function(data) {
						debug.trace(medebug, 'Track Recommendations', data);
						trackmeta.spotify.recommendations = data;
						doRecommendations(trackmeta.spotify.layout, trackmeta);
					},

					spotifyRecError: function(data) {
						debug.warn(medebug,"Error getting track reccomendations",data);
					},

					doBrowserUpdate: function() {
						getTrackHTML(self.track, trackmeta, trackmeta.spotify.layout);
					}
				}

			}();

			this.album = function() {

				function spotifyResponse(data) {
					debug.debug(medebug, "Got Spotify Album Data",data);
					albummeta.spotify.album = data;
					self.album.doBrowserUpdate();
				}

				return {

					populate: function() {
						spotify.album.getInfo(albummeta.spotify.id, spotifyResponse, self.album.spotifyError, true);
					},

					spotifyError: function(data) {
						debug.error(medebug, "Spotify Error!",data);
						data.name = parent.playlistinfo.Album;
						data.external_urls = {spotify: ''};
						albummeta.spotify.album = data;
						self.album.doBrowserUpdate();
					},

					doBrowserUpdate: function() {
						getAlbumHTML(self.album, albummeta, albummeta.spotify.layout);
						infobar.markCurrentTrack();
					}

				}

			}();

			this.artist = function() {

				this.spinnterthing = null;

				function spotifyResponse(data) {
					debug.debug(medebug, "Got Spotify Artist Data", data);
					artistmeta.spotify.artist = data;
					self.artist.doBrowserUpdate();
				}

				function search(aname) {
					if (parent.playlistinfo.type == "stream" && artistmeta.name == "" && trackmeta.name == "") {
						debug.trace(medebug, "Searching Spotify for artist",albummeta.name)
						spotify.artist.find_possibilities(albummeta.name, searchResponse, searchFail, true);
					} else {
						debug.trace(medebug, "Searching Spotify for artist",aname)
						spotify.artist.find_possibilities(aname, searchResponse, searchFail, true);
					}
				}

				function searchResponse(data) {
					debug.debug(medebug,"Got Spotify Search Data",data);
					artistmeta.spotify.possibilities = data;
					if (artistmeta.spotify.possibilities.length > 0) {
						artistmeta.spotify.currentposs = 0;
						artistmeta.spotify.id = artistmeta.spotify.possibilities[0].id;
						artistmeta.spotify.showing = "albums";
					}
					if (artistmeta.spotify.id === undefined) {
						searchFail();
					} else {
						self.artist.populate();
					}
				}

				function searchFail() {
					debug.trace("SPOTIFY PLUGIN","Couldn't find anything for",artistmeta.name);
					parent.updateData({
						spotify: {
							artist: {
								error: language.gettext("label_noartistinfo"),
								name: artistmeta.name,
								external_urls: { spotify: '' }
							}
						}
					}, artistmeta);
					self.artist.doBrowserUpdate();
				}

				return {

					populate: function() {

						if (artistmeta.name == '' && trackmeta.name == '' && !artistmeta.spotify.populated) {
							artistmeta.spotify.populated = true;
							artistmeta.spotify.layout.display_error('There is no Artist to display information for');
							artistmeta.spotify.layout.finish(null, 'No Artist');
						}

						if (artistmeta.spotify.id == '' || artistmeta.spotify.populated)
							return;

						if (artistmeta.spotify.id === null) {
							search(trackmeta.artist);
						} else {
							parent.updateData(
								{
									spotify: {
										populated: true
									}
								},
								artistmeta
							);
							debug.mark('SPOTIFY', 'Getting Artist Data Using Id',artistmeta.spotify.id);
							spotify.artist.getInfo(artistmeta.spotify.id, spotifyResponse, self.artist.spotifyError, true);
						}
					},

					spotifyError: function(data) {
						debug.error(medebug, "Spotify Error!",data);
						data.external_urls = {spotify: ''};
						data.name = artistmeta.name;
						artistmeta.spotify.artist = data;
						self.artist.doBrowserUpdate();
					},

					tryForAllMusicBio: async function() {
						if (artistmeta.spotify.done_bio || artistmeta.allmusic.link == null || artistmeta.allmusic.link == '') {
							return;
						}
						artistmeta.spotify.done_bio = true;
						debug.debug(medebug,"Getting allmusic bio from",artistmeta.allmusic.link);
						try {

							fetch(
								'browser/backends/getambio.php',
								{
									signal: AbortSignal.timeout(60000),
									cache: 'no-store',
									method: 'POST',
									priority: 'low',
									body: JSON.stringify({url: artistmeta.allmusic.link})
								}
							).then(async function(response) {
								if (response.ok) {
									debug.log(medebug,"Got Allmusic Bio", response);
									var data = await response.text();
									artistmeta.spotify.layout.add_profile(data);
								} else {
									debug.log(medebug, 'Unable to find AllMusic bio', response);
								}
							});
						} catch (err) {
							debug.log(medebug,"Didn't Get Allmusic Bio",data);
						}
					},

					doBrowserUpdate: function() {
						getArtistHTML(artistmeta.spotify.artist, artistmeta.spotify.layout, artistmeta, self.artist);
						if (artistmeta.spotify.spotichooser)
							getAlbums();

					}
				}
			}();
		}
	}
}();

nowplaying.registerPlugin("spotify", info_spotify, "icon-spotify-circled", "button_infospotify");
