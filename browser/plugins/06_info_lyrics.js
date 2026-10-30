var info_lyrics = function() {

	var me = "lyrics";

	return {
		getRequirements: function(parent) {
			return ['file'];
		},

		collection: function(parent, artistmeta, albummeta, trackmeta) {

			debug.debug("LYRICS PLUGIN", "Creating data collection");

			var self = this;
			var synced = [];
			var syncing = false;

			function formatLyrics(data) {
				debug.trace("LYRICS PLUGIN","Formatting Lyrics");
				if (data.instrumental) {
					return '<h3 align=center>'+language.gettext("lyrics_instrumental")+'</h3>';
				} else if (
					(prefs.prefer_synced_lyrics && data.syncedLyrics && data.syncedLyrics.length > 0)
					|| (!data.plainLyrics && data.syncedLyrics && data.syncedLyrics.length > 0)
				) {
					let r = '<div class="lyrics"><h2 align="center">'+language.gettext("lyrics_lyrics")+'</h2>';
					data.syncedLyrics[0].line = data.syncedLyrics[0].line.replace(/^(\w)/, '<font size="120%">$1</font>');
					data.syncedLyrics.forEach(function(lyric, index) {
						r += '<p name="lyric_'+index+'">'+lyric.line+'</p>';
					});
					r += '</div>';
					self.synced = data.syncedLyrics;
					self.syncing = true;
					return r;
				} else if (data.plainLyrics) {
					l = data.plainLyrics.replace(/^(\w)/, '<font size="120%">$1</font>');
					l = l.replace(/\n/g, '<br>');
					return '<div class="lyrics"><h2 align="center">'+language.gettext("lyrics_lyrics")+'</h2><p>'+l+'</p></div>';
				} else {
					return data;
				}
			}

			function getSearchArtist() {
				return (albummeta.artist && albummeta.artist != "") ? albummeta.artist : parent.playlistinfo.trackartist;
			}

			this.startAfterSpecial = function() {
			}

			this.progressUpdate = function(percent) {
				if (!self.syncing) return;

				var t = infobar.getProgress();
				self.synced.forEach(function(l, i) {
					if (t >= l.time-0.5 && t <= l.time+1) {
						$(".currentlyric.playlistcurrentitem").removeClass("currentlyric playlistcurrentitem");
						$('[name="lyric_'+i+'"]').addClass("currentlyric playlistcurrentitem");
						uiHelper.scrollLyrics($('[name="lyric_'+i+'"]'));
					}
				});
			}

			this.get_lyrics = function() {
				lyrics.get_lyrics(player.status.file, getSearchArtist(), trackmeta.name, playlist.getCurrent('Time'), self.gotLyrics, self.gotLyrics)
			}

			this.gotLyrics = function(data) {
				debug.log("LYRICS", data);
				if (data.error) {
					trackmeta.lyrics.lyrics = '<h3 align=center>'+language.gettext("lyrics_nonefound")+'</h3><p>'+data.error+'</p>';
					self.doBrowserUpdate();
				} else {
					trackmeta.lyrics.lyrics = data;
					self.doBrowserUpdate();
				}
			}

			this.populate = async function() {

				// info.file will populate the lyrics metadata. Don't do it here
				parent.updateData({
					lyrics: {
						lyrics: ''
					},
					triggers: {
						lyrics: {
							lyrics: self.do_lyrics
						}
					}
				}, trackmeta);

				parent.updateData({
						lyrics: {}
					},
					artistmeta
				);

				parent.updateData({
						lyrics: {}
					},
					albummeta
				);

				if (typeof artistmeta.lyrics.layout == 'undefined')
					artistmeta.lyrics.layout = new info_layout_empty();

				if (typeof albummeta.lyrics.layout == 'undefined')
					albummeta.lyrics.layout = new info_layout_empty();

				if (typeof trackmeta.lyrics.layout == 'undefined')
					trackmeta.lyrics.layout = new info_html_layout({title: trackmeta.name, type: 'track', source: me});

				self.do_lyrics();

			}

			this.do_lyrics = function() {
				if (trackmeta.lyrics.lyrics == '')
					return;

				if (trackmeta.lyrics.lyrics === null) {
					self.get_lyrics();
				} else {
					self.doBrowserUpdate();
				}
			}

			this.doBrowserUpdate = function() {
				if (trackmeta.name) {
					trackmeta.lyrics.layout.finish(null, trackmeta.name, formatLyrics(trackmeta.lyrics.lyrics));
				} else {
					trackmeta.lyrics.layout.finish(null, 'No Track');
				}
			}
		}

	}


}();

nowplaying.registerPlugin("lyrics", info_lyrics, "icon-doc-text-1", "button_lyrics");
