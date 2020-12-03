var soundcloud = function() {

	var self = this;

	return {
		getTrackInfo: function(mopidyURI, callback) {
			// "soundcloud:song/King Tubby meets Soul Rebel Uptown.92868852"
			debug.trace("SOUNDCLOUD","Trying to get track info from",mopidyURI);
			var a = mopidyURI.match(/\.(\d+)$/);
			var tracknum = a[1];
			debug.debug("SOUNDCLOUD","Getting soundcloud info for track",tracknum);
			$.ajax({
				method: 'POST',
				dataType: 'json',
				url: 'browser/backends/api_handler.php',
				data: JSON.stringify({
					module: 'soundcloud',
					method: 'track_info',
					params: {
						trackid: tracknum
					}
				})
			})
			.done(callback)
			.fail(function(xhr,status,err) {
				debug.warn("SOUNDCLOUD","SoundCloud Error",xhr);
				callback(xhr.responseJSON);
			});
		},

		getUserInfo: function(userid, callback) {
			debug.trace("SOUNDCLOUD","Getting soundcloud info for user",userid);
			$.ajax({
				method: 'POST',
				dataType: 'json',
				url: 'browser/backends/api_handler.php',
				data: JSON.stringify({
					module: 'soundcloud',
					method: 'user_info',
					params: {
						userid: userid
					}
				})
			})
			.done(callback)
			.fail(function(xhr,status,err) {
				debug.warn("SOUNDCLOUD","SoundCloud Error",xhr);
				callback(xhr.responseJSON);
			});
		}
	}
}();
