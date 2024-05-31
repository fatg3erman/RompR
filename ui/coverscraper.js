function coverScraper(size, useLocalStorage, sendUpdates, enabled) {

	var self = this;
	var timer_running = false;
	var formObjects = [];
	var numAlbums = 0;
	var albums_without_cover = 0;
	var infotext = $('#infotext');
	var statusobj = $('#status');
	var imgparams = null;
	var covertimer = null;
	var scrolling = false;
	var ignorelocal = false;

	// Pass the img name to this function
	this.GetNewAlbumArt = function(params) {
		if (enabled) {
			formObjects.push(params);
			numAlbums = (formObjects.length)-1;
			if (timer_running == false) {
				doNextImage(1);
			}
		} else {
			imgparams = self.getImageSearchParams(params);
			setDefaultImage(imgparams);
		}
	}

	this.toggle = function(o) {
		enabled = o;
	}

	this.reset = function(awc) {
		numAlbums = 0;
		if (awc > -1) {
			albums_without_cover = awc;
		}
		formObjects = [];
		timer_running = false;
		self.updateInfo(0);
		if (typeof aADownloadFinished == 'function') {
			aADownloadFinished();
		}
	}

	this.updateInfo = function(n) {
		if (sendUpdates) {
			albums_without_cover = albums_without_cover - n;
			infotext.html(albums_without_cover+" "+language.gettext("albumart_nocovercount"));
		}
	}

	this.toggleScrolling = function(s) {
		scrolling = s;
	}

	this.toggleLocal = function(s) {
		ignorelocal = s;
	}

	this.getImageSearchParams = function(imgobj) {
		if (imgobj.hasOwnProperty('cb')) {
			// pre-populated data from the playlist
			return imgobj;
		} else {
			var key = imgobj.attr('name');
			var artist =  imgobj.parent().find('input[name="artist"]').val();
			var album =  imgobj.parent().find('input[name="album"]').val();
			if (artist) {
				return {key: '', artist: decodeURIComponent(artist), album: decodeURIComponent(album), imgkey: imgobj.attr('name')};
			} else {
				return {key: key, artist: '', album: '', imgkey: imgobj.attr('name')};
			}
		}
	}

	this.getImageFormParams = function(imgobj) {
		var p = self.getImageSearchParams(imgobj);
		var formData = new FormData();
		$.each(p, function(i, v) {
			if (i != 'cb') {
				// 'cb' parameter is sent by the play queue, it's a callback
				// function so we don't want to use it in our formdata
				formData.append(i, v);
			}
		});
		return formData;
	}

	// Is there something else I could be doing?

	function doNextImage(time) {
		clearTimeout(covertimer);
		if (formObjects.length > 0) {
			debug.debug("COVERSCRAPER","Next Image, delay time is",time);
			timer_running = true;
			covertimer = setTimeout(processForm, time);
		} else {
			$(statusobj).empty();
			timer_running = false;
			if (typeof aADownloadFinished == 'function') {
				aADownloadFinished();
			}
		}
	}

	function processForm() {

		if (formObjects.length == 0) {
			return 0;
		}

		image = formObjects.shift();
		if (image.hasOwnProperty('cb') && image.cb === null) {
			// Callbacks are nullified when the playlist repopulates, which means we don't need to
			// look for these any more, as if they are still in the playlist they'll have been re-added to formObjects.
			// Rather than do some horrid splicing of formObjects in clearCallbacks, just skip them here.
			debug.trace("COVERSCRAPER","Skipping cleared playlist image");
			doNextImage(500);
			return false;
		}
		imgparams = self.getImageSearchParams(image);
		imgparams.ignorelocal = ignorelocal;
		debug.info("COVERSCRAPER","Getting Cover for", imgparams.imgkey);

		if (sendUpdates) {
			var x = image.prev('input').val();
			statusobj.empty().html(language.gettext("albumart_getting")+" "+decodeURIComponent(x));
			var percent = ((numAlbums - formObjects.length)/numAlbums)*100;
			progress.rangechooser('setProgress', percent.toFixed(2));
			if (scrolling) {
				$('#coverslist').mCustomScrollbar("scrollTo",$('img[name="'+imgparams.imgkey+'"]').parent().parent());
			}
		 }

		animateWaiting();

		var formData = self.getImageFormParams(image);
		fetch(
			"utils/getalbumcover.php",
			{
				method: 'POST',
				signal: AbortSignal.timeout(60000),
				priority: 'low',
				body: formData
			}
		)
		.then(response => {
			if (response.ok) {
				return response.json();
			} else {
				throw new Error(' GetAlbumCover failed<br />'+response.statusText);
			}
		})
		.then(data => { gotImage(data) })
		.catch(err => {
			debug.error('COVERSCARPER', err);
			revertCover();
		});
	}

	function animateWaiting() {
		$('img[name="'+imgparams.imgkey+'"]').removeClass('nospin').addClass('spinner').attr({src: "newimages/transparent.png"});
	}

	function stopAnimation() {
		$('img[name="'+imgparams.imgkey+'"]').removeClass('spinner').addClass('nospin').attr({src: "newimages/transparent.png"});
	}

	// Hello

	function gotImage(data) {
		debug.debug("COVERSCRAPER","Result Is", data);
		stopAnimation();
		if (data.small) {
			self.updateInfo(1);
			if (useLocalStorage) {
				sendLocalStorageEvent(imgparams.imgkey, data);
			}
			finaliseImage(data);
			doNextImage(data.delaytime);
		} else {
			if (setDefaultImage(imgparams)) {
				doNextImage(data.delaytime);
			} else {
				revertCover(data.delaytime);
			}
		}
   }

   function finaliseImage(data) {
		update_ui_images(imgparams.imgkey, data);
		if (typeof (imgparams.cb) == 'function') {
			debug.trace("COVERSCRAPER","calling back for",imgparams.imgkey,data);
			imgparams.cb(data);
		}
	}

	function revertCover(delaytime) {
		if (!delaytime) {
			delaytime = 800;
		}
		debug.info("COVERSCRAPER","No Cover Found. Reverting to the blank icon");
		update_failed_ui_images(imgparams.imagekey);
		if (useLocalStorage) {
			sendLocalStorageEvent("!"+imgparams.imgkey);
		}
		doNextImage(delaytime);
	}

	function setDefaultImage(imgparams) {
		if (sendUpdates) {
			// Dont' do this if we're using the album art manager
			return false;
		}
		// Although getalbumcover does return a default for streams, this is here
		// mainly for the case where auto art download is disabled - this will set a
		// sensible default for streams and also for soundcloud and youtube in the same case
		debug.trace('COVERSCRAPER', 'Checking for default image',imgparams);
		var def = null;
		if (imgparams.type) {
			switch (imgparams.type) {
				case 'stream':
					def = 'newimages/broadcast.svg';
					break;

				case 'podcast':
					def = 'newimages/podcast-logo.svg';
					break;

				case 'local':
					if (imgparams.albumuri && imgparams.albumuri.indexOf('soundcloud:') == 0) {
						def = 'newimages/soundcloud-logo.svg';
					} else if (imgparams.albumuri && (imgparams.albumuri.indexOf('youtube:') == 0 || imgparams.indexOf('yt:') == 0)) {
						def = 'newimages/youtube-logo.svg';
					}

					break;
			}
		} else {
			switch (imgparams.artist) {
				case 'STREAM':
					def = 'newimages/broadcast.svg';
					break;

				case 'PODCAST':
					def = 'newimages/podcast-logo.svg';
					break;
			}
		}
		if (def) {
			debug.info("COVERSCRAPER", "Returning default image of",def);
			var images = {
				small: def,
				medium: def,
				asdownloaded: def
			}
			finaliseImage(images);
			return true;
		} else {
			return false;
		}
	}

	this.clearCallbacks = function() {
		for (var j in formObjects) {
			if (formObjects[j].hasOwnProperty('cb')) {
				formObjects[j].cb = null;
			}
		}
	}

}

function sendLocalStorageEvent(key, data) {
	if (data && data.small) {
		localStorage.setItem("albumimg_"+key, JSON.stringify(data));
	}
	debug.core("COVERSCRAPER","Sending local storage event",key);
	// Event only fires when the key value actually CHANGES
	localStorage.setItem("key", "Blerugh");
	localStorage.setItem("key", key);
}
