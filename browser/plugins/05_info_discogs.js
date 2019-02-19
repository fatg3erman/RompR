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

	function formatNotes(p) {
        p = p.replace(/\n/g, '<br>');
        p = p.replace(/\[a(\d+?)\]/g, '<span name="$1">$1</span>');
        p = p.replace(/\[a=(.+?)\]/g, '<a href="http://www.discogs.com/artist/$1" target="_blank">$1</a>');
        p = p.replace(/\[l=(.+?)\]/g, '$1');
        p = p.replace(/\[m(\d+?)\]/g, '<a href="http://www.discogs.com/master/$1" target="_blank"><i>External Link</i></a>');
        p = p.replace(/\[url=(.+?)\](.+?)\[\/url\]/g, '<a href="$1" target="_blank">$2</a>');
        p = p.replace(/\[b\]/g, '<b>');
        p = p.replace(/\[\/b\]/g, '</b>');
        p = p.replace(/\[i\]/g, '<i>');
        p = p.replace(/\[\/i\]/g, '</i>');
        return p;
	}

	function getReleaseHTML(data) {
		var html = "";
		debug.trace(medebug,"Generating release HTML for",data.id);
		if (data.data.releases.length > 0) {
        	html += '<div class="mbbox clearfix"><span style="float:right">PAGES: ';
        	for (var i = 1; i <= data.data.pagination.pages; i++) {
        		if (i == data.data.pagination.page) {
        			html += " <b>"+i+"</b>";
        		} else {
        			var a = data.data.pagination.urls.last || data.data.pagination.urls.first;
        			var b = a.match(/artists\/(\d+)\/releases/);
        			if (b && b[1]) {
        				html += ' <a href="#" class="infoclick clickreleasepage" name="'+b[1]+'">'+i+'</a>';
        			}
        		}
        	}
        	html += '</span></div>';
        	html += '<div class="mbbox"><table class="padded" width="100%">';
        	html += '<tr><th>'+language.gettext("title_year")+'</th><th>'+language.gettext("title_title")+'</th><th>'
        				+language.getUCtext("label_artist")+'</th><th>'+language.gettext("title_type")+'</th><th>'+language.gettext("title_label")+'</th></tr>';
        	for (var i in data.data.releases) {
        		html += '<tr>';
        		if (data.data.releases[i].year) {
        			html += '<td>'+data.data.releases[i].year+'</td>';
        		} else {
        			html += '<td></td>';
        		}
        		if (data.data.releases[i].title) {
        			html += '<td><a href="#" class="infoclick clickgetdiscstuff" target="_blank">'+
        							data.data.releases[i].title+
        							'</a><input type="hidden" value="'+data.data.releases[i].resource_url+'" />';
        			if (data.data.releases[i].role && data.data.releases[i].role != 'Main') {
        				var r = data.data.releases[i].role;
        				r = r.replace(/([a-z])([A-Z])/, '$1 $2');
        				html += '<br>(<i>'+r+'</i>)'
        			}
        			if (data.data.releases[i].trackinfo) {
        				html += '<br>(<i>'+data.data.releases[i].trackinfo+'</i>)'
        			}
        			html += '</td>';
        		} else {
        			html += '<td></td>';
        		}
        		if (data.data.releases[i].artist) {
        			html += '<td>'+data.data.releases[i].artist+'</td>';
        		} else {
        			html += '<td></td>';
        		}
        		if (data.data.releases[i].format) {
        			html += '<td>'+data.data.releases[i].format+'</td>';
        		} else {
        			html += '<td></td>';
        		}
        		if (data.data.releases[i].label) {
        			html += '<td>'+data.data.releases[i].label+'</td>';
        		} else {
        			html += '<td></td>';
        		}
        		html += '</tr>';
        	}
        	html += '</table></div>';
        	html += '<div class="mbbox clearfix"><span style="float:right">'+language.gettext("label_pages")+': ';
        	for (var i = 1; i <= data.data.pagination.pages; i++) {
        		if (i == data.data.pagination.page) {
        			html += " <b>"+i+"</b>";
        		} else {
        			var a = data.data.pagination.urls.last || data.data.pagination.urls.first;
        			var b = a.match(/artists\/(\d+)\/releases/);
        			if (b && b[1]) {
        				html += ' <a href="#" class="infoclick clickreleasepage" name="'+b[1]+'">'+i+'</a>';
        			}
        		}
        	}
        	html += '</span></div>';
		}
		debug.trace(medebug,"Returning release HTML for",data.id);
		return html;
	}

	function getAlbumHTML(data) {
		debug.trace(medebug,"Creating HTML from release/master data",data);

		if (data.error && data.master === undefined && data.release === undefined) {
			return '<h3 align="center">'+data.error.error+'</h3>';
		}

        var html = '<div class="containerbox info-detail-layout">';
    	html += '<div class="info-box-fixed info-box-list info-border-right">';
		if (data.release) {
			html += getStyles(data.release.data.styles);
		} else {
			html += getStyles(data.master.data.styles);
		}
		if (data.release) {
			html += getGenres(data.release.data.genres);
		} else {
			html += getGenres(data.master.data.genres);
		}

		if (data.release) {
			html += '<br><ul><li><b>'+language.gettext("discogs_companies")+'</b></li>';
			for (var i in data.release.data.companies) {
				html += '<li>'+data.release.data.companies[i].entity_type_name+
							" "+data.release.data.companies[i].name+'</li>';

			}
			html += '</ul>';
		}

		html += '</div>';

    	html += '<div class="info-box-expand stumpy">';
        if (data.master && data.master.data.notes) {
        	var n = data.master.data.notes;
        	n = n.replace(/\n/g, '<br>');
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

		if (data.release) {
			html += getTracklist(data.release.data.tracklist)
		} else {
			html += getTracklist(data.master.data.tracklist)
		}

		html += '</div>';
		html += '</div>';
		return html;
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

			debug.trace(medebug, "Creating data collection");

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
				debug.trace(medebug,parent.nowplayingindex,source,"is handling a click event");
				if (element.hasClass('clickdoartist')) {
					var targetdiv = element.parent().next();
					if (!(targetdiv.hasClass('full')) && element.isClosed()) {
						doSomethingUseful(targetdiv, language.gettext("info_gettinginfo"));
	        			targetdiv.slideToggle('fast');
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
				} else if (element.hasClass('clickreleasepage')) {
					var targetdiv = element.parent().parent().parent().attr("name");
					element.parent().parent().parent().addClass("expectingpage_"+element.text());
					doSomethingUseful(element.parent().parent(), language.gettext("info_gettinginfo"));
					getArtistReleases(element.attr('name'), element.text());
				} else if (element.hasClass('clickdodiscography')) {
					var targetdiv = element.parent().next();
					if (!(targetdiv.hasClass('full')) && element.isClosed()) {
						doSomethingUseful(targetdiv, language.gettext("info_gettinginfo"));
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
		        } else if (element.hasClass('clickzoomimage')) {
					imagePopup.create(element, event, element.next().val());
				} else if (element.hasClass('clickgetdiscstuff')) {
					var link = element.next().val();
					var b = link.match(/(releases\/\d+)|(masters\/\d+)/);
					if (b && b[0]) {
						debug.log("DISCOGS","Getting info for",b[0])
						discogs.album.getInfo('', b[0],
							function(data) {
								debug.log("DISCOGS", "Got Data",data);
								if (data.data.uri) {
									debug.log("DISCOGS", "Opening Data",data.data.uri);
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
				} else if (element.hasClass('clickexpandbox')) {
					var id = element.attr('name');
					var expandingframe = element.parent().parent().parent().parent();
					var content = expandingframe.html();
					content=content.replace(/<i class="icon-expand-up.*?>/, '');
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
			}

			function getArtistData(id) {
				debug.mark(medebug,parent.nowplayingindex,"Getting data for artist with ID",id);
				if (artistmeta.discogs['artist_'+id] === undefined) {
					debug.trace(medebug,parent.nowplayingindex," ... retrieivng data");
					discogs.artist.getInfo(
						'artist_'+id,
						id,
						self.artist.extraResponseHandler,
						self.artist.extraResponseHandler
					);
				} else {
					debug.trace(medebug,parent.nowplayingindex," ... displaying what we've already got");
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
				debug.mark(medebug,parent.nowplayingindex,"Looking for release info for",name,"page",page);
				if (artistmeta.discogs['discography_'+name+"_"+page] === undefined) {
					debug.trace(medebug,"  ... retreiving them");
					discogs.artist.getReleases(
						name,
						page,
						'discography_'+name,
						self.artist.releaseResponseHandler,
						self.artist.releaseResponseHandler
					);
				} else {
					debug.trace(medebug,"  ... displaying what we've already got",artistmeta.discogs['discography_'+name+"_"+page]);
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

			function getArtistHTML(data, expand) {
				if (data.error) {
					return '<h3 align="center">'+data.error+'</h3>';
				}
				debug.trace(medebug, "Creating Artist HTML",data);
		        var html = '<div class="containerbox info-detail-layout">';
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
		        if (expand) {
					html += '<i class="icon-expand-up medicon clickexpandbox infoclick tleft" name="'+data.data.id+'"></i>';
				}

		        if (data.data.profile) {
			        var p = formatNotes(data.data.profile);

			        // Discogs profiles come with a bunch of references to other artists formatted as [a123456]
			        // (where 123456 is the discogs artist id). formatNotes replaces these with spans so we can
			        // get the arist bio and update the displayed items without having to resort to replacing
			        // all the html in the div every time.
			        // To avoid getting the artist data every time we display the html, we'll also check to see
			        // if we already have the data and use it now if we do.

			        // Not only that they've started doing [r=123456] to denote a release! (See Hawkwind)
			        var m = p.match(/<span name="\d+">/g);
			        if (m) {
			        	for(var i in m) {
			        		var n = m[i].match(/<span name="(\d+)">/);
			        		if (n && n[1]) {
			        			debug.shout(medebug,"Found unpopulated artist reference",n[1]);
								if (artistmeta.discogs['artist_'+n[1]] === undefined) {
									debug.trace(medebug,parent.nowplayingindex," ... retrieivng data");
									discogs.artist.getInfo(
										'artist_'+n[1],
										n[1],
										self.artist.extraResponseHandler2,
										self.artist.extraResponseHandler2
									);
								} else {
									debug.trace(medebug,parent.nowplayingindex," ... displaying what we've already got");
									var name = artistmeta.discogs['artist_'+n[1]].data.name;
									var link = artistmeta.discogs['artist_'+n[1]].data.uri;
									p = p.replace(new RegExp('<span name="'+n[1]+'">'+n[1]+'<\/span>', 'g'), '<a href="'+link+'" target="_blank">'+name+'</a>');
								}
			        		}
			        	}
			        }


			        html += '<p>'+p+'</p>';
			    }
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

		        html += '</div>';

		        html += '</div>';
		        return html;
			}

			function doMembers(members) {
				var html = "";
		    	for (var i in members) {
		    		html += '<div class="mbbox">';
        			html += '<i class="icon-toggle-closed menu infoclick clickdoartist" name="'+members[i].id+'"></i>';
        			var n = members[i].name;
        			n = n.replace(/ \(\d+\)$/, '');
        			html += '<span class="title-menu">'+n+'</span>';
        			html += '</div>';
	        		html += '<div name="artist_'+members[i].id+'" class="invisible"></div>';
		    	}
		    	return html;
		    }

            function getSearchArtist() {
                var a = (albummeta.artist && albummeta.artist != "") ? albummeta.artist : parent.playlistinfo.trackartist;
                if (a == "Various Artists") {
                	a = "Various";
                }
                return a;
            }

			this.artist = function() {

				var retries = 10;

				return {

					populate: function() {
						if (artistmeta.discogs === undefined) {
							artistmeta.discogs = {};
						}

						// OK so:
						// Discogs search requires authentication. And I just can't be bothered with that
						// We get help from musicbrainz, which can give us a link
						// The link will either be /artist/[number] in which case we can use it
						// Or it'll be /artist/Artist+Name which we can't use

						// artistlink is what musicbrainz will try to give us. If it's undefined it means musicbrainz
						//		hasn't yet come up with any sort of answer. If it's null it means musicbrainz failed to find one
						//		or we gave up waiting for musicbrainz.
						// artistid is what we're trying to find. (All we have at the initial stage is an artist name).
						//		If it's undefined it means we haven't even looked yet. If it's null it means we looked and failed.
						//		I can't say I remember why I check here to see if it's null, but I do recall there was a reason.

						if (artistmeta.discogs.artistinfo === undefined &&
							(artistmeta.discogs.artistid === undefined || artistmeta.discogs.artistid === null)) {
							if (artistmeta.discogs.artistlink === undefined) {
								debug.shout(medebug,parent.nowplayingindex,"Artist asked to populate but no link yet");
								retries--;
								if (retries == 0) {
									debug.warn(medebug,parent.nowplayingindex,"Artist giving up waiting for bloody musicbrainz");
									artistmeta.discogs.artistlink = null;
									setTimeout(self.artist.populate, 200);
								} else {
									setTimeout(self.artist.populate, 2000);
								}
								return;
							}
							if (artistmeta.discogs.artistlink !== null) {
								var a = artistmeta.discogs.artistlink;
								var s = a.split('/').pop()
								if (s.match(/^\d+$/)) {
									debug.mark(medebug,parent.nowplayingindex,"Artist asked to populate, using supplied link",s);
									artistmeta.discogs.artistid = s;
									discogs.artist.getInfo(
										'artist_'+s,
										s,
										self.artist.artistResponseHandler,
										self.artist.artistResponseHandler
									);
									return;
								}
							} else {
								self.artist.abjectFailure();
							}
						} else {
							debug.mark(medebug,parent.nowplayingindex,"Artist is already populated");
						}
					},

					artistResponseHandler: function(data) {
						debug.trace(medebug,parent.nowplayingindex,"got artist data",data);
						if (data) {
							artistmeta.discogs['artist_'+artistmeta.discogs.artistid] = data;
							self.artist.doBrowserUpdate();
						} else {
							self.artist.abjectFailure();
						}
					},

					abjectFailure: function() {
						debug.fail(medebug,"Failed to find any artist data");
						artistmeta.discogs.artistinfo = {error: language.gettext("discogs_nonsense")};
						self.artist.doBrowserUpdate();
					},

					extraResponseHandler: function(data) {
						debug.mark(medebug,parent.nowplayingindex,"got extra artist data for",data.id,data);
						if (data) {
							artistmeta.discogs[data.id] = data;
							putArtistData(artistmeta.discogs[data.id], data.id);
							if (data.data) {
								// Fill in any [a123456] links in the main text body.
								// TODO Not sure if this is necessary any more
								$('span[name="'+data.data.id+'"]').html(data.data.name);
								$('span[name="'+data.data.id+'"]').wrap('<a href="'+data.data.uri+'" target="_blank"></a>');
							}
						}
					},

					// TODO Not sure if this is necessary any more
					extraResponseHandler2: function(data) {
						debug.mark(medebug,parent.nowplayingindex,"got stupidly extra artist data for",data.id,data);
						if (data) {
							artistmeta.discogs[data.id] = data;
							if (data.data) {
								// Fill in any [a123456] links in the main text body.
								$('span[name="'+data.data.id+'"]').html(data.data.name);
								$('span[name="'+data.data.id+'"]').wrap('<a href="'+data.data.uri+'" target="_blank"></a>');
							}
						}
					},

					releaseResponseHandler: function(data) {
						debug.mark(medebug,parent.nowplayingindex,"got release data for",data.id,data);
						if (data) {
							artistmeta.discogs[data.id+"_"+data.data.pagination.page] = data;
							putArtistReleases(artistmeta.discogs[data.id+"_"+data.data.pagination.page], data.id);
						}
					},

					doBrowserUpdate: function() {
						if (displaying) {
							debug.mark(medebug,parent.nowplayingindex,"artist was asked to display");
							// Any errors (such as failing to find the artist) go under artistinfo. Originally, this was where the actual artist info would go
							// too, but then this bright spark had the idea to index all the artist info by the artist ID. This is indeed useful.
							// But if there was an error in the initial search we don't know the ID. Hence artistinfo still exists and has to be checked.
							var up = null;
							if (artistmeta.discogs.artistinfo && artistmeta.discogs.artistinfo.error) {
								up = { name: artistmeta.name,
									   link: null,
									   data: '<h3 align="center">'+artistmeta.discogs.artistinfo.error+'</h3>'}
							} else if (artistmeta.discogs.artistid !== null &&
										artistmeta.discogs['artist_'+artistmeta.discogs.artistid] !== undefined) {
								up = { name: artistmeta.name,
									   link: artistmeta.discogs.artistlink,
									   data: getArtistHTML(artistmeta.discogs['artist_'+artistmeta.discogs.artistid], false)}
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
					}
				}
			}();

			this.album = function() {

				var retries = 12;

				return {

					populate: function() {
						// We need to initialise these variables, to avoid 'cannot set property of' errors later.
						if (albummeta.discogs === undefined) {
							albummeta.discogs = {};
						}
						if (albummeta.discogs.album === undefined) {
							albummeta.discogs.album = {};
						}

						// error will be set if there was,er, an error.
						// master will be set if we got some actual data.
						if (albummeta.discogs.album.error === undefined &&
							albummeta.discogs.album.master === undefined) {

							if (albummeta.discogs.albumlink === undefined) {
								debug.shout(medebug,parent.nowplayingindex,"Album asked to populate but no link yet");
								retries--;
								if (retries == 0) {
									debug.warn(medebug,parent.nowplayingindex,"Album giving up waiting for bloody musicbrainz");
									albummeta.discogs.albumlink = null;
									setTimeout(self.album.populate, 200);
								} else {
									setTimeout(self.album.populate, 2000);
							}
								return;
							}
							if (albummeta.discogs.albumlink === null) {
								debug.fail(medebug,parent.nowplayingindex,"Album asked to populate but no link could be found");
								if (albummeta.musicbrainz.album_releasegroupid !== null &&
										albummeta.musicbrainz.album_releasegroupid !== undefined) {
									debug.mark(medebug,parent.nowplayingindex," ... trying the album release group");
									musicbrainz.releasegroup.getInfo(
										albummeta.musicbrainz.album_releasegroupid,
										'',
										self.album.mbRgHandler,
										self.album.mbRgHandler
									);
								} else {
									self.album.abjectFailure();
								}
								return;
							}
							var link = albummeta.discogs.albumlink;
							var b = link.match(/(release\/\d+)|(master\/\d+)/);
							if (b && b[0]) {
								var bunny = b[0];
								bunny = bunny.replace(/\//, 's\/');
								debug.mark(medebug,parent.nowplayingindex,"Album is populating",bunny);
								discogs.album.getInfo(
									'',
									bunny,
									self.album.albumResponseHandler,
									self.album.albumResponseErrorHandler
								);
							}
						} else {
							debug.mark(medebug,parent.nowplayingindex,"Album is already populated, I think");
						}
					},

					albumResponseHandler: function(data) {
						debug.mark(medebug,parent.nowplayingindex,"Got album data",data);
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
								self.album.albumResponseErrorHandler
							);
						} else {
							albummeta.discogs.album.master = data;
							self.album.doBrowserUpdate();
						}
					},

					albumResponseErrorHandler: function(data) {
						debug.fail(medebug,"Error in album request",data);
						albummeta.discogs.album.error = data;
						self.album.doBrowserUpdate();
					},

					mbRgHandler: function(data) {
						debug.mark(medebug,parent.nowplayingindex,"got musicbrainz release group data for",parent.playlistinfo.album, data);
						if (data.error) {
							debug.fail(medebug,parent.nowplayingindex," ... MB error",data);
							self.album.abjectFailure();
						} else {
							for (var i in data.relations) {
								if (data.relations[i].type == "discogs") {
									debug.mark(medebug,parent.nowplayingindex,"has found a Discogs album link",data.relations[i].url.resource);
									albummeta.discogs.albumlink = data.relations[i].url.resource;
									self.album.populate();
									return;
								}
							}
							self.album.abjectFailure();
						}
					},

					abjectFailure: function() {
						debug.fail(medebug,"Completely failed to find the album");
						albummeta.discogs.album.error = {error: language.gettext("discogs_noalbum")};
						self.album.doBrowserUpdate();
					},

					doBrowserUpdate: function() {
						if (displaying && albummeta.discogs.album !== undefined &&
								(albummeta.discogs.album.error !== undefined ||
								albummeta.discogs.album.master !== undefined ||
								albummeta.discogs.album.release !== undefined)) {
							debug.mark(medebug,parent.nowplayingindex,"album was asked to display");
							browser.Update(
								null,
								'album',
								me,
								parent.nowplayingindex,
								{
									name: albummeta.name,
									link: albummeta.discogs.albumlink,
									data: getAlbumHTML(albummeta.discogs.album)
								}
							);
						}
					},
				}
			}();

			this.track = function() {

				var retries = 15;

				return {

					populate: function() {
						if (trackmeta.discogs === undefined) {
							trackmeta.discogs = {};
						}
						if (trackmeta.discogs.track === undefined) {
							trackmeta.discogs.track = {};
						}
						if (trackmeta.discogs.track.error === undefined &&
							trackmeta.discogs.track.master === undefined) {
							if (trackmeta.discogs.tracklink === undefined) {
								debug.trace(medebug,parent.nowplayingindex,"Track asked to populate but no link yet");
								retries--;
								if (retries == 0) {
									debug.warn(medebug,parent.nowplayingindex,"Track giving up on bloody musicbrainz");
									trackmeta.discogs.tracklink = null;
									setTimeout(self.track.populate, 200);
								} else {
									setTimeout(self.track.populate, 2000);
								}
								return;
							}
							if (trackmeta.discogs.tracklink === null) {
								debug.mark(medebug,parent.nowplayingindex,"Track asked to populate but no link could be found");
								self.track.abjectFailure();
								return;
							}
							var link = trackmeta.discogs.tracklink;
							var b = link.match(/(release\/\d+)|(master\/\d+)/);
							if (b && b[0]) {
								var bunny = b[0];
								bunny = bunny.replace(/\//, 's\/');
								debug.mark(medebug,parent.nowplayingindex,"Track is populating",bunny);
								discogs.track.getInfo(
									'',
									bunny,
									self.track.trackResponseHandler,
									self.track.trackResponseErrorHandler
								);
							}
						} else {
							debug.mark(medebug,parent.nowplayingindex,"Track is already populated, probably");
						}
					},

					trackResponseHandler: function(data) {
						debug.mark(medebug,parent.nowplayingindex,"Got track data",data);
						if (data.data.master_id) {
							// If this key exists, then we have retrieved a release page - this data is useful but we also
							// want the master release info. (Links that come to us from musicbrainz could be either master or release).
							// We will only display when we have the master info. Since we can't go back from master to release
							// then if we got a master link from musicbrainz that's all we're ever going to get.
							// On the other hand that's still more accurate the using discog's search facility, so we're going with it.
							trackmeta.discogs.track.release = data;
							discogs.track.getInfo(
								'',
								'masters/'+data.data.master_id,
								self.track.trackResponseHandler,
								self.track.trackResponseErrorHandler
							);
						} else {
							trackmeta.discogs.track.master = data;
							self.track.doBrowserUpdate();
						}
					},

					trackResponseErrorHandler: function(data) {
						debug.fail(medebug,"Got error in track request",data);
						trackmeta.discogs.track.error = data;
						self.track.doBrowserUpdate();
					},

					abjectFailure: function() {
						debug.fail(medebug,"Completely failed to find the track");
						trackmeta.discogs.track.error = {error: language.gettext("discogs_notrack")};
						self.track.doBrowserUpdate();
					},

					doBrowserUpdate: function() {
						if (displaying && trackmeta.discogs.track !== undefined &&
								(trackmeta.discogs.track.error !== undefined ||
								trackmeta.discogs.track.master !== undefined ||
								trackmeta.discogs.track.release !== undefined)) {
							debug.mark(medebug,parent.nowplayingindex,"track was asked to display");
							browser.Update(
								null,
								'track',
								me,
								parent.nowplayingindex,
								{
									name: trackmeta.name,
									link: trackmeta.discogs.tracklink,
									data: getAlbumHTML(trackmeta.discogs.track)
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
