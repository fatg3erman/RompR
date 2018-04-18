var info_lyrics = function() {

	var me = "lyrics";

	return {
		getRequirements: function(parent) {
			return ['file'];
		},

		collection: function(parent, artistmeta, albummeta, trackmeta) {

			debug.trace("LYRICS PLUGIN", "Creating data collection");

			var self = this;
			var displaying = false;

			function formatLyrics(data) {
				debug.trace("LYRICS PLUGIN","Formatting Lyrics");
				if (data) {
					data = data.replace(/^(\w)/, '<font size="120%">$1</font>')
					data = data.replace(/\n/g, '<br>');
				}
				return '<div class="lyrics"><h2 align="center">'+language.gettext("lyrics_lyrics")+'</h2><p>'+data+'</p></div>';
			}

            function getSearchArtist() {
                return (albummeta.artist && albummeta.artist != "") ? albummeta.artist : parent.playlistinfo.trackartist;
            }

			this.displayData = function() {
				displaying = true;
                browser.Update(null, 'album', me, parent.nowplayingindex, { name: "",
                    					link: "",
                    					data: null
                						}
				);
                browser.Update(null, 'artist', me, parent.nowplayingindex, { name: "",
                    					link: "",
                    					data: null
                						}
				);
				self.doBrowserUpdate();
			}

			this.stopDisplaying = function() {
				displaying = false;
			}

            this.startAfterSpecial = function() {

            }

            this.tryReadingTags = function() {
            	if (prefs.music_directory_albumart == "") {
        			trackmeta.lyrics = '<h3 align=center>'+language.gettext("lyrics_nonefound")+'</h3><p>'+language.gettext("lyrics_nopath")+'</p>';
        			self.doBrowserUpdate();
            	} else {
	            	$.get("browser/backends/getLyrics.php?file="+encodeURIComponent(player.status.file)+"&artist="+encodeURIComponent(getSearchArtist())+"&song="+encodeURIComponent(trackmeta.name))
	            		.done(function(data) {
	            			debug.trace("LYRICS",data);
	            			trackmeta.lyrics = data;
	            			self.doBrowserUpdate();
	            		});
	           	}
            }

			this.populate = function() {
				if (trackmeta.lyrics === undefined) {
					debug.trace("LYRICS PLUGIN",parent.nowplayingindex,"No lyrics yet, trying again in 1 second");
					setTimeout(self.populate, 1000);
					return;
				}
				if (trackmeta.lyrics === null) {
					self.tryReadingTags();
				} else {
					self.doBrowserUpdate();
				}

		    }

			this.doBrowserUpdate = function() {
				if (displaying && trackmeta.lyrics !== undefined && trackmeta.lyrics !== null) {
	                browser.Update(null, 'track', me, parent.nowplayingindex, { name: trackmeta.name,
	                    					link: "",
	                    					data: formatLyrics(trackmeta.lyrics)
	                						}
					);
				}
			}
		}

	}


}();

nowplaying.registerPlugin("lyrics", info_lyrics, "icon-doc-text-1", "button_lyrics");
