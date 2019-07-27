var info_soundcloud = function() {

	var me = "soundcloud";
	var tempcanvas = document.createElement('canvas');
	var scImg = new Image();

	function getTrackHTML(data) {

        debug.trace("SOUNDCLOUD PLUGIN","Creating track HTML from",data);
        var html = '<div class="containerbox info-detail-layout">';
        html += '<div class="info-box-fixed info-border-right info-box-list">';

        if (data.artwork_url) {
            html +=  '<img src="' + data.artwork_url + '" class="clrboth" style="margin:8px" />';
        }
        html += '<ul><li><h3>'+language.gettext("soundcloud_trackinfo")+':</h3></li>';
        html += '<li><b>'+language.gettext("soundcloud_plays")+':</b> '+formatSCMessyBits(data.playback_count)+'</li>';
        html += '<li><b>'+language.gettext("soundcloud_downloads")+':</b> '+formatSCMessyBits(data.download_count)+'</li>';
        html += '<li><b>'+language.gettext("soundcloud_faves")+':</b> '+formatSCMessyBits(data.favoritings_count)+'</li>';
        html += '<li><b>'+language.gettext("soundcloud_state")+'</b> '+formatSCMessyBits(data.state)+'</li>';
        html += '<li><b>'+language.gettext("info_genre")+'</b> '+formatSCMessyBits(data.genre)+'</li>';
        html += '<li><b>'+language.gettext("info_label")+'</b> '+formatSCMessyBits(data.label_name)+'</li>';
        html += '<li><b>'+language.gettext("soundcloud_license")+':</b> '+formatSCMessyBits(data.license)+'</li>';
        if (data.purchase_url) {
            html += '<li><b><a href="' + data.purchase_url + '" target="_blank">'+language.gettext("soundcloud_buy")+'</a></b></li>';
        }
        html += '<li><a href="' + data.permalink_url + '" title="View In New Tab" target="_blank"><b>'+language.gettext("soundcloud_view")+'</b></a></li>';
        html += '</ul>';
        html += '</div>';

        html += '<div class="info-box-expand stumpy">';
		html += '<div id="similarartists" class="bordered" style="position:relative">'+
                    '<div id="scprog"></div>'+
                    '<img id="gosblin" />'+
                    '</div>';
        var d = formatSCMessyBits(data.description);
        d = d.replace(/\n/g, "</p><p>");
        html += '<p>'+d+'</p>';
        html += '</div>';
        html += '</div>';
        return html;

	}

	function getArtistHTML(data) {
        debug.trace("SOUNDCLOUD PLUGIN","Creating artist HTML from",data);
        var html = '<div class="containerbox info-detail-layout">';
        html += '<div class="info-box-fixed info-border-right info-box-list">';

        if (data.avatar_url) {
            html +=  '<img src="' + data.avatar_url + '" class="clrboth" style="margin:8px" />';
        }
        html += '<ul><li><h3>'+language.gettext("soundcloud_user")+':</h3></li>';
        html += '<li><b>'+language.gettext("soundcloud_fullname")+':</b> '+formatSCMessyBits(data.full_name)+'</li>';
        html += '<li><b>'+language.gettext("soundcloud_Country")+':</b> '+formatSCMessyBits(data.country)+'</li>';
        html += '<li><b>'+language.gettext("soundcloud_city")+':</b> '+formatSCMessyBits(data.city)+'</li>';
        if (data.website) {
            html += '<li><b><a href="' + data.website + '" target="_blank">'+language.gettext("soundcloud_website")+'</a></b></li>';
        }
        html += '</ul>';
        html += '</div>';
        html += '<div class="info-box-expand stumpy">';
        var f = formatSCMessyBits(data.description)
        f = f.replace(/\n/g, "</p><p>");
        html += '<p>'+ f +'</p>';
        html += '</div>';
        html += '</div>';
		return html;
	}

    function formatSCMessyBits(bits) {
        try {
            if (bits) {
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
			debug.log("SOUNDCLOUD PLUGIN", "Creating data collection");

			var self = this;
			var wi = 0;
			var displaying = false;

            this.populate = function() {
				self.track.populate();
            }

			this.displayData = function(waitingon) {
				displaying = true;
				self.artist.doBrowserUpdate();
				self.album.doBrowserUpdate();
				self.track.doBrowserUpdate();
			}

			this.stopDisplaying = function(waitingon) {
				displaying = false;
			}

			this.progressUpdate = function(percent) {
				self.track.updateProgress(percent);
			}

			this.artist = function() {

				return {

					populate: function() {
						if (trackmeta.soundcloud.track.error) {
			                browser.Update(null, 'artist', me, parent.nowplayingindex, { name: "",
							                    					link: "",
							                    					data: null
						                						}
							);
						} else {
		            		if (artistmeta.soundcloud.artist === undefined) {
		            			debug.log("SOUNDCLOUD PLUGIN","Artist is populating");
			                	soundcloud.getUserInfo(artistmeta.soundcloud.id, self.artist.scResponseHandler);
			                }
						}
					},

					scResponseHandler: function(data) {
						artistmeta.soundcloud.artist = data;
						self.artist.doBrowserUpdate();
					},

					doBrowserUpdate: function() {
						if (displaying && trackmeta.soundcloud.track !== undefined) {
							if (trackmeta.soundcloud.track.error) {
				                browser.Update(null, 'artist', me, parent.nowplayingindex, { name: "",
								                    					link: "",
								                    					data: null
							                						}
								);
							} else if (artistmeta.soundcloud !== undefined &&
										artistmeta.soundcloud.artist !== undefined) {
								if (artistmeta.soundcloud.artist.error) {
									browser.Update(null, 'artist', me, parent.nowplayingindex, {name: artistmeta.name,
																				link: "",
																				data: '<h3 align="center">'+artistmeta.soundcloud.artist.error+'</h3>'
																			}
									);
								} else {
									var accepted = browser.Update(null, 'artist', me, parent.nowplayingindex, {	name: artistmeta.soundcloud.artist.username,
																				link: artistmeta.soundcloud.artist.permalink_url,
																				data: getArtistHTML(artistmeta.soundcloud.artist)
																			}
									);
								}
							}
						}

					}
				}
			}();

			this.album = function() {

				return {

					doBrowserUpdate: function() {
						if (displaying) {
			                browser.Update(null, 'album', me, parent.nowplayingindex, { name: "",
							                    					link: "",
							                    					data: null
						                						}
							);
						}
					}
				}
			}();

			// I do not have a pet alligator

			this.track = function() {

				return {

		            populate: function() {
		            	if (artistmeta.soundcloud === undefined) {
		            		artistmeta.soundcloud = {};
		            	}
		            	if (trackmeta.soundcloud === undefined) {
		            		trackmeta.soundcloud = {};
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
			            		self.artist.populate();
			            		self.track.doBrowserUpdate();
			                }
			            } else {
			            	self.artist.populate();
			            }
		            },

	               scResponseHandler: function(data) {
		                debug.log("SOUNDCLOUD PLUGIN","Got SoundCloud Track Data:",data);
		                trackmeta.soundcloud.track = data;
		                artistmeta.soundcloud.id = data.user_id;
		                self.artist.populate();
		                self.track.doBrowserUpdate();
		            },

		            doBrowserUpdate: function() {
						if (displaying  && trackmeta.soundcloud.track !== undefined) {
							debug.log("SOUNDCLOUD PLUGIN","Track was asked to display");
							if (trackmeta.soundcloud.track.error) {
								browser.Update(null, 'track', me, parent.nowplayingindex, {	name: trackmeta.name,
																		link: "",
																		data: '<h3 align="center">'+trackmeta.soundcloud.track.error+'</h3>'
																		}
								);
							} else {
								var accepted = browser.Update(null, 'track', me, parent.nowplayingindex, {	name: trackmeta.name,
																		link: trackmeta.soundcloud.track.permalink_url,
																		data: getTrackHTML(trackmeta.soundcloud.track)
																		}
								);
								if (accepted) {
									debug.log("SOUNDCLOUD PLUGIN","Getting Track Waveform",formatSCMessyBits(trackmeta.soundcloud.track.waveform_url));
							        scImg.onload = self.track.doSCImageStuff;
							        scImg.src = "getRemoteImage.php?url="+formatSCMessyBits(trackmeta.soundcloud.track.waveform_url);
								}
							}
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
			            $("#gosblin").attr("src", tempcanvas.toDataURL());
				    },

					updateProgress: function(percent) {
						if (displaying) {
					        var w = Math.round($("#similarartists").width()*percent/100);
							if (percent == 0) {
								var h = 0;
							} else {
								var h = $("#similarartists").height() - 8;
							}
					        $("#scprog").css({left: w+"px", height: h+"px"});
						}
					}
				}
			}();
		}
	}
}();

nowplaying.registerPlugin("soundcloud", info_soundcloud, "icon-soundcloud-circled", "button_soundcloud");
