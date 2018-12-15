var spotiCrazyRadio = function() {

	var populated = false;
	var tuner;
	var tags;
	var tagarray;
	var params = {};
	var index = null;

	function populateTuner(numtracks) {
		var sods = tagarray.splice(0,5);
		var params = {seed_genres: sods.join(',')}
		tuner.populate(params, numtracks);
	}

	return {

		populate: function(p, numtracks) {
			index = p;
			if (typeof(spotifyRecommendationsRadio) == 'undefined') {
				debug.log("CRAZY RADIO","Loading Spotify Radio Tuner");
				$.getScript('radios/code/spotifyrecommendationsradio.js?version='+rompr_version,function() {
					spotiCrazyRadio.actuallyGo(numtracks)
				});
			} else {
				spotiCrazyRadio.actuallyGo(numtracks)
			}
		},

		actuallyGo: function(numtracks) {
			if (!populated) {
				crazyRadioManager.load(index);
				tags = $('[name="spotigenres"]').val();
				tagarray = tags.split(',')
				populated = true;
				$('.spotiradioslider').each(function() {
					var attribute = $(this).attr('name');
					var range = $(this).rangechooser("getRange");
					params['max_'+attribute] = range.max.toFixed(2);
					params['min_'+attribute] = range.min.toFixed(2);
				});
				tuner = new spotifyRecommendationsRadio();
				if (tagarray.length > 0) {
					populateTuner(5);
				} else {
	        		infobar.notify(infobar.ERROR,"Please Choose Some Genres");
	        		playlist.radioManager.stop(null);
				}
			} else {
				if (tagarray.length > 0) {
					populateTuner(numtracks);
				} else {
					tuner.sendTracks(numtracks);
				}
			}
		},

		modeHtml: function(p) {
			if (tags) {
				return '<i class="icon-spotify-circled modeimg"/></i><span class="modespan">'+tags+'</span>';
			} else {
				return false;
			}
		},

		stop: function() {
			populated = false;
			params = {};
		}

	}

}();

playlist.radioManager.register("spotiCrazyRadio",spotiCrazyRadio,null);
