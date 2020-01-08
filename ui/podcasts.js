var podcasts = function() {

	var downloadQueue = new Array();
	var downloadRunning = false;
	var refreshtimer;
	var onlineTriggerActivated = false;
	var newcounts = {};

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
					url: "podcasts/podcasts.php",
					cache: false,
					contentType: "text/html; charset=utf-8",
					data: {downloadtrack: track, channel: channel, populate: 1 },
					timeout: 360000
				})
				.done(function(data) {
					monitor.stop(false);
					updatePodcastDropdown(channel, data);
					doDummyProgressBars();
					downloadRunning = false;
					$('[name="podgroupload_'+channel+'"]').stopFlasher().removeClass('podgroupload').addClass('podgroupload');
					checkDownloadQueue();
				})
				.fail(function(data, status) {
					monitor.stop(true);
					debug.error("PODCASTS", "Podcast Download Failed!",data,status);
					downloadRunning = false;
					$('[name="podgroupload_'+channel+'"]').stopFlasher().removeClass('podgroupload').addClass('podgroupload');
					checkDownloadQueue();
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
				dataType: "json"
			})
			.done(function(data) {
				progressdiv.rangechooser('setProgress', data.percent);
				debug.debug("PODCAST DOWNLOAD","Download status is",data);
				if (running) {
					timer = setTimeout(self.checkProgress, 500);
				}
			})
			.fail(function() {
				infobar.error(language.gettext('error_dlpfail'));
			});
		}

		this.stop = function(error) {
			running = false;
			clearTimeout(timer);
			if (error) {
				progressdiv.replaceWith('<div class="fullwidth">'+language.gettext('error_dlfailed')+'</div>');
			}
		}

		timer = setTimeout(self.checkProgress, 1000);
	}

	function doDummyProgressBars() {
		for(var i = 0; i < downloadQueue.length; i++) {
			var track = downloadQueue[i].track;
			debug.debug("PODCAST DOWNLOAD","Putting Dummy Progress Bar in",track);
			$('i[name="poddownload_'+track+'"]').makeSpinner();
		}
	}

	function putPodCount(element, num, numl) {
		debug.trace("PODCASTS","Updating counts",element,num,numl);
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

	function checkForUpdatedPodcasts(data) {
		debug.log('PODCASTS', 'Checking for updated podcasts');
		debug.debug('PODCASTS', data);
		if (data && data.length > 0) {
			$.each(data, function(index, value) {
				if ($('#podcast_'+value).hasClass('loaded')) {
					debug.log("PODCASTS","Podcast",value,"was updated and is loaded - reloading it");
					podcasts.loadPodcast(value);
				}
			});
		}
	}

	function podcastRequest(options, callback) {
		debug.debug("PODCASTS","Sending request",options);
		options.populate = 1;
		if (options.channel) {
			var term = $('[name="podsearcher_'+options.channel+'"]').val();
			if (typeof term !== 'undefined' && term != '') {
				options.searchterm = encodeURIComponent(term);
			}
		}
		$.ajax({
			type: "GET",
			url: "podcasts/podcasts.php",
			cache: false,
			data: options,
			contentType: 'application/json'
		})
		.done(function(data) {
			checkForUpdatedPodcasts(data);
			podcasts.doNewCount();
			if (callback !== null) {
				callback();
			}
		})
		.fail(function(data,status) {
			debug.error("PODCASTS", "Podcast Request Failed:",data,options);
			if (data.status == 412) {
				infobar.error(language.gettext('label_refreshinprogress'));
			} else {
				infobar.error(language.gettext("label_general_error"));
			}
			if (callback !== null) {
				callback();
			}
		});
	}

	function updatePodcastDropdown(channel, html) {
		var target = $('#podcast_'+channel);
		if (html !== null) {
			target.html(html);
		}
		$('i[name="podcast_'+channel+'"]').stopSpinner();
		uiHelper.makeResumeBar(target);
		infobar.markCurrentTrack();
	}

	return {

		getPodcast: function(url, callback) {
			debug.log("PODCAST","Getting podcast",url);
			if (!callback) {
				infobar.notify(language.gettext('label_subscribing'))
				doSomethingUseful('cocksausage', language.gettext("label_downloading"));
			}
			$.ajax( {
				type: "GET",
				url: "podcasts/podcasts.php",
				cache: false,
				contentType: "text/html; charset=utf-8",
				data: {url: encodeURIComponent(url), populate: 1 }
			})
			.done(function(data) {
				if (callback) {
					callback(true);
				} else {
					$("#fruitbat").html(data);
					infobar.notify(language.gettext('label_subscribed'));
					podcasts.doNewCount();
					$('#spinner_cocksausage').remove();
					uiHelper.doThingsAfterDisplayingListOfAlbums($('#fruitbat'));
				}
			})
			.fail(function(data, status, thing) {
				if (callback) {
					callback(false);
				} else {
					infobar.error(language.gettext('error_subfail', [data.responseText]));
					$('#spinner_cocksausage').remove();
				}
			});
		},

		reloadList: function() {
			$.ajax( {
				type: "GET",
				url: "podcasts/podcasts.php",
				cache: false,
				contentType: "text/html; charset=utf-8",
				data: {populate: 1 }
			})
			.done(function(data) {
				$("#fruitbat").html(data);
				podcasts.doNewCount();
				uiHelper.doThingsAfterDisplayingListOfAlbums($('#fruitbat'));
			})
			.fail(function(data, status, thing) {
				infobar.error(language.gettext('error_plfail', [data.responseText]));
			});
		},

		loadPodcast: function(channel) {
			var target = $('#podcast_'+channel);
			debug.log("PODCASTS","Loading Podcast",target);
			var uri = "podcasts/podcasts.php?populate=1&loadchannel="+channel;
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
				// Needed for phone skin
				uiHelper.postAlbumMenu();
			});
		},

		searchinpodcast: function(channel) {
			var term = $('[name="podsearcher_'+channel+'"]').val();
			debug.log("PODCASTS","Searching podcast",channel,'for',term);
			podcasts.loadPodcast(channel);
		},

		doPodcast: function(input) {
			var url = $("#"+input).val();
			if (url != '') {
				podcasts.getPodcast(url);
			}
		},

		handleDrop: function() {
			setTimeout(function() { podcasts.doPodcast('podcastsinput') }, 1000);
		},

		channelAction: function(channel, action) {
			debug.log("PODCAST","Action",action," on podcast ",channel);
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

		markEpisodeAsUnlistened: function(track, channel) {
			debug.log("PODCAST","Marking track",track,"from channel",channel,"as unlistened");
			podcastRequest({markasunlistened: track, channel: channel },null);
		},

		downloadPodcast: function(track, channel) {
			debug.log("PODCAST","Downloading track",track,"from channel",channel);
			downloadQueue.push({track: track, channel: channel});
			doDummyProgressBars();
			checkDownloadQueue();
		},

		downloadPodcastChannel: function(channel) {
			$("#podcast_"+channel).find('.poddownload').trigger('click');
		},

		checkMarkPodcastAsListened: function(file) {
			podcastRequest({listened: encodeURIComponent(file), populate: 1}, null);
		},

		checkForEpisode: function(track) {
			$.each(track, function(i, v) {
				track[i] = encodeURIComponent(v);
			});
			track.checklistened = 1;
			track.populate = 1;
			podcastRequest(track, null);
		},

		doNewCount: function() {
			$.getJSON("podcasts/podcasts.php?populate=1&getcounts=1", function(data) {
				debug.debug('PODCASTS','Got New Counts',data);
				newcounts = data;
				$.each(data, function(index, value) {
					if (index == 'totals') {
						// element = '#total_unlistened_podcasts';
					} else {
						putPodCount('#podnumber_'+index, value.new, value.unlistened)
					}
				});
			});
		},

		checkIfSomeoneElseHasUpdatedStuff: function() {
			var isnewpodcast = false;
			var to_reload = new Array();
			$.getJSON("podcasts/podcasts.php?populate=1&getcounts=1", function(data) {
				$.each(data, function(index, value) {
					if (!newcounts.hasOwnProperty(index)) {
						debug.info('PODCASTS', 'A new podcast has been subscribed to by somebody else');
						isnewpodcast = true;
					}  else if (newcounts[index].new != value.new || newcounts[index].unlistened != value.unlistened) {
						if (index != 'totals') {
							debug.info('PODCASTS', 'Podcast',index,'was updated by someobody else');
							to_reload.push(index);
						}
					}
				});
				if (isnewpodcast) {
					podcasts.reloadList();
				} else {
					checkForUpdatedPodcasts(to_reload);
				}
			});
		},

		changeOption: function(event) {
			var element = $(event.target);
			var elementType = element[0].tagName;
			var options = {option: element.attr("name")};
			var callback = null;
			debug.trace("PODCASTS","Option:",element,elementType);
			switch(elementType) {
				case "SELECT":
					options.val = element.val();
					if (options.option == 'RefreshOption') {
						callback = podcasts.checkRefresh;
					}
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
			podcastRequest(options, callback);
		},

		checkRefresh: function() {
			debug.debug('PODCASTS', 'In checkRefresh');
			clearTimeout(refreshtimer);
			$.ajax({
				type: 'GET',
				url: "podcasts/podcasts.php?populate=1&checkrefresh=1",
				timeout: prefs.collection_load_timeout,
				dataType: 'JSON'
			})
			.done(function(data) {
				debug.debug("PODCASTS","Refresh result",data);
				checkForUpdatedPodcasts(data.updated);
				podcasts.doNewCount();
				if (data.nextupdate) {
					debug.log("PODCASTS","Setting next podcast refresh for",data.nextupdate,'seconds');
					refreshtimer = setTimeout(podcasts.checkRefresh, data.nextupdate*1000);
				}
				if (!onlineTriggerActivated) {
					window.addEventListener('online', podcasts.checkIfSomeoneElseHasUpdatedStuff);
					onlineTriggerActivated = true;
				}
				startBackgroundInitTasks.doNextTask();
			})
			.fail(function(data,status,thing) {
				debug.error("PODCASTS","Refresh Failed with status",data.status);
				if (data.status == 412) {
					podcasts.doInitialRefresh();
				} else {
					infobar.error(language.gettext('error_refreshfail'));
					startBackgroundInitTasks.doNextTask();
				}
			});
		},

		removePodcast: function(name) {
			debug.log("PODCAST","Removing podcast",name);
			$.ajax( {
				type: "GET",
				url: "podcasts/podcasts.php",
				cache: false,
				contentType: "text/html; charset=utf-8",
				data: {remove: name, populate: 1 }
			})
			.done(function(data) {
				$("#fruitbat").html(data);
				uiHelper.doThingsAfterDisplayingListOfAlbums($('#fruitbat'));
				podcasts.doNewCount();
			})
			.fail(function(data, status) {
				infobar.error(language.gettext("podcast_remove_error"));
			});
		},

		doInitialRefresh: function() {
			debug.log('PODCASTS', 'Setting initial refresh timer');
			clearTimeout(refreshtimer);
			refreshtimer = setTimeout(podcasts.checkRefresh, 15000);
		},

		search: function() {
			$('#podcast_search').empty();
			var term = $('#podcastsearch').val();
			if (term == '') {
				return true;
			}
			doSomethingUseful('podcast_search', language.gettext("label_searching"));
			$.ajax( {
				type: "GET",
				url: "podcasts/podcasts.php",
				cache: false,
				contentType: "text/html; charset=utf-8",
				data: {search: encodeURIComponent(term), populate: 1 }
			})
			.done(function(data) {
				$("#podcast_search").html(data);
				$('#podcast_search').prepend('<div class="configtitle dropdown-container brick_wide" style="width:100%"><div class="textcentre expand"><b>Search Results for &quot;'+term+'&quot;</b></div><i class="clickable clickicon podicon icon-cancel-circled removepodsearch podcast fixed"></i></div>');
				uiHelper.doThingsAfterDisplayingListOfAlbums($('#podcast_search'));
			})
			.fail(function(data, status, thing) {
				infobar.error(language.gettext('error_searchfail', [data.responseText]));
				$('#spinner_podcast_search').remove();
			});
		},

		clearsearch: function() {
			$('#podcast_search').empty();
			$('#podsclear').hide();
		},

		subscribe: function(index, clickedElement) {
			clickedElement.makeSpinner().removeClass('clickable');
			$.ajax( {
				type: "GET",
				url: "podcasts/podcasts.php",
				cache: false,
				contentType: "text/html; charset=utf-8",
				data: {subscribe: index, populate: 1 }
			})
			.done(function(data) {
				uiHelper.postPodcastSubscribe(data, index);
			})
			.fail(function(data, status, thing) {
				infobar.error(language.gettext('error_subfail', [data.responseText]));
				$('#spinner_cocksausage').remove();
			});
		},

		removeSearch: function() {
			$('#podcast_search').empty();
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
		},

		makeSearchWork: function(event) {
			event.preventDefault();
			event.stopPropagation();
			var position = getPosition(event);
			var elemright = $(event.target).width() + $(event.target).offset().left;
			if (position.x > elemright - 24) {
				$(event.target).val("");
				var thing = $(event.target).attr('name').replace(/podsearcher_/,'');
				podcasts.searchinpodcast(thing);
			}
		},

		handleClick: function (event, clickedElement) {
			if (clickedElement.hasClass("podremove")) {
				var n = clickedElement.attr('name');
				podcasts.removePodcast(n.replace(/podremove_/, ''));
			} else if (clickedElement.hasClass("podaction")) {
				var n = clickedElement.attr('name').match('(.*)_(.*)');
				podcasts.channelAction(n[2],n[1]);
			} else if (clickedElement.hasClass("podglobal")) {
				podcasts.globalAction(clickedElement.attr('name'), clickedElement);
			} else if (clickedElement.hasClass("podtrackremove")) {
				var n = clickedElement.attr('name');
				var m = clickedElement.parent().attr('name');
				podcasts.removePodcastTrack(n.replace(/podtrackremove_/, ''), m.replace(/podcontrols_/,''));
			} else if (clickedElement.hasClass("clickpodsubscribe")) {
				var index = clickedElement.next().val();
				podcasts.subscribe(index, clickedElement);
			} else if (clickedElement.hasClass("removepodsearch")) {
				podcasts.removeSearch();
			} else if (clickedElement.hasClass("poddownload")) {
				var n = clickedElement.attr('name');
				var m = clickedElement.parent().attr('name');
				podcasts.downloadPodcast(n.replace(/poddownload_/, ''), m.replace(/podcontrols_/,''));
			} else if (clickedElement.hasClass("podgroupload")) {
				var n = clickedElement.attr('name');
				podcasts.downloadPodcastChannel(n.replace(/podgroupload_/, ''));
			} else if (clickedElement.hasClass("podmarklistened")) {
				var n = clickedElement.attr('name');
				var m = clickedElement.parent().attr('name');
				podcasts.markEpisodeAsListened(n.replace(/podmarklistened_/, ''), m.replace(/podcontrols_/,''));
			} else if (clickedElement.hasClass("podmarkunlistened")) {
				var n = clickedElement.attr('name');
				var m = clickedElement.parent().attr('name');
				podcasts.markEpisodeAsUnlistened(n.replace(/podmarkunlistened_/, ''), m.replace(/podcontrols_/,''));
			}
		}
	}
}();

$('#podcastsinput').on('drop', podcasts.handleDrop)
menuOpeners['podcast'] = podcasts.loadPodcast;
clickRegistry.addClickHandlers('podcast', podcasts.handleClick);
// podcasts.doInitialRefresh();

