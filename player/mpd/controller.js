function playerController() {

    var self = this;
	var updatetimer = null;
    var progresstimer = null;
    var safetytimer = 500;
    var previoussongid = -1;
    var AlanPartridge = 0;
    var plversion = null;
    var openpl = null;
    var oldplname;
    var thenowplayinghack = false;
    var lastsearchcmd = "search";
    var stateChangeCallbacks = new Array();

    function updateStreamInfo() {

        // When playing a stream, mpd returns 'Title' in its status field.
        // This usually has the form artist - track. We poll this so we know when
        // the track has changed (note, we rely on radio stations setting their
        // metadata reliably)

        // Note that mopidy doesn't quite work this way. It sets Title and possibly Name
        // - I fixed that bug once but it got broke again

        if (playlist.getCurrent('type') == "stream") {
            debug.trace('STREAMHANDLER','Playlist:',playlist.getCurrent('Title'),playlist.getCurrent('Album'),playlist.getCurrent('trackartist'));
            var temp = playlist.getCurrentTrack();
            if (player.status.Title) {
                var parts = player.status.Title.split(" - ");
                if (parts[0] && parts[1]) {
                    temp.trackartist = parts.shift();
                    temp.Title = parts.join(" - ");
                    temp.metadata.artists = [{name: temp.trackartist, musicbrainz_id: ""}];
                    temp.metadata.track = {name: temp.Title, musicbrainz_id: ""};
                } else if (player.status.Title && player.status.Artist) {
                    temp.trackartist = player.status.Artist;
                    temp.Title = player.status.Title;
                    temp.metadata.artists = [{name: temp.trackartist, musicbrainz_id: ""}];
                    temp.metadata.track = {name: temp.Title, musicbrainz_id: ""};
                }
            }
            if (player.status.Name && !player.status.Name.match(/^\//) && temp.Album == rompr_unknown_stream) {
                // NOTE: 'Name' is returned by MPD - it's the station name as read from the station's stream metadata
                debug.shout('STREAMHANDLER',"Checking For Stream Name Update");
                checkForUpdateToUnknownStream(playlist.getCurrent('StreamIndex'), player.status.Name);
                temp.Album = player.status.Name;
                temp.metadata.album = {name: temp.Album, musicbrainz_id: ""};
            }
            debug.trace('STREAMHANDLER','Current:',temp.Title,temp.Album,temp.trackartist);
            if (playlist.getCurrent('Title') != temp.Title ||
                playlist.getCurrent('Album') != temp.Album ||
                playlist.getCurrent('trackartist') != temp.trackartist)
            {
                debug.log("STREAMHANDLER","Detected change of track",temp);
                var aa = new albumart_translator('');
                temp.key = aa.getKey('stream', '', temp.Album);
                playlist.setCurrent({Title: temp.Title, Album: temp.Album, trackartist: temp.trackartist });
                nowplaying.newTrack(temp, true);
            }
        }
    }

    function checkForUpdateToUnknownStream(streamid, name) {
        // If our playlist for this station has 'Unknown Internet Stream' as the
        // station name, let's see if we can update it from the metadata.
        debug.log("STREAMHANDLER","Checking For Update to Stream",streamid,name, name);
        var m = playlist.getCurrent('Album');
        if (m.match(/^Unknown Internet Stream/)) {
            debug.shout("PLAYLIST","Updating Stream",name);
            yourRadioPlugin.updateStreamName(streamid, name, playlist.getCurrent('file'), playlist.repopulate);
        }
    }

    function setTheClock(callback, timeout) {
        clearProgressTimer();
        progresstimer = setTimeout(callback, timeout);
    }

    function initialised(data) {
        for(var i =0; i < data.length; i++) {
            var h = data[i].replace(/\:\/\/$/,'');
            debug.log("PLAYER","URL Handler : ",h);
            player.urischemes[h] = true;
        }
        if (!player.canPlay('spotify')) {
            $('div.textcentre.textunderline:contains("Music From Spotify")').remove();
        }
        checkSearchDomains();
        doMopidyCollectionOptions();
        playlist.radioManager.init();
        // Need to call this with a callback when we start up so that checkprogress doesn't get called
        // before the playlist has repopulated.
        self.do_command_list([],self.ready);
        if (!player.collectionLoaded) {
            debug.log("MPD", "Checking Collection");
            collectionHelper.checkCollection(false, false);
        }
    }

    this.initialise = function() {
        $.ajax({
            type: 'GET',
            url: 'player/mpd/geturlhandlers.php',
            dataType: 'json',
            success: initialised,
            error: function(data) {
                debug.error("MPD","Failed to get URL Handlers",data);
                infobar.permerror(language.gettext('error_noplayer'));
            }
        });
    }

    this.ready = function() {
        debug.mark("MPD","Player is ready");
        var t = "Connected to "+getCookie('currenthost')+" ("+prefs.player_backend.capitalize() +
            " at " + player_ip + ")";
        infobar.notify(t);
        self.reloadPlaylists();
    }

	this.do_command_list = function(list, callback) {
        // Note, if you call this with a callback, your callback MUST call player.controller.checkProgress
        $.ajax({
            type: 'POST',
            url: 'player/mpd/postcommand.php',
            data: JSON.stringify(list),
            // contentType of false prevents jQuery from re-encoding our data, where it
            // converts %20 to +, which seems to be a bug in jQuery 3.0
            contentType: false,
            dataType: 'json',
            timeout: 30000,
            success: function(data) {
                if (data) {
                    debug.debug("PLAYER",data);
                    if (data.state) {
                        // Clone the object so as not to leave this closure in memory
                        player.status = cloneObject(data);
                        prefs.radiomode = player.status.radiomode;
                        prefs.radioparam = player.status.radioparam;
                        prefs.radiomaster = player.status.radiomaster;
                        prefs.radioconsume = player.status.radioconsume;
                        if (player.status.playlist !== plversion) {
                            debug.blurt("PLAYER","Player has marked playlist as changed");
                            playlist.repopulate();
                        }
                        plversion = player.status.playlist;
                        infobar.setStartTime(player.status.elapsed);
                        checkStateChange();
                    }
                }
                post_command_list(callback);
            },
            error: function(jqXHR, textStatus, errorThrown) {
                debug.error("MPD","Command List Failed",list,textStatus,errorThrown);
                if (list.length > 0) {
                    infobar.error(language.gettext('error_sendingcommands', [prefs.player_backend]));
                }
                post_command_list(callback);
            }
        });
	}

    function post_command_list(callback) {
        if (callback) {
            callback();
        } else {
           self.checkProgress();
        }
        infobar.updateWindowValues();
    }

    this.isConnected = function() {
        return true;
    }

    this.addStateChangeCallback = function(sc) {
        if (player.status.state == sc.state) {
            sc.callback();
        } else {
            stateChangeCallbacks.push(sc);
        }
    }

    function checkStateChange() {
        for (var i = 0; i < stateChangeCallbacks.length ; i++) {
            if (stateChangeCallbacks[i].state == player.status.state) {
                stateChangeCallbacks[i].callback();
                stateChangeCallbacks.splice(i, 1);
                i--;
            }
        }
    }

	this.reloadPlaylists = function() {
        var openplaylists = [];
        $('#storedplaylists').find('i.menu.openmenu.playlist.icon-toggle-open').each(function() {
            openplaylists.push($(this).attr('name'));
        })
        $.get("player/mpd/loadplaylists.php", function(data) {
            $("#storedplaylists").html(data);
            layoutProcessor.postAlbumActions();
            $('b:contains("'+language.gettext('button_loadplaylist')+'")').parent('.configtitle').append('<a href="https://fatg3erman.github.io/RompR/Using-Saved-Playlists" target="_blank"><i class="icon-info-circled playlisticonr tright"></i></a>');
            for (var i in openplaylists) {
                $('i.menu.openmenu.playlist.icon-toggle-closed[name="'+openplaylists[i]+'"]').click();
            }
            if (openplaylists.length > 0) {
                infobar.markCurrentTrack();
            }
            $('#addtoplaylistmenu').load('player/mpd/loadplaylists.php?addtoplaylistmenu');
        });
	}

	this.loadPlaylist = function(name) {
        self.do_command_list([['load', name]]);
        return false;
	}

    this.loadPlaylistURL = function(name) {
        if (name == '') {
            return false;
        }
        var data = {url: encodeURIComponent(name)};
        $.ajax({
            type: "GET",
            url: "utils/getUserPlaylist.php",
            cache: false,
            data: data,
            dataType: "xml",
            success: function() {
                self.reloadPlaylists();
                self.addTracks([{type: 'remoteplaylist', name: name}], null, null);
            },
            error: function(data, status) {
                playlist.repopulate();
                debug.error("MPD","Failed to save user playlist URL");
            }
        });
        return false;
    }

	this.deletePlaylist = function(name, callback) {
        openpl = null;
        name = decodeURIComponent(name);
        if (callback) {
            self.do_command_list([['rm',name]], callback);
        } else {
    		self.do_command_list([['rm',name]], function() {
                self.reloadPlaylists();
                if (typeof(playlistManager) != 'undefined') {
                    playlistManager.reloadAll();
                }
            });
        }
	}

    this.deleteUserPlaylist = function(name) {
        openpl = null;
        var data = {del: encodeURIComponent(name)};
        $.ajax( {
            type: "GET",
            url: "utils/getUserPlaylist.php",
            cache: false,
            data: data,
            dataType: "xml",
            success: self.reloadPlaylists,
            error: function(data, status) {
                debug.error("MPD","Failed to delete user playlist",name);
            }
        } );
    }

    this.renamePlaylist = function(name, e, callback) {
        openpl = null;
        oldplname = name;
        debug.log("MPD","Renaming Playlist",name,e);
        var fnarkle = new popup({
            css: {
                width: 400,
                height: 300
            },
            title: language.gettext("label_renameplaylist"),
            atmousepos: true,
            mousevent: e
        });
        var mywin = fnarkle.create();
        var d = $('<div>',{class: 'containerbox'}).appendTo(mywin);
        var e = $('<div>',{class: 'expand'}).appendTo(d);
        var i = $('<input>',{class: 'enter', id: 'newplname', type: 'text', size: '200'}).appendTo(e);
        var b = $('<button>',{class: 'fixed'}).appendTo(d);
        b.html('Rename');
        fnarkle.useAsCloseButton(b, callback);
        fnarkle.open();
    }

    this.doRenamePlaylist = function() {
        self.do_command_list([["rename", decodeURIComponent(oldplname), $("#newplname").val()]],
            function() {
                self.reloadPlaylists();
                layoutProcessor.postAlbumActions();
                if (typeof(playlistManager) != "undefined") {
                    playlistManager.reloadAll();
                }
            }
        );
        return true;
    }

    this.doRenameUserPlaylist = function() {
        var data = {rename: encodeURIComponent(oldplname),
                    newname: encodeURIComponent($("#newplname").val())
        };
        $.ajax( {
            type: "GET",
            url: "utils/getUserPlaylist.php",
            cache: false,
            data: data,
            dataType: "xml",
            success: function(data) {
                layoutProcessor.postAlbumActions();
                self.reloadPlaylists();
            },
            error: function(data, status) {
                debug.error("MPD","Failed to rename user playlist",name);
            }
        } );
        return true;
    }

    this.deletePlaylistTrack = function(name,songpos,callback) {
        openpl = name;
        if (!callback) {
            callback = self.checkReloadPlaylists;
        }
        self.do_command_list([['playlistdelete',decodeURIComponent(name),songpos]], callback);
    }

    this.checkReloadPlaylists = function() {
        if (openpl !== null) {
            var string = browsePlaylist(encodeURIComponent(openpl), 'pholder_'+hex_md5(openpl));
            $('#pholder_'+hex_md5(openpl)).load(string);
        }
        if (typeof(playlistManager) != 'undefined') {
            playlistManager.checkToUpdateTheThing(encodeURIComponent(openpl));
        }
        openpl = null;
    }

	this.clearPlaylist = function(callback) {
        // Mopidy does not like removing tracks while they're playing
	    self.do_command_list([['stop'], ['clear']], callback);
	}

	this.savePlaylist = function() {
	    var name = $("#playlistname").val();
	    debug.log("GENERAL","Save Playlist",name);
        if (name == '') {
            return false;
        } else if (name.indexOf("/") >= 0 || name.indexOf("\\") >= 0) {
	        infobar.error(language.gettext("error_playlistname"));
	    } else {
	        self.do_command_list([["save", name]], function() {
	            self.reloadPlaylists();
                if (typeof(playlistManager) != "undefined") {
                    playlistManager.reloadAll();
                }
	            infobar.notify(language.gettext("label_savedpl", [name]));
                $("#plsaver").slideToggle('fast');
                self.checkProgress();
	        });
	    }
	}

	this.getPlaylist = function(reqid) {
        debug.log("PLAYER","Getting playlist using mpd connection");
        $.ajax({
            type: "GET",
            url: "getplaylist.php",
            cache: false,
            dataType: "json",
            success: function(data) {
                playlist.newXSPF(reqid, data);
            },
            error: playlist.updateFailure
        });
	}

	this.play = function() {
        self.do_command_list([['play']]);
	}

	this.pause = function() {
        self.do_command_list([['pause']]);
	}

	this.stop = function() {
        playlist.checkPodcastProgress();
        self.do_command_list([["stop"]], self.onStop);
	}

	this.next = function() {
        playlist.checkPodcastProgress();
        self.do_command_list([["next"]]);
	}

	this.previous = function() {
        playlist.checkPodcastProgress();
        self.do_command_list([["previous"]]);
	}

	this.seek = function(seekto) {
        debug.log("PLAYER","Seeking To",seekto);
        self.do_command_list([["seek", player.status.song, parseInt(seekto.toString())]]);
	}

	this.playId = function(id) {
        playlist.checkPodcastProgress();
        self.do_command_list([["playid",id]]);
	}

	this.playByPosition = function(pos) {
        playlist.checkPodcastProgress();
        self.do_command_list([["play",pos.toString()]]);
	}

	this.volume = function(volume, callback) {
        self.do_command_list([["setvol",parseInt(volume.toString())]], callback);
        return true;
	}

	this.removeId = function(ids) {
		var cmdlist = [];
		$.each(ids, function(i,v) {
			cmdlist.push(["deleteid", v]);
		});
		self.do_command_list(cmdlist);
	}

	this.toggleRandom = function() {
	    var new_value = (player.status.random == 0) ? 1 : 0;
	    self.do_command_list([["random",new_value]], function() {
            playlist.doUpcomingCrap();
            self.checkProgress();
        });
	}

	this.toggleCrossfade = function() {
	    var new_value = (player.status.xfade === undefined || player.status.xfade === null ||
            player.status.xfade == 0) ? prefs.crossfade_duration : 0;
	    self.do_command_list([["crossfade",new_value]]);
	}

	this.setCrossfade = function(v) {
	    self.do_command_list([["crossfade",v]]);
	}

	this.toggleRepeat = function() {
	    var new_value = (player.status.repeat == 0) ? 1 : 0;
	    self.do_command_list([["repeat",new_value]]);
	}

	this.toggleConsume = function() {
	    var new_value = (player.status.consume == 0) ? 1 : 0;
	    self.do_command_list([["consume",new_value]]);
	}

    this.checkConsume = function(state) {
        debug.log("PLAYER","Checking Consume",state);
        self.do_command_list([["consume",state]]);
    }

    this.takeBackControl = function(v) {
        self.do_command_list([["repeat",0],["random", 0],["consume", 1]]);
    }

	this.addTracks = function(tracks, playpos, at_pos) {
        var abitofahack = true;
        layoutProcessor.notifyAddTracks();
		debug.log("MPD","Adding Tracks",tracks,playpos,at_pos);
		var cmdlist = [];
		$.each(tracks, function(i,v) {
			switch (v.type) {
				case "uri":
                    if (prefs.cdplayermode && at_pos === null && !playlist.radioManager.isRunning()) {
                        cmdlist.push(['addtoend', v.name]);
                    } else {
    				    cmdlist.push(['add',v.name]);
                    }
    				break;
                case "playlist":
				case "cue":
    				cmdlist.push(['load',v.name]);
    				break;
    			case "item":
    				cmdlist.push(['additem',v.name]);
    				break;
                case "artist":
                    cmdlist.push(['addartist',v.name]);
                    break;
                case "stream":
                    cmdlist.push(['loadstreamplaylist',v.url,v.image,v.station]);
                    break;
                case "playlisttoend":
                    cmdlist.push(['playlisttoend',v.playlist,v.frompos]);
                    break;
                case "resumepodcast":
                    cmdlist.push(['resume', v.uri, v.resumefrom, v.pos]);
                    playpos = null;
                    abitofahack = false;
                    break;
                case 'remoteplaylist':
                    cmdlist.push(['addremoteplaylist', v.name]);
                    break;
    		}
		});
		// Note : playpos will only be set if at_pos isn't, because at_pos is only set when dragging to the playlist
        if (prefs.cdplayermode && at_pos === null && !playlist.radioManager.isRunning()) {
            cmdlist.unshift(["clear"]);
            cmdlist.unshift(["stop"]);
            if (abitofahack) {
                // Don't add the play command if we're doing a resume,
                // because postcommand.php will add it and this will override it
                cmdlist.push(['play']);
            }
        } else if (playpos !== null) {
			cmdlist.push(['play', playpos.toString()]);
		}
        if (at_pos === 0 || at_pos) {
            cmdlist.push(['moveallto', at_pos]);
        }
		self.do_command_list(cmdlist);
	}

	this.move = function(first, num, moveto) {
		var itemstomove = first.toString();
		if (num > 1) {
			itemstomove = itemstomove + ":" + (parseInt(first)+parseInt(num));
		}
        if (itemstomove == moveto) {
            // This can happen if you drag the final track from one album to a position below the
            // next album's header but before its first track. This doesn't change its position in
            // the playlist but the item in the display will have moved and we need to move it back.
            playlist.repopulate();
        } else {
		    debug.log("PLAYER", "Move command is move&arg="+itemstomove+"&arg2="+moveto);
		    self.do_command_list([["move",itemstomove,moveto]]);
        }
	}

	this.stopafter = function() {
        var cmds = [];
        if (player.status.repeat == 1) {
            cmds.push(["repeat", 0]);
        }
        cmds.push(["single", 1]);
        self.do_command_list(cmds);
	}

	this.cancelSingle = function() {
		self.do_command_list([["single",0]]);
	}

    this.doOutput = function(id) {
        state = $('#outputbutton_'+id).is(':checked');
        if (state) {
            self.do_command_list([["disableoutput",id]]);
        } else {
            self.do_command_list([["enableoutput",id]]);
        }
    }

    this.doMute = function() {
        if (prefs.player_backend == "mopidy") {
            if ($("#mutebutton").hasClass('icon-output-mute')) {
                $("#mutebutton").removeClass('icon-output-mute').addClass('icon-output');
                self.do_command_list([["disableoutput", 0]]);
            } else {
                $("#mutebutton").removeClass('icon-output').addClass('icon-output-mute');
                self.do_command_list([["enableoutput", 0]]);
            }
        } else {
            if ($("#mutebutton").hasClass('icon-output-mute')) {
                $("#mutebutton").removeClass('icon-output-mute').addClass('icon-output');
                self.do_command_list([["enableoutput", 0]]);
            } else {
                $("#mutebutton").removeClass('icon-output').addClass('icon-output-mute');
                self.do_command_list([["disableoutput", 0]]);
            }
        }
    }

    this.search = function(command) {
        if (player.updatingcollection) {
            infobar.notify(language.gettext('error_nosearchnow'));
            return false;
        }
        var terms = {};
        var termcount = 0;
        lastsearchcmd = command;
        $("#collectionsearcher").find('.searchterm').each( function() {
            var key = $(this).attr('name');
            var value = $(this).val();
            if (value != "") {
                debug.trace("PLAYER","Searching for",key, value);
                terms[key] = value.split(',');
                termcount++;
            }
        });
        if ($('[name="searchrating"]').val() != "") {
            terms['rating'] = $('[name="searchrating"]').val();
            termcount++;
        }
        var domains = new Array();
        if (prefs.search_limit_limitsearch && $('#mopidysearchdomains').length > 0) {
            // The second term above is just in case we're swapping between MPD and Mopidy
            // - it prevents an illegal invocation in JQuery if limitsearch is true for MPD
            domains = $("#mopidysearchdomains").makeDomainChooser("getSelection");
        }
        if (termcount > 0) {
            $("#searchresultholder").empty();
            doSomethingUseful('searchresultholder', language.gettext("label_searching"));
            var st = {
                command: command,
                resultstype: prefs.displayresultsas,
                domains: domains,
                dump: collectionHelper.collectionKey('b')
            };
            debug.log("PLAYER","Doing Search:",terms,st);
            if ((termcount == 1 && (terms.tag || terms.rating)) ||
                (termcount == 2 && (terms.tag && terms.rating)) ||
                (prefs.player_backend == 'mopidy' && prefs.searchcollectiononly) ||
                ((terms.tag || terms.rating) && !(terms.genre || terms.composer || terms.performer || terms.any))) {
                // Use the sql search engine if we're looking only for things it supports
                debug.log("PLAYER","Searching using database search engine");
                st.terms = terms;
            } else {
                st.mpdsearch = terms;
            }
            $.ajax({
                    type: "POST",
                    url: "albums.php",
                    data: st,
                    success: function(data) {
                        $("#searchresultholder").html(data);
                        collectionHelper.scootTheAlbums($("#searchresultholder"));
                        layoutProcessor.postAlbumActions();
                        data = null;
                    }
            });
        }
    }

    this.reSearch = function() {
        player.controller.search(lastsearchcmd);
    }

    this.rawsearch = function(terms, sources, exact, callback, checkdb) {
        if (player.updatingcollection) {
            infobar.notify(language.gettext('error_nosearchnow'));
            callback([]);
        }
        $.ajax({
                type: "POST",
                url: "albums.php",
                dataType: 'json',
                data: {
                    rawterms: terms,
                    domains: sources,
                    command: exact ? "find" : "search",
                    checkdb: checkdb
                },
                success: function(data) {
                    callback(data);
                    data = null;
                },
                error: function(data) {
                    callback([]);
                    data = null;
                }
        });
    }

	this.postLoadActions = function() {
		self.checkProgress();
        if (thenowplayinghack) {
            // The Now PLaying Hack is so that when we switch the option for
            // 'display composer/performer in nowplaying', we can first reload the
            // playlist (to get the new artist metadata keys from the backend)
            // and then FORCE nowplaying to accept a new track with the same backendid
            // as the previous - this forces the nowplaying info to update
            thenowplayinghack = false;
            nowplaying.newTrack(playlist.getCurrentTrack(), true);
        }
	}

    this.doTheNowPlayingHack = function() {
        debug.log("MPD","Doing the nowplaying hack thing");
        thenowplayinghack = true;
        playlist.repopulate();
    }

    function clearProgressTimer() {
        clearTimeout(progresstimer);
    }

    this.checkProgress = function() {
        clearProgressTimer();
        // Track changes are detected based on the playlist id. This prevents us from repopulating
        // the browser every time the playlist gets repopulated.
        if (player.status.songid !== previoussongid) {
            debug.mark("MPD","Track has changed");
            playlist.trackHasChanged(player.status.songid);
            previoussongid = player.status.songid;
            safetytimer = 500;
        }

        var progress = infobar.progress();
        playlist.setCurrent({progress: progress});
        var duration = playlist.getCurrent('Time') || 0;
        infobar.setProgress(progress,duration);

        if (player.status.state == "play") {
            if (duration > 0 && progress >= duration) {
                setTheClock(self.checkchange, safetytimer);
                if (safetytimer < 5000) { safetytimer += 500 }
            } else {
                AlanPartridge++;
                if (AlanPartridge < 5) {
                    setTheClock( self.checkProgress, 1000);
                } else {
                    AlanPartridge = 0;
                    setTheClock( self.checkchange, 1000);
                }
            }
        } else {
            setTheClock(self.checkchange, 10000);
        }
    }

    this.checkchange = function() {
        clearProgressTimer();
        // Update the status to see if the track has changed
        if (playlist.getCurrent('type') == "stream") {
            self.do_command_list([], self.checkStream);
        } else {
            self.do_command_list([], null);
        }
    }

    this.checkStream = function() {
        updateStreamInfo();
        self.checkProgress();
    }

    this.onStop = function() {
        infobar.setProgress(0,-1,-1);
        self.checkProgress();
    }

    this.replayGain = function(event) {
        var x = $(event.target).attr("id").replace('replaygain_','');
        debug.log("MPD","Setting Replay Gain to",x);
        self.do_command_list([["replay_gain_mode",x]]);
    }

    this.addTracksToPlaylist = function(playlist,tracks,moveto,playlistlength,callback) {
        debug.log("PLAYER","Adding tracks to playlist",playlist,"then moving to",moveto,"playlist length is",playlistlength);
        var cmds = new Array();
        for (var i in tracks) {
            if (tracks[i].uri) {
                cmds.push(['playlistadd',decodeURIComponent(playlist),tracks[i].uri,
                    moveto,playlistlength]);
            } else if (tracks[i].dir) {
                cmds.push(['playlistadddir',decodeURIComponent(playlist),tracks[i].dir,
                    moveto,playlistlength]);
            }
        }
        self.do_command_list(cmds,callback);
    }

    this.movePlaylistTracks = function(playlist,from,to,callback) {
        var cmds = new Array();
        cmds.push(['playlistmove',decodeURIComponent(playlist),from,to]);
        self.do_command_list(cmds,callback);
    }

}
