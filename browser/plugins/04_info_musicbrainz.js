var info_musicbrainz = function() {

	var me = "musicbrainz";
	var medebug = "MBNZ PLUGIN";

	function getYear(data) {
		try {
			var date = data['first-release-date'] || data.date;
			if (!date) {
				var t = data.title;
				var m = t.match(/^(\d\d\d\d)/);
				date = m[0];
			}
			var d = new Date(date);
			var y = d.getFullYear();
			if (!y) { y = 0 }
			return parseInt(y);
		} catch(err) {
			return 0;
		}
	}

	function nozeros(x) {
		return (x == 0) ? '-' : x;
	}

	function albumsbyyear(a, b) {
		var year_a = getYear(a);
		var year_b = getYear(b);
		if (year_a == year_b) { return 0 }
		return (year_a > year_b) ? 1 : -1;
	}

	function getArtistHTML(layout, data) {
		if (data.error) {
			layout.display_error(data.error);
			layout.finish(null, null);
			return;
		}

		layout.add_sidebar_list('', data.disambiguation);

		if (data.type)
			layout.add_sidebar_list(language.gettext("title_type"), data.type);

		if (data.aliases && data.aliases.length > 0) {
			let u = layout.add_sidebar_list(language.gettext("discogs_aliases"));
			data.aliases.forEach(function(alias) {
				u.append($('<li>').html(alias.name));
			});
		}

		if (data.begin_area && data.area) {
			layout.add_sidebar_list(language.gettext("musicbrainz_origin"), data.begin_area.name+", "+data.area.name);
		} else if (data.area) {
			layout.add_sidebar_list(language.gettext("musicbrainz_origin"), data.area.name);
		}

		if (data['life-span'] && data['life-span'].begin !== null) {
			layout.add_sidebar_list(language.gettext("musicbrainz_active"), data['life-span'].begin+" - "+(data['life-span'].end || language.gettext("musicbrainz_now")));
		}

		if (data.rating && data.rating.value !== null) {
			layout.add_sidebar_list(language.gettext("musicbrainz_rating"), data.rating.value+"/5 from "+data.rating['votes-count']+' votes');
		}

		getURLs(layout, data.relations);

		if (data.annotation) {
			layout.add_flow_box_header({title: language.gettext("musicbrainz_notes")});
			layout.add_flow_box(data.annotation.replace(/\n/, '<br>').replace(/\[(.*?)\|(.*?)\]/g, '<a href="$1" target="_blank">$2</a>'));
		}

		doTags(layout, data.tags);

		var bandMembers = data.relations.filter(r => (r.type == 'member of band' && r.direction == 'backward'));
		var memberOf = data.relations.filter(r => (r.type == 'member of band' && r.direction != 'backward'));
		if (bandMembers.length > 0) {
			layout.add_flow_box_header({wide: true, title: language.gettext("discogs_bandmembers")});
			getMembers(layout, bandMembers);
		}
		if (memberOf.length > 0) {
			layout.add_flow_box_header({wide: true, title: language.gettext("discogs_memberof")});
			getMembers(layout, memberOf);
		}

		layout.add_dropdown_box(
			layout.add_flow_box_header(''),
			'clickdodiscography',
			data.id,
			'discography_'+data.id,
			language.gettext("discogs_discography", [data.name.toUpperCase()])
		)

		layout.finish('http://musicbrainz.org/artist/'+data.id, data.name);

	}

	function doTags(layout, tags) {
		if (tags && tags.length > 0) {
			layout.add_flow_box_header({title: language.gettext("musicbrainz_tags")});
			let box = layout.add_flow_box('');
			tags.forEach(function(tag) {
				box.append($('<span>', {class: 'mbtag'}).html(tag.name).append()).append(' ');
			});
		}
	}

	function getMembers(layout, data) {
		var artists = data.map(function(a) {
			if (a.a_years) {
				a.a_years += " "+doSpan(a);
			} else {
				a.a_years = doSpan(a);
			}
			return a;
		});
		artists.forEach(function(artist) {
			layout.add_dropdown_box(layout.add_flow_box(''), 'clickdoartist', artist.artist.id, artist.artist.id, artist.artist.name+' '+artist.a_years);
		});
	}

	function doSpan(data) {
		if (!data.begin)
			return "";

		var by = new Date(data.begin);
		var ey = new Date(data.end);
		var tby = by.getFullYear() || "";
		var tey = data.ended ? (ey.getFullYear() || "") : language.gettext("musicbrainz_now");
		return '('+tby+'&nbsp;-&nbsp;'+tey+')';
	}

	function getURLs(layout, relations) {
		if (relations.length == 0 || !relations.some(r => (r.url)))
			return "";

		var list = layout.add_sidebar_list(language.gettext("discogs_external"));
		relations.forEach(function(rel) {
			if (rel.url) {
				let item = $('<li>').appendTo(list);
				let icon = $('<i>', {class: 'icon-noicon smallicon'}).appendTo(item);
				let link = $('<a>', {target: '_blank', href: rel.url.resource}).appendTo(item);
				let d = rel.url.resource.match(/https*:\/\/(.*?)(\/|$)/);
				switch (rel.type) {
					case "wikipedia":
						icon.addClass('icon-wikipedia').removeClass('icon-noicon');
						link.html('Wikipedia ('+d[1]+')');
						break;

					case "wikidata":
						icon.addClass('icon-wikipedia').removeClass('icon-noicon');
						link.html('Wikidata');
						break;

					case "discography":
						link.html(language.gettext("musicbrainz_externaldiscography", [d[1]]));
						break;

					case "musicmoz":
						link.html('Musicmoz');
						break;

					case "allmusic":
						icon.addClass('icon-allmusic').removeClass('icon-noicon');
						link.html('Allmusic');
						break;

					case "BBC Music page":
						icon.addClass('icon-bbc-logo').removeClass('icon-noicon');
						link.html('BBC Music Page');
						break;

					case "discogs":
						icon.addClass('icon-discogs').removeClass('icon-noicon');
						link.html('Discogs');
						break;

					case "official homepage":
						link.html(language.gettext("musicbrainz_officalhomepage", [d[1]]));
						break;

					case "fanpage":
						link.html(language.gettext("musicbrainz_fansite", [d[1]]));
						break;

					case "lyrics":
						icon.addClass('icon-doc-text-1').removeClass('icon-noicon');
						link.html(language.gettext("musicbrainz_lyrics", [d[1]]));
						break;

					case "secondhandsongs":
						link.html('Secondhand Songs')
						break;

					case "IMDb":
						icon.addClass('icon-imdb-logo').removeClass('icon-noicon');
						link.html('IMDb');
						break;

					case "social network":
						if (rel.url.resource.match(/last\.fm/i)) {
							icon.addClass('icon-lastfm-1').removeClass('icon-noicon');
							link.html('Last.FM');
						} else if (rel.url.resource.match(/facebook\.com/i)) {
							icon.addClass('icon-facebook-logo').removeClass('icon-noicon');
							link.html('Facebook');
						} else {
							link.html(language.gettext("musicbrainz_social", [d[1]]));
						}
						break;

					case "youtube":
						icon.addClass('icon-youtbe-circled').removeClass('icon-noicon');
						link.html('YouTube');
						break;

					case "myspace":
						link.html('MySpace');
						break;

					case "microblog":
						if (rel.url.resource.match(/twitter\.com/i)) {
							icon.addClass('icon-twitter-logo').removeClass('icon-noicon');
							link.html('Twitter');
						} else {
							link.html(language.gettext("musicbrainz_microblog", [d[1]]));
						}
						break;

					case "review":
						if (rel.url.resource.match(/bbc\.co\.uk/i)) {
							icon.addClass('icon-bbc-logo').removeClass('icon-noicon');
							link.html('BBC Music Review');
						} else {
							link.html(language.gettext("musicbrainz_review", [d[1]]));
						}
						break;

					case "VIAF":
						break;

					default:
						link.html(d[1]);
						break;

				}
			}
		});
	}

	function getReleaseHTML(data) {

		if (data.error)
			return '<h3 align="center">'+language.gettext("musicbrainz_contacterror")+'</h3>';

		if (data['release-groups'].length == 0)
			return '';

		var dby = data['release-groups'].sort(albumsbyyear);
		var html = $('<div>');
		var table = $('<table>', {class: 'padded', width: '100%'}).appendTo($('<div>', {class: 'mbbox'}).appendTo(html));
		var row = $('<tr>').appendTo(table);
		['title_title', 'title_year', 'label_artist', 'title_type'].forEach(function(t) {
			row.append($('<th>').html(language.gettext(t)));
		});
		dby.forEach(function(rel) {
			row = $('<tr>').appendTo(table);
			row.append($('<td>').append($('<a>', {href: 'http://www.musicbrainz.org/release-group/'+rel.id, target: '_blank'}).html(rel.title)));
			row.append($('<td>').html(nozeros(getYear(rel))));
			row.append($('<td>').html(rel['artist-credit'].map(r => r.name).join(' '+rel['artist-credit'][0].joinphrase+' ')));
			row.append($('<td>').html(rel['secondary-types'].join(' ')+' '+(rel['primary-type'] || '')));
		});
		return html.html();
	}

	function getAlbumHTML(albumobj, albummeta, layout, data) {
		if (data.error) {
			layout.display_error(data.error);
			layout.finish(null, null);
			return;
		}

		if (data.disambiguation)
			layout.add_sidebar_list('', data.disambiguation);

		let status = [	(data.status || ''),
						(data['release-group']['secondary-types'] || []).join(' '),
			 			(data['release-group']['primary-type'] || "") ].join(' ');
		layout.add_sidebar_list(language.gettext("musicbrainz_status"), status);

		layout.add_sidebar_list(language.gettext("musicbrainz_date"), (data['release-group']['first-release-date'] || data.date));

		if (data.country)
			layout.add_sidebar_list(language.gettext("musicbrainz_country"), data.country);

		if (data['label-info'] && data['label-info'].length > 0) {
			let u = layout.add_sidebar_list(language.gettext("title_label"));
			data['label-info'].forEach(function(label) {
				if (label.label)
					u.append($('<li>').html(label.label.name));
			});
		}

		getURLs(layout, data.relations);

		if (data.annotation) {
			layout.add_flow_box_header({title: language.gettext("musicbrainz_notes")});
			layout.add_flow_box(data.annotation.replace(/\n/, '<br>').replace(/\[(.*?)\|(.*?)\]/, '<a href="$1" target="_blank">$2</a>'));
		}

		doTags(layout, data.tags);

		doCredits(layout, data.relations);

		layout.add_flow_box_header({wide: true, title: language.gettext("discogs_tracklisting")});
		var tl = $('<table>', {class: 'padded'}).appendTo(layout.add_flow_box(''));
		data.media.forEach(function(media, index) {
			var row = $('<tr>').appendTo(tl);
			row.append($('<th>', {colspan: '3'}).html('<b>'+language.gettext("musicbrainz_disc")+' '+media.position+(media.title ? ' - '+media.title : '')+'</b>'));
			media.tracks.forEach(function(track) {
				row = $('<tr>').appendTo(tl);
				$('<td>').html(track.number).appendTo(row);
				let tit = $('<td>').html(track.title).appendTo(row);
				if (data['artist-credit'][0].name == "Various Artists" && track['artist-credit']) {
					tit.append($('<br>')).append(track['artist-credit'].map(r => r.name).join(' '+track['artist-credit'][0].joinphrase+' '));
				}
				$('<td>').html(formatTimeString(Math.round(track.length/1000))).appendTo(row);
			});
		});

		if (data['cover-art-archive'].artwork == true)
			getCoverArt(albumobj, albummeta, layout);

		debug.log('MUSICBRAINZ', 'Album data', albummeta);
		layout.finish('http://musicbrainz.org/release/'+albummeta.musicbrainz_id, albummeta.musicbrainz[albummeta.musicbrainz_id].title);
	}

	function getCoverArt(albumobj, albummeta, layout) {
		debug.trace(medebug,"Getting Cover Art");
		if (albummeta.musicbrainz.coverart === undefined) {
			debug.trace(medebug," ... retrieivng data");
			musicbrainz.album.getCoverArt(
				albummeta.musicbrainz_id,
				albumobj.coverResponseHandler,
				albumobj.coverResponseHandler
			);
		} else {
			debug.trace(medebug," ... displaying what we've already got");
			getCoverHTML(albummeta.musicbrainz.coverart, layout);
		}
	}

	function getCoverHTML(data, layout) {
		if (!data)
			return;
		debug.trace(medebug, 'Got Cover Images', data);
		var img = data.images.shift();
		layout.add_main_image(img.image);
		data.images.reverse().forEach(function(img) {
			layout.add_sidebar_image(img.thumbnails.small, img.image);
		});
	}

	function getTrackHTML(layout, data) {
		if (data.error) {
			layout.display_error(data.error);
			layout.finish(null, null);
			return;
		}

		if (data.recording && data.recording.disambiguation)
			layout.add_sidebar_list('', data.recording.disambiguation);

		if (data.work && data.work.disambiguation)
			layout.add_sidebar_list('', data.work.disambiguation);

		if (data.recording.rating && data.recording.rating.value !== null)
			layout.add_sidebar_list(language.gettext('musicbrainz_rating'), language.gettext("musicbrainz_votes", [data.recording.rating.value, data.recording.rating['votes-count']]));

		var rels = [];
		if (data.work && data.work.relations)
			rels = data.work.relations;

		if (data.recording && data.recording.relations)
			rels = rels.concat(data.recording.relations);

		getURLs(layout, rels);

		var notes = ((data.work && data.work.annotation) ? data.work.annotation : '') + ((data.recording && data.recording.annotation) ? data.recording.annotation : '');
		if (notes) {
			notes = notes.replace(/\n/, '<br>').replace(/\[(.*?)\|(.*?)\]/, '<a href="$1" target="_blank">$2</a>');
			layout.add_flow_box_header({title: language.gettext("musicbrainz_notes")});
			layout.add_flow_box(notes);
		}

		if (data.recording)
			doTags(layout, data.recording.tags);

		doCredits(layout, rels);

		if (data.recording && data.recording.releases && data.recording.releases.length > 0) {
			layout.add_flow_box_header({title: language.gettext("musicbrainz_appears")});
			var table = $('<table>', {class: 'padded'}).appendTo(layout.add_flow_box(''));
			data.recording.releases.forEach(function(release) {
				let row = $('<tr>').appendTo(table);
				row.append($('<td>').append($('<b>').append($('<a>', {href: 'http://www.musicbrainz.org/release/'+release.id, target: '_blank'}).html(release.title))));
				row.append($('<td>').html(release.date));
				row.append($('<td>').append($('<i>').html(release.status+', '+release.country)));
			});
		}

		debug.log('MUSICBRAINZ', 'Track data', data);
		layout.finish('http://musicbrainz.org/recording/'+data.recording.id, data.recording.title);

	}

	function doCredits(layout, rels) {
		if (!rels.some(r => (r.artist)))
			return;

		layout.add_flow_box_header({title: language.gettext("musicbrainz_credits")});
		var table = $('<table>', {class: 'padded'}).appendTo(layout.add_flow_box(''));
		rels.forEach(function(rel) {
			if (rel.artist) {
				let row = $('<tr>').appendTo(table);
				row.append($('<td>', {class: 'ucfirst'}).html(rel.type+(rel.attributes.length > 0 ? ' ('+rel.attributes.join(', ')+')' : '')));
				row.append($('<td>').append($('<a>', {href: 'http://www.musicbrainz.org/artist/'+rel.artist.id, target: '_blank'}).html(rel.artist.name))
					.append(rel.artist.disambiguation ? ' <i>('+rel.artist.disambiguation+')</i>' : ''));
			}
		});
	}

	function scrape_useful_links(data, update) {

		var wikilinks = { user: null, english: null, anything: null };
		var domain = '^http://'+wikipedia.getLanguage();
		var re = new RegExp(domain);
		for (var i in data.relations) {
			if (data.relations[i].type == "wikipedia") {

				// For wikipedia links we need to prioritise:
				// user's chosen domain first
				// english second
				// followed by anything will do
				// the php side will also try to use the link we choose to get language links for the
				// user's chosen language, but it's definitely best if we prioritise them here
				var wikitemp = data.relations[i].url.resource;
				if (re.test(wikitemp)) {
					wikilinks.user = wikitemp;
				} else if (wikitemp.match(/en.wikipedia.org/)) {
					wikilinks.english = wikitemp;
				} else {
					wikilinks.anything = wikitemp;
				}
			}
			if (data.relations[i].type == "discogs") {
				let dk = objFirst(update.discogs);
				if (dk && update.discogs[dk] == null)
					update.discogs[dk] = data.relations[i].url.resource;

			}
			if (data.relations[i].type == "allmusic") {
				let dk = objFirst(update.allmusic);
				if (dk && update.discogs[dk] == null)
					update.allmusic[dk] = data.relations[i].url.resource;

			}
		}

		let dk = objFirst(update.wikipedia);
		if (dk && update.wikipedia[dk] == null) {
			update.wikipedia[dk] = (wikilinks.user || wikilinks.english || wikilinks.anything);
		}
		if (data.disambiguation && update.disambiguation == null) {
			update.disambiguation = data.disambiguation;
		}

		if (data['release-group']) {
			let dk = objFirst(update.musicbrainz);
			if (dk == 'album_releasegroupid') {
				update.musicbrainz.album_releasegroupid = data['release-group'].id;
			}
		}
		debug.trace(medebug, 'Useful external links are', cloneObject(update));
		return update;
	}

	function scrape_useful_track_data(data, update) {
		if (data.recording) {
			for (var i in data.recording.relations) {
				if (data.recording.relations[i].type == "wikipedia" && update.wikipedia.tracklink === null) {
					update.wikipedia.tracklink = data.recording.relations[i].url.resource;
				}
				if (data.recording.relations[i].type == "discogs" && update.discogs.tracklink === null) {
					update.discogs.tracklink = data.recording.relations[i].url.resource;
				}
			}
		}
		if (data.work) {
			for (var i in data.work.relations) {
				if (data.work.relations[i].type == "wikipedia" && update.wikipedia.tracklink === null) {
					update.wikipedia.tracklink = data.work.relations[i].url.resource;
				}
				if (data.work.relations[i].type == "discogs" && update.discogs.tracklink === null) {
					update.discogs.tracklink = data.work.relations[i].url.resource;
				}
			}
		}
		return update;
	}

	return {

		getRequirements: function(parent) {
			if (parent.playlistinfo.metadata.artists[parent.artistindex].musicbrainz_id == "" ||
				parent.playlistinfo.metadata.album.musicbrainz_id == "" ||
				parent.playlistinfo.metadata.track.musicbrainz_id == "") {
				return ["lastfm"];
			} else {
				return [];
			}
		},

		collection: function(parent, artistmeta, albummeta, trackmeta) {

			debug.debug(medebug, "Creating data collection");

			var self = this;

			this.populate = function() {
				parent.updateData({
					musicbrainz: {}
				}, artistmeta);

				parent.updateData({
					musicbrainz: {}
				}, albummeta);

				parent.updateData({
					musicbrainz: {}
				}, trackmeta);

				if (typeof artistmeta.musicbrainz.layout == 'undefined')
					self.artist.populate();

				if (typeof albummeta.musicbrainz.layout == 'undefined')
					self.album.populate();

				if (typeof trackmeta.musicbrainz.layout == 'undefined')
					self.track.populate();
			}

			this.handleClick = function(source, element, event) {
				debug.debug(medebug,parent.nowplayingindex,source,"is handling a click event");
				if (element.hasClass('clickdoartist')) {
					display_artist(source, element, event);
				} else if (element.hasClass('clickdodiscography')) {
					do_discography(source, element, event);
				} else if (element.hasClass('clickexpandbox')) {
					let artistid = element.attr('name');
					info_panel_expand_box(source, element, event,
						artistmeta.musicbrainz[artistid].name, me, 'http://musicbrainz.org/artist/'+artistmeta.musicbrainz[artistid].id
					);
				} else if (element.hasClass('clickzoomimage')) {
					imagePopup.create(element, event, element.next().val());
				}
			}

			function display_artist(source, element, event) {
				var targetdiv = element.parent().next();
				if (!(targetdiv.hasClass('full')) && element.isClosed()) {
					targetdiv.doSomethingUseful(language.gettext("info_gettinginfo")).slideToggle('fast');
					getArtistData(element.attr('name'));
					element.toggleOpen();
					targetdiv.addClass('underline');
				} else {
					if (element.isOpen()) {
						element.toggleClosed();
						targetdiv.removeClass('underline');
					} else {
						element.toggleOpen();
						targetdiv.addClass('underline');
					}
					targetdiv.slideToggle('fast');
				}
			}

			function do_discography(source, element, event) {
				var targetdiv = element.parent().next();
				if (!(targetdiv.hasClass('full')) && element.isClosed()) {
					targetdiv.doSomethingUseful(language.gettext("info_gettinginfo"));
					getArtistReleases(element.attr('name'), 'discography_'+element.attr('name'));
					element.toggleOpen();
					targetdiv.slideToggle('fast');
				} else {
					if (element.isOpen()) {
						element.toggleClosed();
					} else {
						element.toggleOpen();
					}
					targetdiv.slideToggle('fast');
				}
			}

			function getArtistData(id) {
				debug.debug(medebug,parent.nowplayingindex,"Getting data for artist with ID",id);
				if (artistmeta.musicbrainz[id] === undefined) {
					debug.debug(medebug,parent.nowplayingindex," ... retrieivng data");
					musicbrainz.artist.getInfo(
						id,
						self.artist.extraResponseHandler,
						self.artist.extraResponseHandler
					);
				} else {
					debug.debug(medebug,parent.nowplayingindex," ... displaying what we've already got");
					putArtistData(id);
				}
			}

			function putArtistData(id) {
				// We did once try this by making a layout for every artist and just re-using them
				// but that results in lots of unplesantness including panels being open because you opened
				// them earlier in another layout, and circular references where the child contains the parent.
				var layout = new info_sidebar_layout({
					expand: true,
					expandid: artistmeta.musicbrainz[id].id,
					title: artistmeta.musicbrainz[id].name,
					type: 'artist',
					source: me,
					withbannerid: false
				});
				getArtistHTML(layout, artistmeta.musicbrainz[id]);
				$('div[name="'+id+'"]').each(function() {
					if (!$(this).hasClass('full')) {
						$(this).empty().append(layout.get_contents());
						$(this).addClass('full');
					}
				});
			}

			function getArtistReleases(id, target) {
				debug.debug(medebug,parent.nowplayingindex,"Looking for release info with id",id,target);
				if (artistmeta.musicbrainz[target] === undefined) {
					debug.debug(medebug,"  ... retreiving them");
					musicbrainz.artist.getReleases(
						id,
						target,
						self.artist.releaseResponseHandler,
						self.artist.releaseResponseHandler
					);
				} else {
					debug.debug(medebug,"  ... displaying what we've already got",artistmeta.musicbrainz[target]);
					putArtistReleases(artistmeta.musicbrainz[target], target);
				}
			}

			function putArtistReleases(data, div) {
				var html = getReleaseHTML(data);
				$('div[name="'+div+'"]').each(function() {
					if (!($(this).hasClass('full'))) {
						$(this).html(html);
						$(this).addClass('full');
					}
				});
			}

			this.artist = function() {

				return {

					populate: async function() {
						if (typeof artistmeta.musicbrainz.layout == 'undefined')
							artistmeta.musicbrainz.layout = new info_sidebar_layout({title: artistmeta.name, type: 'artist', source: me});

						while (artistmeta.musicbrainz_id == '') {
							await new Promise(t => setTimeout(t, 500));
						}

						if (artistmeta.musicbrainz_id === null) {
							debug.info(medebug,parent.nowplayingindex,"Artist asked to populate but no MBID could be found. Aborting");
							self.artist.abjectFailure();
						}
						if (artistmeta.musicbrainz[artistmeta.musicbrainz_id] === undefined) {
							debug.debug(medebug,parent.nowplayingindex,"artist is populating",artistmeta.musicbrainz_id);
							musicbrainz.artist.getInfo(
								artistmeta.musicbrainz_id,
								self.artist.mbResponseHandler,
								self.artist.mbResponseHandler);
						}
					},

					abjectFailure: function(err) {
						artistmeta.musicbrainz_id = -1;
						if (err) {
							artistmeta.musicbrainz[artistmeta.musicbrainz_id] = err;
						} else {
							artistmeta.musicbrainz[artistmeta.musicbrainz_id] = {error: language.gettext("musicbrainz_noartist")};
						}
						parent.updateData({
								wikipedia: { artistlink: null },
								discogs: {  artistlink: null },
								allmusic: {  artistlink: null }
							},
							artistmeta
						);
						self.artist.doBrowserUpdate();
					},

					mbResponseHandler: function(data) {
						debug.debug(medebug,parent.nowplayingindex,"got artist data for",artistmeta.musicbrainz_id,data);
						// Look for the information that other plugins need
						var update = 	{ 	disambiguation: null,
											wikipedia: { artistlink: null },
											discogs: {  artistlink: null },
											allmusic: {  artistlink: null }
										};
						if (!data || data.error) {
							self.artist.abjectFailure(data);
						} else {
							artistmeta.musicbrainz[artistmeta.musicbrainz_id] = data;
							update = scrape_useful_links(data, update);
						}
						parent.updateData(update, artistmeta);
						self.artist.doBrowserUpdate();
					},

					extraResponseHandler: function(data) {
						if (data) {
							debug.debug(medebug,parent.nowplayingindex,"got extra artist data for",data.id,data);
							artistmeta.musicbrainz[data.id] = data;
							putArtistData(data.id);
						}
					},

					releaseResponseHandler: function(data) {
						if (data) {
							debug.debug(medebug,parent.nowplayingindex,"got release data for",data.id,data);
							artistmeta.musicbrainz[data.id] = data;
							putArtistReleases(artistmeta.musicbrainz[data.id], data.id);
						}

					},

					doBrowserUpdate: function() {
						getArtistHTML(artistmeta.musicbrainz.layout, artistmeta.musicbrainz[artistmeta.musicbrainz_id]);
					}
				}
			}();

			this.album = function() {

				return {

					populate: async function() {
						if (parent.playlistinfo.type == 'stream') {
							albummeta.musicbrainz.layout = new info_layout_empty();
							return;
						}
						albummeta.musicbrainz.layout = new info_sidebar_layout({title: albummeta.name, type: 'album', source: me});

						while (albummeta.musicbrainz_id == '') {
							await new Promise(t => setTimeout(t, 500));
						}

						if (albummeta.musicbrainz_id === null) {
							debug.info(medebug,parent.nowplayingindex,"Album asked to populate but no MBID could be found. Aborting");
							self.album.abjectFailure();
						}
						if (albummeta.musicbrainz[albummeta.musicbrainz_id] === undefined) {
							debug.debug(medebug,parent.nowplayingindex,"album is populating",albummeta.musicbrainz_id);
							musicbrainz.album.getInfo(
								albummeta.musicbrainz_id,
								self.album.mbResponseHandler,
								self.album.mbResponseHandler
							);
						}
					},

					abjectFailure: function(err) {
						albummeta.musicbrainz_id = -1;
						if (err) {
							albummeta.musicbrainz[albummeta.musicbrainz_id] = err;
						} else {
							albummeta.musicbrainz[albummeta.musicbrainz_id] = {error: language.gettext("musicbrainz_noalbum")};
						}
						parent.updateData({
								musicbrainz: { album_releasegroupid: null },
								wikipedia: { albumlink: null },
								discogs: {  albumlink: null }
							},
							albummeta
						);
						self.album.doBrowserUpdate();
					},

					mbResponseHandler: function(data) {
						debug.debug(medebug,parent.nowplayingindex,"got album data for",albummeta.musicbrainz_id);
						// Look for the information that other plugins need
						var update = {
										musicbrainz: { album_releasegroupid: null },
										wikipedia: { albumlink: null },
										discogs: {  albumlink: null }
									};
						if (!data || data.error) {
							self.album.abjectFailure(data);
						} else {
							albummeta.musicbrainz[albummeta.musicbrainz_id] = data;
							update = scrape_useful_links(data, update);
						}
						parent.updateData(update, albummeta);
						self.album.doBrowserUpdate();
					},

					coverResponseHandler: function(data) {
						debug.info(medebug,parent.nowplayingindex,"got Cover Art Data",data);
						albummeta.musicbrainz.coverart = data;
						getCoverHTML(albummeta.musicbrainz.coverart, albummeta.musicbrainz.layout);
					},

					doBrowserUpdate: function() {
						getAlbumHTML(self.album, albummeta, albummeta.musicbrainz.layout, albummeta.musicbrainz[albummeta.musicbrainz_id]);
					}
				}

			}();

			this.track = function() {

				return {

					populate: async function() {
						trackmeta.musicbrainz.layout = new info_sidebar_layout({title: trackmeta.name, type: 'track', source: me});

						while (trackmeta.musicbrainz_id == "") {
							await new Promise(t => setTimeout(t, 500));
						}

						if (trackmeta.musicbrainz_id === null) {
							debug.info(medebug,parent.nowplayingindex,"Track asked to populate but no MBID could be found. Aborting");
							self.track.abjectFailure();
						}

						if (trackmeta.musicbrainz.track === undefined) {
							debug.debug(medebug,parent.nowplayingindex,"track is populating",trackmeta.musicbrainz_id);
							musicbrainz.track.getInfo(
								trackmeta.musicbrainz_id,
								self.track.mbResponseHandler,
								self.track.mbResponseHandler);

						}
					},

					abjectFailure: function(err) {
						if (err) {
							trackmeta.musicbrainz.track = err;
						} else {
							trackmeta.musicbrainz.track = {error: language.gettext("musicbrainz_notrack")};
						}
						parent.updateData({
								wikipedia: { tracklink: null },
								discogs: {  tracklink: null }
							}, trackmeta);
						self.track.doBrowserUpdate();
					},

					mbResponseHandler: function(data) {
						debug.debug(medebug,parent.nowplayingindex,"got track data for",trackmeta.musicbrainz_id,data);
						// Look for the information that other plugins need
						var update = 	{
							wikipedia: { tracklink: null },
							discogs: { tracklink: null }
						};
						if (!data || data.error) {
							self.track.abjectFailure(data);
						} else {
							trackmeta.musicbrainz.track = data;
							update = scrape_useful_track_data(data, update);
						}
						parent.updateData(update,trackmeta);
						self.track.doBrowserUpdate();
					},

					doBrowserUpdate: function() {
						getTrackHTML(trackmeta.musicbrainz.layout, trackmeta.musicbrainz.track);
					}
				}
			}();
		}
	}
}();

nowplaying.registerPlugin("musicbrainz", info_musicbrainz, "icon-musicbrainz", "button_musicbrainz");
