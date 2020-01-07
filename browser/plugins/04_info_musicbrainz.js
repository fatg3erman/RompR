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

	function doSpan(data) {
		if (data.begin === undefined || data.begin === null) {
			return "";
		}
		var by = new Date(data.begin);
		var ey = new Date(data.end);
		var tby = by.getFullYear() || "";
		var tey = data.ended ? (ey.getFullYear() || "") : language.gettext("musicbrainz_now");
		return '('+tby+'&nbsp;-&nbsp;'+tey+')';
	}

	function albumsbyyear(a, b) {
		var year_a = getYear(a);
		var year_b = getYear(b);
		if (year_a == year_b) { return 0 }
		return (year_a > year_b) ? 1 : -1;
	}

	function getArtistHTML(data, expand) {

		if (data.error) {
			return '<h3 align="center">'+data.error+'</h3>';
		}

		var html = '<div class="containerbox info-detail-layout">';
		html += '<div class="info-box-fixed info-box-list info-border-right">';
		html += '<ul><li>'+data.disambiguation+'</li></ul>';
		if (data.type !== null) {
			html += '<ul><li><b>'+language.gettext("title_type")+': </b>'+data.type+'</li></ul>';
		}
		if (data.aliases && data.aliases.length > 0) {
			html += '<br><ul><li><b>'+language.gettext("discogs_aliases")+'</b></li>';
			for (var i in data.aliases) {
				html += '<li>'+data.aliases[i].name + '</li>';
			}
			html += '</ul>';
		}

		if (data.begin_area && data.area) {
			html += '<br><ul><li><b>'+language.gettext("musicbrainz_origin")+': </b>'+data.begin_area.name+", "+data.area.name+'</li></ul>';
		} else if (data.area) {
			html += '<br><ul><li><b>'+language.gettext("musicbrainz_origin")+': </b>'+data.area.name+'</li></ul>';
		}
		if (data['life-span'] && data['life-span'].begin !== null) {
			html += '<br><ul><li><b>'+language.gettext("musicbrainz_active")+': </b>'+data['life-span'].begin+" - "+(data['life-span'].end || language.gettext("musicbrainz_now"))+'</li></ul>';
		}
		if (data.rating && data.rating.value !== null) {
			html += '<br><ul><li><b>'+language.gettext("musicbrainz_rating")+': </b>'+data.rating.value+"/5 from "+data.rating['votes-count']+' votes</li></ul>';
		}
		html += '<br>'+getURLs(data.relations, true);
		html += '</div>';

		html += '<div class="info-box-expand stumpy">';
		if (expand) {
			html += '<div class="mbbox"><i class="icon-expand-up medicon clickexpandbox infoclick tleft" name="'+data.id+'"></i></div>';
		}

		if (data.annotation) {
			var a = data.annotation;
			a = a.replace(/\n/, '<br>');
			a = a.replace(/\[(.*?)\|(.*?)\]/g, '<a href="$1" target="_blank">$2</a>');
			html += '<div class="mbbox underline"><b>'+language.gettext("musicbrainz_notes")+':</b></div><div class="mbbox">'+a+'</div>';
		}

		if (data.tags && data.tags.length > 0) {
			html += '<div class="mbbox underline"><b>'+language.gettext("musicbrainz_tags")+'</b></div><div class="statsbox">';
			for (var i in data.tags) {
				html += '<span class="mbtag">'+data.tags[i].name+'</span> ';
			}
			html += '</div>';
		}

		var bandMembers = new Array();
		var memberOf = new Array();
		for (var i in (data.relations)) {
			if (data.relations[i].type == "member of band") {
				if (data.relations[i].direction == "backward") {
					bandMembers.push(data.relations[i]);
				} else {
					memberOf.push(data.relations[i]);
				}
			}
		}
		if (bandMembers.length > 0) {
			html += '<div class="mbbox underline"><b>'+language.gettext("discogs_bandmembers")+'</b></div>'+getMembers(bandMembers);
		}
		if (memberOf.length > 0) {
			html += '<div class="mbbox underline"><b>'+language.gettext("discogs_memberof")+'</b></div>'+getMembers(memberOf);
		}

		html += '<div class="mbbox underline">';
		html += '<i class="icon-toggle-closed menu infoclick clickdodiscography" name="'+data.id+'"></i>';
		html += '<span class="title-menu">'+language.gettext("discogs_discography", [data.name.toUpperCase()])+'</span></div>';
		html += '<div name="discography_'+data.id+'" class="invisible">';
		html += '</div>';

		html += '</div>';
		html += '</div>';
		return html;

	}

	function getMembers(data) {
		var html = "";
		var already_done = new Array();
		var ayears = new Array();
		for (var i in data) {
			if (already_done[data[i].artist.id] !== true) {
				debug.debug(medebug,"New Artist",data[i].artist.id,data[i].artist.name,data[i].begin,data[i].end);
				html += '<div class="mbbox">';
				// The already_done flag is just there because artist can appear multiple times in this data
				// if they did multiple stints in the band.

				html += '<i class="icon-toggle-closed menu infoclick clickdoartist" name="'+data[i].artist.id+'"></i>';
				html += '<span class="title-menu">'+data[i].artist.name+'  </span>'+"AYEARS_"+data[i].artist.id;
				ayears[data[i].artist.id] = doSpan(data[i]);
				html += '</div>';
				html += '<div name="'+data[i].artist.id+'" class="invisible"></div>';
				already_done[data[i].artist.id] = true;
			} else {
				debug.debug(medebug,"Repeat Artist",data[i].artist.id,data[i].artist.name,data[i].begin,data[i].end);
				ayears[data[i].artist.id] = ayears[data[i].artist.id] + " " + doSpan(data[i]);
			}
		}
		for(var i in ayears) {
			html = html.replace("AYEARS_"+i, ayears[i]);
		}
		return html;

	}

	function getURLs(relations, withheader) {
		if (relations.length == 0) {
			return "";
		}
		if (withheader) {
			var html = '<ul><li><b>'+language.gettext("discogs_external")+'</b></li>';
		} else {
			var html = '<ul style="list-style:none;margin:2px;padding:0px">';
		}
		for (var i in relations) {
			if (relations[i].url) {
				var u = relations[i].url.resource;
				var d = u.match(/https*:\/\/(.*?)(\/|$)/);
			}
			switch (relations[i].type) {
				case "wikipedia":
					html += '<li><i class="icon-wikipedia smallicon padright"></i><a href="'+u+'" target="_blank">Wikipedia ('+d[1]+')</a></li>';
					break;

				case "wikidata":
					html += '<li><i class="icon-wikipedia smallicon padright"></i><a href="'+u+'" target="_blank">Wikidata</a></li>';
					break;

				case "discography":
					html += '<li><i class="icon-noicon smallicon padright"></i><a href="'+u+'" target="_blank">'+language.gettext("musicbrainz_externaldiscography", [d[1]])+'</a></li>';
					break;

				case "musicmoz":
					html += '<li><i class="icon-noicon smallicon padright"></i><a href="'+u+'" target="_blank">Musicmoz</a></li>';
					break;

				case "allmusic":
					html += '<li><i class="icon-allmusic smallicon padright"></i><a href="'+u+'" target="_blank">Allmusic</a></li>';
					break;

				case "BBC Music page":
					html += '<li><i class="icon-bbc-logo smallicon padright"></i><a href="'+u+'" target="_blank">BBC Music Page</a></li>';
					break;

				case "discogs":
					html += '<li><i class="icon-discogs smallicon padright"></i><a href="'+u+'" target="_blank">Discogs</a></li>';
					break;

				case "official homepage":
					html += '<li><i class="icon-noicon smallicon padright"></i><a href="'+u+'" target="_blank">'+language.gettext("musicbrainz_officalhomepage", [d[1]])+'</a></li>';
					break;

				case "fanpage":
					html += '<li><i class="icon-noicon smallicon padright"></i><a href="'+u+'" target="_blank">'+language.gettext("musicbrainz_fansite", [d[1]])+'</a></li>';
					break;

				case "lyrics":
					html += '<li><i class="icon-doc-text-1 smallicon padright"></i><a href="'+u+'" target="_blank">'+language.gettext("musicbrainz_lyrics", [d[1]])+'</a></li>';
					break;

				case "secondhandsongs":
					html += '<li><i class="icon-noicon smallicon padright"></i><a href="'+u+'" target="_blank">Secondhand Songs</a></li>';
					break;

				case "IMDb":
					html += '<li><i class="icon-imdb-logo smallicon padright"></i><a href="'+u+'" target="_blank">IMDb</a></li>';
					break;

				case "social network":
					if (u.match(/last\.fm/i)) {
						html += '<li><i class="icon-lastfm-1 smallicon padright"></i><a href="'+u+'" target="_blank">Last.FM</a></li>';
					} else if (u.match(/facebook\.com/i)) {
						html += '<li><i class="icon-facebook-logo smallicon padright"></i><a href="'+u+'" target="_blank">Facebook</a></li>';
					} else {
						html += '<li><i class="icon-noicon smallicon padright"></i><a href="'+u+'" target="_blank">'+language.gettext("musicbrainz_social", [d[1]])+'</a></li>';
					}
					break;

				case "youtube":
					html += '<li><i class="icon-youtube-circled smallicon padright"></i><a href="'+u+'" target="_blank">YouTube</a></li>';
					break;

				case "myspace":
					html += '<li><i class="icon-noicon smallicon padright"></i><a href="'+u+'" target="_blank">Myspace</a></li>';
					break;

				case "microblog":
					if (u.match(/twitter\.com/i)) {
						html += '<li><i class="icon-twitter-logo smallicon padright"></i><a href="'+u+'" target="_blank">Twitter</a></li>';
					} else {
						html += '<li><i class="icon-noicon smallicon padright"></i><a href="'+u+'" target="_blank">'+language.gettext("musicbrainz_microblog", [d[1]])+'</a></li>';
					}
					break;

				case "review":
					if (u.match(/bbc\.co\.uk/i)) {
						html += '<li><i class="icon-bbc-logo smallicon padright"></i><a href="'+u+'" target="_blank">BBC Music Review</a></li>';
					} else {
						html += '<li><i class="icon-noicon smallicon padright"></i><a href="'+u+'" target="_blank">'+language.gettext("musicbrainz_review", [d[1]])+'</a></li>';
					}
					break;

				case "VIAF":
					break;

				default:
					if (relations[i].url) {
						html += '<li><i class="icon-noicon smallicon padright"></i><a href="'+u+'" target="_blank">'+d[1]+'</a></li>';
						break;
					}
			}
		}
		html += '</ul>'
		return html;

	}

	function getReleaseHTML(data) {

		if (data.error) {
			return '<h3 align="center">'+language.gettext("musicbrainz_contacterror")+'</h3>';
		}
		if (data['release-groups'].length > 0) {
			var dby = data['release-groups'].sort(albumsbyyear);
			var html = '<div class="mbbox"><table class="padded" width="100%">';
			html += '<tr><th>'+language.gettext("title_year")+'</th><th>'+language.gettext("title_title")+' / '
						+language.getUCtext("label_artist")+'</th><th>'+language.gettext("title_type")+'</th><th>'
						+language.gettext("musicbrainz_rating")+'</th><th>'+language.gettext("discogs_external")+'</th></tr>'
			for (var i in dby) {

				var y = getYear(dby[i]);
				if (y == 0) {
					y = "-";
				}
				html += '<tr><td>'+y+'</td>';
				html += '<td><a href="http://www.musicbrainz.org/release-group/'+dby[i].id+'" target="_blank">'+dby[i].title+'</a>';

				var ac = dby[i]['artist-credit'][0].name;
				var jp = dby[i]['artist-credit'][0].joinphrase;
				for(var j = 1; j < dby[i]['artist-credit'].length; j++) {
					ac = ac + " "+jp+" "+dby[i]['artist-credit'][j].name;
				}

				html += '<br><i>'+ac+'</i></td><td>';
				html += dby[i]['secondary-types'].join(' ');
				html += ' ' + (dby[i]['primary-type'] || "");
				html += '</td><td>';
				if (dby[i].rating['votes-count'] == 0) {
					html += language.gettext("musicbrainz_novotes");
				} else {
					html += language.gettext("musicbrainz_votes", [dby[i].rating.value, dby[i].rating['votes-count']]);
				}
				html += '</td><td>';
				html += getURLs(dby[i].relations);
				html += '</td></tr>';
			}
			html += '</table></div>';
			return html;
		} else {
			return "";
		}
	}

	function getCoverHTML(data) {
		var html = "";
		if (data) {
			for (var i in data.images) {
				html += '<div class="infoclick clickzoomimage">';
				html += '<img style="max-width:220px" src="getRemoteImage.php?url='+rawurlencode(data.images[i].thumbnails.small)+'" />';
				html += '</div>';
				html += '<input type="hidden" value="getRemoteImage.php?url='+rawurlencode(data.images[i].image)+'" />';
			}
		}
		return html;
	}

	function getTrackHTML(data) {
		if (data.error && data.recording === undefined && data.work === undefined) {
			return '<h3 align="center">'+data.error.error+'</h3>';
		}
		var html = '<div class="containerbox info-detail-layout">';
		html += '<div class="info-box-fixed info-box-list info-border-right">';
		if (data.recording) {
			if (data.recording.disambiguation) {
				html += '<ul>'+data.recording.disambiguation+'</ul>';
			}
		}
		if (data.work) {
			if (data.work.disambiguation) {
				html += '<ul>'+data.work.disambiguation+'</ul>';
			}
		}

		if (data.recording.rating && data.recording.rating.value !== null) {
			html += '<br><ul><li><b>RATING: </b>'
						+language.gettext("musicbrainz_votes", [data.recording.rating.value, data.recording.rating['votes-count']])
						+'</li></ul>';
		}
		var rels = new Array();

		if (data.work) {
			for (var i in data.work.relations) {
				rels.push(data.work.relations[i]);
			}
		}
		if (data.recording) {
			for (var i in data.recording.relations) {
				rels.push(data.recording.relations[i]);
			}
		}
		html += getURLs(rels, true);
		html += '</div>';

		html += '<div class="info-box-expand stumpy">';
		if ((data.work && data.work.annotation) || (data.recording && data.recording.annotation)) {
			var a  = "";
			if (data.work && data.work.annotation) {
				a = a + data.work.annotation;
			}
			if (data.recording && data.recording.annotation) {
				a = a + data.recording.annotation;
			}
			a = a.replace(/\n/, '<br>');
			a = a.replace(/\[(.*?)\|(.*?)\]/, '<a href="$1" target="_blank">$2</a>');
			html += '<div class="mbbox underline"><b>'+language.gettext("musicbrainz_notes")+':</b></div><div class="mbbox">'+a+'</div>';
		}

		if (data.recording && data.recording.tags && data.recording.tags.length > 0) {
			html += '<div class="mbbox underline"><b>'+language.gettext("musicbrainz_tags")+'</b></div><div class="statsbox">';
			for (var i in data.recording.tags) {
				html += '<span class="mbtag">'+data.recording.tags[i].name+'</span> ';
			}
			html += '</div>';
		}
		html += doCredits(rels);

		if (data.recording && data.recording.releases && data.recording.releases.length > 0) {
			html += '<div class="mbbox underline"><b>'+language.gettext("musicbrainz_appears")+'</b></div><div class="mbbox"><table class="padded">';
			for (var i in data.recording.releases) {
				html += '<tr><td><b><a href="http://www.musicbrainz.org/release/'+
							data.recording.releases[i].id+'" target="_blank">'+
							data.recording.releases[i].title+'</a></b></td><td>'+
							data.recording.releases[i].date+'</td><td><i>'+
							data.recording.releases[i].status+','+
							data.recording.releases[i].country+'</i></td></tr>';
			}
			html += '</table></div>';

		}


		html += '</div>';
		return html;

	}

	function doCredits(rels) {
		var doit = true;
		var html = "";
		for (var i in rels) {
			if (rels[i].artist) {
				if (doit) {
					html += '<div class="mbbox underline"><b>'+language.gettext("musicbrainz_credits")+'</b></div><div class="mbbox"><table class="padded">';
					doit = false;
				}
				html += '<tr><td class="ucfirst">'+rels[i].type;
				if (rels[i].attributes) {
					var c = false;
					for (var j in rels[i].attributes) {
						if (j == 0) {
							html += ' (';
							c = true;
						} else {
							html = html +', ';
						}
						html += rels[i].attributes[j];
					}
					if (c) {
						html += ')';
					}
				}
				html = html +'</td><td><a href="http://www.musicbrainz.org/artist/'+rels[i].artist.id+'" target="_blank">'+rels[i].artist.name+'</a>';
				if (rels[i].artist.disambiguation) {
					html += ' <i>('+rels[i].artist.disambiguation+')</i>';
				}
				html = html +'</td></tr>';
			}
		}
		if (!doit) {
			html += '</table></div>';
		}
		return html;
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

			this.stopDisplaying = function() {
				displaying = false;
			}

			this.handleClick = function(source, element, event) {
				debug.debug(medebug,parent.nowplayingindex,source,"is handling a click event");
				if (element.hasClass('clickdoartist')){
					var targetdiv = element.parent().next();
					if (!(targetdiv.hasClass('full')) && element.isClosed()) {
						doSomethingUseful(targetdiv, language.gettext("info_gettinginfo"));
						targetdiv.slideToggle('fast');
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
				} else if (element.hasClass('clickdodiscography')) {
					var targetdiv = element.parent().next();
					if (!(targetdiv.hasClass('full')) && element.isClosed()) {
						doSomethingUseful(targetdiv, language.gettext("info_gettinginfo"));
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
				} else if (element.hasClass('clickexpandbox')) {
					var id = element.attr('name');
					var expandingframe = element.parent().parent().parent().parent();
					var content = expandingframe.html();
					content=content.replace(/<i class="icon-expand-up.*?>/, '');
					var pos = expandingframe.offset();
					var target = $("#artistfoldup").length == 0 ? "musicbrainz" : "artist";
					var targetpos = $("#"+target+"foldup").offset();
					debug.debug("MUSICBRAINZ","1. targetpos is",targetpos);
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
											name: artistmeta.musicbrainz[id].name,
											link: null,
											data: content
										}
									);
									animator.remove();
								}
							);
						}
					);
				} else if (element.hasClass('clickzoomimage')) {
					imagePopup.create(element, event, element.next().val());
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
					putArtistData(artistmeta.musicbrainz[id], id);
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

			function getAlbumHTML(data) {
				if (data.error) {
					return '<h3 align="center">'+data.error+'</h3>';
				}

				var html = '<div class="containerbox info-detail-layout">';
				html += '<div class="info-box-fixed info-box-list info-border-right">';

				if (data['cover-art-archive'].artwork == true) {
					debug.trace(medebug,"There is cover art available");
					html += '<ul id="coverart">';
					html += getCoverArt();
					html += '</ul><br />';
				}

				html += '<ul><li>'+data.disambiguation+'</li></ul>';
				html += '<ul><li><b>'+language.gettext("musicbrainz_status")+': </b>';
				if (data.status) {
					html = html +data.status+" ";
				}
				for(var j in data['release-group']['secondary-types']) {
					html += data['release-group']['secondary-types'][j] + " ";
				}
				html += (data['release-group']['primary-type'] || "");
				html += '</li></ul>';
				if (data['release-group'] && data['release-group']['first-release-date']) {
					html += '<ul><li><b>'+language.gettext("musicbrainz_date")+': </b>'+data['release-group']['first-release-date']+'</li></ul>';
				} else {
					html += '<ul><li><b>'+language.gettext("musicbrainz_date")+': </b>'+data.date+'</li></ul>';
				}
				if (data.country) {
					html += '<ul><li><b>'+language.gettext("musicbrainz_country")+': </b>'+data.country+'</li></ul>';
				}
				if (data['label-info'] && data['label-info'].length > 0) {
					html += '<ul><li><b>'+language.gettext("title_label")+': </b></li>';
					for (var i in data['label-info']) {
						if (data['label-info'][i].label) {
							html += '<li>'+data['label-info'][i].label.name+'</li>';
						}
					}
					html += '</ul>';
				}
				html += '<br>'+getURLs(data.relations, true);
				html += '</div>';

				html += '<div class="info-box-expand stumpy">';
				if (data.annotation) {
					var a = data.annotation;
					a = a.replace(/\n/, '<br>');
					a = a.replace(/\[(.*?)\|(.*?)\]/, '<a href="$1" target="_blank">$2</a>');
					html += '<div class="mbbox underline"><b>'+language.gettext("musicbrainz_notes")+':</b></div><div class="mbbox">'+a+'</div>';
				}

				if (data.tags && data.tags.length > 0) {
					html += '<div class="mbbox underline"><b>'+language.gettext("musicbrainz_tags")+'</b></div><div class="statsbox">';
					for (var i in data.tags) {
						html += '<span class="mbtag">'+data.tags[i].name+'</span> ';
					}
					html += '</div>';
				}

				html += doCredits(data.relations);

				html += '<div class="mbbox underline"><b>'+language.gettext("discogs_tracklisting")+'</b></div><div class="mbbox"><table class="padded">';
				for (var i in data.media) {
					html += '<tr><th colspan="3"><b>'+language.gettext("musicbrainz_disc")+' '+data.media[i].position;
					if (data.media[i].title !== null && data.media[i].title != "") {
						html += " - " + data.media[i].title;
					}
					html += '</b></th></tr>';
					for (var j in data.media[i].tracks) {
						html += '<tr><td>'+data.media[i].tracks[j].number+'</td>';
						html += '<td>'+data.media[i].tracks[j].title;
						if (data['artist-credit'][0].name == "Various Artists" && data.media[i].tracks[j]['artist-credit']) {
							html += '<br><i>';
							var jp = "";
							for (var k in data.media[i].tracks[j]['artist-credit']) {
								if (jp != "") {
									html += " "+jp+" ";
								}
								html += data.media[i].tracks[j]['artist-credit'][k].name;
								jp = data.media[i].tracks[j]['artist-credit'][k].joinphrase;
							}
							html += '</i>';
						}
						html += '</td>';
						html += '<td>'+formatTimeString(Math.round(data.media[i].tracks[j].length/1000))+'</td></tr>';
					}
				}
				html += '</table>';
				html += '</div>';
				html += '</div>';
				html += '</div>';
				return html;
			}

			function getCoverArt() {
				debug.trace(medebug,parent.nowplayingindex,"Getting Cover Art");
				if (albummeta.musicbrainz.coverart === undefined) {
					debug.debug(medebug,parent.nowplayingindex," ... retrieivng data");
					musicbrainz.album.getCoverArt(
						albummeta.musicbrainz_id,
						self.album.coverResponseHandler,
						self.album.coverResponseHandler
					);
					return "";
				} else {
					debug.debug(medebug,parent.nowplayingindex," ... displaying what we've already got");
					return (getCoverHTML(albummeta.musicbrainz.coverart));
				}
			}

			this.artist = function() {

				return {

					populate: function() {
						if (artistmeta.musicbrainz === undefined) {
							artistmeta.musicbrainz = {};
						}
						if (artistmeta.musicbrainz_id == "") {
							debug.debug(medebug,parent.nowplayingindex,"Artist asked to populate but no MBID, trying again in 2 seonds");
							setTimeout(self.artist.populate, 2000);
							return;
						}
						if (artistmeta.musicbrainz_id === null) {
							debug.info(medebug,parent.nowplayingindex,"Artist asked to populate but no MBID could be found. Aborting");
							artistmeta.musicbrainz.artist = {error: language.gettext("musicbrainz_noartist")};
							parent.updateData({
									wikipedia: { artistlink: null },
									discogs: {  artistlink: null },
									allmusic: {  artistlink: null }
								},
								artistmeta
							);
							self.artist.doBrowserUpdate();
							return;
						}
						if (artistmeta.musicbrainz.artist === undefined &&
							artistmeta.musicbrainz[artistmeta.musicbrainz_id] === undefined) {
							debug.debug(medebug,parent.nowplayingindex,"artist is populating",artistmeta.musicbrainz_id);
							musicbrainz.artist.getInfo(artistmeta.musicbrainz_id, self.artist.mbResponseHandler, self.artist.mbResponseHandler);
						} else {
							debug.trace(medebug,parent.nowplayingindex,"artist is already populated",artistmeta.musicbrainz_id);
						}
					},

					mbResponseHandler: function(data) {
						debug.debug(medebug,parent.nowplayingindex,"got artist data for",artistmeta.musicbrainz_id,data);
						// Look for the information that other plugins need
						var update = 	{ 	disambiguation: null,
											wikipedia: { artistlink: null },
											discogs: {  artistlink: null },
											allmusic: {  artistlink: null }
										};
						if (data) {
							if (data.error) {
								artistmeta.musicbrainz.artist = data;
							} else {
								artistmeta.musicbrainz[artistmeta.musicbrainz_id] = data;
								var wikilinks = { user: null, english: null, anything: null };
								debug.debug(medebug,parent.nowplayingindex,"wikipedia language is",wikipedia.getLanguage());

								var domain = '^http://'+wikipedia.getLanguage();
								var re = new RegExp(domain);
								for (var i in data.relations) {
									if (data.relations[i].type == "wikipedia") {
										debug.trace(medebug,parent.nowplayingindex,"has found a Wikipedia artist link",data.relations[i].url.resource);
										// For wikipedia links we need to prioritise:
										// user's chosen domain first
										// english second
										// followed by anything will do
										// the php side will also try to use the link we choose to get language links for the
										// user's chosen language, but it's definitely best if we prioritise them here
										var wikitemp = data.relations[i].url.resource;
										if (re.test(wikitemp)) {
											debug.trace(medebug,parent.nowplayingindex,"found user domain wiki link");
											wikilinks.user = wikitemp;
										} else if (wikitemp.match(/en.wikipedia.org/)) {
											debug.trace(medebug,parent.nowplayingindex,"found english domain wiki link");
											wikilinks.english = wikitemp;
										} else {
											debug.trace(medebug,parent.nowplayingindex,"found wiki link");
											wikilinks.anything = wikitemp;
										}
									}
									if (data.relations[i].type == "discogs" && update.discogs.artistlink == null) {
										debug.trace(medebug,parent.nowplayingindex,"has found a Discogs artist link",data.relations[i].url.resource);
										update.discogs.artistlink = data.relations[i].url.resource;
									}
									if (data.relations[i].type == "allmusic" && update.allmusic.artistlink == null) {
										debug.trace(medebug,parent.nowplayingindex,"has found an Allmusic artist link",data.relations[i].url.resource);
										update.allmusic.artistlink = data.relations[i].url.resource;
									}
								}
								if (update.wikipedia.artistlink == null) {
									if (wikilinks.user) {
										debug.trace(medebug,parent.nowplayingindex,"using user domain wiki link",wikilinks.user);
										update.wikipedia.artistlink = wikilinks.user;
									} else if (wikilinks.english) {
										debug.trace(medebug,parent.nowplayingindex,"using english domain wiki link",wikilinks.english);
										update.wikipedia.artistlink = wikilinks.english;
									} else if (wikilinks.anything) {
										debug.trace(medebug,parent.nowplayingindex,"using any old domain wiki link",wikilinks.anything);
										update.wikipedia.artistlink = wikilinks.anything;
									}

								}
								if (data.disambiguation) {
									update.disambiguation = data.disambiguation;
								}
							}
						} else {
							artistmeta.musicbrainz.artist = {error: language.gettext("musicbrainz_noinfo")};
						}

						parent.updateData(update, artistmeta);
						self.artist.doBrowserUpdate();

					},

					extraResponseHandler: function(data) {
						if (data) {
							debug.debug(medebug,parent.nowplayingindex,"got extra artist data for",data.id,data);
							artistmeta.musicbrainz[data.id] = data;
							putArtistData(artistmeta.musicbrainz[data.id], data.id);
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
						if (displaying) {
							debug.debug(medebug,parent.nowplayingindex," artist was asked to display");
							var up = null;
							if (artistmeta.musicbrainz.artist !== undefined && artistmeta.musicbrainz.artist.error) {
								up = { name: artistmeta.name,
									   link: null,
									   data: '<h3 align="center">'+artistmeta.musicbrainz.artist.error+'</h3>'}
							} else if (artistmeta.musicbrainz[artistmeta.musicbrainz_id] !== undefined) {
								up = { name: artistmeta.musicbrainz[artistmeta.musicbrainz_id].name,
									   link: 'http://musicbrainz.org/artist/'+artistmeta.musicbrainz_id,
									   data: getArtistHTML(artistmeta.musicbrainz[artistmeta.musicbrainz_id], false)}
							}
							if (up !== null) {
								browser.Update(
									null,
									'artist',
									me,
									parent.nowplayingindex,
									up
								);
							}
						}
					},

				}

			}();

			this.album = function() {

				return {

					populate: function() {
						if (albummeta.musicbrainz === undefined) {
							albummeta.musicbrainz = {};
						}
						if (albummeta.musicbrainz.album === undefined) {
							if (albummeta.musicbrainz_id == "") {
								debug.debug(medebug,parent.nowplayingindex,"Album asked to populate but no MBID, trying again in 2 seonds");
								setTimeout(self.album.populate, 2000);
								return;
							}
							if (albummeta.musicbrainz_id === null) {
								debug.info(medebug,parent.nowplayingindex,"Album asked to populate but no MBID could be found.");
								albummeta.musicbrainz.album = {error: language.gettext("musicbrainz_noalbum")};
								parent.updateData({
											musicbrainz: { album_releasegroupid: null },
											wikipedia: { albumlink: null },
											discogs: {  albumlink: null }
										}, albummeta);
								self.album.doBrowserUpdate();
								return;
							}
							debug.debug(medebug,parent.nowplayingindex,"album is populating",albummeta.musicbrainz_id);
							musicbrainz.album.getInfo(
								albummeta.musicbrainz_id,
								self.album.mbResponseHandler,
								self.album.mbResponseHandler
							);
						} else {
							debug.trace(medebug,parent.nowplayingindex,"album is already populated",albummeta.musicbrainz_id);
						}
					},

					mbResponseHandler: function(data) {
						debug.debug(medebug,parent.nowplayingindex,"got album data for",albummeta.musicbrainz_id);
						// Look for the information that other plugins need
						var update = {
										musicbrainz: { album_releasegroupid: null },
										wikipedia: { albumlink: null },
										discogs: {  albumlink: null }
									};
						if (data) {
							albummeta.musicbrainz.album = data;
							var wikilinks = { user: null, english: null, anything: null };
							debug.trace(medebug,parent.nowplayingindex,"wikipedia language is",wikipedia.getLanguage());

							var domain = '^http://'+wikipedia.getLanguage();
							var re = new RegExp(domain);
							for (var i in data.relations) {
								if (data.relations[i].type == "wikipedia" && update.wikipedia.albumlink === null) {
									debug.info(medebug,parent.nowplayingindex,"has found a Wikipedia album link",data.relations[i].url.resource);
									var wikitemp = data.relations[i].url.resource;
									if (re.test(wikitemp)) {
										debug.trace(medebug,parent.nowplayingindex,"found user domain wiki link");
										wikilinks.user = wikitemp;
									} else if (wikitemp.match(/en.wikipedia.org/)) {
										debug.trace(medebug,parent.nowplayingindex,"found english domain wiki link");
										wikilinks.english = wikitemp;
									} else {
										debug.trace(medebug,parent.nowplayingindex,"found wiki link");
										wikilinks.anything = wikitemp;
									}
								}
								if (data.relations[i].type == "discogs" && update.discogs.albumlink === null) {
									debug.trace(medebug,parent.nowplayingindex,"has found a Discogs album link",data.relations[i].url.resource);
									update.discogs.albumlink = data.relations[i].url.resource;
								}
							}
							if (update.wikipedia.albumlink == null) {
								if (wikilinks.user) {
									debug.trace(medebug,parent.nowplayingindex,"using user domain wiki link",wikilinks.user);
									update.wikipedia.albumlink = wikilinks.user;
								} else if (wikilinks.english) {
									debug.trace(medebug,parent.nowplayingindex,"using english domain wiki link",wikilinks.english);
									update.wikipedia.albumlink = wikilinks.english;
								} else if (wikilinks.anything) {
									debug.trace(medebug,parent.nowplayingindex,"using any old domain wiki link",wikilinks.anything);
									update.wikipedia.albumlink = wikilinks.anything;
								}

							}

							if (data['release-group']) {
								update.musicbrainz.album_releasegroupid = data['release-group'].id;
							}
						} else {
							albummeta.musicbrainz.album = {error: language.gettext("musicbrainz_noinfo")};
						}
						parent.updateData(update,albummeta);
						self.album.doBrowserUpdate();
					},

					coverResponseHandler: function(data) {
						debug.info(medebug,parent.nowplayingindex,"got Cover Art Data",data);
						parent.updateData({ musicbrainz: { coverart: data }}, albummeta);
						if (displaying) {
							$("#coverart").html(getCoverHTML(albummeta.musicbrainz.coverart));
						}
					},

					doBrowserUpdate: function() {
						if (displaying && albummeta.musicbrainz.album !== undefined) {
							debug.debug(medebug,parent.nowplayingindex,"album was asked to display");
							var up = null;
							if (parent.playlistinfo.type == 'stream') {
								browser.Update(null, 'album', me, parent.nowplayingindex, { name: "",
														link: "",
														data: null
														}
								);
							} else if (albummeta.musicbrainz.album.error) {
								up = { name: albummeta.name,
									   link: null,
									   data: '<h3 align="center">'+albummeta.musicbrainz.album.error+'</h3>'}
							} else {
								up = { name: albummeta.musicbrainz.album.title,
									   link: 'http://musicbrainz.org/release/'+albummeta.musicbrainz.album.id,
									   data: html = getAlbumHTML(albummeta.musicbrainz.album)}

							}
							browser.Update(
								null,
								'album',
								me,
								parent.nowplayingindex,
								up
							);
						}
					}
				}

			}();

			this.track = function() {

				return {

					populate: function() {
						if (trackmeta.musicbrainz === undefined) {
							trackmeta.musicbrainz = {};
						}
						if (trackmeta.musicbrainz.track === undefined) {
							if (trackmeta.musicbrainz_id == "") {
								debug.debug(medebug,parent.nowplayingindex,"Track asked to populate but no MBID, trying again in 2 seonds");
								setTimeout(self.track.populate, 2000);
								return;
							}
							if (trackmeta.musicbrainz_id === null) {
								debug.info(medebug,parent.nowplayingindex,"Track asked to populate but no MBID could be found..");
								trackmeta.musicbrainz.track = {};
								trackmeta.musicbrainz.track.error = {error: language.gettext("musicbrainz_notrack")};
								parent.updateData({
										wikipedia: { tracklink: null },
										discogs: {  tracklink: null }
									}, trackmeta);
								self.track.doBrowserUpdate();
								return;
							}
							debug.debug(medebug,parent.nowplayingindex,"track is populating",trackmeta.musicbrainz_id);
							musicbrainz.track.getInfo(trackmeta.musicbrainz_id, self.track.mbResponseHandler, self.track.mbResponseHandler);
						} else {
							debug.trace(medebug,parent.nowplayingindex,"track is already populated",trackmeta.musicbrainz_id);
						}
					},

					mbResponseHandler: function(data) {
						debug.debug(medebug,parent.nowplayingindex,"got track data for",trackmeta.musicbrainz_id,data);
						// Look for the information that other plugins need
						var update = 	{
							wikipedia: { tracklink: null },
							discogs: { tracklink: null }
						};
						if (data) {
							if (data.error) {
								trackmeta.musicbrainz.track = {};
								trackmeta.musicbrainz.track.error = data;
							} else {
								trackmeta.musicbrainz.track = data;
								if (data.recording) {
									for (var i in data.recording.relations) {
										if (data.recording.relations[i].type == "wikipedia" && update.wikipedia.tracklink === null) {
											debug.trace(medebug,parent.nowplayingindex,"has found a Wikipedia track link!!!!!",data.recording.relations[i].url.resource);
											update.wikipedia.tracklink = data.recording.relations[i].url.resource;
										}
										if (data.recording.relations[i].type == "discogs" && update.discogs.tracklink === null) {
											debug.trace(medebug,parent.nowplayingindex,"has found a Discogs track link!!!!!",data.recording.relations[i].url.resource);
											update.discogs.tracklink = data.recording.relations[i].url.resource;
										}
									}
								}
								if (data.work) {
									for (var i in data.work.relations) {
										if (data.work.relations[i].type == "wikipedia" && update.wikipedia.tracklink === null) {
											debug.trace(medebug,parent.nowplayingindex,"has found a Wikipedia track link!!!!!",data.work.relations[i].url.resource);
											update.wikipedia.tracklink = data.work.relations[i].url.resource;
										}
										if (data.work.relations[i].type == "discogs" && update.discogs.tracklink === null) {
											debug.trace(medebug,parent.nowplayingindex,"has found a Discogs track link!!!!!",data.work.relations[i].url.resource);
											update.discogs.tracklink = data.work.relations[i].url.resource;
										}
									}
								}
							}
						} else {
							trackmeta.musicbrainz.track.error = {error: language.gettext("musicbrainz_noinfo")};
						}
						parent.updateData(update,trackmeta);
						self.track.doBrowserUpdate();
					},

					doBrowserUpdate: function() {
						if (displaying && trackmeta.musicbrainz.track !== undefined &&
								(trackmeta.musicbrainz.track.error !== undefined ||
								trackmeta.musicbrainz.track.recording !== undefined ||
								trackmeta.musicbrainz.track.work !== undefined)) {
							debug.debug(medebug,parent.nowplayingindex,"track was asked to display");
							var link = null;
							if (trackmeta.musicbrainz.track.recording) {
								link = 'http://musicbrainz.org/recording/'+trackmeta.musicbrainz.track.recording.id;
							}
							browser.Update(
								null,
								'track',
								me,
								parent.nowplayingindex,
								{	name: trackmeta.name,
									link: link,
									data: getTrackHTML(trackmeta.musicbrainz.track)
								}
							);
						}
					}
				}

			}();
		}
	}

}();

nowplaying.registerPlugin("musicbrainz", info_musicbrainz, "icon-musicbrainz", "button_musicbrainz");
