var info_file = function() {

	var me = "file";

	function podComment(parent) {
		if (parent.playlistinfo.type == 'podcast' && parent.playlistinfo.Comment) {
			return '<div class="brick tagholder_wode tagholder"><table class="fileinfotable" style="width:100%"><tr><th>'+
			language.gettext("info_comment").replace(':','')+'</th></tr><tr><td class="notbold">'+parent.playlistinfo.Comment+'</td></tr></table></div>';
		}
		return '';
	}

	function createInfoFromPlayerInfo(info, parent) {

		var html = "";
		var file = decodeURI(info.file);
		debug.log("FILE INFO","Decoded File Name is",file);
		file = file.replace(/^file:\/\//, '');
		var filetype = "";
		if (file) {
			var n = file.match(/.*\.(.*?)$/);
			if (n) {
				filetype = n[n.length-1];
				filetype = filetype.toLowerCase();
				if (filetype.match(/\/|\?|\=/)) {
					filetype = "";
				}
			}
		}
		if (file == "null" || file == "undefined") file = "";
		html += '<table class="fileinfotable" style="width:100%">';
		html += '<tr><th colspan="2">Format Information</th></tr>';
		html += '<tr><td>'+language.gettext("info_file")+'</td><td>'+file;
		if (file.match(/^http:\/\/.*item\/\d+\/file/)) html += ' <i>'+language.gettext("info_from_beets")+'</i>';
		if (info.file) {
			var f = info.file.match(/^podcast[\:|\+](http.*?)\#/);
			if (f && f[1]) {
				html += '<button onclick="podcasts.doPodcast(\'filepodiput\')">'+language.gettext('button_subscribe')+'</button>'+
								'<input type="hidden" id="filepodiput" value="'+f[1]+'" />';
			}
		}
		html += '</td></tr>';
		if (filetype != "" && !file.match(/^http/)) {
			html += '<tr><td>'+language.gettext("info_format")+'</td><td>'+filetype+'</td></tr>';
		}
		if (info.bitrate && info.bitrate != 'None' && info.bitrate != 0) {
			html += '<tr><td>'+language.gettext("info_bitrate")+'</td><td>'+info.bitrate+'</td></tr>';
		}
		var ai = info.audio;
		if (ai) {
			var p = ai.split(":");
			html += '<tr><td>'+language.gettext("info_samplerate")+'</td><td>'+p[0]+' Hz, '+p[1]+' Bit, ';
			if (p[2] == 1) {
				html += language.gettext("info_mono");
			} else if (p[2] == 2) {
				html += language.gettext("info_stereo");
			} else {
				html += p[2]+' '+language.gettext("info_channels");
			}
			'</td></tr>';
		}
		if (info.Date) {
			if (typeof info.Date == "string") {
				info.Date = info.Date.split(';');
			}
			html += '<tr><td>'+language.gettext("info_date")+'</td><td>'+info.Date[0]+'</td></tr>';
		}

		if (info.Genre) {
			if (typeof info.Genre == "string") {
				info.Genre = info.Genre.split(';');
			}
			html += '<tr><td>'+language.gettext("info_genre")+'</td><td>'+info.Genre.join(', ')+'</td></tr>';
		}

		if (info.Performer) {
			if (typeof info.Performer == "object") {
				info.Performer = info.Performer.join(';');
			}
			html += '<tr><td>'+language.gettext("info_performers")+'</td><td>'+concatenate_artist_names(info.Performer.split(';'))+'</td></tr>';
		}
		if (info.Composer) {
			if (typeof info.Composer == "object") {
				info.Composer = info.Composer.join(';');
			}
			html += '<tr><td>'+language.gettext("info_composers")+'</td><td>'+concatenate_artist_names(info.Composer.split(';'))+'</td></tr>';
		}
		if (info.Comment) {
			if (typeof info.Comment == "object") {
				info.Comment = info.Comment.join('<br>');
			}
			html += '<tr><td>'+language.gettext("info_comment")+'</td><td>'+info.Comment+'</td></tr>';
		}
		if (parent.playlistinfo.type == 'stream' && parent.playlistinfo.stream) {
			html += '<tr><td>'+language.gettext("info_comment")+'</td><td>'+parent.playlistinfo.stream+'</td></tr>';
		}
		html += '</table>';
		return html;
	}

	function createInfoFromBeetsInfo(data) {

		var html = "";
		debug.log("FILE PLUGIN","Doing info from Beets server");
		var file = decodeURIComponent(player.status.file);
		var gibbons = [ 'year', 'genre', 'label', 'disctitle', 'encoder'];
		if (!file) { return "" }
		html += '<table class="motherfucker" style="width:100%">';
		html += '<tr><th colspan="2">Format Information</th></tr>';
		html += '<tr><td class="fil">'+language.gettext("info_file")+'</td><td>'+file;
		html += ' <i>'+language.gettext("info_from_beets")+'</i>';
		html = html +'</td></tr>';
		html += '<tr><td class="fil">'+language.gettext("info_format")+'</td><td>'+data.format+'</td></tr>';
		if (data.bitrate)  html += '<tr><td class="fil">'+language.gettext("info_bitrate")+'</td><td>'+data.bitrate+'</td></tr>';
		html += '<tr><td class="fil">'+language.gettext("info_samplerate")+'</td><td>'+data.samplerate+' Hz, '+data.bitdepth+' Bit, ';
		if (data.channels == 1) {
			html += language.gettext("info_mono");
		} else if (data.channels == 2) {
			html = html +language.gettext("info_stereo");
		} else {
			html += data.channels +' '+language.gettext("info_channels");
		}
		html += '</td></tr>';
		$.each(gibbons, function (i,g) {
			if (data[g]) html += '<tr><td class="fil">'+language.gettext("info_"+g)+'</td><td>'+data[g]+'</td></tr>';
		});
		if (data.composer) html += '<tr><td class="fil">'+language.gettext("info_composers")+'</td><td>'+data.composer+'</td></tr>';
		if (data.comments) html += '<tr><td class="fil">'+language.gettext("info_comment")+'</td><td>'+data.comments+'</td></tr>';
		html += '</table>';
		return html;
	}

	return {
		getRequirements: function(parent) {
			return [];
		},

		collection: function(parent, artistmeta, albummeta, trackmeta) {

			debug.trace("FILE PLUGIN", "Creating data collection");

			var self = this;
			var displaying = false;

			this.displayData = function() {
				displaying = true;
				self.doBrowserUpdate();
				browser.Update(null, 'album', me, parent.nowplayingindex,
								{ name: "", link: "", data: null }
				);
				browser.Update(null, 'artist', me, parent.nowplayingindex,
								{ name: "", link: "", data: null }
				);
			}

			this.stopDisplaying = function() {
				displaying = false;
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
				}
			}

			this.populate = function() {
				if (trackmeta.fileinfo === undefined) {
					var file = parent.playlistinfo.file;
					var m = file.match(/^beets:library:track(:|;)(\d+)/)
					if (m && m[2] && prefs.beets_server_location != '') {
						debug.trace("FILE PLUGIN","File is from beets server",m[2]);
						self.updateBeetsInformation(m[2]);
					} else {
						setTimeout(function() {
							player.controller.do_command_list([], self.updateFileInformation)
						}, 1000);
					}
				} else {
					debug.mark("FILE PLUGIN",parent.nowplayingindex,"is already populated");
				}
			}

			this.updateFileInformation = function() {
				trackmeta.fileinfo = {beets: null, player: cloneObject(player.status)};
				debug.log("FILE PLUGIN","Doing update from",trackmeta);
				trackmeta.lyrics = null;
				player.controller.checkProgress();
				self.doBrowserUpdate();
			}

			this.updateBeetsInformation = function(thing) {
				// Get around possible same origin policy restriction by using a php script
				$.getJSON('browser/backends/getBeetsInfo.php', 'uri='+thing)
				.done(function(data) {
					debug.trace("FILE PLUGIN",'Got info from beets server',data);
					trackmeta.fileinfo = {beets: data, player: null};
					if (data.lyrics) {
						debug.shout("FILE PLUGIN","Got lyrics from Beets Server");
						trackmeta.lyrics = data.lyrics;
					} else {
						trackmeta.lyrics = null;
					}
					self.doBrowserUpdate();

				})
				.fail( function() {
					debug.error("FILE PLUGIN", "Error getting info from beets server");
					self.updateFileInformation();
				});
			}

			this.ratingsInfo = function() {
				var html = "";
				debug.shout("FILE PLUGIN","Doing the monkey spanner",trackmeta);
				if (trackmeta.usermeta) {
					html += '<table class="fileinfotable" style="width:100%">';
					html += '<tr><th colspan="2">Collection Information</th></tr>';
					if (typeof trackmeta.usermeta.Playcount != 'undefined') {
						html += '<tr><td colspan="2" class="notbold">';
						switch (trackmeta.usermeta.Playcount) {
							case '0':
								html += language.gettext('played_never');
								break
							case '1':
								html += language.gettext('played_once');
								break
							case '2':
								html += language.gettext('played_twice');
								break
							default:
								html += language.gettext('played_n',[trackmeta.usermeta.Playcount]);
								break
						}
						html += '</td></tr>';
					}
					if (typeof trackmeta.usermeta.Last != 'undefined' && trackmeta.usermeta.Last != 0) {
						var t = parseInt(trackmeta.usermeta.Last) * 1000;
						var d = new Date(t);
						var s = d.toLocaleTimeString(getLocale(), { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
						html += '<tr><td colspan="2" class="notbold">'+language.gettext('played_last',[s]);
						html += '</td></tr>';
					}
					if (prefs.player_backend == 'mopidy') {
						html += '<tr><td colspan="2" class="notbold">';
						if (trackmeta.usermeta.isSearchResult < 2 && trackmeta.usermeta.Hidden == 0) {
							html += 'This track is in the Music Collection';
						} else {
							html += '<span class="infoclick clickaddtocollection">This track is not in the Music Collection. Click to add it</span>';
						}
						html += '</td></tr>';
					}
					if (trackmeta.usermeta.isSearchResult < 2 && trackmeta.usermeta.Hidden == 0) {
						var t = parseInt(trackmeta.usermeta.DateAdded) * 1000;
						var d = new Date(t);
						var s = d.toLocaleDateString(getLocale(), { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
						html += '<tr><td colspan="2" class="notbold">'+language.gettext('added_on',[s]);
						html += '</td></tr>';
					}
					html += '<tr><td>Rating:</td><td>';
					html += '<i class="icon-'+trackmeta.usermeta.Rating+'-stars rating-icon-big infoclick clicksetrating"></i>';
					html += '<input type="hidden" value="'+parent.nowplayingindex+'" />';
					html += '</td></tr>';
					html += '<tr><td style="vertical-align:top">'+language.gettext("musicbrainz_tags")+'<i class="icon-plus infoclick smallicon clickaddtags"></i></td><td>';
					for(var i = 0; i < trackmeta.usermeta.Tags.length; i++) {
						if (trackmeta.usermeta.Tags[i] != '') {
							html += '<span class="tag">'+trackmeta.usermeta.Tags[i]+'<i class="icon-cancel-circled clickicon tagremover playlisticon"></i></span> ';
						}
					}
					html += '</td></tr>';
				}
				html += '</table>';
				return html;
			}

			this.doBrowserUpdate = function() {
				if (displaying && trackmeta.fileinfo !== undefined) {
					var data = '<div id="tinfobox" class="holdingcell masonified7 helpfulholder fullwidth">';
					// data += '<div class="sizer"></div>';
					data += '<div class="brick dingo tagholder2 tagholder">';
					data += (trackmeta.fileinfo.player !== null) ? createInfoFromPlayerInfo(trackmeta.fileinfo.player, parent) : createInfoFromBeetsInfo(trackmeta.fileinfo.beets);
					data += '</div>';
					data += '<div class="brick dingo tagholder2 tagholder">';
					data += self.ratingsInfo();
					data += '</div>';
					data += podComment(parent);
					data += '</div>';
					browser.Update(
						null,
						'track',
						me,
						parent.nowplayingindex,
						{ name: trackmeta.name,
						  link: "",
						  data: data
						}
					);
					browser.rePoint($('#tinfobox'), { itemSelector: '.brick', columnWidth: '.dingo', percentPosition: true });
				}
			}
		}
	}
}();

nowplaying.registerPlugin("file", info_file, "icon-library", "button_fileinfo");
