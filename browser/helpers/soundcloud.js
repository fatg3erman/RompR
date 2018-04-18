var soundcloud = function() {

	var self = this;

	return {
		getTrackInfo: function(mopidyURI, callback) {
			// "soundcloud:song/King Tubby meets Soul Rebel Uptown.92868852"
			debug.log("SOUNDCLOUD","Trying to get track info from",mopidyURI);
			var a = mopidyURI.match(/(\d+)$/);
			var tracknum = a[1];
			debug.log("SOUNDCLOUD","Getting soundcloud info for track",tracknum);
			$.ajax({
				method: 'GET',
				dataType: 'json',
				url: 'browser/backends/getscdata.php',
				data: {url: 'tracks/'+tracknum+'.json'},
				success: callback,
				error: function(data) { debug.warn("SOUNDCLOUD","SoundCloud Error",data);
										callback(data);
				}
			});
		},

		getUserInfo: function(userid, callback) {
			debug.log("SOUNDCLOUD","Getting soundcloud info for user",userid);
			$.ajax({
				method: 'GET',
				dataType: 'json',
				url: 'browser/backends/getscdata.php',
				data: {url: 'users/'+userid+'.json'},
				success: callback,
				error: function(data) { debug.warn("SOUNDCLOUD","SoundCloud Error",data);
										callback(data);
				}
			});
		}
	}
}();