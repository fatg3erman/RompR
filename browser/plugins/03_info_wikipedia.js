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

		// Make the contents table links work. Remove the '#' because
		// when we click them we need to escape the selector (see uiHelper.goToBrowserSection)
		jq.find("a[href^='#']").each( function() {
			if (!$(this).hasClass('infoclick')) {
				var ref = $(this).attr('href');
				$(this).attr('name', ref.replace('#', ''));
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
		jq.find("a.mw-file-description[href^='/wiki/']").each( function() {
			var ref = $(this).attr('href');
			$(this).attr('href', '#');
			$(this).attr('name', domain+'.wikipedia.org/'+ref.replace(/\/wiki\//,''));
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
		jq.find('[style*=background-color]').removeInlineCss('background-color');
		jq.find('[style*=background-color]').removeInlineCss('background-color');
		jq.find('[style*=background]').removeInlineCss('background');
		jq.find('[style*=color]').removeInlineCss('color');

		jq.find('table.wikitable.floatright').removeInlineCss('width');

		// Remove these bits because they're a pain in the arse
		jq.find("li[class|='nv']").remove();

		return jq.html();

	}

	function formatLink(xml) {


		var xml_node = $(xml);
		var domain = xml_node.find('rompr > domain').text();
		var page = xml_node.find('rompr > page').text();
		if (domain == 'null' || page == 'null') {
			return null;
		}

		return 'http://'+domain+'.wikipedia.org/wiki/'+page;
	}

	function formatPage(xml) {
		var xml_node = $(xml);
		var page = xml_node.find('rompr > page').text();
		if (page == 'null')
			return null;

		debug.mark('WIKIPEDIA', "page Title is",page);

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
			// Don't initialise the links, musicbrainz will do it and we might overwrite
			// that data with our empty values.
			this.populate = function() {
				parent.updateData({
					wikipedia: {},
					triggers: {
						wikipedia: {
							link: self.artist.populate
						}
					}
				}, artistmeta);

				parent.updateData({
					wikipedia: {},
					triggers: {
						wikipedia: {
							link: self.album.populate
						}
					}
				}, albummeta);

				parent.updateData({
					wikipedia: {},
					triggers: {
						wikipedia: {
							link: self.track.populate
						}
					}
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
					debug.info('WIKIPEDIA', 'clickwikimedia');
					wikipedia.wikiMediaPopup(element, event);
				} else if (element.hasClass('clickwikilink')) {
					debug.info('WIKIPEDIA', 'clickwikilink');
					var link = decodeURIComponent(element.attr('name'));
					var title = decodeURIComponent(element.attr('title'));
					debug.debug("WIKI PLUGIN",parent.nowplayingindex,source,"clicked a wiki link",link);
					self[source].followLink(link, title);
				} else if (element.hasClass('clickwikicontents')) {
					debug.info('WIKIPEDIA', 'clickwikicontents');
					var section = element.attr('name');
					debug.debug("WIKI PLUGIN",parent.nowplayingindex,source,"clicked a contents link",section);
					uiHelper.goToBrowserSection(section);
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
						if (typeof artistmeta.wikipedia.layout == 'undefined') {
							if (artistmeta.name == '') {
								artistmeta.wikipedia.layout = new info_layout_empty();
							} else {
								artistmeta.wikipedia.layout = new info_html_layout({title: artistmeta.name, type: 'artist', source: me});
							}
						}

						if (artistmeta.wikipedia.link == '' || artistmeta.name == '')
							return;

						if (artistmeta.wikipedia.link === null) {
							debug.debug("WIKI ARTIST",parent.nowplayingindex,"Artist asked to populate but no link could be found. Trying a search for",artistmeta.name);
							wikipedia.search({	artist: artistmeta.name,
												disambiguation: artistmeta.disambiguation || ""
											},
											self.artist.wikiResponseHandler,
											self.artist.wikiResponseHandler);
							return;
						}
						debug.debug("WIKI ARTIST",parent.nowplayingindex,"artist is populating",artistmeta.wikipedia.link);
						wikipedia.getFullUri({	uri: artistmeta.wikipedia.link,
												term: artistmeta.name
											},
											self.artist.wikiResponseHandler,
											self.artist.wikiResponseHandler);
					},

					wikiResponseHandler: function(data) {
						debug.debug("WIKI ARTIST",parent.nowplayingindex,"got artist data for",artistmeta.name,data);
						if (data) {
							artistmeta.wikipedia.layout.finish(formatLink(data), formatPage(data), formatWiki(data));
						} else {
							artistmeta.wikipedia.layout.finish(null, artistmeta.name, formatNoResponse());
						}
					},

					followLink: function(link, title) {
						following_layout = new info_html_layout({title: title, type: 'artist', source: me});
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
						if (parent.playlistinfo.type == 'stream') {
							// Force us to search
							albummeta.wikipedia.link = null;
						}
						if (typeof albummeta.wikipedia.layout == 'undefined')
							albummeta.wikipedia.layout = new info_html_layout({title: albummeta.name, type: 'album', source: me});

						if (albummeta.wikipedia.link == '')
							return;

						if (albummeta.wikipedia.link === null) {
							debug.debug("WIKI ALBUM",parent.nowplayingindex,"No album link, trying a search");
							wikipedia.search({album: albummeta.name, albumartist: getSearchArtist()}, self.album.wikiResponseHandler, self.album.wikiResponseHandler);
							return;
						}
						debug.debug("WIKI ALBUM",parent.nowplayingindex,"album is populating",albummeta.wikipedia.link);
						wikipedia.getFullUri({	uri: albummeta.wikipedia.link,
												term: albummeta.name
											  },
											  self.album.wikiResponseHandler,
											  self.album.wikiResponseHandler
											);
					},

					wikiResponseHandler: function(data) {
						debug.debug("WIKI ALBUM",parent.nowplayingindex,"got album data for",albummeta.name,data);
						if (data) {
							albummeta.wikipedia.layout.finish(formatLink(data), formatPage(data), formatWiki(data));
						} else {
							albummeta.wikipedia.layout.finish(null, albummeta.name, formatNoResponse());
						}
					},

					followLink: function(link, title) {
						following_layout = new info_html_layout({title: title, type: 'album', source: me});
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
						if (typeof trackmeta.wikipedia.layout == 'undefined') {
							if (trackmeta.name == '') {
								trackmeta.wikipedia.layout = new info_layout_empty();
							} else {
								trackmeta.wikipedia.layout = new info_html_layout({title: trackmeta.name, type: 'track', source: me});
							}
						}

						if (trackmeta.wikipedia.link == '' || trackmeta.name == '')
							return;

						if (trackmeta.wikipedia.link === null) {
							debug.debug("WIKI TRACK",parent.nowplayingindex,"track asked to populate but no link could be found. Trying a search");
							wikipedia.search({track: trackmeta.name, trackartist: parent.playlistinfo.trackartist}, self.track.wikiResponseHandler, self.track.wikiResponseHandler);
							return;
						}
						debug.debug("WIKI TRACK",parent.nowplayingindex,"track is populating",trackmeta.wikipedia.link);
						wikipedia.getFullUri({	uri: trackmeta.wikipedia.link,
												term: trackmeta.name
											  },
											  self.track.wikiResponseHandler,
											  self.track.wikiResponseHandler
											);
					},

					wikiResponseHandler: function(data) {
						debug.debug("WIKI TRACK",parent.nowplayingindex,"got track data for",trackmeta.name);
						if (data) {
							trackmeta.wikipedia.layout.finish(formatLink(data), formatPage(data), formatWiki(data));
						} else {
							trackmeta.wikipedia.layout.finish(null, trackmeta.name, formatNoResponse());
						}
					},

					followLink: function(link, title) {
						following_layout = new info_html_layout({title: title, type: 'track', source: me});
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
