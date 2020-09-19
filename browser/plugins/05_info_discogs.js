var info_discogs = function() {

	var me = "discogs";
	var medebug = 'DISCOGS PLUGIN';

	function getURLs(urls) {
		var html = "";
		for (var i in urls) {
			if (urls[i] != "") {
				var u = urls[i];
				var d = u.match(/https*:\/\/(.*?)(\/|$)/);
				if (d == null) {
					d = [u,u];
					u = 'http://'+u;
				}
				if (u.match(/wikipedia/i)) {
					html += '<li><i class="icon-wikipedia smallicon padright"></i><a href="'+u+'" target="_blank">Wikipedia ('+d[1]+')</a></li>';
				} else if (u.match(/facebook/i)) {
					html += '<li><i class="icon-facebook-logo smallicon padright"></i><a href="'+u+'" target="_blank">Facebook</a></li>';
				} else {
					html += '<li><i class="icon-noicon smallicon padright"></i><a href="'+u+'" target="_blank">'+d[1]+'</a></li>';
				}
			}
		}
		return html;
	}

	function getBestImage(images) {
		var image = null;
		var types = ['primary', 'secondary'];
		var index = -1;
		while (image === null && types.length > 0) {
			var type = types.shift();
			var maxsize = 0;
			for (var i in images) {
				if (images[i].resource_url && images[i].type == type) {
					var size = images[i].height*images[i].width;
					if (size > maxsize) {
						image = images[i].resource_url;
						maxsize = size;
					}
				}
			}
		}
		return image;
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

	function getStyles(styles) {
		var html = '<br><ul><li><b>'+language.gettext("discogs_styles")+'</b></li>';
		for (var i in styles) {
			html += '<li>'+styles[i]+'</li>';
		}
		html += '</ul>';
		return html;
	}

	function getGenres(genres) {
		var html = '<br><ul><li><b>'+language.gettext("discogs_genres")+'</b></li>';
		for (var i in genres) {
			html += '<li>'+genres[i]+'</li>';
		}
		html += '</ul>';
		return html;
	}

	function getTracklist(tracks) {
		var html = '<div class="mbbox underline"><b>'+language.gettext("discogs_tracklisting")+'</b></div><div class="mbbox"><table class="padded">';
		for (var i in tracks) {
			if (tracks[i].position == "") {
				html += '<tr><th colspan="3">'+tracks[i].title+'</th></tr>';
			} else {
				html += '<tr><td>'+tracks[i].position+'</td>';
				html += '<td><b>'+tracks[i].title+'</b>';
				if (tracks[i].artists && tracks[i].artists.length > 0) {
					html += '<br><i>';
					var jp = "";
					for (var k in tracks[i].artists) {
						if (jp != "") {
							html += " "+jp+" ";
						}
						html += tracks[i].artists[k].name;
						jp = tracks[i].artists[k].join;
					}
					html += '</i>';
				}

				if (tracks[i].extraartists) {
					for (var j in tracks[i].extraartists) {
						html += '<br><i>'+tracks[i].extraartists[j].role+
									' - '+tracks[i].extraartists[j].name+'</i>';
					}
				}
				html += '</td>';
				html += '<td>'+tracks[i].duration+'</td></tr>';
			}
		}
		html += '</table></div>';
		return html;
	}

	return {

		getRequirements: function(parent) {
			return ["musicbrainz"];
		},

		collection: function(parent, artistmeta, albummeta, trackmeta) {

			debug.debug(medebug, "Creating data collection");

			var self = this;
			var displaying = false;

			this.populate = function() {
				debug.debug(medebug,'Populating');
				parent.updateData({
						discogs: {
							triedsearch: false,
							triedmunge: false
						}
					},
					artistmeta
				);
				parent.updateData({
						discogs: {
							triedmusicbrainz: false,
							triedsearch: false,
							triedmunge: false
						}
					},
					albummeta
				);
				parent.updateData({
						discogs: {
							triedsearch: false,
							triedmunge: false
						}
					},
					trackmeta
				);
				self.track.populate();
				self.album.populate();
				self.artist.populate();
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
					expand_box(source, element, event);
				} else if (element.hasClass('clickchooseposs')) {
					choose_possbility(source, element, event);
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

			function expand_box(source, element, event) {
				var id = element.attr('name');
				var expandingframe = element.parent().parent().parent().parent();
				var content = expandingframe.html();
				content=content.replace(/<i class="icon-expand-up.*?\/i>/, '');
				var pos = expandingframe.offset();
				var target = $("#artistfoldup").length == 0 ? "discogs" : "artist";
				var targetpos = $("#"+target+"foldup").offset();
				var animator = expandingframe.clone();
				animator.css('position', 'absolute');
				animator.css('top', pos.top+"px");
				animator.css('left', pos.left+"px");
				animator.css('width', expandingframe.width()+"px");
				animator.appendTo($('body'));
				$("#"+target+"foldup").animate(
					{
						opacity: 0
					},
					'fast',
					'swing',
					function() {
						animator.animate(
							{
								top: targetpos.top+"px",
								left: targetpos.left+"px",
								width: $("#artistinformation").width()+"px"
							},
							'fast',
							'swing',
							function() {
								browser.speciaUpdate(
									me,
									'artist',
									{
										name: artistmeta.discogs['artist_'+id].data.name,
										link: null,
										data: content
									}
								);
								animator.remove();
							}
						);
					}
				);
			}

			function choose_possibility(source, element, event) {
				var poss = element.attr('name');
				if (poss != artistmeta.discogs.currentposs) {
					self.artist.force = true;
					artistmeta.discogs = {
						currentposs: poss,
						possibilities: artistmeta.discogs.possibilities,
						artistlink: artistmeta.discogs.possibilities[poss].link
					}
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
					putArtistData(artistmeta.discogs['artist_'+id], "artist_"+id);
				}
			}

			function putArtistData(data, div) {
				var html = getArtistHTML(data, true);
				$('div[name="'+div+'"]').each(function() {
					if (!$(this).hasClass('full')) {
						$(this).html(html);
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

			function formatNotes(p) {
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

				// Not only that they've started doing [r=123456] to denote a release! (See Hawkwind)
				var m = p.match(/<span class="artists" name="\d+">/g);
				if (m) {
					for(var i in m) {
						var n = m[i].match(/<span class="artists" name="(\d+)">/);
						if (n && n[1]) {
							debug.trace(medebug,"Found unpopulated artist reference",n[1]);
							if (artistmeta.discogs['artist_'+n[1]] === undefined) {
								debug.debug(medebug,parent.nowplayingindex," ... retrieivng data");
								discogs.artist.getInfo(
									n[1],
									n[1],
									self.artist.extraResponseHandler2,
									self.artist.extraResponseHandler2
								);
							} else {
								debug.debug(medebug,parent.nowplayingindex," ... displaying what we've already got");
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
								self.artist.gotExtraAlbumInfo,
								self.artist.gotExtraAlbumInfo
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
								self.artist.gotExtraAlbumInfo2,
								self.artist.gotExtraAlbumInfo2
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
								self.artist.gotExtraLabelInfo,
								self.artist.gotExtraLabelInfo
							);
						}
					}
				}

				return p;
			}

			function getAlbumHTML(data, order) {
				debug.debug(medebug,"Creating HTML from release/master data",data);

				if (data.error && data.master === undefined && data.release === undefined) {
					return '<h3 align="center">'+data.error+'</h3>';
				}

				var html = '<div class="containerbox info-detail-layout">';

				html += '<div class="info-box-fixed info-box-list info-border-right">';

				for (var i in order) {
					if (data[order[i]] && data[order[i]].data.styles && data[order[i]].data.styles.length > 0) {
						html += getStyles(data[order[i]].data.styles);
						break;
					}
				}

				for (var i in order) {
					if (data[order[i]] && data[order[i]].data.genres && data[order[i]].data.genres.length > 0) {
						html += getGenres(data[order[i]].data.genres);
						break;
					}
				}

				if (data.release && data.release.data.companies && data.release.data.companies.length > 0) {
					html += '<br><ul><li><b>'+language.gettext("discogs_companies")+'</b></li>';
					for (var i in data.release.data.companies) {
						html += '<li>'+data.release.data.companies[i].entity_type_name+
									" "+data.release.data.companies[i].name+'</li>';

					}
					html += '</ul>';
				}

				html += '</div>';

				html += '<div class="info-box-expand stumpy">';

				var image = null;
				for (var i in order) {
					if (data[order[i]] && data[order[i]].data.images) {
						image = getBestImage(data[order[i]].data.images);
						if (image !== null) {
							break;
						}
					}
				}
				if (image !== null) {
					html += '<img class="standout infoclick clickzoomimage cshrinker stright" src="getRemoteImage.php?url='+rawurlencode(image)+'" />';
					html += '<input type="hidden" value="getRemoteImage.php?url='+rawurlencode(image)+'" />';
				}

				if (data.master && data.master.data.notes) {
					var n = formatNotes(data.master.data.notes);
					html += '<p>'+n+'</p>';
				}

				if (data.release && data.release.data.notes) {
					var n = formatNotes(data.release.data.notes);
					html += '<p>'+n+'</p>';
				}

				if (data.release && data.release.data.extraartists && data.release.data.extraartists.length > 0) {
					html += '<div class="mbbox underline"><b>'+language.gettext("discogs_personnel")+'</b></div>';
					for (var i in data.release.data.extraartists) {
						html += '<div class="mbbox">'+data.release.data.extraartists[i].role+' <b>'+data.release.data.extraartists[i].name+'</b></div>';
					}
				}

				for (var i in order) {
					if (data[order[i]] && data[order[i]].data.tracklist && data[order[i]].data.tracklist.length > 0) {
						html += '<div class="minwidthed3"></div>';
						html += getTracklist(data[order[i]].data.tracklist);
						break;
					}
				}

				html += '</div>';
				html += '</div>';
				return html;
			}

			function getArtistHTML(data, expand) {
				if (data.error) {
					return '<h3 align="center">'+data.error+'</h3>';
				}
				debug.debug(medebug, "Creating Artist HTML",data);

				var html = '';

				if (artistmeta.discogs.possibilities && artistmeta.discogs.possibilities.length > 1) {
					html += '<div class="spotchoices clearfix">'+
					'<table><tr><td>'+
					'<div class="bleft tleft spotthing"><span class="spotpossname">All possibilities for "'+
						artistmeta.name+'"</span></div>'+
					'</td><td>';
					for (var i in artistmeta.discogs.possibilities) {
						html += '<div class="tleft infoclick bleft ';
						if (i == artistmeta.discogs.currentposs) {
							html += 'bsel ';
						}
						html += 'clickchooseposs" name="'+i+'">';
						if (artistmeta.discogs.possibilities[i].image) {
							html += '<img class="spotpossimg title-menu" src="getRemoteImage.php?url='+
								rawurlencode(artistmeta.discogs.possibilities[i].image)+'" />';
						} else {
							html += '<img class="spotpossimg title-menu" src="newimages/artist-icon.png" />';
						}
						html += '<span class="spotpossname">'+artistmeta.discogs.possibilities[i].name+'</span>';
						html += '</div>';
					}
					html += '</td></tr></table>';
					html += '</div>';
				}

				html += '<div class="containerbox info-detail-layout">';
					html += '<div class="info-box-fixed info-box-list info-border-right">';

				if (data.data.realname && data.data.realname != "") {
					html += '<br><ul><li><b>'+language.gettext("discogs_realname")+'</b> '+data.data.realname+'</li>';
				}

				if (data.data.aliases && data.data.aliases.length > 0) {
					html += '<br><ul><li><b>'+language.gettext("discogs_aliases")+'</b></li>';
					for (var i in data.data.aliases) {
						html += '<li>'+data.data.aliases[i].name+'</li>';
					}
					html += '</ul>';
				}

				if (data.data.namevariations && data.data.namevariations.length > 0) {
					html += '<br><ul><li><b>'+language.gettext("discogs_alsoknown")+'</b></li>';
					for (var i in data.data.namevariations) {
						html += '<li>'+data.data.namevariations[i]+'</li>';
					}
					html += '</ul>';
				}

				if (data.data.urls && data.data.urls.length > 0) {
					html += '<br><ul><li><b>'+language.gettext("discogs_external")+'</b></li>';
					html += getURLs(data.data.urls);
					html += '</ul>';
				}
					html += '</div>';

					html += '<div class="info-box-expand stumpy">';

						html += '<div class="holdingcell">';
						var image = null;
						if (data.data.images) {
							image = getBestImage(data.data.images);
						}
						if (image !== null) {
							html += '<img class="standout infoclick clickzoomimage cshrinker stright" src="getRemoteImage.php?url='+rawurlencode(image)+'" />';
							html += '<input type="hidden" value="getRemoteImage.php?url='+rawurlencode(image)+'" />';
						}

						if (expand) {
							html += '<i class="icon-expand-up medicon clickexpandbox infoclick tleft" name="'+data.data.id+'"></i>';
						}

						if (data.data.profile) {
							var p = formatNotes(data.data.profile);
							html += '<p>'+p+'</p>';
						}
						html += '</div>';

					html += '</div>';

				html += '</div>';
				if (data.data.members && data.data.members.length > 0) {
					html += '<div class="mbbox underline"><b>'+language.gettext("discogs_bandmembers")+'</b></div>';
					html += doMembers(data.data.members);
				}

				if (data.data.groups && data.data.groups.length > 0) {
					html += '<div class="mbbox underline"><b>'+language.gettext("discogs_memberof")+'</b></div>';
					html += doMembers(data.data.groups);
				}
				html += '<div class="mbbox underline">';
				html += '<i class="icon-toggle-closed menu infoclick clickdodiscography" name="'+data.data.id+'"></i>';
				html += '<span class="title-menu">'+language.gettext("discogs_discography", [data.data.name.toUpperCase()])+'</span></div>';
				html += '<div name="discography_'+data.data.id+'" class="invisible">';
				html += '</div>';
				return html;
			}

			function doMembers(members) {
				var html = $('<div>');
				members.forEach(function(member) {
					var h = $('<div>', {class: 'mbbox'}).appendTo(html);
					h.append($('<i>', {class: 'icon-toggle-closed menu infoclick clickdoartist', name: member.id}));
					h.append($('<span>', {class: 'title-menu'}).html(member.name.replace(/ \(\d+\)$/, '')));
					html.append($('<div>', {name: 'artist_'+member.id, class: 'invisible'}));
				});
				return html.html();
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
				var force = false;
				var searching = false;

				return {

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
						if (typeof artistmeta.discogs['artist_'+artistmeta.discogs.artistid] == 'undefined') {
							discogs.artist.getInfo(
								'artist_'+artistmeta.discogs.artistid,
								artistmeta.discogs.artistid,
								self.artist.artistResponseHandler,
								self.artist.artistResponseHandler
							);
						} else {
							debug.trace(medebug,parent.nowplayingindex,"Artist is populated");
						}
					},

					search: function() {
						if (!artistmeta.discogs.triedmunge) {
							searching = true;
							var name = artistmeta.discogs.triedsearch ? mungeArtist(getSearchArtist()) : getSearchArtist();
							debug.trace(medebug,'Searching for artist',name);
							discogs.artist.search(name, self.artist.searchResponse, self.artist.searchFailure);
						} else {
							artistmeta.discogs.artistid = '-1';
							self.artist.abjectFailure();
						}
					},

					searchResponse: function(data) {
						debug.debug(medebug,'Got artist search response', data);
						if (artistmeta.discogs.triedsearch) {
							artistmeta.discogs.triedmunge = true;
						} else {
							artistmeta.discogs.triedsearch = true;
						}
						artistmeta.discogs.possibilities = new Array();
						if (data.data.results && data.data.results.length > 0) {
							for (var i in data.data.results) {
								if (artistmeta.discogs.triedmunge) {
									var amatch = mungeArtist(getSearchArtist());
								} else {
									var amatch = getSearchArtist();
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
							artistmeta.videos.youtube = null;
						}
					},

					abjectFailure: function() {
						debug.info(medebug,"Failed to find any artist data");
						artistmeta.discogs['artist_'+artistmeta.discogs.artistid] = {error: language.gettext("discogs_nonsense")};
						self.artist.doBrowserUpdate();
					},

					extraResponseHandler: function(data) {
						debug.debug(medebug,parent.nowplayingindex,"got extra artist data for",data.id,data);
						if (data) {
							artistmeta.discogs[data.id] = data;
							putArtistData(artistmeta.discogs[data.id], data.id);
						}
					},

					extraResponseHandler2: function(data) {
						debug.debug(medebug,parent.nowplayingindex,"got stupidly extra artist data for",data.id,data);
						if (data) {
							artistmeta.discogs['artist_'+data.id] = data;
							if (data.data) {
								// Fill in any [a123456] or [a=123456] links in the main text body.
								$('span.artists[name="'+data.data.id+'"]').html(data.data.name);
								$('span.artists[name="'+data.data.id+'"]').wrap('<a href="'+data.data.uri+'" target="_blank"></a>');
							}
						}
					},

					gotExtraAlbumInfo: function(data) {
						debug.debug(medebug, 'got extra album info',data);
						if (data) {
							artistmeta.discogs['masters_'+data.id] = data;
							if (data.data) {
								// Fill in any [m123456] or [m=123456] links in the main text body.
								$('span.masters[name="'+data.data.id+'"]').html(data.data.title);
								$('span.masters[name="'+data.data.id+'"]').wrap('<a href="'+data.data.uri+'" target="_blank"></a>');
							}
						}
					},

					gotExtraAlbumInfo2: function(data) {
						debug.debug(medebug, 'got extra album info',data);
						if (data) {
							artistmeta.discogs['releases_'+data.id] = data;
							if (data.data) {
								// Fill in any [r123456] or [r=123456] links in the main text body.
								$('span.releases[name="'+data.data.id+'"]').html(data.data.title);
								$('span.releases[name="'+data.data.id+'"]').wrap('<a href="'+data.data.uri+'" target="_blank"></a>');
							}
						}
					},

					gotExtraLabelInfo: function(data) {
						debug.debug(medebug, 'got extra label info',data);
						if (data) {
							if (data.data) {
								// Fill in any [l123456] or [l=123456] links in the main text body.
								$('span.labels[name="'+data.data.id+'"]').html(data.data.name);
								$('span.labels[name="'+data.data.id+'"]').wrap('<a href="'+data.data.uri+'" target="_blank"></a>');
							}
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
						var up = null;
						if (displaying) {
							debug.debug(medebug,parent.nowplayingindex,"artist was asked to display");
							if (typeof artistmeta.discogs.artistid != 'undefined' && typeof artistmeta.discogs['artist_'+artistmeta.discogs.artistid] != 'undefined') {
								if (artistmeta.discogs['artist_'+artistmeta.discogs.artistid].error) {
									up = { name: artistmeta.name,
										   link: null,
										   data: '<h3 align="center">'+artistmeta.discogs.artistinfo.error+'</h3>'
										}
								} else {
									up = { name: artistmeta.name,
										   link: artistmeta.discogs['artist_'+artistmeta.discogs.artistid].data.uri,
										   data: getArtistHTML(artistmeta.discogs['artist_'+artistmeta.discogs.artistid], false)
										}
								}
							}
							if (up !== null) {
								browser.Update(
									null,
									'artist',
									me,
									parent.nowplayingindex,
									up,
									false,
									self.artist.force
								);
							}
						}
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
						if (typeof albummeta.discogs.album == 'undefined') {
							discogs.album.getInfo(
								albummeta.discogs.albumid,
								albummeta.discogs.idtype+'/'+albummeta.discogs.albumid,
								self.album.albumResponseHandler,
								self.album.albumResponseErrorHandler
							);
						} else {
							debug.trace(medebug,parent.nowplayingindex,"Album is already populated");
						}
					},

					search: function() {
						if (!trackmeta.discogs.triedmunge) {
							searching = true;
							var artist = albummeta.discogs.triedsearch ? mungeArtist(getSearchArtistForAlbum()) : getSearchArtistForAlbum();
							debug.trace(medebug, 'Searching for album',artist,albummeta.name);
							discogs.album.search(artist, albummeta.name, self.album.searchResponse, self.album.abjectFailure);
						} else {
							self.album.abjectFailure();
						}
					},

					searchResponse: function(data) {
						debug.debug(medebug, 'Got album search results', data);
						if (albummeta.discogs.triedsearch) {
							albummeta.discogs.triedmunge = true;
						} else {
							albummeta.discogs.triedsearch = true;
						}
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
							albummeta.discogs.albumlink = data.data.results[best].master_url;
							debug.debug(medebug,'Using album search result', best, albummeta.discogs.albumlink);
						}
						searching = false;
					},

					albumResponseHandler: function(data) {
						debug.debug(medebug,parent.nowplayingindex,"Got album data",data);
						if (typeof albummeta.discogs.album == 'undefined') {
							albummeta.discogs.album = {};
						}
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
						albummeta.discogs.album = {error: language.gettext("discogs_noalbum")};
						self.album.doBrowserUpdate();
					},

					doBrowserUpdate: function() {
						if (displaying && albummeta.discogs.album !== undefined &&
								(albummeta.discogs.album.error !== undefined ||
								albummeta.discogs.album.master !== undefined ||
								albummeta.discogs.album.release !== undefined)) {
							debug.debug(medebug,parent.nowplayingindex,"album was asked to display");
							if (parent.playlistinfo.type == 'stream') {
								browser.Update(null, 'album', me, parent.nowplayingindex, { name: "",
														link: "",
														data: null
														}
								);
							} else {
								browser.Update(
									null,
									'album',
									me,
									parent.nowplayingindex,
									{
										name: albummeta.name,
										link: (albummeta.discogs.album.master === undefined) ? null : albummeta.discogs.album.master.data.uri,
										data: getAlbumHTML(albummeta.discogs.album, ['master', 'release'])
									}
								);
							}
						}
					},
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
						if (typeof trackmeta.discogs.track == 'undefined') {
							discogs.track.getInfo(
								trackmeta.discogs.trackid,
								trackmeta.discogs.idtype+'/'+trackmeta.discogs.trackid,
								self.track.trackResponseHandler,
								self.track.abjectFailure
							);
						} else {
							debug.trace(medebug,parent.nowplayingindex,"Track is already populated");
						}
					},

					search: function() {
						if (!trackmeta.discogs.triedmunge) {
							searching = true;
							var artist = trackmeta.discogs.triedsearch ? mungeArtist(artistmeta.name) : artistmeta.name;
							debug.trace(medebug,'Searching for track',artist, trackmeta.name);
							discogs.track.search(artist, trackmeta.name, self.track.searchResponse, self.track.abjectFailure);
						} else {
							self.track.abjectFailure();
						}
					},

					searchResponse: function(data) {
						debug.debug(medebug, 'Got track search results', data);
						if (trackmeta.discogs.triedsearch) {
							trackmeta.discogs.triedmunge = true;
						} else {
							trackmeta.discogs.triedsearch = true;
						}
						if (data.data.results && data.data.results.length > 0) {
							var best = 0;
							find_best: {
								for (var i in data.data.results) {
									if (trackmeta.discogs.triedmunge) {
										var amatch = mungeArtist(artistmeta.name)
									} else {
										var amatch = artistmeta.name.toLowerCase();
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
						searching = false;
					},

					trackResponseHandler: function(data) {
						debug.debug(medebug,parent.nowplayingindex,"Got track data",data);
						if (typeof trackmeta.discogs.track == 'undefined') {
							trackmeta.discogs.track = {};
						}
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
						trackmeta.discogs.track = {error: language.gettext("discogs_notrack")};
						self.track.doBrowserUpdate();
					},

					doBrowserUpdate: function() {
						if (displaying && trackmeta.discogs.track !== undefined &&
								(trackmeta.discogs.track.error !== undefined ||
								trackmeta.discogs.track.master !== undefined)) {
							debug.debug(medebug,parent.nowplayingindex,"track was asked to display");
							browser.Update(
								null,
								'track',
								me,
								parent.nowplayingindex,
								{
									name: trackmeta.name,
									link: (trackmeta.discogs.track.master === undefined) ? null : trackmeta.discogs.track.master.data.uri,
									data: getAlbumHTML(trackmeta.discogs.track, ['master', 'release'])
								}
							);
						}
					},
				}
			}();
		}
	}
}();

nowplaying.registerPlugin("discogs", info_discogs, "icon-discogs", "button_discogs");
