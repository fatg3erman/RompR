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
			if (data[order[i]] && data[order[i]].data.styles && data[order[i]].data.styles.length > 0) {
				let u = layout.add_sidebar_list(language.gettext("discogs_styles"));
				data[order[i]].data.styles.forEach(function(style) {
					u.append($('<li>').html(style));
				});
				break;
			}
		}

		for (var i in order) {
			if (data[order[i]] && data[order[i]].data.genres && data[order[i]].data.genres.length > 0) {
				let u = layout.add_sidebar_list(language.gettext("discogs_genres"));
				data[order[i]].data.genres.forEach(function(genre) {
					u.append($('<li>').html(genre));
				});
				break;
			}
		}

		if (data.release && data.release.data.companies && data.release.data.companies.length > 0) {
			let u = layout.add_sidebar_list(language.gettext("discogs_companies"));
			data.release.data.companies.forEach(function(company) {
				u.append($('<li>').html(company.entity_type_name+' '+company.name));
			});
		}

		var image = null;
		for (var i in order) {
			if (data[order[i]] && data[order[i]].data.images) {
				image = getBestImage(data[order[i]].data.images);
				if (image !== null) {
					break;
				}
			}
		}
		if (image !== null)
			layout.add_main_image(image);

		for (var i in order) {
			if (data[order[i]] && data[order[i]].data.images) {
				let others = get_other_images(data[order[i]].data.images, image);
				others.forEach(function(image) {
					if (!artist.images_used.includes(image)) {
						layout.add_sidebar_image(image, image);
						artist.images_used.push(image);
					}
				});
			}
		}

		if (data.master && data.master.data.notes)
			layout.add_profile(formatNotes(artist, artistmeta, data.master.data.notes));

		if (data.release && data.release.data.notes)
			layout.add_profile(formatNotes(artist, artistmeta, data.release.data.notes));

		if (data.release && data.release.data.extraartists && data.release.data.extraartists.length > 0) {
			layout.add_flow_box_header({title: language.gettext("discogs_personnel")});
			data.release.data.extraartists.forEach(function(artist) {
				layout.add_flow_box(artist.role+' <b>'+artist.name+'</b>');
			});
		}

		for (var i in order) {
			if (data[order[i]] && data[order[i]].data.tracklist && data[order[i]].data.tracklist.length > 0) {
				layout.add_flow_box_header({wide: true, title: language.gettext("discogs_tracklisting")});
				layout.add_flow_box(getTracklist(data[order[i]].data.tracklist));
				break;
			}
		}

		layout.finish(data.master.data.uri, data.master.data.title);

	}

	function getURLs(layout, urls) {
		if (!urls || urls.length == 0)
			return;

		var u = layout.add_sidebar_list(language.gettext('discogs_external'));
		urls.forEach(function(url) {
			if (url) {
				let d = url.match(/https*:\/\/(.*?)(\/|$)/);
				if (!d) {
					d = [url, url];
					url = 'http://'+url;
				}
				let icon = $('<i>', {class: 'smallicon padright'}).appendTo($('<li>').appendTo(u));
				let link = $('<a>', {href: url, target: '_blank'}).insertAfter(icon);
				if (url.match(/wikipedia/i)) {
					icon.addClass('icon-wikipedia');
					link.html('Wikipedia ('+d[1]+')');
				} else if (url.match(/facebook/i)) {
					icon.addClass('icon-facebook-logo');
					link.html('Facebook');
				} else {
					icon.addClass('icon-noicon');
					link.html(d[1]);
				}
			}
		});
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
							searchparam: 0
						}
					},
					artistmeta
				);
				parent.updateData({
						discogs: {
							triedmusicbrainz: false,
							searchparam: 0,
							album: {}
						}
					},
					albummeta
				);
				parent.updateData({
						discogs: {
							searchparam: 0,
							track: {}
						}
					},
					trackmeta
				);
				if (typeof trackmeta.discogs.layout == 'undefined')
					self.track.populate();

				if (typeof albummeta.discogs.layout == 'undefined')
					self.album.populate();

				if (typeof artistmeta.discogs.layout == 'undefined')
					self.artist.populate();

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
				var a = (albummeta.artist && albummeta.artist != "") ? albummeta.artist : parent.playlistinfo.trackartist;
				if (a == "Various Artists") {
					a = "Various";
				}
				return a;
			}

			function getSearchArtist() {
				return artistmeta.name;
			}

			this.artist = function() {

				var retries = 50;
				var searching = false;

				return {

					images_used: new Array(),

					populate: async function() {

						// The link will either be /artist/[number] in which case we can use it
						// Or it'll be /artist/Artist+Name which we can't use

						// artistlink is what musicbrainz will try to give us. If it's undefined it means musicbrainz
						//		hasn't yet come up with any sort of answer. If it's null it means musicbrainz failed to find one
						//		or we gave up waiting for musicbrainz.
						// artistid is what we're trying to find. (All we have at the initial stage is an artist name).
						//		If it's undefined it means we haven't even looked yet. If it's null it means we looked and failed.

						// Let's look at what we're doing here, because this is hard.
						// 1. Wait to see if musicbrainz can find a link to discogs for us. This is good because it will
						//    have come via the MUSICBRAINZ_ARTISTID tag (althought possible it might have come from an MB tag found by Last.FM)
						// 2. We wait for up to 20 seconds for that to happen, then give up. If MB doesn't find one it sents it to null.
						// 3. In the meantime, the track may have found an aristuri for us. This is likely to be accurate, and probably more
						//    accurate than the one MB gave us (since that may have come bia Last.FM) so if there is one, we use it.
						// 4. If we didn't get a link, or it didn't work, we search. First we search for the artist name as it stands.
						//    Then if that didn't give us anything useful we munge it (remove (digits) from the end, The from the beginning
						//    ' featuring some shithead' from the end etc) and try again.

						debug.mark('DISCOGS ARTIST', 'Populating');
						if (typeof artistmeta.discogs.layout == 'undefined')
							artistmeta.discogs.layout = new info_sidebar_layout({title: artistmeta.name, type: 'artist', source: me});

						while (typeof artistmeta.discogs.artistid == 'undefined') {
							while (typeof artistmeta.discogs.artistlink == 'undefined' && typeof artistmeta.discogs.artisturi == 'undefined' && retries > 0) {
								retries--;
								await new Promise(t => setTimeout(t, 500));
							}
							debug.trace(medebug,parent.nowplayingindex,'Looking for artistid',artistmeta.discogs.artistlink, artistmeta.discogs.artisturi);
							if (!searching) {
								var link = (artistmeta.discogs.artisturi === undefined) ? artistmeta.discogs.artistlink : artistmeta.discogs.artisturi;
								if (link) {
									var s = link.split('/').pop()
									if (s.match(/^\d+$/)) {
										debug.debug(medebug,parent.nowplayingindex,"Found aristid",s);
										artistmeta.discogs.artistid = s;
									} else {
										self.artist.search();
									}
								} else {
									self.artist.search();
								}
							}
							await new Promise(t => setTimeout(t, 1000));
						}
						if (artistmeta.discogs['artist_'+artistmeta.discogs.artistid] === undefined) {
							discogs.artist.getInfo(
								'artist_'+artistmeta.discogs.artistid,
								artistmeta.discogs.artistid,
								self.artist.artistResponseHandler,
								self.artist.artistResponseHandler
							);
						}
					},

					get_random_image: async function() {
						while (typeof artistmeta.discogs.artistid == 'undefined' || typeof artistmeta.discogs['artist_'+artistmeta.discogs.artistid] == 'undefined') {
							await new Promise(x => setTimeout(x, 500));
						}
						if (artistmeta.discogs['artist_'+artistmeta.discogs.artistid].data.images) {
							var images = get_other_images(artistmeta.discogs['artist_'+artistmeta.discogs.artistid].data.images, 'notanimage');
							debug.mark(medebug, 'Random Images', images);
							return images[Math.floor(Math.random()*images.length)];
						} else {
							return null;
						}
					},

					search: function() {
						switch (artistmeta.discogs.searchparam) {
							case 0:
								var name = getSearchArtist();;
								break;

							case 1:
								var name = mungeArtist(getSearchArtist());
								break;

							case 2:
								self.artist.abjectFailure();
								return;
						}
						searching = true;
						debug.trace(medebug,'Searching for artist',name);
						discogs.artist.search(name, self.artist.searchResponse, self.artist.searchFailure);
					},

					searchResponse: function(data) {
						debug.debug(medebug,'Got artist search response', data);
						artistmeta.discogs.possibilities = new Array();
						if (data.data.results && data.data.results.length > 0) {
							for (var i in data.data.results) {
								switch (artistmeta.discogs.searchparam) {
									case 0:
										var amatch = getSearchArtist();;
										break;

									case 1:
										var amatch = mungeArtist(getSearchArtist());
										break;
								}
								if (compareArtists(data.data.results[i].title, amatch)) {
									debug.trace(medebug,'Found Artist index',i,data.data.results[i].title);
									artistmeta.discogs.possibilities.push({
										name: data.data.results[i].title,
										link: data.data.results[i].resource_url,
										image: data.data.results[i].thumb
									});
								}
							}
							if (artistmeta.discogs.possibilities.length > 0) {
								artistmeta.discogs.artistlink = artistmeta.discogs.possibilities[0].link;
								artistmeta.discogs.currentposs = 0;
							}
						}
						artistmeta.discogs.searchparam++;
						searching = false;
					},

					searchFailure: function() {
						artistmeta.discogs.artistid = '-1';
						self.artist.abjectFailure();
					},

					artistResponseHandler: function(data) {
						debug.debug(medebug,parent.nowplayingindex,"got artist data",data);
						if (data) {
							artistmeta.discogs['artist_'+artistmeta.discogs.artistid] = data;
							self.artist.doBrowserUpdate();
						} else {
							self.artist.abjectFailure();
						}
					},

					abjectFailure: function() {
						debug.info(medebug,"Failed to find any artist data");
						artistmeta.discogs.artistid = -1;
						artistmeta.discogs['artist_'+artistmeta.discogs.artistid] = {error: language.gettext("discogs_nonsense"), data: {uri: null}};
						parent.updateData({
							videos: {
								youtube: null
							}
						}, artistmeta);
						self.artist.doBrowserUpdate();
					},

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

					doBrowserUpdate: function() {
						getArtistHTML(artistmeta.discogs.layout, self.artist, artistmeta, artistmeta.discogs['artist_'+artistmeta.discogs.artistid], false);
					}
				}
			}();

			this.album = function() {

				var retries = 40;
				var searching = false;

				function try_album_release_group() {
					if (albummeta.musicbrainz.album_releasegroupid) {
						debug.debug(medebug,parent.nowplayingindex," ... trying the album release group");
						musicbrainz.releasegroup.getInfo(
							albummeta.musicbrainz.album_releasegroupid,
							'',
							self.album.mbRgHandler,
							self.album.mbRgHandler
						);
					} else {
						albummeta.discogs.triedmusicbrainz = true;
					}
				}

				function check_albumlink() {
					var b = albummeta.discogs.albumlink.match(/releases*\/(\d+)/);
					if (b && b[1]) {
						debug.debug(medebug,parent.nowplayingindex,"Album Link is release - Album is populating",b[1]);
						albummeta.discogs.albumid = b[1];
						albummeta.discogs.idtype = 'releases';
						return true;
					}
					b = albummeta.discogs.albumlink.match(/masters*\/(\d+)/);
					if (b && b[1]) {
						debug.debug(medebug,parent.nowplayingindex,"Album Link is master - Album is populating",b[1]);
						albummeta.discogs.albumid = b[1];
						albummeta.discogs.idtype = 'masters';
						return true;
					}
					albummeta.discogs.albumlink = null;
				}

				return {

					populate: async function() {

						debug.mark('DISCOGS ALBUM', 'Populating');
						if (parent.playlistinfo.type == 'stream') {
							albummeta.discogs.layout = new info_layout_empty();
							return;
						}
						albummeta.discogs.layout = new info_sidebar_layout({title: albummeta.name, type: 'album', source: me});
						while (typeof albummeta.discogs.albumid == 'undefined') {
							while (typeof albummeta.discogs.albumlink == 'undefined' && retries > 0) {
								// Wait to see if MusicBrainz finds anything
								retries--;
								await new Promise(t => setTimeout(t, 500));
							}
							while (albummeta.discogs.albumlink === null && albummeta.discogs.triedmusicbrainz === false) {
								try_album_release_group();
								await new Promise(t => setTimeout(t, 500));
							}
							if (!searching) {
								if (albummeta.discogs.albumlink) {
									check_albumlink();
								} else {
									self.album.search();
								}
							}
							await new Promise(t => setTimeout(t, 500));
						}
						if (Object.keys(albummeta.discogs.album).length == 0) {
							discogs.album.getInfo(
								albummeta.discogs.albumid,
								albummeta.discogs.idtype+'/'+albummeta.discogs.albumid,
								self.album.albumResponseHandler,
								self.album.abjectFailure
							);
						}
					},

					search: function() {
						switch (albummeta.discogs.searchparam) {
							case 0:
								var artist = getSearchArtistForAlbum();;
								break;

							case 1:
								var artist = mungeArtist(getSearchArtistForAlbum());
								break;

							case 2:
								self.album.abjectFailure();
								return;
						}
						searching = true;
						debug.trace(medebug, 'Searching for album',artist,albummeta.name);
						discogs.album.search(artist, albummeta.name, self.album.searchResponse, self.album.abjectFailure);
					},

					searchResponse: function(data) {
						debug.debug(medebug, 'Got album search results', data);
						albummeta.discogs.searchparam++;
						if (data.data.results && data.data.results.length > 0) {
							var best = -1;
							var besta = 0;
							find_best_album: {
								for (var i in data.data.results) {
									if (data.data.results[i].format && data.data.results[i].master_url) {
										for (var j in data.data.results[i].format) {
											if (getSearchArtistForAlbum() == "Various" && data.data.results[i].format[j] == "Compilation") {
												debug.trace(medebug, 'Using Compilation Result');
												best = i;
												break find_best_album;
											} else if (data.data.results[i].format[j] == 'Album' && besta == 0) {
												besta = i;
											}
										}
									}
								}
							}
							best = (best >= 0) ? best : besta;
							albummeta.discogs.albumlink = data.data.results[best].resource_url || data.data.results[best].master_url;
							debug.debug(medebug,'Using album search result', best, albummeta.discogs.albumlink);
						}
						searching = false;
					},

					albumResponseHandler: function(data) {
						debug.debug(medebug,parent.nowplayingindex,"Got album data",data);
						if (data.data.master_id) {
							// If this key exists, then we have retrieved a release page - this data is useful but we also
							// want the master release info. (Links that come to us from musicbrainz could be either master or release).
							// We will only display when we have the master info. Since we can't go back from master to release
							// then if we got a master link from musicbrainz that's all we're ever going to get.
							albummeta.discogs.album.release = data;
							discogs.album.getInfo(
								'',
								'masters/'+data.data.master_id,
								self.album.albumResponseHandler,
								self.album.abjectFailure
							);
						} else {
							albummeta.discogs.album.master = data;
							self.album.doBrowserUpdate();
						}
					},

					mbRgHandler: function(data) {
						debug.debug(medebug,parent.nowplayingindex,"got musicbrainz release group data for",parent.playlistinfo.album, data);
						if (data.error) {
							debug.warn(medebug,parent.nowplayingindex," ... MB error",data);
						} else {
							for (var i in data.relations) {
								if (data.relations[i].type == "discogs") {
									debug.trace(medebug,parent.nowplayingindex,"has found a Discogs album link from musicbrainz data",data.relations[i].url.resource);
									albummeta.discogs.albumlink = data.relations[i].url.resource;
								}
							}
						}
						albummeta.discogs.triedmusicbrainz = true;
					},

					abjectFailure: function() {
						debug.info(medebug,"Completely failed to find the album");
						albummeta.discogs.albumid = null;
						albummeta.discogs.album = {error: language.gettext("discogs_noalbum"), master: {data: {uri: null}}};
						self.album.doBrowserUpdate();
					},

					doBrowserUpdate: function() {
						getAlbumHTML(albummeta.discogs.layout, self.artist, artistmeta, albummeta.discogs.album, ['master', 'release']);
					}

				}
			}();

			this.track = function() {

				var retries = 40;
				var searching = false;

				function check_tracklink() {
					var b = trackmeta.discogs.tracklink.match(/releases*\/(\d+)/);
					if (b && b[1]) {
						debug.debug(medebug,parent.nowplayingindex,"Track Link is release - Track is populating",b[1]);
						trackmeta.discogs.trackid = b[1];
						trackmeta.discogs.idtype = 'releases';
						return true;
					}
					b = trackmeta.discogs.tracklink.match(/masters*\/(\d+)/);
					if (b && b[1]) {
						debug.debug(medebug,parent.nowplayingindex,"Track Link is master - Track is populating",b[1]);
						trackmeta.discogs.trackid = b[1];
						trackmeta.discogs.idtype = 'masters';
						return true;
					}
					trackmeta.discogs.tracklink = null;
				}

				return {

					populate: async function() {

						debug.mark('DISCOGS TRACK', 'Populating');
						trackmeta.discogs.layout = new info_sidebar_layout({title: trackmeta.name, type: 'track', source: me});
						while (typeof trackmeta.discogs.trackid == 'undefined') {
							while (typeof trackmeta.discogs.tracklink == 'undefined' && retries > 0) {
								// Wait to see if MusicBrainz finds anything
								retries--;
								await new Promise(t => setTimeout(t, 500));
							}
							if (!searching) {
								if (trackmeta.discogs.tracklink) {
									check_tracklink();
								} else {
									self.track.search();
								}
							}
							await new Promise(t => setTimeout(t, 500));
						}
						if (Object.keys(trackmeta.discogs.track).length == 0) {
							discogs.track.getInfo(
								trackmeta.discogs.trackid,
								trackmeta.discogs.idtype+'/'+trackmeta.discogs.trackid,
								self.track.trackResponseHandler,
								self.track.abjectFailure
							);
						}
					},

					search: function() {
						switch (trackmeta.discogs.searchparam) {
							case 0:
								var artist = artistmeta.name;
								break;

							case 1:
								var artist = mungeArtist(artistmeta.name);
								break;

							case 2:
								var artist = parent.playlistinfo.AlbumArtist;
								break;

							case 4:
								self.track.abjectFailure();
								return;
						}
						searching = true;
						debug.trace(medebug,'Searching for track',artist, trackmeta.name);
						discogs.track.search(artist, trackmeta.name, self.track.searchResponse, self.track.abjectFailure);
					},

					searchResponse: function(data) {
						debug.debug(medebug, 'Got track search results', data);
						if (data.data.results && data.data.results.length > 0) {
							var best = 0;
							find_best: {
								for (var i in data.data.results) {
									switch (trackmeta.discogs.searchparam) {
										case 0:
											var amatch = artistmeta.name;
											break;

										case 1:
											var amatch = mungeArtist(artistmeta.name);
											break;

										case 2:
											var amatch = parent.playlistinfo.AlbumArtist;
											break;

									}
									if (data.data.results[i].format && data.data.results[i].resource_url && data.data.results[i].title.toLowerCase() == amatch+' - '+trackmeta.name.toLowerCase()) {
										debug.debug(medebug,'Found Artist - Title match');
										for (var j in data.data.results[i].format) {
											if (data.data.results[i].format[j] == 'Single') {
												best = i;
												break find_best;
											}
										}
									}
								}
							}
							trackmeta.discogs.tracklink = data.data.results[best].resource_url;
							debug.debug(medebug,'Using track search result', best, trackmeta.discogs.tracklink);
						}
						trackmeta.discogs.searchparam++;
						searching = false;
					},

					trackResponseHandler: function(data) {
						debug.debug(medebug,parent.nowplayingindex,"Got track data",data);
						if (data.data.master_id) {
							// If this key exists, then we have retrieved a release page - this data is useful but we also
							// want the master release info. (Links that come to us from musicbrainz could be either master or release).
							// We will only display when we have the master info. Since we can't go back from master to release
							// then if we got a master link from musicbrainz that's all we're ever going to get.
							trackmeta.discogs.track.release = data;
							discogs.track.getInfo(
								'',
								'masters/'+data.data.master_id,
								self.track.trackResponseHandler,
								self.track.abjectFailure
							);
						} else {
							trackmeta.discogs.track.master = data;
							if (artistmeta.discogs.artisturi === undefined) {
								if (data.data.artists) {
									for (var i in data.data.artists) {
										if (compareArtists(data.data.artists[i].name, getSearchArtist())) {
											debug.log(medebug,'Using artist link from found track',data.data.artists[i].resource_url);
											artistmeta.discogs.artisturi = data.data.artists[i].resource_url;
										}
									}
								}
							}
							self.track.doBrowserUpdate();
						}
					},

					abjectFailure: function() {
						debug.info(medebug,"Completely failed to find the track");
						trackmeta.discogs.trackid = null;
						trackmeta.discogs.track = {error: language.gettext("discogs_notrack"), master: {data: {uri: null}}};
						self.track.doBrowserUpdate();
					},

					doBrowserUpdate: function() {
						getAlbumHTML(trackmeta.discogs.layout, self.artist, artistmeta, trackmeta.discogs.track, ['master', 'release']);
					}

				}
			}();
		}
	}
}();

nowplaying.registerPlugin("discogs", info_discogs, "icon-discogs", "button_discogs");
