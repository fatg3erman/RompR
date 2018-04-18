var info_ratings = function() {

	var me = "ratings";
    var trackFinder = new faveFinder(false);

	return {
		getRequirements: function(parent) {
			return [];
		},

		collection: function(parent, artistmeta, albummeta, trackmeta) {

			debug.log("RATINGS PLUGIN", "Creating data collection");

			var self = this;
			var displaying = false;

            function doThingsWithData() {
                if (parent.isCurrentTrack() && trackmeta.usermeta) {
                    if (trackmeta.usermeta.Playcount && trackmeta.usermeta.Playcount > 0) {
                        $("#playcount").html("<b>PLAYS :</b>&nbsp;"+trackmeta.usermeta.Playcount);
                        if (typeof charts != 'undefined') {
                            charts.reloadAll();
                        }
                        if (typeof recentlyPlayed != 'undefined') {
                            recentlyPlayed.reloadAll();
                        }
                    } else {
                        $("#playcount").html("");
                    }
                    displayRating("#ratingimage", trackmeta.usermeta.Rating);
                    $("#dbtags").html('<span><b>'+language.gettext("musicbrainz_tags")+
                        '</b></span><i class="icon-plus clickicon playlisticon" '+
                        'onclick="tagAdder.show(event)" style="margin-left:2px;margin-top:0px;margin-right:1em;"></i>');
                    for(var i = 0; i < trackmeta.usermeta.Tags.length; i++) {
                        $("#dbtags").append('<span class="tag">'+trackmeta.usermeta.Tags[i]+
                            '<i class="icon-cancel-circled clickicon tagremover playlisticon" '+
                            'onclick="nowplaying.removeTag(event)" style="display:none"></i></span> ');
                    }
                    $("#dbtags .tag").hover( function() {
                        $(this).children().show();
                    }, function() {
                        $(this).children().hide();
                    });
                    layoutProcessor.adjustLayout();
                }
                // Make sure the browser updates the file info display
                browser.reDo(parent.nowplayingindex, 'file');
            }

            function hideTheInputs() {
                if (parent.isCurrentTrack()) {
                    displayRating("#ratingimage", false);
                    $("#dbtags").html('');
                    $("#playcount").html('');
                }
            }

            function setSuccess(rdata) {
                debug.log("RATING PLUGIN","Success");
                if (rdata) {
                    trackmeta.usermeta = rdata.metadata;
                    doThingsWithData();
                    updateCollectionDisplay(rdata);
                }
            }

            function findSuccess(rdata) {
                debug.log("RATING PLUGIN","Success");
                if (rdata) {
                    trackmeta.usermeta = rdata.metadata;
                    doThingsWithData();
                    updateCollectionDisplay(rdata);
                    if (!rdata.hasOwnProperty('addedtracks')) {
                        infobar.notify(infobar.NOTIFY,"That track is already in the collection");
                    }
                }
            }
            
            function setFail(rdata) {
                debug.warn("RATING PLUGIN","Failure");
                // infobar.notify(infobar.ERROR,"Failed! Have you read the Wiki?");
                doThingsWithData();
            }

			this.displayData = function() {
                debug.error("RATINGS PLUGIN", "Was asked to display data!");
			}

			this.stopDisplaying = function() {
			}

            this.refresh = function() {
                trackmeta.usermeta = undefined;
                self.populate();
            }

			this.populate = function() {
                if (trackmeta.usermeta === undefined) {
                    metaHandlers.fromPlaylistInfo.getMeta(
                        parent.playlistinfo,
                        function(data) {
                            trackmeta.usermeta = data;
                            doThingsWithData();
                        },
                        function(data) {
                            trackmeta.usermeta = null;
                            hideTheInputs();
                        }
                    );
                } else {
                    debug.mark("RATINGS PLUGIN",parent.nowplayingindex,"is already populated");
                    doThingsWithData();
                }
		    }

            this.setMeta = function(action, type, value) {
                debug.log("RATINGS PLUGIN",parent.nowplayingindex,"Doing",action,type,value);
                if (parent.playlistinfo.type == 'stream') {
                    infobar.notify(infobar.NOTIFY,language.gettext('label_searching'));
                    trackFinder.findThisOne(metaHandlers.fromPlaylistInfo.mapData(parent.playlistinfo, action, [{attribute: type, value: value}]),
                        self.updateDatabase,
                        false
                    );
                } else {
                    metaHandlers.fromPlaylistInfo.setMeta(parent.playlistinfo, action, [{attribute: type, value: value}], setSuccess, setFail);
                }
            }

            this.setAlbumMBID = function(mbid) {
                debug.log("RATINGS PLUGIN",parent.nowplayingindex," Updating backend album MBID");
                metaHandlers.fromPlaylistInfo.setMeta(parent.playlistinfo, 'setalbummbid', mbid, false, false);
            }

            this.getMeta = function(meta) {
                if (trackmeta.usermeta) {
                    if (trackmeta.usermeta[meta]) {
                        return trackmeta.usermeta[meta];
                    } else {
                        return 0;
                    }
                } else {
                    return 0;
                }
            }

            this.updateDatabase = function(data) {
                debug.log("RATINGS","Update Database Function Called",data);
                if (!data.uri) {
                    infobar.notify(infobar.NOTIFY,language.gettext("label_addtow"));
                }
                dbQueue.request([data], findSuccess, setFail);
            }
		}
	}
}();

nowplaying.registerPlugin("ratings", info_ratings, null, null);
