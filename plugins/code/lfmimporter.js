var lfmImporter = function() {

	var lfmi = null;
	var offset = prefs.lfm_importer_start_offset;
	var alloffset = 0;
	var alldata = new Array();
	var limit = 25;
	var row;
	var tracksdone = 0;
	var totaltracks;
	var starttime;
	var errorTimer;
	var running = false;

	function getNextChunk() {
		$.ajax({
			url: 'plugins/code/lfmimporter.php',
			type: "POST",
			data: {action: 'getchunk', offset: offset, limit: limit},
			dataType: 'json'
		})
		.done(putTracks)
		.fail(function() {
			infobar.error(language.gettext('label_general_error'));
		});
	}

	function putTracks(data) {
		alldata = data;
		alloffset = 0;
		if (data.length > 0) {
			$('#lfmitable tr:not(:first-child)').remove();
			debug.debug("LFMIMPORTER","Got data",data);
			for (var i in data) {
				var tr = $('<tr>', {name: data[i].TTindex}).appendTo('#lfmitable');
				tr.append('<td class="playlistinfo underline" name="albumartist">'+data[i].Albumartist+'</td>');
				tr.append('<td class="playlistinfo underline" name="Album">'+data[i].Albumname+'</td>');
				tr.append('<td class="playlistinfo underline" name="Title">'+data[i].Title+'</td>');
				tr.append('<td class="playlistinfo invisible" name="trackartist">'+data[i].Trackartist+'</td>');
				tr.append('<td class="playlistinfo invisible" name="Track">'+data[i].TrackNo+'</td>');
				tr.append('<td class="playlistinfo invisible" name="Disc">'+data[i].Disc+'</td>');
				tr.append('<td class="underline" name="playcount">'+data[i].Playcount+'</td>');
				tr.append('<td class="underline" name="lastfmplaycount"></td>');
				tr.append('<td class="underline" name="tick"></td>');
			}
			offset += limit;
			getNextRow();
		} else {
			debug.info("LFMIMPORTER","Got all data");
			metaHandlers.resetSyncCounts();
		}
	}

	function getNextRow() {
		var data = cloneObject(alldata[alloffset]);
		lastfm.track.getInfo( { artist: data.Albumartist, track: data.Title },
								lfmResponseHandler,
								lfmResponseHandler,
								alloffset);

		alloffset++;
	}

	function lfmResponseHandler(data, reqid) {
		row = $('#lfmitable').children('tr[name="'+alldata[reqid].TTindex+'"]');
		if (data && !data.error) {
			var de = new lfmDataExtractor(data.track);
			var trackdata = de.getCheckedData('track');
			de = new lfmDataExtractor(trackdata);
			debug.trace("LFMIMPORTER","Playcount for",reqid,"is",alldata[reqid].Playcount, de.userplaycount());
			row.children('td[name="lastfmplaycount"]').html(de.userplaycount());
			if (parseInt(alldata[reqid].Playcount) < parseInt(de.userplaycount())) {
				debug.log("LFMIMPORTER","Incrementing Playcount for",alldata[reqid].TTindex,"to",de.userplaycount());
				var playlistinfo = {type: 'local'};
				$.each(row.children('td.playlistinfo'), function() {
					playlistinfo[$(this).attr('name')] = htmlspecialchars_decode($(this).html());
				});
				debug.debug("LFMIMPORTER","Using data",playlistinfo);
				metaHandlers.fromPlaylistInfo.setMeta(playlistinfo, 'inc', [{attribute: 'Playcount', value: de.userplaycount()}], setSuccess, setFail);
			} else {
				row.children('td[name="tick"]').html('<i class="icon-block inline-icon"></i>');
				doNext();
			}
		} else {
			if (data.error) {
				row.children('td[name="tick"]').html('<i class="icon-block inline-icon"></i>');
				doNext();
			} else {
				debug.warn('LFMIMPORTER', 'Result has no data - was there an error? Pausing before continuing');
				clearTimeout(errorTimer);
				errorTimer = setTimeout(doNext, 10000);
			}
		}
	}

	function setSuccess() {
		debug.debug("LFMIMPORTER","Success");
		row.children('td[name="tick"]').html('<i class="icon-tick inline-icon"></i>');
		doNext();
	}

	function setFail(data) {
		debug.warn("LFMIMPORTER","Fail",data);
		row.children('td[name="tick"]').html('<i class="dialog-error inline-icon"></i>');
		doNext();
	}

	function doNext() {
		tracksdone++;
		$('#lfmiprogress').rangechooser("setRange", {min: 0, max: tracksdone+prefs.lfm_importer_start_offset});
		var elapsed = Date.now() - starttime;
		var remaining = (elapsed/tracksdone) * (totaltracks - tracksdone - prefs.lfm_importer_start_offset);
		$('#lfmiinfo').html(language.gettext('importer_status', [tracksdone+prefs.lfm_importer_start_offset, totaltracks, formatTimeString(elapsed/1000), formatTimeString(remaining/1000)]));
		if (running) {
			if (alloffset < alldata.length) {
				getNextRow();
			} else {
				getNextChunk();
			}
		}
	}

	function getTotalTracks() {
		$.ajax({
			url: 'plugins/code/lfmimporter.php',
			type: "POST",
			data: {action: 'gettotal'},
			dataType: 'json'
		})
		.done(function(data) {
			totaltracks = data.total;
			if (totaltracks > 0) {
				$("#lfmiprogress").rangechooser({
					range: data.total,
					interactive: false,
					startmax: 0,
				});
				starttime = Date.now();
				getNextChunk();
			} else {
				$('#lfmitable').remove();
				$('#lfmimunger').append('<div class="textcentre fullwidth"><h3>'+language.gettext('label_lfm_nonew', [new Date(prefs.lfm_importer_last_import * 1000).toLocaleString()])+'</h3></div>');
			}
		})
		.fail(function() {
			infobar.error(language.gettext('label_general_error'));
		});
	}

	return {

		open: function() {

			running = true;
			switch (true) {
				case lfmi == null:
					lfmi = browser.registerExtraPlugin("lfmi", language.gettext("label_lfm_playcountimporter"), lfmImporter, 'https://fatg3erman.github.io/RompR/Keeping-Playcounts-In-Sync');
					$("#lfmifoldup").append('<div class="noselection fullwidth" id="lfmimunger"></div>');
					$("#lfmimunger").append('<div style="height:1em;max-width:80%;margin:auto" id="lfmiprogress"></div>');
					$("#lfmimunger").append('<div style="padding:4px;max-width:80%;margin:auto;text-align:center;font-size:80%;margin-bottom:1em" id="lfmiinfo"></div>');
					$("#lfmimunger").append('<table id="lfmitable"></table>');
					if (lastfm.isLoggedIn()) {
						$('#lfmitable').append('<tr><th>Artist</th><th>'+language.gettext('label_album')+'</th><th>'+language.gettext('label_track')+'</th>th class="invisible"></th><th class="invisible"></th><th class="invisible"></th><th>'+language.gettext('label_playcount')+'</th><th>'+language.gettext('label_lfm_playcount')+'</th><th></th></tr>');
						getTotalTracks();
					} else {
						$('#lfmimunger').append('<div class="textcentre"><h3>'+language.gettext('label_mustlogintolfm')+'</h3></div>');
					}
					lfmi.show();
					// fall through

				default:
				  browser.goToPlugin("lfmi");
				  break;
			}
		},

		handleClick: function(element, event) {
		},

		close: function() {
			lfmi = null;
			offset = 0;
			alldata = new Array();
			alloffset = 0;
			tracksdone = 0;
			running = false;
		}

	}

}();

pluginManager.setAction(language.gettext("label_lfm_playcountimporter"), lfmImporter.open);
lfmImporter.open();
