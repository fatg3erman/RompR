var podcasts = function() {

	var downloadQueue = new Array();
	var downloadRunning = false;
	var newcounts = {};
	var scrobblesToCheck = [];
	var refreshtimer;
	var checktimer;

	async function downloadTrack(track, channel) {
		downloadQueue.push({track: track, channel: channel});
		$('i[name="poddownload_'+track+'"]').not('.spinner').makeSpinner();
		if (downloadRunning) {
			return;
		}
		while (downloadQueue.length > 0) {
			downloadRunning = true;
			var newTrack = downloadQueue.shift();
			$('[name="podgroupload_'+newTrack.channel+'"]').makeFlasher().removeClass('podgroupload');
			var monitor = new podcastDownloadMonitor(newTrack.track, newTrack.channel);
			var success = await clickRegistry.loadContentIntoTarget({
				target: $('#podcast_'+newTrack.channel),
				clickedElement: $('.openmenu[name="podcast_'+channel+'"]'),
				uri: 'api/podcasts/',
				data: {downloadtrack: newTrack.track, channel: newTrack.channel, populate: 1}
			});
			monitor.stop();
			doDummyProgressBars();
			$('[name="podgroupload_'+newTrack.channel+'"]').stopFlasher().removeClass('podgroupload').addClass('podgroupload');
			if (!success) {
				podcasts.loadPodcast(channel);
			}
		}
		downloadRunning = false;
	}

	function podcastDownloadMonitor(track, channel) {

		var self = this;
		var progressdiv = $('<div>', {class: 'podcast-download-bar'}).insertAfter($('i[name="poddownload_'+track+'"]').parent().parent());
		progressdiv.rangechooser({range: 100, startmax: 0, interactive: false});
		var timer;
		var running = true;

		this.checkProgress = function() {
			fetch(
				"utils/checkpodcastdownload.php",
				{
					cache: 'no-store',
					priority: 'low'
				}
			)
			.then(response => {
				if (response.ok) {
					return response.json();
				} else {
					throw new Error(language.gettext('error_dlpfail')+' : '+response.statusText);
				}
			})
			.then(data => {
				if (progressdiv && progressdiv.hasClass('rangechooser'))
						progressdiv.rangechooser('setProgress', data.percent);

				if (running)
					timer = setTimeout(self.checkProgress, 500);
			})
			.catch(err => {
				debug.warn(err.message);
				if (running) {
					timer = setTimeout(self.checkProgress, 1000);
				}
			});
		}

		this.stop = function() {
			running = false;
			clearTimeout(timer);
		}

		timer = setTimeout(self.checkProgress, 1000);
	}

	function doDummyProgressBars() {
		for (var track of downloadQueue) {
			debug.trace('PODCASTS', 'Making spinner on', track.track,'in',track.channel);
			$('i[name="poddownload_'+track.track+'"]').not('.spinner').makeSpinner();
		}
	}

	function putPodCount(element, num, numl) {
		debug.core("PODCASTS","Updating counts",element,num,numl);
		var indicator = $(element);
		if (num == 0) {
			indicator.removeClass('newpod').addClass('nopod_spacer');
			indicator.html('&nbsp;');
		} else {
			indicator.html(num);
			indicator.removeClass('nopod_spacer');
			if (!indicator.hasClass('newpod')) {
				indicator.addClass('newpod');
			}
		}
		var il = indicator.next();
		if (numl == 0) {
			il.removeClass('unlistenedpod');
			il.html('&nbsp;');
		} else {
			il.html(numl);
			if (!il.hasClass('unlistenedpod')) {
				il.addClass('unlistenedpod');
			}
		}
	}

	function checkForUpdatedPodcasts(data) {
		debug.log('PODCASTS', 'Checking for updated podcasts');
		debug.trace('PODCASTS', data);
		if (data && data.length > 0) {
			$.each(data, function(index, value) {
				if (!$('#podcast_'+value).hasClass('notfilled')) {
					debug.log("PODCASTS","Podcast",value,"was updated and is loaded - reloading it");
					podcasts.loadPodcast(value);
				}
			});
		}
	}

	async function podcastRequest(options, clickedElement) {
		if (clickedElement)	clickedElement.makeSpinner();
		options.populate = 1;
		if (options.channel) {
			var term = $('[name="podsearcher_'+options.channel+'"]').val();
			if (typeof term !== 'undefined' && term != '') {
				options.searchterm = encodeURIComponent(term);
			}
		}
		debug.trace("PODCASTS","Sending request",options);
		var params = new URLSearchParams(options);
		try {
			var response = await fetch(
				"api/podcasts/?"+params.toString(),
				{
					priority: 'low',
					cache: 'no-store'
				}
			);
			if (response.ok) {
				if (response.status == 200) {
					var data = await response.json();
					checkForUpdatedPodcasts(data);
					podcasts.doNewCount();
				}
			} else {
				if (response.status == 412) {
					infobar.error(language.gettext('label_refreshinprogress'));
				} else {
					throw new Error(language.gettext("label_general_error")+'<br />'+response.statusText);
				}
			}
		} catch(err) {
			infobar.error(err.message);
		}
		if (clickedElement)	clickedElement.stopSpinner();
	}

	return {

		getFromUrl: async function(url, element) {
			debug.log("PODCASTS","Importing Podcast",url);
			if (!element) {
				element = $('i.icon-podcast-circled.choosepanel');
			}
			await clickRegistry.loadContentIntoTarget({
				target: $('#fruitbat'),
				clickedElement: element,
				uri: 'api/podcasts/',
				data: {url: url, populate: 1},
				type: 'POST'
			});
			podcasts.doNewCount();
		},

		reloadList: async function() {
			$('.choosepanel[name="podcastslist"]').makeSpinner();
			await clickRegistry.loadContentIntoTarget({
				target: $('#fruitbat'),
				clickedElement: $('i.icon-podacst-cricled.choosepanel'),
				uri: "api/podcasts/?populate=1"
			});
			$('.choosepanel[name="podcastslist"]').stopSpinner();
			podcasts.doNewCount();
			sleepHelper.addWakeHelper(podcasts.checkIfSomeoneElseHasUpdatedStuff);
			sleepHelper.addSleepHelper(podcasts.stopPolling);
		},

		getPodcast: function(clickedElement, menutoopen) {
			return podcasts.channelUri(menutoopen.replace(/podcast_/, ''));
		},

		channelUri: function(channel) {
			var uri = "api/podcasts/?populate=1&loadchannel="+channel;
			var term = $('[name="podsearcher_'+channel+'"]').val();
			if (typeof term !== 'undefined' && term != '') {
				uri += '&searchterm='+encodeURIComponent(term);
			}
			return uri;
		},

		loadPodcast: function(channel) {
			debug.log("PODCASTS","Loading Podcast",channel);
			var configvisible = $('#podcast_'+channel).find('.podconfigpanel').is(':visible');

			clickRegistry.loadContentIntoTarget({
				target: $('#podcast_'+channel),
				clickedElement: $('.openmenu[name="podcast_'+channel+'"]'),
				scoot: true,
				data: {configvisible: configvisible ? 1 : 0}
			});
		},

		searchinpodcast: function(channel) {
			var term = $('[name="podsearcher_'+channel+'"]').val();
			debug.log("PODCASTS","Searching podcast",channel,'for',term);
			podcasts.loadPodcast(channel);
		},

		doPodcast: function(input, element) {
			var url = $("#"+input).val();
			if (!element) {
				element = $("#"+input).parent().next('');
			}
			if (url != '') {
				podcasts.getFromUrl(url, element);
			}
		},

		handleDrop: function() {
			setTimeout(function() { podcasts.doPodcast('podcastsinput', $('#podcastsinput').parent().next()) }, 1000);
		},

		channelAction: function(event, clickedElement) {
			var data = {};
			var n = clickedElement.attr('name').match('(.*)_(.*)');
			data[n[1]] = n[2];
			data.channel = n[2];
			debug.log("PODCAST","Action",data.action,"on podcast",data.channel);
			podcastRequest(data, clickedElement);
		},

		removePodcastTrack: function(event, clickedElement) {
			var track = clickedElement.attr('name').replace(/podtrackremove_/, '');
			var channel = clickedElement.parent().attr('name').replace(/podcontrols_/,'');
			debug.log("PODCAST","Removing track",track,"from channel",channel);
			podcastRequest({removetrack: track, channel: channel}, clickedElement);
		},

		markEpisodeAsListened: function(event, clickedElement) {
			var track = clickedElement.attr('name').replace(/podmarklistened_/, '');
			var channel = clickedElement.parent().attr('name').replace(/podcontrols_/,'');
			debug.log("PODCAST","Marking track",track,"from channel",channel,"as listened");
			podcastRequest({markaslistened: track, channel: channel }, clickedElement);
		},

		markEpisodeAsUnlistened: function(event, clickedElement) {
			var track = clickedElement.attr('name').replace(/podmarkunlistened_/, '');
			var channel = clickedElement.parent().attr('name').replace(/podcontrols_/,'');
			debug.log("PODCAST","Marking track",track,"from channel",channel,"as unlistened");
			podcastRequest({markasunlistened: track, channel: channel },null);
		},

		downloadPodcast: function(event, clickedElement) {
			var track = clickedElement.attr('name').replace(/poddownload_/, '');
			var channel = clickedElement.parent().attr('name').replace(/podcontrols_/,'');
			debug.log("PODCAST","Downloading track",track,"from channel",channel);
			downloadTrack(track, channel);
		},

		undownloadPodcast: function(event, clickedElement) {
			var track = clickedElement.attr('name').replace(/podremdownload_/, '');
			var channel = clickedElement.parent().attr('name').replace(/podcontrols_/,'');
			debug.log("PODCAST","Un-Downloading track",track,"from channel",channel);
			podcastRequest({undownloadtrack: track, channel: channel },null);
		},

		downloadPodcastChannel: function(event, clickedElement) {
			var channel = clickedElement.attr('name').replace(/podgroupload_/, '');
			$("#podcast_"+channel).find('.poddownload').trigger(prefs.click_event);
		},

		checkMarkPodcastAsListened: function(file) {
			podcastRequest({listened: encodeURIComponent(file), populate: 1}, null);
		},

		doNewCount: function() {
			fetch(
				"api/podcasts/?populate=1&getcounts=1",
				{
					cache: 'no-store',
					priority: 'low'
				}
			)
			.then(response => {
				if (response.ok) {
					return response.json();
				} else {
					throw new Error(response.statusText);
				}
			})
			.then(data => {
				debug.core('PODCASTS','Got New Counts',data);
				$.each(data, function(index, value) {
					putPodCount('#podnumber_'+index, value.new, value.unlistened);
				});
				newcounts = data;
			})
			.catch(err => {
				debug.warn('PODCASTS', 'doNewCount Failed', err);
			});
		},

		checkIfSomeoneElseHasUpdatedStuff: async function() {
			clearTimeout(refreshtimer);
			debug.log('PODCASTS', 'Checking if someone else has updated stuff');
			var isnewpodcast = false;
			var to_reload = new Array();
			try {
				var response = await fetch(
					"api/podcasts/?populate=1&getcounts=1",
					{
						cache: 'no-store',
						priority: 'low'
					}
				);
				if (response.ok) {
					var data = await response.json();
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
				} else {
					throw new Error(response.statusText);
				}
			} catch(err) {
				debug.warn('PODCASTS', 'Failed when doing post-wake actions', err.message);
			}
			if (isnewpodcast) {
				await podcasts.reloadList();
			} else {
				checkForUpdatedPodcasts(to_reload);
				podcasts.doNewCount();
			}
			// Check every 30 minutes for updates to podcasts
			refreshtimer = setTimeout(podcasts.checkIfSomeoneElseHasUpdatedStuff, 1800000);
		},

		stopPolling: function() {
			clearTimeout(refreshtimer);
		},

		changeOption: async function(event) {
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
			await podcastRequest(options, null);
			if (callback) callback();
		},

		checkRefresh: function() {
			clearTimeout(checktimer);
			debug.info('PODCASTS', 'Starting Refresh');
			fetch(
				"api/podcasts/?populate=1&checkrefresh=1",
				{
					signal: AbortSignal.timeout(prefs.collection_load_timeout),
					cache: 'no-store',
					priority: 'low'
				}
			)
			.then(response => {
				if (response.ok) {
					return response.json();
				} else {
					if (response.status == 412) {
						debug.trace("PODCASTS", 'Refresh is in progress, checking again in 10 seconds');
						checktimer = setTimeout(podcasts.checkRefresh, 10000);
					} else {
						throw new Error(language.gettext('error_refreshfail')+'<br />'+response.statusText);
					}
				}
			})
			.then(data => {
				debug.log('PODCASTS', 'Refresh complete');
				checkForUpdatedPodcasts(data.updated);
				podcasts.doNewCount();
			})
			.catch(err => {
				debug.error("PODCASTS","Refresh Failed", err);
				infobar.error(err.message);
			});
		},

		removePodcast: function(event, clickedElement) {
			var index = clickedElement.attr('name').replace(/podremove_/, '');
			debug.log("PODCAST","Removing podcast",index);
			// Remove it right away for responsiveness
			$('.openmenu[name="podcast_'+index+'"]').removeCollectionItem();
			$('#podcast_'+index).removeCollectionDropdown();
			fetch('api/podcasts/?remove='+index+'&populate=1').finally(podcasts.reloadList);
		},

		search: async function(terms, domains) {
			// Note, terms.any is an array but encodeURIComponent will join them with a ,
			await clickRegistry.loadContentIntoTarget({
				target: $('#podcast_search'),
				clickedElement: $('button[name="globalsearch"]'),
				uri: 'api/podcasts/',
				data: {search: encodeURIComponent(terms.any), populate: 1}
			});
			searchManager.make_search_title('podcast_search', 'Podcast Search Results for &quot;'+terms.any+'&quot;');
		},

		subscribe: async function(event, clickedElement) {
			var index = clickedElement.next().val();
			await clickRegistry.loadContentIntoTarget({
				target: $('#fruitbat'),
				clickedElement: clickedElement,
				uri: 'api/podcasts/?subscribe='+index+'&populate=1'
			});
			$('#podcast_search').find('.openmenu[name="podcast_'+index+'"]').removeCollectionItem();
			$('#podcast_search').find('#podcast_'+index).removeCollectionDropdown();
			podcasts.doNewCount();
		},

		storePlaybackProgress: function(track) {
			podcastRequest({setprogress: Math.round(track.progress), track: encodeURIComponent(track.uri), name: track.name}, null);
		},

		removeBookmark: function(event, clickedElement) {
			var self = $(clickedElement);
			podcasts.storePlaybackProgress({progress: 0, uri: self.attr('uri'), name: self.attr('name')});
		},

		globalAction: async function(event, clickedElement) {
			var options = {};
			options[clickedElement.attr('name')] = 1;
			await podcastRequest(options, clickedElement);
			podcasts.reloadList()
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
		}

	}
}();

$('#podcastsinput').on('drop', podcasts.handleDrop)
clickRegistry.addClickHandlers('podremove', podcasts.removePodcast);
clickRegistry.addClickHandlers('podaction', podcasts.channelAction);
clickRegistry.addClickHandlers('podglobal', podcasts.globalAction);
clickRegistry.addClickHandlers('podtrackremove', podcasts.removePodcastTrack);
clickRegistry.addClickHandlers('clickpodsubscribe', podcasts.subscribe);
// clickRegistry.addClickHandlers('removepodsearch', podcasts.removeSearch);
clickRegistry.addClickHandlers('poddownload', podcasts.downloadPodcast);
clickRegistry.addClickHandlers('podremdownload', podcasts.undownloadPodcast);
clickRegistry.addClickHandlers('podgroupload', podcasts.downloadPodcastChannel);
clickRegistry.addClickHandlers('podmarklistened', podcasts.markEpisodeAsListened);
clickRegistry.addClickHandlers('podmarkunlistened', podcasts.markEpisodeAsUnlistened);
clickRegistry.addClickHandlers('clickremovebookmark', podcasts.removeBookmark);
clickRegistry.addMenuHandlers('podcast', podcasts.getPodcast);
searchManager.add_search_plugin('podcastsearch', podcasts.search, ['podcasts']);
