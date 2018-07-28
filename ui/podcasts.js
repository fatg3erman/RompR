var podcasts = function() {

	var downloadQueue = new Array();
	var downloadRunning = false;
	var loaded = false;
	var refreshtimer;

	function checkDownloadQueue() {
		if (downloadRunning == false) {
			var newTrack = downloadQueue.shift();
			if (newTrack) {
				downloadRunning = true;
				var track = newTrack.track;
				var channel = newTrack.channel;
		    	$('[name="podgroupload_'+channel+'"]').makeFlasher().removeClass('podgroupload');
				var monitor = new podcastDownloadMonitor(track, channel);
			    $.ajax( {
			        type: "GET",
			        url: "includes/podcasts.php",
			        cache: false,
			        contentType: "text/html; charset=utf-8",
			        data: {downloadtrack: track, channel: channel, populate: 1 },
			        timeout: 360000,
			        success: function(data) {
			            monitor.stop(false);
						updatePodcastDropdown(channel, data);
			            doDummyProgressBars();
			            downloadRunning = false;
				    	$('[name="podgroupload_'+channel+'"]').stopFlasher().removeClass('podgroupload').addClass('podgroupload');
			            checkDownloadQueue();
			        },
			        error: function(data, status) {
			            monitor.stop(true);
			            debug.error("PODCASTS", "Podcast Download Failed!",data,status);
			            downloadRunning = false;
				    	$('[name="podgroupload_'+channel+'"]').stopFlasher().removeClass('podgroupload').addClass('podgroupload');
			            checkDownloadQueue();
			        }
			    });
			} else {
		    	$('[name^="podgroupdownload_"]').stopFlasher();
			}
		}
	}

	function podcastDownloadMonitor(track, channel) {

	    var self = this;
	    var progressdiv = $('i[name="poddownload_'+track+'"]').parent();
	    progressdiv.html('<div class="fullwidth"></div>');
	    progressdiv.rangechooser({range: 100, startmax: 0, interactive: false});
	    var timer;
	    var running = true;

	    this.checkProgress = function() {
	        $.ajax( {
	            type: "GET",
	            url: "utils/checkpodcastdownload.php",
	            cache: false,
	            dataType: "json",
	            success: function(data) {
	                progressdiv.rangechooser('setProgress', data.percent);
	                debug.log("PODCAST DOWNLOAD","Download status is",data);
	                if (running) {
	                    timer = setTimeout(self.checkProgress, 500);
	                }
	            },
	            error: function() {
	                infobar.notify(infobar.ERROR, "Something went wrong checking the download progress!");
	            }
	        });
	    }

	    this.stop = function(error) {
	        running = false;
	        clearTimeout(timer);
			if (error) {
				progressdiv.replaceWith('<div class="fullwidth">Download Failed</div>');
			}
	    }

	    timer = setTimeout(self.checkProgress, 1000);
	}

	function doDummyProgressBars() {
		for(var i = 0; i < downloadQueue.length; i++) {
			var track = downloadQueue[i].track;
			debug.trace("PODCAST DOWNLOAD","Putting Dummy Progress Bar in",track);
		    $('i[name="poddownload_'+track+'"]').makeSpinner();
		}
	}

	function makeSearchWork(event) {
		event.preventDefault();
		event.stopPropagation();
		var position = getPosition(event);
		var elemright = $(event.target).width() + $(event.target).offset().left;
		if (position.x > elemright - 24) {
			$(event.target).val("");
			var thing = $(event.target).attr('name').replace(/podsearcher_/,'');
			podcasts.searchinpodcast(thing);
		}
	}

	function putPodCount(element, num, numl) {
		debug.log("PODCASTS","Updating counts",element,num,numl);
		var indicator = $(element);
		if (num == 0) {
			indicator.removeClass('newpod');
			indicator.html("");
		} else {
			indicator.html(num);
			if (!indicator.hasClass('newpod')) {
				indicator.addClass('newpod');
			}
		}
		var il = indicator.next();
		if (numl == 0) {
			il.removeClass('unlistenedpod');
			il.html("");
		} else {
			il.html(numl);
			if (!il.hasClass('unlistenedpod')) {
				il.addClass('unlistenedpod');
			}
		}
	}

	function podcastRequest(options, callback) {
		debug.log("PODCASTS","Sending request",options);
		options.populate = 1;
		if (options.channel) {
			var term = $('[name="podsearcher_'+options.channel+'"]').val();
			if (typeof term !== 'undefined' && term != '') {
				options.searchterm = encodeURIComponent(term);
			}
		}
	    $.ajax( {
	        type: "GET",
	        url: "includes/podcasts.php",
	        cache: false,
	        data: options,
			contentType: 'application/json',
	        success: function(data) {
				if (data && data.length > 0) {
					$.each(data, function(index, value) {
						if (!($('#podcast_'+value).is(':empty'))) {
							debug.log("PODCASTS","Podcast",value,"was updated and is loaded - reloading it");
							podcasts.loadPodcast(value);
						}
					});
					podcasts.doNewCount();
				}
	            if (callback !== null) {
	            	callback();
	            }
	        },
	        error: function(data, status) {
	            debug.error("PODCASTS", "Podcast Request Failed:",options,data,status);
	            infobar.notify(infobar.ERROR,language.gettext("label_general_error"));
				if (callback !== null) {
	            	callback();
	            }
	        }
	    });
	}

	function updatePodcastDropdown(channel, html) {
		var target = $('#podcast_'+channel);
		if (html !== null) {
			target.html(html);
		}
		$('i[name="podcast_'+channel+'"]').stopSpinner();
		target.find('.fridge').tipTip({delay: 500, edgeOffset: 8});
		target.find('.clearbox').on('click', makeSearchWork).on('mouseenter',makeHoverWork).on('mousemove', makeHoverWork).on('keyup', onKeyUp);
		target.find('input.resumepos').each(function() {
			var pos = parseInt($(this).val());
			var duration = parseInt($(this).next().val());
			debug.log("PODCASTS", "Episode has a progress bar",pos,duration);
			var thething = $(
				'<div>',
				{
					class: 'containerbox fullwidth playlistrow2 dropdown-container podcastresume clickable clickicon',
					name: $(this).prev().attr('name')
				}
			).insertBefore($(this));
			thething.append('<div class="fixed padright">'+language.gettext('label_resume')+'</div>');
			var bar = $('<div>', {class: 'expand', style: "height: 0.5em"}).appendTo(thething);
			bar.rangechooser({range: duration, startmax: pos/duration, interactive: false});
		});
		infobar.markCurrentTrack();
		layoutProcessor.postAlbumActions( $('#podcast_'+channel));
	}

	return {

		getPodcast: function(url, callback) {
		    debug.log("PODCAST","Getting podcast",url);
			if (!callback) {
			    infobar.notify(infobar.NOTIFY, "Subscribing to Podcast....")
			    doSomethingUseful('cocksausage', language.gettext("label_downloading"));
			}
		    $.ajax( {
		        type: "GET",
		        url: "includes/podcasts.php",
		        cache: false,
		        contentType: "text/html; charset=utf-8",
		        data: {url: encodeURIComponent(url), populate: 1 },
		        success: function(data) {
					if (callback) {
						callback(true);
					} else {
			            $("#fruitbat").html(data);
			            $("#fruitbat .fridge").tipTip({delay: 500, edgeOffset: 8});
			            infobar.notify(infobar.NOTIFY, "Subscribed to Podcast");
			            podcasts.doNewCount();
						$('#spinner_cocksausage').remove();
						layoutProcessor.postAlbumActions($('#fruitbat'));
					}
		        },
		        error: function(data, status, thing) {
					if (callback) {
						callback(false);
					} else {
		            	infobar.notify(infobar.ERROR, "Failed to Subscribe to Podcast : "+data.responseText);
		            	$('#spinner_cocksausage').remove();
					}
		        }
		    } );
		},

		reloadList: function() {
			$.ajax( {
		        type: "GET",
		        url: "includes/podcasts.php",
		        cache: false,
		        contentType: "text/html; charset=utf-8",
		        data: {populate: 1 },
		        success: function(data) {
		            $("#fruitbat").html(data);
		            $("#fruitbat .fridge").tipTip({delay: 500, edgeOffset: 8});
		            podcasts.doNewCount();
					layoutProcessor.postAlbumActions($('#fruitbat'));
		        },
		        error: function(data, status, thing) {
	            	infobar.notify(infobar.ERROR, "Failed to load podcasts list : "+data.responseText);
		        }
		    });
		},

		loadPodcast: function(channel) {
			var target = $('#podcast_'+channel);
			var uri = "includes/podcasts.php?populate=1&loadchannel="+channel;
			var term = $('[name="podsearcher_'+channel+'"]').val();
			var configvisible = target.find('.podconfigpanel').is(':visible');
			if (typeof term !== 'undefined' && term != '') {
				uri += '&searchterm='+encodeURIComponent(term);
			}
			$('i[name="podcast_'+channel+'"]').makeSpinner();
			target.load(uri, function() {
				if (configvisible) {
					target.find('.podconfigpanel').show();
				}
				target.removeClass('loaded').addClass('loaded');
				updatePodcastDropdown(channel, null);
			});
		},

    	searchinpodcast: function(channel) {
    		var term = $('[name="podsearcher_'+channel+'"]').val();
    		debug.log("PODCASTS","Searching podcast",channel,'for',term);
    		podcasts.loadPodcast(channel);
    	},

		doPodcast: function(input) {
		    var url = $("#"+input).val();
		    podcasts.getPodcast(url);
		},

		handleDrop: function() {
    		setTimeout(function() { podcasts.doPodcast('podcastsinput') }, 1000);
    	},

    	channelAction: function(channel, action) {
    		debug.mark("PODCAST","Action",action," on podcast ",channel);
    		var data = {populate: 1};
    		data[action] = channel;
			data.channel = channel;
			$('.podaction[name="'+action+'_'+channel+'"]').makeSpinner();
			podcastRequest(data, function() {
				$('.podaction[name="'+action+'_'+channel+'"]').stopSpinner();
			});
    	},

		removePodcastTrack: function(track, channel) {
		    debug.log("PODCAST","Removing track",track,"from channel",channel);
		    podcastRequest({removetrack: track, channel: channel },null);
		},

		markEpisodeAsListened: function(track, channel) {
		    debug.log("PODCAST","Marking track",track,"from channel",channel,"as listened");
		    podcastRequest({markaslistened: track, channel: channel },null);
		},

		downloadPodcast: function(track, channel) {
		    debug.mark("PODCAST","Downloading track",track,"from channel",channel);
		    downloadQueue.push({track: track, channel: channel});
		    doDummyProgressBars();
		    checkDownloadQueue();
		},

		downloadPodcastChannel: function(channel) {
            $("#podcast_"+channel).find('.poddownload').click();
		},

		checkMarkPodcastAsListened: function(file) {
			podcastRequest({listened: encodeURIComponent(file), populate: 1}, null);
		},

		doNewCount: function() {
			$.getJSON("includes/podcasts.php?populate=1&getcounts=1", function(data) {
				$.each(data, function(index, value) {
					var element;
					if (index == 'totals') {
						element = '#total_unlistened_podcasts';
					} else {
						element = '#podnumber_'+index;
					}
					putPodCount(element, value.new, value.unlistened)
				});
				layoutProcessor.postAlbumActions();
			});
		},

		changeOption: function(event) {
			var element = $(event.target);
			var elementType = element[0].tagName;
			var options = {option: element.attr("name")};
			debug.log("PODCASTS","Option:",element,elementType);
			switch(elementType) {
				case "SELECT":
					options.val = element.val();
					break;
				case "LABEL":
					options.val = !element.prev().is(':checked');
					break;
			}
			while(!element.hasClass('toggledown')) {
				element = element.parent();
			}
			var channel = element.attr('id');
			options.channel = channel.replace(/podconf_/,'');
			podcastRequest(options, null);
		},

		checkRefresh: function() {
			clearTimeout(refreshtimer);
			$.ajax({
				type: 'GET',
				url: "includes/podcasts.php?populate=1&checkrefresh=1",
				timeout: prefs.collection_load_timeout,
				dataType: 'JSON',
				success: function(data) {
					debug.log("PODCASTS","Refresh result",data);
					if (data.updated && data.updated.length > 0) {
						if (loaded) {
							$.each(data.updated, function(index, value){
								if (!($('#podcast_'+value).hasClass('loaded'))) {
									debug.log("PODCASTS","Podcast",value,"was refreshed and is loaded - reloading it");
									podcasts.loadPodcast(value);
								}
							});
						}
						podcasts.doNewCount();
					}
					if (data.nextupdate) {
						debug.log("PODCASTS","Setting next podcast refresh for",data.nextupdate,'seconds');
						refreshtimer = setTimeout(podcasts.checkRefresh, data.nextupdate*1000);
					}
				},
				error: function() {
					debug.error("PODCASTS","Refresh Failed");
					infobar.notify(infobar.ERROR, "Podcast Refresh Failed");
				}
			})
		},

		removePodcast: function(name) {
		    debug.log("PODCAST","Removing podcast",name);
		    $.ajax( {
		        type: "GET",
		        url: "includes/podcasts.php",
		        cache: false,
		        contentType: "text/html; charset=utf-8",
		        data: {remove: name, populate: 1 },
		        success: function(data) {
		            $("#fruitbat").html(data);
		            $("#fruitbat .fridge").tipTip({delay: 500, edgeOffset: 8});
		            podcasts.doNewCount();
					layoutProcessor.postAlbumActions();
		        },
		        error: function(data, status) {
		            infobar.notify(infobar.ERROR, language.gettext("podcast_remove_error"));
		        }
		    } );
		},

		doInitialRefresh: function() {
			clearTimeout(refreshtimer);
			refreshtimer = setTimeout(podcasts.checkRefresh, 10000);
		},

		search: function() {
			$('#podcast_search').empty();
		    doSomethingUseful('podcast_search', language.gettext("label_searching"));
			var term = $('#podcastsearch').val();
		    $.ajax( {
		        type: "GET",
		        url: "includes/podcasts.php",
		        cache: false,
		        contentType: "text/html; charset=utf-8",
		        data: {search: encodeURIComponent(term), populate: 1 },
		        success: function(data) {
		            $("#podcast_search").html(data);
		            $('#podcast_search').prepend('<div class="menuitem containerbox padright brick_wide sensiblebox"><div class="configtitle textcentre expand"><b>Search Results for &quot;'+term+'&quot;</b></div><i class="clickable clickicon podicon icon-cancel-circled removepodsearch fixed"></i></div>');
		            $("#podcast_search .fridge").tipTip({delay: 500, edgeOffset: 8});
					layoutProcessor.postAlbumActions($('#podcast_search'));
		        },
		        error: function(data, status, thing) {
		            infobar.notify(infobar.ERROR, "Search Failed : "+data.responseText);
		            $('#spinner_podcast_search').remove();
		        }
		    } );
		},

		clearsearch: function() {
			$('#podcast_search').empty();
			$('#podsclear').hide();
		},

		subscribe: function(index, clickedElement) {
			clickedElement.makeSpinner().removeClass('clickable');
		    $.ajax( {
		        type: "GET",
		        url: "includes/podcasts.php",
		        cache: false,
		        contentType: "text/html; charset=utf-8",
		        data: {subscribe: index, populate: 1 },
		        success: function(data) {
					uiHelper.postPodcastSubscribe(data, index);
				},
		        error: function(data, status, thing) {
		            infobar.notify(infobar.ERROR, "Subscribe Failed : "+data.responseText);
		            $('#spinner_cocksausage').remove();
		        }
		    } );
		},

		removeSearch: function() {
			$('#podcast_search').empty();
			layoutProcessor.postAlbumActions();
		},

		toggleButtons: function() {
			$("#podcastbuttons").slideToggle('fast');
		    var p = !prefs.podcastcontrolsvisible;
		    prefs.save({ podcastcontrolsvisible: p });
		    return false;
		},

		storePlaybackProgress: function(track) {
			podcastRequest({setprogress: track.progress, track: encodeURIComponent(track.uri)}, null);
		},

		globalAction: function(thing, el) {
			el.makeSpinner();
			var options = new Object;
			options[thing] = 1;
			podcastRequest(options, function() {
				el.stopSpinner();
				podcasts.reloadList()
			});
		}

	}

}();

$('#podcastsinput').on('drop', podcasts.handleDrop)
menuOpeners['podcast'] = podcasts.loadPodcast;
podcasts.doInitialRefresh();
