var infobar = function() {

	var playlistinfo = {};
	var npinfo = {};
	var starttime = 0;
	var scrobbled = false;
	var playcount_incremented = false;
	var nowplaying_updated = false;
	var markedaslistened = false;
	var fontsize = 8;
	// var ftimer = null;
	var singling = false;
	var notifycounter = 0;
	var biggerizing = false;
	var current_progress = 0;

	function scrobble() {
		if (!scrobbled) {
			debug.info("INFOBAR","Track is not scrobbled");
			scrobbled = true;
			if (lastfm.isLoggedIn()) {
				if (playlistinfo.Title != "" && playlistinfo.trackartist != "") {
					var options = {
						timestamp: starttime,
						track: playlistinfo.Title,
						artist: playlistinfo.trackartist,
						album: playlistinfo.Album
					};
					options.chosenByUser = (playlistinfo.type != 'stream' && prefs.radiomode == '') ? 1 : 0;
					if (playlistinfo.albumartist && playlistinfo.albumartist != "" && playlistinfo.albumartist.toLowerCase() != playlistinfo.trackartist.toLowerCase()) {
						 options.albumArtist = playlistinfo.albumartist;
					 }
					debug.trace("INFOBAR","Scrobbling", options);
					lastfm.track.scrobble( options );
				}
			}
		}
	}

	function showLove(flag) {
		if (lastfm.isLoggedIn() && flag) {
			$("#lastfm").removeClass('invisible');
		} else {
			$("#lastfm").addClass('invisible');
		}
	}

	function updateNowPlaying() {
		if (!nowplaying_updated && lastfm.isLoggedIn()) {
			if (playlistinfo.Title != "" && playlistinfo.type && playlistinfo.type != "stream") {
				var opts = {
					track: playlistinfo.Title,
					artist: playlistinfo.trackartist,
					album: playlistinfo.Album
				};
				debug.debug("INFOBAR","is updating nowplaying",opts);
				lastfm.track.updateNowPlaying(opts);
				nowplaying_updated = true;
			}
		}
	}

	function setTheText(info) {
		var stuff = mungeplaylistinfo(info);
		if (document.title != stuff.doctitle) {
			document.title = stuff.doctitle;
		}
		npinfo = stuff.textbits
		debug.debug("INFOBAR","Now Playing Info",npinfo);
		infobar.rejigTheText();
	}

	function mungeplaylistinfo(info) {
		var npinfo = {};
		var doctitle = "RompЯ";
		debug.debug("INFOBAR", "Doing Track Things",info);
		if (info.Title != "") {
			npinfo.Title = info.Title;
			doctitle = info.Title;
		}
		var s = info.trackartist;
		if (info.type != "stream" || s != "") {
			if (info.metadata && info.metadata.artists) {
				s = "";
				var prevtype = "";
				for (var i in info.metadata.artists) {
					var joinstring = ", ";
					var afterstring = "";
					if (info.metadata.artists[i].type == "performer" && prevtype != "performer" && prevtype != "composer") {
						joinstring = " : ";
					}
					if (i == info.metadata.artists.length - 1) {
						joinstring = (info.metadata.artists.length == 2 && prevtype == "artist" && info.metadata.artists[i].type == "albumartist") ? " / " : " & ";
					}
					if (info.metadata.artists[i].type == "composer") {
						if (!info.metadata.artists[i].name.match(/composer/i)) {
							afterstring = " ("+language.gettext('label_composer')+")";
						}
						if (prevtype == "composer") {
							joinstring = ", "
						} else {
							joinstring = " : "
						}
					}
					if (i == 0) {
						joinstring = "";
					}
					s = s + joinstring + info.metadata.artists[i].name + afterstring;
					prevtype = info.metadata.artists[i].type;
				}
			}
		}
		if (s != "") {
			npinfo.Artist = s;
			doctitle = doctitle + " : " + s;
		}
		if (info.Album) {
			npinfo.Album = info.Album;
		}
		npinfo.stream = info.stream;
		if (prefs.player_in_titlebar) {
			doctitle = prefs.currenthost+' - RompЯ';
		}

		return {doctitle: doctitle, textbits: npinfo};

	}

	function getLines(numlines) {

		// debug.log('GETLINES', npinfo);

		var lines;
		switch (numlines) {
			case 2:
				lines = [
					{text: " "},
					{text: " "}
				];
				if (npinfo.Artist && npinfo.Album) {
					lines[1].text = '<i>'+frequentLabels.by+'</i>'+' '+npinfo.Artist+" "
						+'<i>'+frequentLabels.on+'</i>'+" "+npinfo.Album;
				} else if (npinfo.stream) {
					if (npinfo.stream != 'No Title') {
						lines[1].text = npinfo.stream;
					}
				} else if (npinfo.Album && npinfo.Title) {
					if (!(playlistinfo.type == 'stream' && npinfo.Title == npinfo.Album)) {
						lines[1].text = '<i>'+frequentLabels.on+'</i>'+" "+npinfo.Album;
					}
				}
				break;

			case 3:
				lines = [
					{text: " "},
					{text: '<i>'+frequentLabels.by+'</i>'+" "+npinfo.Artist},
					{text: '<i>'+frequentLabels.on+'</i>'+" "+npinfo.Album}
				]
				break;

		}

		if (npinfo.Title) {
			lines[0].text = npinfo.Title;
		} else if (npinfo.Album) {
			lines[0].text = npinfo.Album;
		}

		return lines;

	}

	function put_text_in_area(output_lines, nptext) {
		nptext.html('');
		for (var i in output_lines) {
			// Just in case we have a long line with no spaces, insert some zero-width spaces
			// after -, _, or & to permit text wrapping
			var spaceCount = (output_lines[i].text.split(" ").length - 1);
			if (spaceCount <= 0) {
				output_lines[i].text = output_lines[i].text.replace(/(_|&amp;|-)/g, '$&\u200B');
			}
			nptext.append($('<p>', {class: 'line'+i}).html(output_lines[i].text));
		}
	}

	function doNotification(message, icontype) {
		notifycounter++;
		var div = $('<div>', {
			class: 'containerbox menuitem notification new',
			id: 'notify_'+notifycounter
		}).appendTo('#notifications');
		var icon = $('<div>', {class: 'fixed'}).appendTo(div);
		icon.append($('<i>', {
			class: icontype+' svg-square'
		}));
		div.append($('<div>', {
			class: 'expand indent'
		}).html(message));
		if ($('#notifications').is(':hidden')) {
			$('#notifications').slideToggle('slow');
		}
		div.removeClass('new');
		return div;
	}

	async function biggerize() {
		// clearTimeout(ftimer);

		if (Object.keys(npinfo).length == 0) {
			$("#nptext").html("&nbsp");
			return;
		}

		// Note this relies on nowplaying and nptext having min-with: 100% and width: min-content
		// and nowplaying being wrapped in a div from which I can calculate the max width.
		// If we don't do this then long words that don't wrap can extend beyond the edge of the
		// text area, especially when the area is taller than it is wide
		// Did try using set-css-variable here but that turned out to be really slow

		var nptext = $('#nptext');
		var parent = nptext.parent();
		/* Empty it - but we need to have at least an nbsp in there for phone skin where
			we use flexbox vertical, otherwise the height will be zero, and we need to set font size
			to zero otherwise the flexbox will just keep expanding when we change the contents */
		nptext.removeClass('ready calculating').addClass('calculating').html('&nbsp').css('font-size', '0px');

		var maxheight = parent.height();
		var maxwidth = parent.parent().width();

		// Start with a font size that will fill the height if no text wraps.
		// This is like (maxheight/1.75)/1.25 which is based on the relative font
		// sizes set in the CSS
		var fontsize = Math.floor(maxheight*0.45);
		var two_lines = getLines(2);

		nptext.css('font-size', fontsize+'px');

		// debug.log('BIGGER_START','Font Size',fontsize,'Max Height',maxheight,'Max Width',maxwidth);

		if (two_lines[0] != ' ') {
			put_text_in_area(two_lines, nptext);

			// We can't simply calculate the font size based on the difference in height,
			// because we've got text wrapping onto multiple lines and we don't know how that will
			// change when we adjust the font size.
			var final_fontsize = fontsize;
			while (fontsize > 8 && (nptext.outerHeight(true) > maxheight || nptext.outerWidth(true) > maxwidth)) {
				fontsize = Math.floor(fontsize * 0.75);
				final_fontsize = fontsize;
				nptext.css('font-size', fontsize+'px');
				// debug.log('BIGGER_DOWN','Font Size',fontsize,nptext.outerHeight(true),nptext.outerWidth(true));
			}

			var increment = final_fontsize / 4;
			// debug.log('BIGGER-UP', 'Increment is',increment);
			while (
					increment > 1
					// && (nptext.outerHeight(true) < maxheight || nptext.outerWidth(true) < maxwidth)
			) {
				fontsize = Math.floor(fontsize + increment);
				increment = increment / 2;
				// debug.log('BIGGER-UP', 'Increase font size to',fontsize);
				nptext.css('font-size', fontsize+'px');
				if (nptext.outerHeight(true) < maxheight && nptext.outerWidth(true) < maxwidth) {
					// debug.log('BIGGER_UP','Font Size',fontsize,nptext.outerHeight(true),nptext.outerWidth(true));
					final_fontsize = fontsize
				} else {
					break;
				}
			}

			// debug.log('BIGGEROZE', 'Final font size is',final_fontsize);
			nptext.css('font-size', final_fontsize+'px');

			if (npinfo.Title && npinfo.Album && npinfo.Artist) {
				/* Does it still fit if we use 3 lines -  this is because
					Title
					by Artist
					on Album Has A Name
				Looks better than
					Title
					by Artist on Album Has
					A Name
				*/
				var three_lines = getLines(3);
				put_text_in_area(three_lines, nptext);
				if (nptext.outerHeight(true) > maxheight || nptext.outerWidth(true) > maxwidth) {
					put_text_in_area(two_lines, nptext);
				}

			}

			nptext.removeClass('calculating').addClass('ready');

		}
	}

	return {

		rejigTheText: async function() {
			if (!biggerizing) {
				biggerizing = true;
				await biggerize();
				biggerizing = false;
			}
		},

		albumImage: function() {
			var aImg = new Image();
			var current_image;
			const noimage = "newimages/vinyl_record.svg";
			const notafile = "newimages/thisdoesntexist.png";

			aImg.onload = function() {
				debug.debug("ALBUMPICTURE","Image Loaded",$(this).attr("src"));
				$('#albumpicture').attr("src", $(this).attr("src"));
			}

			aImg.onerror = function() {
				debug.warn("ALBUMPICTURE","Image Failed To Load",$(this).attr("src"));
				$('img[name="'+$(this).attr('name')+'"]').addClass("notfound");
				$('#albumpicture').fadeOut('fast',infobar.rejigTheText);
			}

			return {
				setSource: function(data) {
					debug.debug("ALBUMPICTURE","New source",data,"current is",aImg.src);
					if (data.ImgKey && data.ImgKey != aImg.name) {
						return false;
					}
					if (data.images === null) {
						// null means playlist.emptytrack. Set the source to a file that doesn't exist
						// and let the onerror handler do the stuff. Then if we start playing the same
						// album again the image src will change and the image will be re-displayed.
						infobar.albumImage.setKey('notrack');
						aImg.src = notafile;
					} else if (data.images.asdownloaded == "") {
						// No album image was supplied
						aImg.src = noimage;
					} else {
						debug.trace("ALBUMPICTURE","Source is being set to ",data.images.asdownloaded);
						aImg.src = data.images.asdownloaded;
					}
				},

				setSecondarySource: function(data) {
					if (data.key === undefined || data.key == aImg.getAttribute('name')) {
						debug.trace("ALBUMPICTURE","Secondary Source is being set to ",data.image);
						if (data.image != "" && data.image !== null && (aImg.src.match(noimage) !== null || aImg.src.match(notafile) !== null)) {
							debug.trace("ALBUMPICTURE","  OK, the secondary criteria have been met");
							aImg.src = data.image;
						}
					}
				},

				setKey: function(key) {
					if (aImg.name != key) {
						debug.trace("ALBUMPICTURE","Setting Image Key to ",key);
						$(aImg).attr('name', key);
					}
				},

				getKey: function() {
					return aImg.name;
				},

				displayOriginalImage: function(event) {
					imagePopup.create($(event.target), event, aImg.src);
				},

				dragEnter: function(ev) {
					evt = ev.originalEvent;
					evt.stopPropagation();
					evt.preventDefault();
					$(ev.target).parent().addClass("highlighted");
					return false;
				},

				dragOver: function(ev) {
					evt = ev.originalEvent;
					evt.stopPropagation();
					evt.preventDefault();
					return false;
				},

				dragLeave: function(ev) {
					evt = ev.originalEvent;
					evt.stopPropagation();
					evt.preventDefault();
					$(ev.target).parent().removeClass("highlighted");
					return false;
				},

				handleDrop: function(ev) {
					debug.info("INFOBAR","Something dropped onto album image");
					$(ev.target).parent().removeClass("highlighted");
					$('#albumpicture').attr("name", aImg.name).removeAttr('src');
					current_image = aImg.src;
					aImg.src = noimage;
					dropProcessor(ev.originalEvent, $('#albumpicture'), coverscraper, infobar.albumImage.uploaded, infobar.albumImage.uploadfail);
				},

				uploaded: function(data) {
					if (data.asdownlaoded) {
						infobar.albumimage.uploadfail();
						return;
					}
					debug.log("INFOBAR","Album Image Updated Successfully",aImg.name);
					$('#albumpicture').removeClass('spinner').addClass('nospin').removeAttr('name');
					update_ui_images(aImg.name, data);
				},

				uploadfail: function() {
					$('#albumpicture').removeClass('spinner').addClass('nospin').removeAttr('name');
					aImg.src = current_image;
					infobar.error(language.gettext('error_imageupload'));
				}

			}

		}(),

		playbutton: function() {
			state = 0;

			return {
				clicked: function() {
					switch (player.status.state) {
						case "play":
							player.controller.pause();
							break;
						case "pause":
						case "stop":
							player.controller.play();
							break;
					}
				},

				setState: function(s) {
					if (s != state) {
						debug.debug("INFOBAR","Setting Play Button State");
						state = s;
						switch (state) {
							case "play":
								$(".icon-play-circled").removeClass("icon-play-circled").addClass("icon-pause-circled");
								break;
							default:
								$(".icon-pause-circled").removeClass("icon-pause-circled").addClass("icon-play-circled");
								break;
						}
					}
				}
			}
		}(),

		updateWindowValues: function() {
			$("#volume").volumeControl("displayVolume", player.status.volume);
			infobar.playbutton.setState(player.status.state);
			playlist.setButtons();
			if (player.status.single == 0 && singling) {
				$('.icon-to-end-1').stopFlasher();
				singling = false;
			}
			if (player.status.single == 1 && !singling) {
				$('.icon-to-end-1').makeFlasher({flashtime: 5});
				singling = true;
			}
			if (player.status.error && player.status.error != null) {
				infobar.error(language.gettext("label_playererror")+": "+player.status.error);
			}
		},

		markCurrentTrack: function() {
			if (playlistinfo.file) {
				$('[name="'+rawurlencode(playlistinfo.file)+'"]')
					.not('.playlistcurrentitem')
					.not('.podcastresume')
					.not('.icon-no-response-playbutton')
					.addClass('playlistcurrentitem');
			}
		},

		forceTitleUpdate: function() {
			setTheText(playlistinfo);
		},

		setNowPlayingInfo: async function(info) {
			//Now playing info
			debug.log("INFOBAR","NPinfo",info);
			if (playlistinfo.file) {
				$('[name="'+rawurlencode(playlistinfo.file)+'"]').removeClass('playlistcurrentitem');
			}
			playlistinfo = info;
			infobar.markCurrentTrack();
			scrobbled = false;
			playcount_incremented = false;
			starttime = Math.floor(Date.now()/1000);
			nowplaying_updated = false;
			$("#progress").rangechooser("setOptions", {range: info.Time})
			setTheText(info);
			if (info.Title != "" && info.trackartist != "") {
				$("#stars").removeClass('invisible');
				$("#dbtags").removeClass('invisible');
				$("#ptagadd").removeClass('invisible');
				$("#playcount").removeClass('invisible');
				showLove(true);
			} else {
				$("#stars").addClass('invisible');
				$("#dbtags").addClass('invisible');
				$("#ptagadd").addClass('invisible');
				$("#playcount").addClass('invisible');
				showLove(false);
			}
			if (info.type != 'stream') {
				$("#addtoplaylist").removeClass('invisible');
				$("#bookmark").removeClass('invisible');
			} else {
				$("#addtoplaylist").addClass('invisible');
				$("#bookmark").addClass('invisible');
			}
			if (info.Id === -1) {
				$("#stars").addClass('invisible');
				$("#dbtags").addClass('invisible');
				$("#playcount").addClass('invisible');
				$("#addtoplaylist").addClass('invisible');
				$("#ptagadd").addClass('invisible');
				showLove(false);
			} else {
				infobar.albumImage.setKey(info.ImgKey);
			}
			infobar.albumImage.setSource(info);
			await infobar.checkForTrackSpecificImage(info);
			await infobar.rejigTheText();
			// uiHelper.adjustLayout();
		},

		checkForTrackSpecificImage: async function(info) {
			// if (info.domain == 'local' && prefs.music_directory_albumart != '') {
			if (info.ImgKey && (info.usetrackimages == 1 || info.type == 'podcast')) {
				try {
					data = await (jqxhr = $.ajax({
						method: 'POST',
						url: 'utils/checklocalcover.php',
						data: {
							file: info.file,
							unmopfile: info.unmopfile,
							ImgKey: info.ImgKey},
						dataType: 'json'
					}));
					if (data.ImgKey) {
						debug.log('INFOBAR', 'Setting image to track-specific image returned by plonkington boofar');
						infobar.albumImage.setSource(data);
					}
				} catch (err) {
					debug.warn('FETTLE', 'Fettling failed', err);
				}
			}
		},

		stopped: function() {
			scrobbled = false;
			nowplaying_updated = false;
			playcount_incremented = false;
		},

		setLastFMCorrections: function(info) {
			debug.log('INFOBAR', 'LastFm Corrections',info);
			if (prefs.lastfm_autocorrect && playlistinfo.metadata.iscomposer == 'false' && playlistinfo.type != "stream" && playlistinfo.type != "podcast") {
				setTheText({
					Album: info.album,
					trackartist: info.trackartist,
					Title: info.title
				});
			}
			infobar.albumImage.setSecondarySource(info);
		},

		seek: function(e) {
			if (playlistinfo.type != "stream") {
				player.controller.seek(e.max);
			}
		},

		volumeKey: function(inc) {
			var volume = parseInt(player.status.volume);
			debug.trace("INFOBAR","Volume key with volume on",volume);
			volume = volume + inc;
			if (volume > 100) { volume = 100 };
			if (volume < 0) { volume = 0 };
			if (player.controller.volume(volume)) {
				$("#volume").volumeControl("displayVolume", volume);
				prefs.save({volume: parseInt(volume.toString())});
			}
		},

		notify: function(message) {
			debug.debug("INFOBAR","Creating notification",message);
			var div = doNotification(message, 'icon-info-circled');
			setTimeout($.proxy(infobar.removenotify, div, notifycounter), 5000);
			return notifycounter;
		},

		longnotify: function(message) {
			var div = doNotification(message, 'icon-info-circled');
			setTimeout($.proxy(infobar.removenotify, div, notifycounter), 10000);
			return notifycounter;
		},

		error: function(message) {
			var div = doNotification(message, 'icon-attention-1');
			setTimeout($.proxy(infobar.removenotify, div, notifycounter), 5000);
			return notifycounter;
		},

		permerror: function(message) {
			doNotification(message, 'icon-attention-1');
			return notifycounter;
		},

		permnotify: function(message) {
			doNotification(message, 'icon-info-circled');
			return notifycounter;
		},

		updatenotify: function(id, message) {
			$('#notify_'+id).children('div.expand').first().html(message);
		},

		smartradio: function(message) {
			var div = doNotification(message, 'icon-wifi');
			setTimeout($.proxy(infobar.removenotify, div, notifycounter), 5000);
			return notifycounter;
		},

		removenotify: function(data) {
			if ($('#notifications>div').length == 1) {
				debug.debug("INFOBAR","Removing single notification");
				if ($('#notifications').is(':visible')) {
					$('#notifications').slideToggle('slow', function() {
						$('#notifications').empty();
					});
				} else {
					$('#notifications').empty();
				}
			} else {
				debug.debug("INFOBAR","Removing notification", data);
				$('#notify_'+data).fadeOut('slow', function() {
					$('#notify_'+data).remove();
				});
			}
		},

		createProgressBar: function() {
			$("#progress").rangechooser({
				ends: ['max'],
				onstop: infobar.seek,
				startmax: 0,
				animate: false
			});
			$('#playlist-progress').rangechooser({
				ends: [],
				startmax: 0,
				animate: false,
				interactive: false
			});
		},

		getProgress: function() {
			return current_progress;
		},

		setProgress: function(progress, duration) {
			current_progress = progress;
			if (progress < 3) {
				scrobbled = false;
				nowplaying_updated = false;
				markedaslistened = false;
				playcount_incremented = false;
			}
			if (progress > 4) { updateNowPlaying() };
			var percent = (duration == 0) ? 0 : (progress/duration) * 100;
			if (percent >= prefs.scrobblepercent) {
				scrobble();
			}
			if (!playcount_incremented && percent >= 95) {
				debug.trace("INFOBAR","Track playcount being updated");
				nowplaying.incPlaycount(null);
				playcount_incremented = true;
			}
			if (!markedaslistened && percent >= 95 && playlist.getCurrent('type') == 'podcast') {
				podcasts.checkMarkPodcastAsListened(playlist.getCurrent('file'));
				markedaslistened = true;
			}
			$("#progress").rangechooser("setRange", {min: 0, max: progress});
			var remain = duration - progress;
			uiHelper.setProgressTime({
				progress: progress,
				duration: duration,
				remain: remain,
				progressString: formatTimeString(progress),
				durationString: formatTimeString(duration),
				remainString: '-'+formatTimeString(remain)
			});
			nowplaying.progressUpdate(percent);
			playlist.doTimeLeft();
		},

		addToPlaylist: function(event) {
			var element = $(this);
			playlistManager.addTracksToPlaylist(
				element.attr('name'),
				[{uri: playlistinfo.file}]
			);
		}
	}

}();
