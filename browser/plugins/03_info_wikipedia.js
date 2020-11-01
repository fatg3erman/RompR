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
		var domain = xml_node.find('rompr > domain').text();
		var page = xml_node.find('rompr > page').text();
		if (domain == 'null' || page== 'null')
			return null;

		return 'http://'+domain+'.wikipedia.org/wiki/'+page;
	}

	function formatPage(xml) {
		var xml_node = $('api',xml);
		var page = xml_node.find('rompr > page').text();
		if (page == 'null')
			return null;

		return page.replace(/_/g, ' ');
	}

	function formatNoResponse() {
		return '<h3 align="center">'+language.gettext("wiki_nothing")+'</h3>';
	}

	return {
		getRequirements: function(parent) {
			return ["musicbrainz"];
		},

		collection: function(parent, artistmeta, albummeta, trackmeta) {

			debug.debug("WIKI PLUGIN", "Creating data collection");

			var self = this;

			this.populate = function() {
				parent.updateData({
					wikipedia: {}
				}, artistmeta);

				parent.updateData({
					wikipedia: {}
				}, albummeta);

				parent.updateData({
					wikipedia: {}
				}, trackmeta);

				if (typeof artistmeta.wikipedia.layout == 'undefined')
					self.artist.populate();

				if (typeof albummeta.wikipedia.layout == 'undefined')
					self.album.populate();

				if (typeof trackmeta.wikipedia.layout == 'undefined')
					self.track.populate();
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

				var following_layout;

				return {

					populate: async function() {
						artistmeta.wikipedia.layout = new info_html_layout({title: artistmeta.name, type: 'artist', source: me});
						while (typeof artistmeta.wikipedia.artistlink == 'undefined') {
							await new Promise(t => setTimeout(t, 500));
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
					},

					wikiResponseHandler: function(data) {
						debug.debug("WIKI PLUGIN",parent.nowplayingindex,"got artist data for",artistmeta.name,data);
						if (data) {
							artistmeta.wikipedia.layout.finish(formatLink(data), formatPage(data), formatWiki(data));
						} else {
							artistmeta.wikipedia.layout.finish(null, artistmeta.name, formatNoResponse());
						}
					},

					followLink: function(link) {
						following_layout = new info_html_layout({title: 'Wikipedia...', type: 'artist', source: me});
						nowplaying.special_update(me, 'artist', following_layout);
						wikipedia.getWiki(link, self.artist.gotWikiLink, self.wikiGotFailed);
					},

					gotWikiLink: async function(data) {
						following_layout.finish(formatLink(data), formatPage(data), formatWiki(data));
						// The browser. the FUCKING browser is adding inline css to all the <a> tags.
						// Chrome. What the actrual fuck?
						await new Promise(t => setTimeout(t, 1000));
						$('#artistinformation').find('a[style*=cursor]').removeInlineCss('cursor');
					}
				}
			}();

			this.album = function() {

				var following_layout;

				return {

					populate: async function() {
						if (typeof albummeta.wikipedia.layout == 'undefined')
							albummeta.wikipedia.layout = new info_html_layout({title: albummeta.name, type: 'album', source: me});

						while (typeof albummeta.wikipedia.albumlink == 'undefined') {
							await new Promise(t => setTimeout(t, 500));
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
					},

					wikiResponseHandler: function(data) {
						debug.debug("WIKI PLUGIN",parent.nowplayingindex,"got album data for",albummeta.name,data);
						if (data) {
							albummeta.wikipedia.layout.finish(formatLink(data), formatPage(data), formatWiki(data));
						} else {
							albummeta.wikipedia.layout.finish(null, albummeta.name, formatNoResponse());
						}
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

					followLink: function(link) {
						following_layout = new info_html_layout({title: 'Wikipedia...', type: 'album', source: me});
						nowplaying.special_update(me, 'album', following_layout);
						wikipedia.getWiki(link, self.album.gotWikiLink, self.wikiGotFailed);
					},

					gotWikiLink: async function(data) {
						following_layout.finish(formatLink(data), formatPage(data), formatWiki(data));
						// The browser. the FUCKING browser is adding inline css to all the <a> tags.
						// Chrome. What the actrual fuck?
						await new Promise(t => setTimeout(t, 1000));
						$('#albuminformation').find('a[style*=cursor]').removeInlineCss('cursor');
					}

				}
			}();

			this.track = function() {

				var following_layout;

				return {

					populate: async function() {
						trackmeta.wikipedia.layout = new info_html_layout({title: trackmeta.name, type: 'track', source: me});
						while (typeof trackmeta.wikipedia.tracklink == 'undefined') {
							await new Promise(t => setTimeout(t, 500));
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
					},

					wikiResponseHandler: function(data) {
						debug.debug("WIKI PLUGIN",parent.nowplayingindex,"got track data for",trackmeta.name);
						if (data) {
							trackmeta.wikipedia.layout.finish(formatLink(data), formatPage(data), formatWiki(data));
						} else {
							trackmeta.wikipedia.layout.finish(null, trackmeta.name, formatNoResponse());
						}
					},

					followLink: function(link) {
						following_layout = new info_html_layout({title: 'Wikipedia...', type: 'track', source: me});
						nowplaying.special_update(me, 'track', following_layout);
						wikipedia.getWiki(link, self.track.gotWikiLink, self.wikiGotFailed);
					},

					gotWikiLink: async function(data) {
						following_layout.finish(formatLink(data), formatPage(data), formatWiki(data));
						// The browser. the FUCKING browser is adding inline css to all the <a> tags.
						// Chrome. What the actrual fuck?
						await new Promise(t => setTimeout(t, 1000));
						$('#trackinformation').find('a[style*=cursor]').removeInlineCss('cursor');
					}

				}
			}();
		}
	}

}();

nowplaying.registerPlugin("wikipedia", info_wikipedia, "icon-wikipedia", "button_wikipedia");
