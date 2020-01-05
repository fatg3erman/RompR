var spotiMixRadio = function() {

	var trackseeds;
	var nonspotitracks;
	var tuner;
	var medebug = "SPOTIMIXRADIO";
	var param;
	var trackfinder = new faveFinder(false);
	trackfinder.setCheckDb(false);
	trackfinder.setExact(true);
	trackfinder.setPriorities(['spotify']);

	function checkNonSpotifyTracks() {
		if (nonspotitracks.length > 0) {
			var t = nonspotitracks.shift();
			debug.log(medebug, "Searching For Spotify ID for",t.Title,t.Artistname);
			trackfinder.findThisOne(
				{
					title: t.Title,
					artist: t.Artistname,
					duration: 0,
					albumartist: t.Artistname,
					date: 0
				},
				gotTrackSearchResults
			);
		} else {
			tuner.addSeedTracks(trackseeds);
			tuner.moreseedscoming = false;
		}
	}

	function gotTrackSearchResults(data) {
		debug.trace(medebug,"Got Track Results",data);
		if (data.uri) {
			var m = data.uri.match(/spotify:track:(.*)$/);
			if (m && m[1]) {
				debug.log(medebug,"Found Spotify Track Uri",m[1]);
				trackseeds.push(m[1]);
			}
		}
		checkNonSpotifyTracks();;
	}

	return {

		initialise: async function(p) {
			param = p;
			if (typeof(spotifyRecommendationsRadio) == 'undefined') {
				debug.log(medebug,"Loading Spotify Radio Tuner");
				await $.getScript('radios/code/spotifyrecommendationsradio.js?version='+rompr_version)
			}
			trackseeds = new Array();
			nonspotitracks = new Array();
			tuner = new spotifyRecommendationsRadio();
			var searchparms;
			switch (param) {
				case 'surprise':
					searchparms = [{action: 'getrecommendationseeds', days: 7, limit: 50, top: 1}];
					break;

				case '7day':
					searchparms = [{action: 'getrecommendationseeds', days: 7, limit: 30, top: 20}];
					break;

				case '1year':
					searchparms = [{action: 'getrecommendationseeds', days: 365, limit: 100, top: 50}];
					break;
			}
			try {
				var seeds = await $.ajax({
					url: "backends/sql/userRatings.php",
					type: "POST",
					contentType: false,
					data: JSON.stringify(searchparms),
					dataType: 'json'
				});
			} catch(err) {
				debug.error(medebug, 'Error getting tracks',err);
				return false;
			}
			for (var i in seeds) {
				var m = seeds[i].Uri.match(/spotify:track:(.*)$/);
				if (m && m[1]) {
					debug.trace(medebug,"Got seed",seeds[i].Uri);
					trackseeds.push(m[1]);
				} else {
					debug.trace(medebug,"Didn't match Uri",seeds[i].Uri);
					nonspotitracks.push(seeds[i]);
				}
			}
			tuner.moreseedscoming = true;
			tuner.addSeedTracks(trackseeds);
			trackseeds = [];
			checkNonSpotifyTracks();
		},

		getURIs: async function(numtracks) {
			var tracks = await tuner.getTracks(numtracks);
			var retval = new Array();
			tracks.forEach(function(uri) {
				retval.push({type: 'uri', name: uri});
			});
			return retval;
		},

		modeHtml: function(p) {
			switch (p) {
				case 'surprise':
					return '<i class="icon-spotify-circled modeimg"/></i><span class="modespan">'+language.gettext('label_spottery_lottery')+'</span>';
					break;

				case '7day':
					return '<i class="icon-spotify-circled modeimg"/></i><span class="modespan">'+language.gettext('label_spotify_mix')+'</span>';
					break;

				case '1year':
					return '<i class="icon-spotify-circled modeimg"/></i><span class="modespan">'+language.gettext('label_spotify_dj')+'</span>';
					break;

				default:
					return '<i class="icon-spotify-circled modeimg"/></i><span class="modespan">Spotify Mix</span>';
					break;

			}
		},

		stop: function() {

		}
	}

}();

playlist.radioManager.register("spotiMixRadio", spotiMixRadio, null);
