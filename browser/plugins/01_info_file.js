var info_file = function() {

	var me = "file";
	var maxwidth = 300;

	function format_date(date) {
		var t = parseInt(date) * 1000;
		var d = new Date(t);
		return d.toLocaleDateString(getLocale(), { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
	}

	async function make_file_information(layout, fileinfo, parent, usermeta, name) {

		if (usermeta)
			make_usermeta_info(layout, usermeta, parent);

		if (fileinfo.player)
			make_player_info(layout, fileinfo.player, parent);

		if (fileinfo.beets)
			make_beets_info(layout, fileinfo.beets);

		if (parent.playlistinfo.Comment) {
			add_comment(layout, parent.playlistinfo.Comment);
		} else if (prefs.music_directory_albumart != '') {
			try {
				data = await $.ajax({
					type: 'POST',
					url: "browser/backends/getComment.php",
					data: {file: player.status.file}
				});
				parent.playlistinfo.Comment = data;
				add_comment(layout, data);
			} catch (err) {
				parent.playlistinfo.Comment = "NOCOMMENT";
			}
		}
		try {
			if (typeof(fileinfo.albums_spoti) == 'object' && fileinfo.albums_spoti.length > 0) {
				layout.add_non_flow_box_header({title: language.gettext('label_bythisartist')});
				fileinfo.albums_spoti_widget = layout.add_non_flow_box();
				fileinfo.albums_spoti_widget.addClass('fullwidth medium_masonry_holder');
				fileinfo.albums_spoti_widget.removeClass('masonry-initialised');
				fileinfo.albums_spoti_widget.spotifyAlbumThing({
					classes: 'nobwobbler nobalbum spotify_album_masonry selecotron',
					itemselector: 'nobwobbler',
					sub: null,
					showbiogs: false,
					maxwidth: maxwidth,
					is_plugin: false,
					imageclass: 'spotify_album_image',
					data: fileinfo.albums_spoti
				});
			}
		} catch (err) {

		}

		layout.finish(null, name);

	}

	function add_comment(layout, poo) {
		// The value NOCOMMENT prevents us from repeatedly querying the backend
		// for a comment in the event that there isn't one.
		if (poo == "NOCOMMENT")
			return;

		layout.add_flow_box_header({title: language.gettext("info_comment").replace(':','')});
		layout.add_flow_box(poo);
	}

	function make_player_info(layout, info, parent) {

		var file = decodeURI(info.file);
		file = file.replace(/^file:\/\//, '');

		layout.add_flow_box_header({title: language.gettext("info_file")});
		fl = layout.add_flow_box_wrap_all(file);
		fl.addClass('monospace');

		if (info.Performer) {
			if (typeof info.Performer == "object") {
				info.Performer = info.Performer.join(';');
			}
			layout.add_flow_box_header({title: language.gettext("info_performers")});
			layout.add_flow_box(concatenate_artist_names(info.Performer.split(';')));
		}
		if (info.Composer) {
			if (typeof info.Composer == "object") {
				info.Composer = info.Composer.join(';');
			}
			layout.add_flow_box_header({title: language.gettext("info_composers")});
			layout.add_flow_box(concatenate_artist_names(info.Composer.split(';')));
		}
		if (info.Comment && parent.playlistinfo.Comment == '') {
			if (typeof info.Comment == "object") {
				info.Comment = info.Comment.join('<br />');
			}
			var poo = info.Comment;
			if (parent.playlistinfo.type == 'stream' && parent.playlistinfo.stream) {
				poo += '<br />'+parent.playlistinfo.stream;
			}
			parent.playlistinfo.Comment = poo;
		}

		var filetype = get_file_extension(file).toLowerCase();
		var list = layout.add_sidebar_list(language.gettext("info_format"), filetype);
		if (info.bitrate && info.bitrate != 'None' && info.bitrate != 0) {
			layout.append_to_list(list, language.gettext("info_bitrate"), info.bitrate);
		}
		if (info.audio) {
			var p = info.audio.split(":");
			if (p[1] == 'f') p[1] = '16';
			var format = p[0]+' Hz, '+p[1]+' Bit, ';
			if (p[2] == 1) {
				format += language.gettext("info_mono");
			} else if (p[2] == 2) {
				format += language.gettext("info_stereo");
			} else {
				format += p[2]+' '+language.gettext("info_channels");
			}
			layout.append_to_list(list, language.gettext("info_samplerate"), format);
		}

		if (info.Date) {
			if (typeof info.Date == "string") {
				info.Date = info.Date.split(';');
			}
			layout.append_to_list(list, language.gettext("info_date"), info.Date[0]);
		}

		if (info.Genre) {
			if (typeof info.Genre == "string") {
				info.Genre = info.Genre.split(';');
			}
			layout.append_to_list(list, language.gettext("info_genre"), info.Genre.join(', '));
		}

	}

	function make_beets_info(layout, data) {
		try {
			var file = decodeURIComponent(player.status.file);
			if (!file)
				return;

			layout.add_flow_box_header({title: language.gettext("info_file")});
			layout.add_flow_box_wrap_all(file);
			var list = layout.add_sidebar_list(language.gettext("info_format"), data.format);
			layout.append_to_list(list, language.gettext("info_samplerate"), data.samplerate+' Hz, '+data.bitdepth+' Bit');
			[ 'bitrate', 'channels', 'year', 'genre', 'label', 'disctitle', 'encoder'].forEach(function(thing) {
				if (data[thing])
					layout.append_to_list(list, language.gettext("info_"+thing), data[thing]);

			});
			if (data.composer) {
				layout.add_flow_box_header({title: language.gettext("info_composers")});
				layout.add_flow_box(data.composer);
			}

			if (data.comments) {
				parent.playlistinfo.Comment = data.comments;
			} else {
				parent.playlistinfo.Comment = "NOCOMMENT";
			}

		} catch (err) {
			layout.add_flow_box('Sorry, there is a bug with creating this box from Beets server info. Please report this');
		}
	}

	function make_usermeta_info(layout, usermeta, parent) {

		layout.add_sidebar_list(language.gettext('label_rating'), '<i class="icon-'+usermeta.Rating+'-stars rating-icon-big infoclick clicksetrating"></i><input type="hidden" value="'+parent.nowplayingindex+'" />');

		var tl = layout.add_sidebar_list(language.gettext("musicbrainz_tags"), '<i class="icon-plus infoclick smallicon clickaddtags"></i>');
		usermeta.Tags.forEach(function(tag) {
			layout.append_to_list(tl, '&nbsp;', '<span class="tag">'+tag+'<i class="icon-cancel-circled clickicon tagremover inline-icon"></i></span>');
		});

		layout.add_flow_box_header({title: language.gettext('label_collinfo')});

		if (prefs.player_backend == 'mopidy') {
			if (usermeta.isSearchResult < 2 && usermeta.Hidden == 0) {
				layout.add_flow_box(language.gettext('label_incoll'));
			} else {
				layout.add_flow_box('<span class="infoclick clickaddtocollection">'+language.gettext('label_notincoll')+'</span>');
			}
		}

		if (usermeta.Playcount) {
			switch (usermeta.Playcount) {
				case '0':
					layout.add_flow_box(language.gettext('played_never'));
					break
				case '1':
					layout.add_flow_box(language.gettext('played_once'));
					break
				case '2':
					layout.add_flow_box(language.gettext('played_twice'));
					break
				default:
					layout.add_flow_box(language.gettext('played_n',[usermeta.Playcount]));
					break
			}
		}

		if (typeof usermeta.Last != 'undefined' && usermeta.Last != 0) {
			layout.add_flow_box(language.gettext('played_last',[format_date(usermeta.Last)]));
		}

		if (usermeta.isSearchResult < 2 && usermeta.Hidden == 0) {
			layout.add_flow_box(language.gettext('added_on',[format_date(usermeta.DateAdded)]));
		}

	}

	return {
		getRequirements: function(parent) {
			return [];
		},

		collection: function(parent, artistmeta, albummeta, trackmeta) {

			debug.debug("FILE PLUGIN", "Creating data collection");

			var self = this;

			this.populate = function() {
				parent.updateData({
					file: { },
					fileinfo: { },
					lyrics: {
						lyrics: ''
					}
				}, trackmeta);

				parent.updateData({
					file: { },
				}, albummeta);

				parent.updateData({
					file: { },
				}, artistmeta);

				// Don't put these definitions in the updateData calls above
				// because they're functions and get treated like triggers
				if (typeof albummeta.file.layout == 'undefined')
					albummeta.file.layout = new info_layout_empty();

				if (typeof artistmeta.file.layout == 'undefined')
					artistmeta.file.layout = new info_layout_empty();

				if (typeof trackmeta.file.layout == 'undefined') {
					trackmeta.file.layout = new info_sidebar_layout({title: trackmeta.name, type: 'track', source: me});
					var file = parent.playlistinfo.file;
					var m = file.match(/^beets:library:track(:|;)(\d+)/)
					if (m && m[2] && prefs.beets_server_location != '') {
						debug.trace("FILE PLUGIN","File is from beets server",m[2]);
						self.updateBeetsInformation(m[2]);
					} else {
						setTimeout(function() {
							player.controller.do_command_list([]).then(self.updateFileInformation);
						}, 500);
					}

				}
			}

			this.handleClick = function(source, element, event) {
				if (element.hasClass("clicksetrating")) {
					nowplaying.setRating(event);
				} else if (element.hasClass("clickaddtocollection")) {
					nowplaying.addTrackToCollection(event, parent.nowplayingindex);
				} else if (element.hasClass("clickremtag")) {
					nowplaying.removeTag(event, parent.nowplayingindex);
				} else if (element.hasClass("clickaddtags")) {
					tagAdder.show(event, parent.nowplayingindex);
				} else if (element.hasClass('clickspotifywidget')) {
					trackmeta.fileinfo.albums_spoti_widget.spotifyAlbumThing('handleClick', element);
				}
			}

			this.re_display = function() {
				debug.mark('FILEINFO', 'Re-displaying');
				self.doBrowserUpdate();
			}

			this.updateFileInformation = function() {
				trackmeta.fileinfo.player = cloneObject(player.status);
				debug.core("FILE PLUGIN","Doing update from",trackmeta);
				parent.updateData({
						lyrics: {
							lyrics: null
						}
					},
					trackmeta
				);
				trackmeta.lyrics.lyrics = null;
				self.getAlbumsInfo();
			}

			this.updateBeetsInformation = function(thing) {
				// Get around possible same origin policy restriction by using a php script
				$.getJSON('browser/backends/getBeetsInfo.php', 'uri='+thing)
				.done(function(data) {
					debug.core("FILE PLUGIN",'Got info from beets server',data);
					trackmeta.fileinfo.beets = data;
					parent.updateData({
							lyrics: {
								lyrics: (data.lyrics) ? data.lyrics : null
							}
						},
						trackmeta
					);
					self.getAlbumsInfo();
				})
				.fail( function() {
					debug.error("FILE PLUGIN", "Error getting info from beets server");
					self.updateFileInformation();
				});
			}

			this.getAlbumsInfo = async function() {
				var artist = (parent.playlistinfo.Artist == null) ? parent.playlistinfo.trackartist : parent.playlistinfo.Artist;
				if (artist)
					trackmeta.fileinfo.albums_spoti = await metaHandlers.get_artist_albums_as_spoti(artist);
				self.doBrowserUpdate();
			}

			this.doBrowserUpdate = async function() {
				trackmeta.file.layout.clear_out();
				make_file_information(trackmeta.file.layout, trackmeta.fileinfo, parent, trackmeta.usermeta, trackmeta.name);
			}
		}
	}
}();

nowplaying.registerPlugin("file", info_file, "icon-library", "button_fileinfo");
