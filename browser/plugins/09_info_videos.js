var info_videos = function() {

	var me = "videos";

	return {

		getRequirements: function(parent) {
			return ['discogs'];
		},

		collection: function(parent, artistmeta, albummeta, trackmeta) {

			debug.debug("VIDEOS PLUGIN", "Creating data collection");

			var self = this;
			var ids = [];

			function mungeDiscogsData(videos) {
				debug.debug("VIDEOS PLUGIN","Doing Videos From Discogs Data",videos);
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
				if (albummeta.discogs.master && albummeta.discogs.master.data && parent.playlistinfo.type != 'stream') {
					debug.debug('VIDEOS', 'Doing videos from album master');
					mungeDiscogsData(albummeta.discogs.master.data.videos);
				}
				if (albummeta.discogs.release && albummeta.discogs.release.data && parent.playlistinfo.type != 'stream') {
					debug.debug('VIDEOS', 'Doing videos from album release');
					mungeDiscogsData(albummeta.discogs.release.data.videos);
				}
				if (trackmeta.discogs.master && trackmeta.discogs.master.data) {
					debug.debug('VIDEOS', 'Doing videos from track master');
					mungeDiscogsData(trackmeta.discogs.master.data.videos);
				}
				if (trackmeta.discogs.release && trackmeta.discogs.release.data) {
					debug.debug('VIDEOS', 'Doing videos from track release');
					mungeDiscogsData(trackmeta.discogs.release.data.videos);
				}
				return doVideos();
			}

			this.populate = function() {

				parent.updateData({
					videos: {}
				}, artistmeta);

				parent.updateData({
					videos: {}
				}, albummeta);

				parent.updateData({
					videos: {},
					triggers: {
						discogs: {
							releaseid: self.track.populate
						}
					}
				}, trackmeta);

				if (typeof artistmeta.videos.layout == 'undefined')
					artistmeta.videos.layout = new info_layout_empty();

				if (typeof albummeta.videos.layout == 'undefined')
					albummeta.videos.layout = new info_layout_empty();

				if (typeof trackmeta.videos.layout == 'undefined') {
					trackmeta.videos.layout = new info_html_layout({
						title: artistmeta.name+' / '+trackmeta.name,
						type: 'track',
						source: me
					});
					self.track.populate();
				}

			}

			this.track = function() {
				return {
					populate: async function() {
						if (artistmeta.name == '' && trackmeta.name == '') {
							trackmeta.videos.layout.finish(null, 'No Videos');
							return;
						}

						if (trackmeta.discogs.releaseid == '')
							return;

						trackmeta.videos.layout.finish(null, artistmeta.name+' / '+trackmeta.name, getVideosHtml());
					}

				}
			}();
		}
	}

}();

nowplaying.registerPlugin("videos", info_videos, "icon-video", "button_videos");
