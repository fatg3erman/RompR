var lfmImporter = function() {

	var lfmi = null;
    var offset = 0;
    var alloffset = 0;
    var alldata = new Array();
    var limit = 20;
    var row;

    function getNextChunk() {
        $.ajax({
        	url: 'plugins/code/lfmimporter.php',
        	type: "POST",
        	data: {action: 'getchunk', offset: offset, limit: limit},
        	dataType: 'json',
        	success: function(data) {
        		putTracks(data);
        	},
        	error: function() {
        		infobar.notify(infobar.ERROR, "Failed to fetch data!");
        	}
        });
    }

    function putTracks(data) {
        if (data.length > 0) {
            debug.log("LFMIMPORTER","Got data",data);
            for (var i in data) {
                var tr = $('<tr>', {name: data[i].TTindex}).appendTo('#lfmitable');
                tr.append('<td class="playlistinfo underline" name="albumartist">'+data[i].Albumartist+'</td>');
                tr.append('<td class="playlistinfo underline" name="album">'+data[i].Albumname+'</td>');
                tr.append('<td class="playlistinfo underline" name="title">'+data[i].Title+'</td>');
                tr.append('<td class="playlistinfo invisible" name="trackartist">'+data[i].Trackartist+'</td>');
                tr.append('<td class="playlistinfo invisible" name="tracknumber">'+data[i].TrackNo+'</td>');
                tr.append('<td class="playlistinfo invisible" name="disc">'+data[i].Disc+'</td>');
                tr.append('<td class="underline" name="playcount">'+data[i].Playcount+'</td>');
                tr.append('<td class="underline" name="lastfmplaycount"></td>');
                tr.append('<td class="underline" name="tick"></td>');
                alldata.push(data[i]);
            }
            if (!lfmi.is(':visible')) {
                lfmi.slideToggle('fast');
            }
            offset += limit;
            getNextRow();
        } else {
            debug.log("LFMIMPORTER","Got all data");
        }
    }

    function getNextRow() {
        var data = alldata[alloffset];
        lastfm.track.getInfo( { artist: data.Albumartist, track: data.Title },
                                lfmResponseHandler,
                                lfmResponseHandler,
                                alloffset);

        alloffset++;

    }

    function lfmResponseHandler(data, reqid) {
        var de = new lfmDataExtractor(data.track);
        var trackdata = de.getCheckedData('track');
        de = new lfmDataExtractor(trackdata);
        debug.trace("LFMIMPORTER","Playcount for",reqid,"is",alldata[reqid].Playcount, de.userplaycount());
        row = $('#lfmitable').children('tr[name="'+alldata[reqid].TTindex+'"]');
        row.children('td[name="lastfmplaycount"]').html(de.userplaycount());
        if (parseInt(alldata[reqid].Playcount) < parseInt(de.userplaycount())) {
            debug.mark("LFMIMPORTER","Incrementing Playcount for",alldata[reqid].TTindex,"to",de.userplaycount());
            var playlistinfo = {type: 'local', location: ''};
            $.each(row.children('td.playlistinfo'), function() {
                playlistinfo[$(this).attr('name')] = $(this).html();
            });
            debug.debug("LFMIMPORTER","Using data",playlistinfo);
            metaHandlers.fromPlaylistInfo.setMeta(playlistinfo, 'inc', [{attribute: 'Playcount', value: de.userplaycount()}], setSuccess, setFail);
        } else {
            row.children('td[name="tick"]').html('<i class="icon-block collectionicon"></i>');
            doNext();
        }
    }

    function setSuccess() {
        debug.log("LFMIMPORTER","Success");
        row.children('td[name="tick"]').html('<i class="icon-tick collectionicon"></i>');
        doNext();
    }

    function setFail() {
        debug.warn("LFMIMPORTER","Fail");
        row.children('td[name="tick"]').html('<i class="dialog-error collectionicon"></i>');
        doNext();
    }

    function doNext() {
        if (alloffset < alldata.length) {
            getNextRow();
        } else {
            getNextChunk();
        }
    }

	return {

		open: function() {

        	if (lfmi == null) {
	        	lfmi = browser.registerExtraPlugin("lfmi", language.gettext("label_lfm_playcountimporter"), lfmImporter, 'https://fatg3erman.github.io/RompR/Using-Saved-Playlists#editing-your-saved-playlists');
			    $("#lfmifoldup").append('<div class="noselection fullwidth" id="lfmimunger"></div>').append('<table id="lfmitable"></table>');
                $('#lfmitable').append('<tr><th>Artist</th><th>Album</th><th>Title</th>th class="invisible"></th><th class="invisible"></th><th class="invisible"></th><th>Playcount</th><th>Last.FM Playcount</th><th></th></tr>');
			    getNextChunk();
	        } else {
	        	browser.goToPlugin("lfmi");
	        }
		},

		handleClick: function(element, event) {
		},

		close: function() {
			lfmi = null;
			offset = 0;
            alldata = new Array();
            alloffset = 0;
		}

	}

}();

pluginManager.setAction(language.gettext("label_lfm_playcountimporter"), lfmImporter.open);
lfmImporter.open();
