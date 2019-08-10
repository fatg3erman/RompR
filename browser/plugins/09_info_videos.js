var info_videos = function() {

	var me = "videos";

	return {

		getRequirements: function(parent) {
			return ['discogs'];
		},

		collection: function(parent, artistmeta, albummeta, trackmeta) {

			debug.trace("VIDEOS PLUGIN", "Creating data collection");

			var self = this;
			var displaying = false;
			var ids = [];
			var retrytimer;

			function mungeDiscogsData(videos) {
				debug.trace("VIDEOS PLUGIN","Doing Videos From Discogs Data",videos);
				for (var i in videos) {
					var u = videos[i].uri;
					if (videos[i].embed == true && u.match(/youtube\.com/)) {
						var d = u.match(/\/\/www\.youtube\.com\/watch\?v=(.+)$/);
						if (d && d[1] && ids.indexOf(d[1]) == -1) {
							ids.push(d[1]);
						}
					}
				}
			}

			function doVideos() {
				var html = '';
				if (ids.length == 0) {
					return '<h3 align="center">No Videos Found</h3>';
				}
				for (var i in ids) {
					html += '<div class="video"><iframe class="youtubevid" src="http://www.youtube.com/embed/'+ids[i]+'"></iframe></div>';
				}
				return html;
			}

			function getVideosHtml() {
				if (albummeta.discogs.album.master && parent.playlistinfo.type != 'stream') {
					debug.debug('VIDEOS', 'Doing videos from album master');
					mungeDiscogsData(albummeta.discogs.album.master.data.videos);
				}
				if (albummeta.discogs.album.release && parent.playlistinfo.type != 'stream') {
					debug.debug('VIDEOS', 'Doing videos from album release');
					mungeDiscogsData(albummeta.discogs.album.release.data.videos);
				}
				if (trackmeta.discogs.track.master) {
					debug.debug('VIDEOS', 'Doing videos from track master');
					mungeDiscogsData(trackmeta.discogs.track.master.data.videos);
				}
				if (trackmeta.discogs.track.release) {
					debug.debug('VIDEOS', 'Doing videos from track release');
					mungeDiscogsData(trackmeta.discogs.track.release.data.videos);
				}
				return doVideos();
			}

			this.populate = function() {
				self.track.populate();
			}

			this.displayData = function() {
				displaying = true;
				self.track.doBrowserUpdate();
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
			}

			this.stopDisplaying = function() {
				displaying = false;
				clearTimeout(retrytimer);
			}

			this.track = function() {
				return {
					populate: function() {
						debug.trace('VIDEOS','album master', albummeta.discogs.album.master);
						debug.trace('VIDEOS','track master', trackmeta.discogs.track.master);
						debug.trace('VIDEOS','album error', albummeta.discogs.album.error);
						debug.trace('VIDEOS','track error', trackmeta.discogs.track.error);
						if ((trackmeta.discogs.track.master && albummeta.discogs.album.master) ||
							(trackmeta.discogs.track.master && albummeta.discogs.album.error)  ||
							(trackmeta.discogs.track.error  && albummeta.discogs.album.master) ||
							(trackmeta.discogs.track.error  && albummeta.discogs.album.error)) {
							self.track.doBrowserUpdate();
						} else {
							debug.trace("VIDEOS PLUGIN",parent.nowplayingindex,"No data yet, trying again in 1 second");
							retrytimer = setTimeout(self.track.populate, 2000);
						}
				    },

					doBrowserUpdate: function() {
						if (displaying && albummeta.discogs.album !== undefined && trackmeta.discogs.track !== undefined &&
								(albummeta.discogs.album.error !== undefined ||	albummeta.discogs.album.master !== undefined) &&
								(trackmeta.discogs.track.error !== undefined ||	trackmeta.discogs.track.master !== undefined)) {
							debug.mark("VIDEOS PLUGIN",parent.nowplayingindex,"track was asked to display");
			                browser.Update(null, 'track', me, parent.nowplayingindex, { name: artistmeta.name+' / '+trackmeta.name,
			                    					link: "",
			                    					data: getVideosHtml()
			                						}
							);
						}
					}
				}
			}();
		}
	}

}();

nowplaying.registerPlugin("videos", info_videos, "icon-video", "button_videos");
