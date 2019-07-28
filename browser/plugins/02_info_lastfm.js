var info_lastfm = function() {

	var me = "lastfm";
    var medebug = "LASTFM PLUGIN";

    function formatLastFmError(lfmdata, type) {
		if (lfmdata.errorcode() == 6) {
			return '<h3 align="center">'+language.gettext('label_no'+type+'info')+'</h3>';
		} else {
        	return '<h3 align="center">'+lfmdata.error()+'</h3>';
		}
    }

    function sectionHeader(data) {
        var html = '<div class="holdingcell">';
        html += '<div class="standout stleft statsbox"><ul>';
        html += '<li><b>'+language.gettext("lastfm_listeners")+'</b> '+data.listeners()+'</li>';
        html += '<li><b>'+language.gettext("lastfm_plays")+'</b> '+data.playcount()+'</li>';
        html += '<li><b>'+language.gettext("lastfm_yourplays")+'</b> '+data.userplaycount()+'</li>';
        return html;
    }

    function doTags(taglist) {
    	debug.trace(medebug,"    Doing Tags");
        var html = '<ul><li><b>'+language.gettext("lastfm_toptags")+'</b></li><li><table class="fullwidth">';
        for(var i in taglist) {
            if (taglist[i].name) {
                html += '<tr><td><a href="'+taglist[i].url+'" target="_blank">'+taglist[i].name+'</a></td>';
            }
        }
        html += '</table></li></ul>';
        return html;
    }

    function tagsInput(type) {
        var html = '<ul class="holdingcell"><li><b>'+language.gettext("lastfm_addtags")+'</b></li>';
        html += '<li class="tiny">'+language.gettext("lastfm_addtagslabel")+'</li>';
        html += '<li><input class="enter tiny inbrowser" type="text"></input>';
        html += '<button class="infoclick clickaddtags tiny">'+language.gettext("button_add")+'</button>'+
                        '<i class="smallicon tright" id="tagadd'+type+'"></i></li></ul>';
        return html;
    }

    function doUserTags(name) {
        var html = '<ul><li><b>'+language.gettext("lastfm_yourtags")+'</b></li><li><table class="fullwidth" name="'+name+'tagtable">';
        html += '</table></li></ul>';
        return html;
    }

    function findTag(name, taglist) {
        for(var i in taglist) {
            if (name == taglist[i].name) {
                debug.debug("FINDTAG", "Found tag",name);
                return true;
            }
        }
        return false;
    }

    function findTag2(name, table) {
        var retval = false;
        table.find('tr').each( function() {
            var n = $(this).find('a').text();
            if (n.toLowerCase() == name.toLowerCase()) {
                debug.debug("FINDTAG 2",'Found Tag',name);
                retval = true;
            }
        });
        return retval;
    }

    function appendTag(table, name, url) {
        var html = '<tr class="newtag invisible"><td><a href="'+url+'" target="_blank">'+name+'</a></td>';
        html += '<td><i class="icon-cancel-circled playlisticon infoclick clickremovetag tooltip" title="'+language.gettext("lastfm_removetag")+'"></i></td>';
        $('table[name="'+table+'tagtable"]').append(html);
        $(".newtag").fadeIn('fast', function(){
            $(this).removeClass('newtag');
        });
    }

    function getArtistHTML(lfmdata, parent, artistmeta) {
        if (lfmdata.error()) {
            return formatLastFmError(lfmdata, 'artist');
        }
        var html = sectionHeader(lfmdata);
        html += '</ul><br>';

        html += doTags(lfmdata.tags());
        if (lastfm.isLoggedIn()) {
             html += tagsInput("artist");
             html += doUserTags("artist");
        }

        html += '</div><div class="statsbox">';

        var imageurl = lfmdata.image("mega");
        if (imageurl != '') {
            html +=  '<img class="stright standout'
            html += ' infoclick clickzoomimage cshrinker';
            html += '" src="getRemoteImage.php?url=' + imageurl + '" />';
            html += '<input type="hidden" value="getRemoteImage.php?url='+imageurl+'" />';
        }
        html +=  '<div id="artistbio" class="minwidthed">';
        html += lastfm.formatBio(lfmdata.bio(), lfmdata.url());
        html += '</div></div>';
        html += '</div>';

        var similies = lfmdata.similar();
        if (similies.length > 0 && typeof similies[0].name != 'undefined') {
            html += '<div id="similarartists" class="bordered"><h3 align="center">'+language.gettext("lastfm_simar")+'</h3>';
            html += '<table width="100%" cellspacing="0" cellpadding="0"><tr><td align="center"><div class="smlrtst">';
            for(var i in similies) {
                html += '<div class="simar">';
                html += '<table><tr><td align="center">';
                var mi = lfmdata.similarimage(i, "medium");
                var bi = lfmdata.similarimage(i, "mega");
                if (!bi) bi = mi;
                if (mi) {
                    html += '<img class="infoclick clickzoomimage" src="getRemoteImage.php?url='+lfmdata.similarimage(i, "medium")+'"><input type="hidden" value="getRemoteImage.php?url='+lfmdata.similarimage(i, "mega")+'" />';
                }
                html += '</td></tr>';
                html += '<tr><td align="center"><a href="'+similies[i].url+'" target="_blank">'+similies[i].name+'</a></td></tr>';
                html += '</table>';
                html += '</div>';
            }
            html += '</div></td></tr></table></div>';
        }
        return html;
    }

    function getAlbumHTML(lfmdata) {
        if (lfmdata.error()) {
            return formatLastFmError(lfmdata, 'album');
        }
        var html = sectionHeader(lfmdata);
        html += '</ul><br>';

        html += doTags(lfmdata.tags());
        if (lastfm.isLoggedIn()) {
            html += tagsInput("album");
            html += doUserTags("album");
        }

        html += '</div><div class="statsbox">';
        var imageurl = lfmdata.image("large");
        var bigurl = lfmdata.image("mega");
        if (imageurl != '') {
            html +=  '<img class="stright standout'
            if (bigurl && bigurl != imageurl) {
                html += ' infoclick clickzoomimage';
            }
            html += '" src="getRemoteImage.php?url=' + imageurl + '" />';
            if (bigurl && bigurl != imageurl) {
                html += '<input type="hidden" value="getRemoteImage.php?url='+bigurl+'" />';
            }
        }
        if (lfmdata.releasedate() != 'Unknown') {
            html +=  '<p class="minwidthed">';
            html += '<b>'+language.gettext("lastfm_releasedate")+' : </b>'+lfmdata.releasedate();
            html +=  '</p>';
        }
        html += '<p class="minwidthed">'+lastfm.formatBio(lfmdata.bio())+'</p>';
        var tracks = lfmdata.tracklisting();
        debug.trace(medebug,"Track Listing",tracks);
        if (tracks && tracks.length > 0) {
            var dh = false;
            for(var i in tracks) {
                if (tracks[i].name) {
                    if (!dh) {
                        html += '<table><tr><th colspan="3">'+language.gettext("discogs_tracklisting")+'</th></tr>';
                        dh = true;
                    }
                    html += '<tr><td>';
                    if (tracks[i]['@attr']) { html += tracks[i]['@attr'].rank+':'; }
                    html += '</td><td>'+tracks[i].name+'</td><td>'+formatTimeString(tracks[i].duration)+'</td>';
                    html += '<td align="right"><a target="_blank" href="'+tracks[i].url+'"><i class="icon-lastfm-1 smallicon tooltip" title="'+language.gettext("lastfm_viewtrack")+'"></i></a></td><td align="right">';
                    html += '</td></tr>';
                }
            }
            html += '</table>';
        }
        html += '</div>'
        html += '</div>';
        return html;
    }

    function getTrackHTML(lfmdata) {
        if (lfmdata.error()) {
            return formatLastFmError(lfmdata, 'track');
        }
        var html = sectionHeader(lfmdata);
        html += '<li name="userloved">';
        html = html +'</li>';
        html += '</ul><br>';

        html += doTags(lfmdata.tags());
        if (lastfm.isLoggedIn()) {
            html += tagsInput("track");
            html += doUserTags("track");
        }
        html += '</div>';
        html += '<p>'+lastfm.formatBio(lfmdata.bio())+'</p>';
        html += '</div>';
        return html;
    }

	return {
        getRequirements: function(parent) {
            return [];
        },

		collection: function(parent, artistmeta, albummeta, trackmeta) {

			debug.trace(medebug, "Creating data collection");

			var self = this;
            var displaying = false;

            this.populate = function() {
				$('#love').addClass('notloved').makeSpinner();
                self.artist.populate();
				self.album.populate();
				self.track.populate();
            }

            this.displayData = function() {
                displaying = true;
                self.artist.doBrowserUpdate();
                self.album.doBrowserUpdate();
                self.track.doBrowserUpdate();
            }

            this.stopDisplaying = function() {
				$('#love').stopSpinner();
                displaying = false;
            }

            this.handleClick = function(source, element, event) {
                debug.trace(medebug,parent.nowplayingindex,source,"is handling a click event");
                if (element.hasClass('clickremovetag')) {
                    var tagname = element.parent().prev().children().text();
                    debug.trace(medebug,parent.nowplayingindex,source,"wants to remove tag",tagname);
                    self[source].removetags(tagname);
                    if (prefs.synctags) {
                        parent.setMeta('remove', 'Tags', tagname);
                    }
                } else if (element.hasClass('clickaddtags')) {
                    var tagname = element.prev().val();
                    debug.trace(medebug,parent.nowplayingindex,source,"wants to add tags",tagname);
                    self[source].addtags(tagname);
                    if (prefs.synctags) {
                        parent.setMeta('set', 'Tags', tagname.split(','));
                    }
                } else if (element.hasClass('clickzoomimage')) {
                    imagePopup.create(element, event, element.next().val());
                } else if (element.hasClass('clickunlove')) {
                    self[source].unlove();
                    if (prefs.synclove) {
                        parent.setMeta('set', 'Rating', '0');
                    }
                } else if (element.hasClass('clicklove')) {
                    self[source].love();
                    if (prefs.synclove) {
                        parent.setMeta('set', 'Rating', prefs.synclovevalue);
                    }
                }
            }

            this.somethingfailed = function(data) {
                debug.warn(medebug,"Something went wrong",data);
            }

            this.justaddedtags = function(type, tags) {
                debug.trace(medebug,parent.nowplayingindex,"Just added or removed tags",tags,"on",type);
                self[type].resetUserTags();
                self[type].getUserTags();
            }

            this.tagAddFailed = function(type, tags) {
                $("#tagadd"+type).stopSpinner();
                infobar.error(language.gettext("lastfm_tagerror"));
                debug.warn(medebug,"Failed to modify tags",type,tags);
            }

            function formatUserTagData(name, taglist, displaying) {
                if (displaying) {
                    debug.trace("FUTD","Doing",name,"tags");
                    var toAdd = new Array();
                    var toRemove = new Array();
                    $('table[name="'+name+'tagtable"]').find("tr").each( function() {
                        if (!(findTag($(this).find('a').text(), taglist))) {
                            debug.trace("FUTD","Marking tag",$(this).find('a').text(),"for removal");
                            toRemove.push($(this));
                        }
                    });
                    for(var i in taglist) {
                        debug.trace("FUTD","Checking for addition",taglist[i].name);
                        if (!(findTag2(taglist[i].name, $('table[name="'+name+'tagtable"]')))) {
                            debug.trace("FUTD","Marking Tag",taglist[i].name,"for addition");
                            toAdd.push(taglist[i])
                        }
                    }
                    for (var i in toRemove) {
                        toRemove[i].fadeOut('fast', function() { $(this).remove() });
                    }
                    for (var i in toAdd) {
                        appendTag(name, toAdd[i].name, toAdd[i].url);
                    }
                }
            }

            function doUserLoved(flag) {
				debug.log("LASTFM","Doing UserLoved With Flags at",flag);
				if (parent.isCurrentTrack()) {
					$('#love').stopSpinner();
					if (flag) {
						$('#love').removeClass('notloved').attr('title', language.gettext("lastfm_unlove")).off('click').on('click', nowplaying.unlove);
					} else {
						$('#love').removeClass('notloved').addClass('notloved').attr('title', language.gettext("lastfm_lovethis")).off('click').on('click', nowplaying.love);
					}
				}
				if (displaying) {
					var li = $('li[name="userloved"]');
					li.empty();
	                if (flag) {
						li.append($('<b>').html(language.gettext("lastfm_loved")+': ')).append(language.gettext("label_yes")+'&nbsp;&nbsp;&nbsp;')
						li.append($('<i>', {
							title: language.gettext("lastfm_unlove"),
							class: "icon-heart-broken smallicon infoclick clickunlove tooltip"
						}));
	                } else {
						li.append($('<b>').html(language.gettext("lastfm_loved")+': ')).append(language.gettext("label_no")+'&nbsp;&nbsp;&nbsp;')
						li.append($('<i>', {
							title: language.gettext("lastfm_lovethis"),
							class: "icon-heart smallicon infoclick clicklove tooltip notloved"
						}));
	                }
				}
            }

            function getSearchArtist() {
                return (albummeta.artist && albummeta.artist != "" && parent.playlistinfo.type != 'stream') ? albummeta.artist : parent.playlistinfo.trackartist;
            }

            function sendLastFMCorrections() {
                try {
                    var updates = { trackartist: (parent.playlistinfo.metadata.artists.length == 1) ? self.artist.name() : parent.playlistinfo.trackartist,
                                    album: self.album.name(),
                                    title: self.track.name(),
                                    image: self.album.image('mega') ? self.album.image('mega') : self.album.image('medium')
                                };
                    nowplaying.setLastFMCorrections(parent.currenttrack, updates);
                } catch(err) {
                    debug.fail(medebug,"Not enough information to send corrections");
                }
            }

			function sendMetadataUpdates(de) {
				var lfmdata = new lfmDataExtractor(trackmeta.lastfm.track);
				nowplaying.setMetadataFromLastFM(parent.nowplayingindex, {Playcount: lfmdata.userplaycount()});
			}

			this.artist = function() {

                return {

					populate: function() {
                        if (artistmeta.lastfm === undefined) {
    						debug.mark(medebug,parent.nowplayingindex,"artist is populating",artistmeta.name);
    						lastfm.artist.getInfo( {artist: artistmeta.name},
    												this.lfmResponseHandler,
    												this.lfmResponseHandler
    						);
                        } else {
                            debug.trace(medebug,parent.nowplayingindex,"artist is already populated",artistmeta.name);
                        }
					},

		            lfmResponseHandler: function(data) {
						debug.trace(medebug,parent.nowplayingindex,"got artist data for",artistmeta.name);
                        debug.trace(medebug,data);
						var de = new lfmDataExtractor(data);
						artistmeta.lastfm = de.getCheckedData('artist');
		                if (artistmeta.musicbrainz_id == "") {
                            var mbid = null;
                            try {
                                mbid = data.artist.mbid || null;
                            } catch(err) {
                                mbid = null;
                            }
		                	debug.log(medebug,parent.nowplayingindex,"has found a musicbrainz artist ID",mbid);
		                	artistmeta.musicbrainz_id = mbid;
		                }
		                self.artist.doBrowserUpdate();
		            },

					doBrowserUpdate: function() {
						if (displaying && artistmeta.lastfm !== undefined) {
                            debug.trace(medebug,parent.nowplayingindex,"artist was asked to display");
                            var lfmdata = new lfmDataExtractor(artistmeta.lastfm.artist);
					        var accepted = browser.Update(
                                null,
                                'artist',
                                me,
                                parent.nowplayingindex,
                                { name: self.artist.name(),
					        	  link: lfmdata.url(),
					        	  data: getArtistHTML(lfmdata, parent, artistmeta)
					        	}
					        );

                            if (accepted && lastfm.isLoggedIn() && !lfmdata.error()) {
                                self.artist.getUserTags();
                            }

						}
					},

                    name: function() {
                        try {
                            return artistmeta.lastfm.artist.name || artistmeta.name;
                        } catch(err) {
                            return artistmeta.name;
                        }
                    },

                    getFullBio: function(callback, failcallback) {
                        debug.shout(medebug,parent.nowplayingindex,"Not Getting Bio URL:", artistmeta.lastfm.artist.url);
                    },

                    updateBio: function(data) {
                        if (displaying) {
                            $("#artistbio").html(lastfm.formatBio(data, null));
                        }
                    },

                    resetUserTags: function() {
                        artistmeta.lastfm.usertags = null;
                    },

                    getUserTags: function() {
                        debug.debug(medebug,parent.nowplayingindex,"Getting Artist User Tags");
                        if (artistmeta.lastfm.usertags) {
                            formatUserTagData('artist', artistmeta.lastfm.usertags, displaying);
                        } else {
                            var options = { artist: self.artist.name() };
                            if (artistmeta.musicbrainz_id != "") {
                                options.mbid = artistmeta.musicbrainz_id;
                            }
                            lastfm.artist.getTags(
                                options,
                                self.artist.gotUserTags,
                                self.artist.somethingfailed
                            );
                        }

                    },

                    somethingfailed: function(data) {
                        $("#tagaddartist").stopSpinner();
                        debug.warn(medebug,"Something went wrong getting artist user tags",data);
                    },

                    gotUserTags: function(data) {
                        $("#tagaddartist").stopSpinner();
						var de = new lfmDataExtractor(data);
                        artistmeta.lastfm.usertags = de.tags();
                        formatUserTagData('artist', artistmeta.lastfm.usertags, displaying);
                    },

                    addtags: function(tags) {
                        $("#tagaddartist").makeSpinner();
                        lastfm.artist.addTags({ artist: self.artist.name(),
                                                tags: tags},
                                                self.justaddedtags,
                                                self.tagAddFailed
                        );
                    },

                    removetags: function(tags) {
                        $("#tagaddartist").makeSpinner();
                        lastfm.artist.removeTag({artist: self.artist.name(),
                                                tag: tags},
                                                self.justaddedtags,
                                                self.tagAddFailed
                        );
                    }

				}

			}();

            this.album = function() {

                return {

                    populate: function() {
                        if (albummeta.lastfm === undefined) {
                            debug.mark(medebug,"Getting last.fm data for album",albummeta.name);
							if (parent.playlistinfo.type == 'stream') {
								lastfm.artist.getInfo({  artist: albummeta.name },
	                                                this.lfmArtistResponseHandler,
	                                                this.lfmArtistResponseHandler );

							} else {
	                            lastfm.album.getInfo({  artist: getSearchArtist(),
	                                                    album: albummeta.name},
	                                                this.lfmResponseHandler,
	                                                this.lfmResponseHandler );
							}
                        } else {
                            debug.trace(medebug,"Album is already populated",albummeta.name);
                        }

                    },

                    lfmResponseHandler: function(data) {
                        debug.trace(medebug,"Got Album Info for",albummeta.name);
                        debug.debug(medebug, data);
						var de = new lfmDataExtractor(data);
						albummeta.lastfm = de.getCheckedData('album');
                        if (albummeta.musicbrainz_id == "") {
                            var mbid = null;
                            try {
                                mbid = data.album.mbid || null;
                            } catch(err) {
                                mbid = null;
                            }
                            if (mbid !== null) {
                                debug.log(medebug,parent.nowplayingindex,"has found a musicbrainz album ID",mbid);
                                nowplaying.updateAlbumMBID(parent.nowplayingindex,mbid);
                            }
                            albummeta.musicbrainz_id = mbid;
                        }
                        self.album.doBrowserUpdate();
                    },

					lfmArtistResponseHandler: function(data) {
						debug.trace(medebug,"Got Album/Artist Info for",albummeta.name);
                        debug.debug(medebug, data);
						var de = new lfmDataExtractor(data);
						albummeta.lastfm = de.getCheckedData('artist');
						albummeta.musicbrainz_id = null;
                        self.album.doBrowserUpdate();
					},

                    doBrowserUpdate: function() {
                        if (displaying && albummeta.lastfm !== undefined) {
                            debug.trace(medebug,parent.nowplayingindex,"album was asked to display");
                            var lfmdata = (parent.playlistinfo.type == 'stream') ? new lfmDataExtractor(albummeta.lastfm.artist) : new lfmDataExtractor(albummeta.lastfm.album);
                            var accepted = browser.Update(
                                null,
                                'album',
                                me,
                                parent.nowplayingindex,
                                { name: lfmdata.name() || albummeta.name,
                                  link: lfmdata.url(),
                                  data: (parent.playlistinfo.type == 'stream') ? getArtistHTML(lfmdata) : getAlbumHTML(lfmdata)
                                }
                            );

                            if (accepted && lastfm.isLoggedIn() && !lfmdata.error()) {
                                self.album.getUserTags();
                            }
                        }
                    },

                    name: function() {
                        try {
                            return albummeta.lastfm.album.name || albummeta.name;
                        } catch(err) {
                            return albummeta.name;
                        }
                    },

                    image: function(size) {
                        if (albummeta.lastfm.album) {
                            var lfmdata = new lfmDataExtractor(albummeta.lastfm.album);
                            return lfmdata.image(size);
                        }
                        return "";
                    },

                    resetUserTags: function() {
                        albummeta.lastfm.usertags = null;
                    },

                    getUserTags: function() {
                        debug.debug(medebug,parent.nowplayingindex,"Getting Album User Tags");
                        if (albummeta.lastfm.usertags) {
                            formatUserTagData('album', albummeta.lastfm.usertags, displaying);
                        } else {
                            var options = { artist: getSearchArtist(), album: self.album.name() };
                            if (albummeta.musicbrainz_id != "" && albummeta.musicbrainz_id != null) {
                                options.mbid = albummeta.musicbrainz_id;
                            }
                            lastfm.album.getTags(
                                options,
                                self.album.gotUserTags,
                                self.album.somethingfailed
                            );
                        }

                    },

                    somethingfailed: function(data) {
                        $("#tagaddalbum").stopSpinner();
                        debug.warn(medebug,"Something went wrong getting album user tags",data);
                    },

                    gotUserTags: function(data) {
                        $("#tagaddalbum").stopSpinner();
						var de = new lfmDataExtractor(data);
						albummeta.lastfm.usertags = de.tags();
                        formatUserTagData('album', albummeta.lastfm.usertags, displaying);
                    },

                    addtags: function(tags) {
                        $("#tagaddalbum").makeSpinner();
                        lastfm.album.addTags({  artist: getSearchArtist(),
                                                album: self.album.name(),
                                                tags: tags},
                                            self.justaddedtags,
                                            self.tagAddFailed
                        );
                    },

                    removetags: function(tags) {
                        $("#tagaddalbum").makeSpinner();
                        lastfm.album.removeTag({    artist: getSearchArtist(),
                                                    album: self.album.name(),
                                                    tag: tags},
                                            self.justaddedtags,
                                            self.tagAddFailed
                        );
                    }
                }
            }();

            this.track = function() {

                return {

                    populate: function() {
                        if (trackmeta.lastfm === undefined) {
                            debug.mark(medebug,parent.nowplayingindex,"Getting last.fm data for track",trackmeta.name);
                            lastfm.track.getInfo( { artist: getSearchArtist(), track: trackmeta.name },
                                                    this.lfmResponseHandler,
                                                    this.lfmResponseHandler );
                        } else {
                            debug.trace(medebug,parent.nowplayingindex,"Track is already populated",trackmeta.name);
                            sendLastFMCorrections();
                        }
                    },

                    lfmResponseHandler: function(data) {
                        debug.trace(medebug,parent.nowplayingindex,"Got Track Info for",trackmeta.name);
                        debug.debug(medebug, data);
						var de = new lfmDataExtractor(data);
						trackmeta.lastfm = de.getCheckedData('track');
                        if (trackmeta.musicbrainz_id == "") {
                            var mbid = null;
                            try {
                                mbid = data.track.mbid || null;
                            } catch(err) {
                                mbid = null;
                            }
                            debug.trace(medebug,parent.nowplayingindex,"has found a musicbrainz track ID",mbid);
                            trackmeta.musicbrainz_id = mbid;
                        }
                        sendLastFMCorrections();
						sendMetadataUpdates();
                        self.track.doBrowserUpdate();
                    },

                    doBrowserUpdate: function() {
                        if (displaying && trackmeta.lastfm !== undefined) {
                            debug.trace(medebug,parent.nowplayingindex,"track was asked to display");
                            var lfmdata = new lfmDataExtractor(trackmeta.lastfm.track);
                            var accepted = browser.Update(
                                null,
                                'track',
                                me,
                                parent.nowplayingindex,
                                { name: self.track.name(),
                                  link: lfmdata.url(),
                                  data: getTrackHTML(lfmdata)
                                }
                            );

                            if (accepted && lastfm.isLoggedIn() && !lfmdata.error()) {
                                self.track.getUserTags();
							}
                        }
						if (trackmeta.lastfm !== undefined) {
							var lfmdata = new lfmDataExtractor(trackmeta.lastfm.track);
							doUserLoved(lfmdata.userloved());
						}
                    },

                    name: function() {
                        try {
                            return trackmeta.lastfm.track.name || trackmeta.name;
                        } catch(err) {
                            return trackmeta.name;
                        }
                    },

                    resetUserTags: function() {
                        trackmeta.lastfm.usertags = null;
                    },

                    getUserTags: function() {
                        debug.debug(medebug,parent.nowplayingindex,"Getting Track User Tags");
                        if (trackmeta.lastfm.usertags) {
                            formatUserTagData('track', trackmeta.lastfm.usertags, displaying);
                        } else {
                            var options = { artist: self.artist.name(), track: self.track.name() };
                            if (trackmeta.musicbrainz_id != "" && trackmeta.musicbrainz_id != null) {
                                options.mbid = trackmeta.musicbrainz_id;
                            }
                            lastfm.track.getTags(
                                options,
                                self.track.gotUserTags,
                                self.track.somethingfailed,
                                0
                            );
                        }

                    },

                    somethingfailed: function(data) {
                        $("#tagaddtrack").stopSpinner();
                        debug.warn(medebug,"Something went wrong getting track user tags",data);
                    },

                    gotUserTags: function(data) {
                        $("#tagaddtrack").stopSpinner();
						var de = new lfmDataExtractor(data);
                        trackmeta.lastfm.usertags = de.tags();
                        formatUserTagData('track', trackmeta.lastfm.usertags, displaying);
                    },

                    addtags: function(tags) {
                        $("#tagaddtrack").makeSpinner();
                        lastfm.track.addTags({  artist: self.artist.name(),
                                                track: self.track.name(),
                                                tags: tags},
                                            self.justaddedtags,
                                            self.tagAddFailed
                        );
                    },

                    removetags: function(tags) {
                        if (findTag2(tags, $('table[name="tracktagtable"]'))) {
                            $("#tagaddtrack").makeSpinner();
                            lastfm.track.removeTag({    artist: self.artist.name(),
                                                        track: self.track.name(),
                                                        tag: tags},
                                                self.justaddedtags,
                                                self.tagAddFailed
                            );
                        } else {
                            debug.warn(medebug, "Tag",tags,"not found on track");
                        }
                    },

                    love: function() {
                        lastfm.track.love({ track: self.track.name(), artist: self.artist.name() }, self.track.donelove);
                    },

                    unlove: function(callback) {
                        lastfm.track.unlove({ track: self.track.name(), artist: self.artist.name() }, self.track.donelove);
                    },

					unloveifloved: function() {
						if (trackmeta.lastfm.track.userloved == 1) {
							self.track.unlove();
						}
					},

                    donelove: function(loved) {
                        if (loved) {
                            // Rather than re-get all the details, we can just edit the track data directly.
                            trackmeta.lastfm.track.userloved = 1;
                            if (prefs.autotagname != '') {
                                self.track.addtags(prefs.autotagname);
                                if (prefs.synctags && prefs.synclove) {
                                    parent.setMeta('set', 'Tags', [prefs.autotagname]);
                                }
                            }
                            doUserLoved(true)
                        } else {
                            trackmeta.lastfm.track.userloved = 0;
                            if (prefs.autotagname != '') {
                                self.track.removetags(prefs.autotagname);
                                if (prefs.synctags && prefs.synclove) {
                                    parent.setMeta('remove', 'Tags', prefs.autotagname);
                                }
                            }
                            doUserLoved(false)
                        }
                    }

                }
            }();
		}
	}

}();

nowplaying.registerPlugin("lastfm", info_lastfm, "icon-lastfm-1", "button_infolastfm");
