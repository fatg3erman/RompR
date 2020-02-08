var info_wikipedia = function() {

	var me = "wikipedia";

	function formatWiki(xml) {
		var xml_node = $(xml);
		var html = xml_node.find('parse > text').text();
		var domain = xml_node.find('rompr > domain').text();

		var jq = $('<div>'+html+'</div>');

		// Remove unwanted edit links
		jq.find("span.editsection").remove();
		jq.find("a.edit-page").remove();

		// Make external links open in a new tab
		jq.find("a[href^='http:']").attr("target", "_blank");
		jq.find("a[href^='//']").attr("target", "_blank");
		jq.find("a[href^='/w/']").each( function() {
			var ref = $(this).attr('href');
			$(this).attr('href', 'http://'+domain+'.wikipedia.org'+ref);
			$(this).attr("target", "_blank");
		});

		// Make the contents table links work
		jq.find("a[href^='#']").each( function() {
			if (!$(this).hasClass('infoclick')) {
				var ref = $(this).attr('href');
				$(this).attr('name', ref);
				$(this).attr("href", "#");
				$(this).addClass("infoclick clickwikicontents");
			}
		});

		// Redirect wiki image links so they go to our function to be displayed
		jq.find("a.image[href^='/wiki/']").each( function() {
			var ref = $(this).attr('href');
			$(this).attr('href', '#');
			$(this).attr('name', domain+'.wikipedia.org/'+ref.replace(/\/wiki\//,''));
			$(this).addClass('infoclick clickwikimedia');
		});
		jq.find("a.image[href^='//commons.wikimedia.org/']").each( function() {
			var ref = $(this).attr('href');
			$(this).attr('href', '#');
			$(this).attr('name', 'commons.wikimedia.org/'+ref.replace(/\/\/commons\.wikimedia\.org\/wiki\//,''));
			$(this).addClass('infoclick clickwikimedia');
		});

		// Redirect intra-wikipedia links so they go to our function
		jq.find("a[href^='/wiki/']").each( function() {
			var ref = $(this).attr('href');
			$(this).attr('href', '#');
			$(this).attr('name', domain+'/'+ref.replace(/\/wiki\//,''));
			$(this).addClass('infoclick clickwikilink');
		});

		// Remove inline colour styles on elements.
		// We do background color twice because some elements have been found
		// to have 2 background color styles applied.
		// if (prefs.theme == "Darkness.css" || prefs.theme == "TheBlues.css" || prefs.theme == "DarknessHiDPI.css" ) {
			jq.find('[style*=background-color]').removeInlineCss('background-color');
			jq.find('[style*=background-color]').removeInlineCss('background-color');
			jq.find('[style*=background]').removeInlineCss('background');
			jq.find('[style*=color]').removeInlineCss('color');
		// }
		// Remove these bits because they're a pain in the arse
		jq.find("li[class|='nv']").remove();

		return jq.html();

	}

	function formatLink(xml) {
		var xml_node = $('api',xml);
		return 'http://'+xml_node.find('rompr > domain').text()+'.wikipedia.org/wiki/'+xml_node.find('rompr > page').text();
	}

	function formatPage(xml) {
		var xml_node = $('api',xml);
		var page = xml_node.find('rompr > page').text();
		return page.replace(/_/g, ' ');
	}

	return {
		getRequirements: function(parent) {
			return ["musicbrainz"];
		},

		collection: function(parent, artistmeta, albummeta, trackmeta) {

			debug.debug("WIKI PLUGIN", "Creating data collection");

			var self = this;
			var displaying = false;

			this.populate = function() {
				self.artist.populate();
				self.album.populate();
				self.track.populate();
			}

			this.displayData = function() {
				displaying = true;
				self.artist.doBrowserUpdate();
				self.album.doBrowserUpdate();
				self.track.doBrowserUpdate();
			}

			this.stopDisplaying = function(waitingon) {
				displaying = false;
			}


			this.handleClick = function(source, element, event) {
				debug.debug("WIKI PLUGIN",parent.nowplayingindex,source,"is handling a click event");
				if (element.hasClass('clickwikimedia')) {
					wikipedia.wikiMediaPopup(element, event);
				} else if (element.hasClass('clickwikilink')) {
					var link = decodeURIComponent(element.attr('name'));
					debug.debug("WIKI PLUGIN",parent.nowplayingindex,source,"clicked a wiki link",link);
					self[source].followLink(link);
				} else if (element.hasClass('clickwikicontents')) {
					var section = element.attr('name');
					debug.debug("WIKI PLUGIN",parent.nowplayingindex,source,"clicked a contents link",section);
					layoutProcessor.goToBrowserSection(section);
				}
			}

			this.wikiGotFailed = function(data) {
				debug.warn("WIKI PLUGIN", "Failed to get Wiki Link",data);
			}

			function getSearchArtist() {
				return (albummeta.artist && albummeta.artist != "") ? albummeta.artist : parent.playlistinfo.trackartist;
			}

			this.artist = function() {

				var retries = 10;

				return {

					populate: function() {
						if (artistmeta.wikipedia === undefined) {
							artistmeta.wikipedia = {};
						}
						if (artistmeta.wikipedia.artistinfo === undefined) {
							if (artistmeta.wikipedia.artistlink === undefined) {
								debug.debug("WIKI PLUGIN",parent.nowplayingindex,"Artist asked to populate but no link yet");
								retries--;
								if (retries == 0) {
									debug.info("WIKI PLUGIN",parent.nowplayingindex,"Artist giving up waiting for poxy musicbrainz");
									artistmeta.wikipedia.artistlink = null;
									setTimeout(self.artist.populate, 200);
								} else {
									setTimeout(self.artist.populate, 2000);
								}
								return;
							}
							if (artistmeta.wikipedia.artistlink === null) {
								debug.debug("WIKI PLUGIN",parent.nowplayingindex,"Artist asked to populate but no link could be found. Trying a search");
								wikipedia.search({	artist: artistmeta.name,
													disambiguation: artistmeta.disambiguation || ""
												},
												self.artist.wikiResponseHandler,
												self.artist.wikiResponseHandler);
								return;
							}
							debug.debug("WIKI PLUGIN",parent.nowplayingindex,"artist is populating",artistmeta.wikipedia.artistlink);
							wikipedia.getFullUri({	uri: artistmeta.wikipedia.artistlink,
													term: artistmeta.name
												},
												self.artist.wikiResponseHandler,
												self.artist.wikiResponseHandler);
						} else {
							debug.trace("WIKI PLUGIN",parent.nowplayingindex,"artist is already populated",artistmeta.wikipedia.artistlink);
						}
					},

					wikiResponseHandler: function(data) {
						debug.debug("WIKI PLUGIN",parent.nowplayingindex,"got artist data for",artistmeta.name,data);
						if (data) {
							artistmeta.wikipedia.artistinfo = formatWiki(data);
							artistmeta.wikipedia.artistlink = formatLink(data);
						} else {
							artistmeta.wikipedia.artistinfo = '<h3 align="center">'+language.gettext("wiki_nothing")+'</h3>';
							artistmeta.wikipedia.artistlink = null;
						}

						self.artist.doBrowserUpdate();
					},

					doBrowserUpdate: function() {
						if (displaying && artistmeta.wikipedia.artistinfo !== undefined) {
							debug.debug("WIKI PLUGIN",parent.nowplayingindex,"artist was asked to display");
							browser.Update(
								null,
								'artist',
								me,
								parent.nowplayingindex,
								{ name: artistmeta.name,
								  link: artistmeta.wikipedia.artistlink,
								  data: artistmeta.wikipedia.artistinfo
								}
							);
						}
					},

					followLink: function(link) {
						wikipedia.getWiki(link, self.artist.gotWikiLink, self.wikiGotFailed);
					},

					gotWikiLink: function(data) {
						browser.speciaUpdate(
							me,
							'artist',
							{ name: formatPage(data),
							  link: formatLink(data),
							  data: formatWiki(data)
							}
						);
					}
				}
			}();

			this.album = function() {

				var retries = 12;

				return {

					populate: function() {
						if (albummeta.wikipedia === undefined) {
							albummeta.wikipedia = {};
						}
						if (albummeta.wikipedia.albumdata === undefined) {
							if (albummeta.wikipedia.albumlink === undefined) {
								debug.debug("WIKI PLUGIN",parent.nowplayingindex,"Album asked to populate but no link yet");
								retries--;
								if (retries == 0) {
									debug.info("WIKI PLUGIN",parent.nowplayingindex,"Album giving up waiting for poxy musicbrainz");
									albummeta.wikipedia.albumlink = null;
									setTimeout(self.album.populate, 200);
								} else {
									setTimeout(self.album.populate, 2000);
								}
								return;
							}
							if (albummeta.wikipedia.albumlink === null) {
								if (albummeta.musicbrainz.album_releasegroupid !== null) {
									debug.debug("WIKI PLUGIN",parent.nowplayingindex,"No album link found  ... trying the album release group");
									musicbrainz.releasegroup.getInfo(albummeta.musicbrainz.album_releasegroupid, '', self.album.mbRgHandler, self.album.mbRgHandler);
								} else {
									debug.debug("WIKI PLUGIN",parent.nowplayingindex,"No album link or release group link ... trying a search");
									wikipedia.search({album: albummeta.name, albumartist: getSearchArtist()}, self.album.wikiResponseHandler, self.album.wikiResponseHandler);
								}
								return;
							}
							debug.debug("WIKI PLUGIN",parent.nowplayingindex,"album is populating",albummeta.wikipedia.albumlink);
							wikipedia.getFullUri({	uri: albummeta.wikipedia.albumlink,
													term: albummeta.name
												  },
												  self.album.wikiResponseHandler,
												  self.album.wikiResponseHandler
												);
						} else {
							debug.trace("WIKI PLUGIN",parent.nowplayingindex,"album is already populated",albummeta.wikipedia.albumlink);
						}
					},

					wikiResponseHandler: function(data) {
						debug.debug("WIKI PLUGIN",parent.nowplayingindex,"got album data for",albummeta.name);
						if (data) {
							albummeta.wikipedia.albumdata = formatWiki(data);
							albummeta.wikipedia.albumlink = formatLink(data);
						} else {
							albummeta.wikipedia.albumdata = '<h3 align="center">'+language.gettext("wiki_nothing")+'</h3>';
							albummeta.wikipedia.albumlink = null;
						}
						self.album.doBrowserUpdate();
					},

					mbRgHandler: function(data) {
						debug.core("WIKI PLUGIN",parent.nowplayingindex,"got musicbrainz release group data for",albummeta.name, data);
						if (data.error) {
							debug.trace("WIKI PLUGIN",parent.nowplayingindex," ... MB error",data);
						} else {
							for (var i in data.relations) {
								if (data.relations[i].type == "wikipedia") {
									debug.trace("WIKI PLUGIN",parent.nowplayingindex,"has found a Wikipedia album link",data.relations[i].url.resource);
									albummeta.wikipedia.albumlink = data.relations[i].url.resource;
									self.album.populate();
									return;
								}
							}
						}
						albummeta.wikipedia.albumlink = null;
						albummeta.musicbrainz.album_releasegroupid = null;
						self.album.populate();
					},

					doBrowserUpdate: function() {
						if (displaying && albummeta.wikipedia.albumdata !== undefined) {
							debug.debug("WIKI PLUGIN",parent.nowplayingindex,"album was asked to display");
							browser.Update(
								null,
								'album',
								me,
								parent.nowplayingindex,
								{ name: albummeta.name,
								  link: albummeta.wikipedia.albumlink,
								  data: albummeta.wikipedia.albumdata
								}
							);
						}
					},

					followLink: function(link) {
						wikipedia.getWiki(link, self.album.gotWikiLink, self.wikiGotFailed);
					},

					gotWikiLink: function(data) {
						browser.speciaUpdate(me, 'album', { name: formatPage(data),
															link: formatLink(data),
															data: formatWiki(data)});
					}

				}
			}();

			this.track = function() {

				var retries = 15;

				return {

					populate: function() {
						if (trackmeta.wikipedia === undefined) {
							trackmeta.wikipedia = {};
						}
						if (trackmeta.wikipedia.trackdata === undefined) {
							if (trackmeta.wikipedia.tracklink === undefined) {
								debug.debug("WIKI PLUGIN",parent.nowplayingindex,"track asked to populate but no link yet");
								retries--;
								if (retries == 0) {
									debug.info("WIKI PLUGIN",parent.nowplayingindex,"Track giving up waiting for poxy musicbrainz");
									trackmeta.wikipedia.tracklink = null;
									setTimeout(self.track.populate, 200);
								} else {
									setTimeout(self.track.populate, 2000);
								}
								return;
							}
							if (trackmeta.wikipedia.tracklink === null) {
								debug.debug("WIKI PLUGIN",parent.nowplayingindex,"track asked to populate but no link could be found. Trying a search");
								wikipedia.search({track: trackmeta.name, trackartist: parent.playlistinfo.trackartist}, self.track.wikiResponseHandler, self.track.wikiResponseHandler);
								return;
							}
							debug.debug("WIKI PLUGIN",parent.nowplayingindex,"track is populating",trackmeta.wikipedia.tracklink);
							wikipedia.getFullUri({	uri: trackmeta.wikipedia.tracklink,
													term: trackmeta.name
												  },
												  self.track.wikiResponseHandler,
												  self.track.wikiResponseHandler
												);
						} else {
							debug.trace("WIKI PLUGIN",parent.nowplayingindex,"track is already populated",trackmeta.wikipedia.tracklink);
						}
					},

					wikiResponseHandler: function(data) {
						debug.debug("WIKI PLUGIN",parent.nowplayingindex,"got track data for",trackmeta.name);
						if (data) {
							trackmeta.wikipedia.trackdata = formatWiki(data);
							trackmeta.wikipedia.tracklink = formatLink(data);
						} else {
							trackmeta.wikipedia.trackdata = '<h3 align="center">'+language.gettext("wiki_nothing")+'</h3>';
							trackmeta.wikipedia.tracklink = null;
						}

						self.track.doBrowserUpdate();
					},

					doBrowserUpdate: function() {
						if (displaying && trackmeta.wikipedia.trackdata !== undefined) {
							debug.debug("WIKI PLUGIN",parent.nowplayingindex,"track was asked to display");
							browser.Update(
								null,
								'track',
								me,
								parent.nowplayingindex,
								{ name: trackmeta.name,
								  link: trackmeta.wikipedia.tracklink,
								  data: trackmeta.wikipedia.trackdata
								}
							);
						}
					},

					followLink: function(link) {
						wikipedia.getWiki(link, self.track.gotWikiLink, self.wikiGotFailed);
					},

					gotWikiLink: function(data) {
						browser.speciaUpdate( me, 'track',
							{ name: formatPage(data),
							  link: formatLink(data),
							  data: formatWiki(data)
							}
						);
					}

				}
			}();
		}
	}

}();

nowplaying.registerPlugin("wikipedia", info_wikipedia, "icon-wikipedia", "button_wikipedia");
