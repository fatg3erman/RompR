var info_bandcamp = function() {

	var medebug = "BANDCAMP PLUGIN";
	var me = "bandcamp";
	var maxwidth = 300;

	return {

		getRequirements: function(parent) {
			return [];
		},

		collection: function(parent, artistmeta, albummeta, trackmeta) {

			debug.debug(medebug, "Creating data collection");

			var self = this;

			function getArtistHTML(data, layout, artistmeta, artistobj) {
				debug.debug(medebug,"Making Artist Info From",data);
				if (data.error) {
					layout.display_error(data.error);
					layout.finish(null, null);
					return;
				}

				if (data.image) {
					layout.add_main_image(data.image);
				}

				if (data.bio) {
					layout.add_profile(data.bio)
				}

				if (data.location) {
					layout.add_sidebar_list(language.gettext("label_location"), data.location);
				}

				if (data.genre) {
					layout.add_sidebar_list(language.gettext("label_genre"), data.genre);
				}

				if (data.tags && data.tags.length > 0) {
					var list = layout.add_sidebar_list(language.gettext("musicbrainz_tags"));
					for (var t of data.tags) {
						list.append($('<li>').html(t));
					}
				}

				let b = layout.add_non_flow_box();
				let h = $('<div>', {class: 'containerbox textunderline'}).appendTo(b);
				h.append($('<div>', {class: 'fixed bleft'}).html(language.gettext('label_albumsby')));

				if (typeof artistmeta.bandcamp.widget == "undefined") {
					artistmeta.bandcamp.widget = layout.add_non_flow_box();
					artistmeta.bandcamp.widget.addClass('fullwidth medium_masonry_holder');
					artistmeta.bandcamp.widget.spotifyAlbumThing({
						classes: 'nobwobbler nobalbum spotify_album_masonry selecotron',
						itemselector: 'nobwobbler',
						sub: null,
						showbiogs: false,
						layoutcallback: browser.rePoint,
						maxwidth: maxwidth,
						is_plugin: false,
						imageclass: 'spotify_album_image',
						data: data.albums
					});
				}

				layout.finish(data.external_urls.bandcamp, data.name);

			}

			this.populate = function() {
				parent.updateData({
					bandcamp: {
					},
				}, artistmeta);

				parent.updateData({
					bandcamp: { }
				}, trackmeta);

				parent.updateData({
					bandcamp: { }
				}, albummeta);

				if (typeof trackmeta.bandcamp.layout == 'undefined')
					trackmeta.bandcamp.layout = new info_layout_empty();

				if (typeof albummeta.bandcamp.layout == 'undefined')
					albummeta.bandcamp.layout = new info_layout_empty();

				if (typeof artistmeta.bandcamp.layout == 'undefined') {
					artistmeta.bandcamp.layout = new info_sidebar_layout({title: artistmeta.name, type: 'artist', source: me});
					self.artist.populate();
				}

			}

			this.handleClick = function(source, element, event) {
				debug.debug(medebug,parent.nowplayingindex,source,"is handling a click event");
				if (element.hasClass('clickzoomimage')) {
					imagePopup.create(element, event, element.attr("src"));
				} else if (element.hasClass('clickspotifywidget')) {
					artistmeta.bandcamp.widget.spotifyAlbumThing('handleClick', element);
				}
			}

			this.artist = function() {

				function bandcampResponse(data) {
					debug.debug(medebug, "Got Bandcamp Artist Data", data);
					artistmeta.bandcamp.artist = data;
					self.artist.doBrowserUpdate();
				}

				return {

					populate: function() {
						bandcamp.get_artist(artistmeta.name, bandcampResponse, self.artist.bandcampError)
					},

					bandcampError: function(data) {
						debug.error(medebug, "Bandcamp Error!",data);
						data.external_urls = {bandcamp: ''};
						data.name = artistmeta.name;
						artistmeta.bandcamp.artist = data;
						self.artist.doBrowserUpdate();
					},

					doBrowserUpdate: function() {
						getArtistHTML(artistmeta.bandcamp.artist, artistmeta.bandcamp.layout, artistmeta, self.artist);
					}

				}

			}();

		}

	}

}();

nowplaying.registerPlugin("bandcamp", info_bandcamp, "icon-bandcamp-circled", "button_infobandcamp");
