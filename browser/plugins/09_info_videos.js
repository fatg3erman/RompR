var info_videos = function() {

	var me = "videos";

	function getVideosHtml(data) {
		if (data.release && data.release.data.videos) {
			return doVideos(mungeDiscogsData(data.release.data.videos));
		} else if (data.master && data.master.data.videos) {
			return doVideos(mungeDiscogsData(data.master.data.videos));
		} else {
			return '<h3 align="center">No Videos Found</h3>';
		}
	}

	function mungeDiscogsData(videos) {
		debug.trace("VIDEOS PLUGIN","Doing Videos From Discogs Data",videos);
		var ids = new Array();
		for (var i in videos) {
			var u = videos[i].uri;
			if (videos[i].embed == true && u.match(/youtube\.com/)) {
				var d = u.match(/http:\/\/www\.youtube\.com\/watch\?v=(.+)$/);
				if (d && d[1]) {
					ids.push(d[1]);
				}
			}
		}
		return ids;
	}

	function getVideosFromYoutube(searchresult) {
		if (searchresult.items) {
			var ids = new Array();
			for (var i in searchresult.items) {
				ids.push(searchresult.items[i].id.videoId);
			}
			return doVideos(ids);
		} else {
			return '<h3 align="center">No Videos Found</h3>';
		}
	}

	function doVideos(videos) {
		var html = '';
		if (videos.length == 0) {
			return '<h3 align="center">No Videos Found</h3>';
		}
		for (var i in videos) {
			html += '<div class="video"><iframe class="youtubevid" src="http://www.youtube.com/embed/'+videos[i]+'"></iframe></div>';
		}
		return html;
	}

	function searchYoutube(term, callback) {
		debug.trace("VIDEOS","Searching Youtube for",term);
		$.ajax({
			type: "POST",
			dataType: "json",
			url: 'browser/backends/google.php',
			data: {uri: encodeURIComponent("https://www.googleapis.com/youtube/v3/search?part=snippet&maxResults=10&q="+encodeURIComponent(term+' Band')+"&key="+google_api_key)},
			success: callback,
			error: function(data) {
				debug.error("VIDEOS PLUGIN","Youtube search failed",data);
				callback({error: data});
			}
		});
	}

	return {
		getRequirements: function(parent) {
			return ['discogs'];
		},

		collection: function(parent, artistmeta, albummeta, trackmeta) {

			debug.trace("VIDEOS PLUGIN", "Creating data collection");

			var self = this;
			var displaying = false;
			if (artistmeta.videos === undefined) {
				artistmeta.videos = {};
			}

			function artistSearchYoutubeDone(data) {
				debug.trace("VIDEOS","Got artist search data",data);
				artistmeta.videos.youtube = data;
				self.artist.doBrowserUpdate();
			}

			this.populate = function() {
				self.album.populate();
				self.artist.populate();
			}

			this.displayData = function() {
				displaying = true;
				self.artist.doBrowserUpdate();
				self.album.doBrowserUpdate();
                browser.Update(null, 'track', me, parent.nowplayingindex, { name: "",
                    					link: "",
                    					data: null
                						}
				);
			}

			this.stopDisplaying = function() {
				displaying = false;
			}

			this.album = function() {

				return {
					populate: function() {
						if (albummeta.discogs.album.error === undefined &&
							albummeta.discogs.album.master === undefined &&
							albummeta.discogs.album.release === undefined) {
							debug.trace("VIDEOS PLUGIN",parent.nowplayingindex,"No data yet, trying again in 1 second");
							setTimeout(self.album.populate, 1000);
							return;
						}
						self.album.doBrowserUpdate();
				    },

					doBrowserUpdate: function() {
						if (displaying && albummeta.discogs.album !== undefined &&
								(albummeta.discogs.album.error !== undefined ||
								albummeta.discogs.album.master !== undefined ||
								albummeta.discogs.album.release !== undefined)) {
							debug.mark("VIDEOS PLUGIN",parent.nowplayingindex,"album was asked to display");
			                browser.Update(null, 'album', me, parent.nowplayingindex, { name: albummeta.name,
			                    					link: "",
			                    					data: getVideosHtml(albummeta.discogs.album)
			                						}
							);
						}
					}
				}
			}();

			this.artist = function() {
				return {
					populate: function() {
						if (artistmeta.videos.youtube === undefined) {
							searchYoutube(artistmeta.name, artistSearchYoutubeDone);
						} else {
							self.artist.doBrowserUpdate();
						}
					},

					doBrowserUpdate: function() {
						if (displaying && artistmeta.videos.youtube !== undefined) {
			                browser.Update(null, 'artist', me, parent.nowplayingindex, { name: artistmeta.name,
			                    					link: "",
			                    					data: getVideosFromYoutube(artistmeta.videos.youtube)
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
