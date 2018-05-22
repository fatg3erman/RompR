var helpfulThings = function() {

    // It'd be nice to base some recommendations on all time favourite artists. These should come lower down as they're likely to be always the same.

	var hpl = null;
	var medebug = "SPANKY";
	var trackseeds;
	var nonspotitracks;
    var maxwidth = 640;
    var doneonce = false;
    var current_seed = null;

	function getRecommendationSeeds() {
		debug.log(medebug, "Getting Seeds For Recommendations");
		metaHandlers.genericAction([{action: 'getrecommendationseeds', days: 30, limit: 20, top: 15}],
    		gotRecommendationSeeds,
        	function() {
        		debug.error(medebug,"Error Getting Seeds");
        	}
        );
	}

	function gotRecommendationSeeds(data) {
		debug.log(medebug, "Got Seeds For Recommendations",data);
		if (doneonce) {
			$('.helpfulholder').spotifyAlbumThing('destroy');
			doneonce = false;
		}
		trackseeds = new Array();
		nonspotitracks = new Array();
		for (var i in data) {
			if (data[i].Uri) {
				var m = data[i].Uri.match(/spotify:track:(.*)$/);
				if (m && m[1]) {
					data[i].id = m[1];
					trackseeds.push(data[i]);
				} else {
					debug.log(medebug,"Didn't match Uri",data[i].Uri);
					nonspotitracks.push(data[i]);
				}
			}
		}
		helpfulThings.doStageTwo();
	}

	return {

		open: function() {

        	if (hpl == null) {
	        	hpl = browser.registerExtraPlugin("hpl", language.gettext("button_infoyou"), helpfulThings, 'https://fatg3erman.github.io/RompR/Music-Discovery');

			    $('#hplfoldup').append('<div id="helpful_radio" class="containerbox wrap mixcontainer"></div>');

			    if (player.canPlay('spotify') && lastfm.isLoggedIn()) {
			    	var html = '<div class="fixed infosection containerbox mixbox clickable infoclick plugclickable clickmixradio">';
			    	html += '<img class="smallcover fixed" src="newimages/lastfm-icon.png" />';
			    	html +=	'<div class="expand alignmid mixinfo"><b>'+language.gettext("label_dailymix")+'</b><br/>';
			    	html += "A playlist just for you, a mix of tracks you know and new music you might love. Powered by Last.FM and Spotify</div>";
			    	html += '</div>';

			    	html += '<div class="fixed infosection containerbox mixbox clickable infoclick plugclickable clickartradio">';
			    	html += '<img class="smallcover fixed" src="newimages/lastfm-icon.png" />';
			    	html +=	'<div class="expand alignmid mixinfo"><b>'+language.gettext("label_luckydip")+'</b><br/>';
			    	html += "A radio station just for you, playing a wider range of music by artists you know and artists you don't yet love. Powered by Last.FM and Spotify</div>";
			    	html += '</div>';
			    } else if (player.canPlay('spotify') && !lastfm.isLoggedIn()) {
			    	var html = '<div class="fixed infosection containerbox mixbox clickable infoclick plugclickable clickmixradio">';
			    	html += '<img class="smallcover fixed" src="newimages/lastfm-icon.png" />';
			    	html +=	'<div class="expand alignmid mixinfo"><b>'+language.gettext("label_startshere")+'</b><br/>';
			    	html += "Log in to Last.FM and start scrobbling. Rompr can then delight you with new music you're going to love!</div>";
			    	html += '</div>';
			    } else if (!player.canPlay('spotify')) {
			    	var html = '<div class="fixed infosection containerbox mixbox clickable infoclick plugclickable clickmixradio">';
			    	html += '<img class="smallcover fixed" src="newimages/spotify-icon.png" />';
			    	html +=	'<div class="expand alignmid mixinfo"><b>'+language.gettext("label_getspotify")+'</b><br/>';
			    	html += "Use Mopidy, a Spotify Premium subscription, and start scrobbbling to Last.FM so RompЯ can delight you with new music you're going to love!</div>";
			    	html += '</div>';
			    }

			    if (player.canPlay('spotify')) {
			    	html += '<div class="fixed infosection containerbox mixbox clickable infoclick plugclickable clickspotmixradio">';
			    	html += '<img class="smallcover fixed" src="newimages/spotify-icon.png" />';
			    	html +=	'<div class="expand alignmid mixinfo"><b>'+language.gettext('label_spotify_mix')+'</b><br/>';
			    	html += "A radio station of suggestions based on your recent listening. Powered by RompЯ and Spotify</div>";
			    	html += '</div>';

			    	html += '<div class="fixed infosection containerbox mixbox clickable infoclick plugclickable clickspotdjradio">';
			    	html += '<img class="smallcover fixed" src="newimages/spotify-icon.png" />';
			    	html +=	'<div class="expand alignmid mixinfo"><b>'+language.gettext('label_spotify_dj')+'</b><br/>';
			    	html += "Scanning the vastness of Spotify. Powered by RompЯ and Spotify</div>";
			    	html += '</div>';
		        }

			    $('#helpful_radio').append(html);

			    if (player.canPlay('spotify')) {
					$('#hplfoldup').append('<div id="helpful_spinner"><i class="svg-square icon-spin6 spinner"></i></div>');
		            getRecommendationSeeds();
		        }

				hpl.slideToggle('fast');
	        	browser.goToPlugin("hpl");
	        	browser.rePoint();

	        } else {
	        	browser.goToPlugin("hpl");
	        }

		},

		handleClick: function(element, event) {
			if (element.hasClass('clickmixradio')) {
				playlist.radioManager.stop();
				playlist.radioManager.load('lastFMTrackRadio', '1month');
			} else if (element.hasClass('clickartradio')) {
				playlist.radioManager.stop();
				playlist.radioManager.load('lastFMArtistRadio', '6month');
			} else if (element.hasClass('clickspotmixradio')) {
				playlist.radioManager.stop();
				playlist.radioManager.load('spotiMixRadio', '7day');
			} else if (element.hasClass('clickspotdjradio')) {
				playlist.radioManager.stop();
				playlist.radioManager.load('spotiMixRadio', '1year');
			} else if (element.hasClass('clickrefreshalbums')) {
				getRecommendationSeeds();
            } else if (element.hasClass('clickopenalbum')) {
            	var e = element;
            	while (!e.hasClass('helpfulholder')) {
            		e = e.parent();
            	}
            	e.spotifyAlbumThing('handleClick', element);
        	}
		},

		close: function() {
            nowplaying.notifyTrackChanges('helpfulthings', null);
			if (doneonce) {
				$('.helpfulholder').each(function() {
					debug.log(medebug,"Removing And Destroying",$(this).attr("id"));
					$(this).prev().remove();
					$(this).remove();
				});
			}
			doneonce = false;
			hpl = null;
		},

		doStageTwo: function() {
			if (nonspotitracks.length > 0) {
				var t = nonspotitracks[0];
				debug.log(medebug, "Searching For Spotify ID for",t);
				player.controller.rawsearch(
					{artist: [t.Artistname],
					 title: [t.Title]
					},
					['spotify'],
					true,
					helpfulThings.gotTrackResults,
					false
				);
			} else if (trackseeds.length > 0) {
				helpfulThings.getMoreStuff();
			} else {
				$('#helpful_spinner').remove();
				$('#hplfoldup').append('<div class="textunderline containerbox menuitem" style="padding-left:12px;margin-top:1em"><h3 class="fixed">'
					+'Once RompЯ has gathered some data, it will show recommendations here. Play some music!</h3></div>');
			}

		},

		gotTrackResults: function(data) {
			debug.log(medebug,"Got Track Results",data);
			var t = nonspotitracks.shift();
			if (data && data[0] && data[0].tracks && data[0].tracks[0]) {
				var uri = data[0].tracks[0].uri;
				if (uri) {
					var m = uri.match(/spotify:track:(.*)$/);
					if (m && m[1]) {
						debug.log(medebug,"Found Spotify Track Uri",m[1]);
						t.id = m[1];
						trackseeds.push(t);
					}
				}
			}
			helpfulThings.doStageTwo();
		},

		getMoreStuff: function() {
			if (trackseeds.length > 0) {
				current_seed = trackseeds.shift();
				var params = { limit: 8 }
				if (prefs.lastfm_country_code) {
					params.market = prefs.lastfm_country_code;
				}
				params.seed_tracks = current_seed.id;
				spotify.recommendations.getRecommendations(params, helpfulThings.gotTrackRecommendations, helpfulThings.spotiError);
			} else {
				$('#helpful_spinner').remove();
				setDraggable('.helpfulholder');
				browser.rePoint();
			}
		},

		gotTrackRecommendations: function(data) {
			debug.log(medebug, "Got Track Recommendations for", current_seed.Artistname, current_seed.id, data);
			if (data.tracks.length == 0) {
				helpfulThings.getMoreStuff();
				return true;
			}
			if (current_seed.playtotal == 0) {
				$('#helpful_spinner').before('<div class="textunderline containerbox menuitem" style="padding-left:12px;margin-top:1em"><h3 class="fixed">'
				+language.gettext('because_listened',[current_seed.Artistname])+'</h3></div>');
			} else {
				$('#helpful_spinner').before('<div class="textunderline containerbox menuitem" style="padding-left:12px;margin-top:1em"><h3 class="fixed">'
				+language.gettext('because_liked',[current_seed.Artistname])+'</h3></div>');
			}
			var holder = $('<div>', {id: 'rec_'+current_seed.id, class: 'holdingcell masonified2 helpfulholder noselection'}).insertBefore($('#helpful_spinner'));

			// Need to make sure all the album IDs are unique, since we do get duplicates

			holder.spotifyAlbumThing({
				classes: 'bumfinger tagholder2 selecotron',
				itemselector: 'bumfinger',
				sub: 'album',
				showbiogs: true,
				layoutcallback: function() { doneonce = true; helpfulThings.getMoreStuff() },
				maxwidth: maxwidth,
				is_plugin: true,
				imageclass: 'jalopy',
				data: data.tracks
			});
		}

	}
}();

pluginManager.setAction(language.gettext("button_infoyou"), helpfulThings.open);
helpfulThings.open();
