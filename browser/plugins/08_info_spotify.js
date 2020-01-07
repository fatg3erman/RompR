var info_spotify = function() {

	var me = "spotify";
	var medebug = "SPOTIFY PLUGIN";
	var maxwidth = 300;

	function getTrackHTML(data) {

		debug.debug(medebug,"Making Track Info From",data);
		if (data.error) {
			return '<h3 align="center">'+data.error+'</h3>';
		}

		var h = '<div class="holdingcell">';
		h += '<div class="standout stleft statsbox">';
		h += '<ul>';
		h += '<li><b>'+language.gettext("label_pop")+': </b>'+data.popularity+'</li>';
		if (player.canPlay('spotify')) {
			h += '<li>'+
				'<div class="containerbox menuitem infoclick clickstarttrackradio" style="padding-left:0px">'+
				'<div class="fixed alignmid"><i class="icon-wifi smallicon"></i></div>'+
				'<div class="expand">'+language.gettext('label_radio_recommend',['Track'])+'</div>'+
				'</div></li>';
		}
		h += '</ul>';
		h += '</div>';
		if (data.explicit) {
			h += '<i class="icon-explicit stright standout"></i>';
		}
		h += '</div>';
		h += '<div id="helpful_title"></div>';
		h += '<div id="helpful_tracks" class="holdingcell selecotron masonified4"></div>';
		return h;
	}

	function getAlbumHTML(data) {

		debug.debug(medebug,"Making Album Info From",data);
		if (data.error) {
			return '<h3 align="center">'+data.error+'</h3>';
		}
		var html = '<div class="containerbox standout info-detail-layout">';
		html += '<div class="info-box-expand info-box-list info-border-right">';
		html += '<ul>';
		html += '<li>'+language.gettext("label_pop")+': '+data.popularity+'</li>';
		html += '<li>'+language.gettext("lastfm_releasedate")+': '+data.release_date+'</li>';

		if (player.canPlay('spotify')) {

			html += '<li><div class="containerbox menuitem infoclick clickaddtolistenlater" style="padding-left:0px">'+
				'<div class="fixed alignmid">'+
				'<i class="icon-headphones smallicon"></i></div>'+
				'<div class="expand">'+language.gettext("label_addtolistenlater")+'</div>'+
				'</div></li>';

			html += '<li><div class="containerbox menuitem infoclick clickaddtocollection" style="padding-left:0px">'+
				'<div class="fixed alignmid">'+
				'<i class="icon-music smallicon"></i></div>'+
				'<div class="expand">'+language.gettext("label_addtocollection")+'</div>'+
				'</div></li>';

		}

		html += '</ul>';
		html += '</div>';

		html += '<div class="stumpy selecotron widermiddle">';
		html += spotifyTrackListing(data)+'</div>';
		html += '<div class="cleft narrowright">';
		if (data.images && data.images[0]) {
			html += '<img class="cnotshrinker infoclick clickzoomimage" src="getRemoteImage.php?url='+rawurlencode(data.images[0].url)+'" />';
		}
		html += '</div>';
		html += '</div>';
		return html;
	}

	function getArtistHTML(data, parent, artistmeta) {

		debug.debug(medebug,"Making Artist Info From",data);
		if (data.error) {
			return '<h3 align="center">'+data.error+'</h3>';
		}

		var h = "";

		if (artistmeta.spotify.possibilities && artistmeta.spotify.possibilities.length > 1) {
			h += '<div class="spotchoices clearfix">'+
			'<table><tr><td>'+
			'<div class="bleft tleft spotthing"><span class="spotpossname">All possibilities for "'+
				artistmeta.spotify.artist.name+'"</span></div>'+
			'</td><td>';
			for (var i in artistmeta.spotify.possibilities) {
				h += '<div class="tleft infoclick bleft ';
				if (i == artistmeta.spotify.currentposs) {
					h += 'bsel ';
				}
				h += 'clickchooseposs" name="'+i+'">';
				if (artistmeta.spotify.possibilities[i].image) {
					h += '<img class="spotpossimg title-menu" src="getRemoteImage.php?url='+rawurlencode(artistmeta.spotify.possibilities[i].image)+'" />';
				} else {
					h += '<img class="spotpossimg title-menu" src="newimages/artist-icon.png" />';
				}
				h += '<span class="spotpossname">'+artistmeta.spotify.possibilities[i].name+'</span>';
				h += '</div>';
			}
			h += '</td></tr></table>';
			h += '</div>';
		}

		h += '<div class="holdingcell">';
		h += '<div class="standout stleft statsbox"><ul><li><b>'+language.gettext("label_pop")+
			': </b>'+data.popularity+'</li>';
		h += '<li><div class="containerbox menuitem infoclick clickstartsingleradio" style="padding-left:0px">'+
			'<div class="fixed alignmid">'+
			'<i class="icon-wifi smallicon"></i></div>'+
			'<div class="expand">'+language.gettext("label_singleartistradio")+'</div>'+
			'</div></li>';
		if (player.canPlay('spotify')) {
			h += '<li>'+
				'<div class="containerbox menuitem infoclick clickstartradio" style="padding-left:0px">'+
				'<div class="fixed alignmid">'+
				'<i class="icon-wifi smallicon"></i></div>'+
				'<div class="expand">'+language.gettext("lastfm_simar")+'</div>'+
				'</div></li>';
			h += '<li>'+
				'<div class="containerbox menuitem infoclick clickstartartistradio" style="padding-left:0px">'+
				'<div class="fixed alignmid">'+
				'<i class="icon-wifi smallicon"></i></div>'+
				'<div class="expand">'+language.gettext('label_radio_recommend',['Artist'])+'</div>'+
				'</div></li>';
		}
		h += '</ul></div>';
		if (data.images && data.images[0]) {
			h += '<img class="stright standout cshrinker infoclick clickzoomimage" '+
				'src="getRemoteImage.php?url='+rawurlencode(data.images[0].url)+'" />';
		}

		h += '<div id="artistbio" class="minwidthed"></div>';
		h += '</div>';
		h += '<div class="containerbox textunderline" id="bumhole"><div class="fixed infoclick clickshowalbums bleft';
		if (artistmeta.spotify.showing == "albums") {
			h += ' bsel';
		}
		h += '">'+language.gettext("label_albumsby") + '</div>' +
			'<div class="fixed infoclick clickshowartists bleft bmid';
		if (artistmeta.spotify.showing == "artists") {
			h += ' bsel';
		}

		h += '">'+language.gettext("lastfm_simar")+'</div>' +
			'<div class="fixed"><i id="hibbert" class="svg-square title-menu invisible">'+
			'</i></div></div>' +
			'<div class="fullwidth masonified2" id="artistalbums"></div>';
		return h;

	}

	function findDisplayPanel(element) {
		var c = element;
		while (!c.hasClass('nobwobbler')) {
			c = c.parent();
		}
		if (c.hasClass('nobalbum')) {
			debug.trace(medebug,"Opening Album Panel Via Widget");
			$('#artistalbums').spotifyAlbumThing('handleClick', element);
			return true;
		} else if (c.hasClass('nobartist')) {
			debug.trace(medebug,"Opening Artist Panel Via Widget");
			$('#artistalbums').spotifyArtistThing('handleClick', element);
			return true;
		} else {
			debug.warn(medebug,"Click On Unknown Element!",element);
			return false;
		}
	}

	return {

		getRequirements: function(parent) {
			return ["musicbrainz"];
		},

		collection: function(parent, artistmeta, albummeta, trackmeta) {

			debug.debug(medebug, "Creating data collection");

			var self = this;
			var displaying = false;
			if (artistmeta.spotify === undefined) {
				artistmeta.spotify = {};
			}
			if (artistmeta.spotify.showing === undefined) {
				artistmeta.spotify.showing = "albums";
			}

			this.populate = function() {
				self.track.populate();
			}

			this.displayData = function() {
				displaying = true;
				self.artist.doBrowserUpdate();
				self.album.doBrowserUpdate();
				self.track.doBrowserUpdate();
			}

			this.stopDisplaying = function() {
				displaying = false;
			}

			this.handleClick = function(source, element, event) {
				debug.debug(medebug,parent.nowplayingindex,source,"is handling a click event");
				if (element.hasClass('clickzoomimage')) {
					imagePopup.create(element, event, element.attr("src"));
				} else if (element.hasClass('clickspotifywidget')) {
					findDisplayPanel(element);
				} else if (element.hasClass('clickchooseposs')) {
					var poss = element.attr("name");
					if (poss != artistmeta.spotify.currentposs) {
						artistmeta.spotify = {
							currentposs: poss,
							possibilities: artistmeta.spotify.possibilities,
							id: artistmeta.spotify.possibilities[poss].id,
							showing: "albums"
						}
						self.artist.force = true;
						self.artist.populate();
					}
				} else if (element.hasClass('clickshowalbums') && artistmeta.spotify.showing != "albums") {
					$('#artistalbums').spotifyArtistThing('destroy');
					artistmeta.spotify.showing = "albums";
					$("#bumhole .bsel").removeClass("bsel");
					element.addClass("bsel");
					getAlbums();
				} else if (element.hasClass('clickshowartists') && artistmeta.spotify.showing != "artists") {
					$('#artistalbums').spotifyAlbumThing('destroy');
					artistmeta.spotify.showing = "artists";
					$("#bumhole .bsel").removeClass("bsel");
					element.addClass("bsel");
					getArtists();
				} else if (element.hasClass('clickaddtolistenlater')) {
					metaHandlers.addToListenLater(albummeta.spotify.album);
				} else if (element.hasClass('clickaddtocollection')) {
					metaHandlers.fromSpotifyData.addAlbumTracksToCollection(albummeta.spotify.album, artistmeta.spotify.artist.name);
				} else if (element.hasClass('clickstartradio')) {
					playlist.radioManager.load("artistRadio", 'spotify:artist:'+artistmeta.spotify.id);
				}  else if (element.hasClass('clickstartsingleradio')) {
					playlist.radioManager.load("singleArtistRadio", artistmeta.name);
				} else if (element.hasClass('clickstarttrackradio')) {
					debug.log("SPOTIFY","Starting Track Recommendations With",trackmeta.spotify.id);
					playlist.radioManager.load("spotiTrackRadio", {seed_tracks: trackmeta.spotify.id, name: trackmeta.spotify.track.name});
				} else if (element.hasClass('clickstartartistradio')) {
					debug.log("SPOTIFY","Starting Artist Recommendations With",artistmeta.spotify.id);
					playlist.radioManager.load("spotiTrackRadio", {seed_artists: artistmeta.spotify.id, name: artistmeta.spotify.artist.name});
				}
			}

			function getAlbums() {
				$("#hibbert").makeSpinner();
				if (artistmeta.spotify.albums === undefined) {
					spotify.artist.getAlbums(artistmeta.spotify.id, 'album,single', storeAlbums, self.artist.spotifyError, true)
				} else {
					doAlbums(artistmeta.spotify.albums);
				}
			}

			function getArtists() {
				$("#hibbert").makeSpinner()
				if (artistmeta.spotify.related === undefined) {
					spotify.artist.getRelatedArtists(artistmeta.spotify.id, storeArtists, self.artist.spotifyError, true)
				} else {
					doArtists(artistmeta.spotify.related);
				}
			}

			function storeAlbums(data) {
				artistmeta.spotify.albums = data;
				doAlbums(data);
			}

			function storeArtists(data) {
				artistmeta.spotify.related = data;
				doArtists(data);
			}

			function doAlbums(data) {
				debug.debug(medebug,"DoAlbums",artistmeta.spotify.showing, displaying);
				if (artistmeta.spotify.showing == "albums" && displaying && data) {
					debug.debug(medebug,"Doing Albums For Artist",data);
					$('#artistalbums').spotifyAlbumThing({
						classes: 'nobwobbler nobalbum tagholder2 selecotron',
						itemselector: 'nobwobbler',
						sub: null,
						showbiogs: false,
						layoutcallback: function() { $("#hibbert").stopSpinner(); browser.rePoint() },
						maxwidth: maxwidth,
						is_plugin: false,
						imageclass: 'masochist',
						data: data.items
					});
				}
			}

			function doArtists(data) {
				if (artistmeta.spotify.showing == "artists" && displaying && data) {
					debug.debug(medebug,"Doing Related Artists",data);
					$('#artistalbums').spotifyArtistThing({
						classes: 'nobwobbler nobartist tagholder2',
						itemselector: 'nobwobbler',
						sub: null,
						layoutcallback: function() { $("#hibbert").stopSpinner(); browser.rePoint() },
						is_plugin: false,
						imageclass: 'jalopy',
						maxalbumwidth: maxwidth,
						data: data.artists
					});

				}
			}

			this.track = function() {

				function spotifyResponse(data) {
					debug.debug(medebug, "Got Spotify Track Data",data);
					if (trackmeta.spotify.track === undefined) {
						trackmeta.spotify.track = data;
					}
					if (albummeta.spotify === undefined) {
						albummeta.spotify = {id: data.album.id};
					}
					for(var i in data.artists) {
						if (data.artists[i].name == artistmeta.name) {
							debug.debug(medebug,parent.nowplayingindex,"Found Spotify ID for", artistmeta.name);
							artistmeta.spotify.id = data.artists[i].id;
						}
					}
					debug.debug(medebug,"Spotify Data now looks like",artistmeta, albummeta, trackmeta);
					self.track.doBrowserUpdate();
					self.artist.populate();
				}

				function gotTrackRecommendations(data) {
					trackmeta.spotify.recommendations = data;
					doRecommendations(data);
				}

				function doRecommendations(data) {
					$('#helpful_title').html('<div class="textunderline notthere"><h3>'+language.gettext('discover_now', [trackmeta.spotify.track.name])+'</h3></div>');
					for (var i in data.tracks) {
						var x = $('<div>', {class: 'arsecandle tagholder4 clickable draggable clicktrack playable notthere', name: rawurlencode(data.tracks[i].uri)}).appendTo($('#helpful_tracks'));
						var a = data.tracks[i].album;
						var img = '';
						if (a.images && a.images[0]) {
							img = 'getRemoteImage.php?url='+rawurlencode(a.images[0].url);
							for (var j in a.images) {
								if (a.images[j].width <= maxwidth) {
									img = 'getRemoteImage.php?url='+rawurlencode(a.images[j].url);
									break;
								}
							}
							img += '&rompr_resize_size=smallish';
						} else {
							img = 'newimages/spotify-icon.png';
						}
						x.append('<img class="cheeseandfish" src="'+img+'" /></div>');

						var an = new Array();
						for (var j in data.tracks[i].artists) {
							an.push(data.tracks[i].artists[j].name);
						}

						x.append('<div>'+concatenate_artist_names(an)+'<br />'+data.tracks[i].name+'</div>');
					}
					$('#helpful_tracks').imagesLoaded(doBlockLayout);

				}

				function doBlockLayout() {
					debug.log(medebug,"Track Images Have Loaded");
					browser.rePoint($('#helpful_tracks'),{ itemSelector: '.arsecandle', columnWidth: '.arsecandle', percentPosition: true});
					donetheother = true;
					setDraggable('#helpful_tracks');
					$('#helpful_title').find('.notthere').removeClass('notthere');
					$('#helpful_tracks').find('.notthere').removeClass('notthere');
				}

				return {

					populate: function() {
						if (trackmeta.spotify === undefined || artistmeta.spotify.id === undefined) {
							if (parent.playlistinfo.file.substring(0,8) !== 'spotify:') {
								self.track.doBrowserUpdate()
								self.artist.populate();
								self.album.populate();
							} else {
								if (trackmeta.spotify === undefined) {
									trackmeta.spotify = {id: parent.playlistinfo.file.substr(14, parent.playlistinfo.file.length) };
								}
								spotify.track.getInfo(trackmeta.spotify.id, spotifyResponse, self.track.spotifyError, true);
							}
						} else {
							self.artist.populate();
						}
					},

					spotifyError: function(data) {
						debug.warn(medebug, "Spotify Error!", data);
						data.name = parent.playlistinfo.Title;
						data.external_urls = {spotify: ''};
						trackmeta.spotify.track = data;
						if (albummeta.spotify === undefined) {
							albummeta.spotify = {};
						}
						self.track.doBrowserUpdate()
						self.artist.populate();
					},

					spotifyRecError: function(data) {
						debug.warn(medebug,"Error getting track reccomendations",data);
					},

					doBrowserUpdate: function() {
						var accepted = false;
						if (displaying && trackmeta.spotify !== undefined && trackmeta.spotify.track !== undefined) {
							debug.debug(medebug,parent.nowplayingindex,"track was asked to display");
							accepted = browser.Update(
								null,
								'track',
								me,
								parent.nowplayingindex,
								{ name: trackmeta.spotify.track.name,
								  link: trackmeta.spotify.track.external_urls.spotify,
								  data: getTrackHTML(trackmeta.spotify.track)
								}
							);
						} else if (parent.playlistinfo.file.substring(0,8) !== 'spotify:') {
							browser.Update(null, 'track', me, parent.nowplayingindex, { name: "",
													link: "",
													data: null
													}
							);
						}
						if (accepted && !trackmeta.spotify.track.error) {
							if (trackmeta.spotify.recommendations) {
								doRecommendations(trackmeta.spotify.recommendations);
							} else {
								var params = { limit: 8 }
								params.seed_tracks = trackmeta.spotify.id;
								spotify.recommendations.getRecommendations(params, gotTrackRecommendations, self.track.spotifyRecError);
							}
						}
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
						if (albummeta.spotify === undefined || albummeta.spotify.album === undefined ) {
							if (parent.playlistinfo.file.substring(0,8) !== 'spotify:') {
								self.album.doBrowserUpdate();
							} else {
								spotify.album.getInfo(albummeta.spotify.id, spotifyResponse, self.album.spotifyError, true);
							}
						}
					},

					spotifyError: function(data) {
						debug.error(medebug, "Spotify Error!",data);
						data.name = parent.playlistinfo.Album;
						data.external_urls = {spotify: ''};
						albummeta.spotify.album = data;
						self.album.doBrowserUpdate();
					},

					doBrowserUpdate: function() {
						if (displaying && albummeta.spotify !== undefined && albummeta.spotify.album !== undefined) {
							debug.debug(medebug,parent.nowplayingindex,"album was asked to display");
							var accepted = browser.Update(
								null,
								'album',
								me,
								parent.nowplayingindex,
								{ name: albummeta.spotify.album.name,
								  link: albummeta.spotify.album.external_urls.spotify,
								  data: getAlbumHTML(albummeta.spotify.album)
								}
							);
						} else if (parent.playlistinfo.file.substring(0,8) !== 'spotify:') {
							browser.Update(null, 'album', me, parent.nowplayingindex, { name: "",
													link: "",
													data: null
													}
							);
						}
						infobar.markCurrentTrack();
					}

				}

			}();

			this.artist = function() {

				var triedWithoutBrackets = 0;
				var retries = 10;
				var searchingfor = artistmeta.name;

				function spotifyResponse(data) {
					debug.debug(medebug, "Got Spotify Artist Data", data);
					artistmeta.spotify.artist = data;
					self.artist.doBrowserUpdate();
					self.album.populate();
				}

				function search(aname) {
					if (parent.playlistinfo.type == "stream" && artistmeta.name == "" && trackmeta.name == "") {
						debug.trace(medebug, "Searching Spotify for artist",albummeta.name)
						spotify.artist.search(albummeta.name, searchResponse, searchFail, true);
					} else {
						debug.trace(medebug, "Searching Spotify for artist",aname)
						spotify.artist.search(aname, searchResponse, searchFail, true);
					}
				}

				function searchResponse(data) {
					debug.debug(medebug,"Got Spotify Search Data",data);
					var match = searchingfor.toLowerCase();
					artistmeta.spotify.possibilities = new Array();
					for (var i in data.artists.items) {
						if (data.artists.items[i].name.toLowerCase() == match) {
							artistmeta.spotify.possibilities.push({
								name: data.artists.items[i].name,
								id: data.artists.items[i].id,
								image: (data.artists.items[i].images &&
									data.artists.items[i].images.length > 0) ?
										data.artists.items[i].images[data.artists.items[i].images.length-1].url : null
							});
						}
					}
					if (artistmeta.spotify.possibilities.length == 0 && data.artists.items.length == 1) {
						// only one match returned, it wasn't an exact match, but use it anyway
						artistmeta.spotify.possibilities.push({
							name: data.artists.items[0].name,
							id: data.artists.items[0].id,
							image: (data.artists.items[0].images &&
								data.artists.items[0].images.length > 0) ?
							data.artists.items[0].images[data.artists.items[0].images.length-1].url : null
						});
					}
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
					var test;
					switch (triedWithoutBrackets) {
						case 0:
							triedWithoutBrackets = 1;
							test = artistmeta.name.replace(/ \(+.+?\)+$/, '');
							if (test != artistmeta.name) {
								searchingfor = test;
								debug.trace("SPOTIFY PLUGIN","Searching instead for",test);
								search(test);
								return;
							}
							// Fall Through

						case 1:
							triedWithoutBrackets = 2;
							test = artistmeta.name.split(/ & | and /)[0];
							if (test != artistmeta.name) {
								searchingfor = test;
								debug.trace("SPOTIFY PLUGIN","Searching instead for",test);
								search(test);
								return;
							}
							// Fall Through

						default:
							artistmeta.spotify = { artist: {    error: '<h3 align="center">'+
																	language.gettext("label_noartistinfo")+
																	'</h3>',
																name: artistmeta.name,
																external_urls: { spotify: '' }
															}
												};
							self.artist.doBrowserUpdate();
							break;

					}
				}

				return {

					force: false,

					populate: function() {
						if (artistmeta.spotify.id === undefined) {
							search(artistmeta.name);
						} else {
							if (artistmeta.spotify.artist === undefined) {
								spotify.artist.getInfo(artistmeta.spotify.id, spotifyResponse, self.artist.spotifyError, true);
							} else {
								self.album.populate();
							}
						}
					},

					spotifyError: function(data) {
						debug.error(medebug, "Spotify Error!",data);
						data.external_urls = {spotify: ''};
						data.name = artistmeta.name;
						artistmeta.spotify.artist = data;
						self.artist.doBrowserUpdate();
						self.album.populate();
					},

					tryForAllmusicBio: function() {
						if (typeof artistmeta.allmusic == 'undefined' || typeof artistmeta.allmusic.artistlink === 'undefined') {
							debug.debug(medebug,"Allmusic artist link not back yet");
							retries--;
							if (retries > 0) {
								setTimeout(self.artist.tryForAllmusicBio, 2000);
							} else {
								debug.info(medebug,"Artist giving up waiting for musicbrainz");
							}
						} else if (artistmeta.allmusic.artistlink === null) {
							debug.info(medebug,"No Allmusic artist bio link found");
						} else {
							debug.debug(medebug,"Getting allmusic bio from",artistmeta.allmusic.artistlink);
						  $.post('browser/backends/getambio.php', {url: artistmeta.allmusic.artistlink})
							 .done( function(data) {
								debug.debug(medebug,"Got Allmusic Bio");
								if (displaying) $("#artistbio").html(data);
							 })
							 .fail( function(data) {
								debug.warn(medebug,"Didn't Get Allmusic Bio",data);
								 if (displaying) $("#artistbio").html("");
							 });
						}
					},

					doBrowserUpdate: function() {
						if (displaying && artistmeta.spotify !== undefined && artistmeta.spotify.artist !== undefined) {
							debug.debug(medebug,parent.nowplayingindex,"artist was asked to display");
							var accepted = browser.Update(
								null,
								'artist',
								me,
								parent.nowplayingindex,
								{ name: artistmeta.spotify.artist.name,
								  link: artistmeta.spotify.artist.external_urls.spotify,
								  data: getArtistHTML(artistmeta.spotify.artist, parent, artistmeta)
								},
								false,
								self.artist.force
							);
							if (accepted && artistmeta.spotify.artist.error == undefined) {
								self.artist.tryForAllmusicBio();
								if (artistmeta.spotify.showing == "albums") {
									getAlbums();
								} else {
									getArtists();
								}
							}
						}
					}
				}
			}();
		}
	}
}();

nowplaying.registerPlugin("spotify", info_spotify, "icon-spotify-circled", "button_infospotify");
