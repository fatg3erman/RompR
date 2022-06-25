var podcasts = function() {

	var downloadQueue = new Array();
	var downloadRunning = false;
	var newcounts = {};
	var scrobblesToCheck = [];
	var refreshtimer;

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
				downloadQueue.unshift(newTrack);
			}
		}
		downloadRunning = false;
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
				// Sometimes we refesh just before this fires, and the content has been
				// reloaded, which makes this throw an error
				debug.debug("PODCAST DOWNLOAD","Download status is",data);
				if (progressdiv.hasClass('rangechooser')) {
					progressdiv.rangechooser('setProgress', data.percent);
				}
				if (running) {
					timer = setTimeout(self.checkProgress, 500);
				}
			})
			.fail(function() {
				debug.warn(language.gettext('error_dlpfail'));
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
		try {
			var data = await $.ajax({
				type: "GET",
				url: "api/podcasts/",
				cache: false,
				data: options,
				contentType: 'application/json'
			});
			checkForUpdatedPodcasts(data);
			podcasts.doNewCount();
		} catch (err) {
			debug.error("PODCASTS", "Podcast Request Failed:",data,options);
			if (data.status == 412) {
				infobar.error(language.gettext('label_refreshinprogress'));
			} else {
				infobar.error(language.gettext("label_general_error"));
			}
		};
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
				data: {url: encodeURIComponent(url), populate: 1}
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
			podcasts.doNewCount();
			$('.choosepanel[name="podcastslist"]').stopSpinner();
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
				scoot: false,
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
			$("#podcast_"+channel).find('.poddownload').trigger('click');
		},

		checkMarkPodcastAsListened: function(file) {
			podcastRequest({listened: encodeURIComponent(file), populate: 1}, null);
		},

		doNewCount: function() {
			$.getJSON("api/podcasts/?populate=1&getcounts=1", function(data) {
				debug.core('PODCASTS','Got New Counts',data);
				$.each(data, function(index, value) {
					if (index == 'totals') {
						// element = '#total_unlistened_podcasts';
					} else {
						if (newcounts[index] &&
							(newcounts[index].new != value.new || newcounts[index].unlistened != value.unlistened)) {
							debug.trace('PODCASTS', 'Podcast',index,'counts have changed to',value);
							putPodCount('#podnumber_'+index, value.new, value.unlistened);
						}
					}
				});
				newcounts = data;
			});
		},

		checkIfSomeoneElseHasUpdatedStuff: async function() {
			clearTimeout(refreshtimer);
			debug.log('PODCASTS', 'Checking if someone else has updated stuff');
			var isnewpodcast = false;
			var to_reload = new Array();
			try {
				var data = await $.ajax({
					type: 'GET',
					url: "api/podcasts/?populate=1&getcounts=1",
					dataType: 'json'
				});
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
			} catch(err) {
				debug.warn('PODCASTS', 'Failed when doing post-wake actions');
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

		checkRefresh: async function() {
			debug.info('PODCASTS', 'Starting Refresh');
			try {
				var data = await $.ajax({
					type: 'GET',
					url: "api/podcasts/?populate=1&checkrefresh=1",
					timeout: prefs.collection_load_timeout,
					dataType: 'JSON'
				});
				debug.log('PODCASTS', 'Refresh complete');
				checkForUpdatedPodcasts(data.updated);
				podcasts.doNewCount();
			} catch (err)  {
				debug.error("PODCASTS","Refresh Failed with status",err.status);
				if (err.status == 412) {
					setTimeout(podcasts.checkRefresh, 10000);
				} else {
					infobar.error(language.gettext('error_refreshfail'));
				}
			}
		},

		removePodcast: async function(event, clickedElement) {
			var index = clickedElement.attr('name').replace(/podremove_/, '');
			debug.log("PODCAST","Removing podcast",index);
			// Remove it right away for responsiveness
			$('.openmenu[name="podcast_'+index+'"]').removeCollectionItem();
			$('#podcast_'+index).removeCollectionDropdown();
			await $.get('api/podcasts/?remove='+index+'&populate=1').promise();
			podcasts.reloadList();
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

		// clearsearch: function() {
		// 	$('#podcast_search').clearOut().empty();
		// 	$('#podsclear').hide();
		// },

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

		// removeSearch: function(event, clickedElement) {
		// 	$('#podcast_search').clearOut().empty();
		// },

		storePlaybackProgress: function(track) {
			podcastRequest({setprogress: track.progress, track: encodeURIComponent(track.uri), name: track.name}, null);
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
