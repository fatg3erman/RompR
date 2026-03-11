var info_allmusic = function() {

	var me = "allmusic";
	var medebug = "ALLMUSIC PLUGIN";

	return {

		getRequirements: function(parent) {
			return ['musicbrainz'];
		},

		collection: function(parent, artistmeta, albummeta, trackmeta) {

			debug.debug(medebug, "Creating data collection");

			var self = this;

			this.populate = function() {
				parent.updateData({
					allmusic: {
						done_bio: false
					},
					triggers: {
						allmusic: {
							link: self.artist.tryForAllMusicBio
						}
					}
				}, artistmeta);

				parent.updateData({
					allmusic: {}
				}, albummeta);

				parent.updateData({
					allmusic: {}
				}, trackmeta);

				if (typeof artistmeta.allmusic.layout == 'undefined')
					self.artist.populate();

				if (typeof trackmeta.allmusic.layout == 'undefined')
					trackmeta.allmusic.layout = new info_layout_empty();

				if (typeof albummeta.allmusic.layout == 'undefined')
					albummeta.allmusic.layout = new info_layout_empty();

			}

			this.handleClick = function(source, element, event) {

			}

			this.artist = function() {
				return {

					populate: function() {
						if (artistmeta.name == '') {
							artistmeta.allmusic.layout = new info_layout_empty();
						} else {
							artistmeta.allmusic.layout = new info_html_layout({title: artistmeta.name, type: 'artist', source: me});
							self.artist.tryForAllMusicBio();
						}

					},

					tryForAllMusicBio: async function() {
						if (artistmeta.allmusic.done_bio || artistmeta.allmusic.link == null || artistmeta.allmusic.link == '') {
							return;
						}
						artistmeta.allmusic.done_bio = true;
						debug.debug(medebug,"Getting allmusic bio from",artistmeta.allmusic.link);
						try {
							fetch(
								'browser/backends/getambio.php',
								{
									signal: AbortSignal.timeout(60000),
									cache: 'no-store',
									method: 'POST',
									priority: 'low',
									body: JSON.stringify({url: artistmeta.allmusic.link})
								}
							).then(async function(response) {
								if (response.ok) {
									debug.debug(medebug,"Got Allmusic Bio", response);
									var data = await response.text();
									artistmeta.allmusic.layout.finish(artistmeta.allmusic.link, null, data);
								} else {
									debug.trace(medebug, 'Unable to find AllMusic bio', response);
									artistmeta.allmusic.layout.finish(artistmeta.allmusic.link, null, 'Could not find an Allmusic Biography');
								}
							});
						} catch (err) {
							debug.log(medebug,"Didn't Get Allmusic Bio",data);
							artistmeta.allmusic.layout.finish(null, null, 'Could not find an Allmusic Biography');
						}
					}

				}

			}();
		}

	}


}();

nowplaying.registerPlugin("allmusic", info_allmusic, "icon-allmusic", "button_infoallmusic");
