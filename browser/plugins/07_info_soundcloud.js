var info_soundcloud = function() {

	var me = "soundcloud";
	var tempcanvas = document.createElement('canvas');
	var scImg = new Image();

	function getTrackHTML(layout, data, trackmeta) {

		if (data.error) {
			layout.display_error(data.error);
			layout.finish(null, null);
			return;
		}
		debug.debug("SOUNDCLOUD PLUGIN","Creating track HTML from",data);

		if (data.artwork_url)
			layout.add_main_image(data.artwork_url);

		var list = layout.add_sidebar_list(language.gettext("soundcloud_trackinfo"));
		layout.append_to_list(list, language.gettext("soundcloud_plays"), formatSCMessyBits(data.playback_count));
		layout.append_to_list(list, language.gettext("soundcloud_downloads"), formatSCMessyBits(data.download_count));
		layout.append_to_list(list, language.gettext("soundcloud_faves"), formatSCMessyBits(data.favoritings_count));
		layout.append_to_list(list, language.gettext("soundcloud_state"), formatSCMessyBits(data.state));
		layout.append_to_list(list, language.gettext("info_genre"), formatSCMessyBits(data.genre));
		layout.append_to_list(list, language.gettext("info_label"), formatSCMessyBits(data.label_name));
		layout.append_to_list(list, language.gettext("soundcloud_license"), formatSCMessyBits(data.license));
		list.append($('<li>').append($('<a>', {href: data.permalink_url, target: '_blank'}).html(language.gettext("soundcloud_view"))));
		if (data.purchase_url)
			list.append($('<li>').append($('<a>', {href: data.purchase_url, target: '_blank'}).html(language.gettext("soundcloud_buy"))));

		var d = formatSCMessyBits(data.description);
		d = d.replace(/\n/g, "</p><p>");
		layout.add_profile(d);

		trackmeta.progress = layout.add_non_flow_box();
		trackmeta.progress.addClass('bordered').css({position: 'relative', 'margin-top': '1em', 'margin-right': '0px'});
		trackmeta.progbar = $('<div>', {class: 'scprog'}).appendTo(trackmeta.progress);
		trackmeta.waveform = $('<img>', {class: 'gosblin'}).appendTo (trackmeta.progress);

	}

	function getArtistHTML(layout, data) {

		if (data.error) {
			layout.display_error(data.error);
			layout.finish(null, null);
			return;
		}

		debug.debug("SOUNDCLOUD PLUGIN","Creating artist HTML from",data);

		if (data.avatar_url)
			layout.add_main_image(data.avatar_url);

		var list = layout.add_sidebar_list(language.gettext("soundcloud_user"));
		layout.append_to_list(list, language.gettext("soundcloud_fullname"), formatSCMessyBits(data.full_name));
		layout.append_to_list(list, language.gettext("soundcloud_Country"), formatSCMessyBits(data.country));
		layout.append_to_list(list, language.gettext("soundcloud_city"), formatSCMessyBits(data.city));

		if (data.website)
			list.append($('<li>').append($('<a>', {href: data.website, target: '_blank'}).html(language.gettext("soundcloud_website"))));

		var f = formatSCMessyBits(data.description)
		f = f.replace(/\n/g, "</p><p>");
		layout.add_profile(f);

	}

	function formatSCMessyBits(bits) {
		try {
			if (bits) {
				if (typeof bits == 'number') {
					return bits;
				}
				return bits.fixDodgyLinks();
			} else {
				return "";
			}
		} catch(err) {
			return "";
		}
	}

	return {
		getRequirements: function(parent) {
			return [];
		},

		collection: function(parent, artistmeta, albummeta, trackmeta) {
			debug.debug("SOUNDCLOUD PLUGIN", "Creating data collection");

			var self = this;
			var wi = 0;

			this.populate = function() {

				parent.updateData({
					soundcloud: { }
				}, artistmeta);

				parent.updateData({
					soundcloud: { layout: new info_layout_empty() }
				}, albummeta);

				parent.updateData({
					soundcloud: { }
				}, trackmeta);

				if (typeof trackmeta.soundcloud.layout == 'undefined')
					self.track.populate();

				if (typeof artistmeta.soundcloud.layout == 'undefined')
					self.artist.populate();
			}

			this.progressUpdate = function(percent) {
				self.track.updateProgress(percent);
			}

			this.handleClick = function() {

			}

			this.artist = function() {

				var retries = 5;

				return {

					populate: async function() {
						artistmeta.soundcloud.layout = new info_sidebar_layout({title: artistmeta.name, type: 'artist', source: me})
						while (retries > 0 && typeof artistmeta.soundcloud.id == 'undefined') {
							await new Promise(t => setTimeout(t, 250));
							retries--;
						}
						// Slightly hacky, but if we have an 'artist switch' in a SoundCloud track that makes no sense
						// - and as the tarck will already be populated the artist id never gets updated.
						if (artistmeta.soundcloud.id === null || typeof artistmeta.soundcloud.id == 'undefined') {
							artistmeta.soundcloud.artist = {error: language.gettext("soundcloud_not")};
							self.artist.doBrowserUpdate();
						} else {
							debug.debug("SOUNDCLOUD PLUGIN","Artist is populating");
							soundcloud.getUserInfo(artistmeta.soundcloud.id, self.artist.scResponseHandler);
						}
					},

					scResponseHandler: function(data) {
						artistmeta.soundcloud.artist = data;
						self.artist.doBrowserUpdate();
					},

					doBrowserUpdate: function() {
						getArtistHTML(artistmeta.soundcloud.layout, artistmeta.soundcloud.artist);
						artistmeta.soundcloud.layout.finish(artistmeta.soundcloud.artist.permalink_url, artistmeta.soundcloud.artist.username);
					}
				}
			}();

			// I do not have a pet alligator

			this.track = function() {

				return {

					populate: function() {
						trackmeta.soundcloud.layout = new info_sidebar_layout({title: trackmeta.name, type: 'track', source: me})
						var t = parent.playlistinfo.file;
						if (t.substring(0,11) == 'soundcloud:') {
							soundcloud.getTrackInfo(parent.playlistinfo.file, self.track.scResponseHandler);
						} else if (t.match(/api\.soundcloud\.com\/tracks\/(\d+)\//)) {
							var sc = t.match(/api\.soundcloud\.com\/tracks\/(\d+)\//);
							soundcloud.getTrackInfo(sc[1], self.track.scResponseHandler);
						} else if (t.match(/feeds\.soundcloud\.com\/stream\/(\d+)/)) {
							var sc = t.match(/feeds\.soundcloud\.com\/stream\/(\d+)/);
							soundcloud.getTrackInfo(sc[1], self.track.scResponseHandler);
						} else {
							trackmeta.soundcloud.track = {error: language.gettext("soundcloud_not")};
							artistmeta.soundcloud.id = null;
							self.track.doBrowserUpdate();
						}
					},

				   scResponseHandler: function(data) {
						debug.debug("SOUNDCLOUD PLUGIN","Got SoundCloud Track Data:",data);
						trackmeta.soundcloud.track = data;
						artistmeta.soundcloud.id = data.user_id;
						self.track.doBrowserUpdate();
					},

					doBrowserUpdate: function() {
						getTrackHTML(trackmeta.soundcloud.layout, trackmeta.soundcloud.track, trackmeta.soundcloud);
						trackmeta.soundcloud.layout.finish(trackmeta.soundcloud.track.permalink_url, trackmeta.name);
						if (trackmeta.soundcloud.progress) {
							debug.debug("SOUNDCLOUD PLUGIN","Getting Track Waveform",formatSCMessyBits(trackmeta.soundcloud.track.waveform_url));
							scImg.onload = self.track.doSCImageStuff;
							scImg.src = "getRemoteImage.php?url="+rawurlencode(formatSCMessyBits(trackmeta.soundcloud.track.waveform_url));
						}
					},

					doSCImageStuff: function() {
						// The soundcloud waveform is a png where the waveform itself is transparent
						// and has a grey-ish border. We want an image with a gradient for the waveform
						// and a transparent border.
						tempcanvas.width = scImg.width;
						tempcanvas.height = scImg.height;
						var ctx = tempcanvas.getContext("2d");
						ctx.clearRect(0,0,tempcanvas.width,tempcanvas.height);

						// Fill tempcanvas with a linear gradient
						var gradient = ctx.createLinearGradient(0,0,0,tempcanvas.height);
						gradient.addColorStop(0,'rgba(255,82,0,1)');
						gradient.addColorStop(0.6,'rgba(150, 48, 0, 1)');
						gradient.addColorStop(1,'rgba(100, 25, 0, 0.1)');
						ctx.fillStyle = gradient;
						ctx.fillRect(0,0,tempcanvas.width,tempcanvas.height);

						// Plop the image over the top.
						ctx.drawImage(scImg,0,0,tempcanvas.width,tempcanvas.height);

						// Now translate all the grey pixels into transparent ones
						var pixels = ctx.getImageData(0,0,tempcanvas.width,tempcanvas.height);
						var data = pixels.data;
						for (var i = 0; i<data.length; i += 4) {
							if (data[i] == data[i+1] && data[i+1] == data[i+2]) {
								data[i+3] = 0;
							}
						}
						ctx.clearRect(0,0,tempcanvas.width,tempcanvas.height);
						ctx.putImageData(pixels,0,0);
						trackmeta.soundcloud.waveform.attr("src", tempcanvas.toDataURL());
					},

					updateProgress: function(percent) {
						if (trackmeta.soundcloud.progress) {
							var w = Math.round(trackmeta.soundcloud.progress.width()*percent/100);
							if (percent == 0) {
								var h = 0;
							} else {
								var h = trackmeta.soundcloud.progress.height() - 8;
							}
							trackmeta.soundcloud.progbar.css({left: w+"px", height: h+"px"});
						}
					}
				}
			}();
		}
	}
}();

nowplaying.registerPlugin("soundcloud", info_soundcloud, "icon-soundcloud-circled", "button_soundcloud");
