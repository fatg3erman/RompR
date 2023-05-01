var info_discogs = function() {

	var me = "discogs";
	var medebug = 'DISCOGS PLUGIN';

	function getArtistHTML(layout, artist, artistmeta, data, expand) {
		var image;
		if (data.error) {
			layout.display_error(data.error);
			layout.finish(null, null);
			return;
		}

		if (!expand)
			layout.make_possibility_chooser(artistmeta.discogs.possibilities, artistmeta.discogs.currentposs, artistmeta.name);

		if (!data.data) {
			layout.finish(null, artistmeta.name);
			return;
		}

		if (data.data.realname)
			layout.add_sidebar_list(language.gettext("discogs_realname"), data.data.realname);

		if (data.data.aliases && data.data.aliases.length > 0) {
			let u = layout.add_sidebar_list(language.gettext("discogs_aliases"));
			data.data.aliases.forEach(function(a) {
				u.append($('<li>').html(a.name));
			});
		}

		if (data.data.namevariations && data.data.namevariations.length > 0) {
			let u = layout.add_sidebar_list(language.gettext("discogs_alsoknown"));
			data.data.namevariations.forEach(function(a) {
				u.append($('<li>').html(a));
			});
		}

		getURLs(layout, data.data.urls);

		if (image = getBestImage(data.data.images))
			layout.add_main_image(image);

		if (data.data.profile)
			layout.add_profile(formatNotes(artist, artistmeta, data.data.profile));

		let others = get_other_images(data.data.images, image);
		if (others.length > 0)
			layout.add_masonry_images(others);

		if (data.data.members && data.data.members.length > 0) {
			layout.add_non_flow_box_header({wide: true, title: language.gettext("discogs_bandmembers")});
			doMembers(layout, data.data.members);
		}
		if (data.data.groups && data.data.groups.length > 0) {
			layout.add_non_flow_box_header({wide: true, title: language.gettext("discogs_memberof")});
			doMembers(layout, data.data.groups);
		}

		layout.add_dropdown_box(
			layout.add_non_flow_box_header({wide: true}),
			'clickdodiscography',
			data.data.id,
			'discography_'+data.data.id,
			language.gettext("discogs_discography", [data.data.name.toUpperCase()])
		);

		layout.finish(data.data.uri, data.data.name);
	}

	function doMembers(layout, members) {
		members.forEach(function(member) {
			layout.add_dropdown_box(layout.add_non_flow_box(''), 'clickdoartist', member.id, 'artist_'+member.id, member.name.replace(/ \(\d+\)$/, ''));
		});
	}

	function formatNotes(artist, artistmeta, p) {
		p = p.replace(/\n/g, '<br>');
		// Inline links using an artist id
		p = p.replace(/\[a=*(\d+?)\]/g, '<span class="artists" name="$1">$1</span>');
		try {
			// Inline links using an artist name
			var reg = /\[a=*(.+?)\]/g;
			var matches = [...p.matchAll(reg)];
			for (var i in matches) {
				p = p.replace(matches[i][0], '<a href="https://www.discogs.com/artist/'+rawurlencode(matches[i][1])+'" target="_blank">'+matches[i][1]+'</a>')
			}
			// Same for inline label links
			p = p.replace(/\[l=*(.\d+?)\]/g, '<span class="labels" name="$1">$1</span>');
			reg = /\[l=*(.+?)\]/g;
			matches = [...p.matchAll(reg)];
			for (var i in matches) {
				p = p.replace(matches[i][0], '<a href="https://www.discogs.com/label/'+rawurlencode(matches[i][1])+'" target="_blank">'+matches[i][1]+'</a>')
			}
			// Same for inline master links
			p = p.replace(/\[m=*(\d+?)\]/g, '<span class="masters" name="$1">$1</span>');
			var reg = /\[m=*(.+?)\]/g;
			var matches = [...p.matchAll(reg)];
			for (var i in matches) {
				p = p.replace(matches[i][0], '<a href="https://www.discogs.com/master/'+rawurlencode(matches[i][1])+'" target="_blank">'+matches[i][1]+'</a>')
			}
			// Same for inline release links
			p = p.replace(/\[r=*(\d+?)\]/g, '<span class="releases" name="$1">$1</span>');
			var reg = /\[r=*(.+?)\]/g;
			var matches = [...p.matchAll(reg)];
			for (var i in matches) {
				p = p.replace(matches[i][0], '<a href="https://www.discogs.com/release/'+rawurlencode(matches[i][1])+'" target="_blank">'+matches[i][1]+'</a>')
			}
		} catch (err) {
			debug.warn('DISCOGS', 'Old browser, matchAll not supported');
		}

		p = p.replace(/\[url=*(.+?)\](.+?)\[\/url\]/g, '<a href="$1" target="_blank">$2</a>');
		p = p.replace(/\[b\]/g, '<b>');
		p = p.replace(/\[\/b\]/g, '</b>');
		p = p.replace(/\[i\]/g, '<i>');
		p = p.replace(/\[\/i\]/g, '</i>');

		// Discogs profiles come with a bunch of references to other artists formatted as [a123456]
		// (where 123456 is the discogs artist id). formatNotes replaces these with spans so we can
		// get the arist bio and update the displayed items without having to resort to replacing
		// all the html in the div every time.
		// To avoid getting the artist data every time we display the html, we'll also check to see
		// if we already have the data and use it now if we do.

		var m = p.match(/<span class="artists" name="\d+">/g);
		if (m) {
			for(var i in m) {
				var n = m[i].match(/<span class="artists" name="(\d+)">/);
				if (n && n[1]) {
					debug.trace(medebug,"Found unpopulated artist reference",n[1]);
					if (artistmeta.discogs['artist_'+n[1]] === undefined) {
						discogs.artist.getInfo(
							n[1],
							n[1],
							artist.extraResponseHandler2,
							artist.extraResponseHandler2
						);
					} else {
						var name = artistmeta.discogs['artist_'+n[1]].data.name;
						var link = artistmeta.discogs['artist_'+n[1]].data.uri;
						p = p.replace(new RegExp('<span name="'+n[1]+'">'+n[1]+'<\/span>', 'g'), '<a href="'+link+'" target="_blank">'+name+'</a>');
					}
				}
			}
		}

		var m = p.match(/<span class="masters" name="\d+">/g);
		if (m) {
			for(var i in m) {
				var n = m[i].match(/<span class="masters" name="(\d+)">/);
				if (n && n[1]) {
					debug.trace(medebug, "Found unpopulated master reference", n[1]);
					discogs.album.getInfo(
						n[1],
						'masters/'+n[1],
						artist.gotExtraAlbumInfo,
						artist.gotExtraAlbumInfo
					);
				}
			}
		}

		var m = p.match(/<span class="releases" name="\d+">/g);
		if (m) {
			for(var i in m) {
				var n = m[i].match(/<span class="releases" name="(\d+)">/);
				if (n && n[1]) {
					debug.trace(medebug, "Found unpopulated release reference", n[1]);
					discogs.album.getInfo(
						n[1],
						'releases/'+n[1],
						artist.gotExtraAlbumInfo2,
						artist.gotExtraAlbumInfo2
					);
				}
			}
		}

		var m = p.match(/<span class="labels" name="\d+">/g);
		if (m) {
			for(var i in m) {
				var n = m[i].match(/<span class="labels" name="(\d+)">/);
				if (n && n[1]) {
					debug.trace(medebug, "Found unpopulated label reference", n[1]);
					discogs.label.getInfo(
						n[1],
						n[1],
						artist.gotExtraLabelInfo,
						artist.gotExtraLabelInfo
					);
				}
			}
		}

		return p;
	}

	function getAlbumHTML(layout, artist, artistmeta, data, order) {
		debug.debug(medebug,"Creating HTML from release/master data",data);

		if (data.error) {
			layout.display_error(data.error);
			layout.finish(null, null);
			return;
		}

		for (var i in order) {
			try {
				let u = layout.add_sidebar_list(language.gettext("discogs_styles"));
				data[order[i]].data.styles.forEach(function(style) {
					u.append($('<li>').html(style));
				});
				break;
			} catch (err) {
				debug.trace('DISCOGS', 'No album data for', order[i]);
			}
		}

		for (var i in order) {
			try {
				let u = layout.add_sidebar_list(language.gettext("discogs_genres"));
				data[order[i]].data.genres.forEach(function(genre) {
					u.append($('<li>').html(genre));
				});
				break;
			} catch (err) {
				debug.trace('DISCOGS', 'No album data for', order[i]);
			}
		}

		try {
			let u = layout.add_sidebar_list(language.gettext("discogs_companies"));
			data.release.data.companies.forEach(function(company) {
				u.append($('<li>').html(company.entity_type_name+' '+company.name));
			});
		} catch (err) {
			debug.trace('DISCOGS', 'No album data for release');
		}

		var image = null;
		for (var i in order) {
			try {
				image = getBestImage(data[order[i]].data.images);
				if (image !== null) {
					break;
				}
			} catch (err) {
				debug.trace('DISCOGS', 'No album data for', order[i]);
			}
		}
		if (image !== null)
			layout.add_main_image(image);

		for (var i in order) {
			try {
				let others = get_other_images(data[order[i]].data.images, image);
				others.forEach(function(image) {
					if (!artist.images_used.includes(image)) {
						layout.add_sidebar_image(image, image);
						artist.images_used.push(image);
					}
				});
			} catch (err) {
				debug.trace('DISCOGS', 'No album data for', order[i]);
			}
		}

		if (data.master && data.master.data && data.master.data.notes)
			layout.add_profile(formatNotes(artist, artistmeta, data.master.data.notes));

		if (data.release && data.release.data && data.release.data.notes)
			layout.add_profile(formatNotes(artist, artistmeta, data.release.data.notes));

		if (data.release && data.release.data && data.release.data.extraartists && data.release.data.extraartists.length > 0) {
			layout.add_flow_box_header({title: language.gettext("discogs_personnel")});
			data.release.data.extraartists.forEach(function(artist) {
				layout.add_flow_box(artist.role+' <b>'+artist.name+'</b>');
			});
		}

		for (var i in order) {
			try {
				layout.add_flow_box_header({wide: true, title: language.gettext("discogs_tracklisting")});
				layout.add_flow_box(getTracklist(data[order[i]].data.tracklist));
				break;
			} catch (err) {
				debug.trace('DISCOGS', 'No album data for', order[i]);
			}
		}

		try {
			layout.finish(data.master.data.uri, data.master.data.title);
		} catch (err) {
			try {
				layout.finish(data.release.uri, data.release.title);
			} catch (err) {
				layout.finish(null, 'No name');
			}
 		}

	}

	function getURLs(layout, urls) {
		if (!urls || urls.length == 0)
			return;

		var u = layout.add_sidebar_list(language.gettext('discogs_external'));
		u.addClass('info-links-column');
		var links = [];
		urls.reverse();
		urls.forEach(function(url) {
			if (url) {
				let d = url.match(/https*:\/\/(.*?)(\/|$)/);
				if (!d) {
					d = [url, url];
					url = 'http://'+url;
				}
				let item = $('<li>');
				let icon = $('<i>', {class: 'smallicon'}).appendTo(item);
				let link = $('<a>', {href: url, target: '_blank'}).appendTo(item);
				if (url.match(/wikipedia/i)) {
					icon.addClass('icon-wikipedia');
					link.html('Wikipedia ('+d[1]+')');
					links.unshift(item);
				} else if (url.match(/facebook/i)) {
					icon.addClass('icon-facebook-logo');
					link.html('Facebook');
					links.unshift(item);
				} else if (url.match(/soundcloud/i)) {
					icon.addClass('icon-soundcloud-circled');
					link.html('Soundcloud');
					links.unshift(item);
				} else if (url.match(/twitter.com/i)) {
					icon.addClass('icon-twitter-logo');
					link.html('Twitter');
					links.unshift(item);
				} else if (url.match(/last.fm/i)) {
					icon.addClass('icon-lastfm-1');
					link.html('Last.FM');
					links.unshift(item);
				} else if (url.match(/bandcamp.com/i)) {
					icon.addClass('icon-bandcamp-circled');
					link.html('Bandcamp');
					links.unshift(item);
				} else if (url.match(/youtube.com/i)) {
					icon.addClass('icon-youtube-circled');
					link.html('Youtube');
					links.unshift(item);
				} else if (url.match(/imdb.com/i)) {
					icon.addClass('icon-imdb-logo');
					link.html('IMDB');
					links.unshift(item);
				} else {
					icon.addClass('icon-noicon');
					link.html(d[1]);
					links.push(item);
				}
			}
		});
		for (var l of links) {
			u.append(l);
		}
	}

	function getBestImage(images) {
		if (!images)
			return null;

		var maxsize = 0;
		var image = null;
		let primaries = images.filter(i => i.type == 'primary');
		primaries.forEach(function(im) {
			if (im.resource_url && (im.height*im.width) > maxsize) {
				maxsize = im.height*im.width;
				image = im.resource_url;
			}
		});
		if (image)
			return image;

		let secondaries = images.filter(i => i.type == 'secondary');
		secondaries.forEach(function(im) {
			if (im.resource_url && (im.height*im.width) > maxsize) {
				maxsize = im.height*im.width;
				image = im.resource_url;
			}
		});

		return image;
	}

	function get_other_images(images, image) {
		if (images)
			return images.filter(i => i.resource_url != image).map(r => r.resource_url);

		return [];
	}

	function compareArtists(art1, art2) {
		var a1 = mungeArtist(art1);
		var a2 = mungeArtist(art2);
		if (a1 == a2) {
			return true;
		}
		return tryWithoutAccents(a1, a2);
	}

	function tryWithoutAccents(a1, a2) {
		return (a1.normalize("NFD").replace(/[\u0300-\u036f]/g, "") == a2.normalize("NFD").replace(/[\u0300-\u036f]/g, ""));
	}

	function mungeArtist(n) {
		n = n.replace(/ \(\d+\)$/, '');
		var p = n.split(' ');
		for (var i in prefs.nosortprefixes) {
			if (p[0].toLowerCase() == prefs.nosortprefixes[i].toLowerCase()) {
				p.shift();
				break;
			}
		}
		var retval = p.join(' ').toLowerCase();
		retval.replace(/ featuring .+$/, '');
		retval.replace(/ feat\. .+$/, '');
		debug.debug(medebug,'Munged artist',n,'to',retval);
		return retval;
	}

	function getReleaseHTML(data) {

		if (!data.data)
			return '';

		if (data.data.releases.length == 0)
			return '';

		var html = $('<div>');
		var pagbox = $('<span>', {style: 'float: right'}).appendTo($('<div>', {class: 'mbbox clearfix'}).appendTo(html));
		pagbox.html('PAGES: ');
		for (var i = 1; i <= data.data.pagination.pages; i++) {
			if (i == data.data.pagination.page) {
				pagbox.append(" ").append($('<b>').html(i));
			} else {
				var a = data.data.pagination.urls.last || data.data.pagination.urls.first;
				var b = a.match(/artists\/(\d+)\/releases/);
				if (b && b[1]) {
					pagbox.append(" ").append($('<a>', {href: '#', class: 'infoclick clickreleasepage', name: b[1]}).html(i));
				}
			}
		}

		var table = $('<table>', {class: 'padded', width: '100%'}).appendTo($('<div>', {class: 'mbbox clearfix'}).appendTo(html));
		var row = $('<tr>').appendTo(table);
		['', 'title_title', 'title_year', 'label_artist', 'title_type', 'title_label'].forEach(function(t) {
			row.append($('<th>').html(language.gettext(t)));
		});
		data.data.releases.forEach(function(release) {
			row = $('<tr>').appendTo(table);

			var cell = $('<td>').appendTo(row);
			if (release.thumb) {
				cell.append($('<div>', {class: 'smallcover'})).append($('<img>', {class: 'smallcover', src: 'getRemoteImage.php?url='+rawurlencode(release.thumb)}));
			}

			cell = $('<td>').appendTo(row);
			if (release.title) {
				cell.append($('<a>', {href: '#', class: 'infoclick clickgetdiscstuff', target: '_blank'}).html(release.title));
				cell.append($('<input>', {type: 'hidden'}).val(release.resource_url));
				if (release.role && release.role != 'Main') {
					cell.append($('<br>'));
					cell.append($('<i>').html(release.role.replace(/([a-z])([A-Z])/, '$1 $2')));
				}
				if (release.trackinfo) {
					cell.append($('<br>'));
					cell.append($('<i>').html('('+release.trackinfo+')'));
				}
			}

			['year', 'artist', 'format', 'label'].forEach(function(c) {
				cell = $('<td>').appendTo(row);
				if (release[c])
					cell.html(release[c]);
			});
		});

		return html.html();
	}

	function getTracklist(tracks) {
		var table = $('<table>',  {class: 'padded'});
		tracks.forEach(function(track) {
			var row = $('<tr>').appendTo(table);
			$('<td>').html(track.position ? track.position : '').appendTo(row);
			$('<td>').html('<b>'+track.title+'</b>'+getTrackArtists(track)).appendTo(row);
			$('<td>').html(track.duration ? track.duration : '').appendTo(row);
		});
		return table;
	}

	function getTrackArtists(track) {
		var main = '';
		var extra = '';
		if (track.artists)
			main = '<br />'+track.artists.map(function(a) { return '<i>'+a.name+'</i>' }).join('<br />');

		if (track.extraartists) {
			extra = '<br />'+track.extraartists.map(function(a) {
				return '<i>'+a.role+' - <b>'+a.name+'</b></i>';
			}).join('<br />');
		}

		return main+extra;
	}

	function get_disc_info(source, element, event) {
		var link = element.next().val();
		var b = link.match(/(releases\/\d+)|(masters\/\d+)/);
		if (b && b[0]) {
			debug.debug("DISCOGS","Getting info for",b[0])
			discogs.album.getInfo('', b[0],
				function(data) {
					debug.debug("DISCOGS", "Got Data",data);
					if (data.data.uri) {
						debug.debug("DISCOGS", "Opening Data",data.data.uri);
						var box = element.parent();
						box.empty();
						var newlink = $('<a href="'+data.data.uri+'" target="_blank">'+data.data.title+'</a>').appendTo(box);
						window.open(data.data.uri, '_blank');
					}
				},
				function(data) {
					infobar.error(language.gettext('label_general_error'));
				}
			);
		}
	}

	return {

		getRequirements: function(parent) {
			return ["musicbrainz"];
		},

		collection: function(parent, artistmeta, albummeta, trackmeta) {

			debug.debug(medebug, "Creating data collection");

			var self = this;

			this.populate = function() {
				debug.debug(medebug,'Populating');
				parent.updateData({
						discogs: {
							populated: false,
							possibilities: [],
							currentposs: 0,
							artistid: ''
						},
					},
					artistmeta
				);
				parent.updateData({
						discogs: {
							populated: false,
							releaseid: '',
							masterid: '',
						},
					},
					albummeta
				);
				parent.updateData({
						discogs: {
							populated: false,
							releaseid: '',
							masterid: '',
						},
						triggers: {
							discogs: {
								releaselink: self.verify_data
							}
						}
					},
					trackmeta
				);

				browser.setup_radio_nondisplay_panel(self, artistmeta, albummeta, trackmeta, me, parent.playlistinfo);

				// At this point, we may already have link data from musicbrainz or we may not.
				self.verify_data();
			}

			this.verify_data = async function() {

				if (artistmeta.discogs.artistlink == '' || albummeta.discogs.releaselink == '' || trackmeta.discogs.releaselink == '')
					return;

				if (artistmeta.discogs.populated && albummeta.discogs.populated && trackmeta.discogs.populated)
					return;

				discogs.verify_data({
						artist: {
							name: artistmeta.name,
							artistlink: artistmeta.discogs.artistlink,
						},
						album: {
							name: albummeta.name,
							artist: getSearchArtistForAlbum(),
							masterlink: albummeta.discogs.masterlink,
							releaselink: albummeta.discogs.releaselink
						},
						track: {
							name: trackmeta.name,
							artist: trackmeta.artist,
							masterlink: trackmeta.discogs.masterlink,
							releaselink: trackmeta.discogs.releaselink
						}
					},
					self.diVerifyResult,
					self.diFailResult
				);
			}

			this.diVerifyResult = function(data) {
				debug.mark('DISCOGS', 'Got Verified Data', data);

				for (var i in data.data.metadata.artist) {
					if (typeof(artistmeta.discogs[i]) == 'undefined')
						artistmeta.discogs[i] = {data: data.data.metadata.artist[i]}
				}

				for (var i in data.data.metadata.album) {
					if (typeof(albummeta.discogs[i]) == 'undefined')
	 					albummeta.discogs[i] = {data: data.data.metadata.album[i]}
				}

				for (var i in data.data.metadata.track) {
					if (typeof(trackmeta.discogs[i]) == 'undefined')
						trackmeta.discogs[i] = {data: data.data.metadata.track[i]}
				}

				parent.updateData(
					data.data.artistmeta,
					artistmeta
				);

				parent.updateData(
					data.data.albummeta,
					albummeta
				);

				parent.updateData(
					data.data.trackmeta,
					trackmeta
				);

				self.artist.populate();
				self.album.populate();
				self.track.populate();
			}

			this.diFailResult =  function(data) {
				debug.error('DISCOGS', 'Error Getting Verified Data', data);
			}

			this.handleClick = function(source, element, event) {
				debug.debug(medebug,parent.nowplayingindex,source,"is handling a click event");
				if (element.hasClass('clickdoartist')) {
					open_artist(source, element, event);
				} else if (element.hasClass('clickreleasepage')) {
					release_pager(source, element, event);
				} else if (element.hasClass('clickdodiscography')) {
					do_discography(source, element, event);
				} else if (element.hasClass('clickzoomimage')) {
					imagePopup.create(element, event, element.next().val());
				} else if (element.hasClass('clickgetdiscstuff')) {
					get_disc_info(source, element, event);
				} else if (element.hasClass('clickexpandbox')) {
					let artistid = 'artist_'+element.attr('name');
					info_panel_expand_box(
						source, element, event,
						artistmeta.discogs[artistid].data.name, me, artistmeta.discogs[artistid].data.uri
					);
				} else if (element.hasClass('clickchooseposs')) {
					choose_possibility(source, element, event);
				}
			}

			function open_artist(source, element, event) {
				var targetdiv = element.parent().next();
				if (!(targetdiv.hasClass('full')) && element.isClosed()) {
					targetdiv.doSomethingUseful(language.gettext("info_gettinginfo")).slideToggle('fast');
					getArtistData(element.attr('name'));
					element.toggleOpen();
					targetdiv.addClass('underline');
				} else {
					var id = element.attr('name');
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

			function release_pager(source, element, event) {
				var targetdiv = element.parent().parent().parent().attr("name");
				element.parent().parent().parent().addClass("expectingpage_"+element.text());
				element.parent().parent().doSomethingUseful(language.gettext("info_gettinginfo"));
				getArtistReleases(element.attr('name'), element.text());
			}

			function do_discography(source, element, event) {
				var targetdiv = element.parent().next();
				if (!(targetdiv.hasClass('full')) && element.isClosed()) {
					targetdiv.doSomethingUseful(language.gettext("info_gettinginfo"));
					targetdiv.addClass("expectingpage_1");
					getArtistReleases(element.attr('name'), 1);
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

			function choose_possibility(source, element, event) {
				// Re-use the current layout, just empty it and refill
				var poss = element.attr('name');
				if (poss != artistmeta.discogs.currentposs) {
					var l = artistmeta.discogs.layout;
					artistmeta.discogs = {
						currentposs: poss,
						possibilities: artistmeta.discogs.possibilities,
						artistlink: artistmeta.discogs.possibilities[poss].link,
						artistid: undefined,
						layout: l
					}
					artistmeta.discogs.layout.clear_out();
					// browser.panel_updating(parent.nowplayingindex,'artist', {name: element.find('.spotpossname').html()});
					self.artist.populate();
				}
			}

			function getArtistData(id) {
				debug.debug(medebug,parent.nowplayingindex,"Getting data for artist with ID",id);
				if (artistmeta.discogs['artist_'+id] === undefined) {
					debug.debug(medebug,parent.nowplayingindex," ... retrieivng data");
					discogs.artist.getInfo(
						'artist_'+id,
						id,
						self.artist.extraResponseHandler,
						self.artist.extraResponseHandler
					);
				} else {
					debug.debug(medebug,parent.nowplayingindex," ... displaying what we've already got");
					putArtistData("artist_"+id);
				}
			}

			function putArtistData(id) {
				// We did once try this by making a layout for every artist and just re-using them
				// but that results in lots of unplesantness including panels being open because you opened
				// them earlier in another layout, and circular references where the child contains the parent.
				var layout = new info_sidebar_layout({
					expand: true,
					expandid: artistmeta.discogs[id].data.id,
					title: artistmeta.discogs[id].data.id.name,
					type: 'artist',
					source: me,
					withbannerid: false
				});
				getArtistHTML(layout, self.artist, artistmeta, artistmeta.discogs[id], true);
				$('div[name="'+id+'"]').each(function() {
					if (!$(this).hasClass('full')) {
						$(this).empty().append(layout.get_contents());
						$(this).addClass('full');
					}
				});
			}

			function getArtistReleases(name, page) {
				debug.debug(medebug,parent.nowplayingindex,"Looking for release info for",name,"page",page);
				if (artistmeta.discogs['discography_'+name+"_"+page] === undefined) {
					debug.debug(medebug,"  ... retreiving them");
					discogs.artist.getReleases(
						name,
						page,
						'discography_'+name,
						self.artist.releaseResponseHandler,
						self.artist.releaseResponseHandler
					);
				} else {
					debug.debug(medebug,"  ... displaying what we've already got",artistmeta.discogs['discography_'+name+"_"+page]);
					putArtistReleases(artistmeta.discogs['discography_'+name+"_"+page], 'discography_'+name);
				}
			}

			function putArtistReleases(data, div) {
				var html = getReleaseHTML(data);
				$('div[name="'+div+'"]').each(function() {
					if ($(this).hasClass('expectingpage_'+data.data.pagination.page)) {
						$(this).html(html);
						$(this).addClass('full');
						$(this).removeClass('expectingpage_'+data.data.pagination.page);
					}
				});
			}

			function getSearchArtistForAlbum() {
				var a = (albummeta.artist && albummeta.artist != "") ? albummeta.artist : trackmeta.artist;
				if (a == "Various Artists") {
					a = "Various";
				}
				return a;
			}

			function getSearchArtist() {
				return artistmeta.name;
			}

			this.artist = function() {

				return {

					images_used: new Array(),

					populate: function() {
						if (artistmeta.discogs.populated == false) {
							if (artistmeta.discogs.artistid && artistmeta.discogs['artist_'+artistmeta.discogs.artistid]) {
								self.artist.doBrowserUpdate(artistmeta.discogs['artist_'+artistmeta.discogs.artistid]);
							} else {
								self.artist.doBrowserUpdate({error: language.gettext("discogs_nonsense")});
							}
							artistmeta.discogs.populated = true;
						}
					},

					// get_random_image: async function() {
					// 	while (typeof artistmeta.discogs.artistid == 'undefined' || typeof artistmeta.discogs['artist_'+artistmeta.discogs.artistid] == 'undefined') {
					// 		await new Promise(x => setTimeout(x, 500));
					// 	}
					// 	if (artistmeta.discogs['artist_'+artistmeta.discogs.artistid].data.images) {
					// 		var images = get_other_images(artistmeta.discogs['artist_'+artistmeta.discogs.artistid].data.images, 'notanimage');
					// 		debug.mark(medebug, 'Random Images', images);
					// 		return images[Math.floor(Math.random()*images.length)];
					// 	} else {
					// 		return null;
					// 	}
					// },


					extraResponseHandler: function(data) {
						debug.debug(medebug,parent.nowplayingindex,"got extra artist data for",data.id,data);
						if (data) {
							artistmeta.discogs[data.id] = data;
							putArtistData(data.id);
						}
					},

					extraResponseHandler2: function(data) {
						debug.debug(medebug,parent.nowplayingindex,"got stupidly extra artist data for",data.id,data);
						if (data) {
							artistmeta.discogs['artist_'+data.id] = data;
							if (data.data) {
								// Fill in any [a123456] or [a=123456] links in the main text body.
								artistmeta.discogs.layout.fill_in_element('span.artists[name="'+data.data.id+'"]', data.data.name);
								artistmeta.discogs.layout.element_to_link('span.artists[name="'+data.data.id+'"]', data.data.uri);
							}
						}
					},

					gotExtraAlbumInfo: function(data) {
						debug.debug(medebug, 'got extra album info',data);
						if (data) {
							artistmeta.discogs['masters_'+data.id] = data;
							if (data.data) {
								// Fill in any [m123456] or [m=123456] links in the main text body.
								artistmeta.discogs.layout.fill_in_element('span.masters[name="'+data.data.id+'"]', data.data.title);
								artistmeta.discogs.layout.element_to_link('span.masters[name="'+data.data.id+'"]', data.data.uri);
							}
						}
					},

					gotExtraAlbumInfo2: function(data) {
						debug.debug(medebug, 'got extra album info',data);
						if (data) {
							artistmeta.discogs['releases_'+data.id] = data;
							if (data.data) {
								// Fill in any [r123456] or [r=123456] links in the main text body.
								artistmeta.discogs.layout.fill_in_element('span.releases[name="'+data.data.id+'"]', data.data.title);
								artistmeta.discogs.layout.element_to_link('span.releases[name="'+data.data.id+'"]', data.data.uri);
							}
						}
					},

					gotExtraLabelInfo: function(data) {
						debug.debug(medebug, 'got extra label info',data);
						if (data && data.data) {
							// Fill in any [l123456] or [l=123456] links in the main text body.
							artistmeta.discogs.layout.fill_in_element('span.labels[name="'+data.data.id+'"]', data.data.name);
							artistmeta.discogs.layout.element_to_link('span.labels[name="'+data.data.id+'"]', data.data.uri);
						}
					},

					releaseResponseHandler: function(data) {
						debug.debug(medebug,parent.nowplayingindex,"got release data for",data.id,data);
						if (data) {
							artistmeta.discogs[data.id+"_"+data.data.pagination.page] = data;
							putArtistReleases(artistmeta.discogs[data.id+"_"+data.data.pagination.page], data.id);
						}
					},

					doBrowserUpdate: function(data) {
						getArtistHTML(artistmeta.discogs.layout, self.artist, artistmeta, data, false);
					}
				}
			}();

			this.album = function() {

				return {

					populate: function() {

						if (albummeta.discogs.populated == false) {
							if ((albummeta.discogs.releaseid && albummeta.discogs.release) || (albummeta.discogs.masterid && albummeta.discogs.master)) {
								self.album.doBrowserUpdate(albummeta.discogs);
							} else {
								self.album.doBrowserUpdate({error: language.gettext("discogs_noalbum")});
							}
							albummeta.discogs.populated = true;
						}

					},

					doBrowserUpdate: function(data) {
						getAlbumHTML(albummeta.discogs.layout, self.artist, artistmeta, data, ['master', 'release']);
					}

				}
			}();

			this.track = function() {

				return {

					populate: function() {

						if (trackmeta.discogs.populated == false) {
							if ((trackmeta.discogs.releaseid && trackmeta.discogs.release) || (trackmeta.discogs.masterid && trackmeta.discogs.master)) {
								self.track.doBrowserUpdate(trackmeta.discogs);
							} else {
								self.track.doBrowserUpdate({error: language.gettext("discogs_notrack")});
							}
							trackmeta.discogs.populated = true;
						}

					},

					doBrowserUpdate: function(data) {
						getAlbumHTML(trackmeta.discogs.layout, self.artist, artistmeta, data, ['master', 'release']);
					}

				}
			}();
		}
	}
}();

nowplaying.registerPlugin("discogs", info_discogs, "icon-discogs", "button_discogs");
