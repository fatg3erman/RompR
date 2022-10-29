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

		getURLs(layout, data.relations, true);

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

	function getURLs(layout, relations, do_image) {
		if (relations.length == 0 || !relations.some(r => (r.url)))
			return "";

		var list = layout.add_sidebar_list(language.gettext("discogs_external"));
		var links = [];
		relations.reverse();
		relations.forEach(function(rel) {
			if (rel.url) {
				let item = $('<li>');
				let icon = $('<i>', {class: 'icon-noicon smallicon'}).appendTo(item);
				let link = $('<a>', {target: '_blank', href: rel.url.resource}).appendTo(item);
				let d = rel.url.resource.match(/https*:\/\/(.*?)(\/|$)/);
				switch (rel.type) {
					case "wikipedia":
						icon.addClass('icon-wikipedia').removeClass('icon-noicon');
						link.html('Wikipedia ('+d[1]+')');
						links.unshift(item);
						break;

					case "wikidata":
						link.html('Wikidata');
						links.unshift(item);
						break;

					case "discography":
						link.html(language.gettext("musicbrainz_externaldiscography", [d[1]]));
						links.unshift(item);
						break;

					case "musicmoz":
						link.html('Musicmoz');
						links.unshift(item);
						break;

					case "allmusic":
						icon.addClass('icon-allmusic').removeClass('icon-noicon');
						link.html('Allmusic');
						links.unshift(item);
						break;

					case "BBC Music page":
						icon.addClass('icon-bbc-logo').removeClass('icon-noicon');
						link.html('BBC Music Page');
						links.unshift(item);
						break;

					case "discogs":
						icon.addClass('icon-discogs').removeClass('icon-noicon');
						link.html('Discogs');
						links.unshift(item);
						break;

					case "last.fm":
						icon.addClass('icon-lastfm-1').removeClass('icon-noicon');
						link.html('Last.FM');
						links.unshift(item);
						break;

					case "official homepage":
						link.html(language.gettext("musicbrainz_officalhomepage", [d[1]]));
						links.unshift(item);
						break;

					case "bandcamp":
						icon.addClass('icon-bandcamp-circled').removeClass('icon-noicon');
						link.html(language.gettext("musicbrainz_purchase", '(Bandcamp)'));
						links.unshift(item);
						break;

					case "fanpage":
						link.html(language.gettext("musicbrainz_fansite", [d[1]]));
						links.unshift(item);
						break;

					case "lyrics":
						icon.addClass('icon-doc-text-1').removeClass('icon-noicon');
						link.html(language.gettext("musicbrainz_lyrics", [d[1]]));
						links.unshift(item);
						break;

					case "secondhandsongs":
						link.html('Secondhand Songs')
						links.unshift(item);
						break;

					case "IMDb":
						icon.addClass('icon-imdb-logo').removeClass('icon-noicon');
						link.html('IMDb');
						links.unshift(item);
						break;

					case "social network":
						if (rel.url.resource.match(/last\.fm/i)) {
							icon.addClass('icon-lastfm-1').removeClass('icon-noicon');
							link.html('Last.FM');
						} else if (rel.url.resource.match(/facebook\.com/i)) {
							icon.addClass('icon-facebook-logo').removeClass('icon-noicon');
							link.html('Facebook');
						} else if (rel.url.resource.match(/twitter\.com/i)) {
							icon.addClass('icon-twitter-logo').removeClass('icon-noicon');
							link.html('Twitter');
						} else if (rel.url.resource.match(/open\.spotify\.com/i)) {
							icon.addClass('icon-spotify-circled').removeClass('icon-noicon');
							link.html('Spotify Social');
						} else if (rel.url.resource.match(/instagram\.com/i)) {
							icon.addClass('icon-instagram').removeClass('icon-noicon');
							link.html('Instagram');
						} else {
							link.html(language.gettext("musicbrainz_social", [d[1]]));
						}
						links.unshift(item);
						break;

					case "youtube":
						icon.addClass('icon-youtube-circled').removeClass('icon-noicon');
						link.html('YouTube');
						links.unshift(item);
						break;

					case "myspace":
						link.html('MySpace');
						links.unshift(item);
						break;

					case "microblog":
						if (rel.url.resource.match(/twitter\.com/i)) {
							icon.addClass('icon-twitter-logo').removeClass('icon-noicon');
							link.html('Twitter');
						} else {
							link.html(language.gettext("musicbrainz_microblog", [d[1]]));
						}
						links.unshift(item);
						break;

					case "review":
						if (rel.url.resource.match(/bbc\.co\.uk/i)) {
							icon.addClass('icon-bbc-logo').removeClass('icon-noicon');
							link.html('BBC Music Review');
						} else {
							link.html(language.gettext("musicbrainz_review", [d[1]]));
						}
						links.unshift(item);
						break;

					case "VIAF":
						break;

					case "purchase for download":
						link.html(language.gettext("musicbrainz_purchase", [d[1]]));
						links.unshift(item);
						break;

					case "free streaming":
						if (rel.url.resource.match(/open\.spotify\.com/i)) {
							icon.addClass('icon-spotify-circled').removeClass('icon-noicon');
							link.html(language.gettext("musicbrainz_streaming", 'Spotify'));
						} else {
							link.html(language.gettext("musicbrainz_streaming", [d[1]]));
						}
						links.unshift(item);
						break;

					case "soundcloud":
						icon.addClass('icon-soundcloud-circled').removeClass('icon-noicon');
						link.html('SoundCloud');
						links.unshift(item);
						break;

					// case 'image':
					// 	if (do_image) {
					// 		debug.mark('MUSICBRAINZ', 'Adding Image', rel.url.resource);
					// 		layout.add_main_image(rel.url.resource);
					// 	}

					default:
						link.html(d[1]);
						links.push(item);
						break;

				}
			}
		});
		for (var l of links) {
			list.append(l);
		}
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

	function getAlbumHTML(albumobj, layout, data, albummeta) {
		if (data.error) {
			layout.display_error(data.error);
			layout.finish(null, null);
			return;
		}

		let release_group = data.release_group;
		let release = data.release[data.musicbrainz_id];

		if (data.disambiguation)
			layout.add_sidebar_list('', data.disambiguation);

		let status = [	(release.status || ''),
						(release_group['secondary-types'] || []).join(' '),
			 			(release_group['primary-type'] || "") ].join(' ');
		layout.add_sidebar_list(language.gettext("musicbrainz_status"), status);

		layout.add_sidebar_list(language.gettext("musicbrainz_date"), (release_group['first-release-date'] || release.date));

		if (release['label-info'] && release['label-info'].length > 0) {
			let labels = [];
			release['label-info'].forEach(function(label) {
				if (label.label && labels.indexOf(label.label.name) < 0)
					labels.push(label.label.name);
			});

			let u = layout.add_sidebar_list(language.gettext("title_label"));
			labels.forEach(function(label) {
				u.append($('<li>').html(label));
			});
		}

		getURLs(layout, release.relations.concat(release_group.relations), false);

		if (release.annotation) {
			layout.add_flow_box_header({title: language.gettext("musicbrainz_notes")});
			layout.add_flow_box(release.annotation.replace(/\n/, '<br>').replace(/\[(.*?)\|(.*?)\]/, '<a href="$1" target="_blank">$2</a>'));
		}

		doTags(layout, release.tags);

		doCredits(layout, release.relations);
		doCredits(layout, release_group.relations);

		layout.add_flow_box_header({wide: true, title: language.gettext("discogs_tracklisting")});
		var tl = $('<table>', {class: 'padded'}).appendTo(layout.add_flow_box(''));
		release.media.forEach(function(media, index) {
			var row = $('<tr>').appendTo(tl);
			row.append($('<th>', {colspan: '3'}).html('<b>'+language.gettext("musicbrainz_disc")+' '+media.position+(media.title ? ' - '+media.title : '')+'</b>'));
			media.tracks.forEach(function(track) {
				row = $('<tr>').appendTo(tl);
				$('<td>').html(track.number).appendTo(row);
				let tit = $('<td>').html(track.title).appendTo(row);
				if (release['artist-credit'][0].name == "Various Artists" && track['artist-credit']) {
					tit.append($('<br>')).append(track['artist-credit'].map(r => r.name).join(' '+track['artist-credit'][0].joinphrase+' '));
				}
				$('<td>').html(formatTimeString(Math.round(track.length/1000))).appendTo(row);
			});
		});

		if (release_group.releases && release_group.releases.length > 0) {
			layout.add_flow_box_header({title: language.gettext("musicbrainz_album_releases")});
			var table = $('<table>', {class: 'padded'}).appendTo(layout.add_flow_box(''));
			do_release_table(release_group.releases, layout);
		}

		if (release['cover-art-archive'].artwork == true)
			getCoverArt(albumobj, albummeta, layout);

		layout.finish('http://musicbrainz.org/release/'+release.id, release.title);
	}

	function getCoverArt(albumobj, albummeta, layout) {
		debug.trace(medebug,"Getting Cover Art");
		if (albummeta.coverart === undefined) {
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
		if (!data || !data.images)
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

		getURLs(layout, rels, false);

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
			do_release_table(data.recording.releases, layout);
		}

		layout.finish('http://musicbrainz.org/recording/'+data.recording.id, data.recording.title);

	}

	function do_release_table(releases, layout) {
		var table = $('<table>', {class: 'padded'}).appendTo(layout.add_flow_box(''));
		releases.forEach(function(release) {
			let row = $('<tr>').appendTo(table);
			row.append($('<td>').append($('<b>').append($('<a>', {href: 'http://www.musicbrainz.org/release/'+release.id, target: '_blank'}).html(release.title))));
			row.append($('<td>').html(release.date));
			row.append($('<td>').append($('<i>').html(release.status+', '+release.country)));
		});
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

	return {

		getRequirements: function(parent) {
			return [];
		},

		collection: function(parent, artistmeta, albummeta, trackmeta) {

			debug.debug(medebug, "Creating data collection");

			var self = this;

			this.repopulating = false;

			this.populate = function() {
				// These are the items this collection provides for other collections
				// as well as the ones we want ourselves
				parent.updateData({
					disambiguation: '',
					lastfm: {
						musicbrainz_id: ''
					},
					musicbrainz: {
						musicbrainz_id: '',
						populated: false
					},
					wikipedia: { link: '' },
					discogs: {
						artistlink: '',
						artistid: ''
					},
					spotify: { id: '' },
					allmusic: { link: '' },
					triggers: {
						musicbrainz: {
							musicbrainz_id: self.artist.populate
						}
					}
				}, artistmeta);

				parent.updateData({
					disambiguation: '',
					musicbrainz: {
						musicbrainz_id: '',
						populated: false
					},
					wikipedia: { link: ''},
					discogs: {
						masterlink: '',
						releaselink: '',
						masterid: '',
						releaseid: '',
					},
					allmusic: { link: '' },
					triggers: {
						musicbrainz: {
							musicbrainz_id: self.album.populate
						}
					}
				}, albummeta);

				parent.updateData({
					disambiguation: '',
					musicbrainz: {
						musicbrainz_id: '',
						populated: false
					},
					wikipedia: { link: ''},
					discogs: {
						masterlink: '',
						releaselink: '',
						masterid: '',
						releaseid: ''
					},
					allmusic: { link: '' },
					triggers: {
						musicbrainz: {
							musicbrainz_id: self.track.populate
						}
					}
				}, trackmeta);

				if (
					typeof artistmeta.musicbrainz.layout == 'undefined'
					|| typeof albummeta.musicbrainz.layout == 'undefined'
					|| typeof trackmeta.musicbrainz.layout == 'undefined'
				) {
					self.verify_data();
				}

				if (typeof artistmeta.musicbrainz.layout == 'undefined') {
					artistmeta.musicbrainz.layout = new info_sidebar_layout({title: artistmeta.name, type: 'artist', source: me});
					if (artistmeta.name == '' && trackmeta.name == '') {
						artistmeta.musicbrainz.populated = true;
						self.artist.doBrowserUpdate({error: 'There is no Artist to display information for'});
					}
				}

				if (typeof albummeta.musicbrainz.layout == 'undefined') {
					if (parent.playlistinfo.type == 'stream') {
						albummeta.musicbrainz.layout = new info_layout_empty();
						albummeta.musicbrainz.populated = true;
					} else {
						albummeta.musicbrainz.layout = new info_sidebar_layout({title: albummeta.name, type: 'album', source: me});
					}
				}

				if (typeof trackmeta.musicbrainz.layout == 'undefined') {
					if (trackmeta.name == '') {
						trackmeta.musicbrainz.populated = true;
						trackmeta.musicbrainz.layout = new info_layout_empty();
					} else {
						trackmeta.musicbrainz.layout = new info_sidebar_layout({title: trackmeta.name, type: 'track', source: me});
					}
				}

			}

			this.verify_data = function() {
				musicbrainz.verify_data({
						language: wikipedia.getLanguage(),
						artist: {
							name: artistmeta.name,
							musicbrainz_id: artistmeta.musicbrainz_id
						},
						album: {
							name: (parent.playlistinfo.type == 'stream') ? null : albummeta.name,
							artist: albummeta.artist,
							musicbrainz_id: albummeta.musicbrainz_id
						},
						track: {
							name: trackmeta.name,
							musicbrainz_id: trackmeta.musicbrainz_id,
							artist: trackmeta.artist
						}
					},
					self.mbVerifyResult,
					self.mbFailResult
				);
			}

			this.mbVerifyResult =  function(data) {
				debug.mark('MUSICBRAINZ', 'Got Verified Data', data);
				if (data.metadata.artist !== null) {
					for (var i in data.metadata.artist) {
						if (typeof(artistmeta.musicbrainz[i]) == 'undefined') {
							debug.log('MUSICBRAINZ', 'Adding artist data for',i);
							artistmeta.musicbrainz[i] = data.metadata.artist[i];
						}
					}
				}
				if (data.metadata.album.release !== null) {
					if (typeof(albummeta.musicbrainz.release) == 'undefined')
						albummeta.musicbrainz.release = {};

					for (var i in data.metadata.album.release) {
						if (typeof(albummeta.musicbrainz.release[i]) == 'undefined') {
							debug.log('MUSICBRAINZ', 'Adding album release data for',i);
							albummeta.musicbrainz.release[i] = data.metadata.album.release[i];
						}
					}
				}
				if (data.metadata.album.release_group !== null) {
					if (typeof(albummeta.musicbrainz.release_group) == 'undefined') {
						albummeta.musicbrainz.release_group = data.metadata.album.release_group;
					}
				}
				if (data.metadata.track.recording !== null) {
					if (typeof(trackmeta.musicbrainz.recording) == 'undefined') {
						trackmeta.musicbrainz.recording = data.metadata.track.recording;
					}
				}
				if (data.metadata.track.work !== null) {
					if (typeof(trackmeta.musicbrainz.work) == 'undefined') {
						trackmeta.musicbrainz.work = data.metadata.track.work;
					}
				}

				if (self.repopulating) {
					self.repopulating = false;
					albummeta.musicbrainz.populated = false;
					trackmeta.musicbrainz.populated = false;
				}

				parent.updateData(
					data.artistmeta,
					artistmeta
				);
				parent.updateData(
					data.albummeta,
					albummeta
				);
				parent.updateData(
					data.trackmeta,
					trackmeta
				);

			}

			this.mbFailResult =  function(data) {
				debug.error('MUSICBRAINZ', 'Error Getting Verified Data', data);
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

					populate: function() {
						if (artistmeta.musicbrainz.populated == false) {
							if (artistmeta.musicbrainz.musicbrainz_id && artistmeta.musicbrainz[artistmeta.musicbrainz.musicbrainz_id]) {
								self.artist.doBrowserUpdate(artistmeta.musicbrainz[artistmeta.musicbrainz.musicbrainz_id]);
							} else {
								// if (self.artist.check_lastfm_mbid()) {
								// 	return;
								// } else {
									self.artist.doBrowserUpdate({error: language.gettext("musicbrainz_noartist")});
								// }
							}
							artistmeta.musicbrainz.populated = true;
						}
					},

					check_lastfm_mbid: function() {

						// This frequently doesn't help as last.FM gives us the wrong MNID
						// when there are multiple artists with the same name

						if (artistmeta.lastfm.musicbrainz_id === '') {
							parent.updateData({
									triggers: {
										lastfm: {
											musicbrainz_id: self.artist.check_lastfm_mbid
										}
									}
								},
								artistmeta
							);
							debug.mark('MUSICBRAINZ', 'Waiting to see if Last.FM gives us an artist mbid');
							return true;
						} else if (artistmeta.lastfm.musicbrainz_id === null) {
							debug.mark('MUSICBRAINZ', 'Last.FM did not give us an artist mbid');
							return false;
						} else {
							debug.mark('MUSICBRAINZ', 'Last.FM gave us an artist mbid');
							artistmeta.musicbrainz_id = artistmeta.lastfm.musicbrainz_id;
							self.repopulating = true;
							self.verify_data();
							return true;
						}
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

					doBrowserUpdate: function(data) {
						getArtistHTML(artistmeta.musicbrainz.layout, data);
					}
				}
			}();

			this.album = function() {

				return {

					populate: function() {
						if (albummeta.musicbrainz.populated == false) {
							if (albummeta.musicbrainz.release_group && albummeta.musicbrainz.musicbrainz_id && albummeta.musicbrainz.release[albummeta.musicbrainz.musicbrainz_id]) {
								self.album.doBrowserUpdate(albummeta.musicbrainz);
							} else {
								self.album.doBrowserUpdate({error: language.gettext("musicbrainz_noalbum")});
							}
							albummeta.musicbrainz.populated = true;
						}
					},

					coverResponseHandler: function(data) {
						debug.info(medebug,parent.nowplayingindex,"got Cover Art Data",data);
						albummeta.musicbrainz.coverart = data;
						getCoverHTML(albummeta.musicbrainz.coverart, albummeta.musicbrainz.layout);
					},

					doBrowserUpdate: function(data) {
						getAlbumHTML(self.album, albummeta.musicbrainz.layout, data, albummeta.musicbrainz);
					}
				}

			}();

			this.track = function() {

				return {

					populate: function() {
						debug.log('MUSICBRAINZ', 'Track is thinking about doing its thing');
						if (trackmeta.musicbrainz.populated == false) {
							debug.log('MUSICBRAINZ', 'Track has not yet done its thing');
							if (trackmeta.musicbrainz.recording || trackmeta.musicbrainz.work) {
								debug.log('MUSICBRAINZ', 'Track is doing its thing');
								self.track.doBrowserUpdate(trackmeta.musicbrainz);
							} else {
								self.track.doBrowserUpdate({error: language.gettext("musicbrainz_notrack")});
							}
							trackmeta.musicbrainz.populated = true;
						}
					},

					doBrowserUpdate: function(data) {
						trackmeta.musicbrainz.layout.clear_out();
						getTrackHTML(trackmeta.musicbrainz.layout, data);
					}
				}
			}();
		}
	}
}();

nowplaying.registerPlugin("musicbrainz", info_musicbrainz, "icon-musicbrainz", "button_musicbrainz");
