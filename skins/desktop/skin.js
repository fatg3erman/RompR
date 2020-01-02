
jQuery.fn.animatePanel = function(options) {
	var settings = $.extend({},options);
	var panel = this.attr("id");
	var opanel = panel;
	panel = panel.replace(/controls/,'');
	if (settings[panel] > 0 && this.is(':hidden')) {
		this.show();
	}
	this.animate({width: settings[panel]+"%"},
		{
			duration: settings.speed[panel],
			always: function() {
				if (settings[panel] == 0) {
					$(this).hide();
				} else {
					if (opanel == "infopane") browser.rePoint();
					if (opanel.match(/controls/)) {
						var i = (prefs.sourceshidden) ? "icon-angle-double-right" : "icon-angle-double-left";
						$("#expandleft").removeClass("icon-angle-double-right icon-angle-double-left").addClass(i);
						i = (prefs.playlisthidden) ? "icon-angle-double-left" : "icon-angle-double-right";
						$("#expandright").removeClass("icon-angle-double-right icon-angle-double-left").addClass(i);
					}
				}
			}
		}
	);
}

function showHistory() {

}

var layoutProcessor = function() {

	function showPanel(source) {
		$('#'+source).fadeIn('fast');
	}

	function setBottomPanelWidths() {
		var widths = getPanelWidths();
		$("#sources").css("width", widths.sources+"%");
		$("#sourcescontrols").css("width", widths.sources+"%");
		$("#infopane").css("width", widths.infopane+"%");
		$("#infopanecontrols").css("width", widths.infopane+"%");
		$("#playlist").css("width", widths.playlist+"%");
		$("#playlistcontrols").css("width", widths.playlist+"%");
	}

	function getPanelWidths() {
		var sourcesweight = (prefs.sourceshidden) ? 0 : 1;
		var playlistweight = (prefs.playlisthidden) ? 0 : 1;
		var browserweight = (prefs.hidebrowser) ? 0 : 1;
		var sourceswidth = prefs.sourceswidthpercent*sourcesweight;
		var playlistwidth = prefs.playlistwidthpercent*playlistweight;
		var browserwidth = (100 - sourceswidth - playlistwidth)*browserweight;
		if (browserwidth < 0) browserwidth = 0;
		return ({infopane: browserwidth, sources: sourceswidth, playlist: playlistwidth});
	}

	function animatePanels() {
		var widths = getPanelWidths();
		widths.speed = { sources: 400, playlist: 400, infopane: 400 };
		// Ensure that the playlist and playlistcontrols don't get pushed off the edge
		if ($("#playlist").is(':hidden')) {
			var w = $("#infopane").width();
			w -= 12;
			$("#infopane").css({width: w+"px"});
			$("#infopanecontrols").css({width: w+"px"});
		} else {
			var w = $("#playlist").width();
			w -= 12;
			$("#playlist").css({width: w+"px"});
			$("#playlistcontrols").css({width: w+"px"});
		}
		$("#sources").animatePanel(widths);
		$("#sourcescontrols").animatePanel(widths);
		$("#playlist").animatePanel(widths);
		$("#playlistcontrols").animatePanel(widths);
		$("#infopane").animatePanel(widths);
		$("#infopanecontrols").animatePanel(widths);
	}

	function showTrack(holder, target) {
		infobar.markCurrentTrack();
		layoutProcessor.scrollCollectionTo(holder, target);
	}

	var my_scrollers = [ "#sources", "#infopane", "#pscroller", ".topdropmenu", ".drop-box" ];

	return {

		supportsDragDrop: true,
		hasCustomScrollbars: true,
		usesKeyboard: true,
		sortFaveRadios: true,
		openOnImage: false,

		changeCollectionSortMode: function() {
			collectionHelper.forceCollectionReload();
		},

		afterHistory: function() {
			browser.rePoint();
			setTimeout(function() { $("#infopane").mCustomScrollbar("scrollTo",0) }, 500);
		},

		addInfoSource: function(name, obj) {
			$("#chooserbuttons").append($('<i>', {
				onclick: "browser.switchsource('"+name+"')",
				title: language.gettext(obj.text),
				class: obj.icon+' topimg sep expand tooltip',
				id: "button_source"+name
			}));
		},

		setupInfoButtons: function() {
			$("#button_source"+prefs.infosource).addClass("currentbun");
		},

		goToBrowserPanel: function(panel) {
			$("#infopane").mCustomScrollbar('update');
			$("#infopane").mCustomScrollbar("scrollTo","#"+panel+"information");
		},

		goToBrowserPlugin: function(panel) {
			setTimeout( function() { layoutProcessor.goToBrowserPanel(panel) }, 1000);
		},

		goToBrowserSection: function(section) {
			$("#infopane").mCustomScrollbar("scrollTo",section);
		},

		notifyAddTracks: function() { },

		playlistControlHotKey: function(button) {
			if (!$("#playlistbuttons").is(':visible')) {
				togglePlaylistButtons()
			}
			$("#"+button).trigger('click');
		},

		updateInfopaneScrollbars: function() {
			$('#infopane').mCustomScrollbar('update');
		},

		hidePanel: function(panel, is_hidden, new_state) {
			if (is_hidden != new_state) {
				if (new_state && prefs.chooser == panel) {
					$("#"+panel).fadeOut('fast');
					var s = ["albumlist", "searcher", "filelist", "radiolist", "audiobooklist", "podcastslist", "playlistslist", "pluginplaylistslist"];
					for (var i in s) {
						if (s[i] != panel && !prefs["hide_"+s[i]]) {
							layoutProcessor.sourceControl(s[i]);
							break;
						}
					}
				}
				if (!new_state && prefs.chooser == panel) {
					$("#"+panel).fadeIn('fast');
				}
			}
		},

		setTagAdderPosition: function(position) {
			$("#tagadder").css({top: Math.min(position.y+8, $(window).height() - $('#tagadder').height()),
				left: Math.min($(window).width() - $('#tagadder').width(),  position.x-16)});
		},

		setPlaylistHeight: function() {
			var newheight = $("#bottompage").height() - $("#horse").outerHeight();
			if ($("#playlistbuttons").is(":visible")) {
				newheight -= $("#playlistbuttons").outerHeight();
			}
			$("#pscroller").css("height", newheight.toString()+"px");
			$('#pscroller').mCustomScrollbar("update");
		},

		playlistLoading: function() {
			infobar.smartradio(language.gettext('label_preparing'));
		},

		scrollPlaylistToCurrentTrack: function() {
			if (prefs.scrolltocurrent) {
				var scrollto = playlist.getCurrentTrackElement();;
				if (scrollto.length > 0) {
					debug.log("LAYOUT","Scrolling Playlist To Song:",player.status.songid);
					$('#pscroller').mCustomScrollbar("stop");
					$('#pscroller').mCustomScrollbar("update");
					var pospixels = Math.round(scrollto.position().top - ($("#sortable").parent().parent().height()/2));
					pospixels = Math.min($("#sortable").parent().height(), Math.max(pospixels, 0));
					$('#pscroller').mCustomScrollbar(
						"scrollTo",
						pospixels,
						{ scrollInertia: 0 }
					);
				}
			}
		},

		toggleAudioOutpts: function() {
			prefs.save({outputsvisible: !$('#outputbox').is(':visible')});
			$("#outputbox").animate({width: 'toggle'},'fast',function() {
				infobar.biggerize();
			});
		},

		preHorse: function() {
			if (!$("#playlistbuttons").is(":visible")) {
				// Make the playlist scroller shorter so the window doesn't get a vertical scrollbar
				// while the buttons are being slid down
				var newheight = $("#pscroller").height() - 48;
				$("#pscroller").css("height", newheight.toString()+"px");
			}
		},

		hideBrowser: function() {
			if (prefs.hidebrowser) {
				prefs.save({playlistwidthpercent: 50, sourceswidthpercent: 50});
			} else {
				prefs.save({playlistwidthpercent: 25, sourceswidthpercent: 25});
			}
			animatePanels();
		},

		expandInfo: function(side) {
			switch(side) {
				case "left":
					var p = !prefs.sourceshidden;
					prefs.save({sourceshidden: p});
					break;
				case "right":
					var p = !prefs.playlisthidden;
					prefs.save({playlisthidden: p});
					break;
			}
			animatePanels();
			return false;
		},

		addCustomScrollBar: function(value) {
			$(value).mCustomScrollbar({
				theme: "light-thick",
				scrollInertia: 300,
				contentTouchScroll: 25,
				mouseWheel: {
					scrollAmount: parseInt(prefs.wheelscrollspeed),
				},
				alwaysShowScrollbar: 1,
				advanced: {
					updateOnContentResize: true,
					updateOnImageLoad: false,
					autoScrollOnFocus: false,
					autoUpdateTimeout: 500,
				}
			});
		},

		scrollCollectionTo: function(holder, jq) {
			if (jq.length > 0) {
				debug.log("LAYOUT","Scrolling",holder,"To",jq, jq.position().top,$(holder).parent().parent().parent().height()/2);
				var pospixels = Math.round(jq.position().top - $(holder).parent().parent().parent().height()/2);
				debug.log("LAYOUT","Scrolling",holder,"To",pospixels);
				$("#sources").mCustomScrollbar('update').mCustomScrollbar('scrollTo', pospixels,
					{ scrollInertia: 1000,
					  scrollEasing: 'easeOut' }
				);
			} else {
				debug.warn("LAYOUT","Was asked to scroll collection to something non-existent",2);
			}
		},

		sourceControl: function(source) {
			if ($('#'+source).length == 0) {
				prefs.save({chooser: 'albumlist'});
				source = 'albumlist';
			}
			if (source != prefs.chooser) {
				$('#'+prefs.chooser).fadeOut('fast', function() {
					showPanel(source);
					prefs.save({chooser: source});
				});
			} else {
				showPanel(source);
			}
			return false;
		},

		adjustLayout: async function() {
			var ws = getWindowSize();
			// Height of the bottom pane (chooser, info, playlist container)
			var newheight = ws.y - $("#bottompage").offset().top;
			$("#bottompage").css("height", newheight+"px");
			layoutProcessor.setPlaylistHeight();
			infobar.biggerize();
			browser.rePoint();
			$('.topdropmenu').fanoogleMenus();
		},

		displayCollectionInsert: function(details) {
			debug.log("COLLECTION","Displaying New Insert",details);
			var prefix;
			var holder;
			if (details.isaudiobook > 0) {
				holder = '#audiobooks';
				layoutProcessor.sourceControl('audiobooklist');
				prefix = 'z';
			} else {
				holder = '#collection';
				layoutProcessor.sourceControl('albumlist');
				prefix = 'a';
			}
			var artistmenu = prefix+'artist'+details.artistindex;
			var albummenu = prefix+"album"+details.albumindex;
			setTimeout(function() {
				if ($('i[name="'+artistmenu+'"]').isClosed()) {
					doAlbumMenu(null, $('i[name="'+artistmenu+'"]'), function() {
						if ($('i[name="'+albummenu+'"]').isClosed()) {
							doAlbumMenu(null, $('i[name="'+albummenu+'"]'), function() {
								showTrack(holder, $('[name="'+albummenu+'"]'));
							});
						} else {
							showTrack(holder, $('[name="'+albummenu+'"]'));
						}
					});
				} else if ($('i[name="'+albummenu+'"]').isClosed()) {
					doAlbumMenu(null, $('i[name="'+albummenu+'"]'), function() {
						showTrack(holder, $('[name="'+albummenu+'"]'));
					});
				} else {
					showTrack(holder, $('[name="'+albummenu+'"]'));
				}
			}, 1000);
		},

		setProgressTime: function(stats) {
			makeProgressOfString(stats);
		},

		setRadioModeHeader: function(html) {
			$("#plmode").html(html);
		},

		makeCollectionDropMenu: function(element, name) {
			var x = $('#'+name);
			// If the dropdown doesn't exist then create it
			if (x.length == 0) {
				if (element.parent().hasClass('album1')) {
					var c = 'dropmenu notfilled album1';
				} else if (element.parent().hasClass('album2')) {
					var c = 'dropmenu notfilled album2';
				} else {
					var c = 'dropmenu notfilled';
				}
				var t = $('<div>', {id: name, class: c}).insertAfter(element.parent());
			}
		},

		initialise: function() {
			if (prefs.outputsvisible) {
				layoutProcessor.toggleAudioOutpts();
			}
			$("#sortable").disableSelection();
			setDraggable('#collection');
			setDraggable('#filecollection');
			setDraggable('#searchresultholder');
			setDraggable("#podcastslist");
			setDraggable("#audiobooks");
			setDraggable("#somafmlist");
			setDraggable("#communityradiolist");
			setDraggable("#icecastlist");
			setDraggable("#tuneinlist");
			setDraggable('#artistinformation');
			setDraggable('#albuminformation');
			setDraggable('#storedplaylists');

			$("#sortable").acceptDroppedTracks({
				scroll: true,
				scrollparent: '#pscroller'
			});
			$("#sortable").sortableTrackList({
				items: '.sortable',
				outsidedrop: playlist.dragstopped,
				insidedrop: playlist.dragstopped,
				allowdragout: true,
				scroll: true,
				scrollparent: '#pscroller',
				scrollspeed: 80,
				scrollzone: 120
			});

			$("#pscroller").acceptDroppedTracks({
				ondrop: playlist.draggedToEmpty,
				coveredby: '#sortable'
			});

			animatePanels();

			$(".topdropmenu").floatingMenu({
				handleClass: 'dragmenu',
				addClassTo: 'configtitle',
				siblings: '.topdropmenu'
			});

			$("#tagadder").floatingMenu({
				handleClass: 'configtitle',
				handleshow: false
			});

			$(".stayopen").not('.dontstealmyclicks').on('click', function(ev) {ev.stopPropagation() });

			$.each(my_scrollers,
				function( index, value ) {
				layoutProcessor.addCustomScrollBar(value);
			});

			$("#sources").find('.mCSB_draggerRail').resizeHandle({
				adjusticons: ['#sourcescontrols', '#infopanecontrols'],
				side: 'left',
				donefunc: setBottomPanelWidths
			});

			$("#infopane").find('.mCSB_draggerRail').resizeHandle({
				adjusticons: ['#playlistcontrols', '#infopanecontrols'],
				side: 'right',
				donefunc: setBottomPanelWidths
			});

			shortcuts.load();
			setControlClicks();
			$('.choose_albumlist').on('click', function(){layoutProcessor.sourceControl('albumlist')});
			$('.choose_searcher').on('click', function(){layoutProcessor.sourceControl('searcher')});
			$('.choose_filelist').on('click', function(){layoutProcessor.sourceControl('filelist')});
			$('.choose_radiolist').on('click', function(){layoutProcessor.sourceControl('radiolist')});
			$('.choose_podcastslist').on('click', function(){layoutProcessor.sourceControl('podcastslist')});
			$('.choose_audiobooklist').on('click', function(){layoutProcessor.sourceControl('audiobooklist')});
			$('.choose_playlistslist').on('click', function(){layoutProcessor.sourceControl('playlistslist')});
			$('.choose_pluginplaylistslist').on('click', function(){layoutProcessor.sourceControl('pluginplaylistslist')});
			$('.open_albumart').on('click', openAlbumArtManager);
			$("#ratingimage").on('click', nowplaying.setRating);
			$('.icon-rss.npicon').on('click', function(){podcasts.doPodcast('nppodiput')});
			$('#expandleft').on('click', function(){layoutProcessor.expandInfo('left')});
			$('#expandright').on('click', function(){layoutProcessor.expandInfo('right')});
			$("#playlistname").parent().next('button').on('click', player.controller.savePlaylist);
			document.body.addEventListener('drop', function(e) {
				e.preventDefault();
			}, false);
			$('#albumcover').on('dragenter', infobar.albumImage.dragEnter);
			$('#albumcover').on('dragover', infobar.albumImage.dragOver);
			$('#albumcover').on('dragleave', infobar.albumImage.dragLeave);
			$("#albumcover").on('drop', infobar.albumImage.handleDrop);
			$(document).on('mouseenter', '.clearbox', makeHoverWork);
			$(document).on('mouseleave', '.clearbox', makeHoverWork);
			$(document).on('mousemove', '.clearbox', makeHoverWork);
			$(document).on('mouseenter', '.combobox-entry', makeHoverWork);
			$(document).on('mouseleave', '.combobox-entry', makeHoverWork);
			$(document).on('mousemove', '.combobox-entry', makeHoverWork);
			// $(document).on('mouseenter', '.tooltip', makeToolTip);
			// $(document).on('mouseleave', '.tooltip', stopToolTip);
			$('#volume').volumeControl({
				orientation: 'vertical',
				command: player.controller.volume
			});
			$(document).on('click', '.clickaddtoplaylist', function() {
				$('#addtoplaylistmenu').parent().parent().parent().hide();
			});
		},

		createPluginHolder: function(icon, title, id, panel) {
			var i = $('<i>', {class: 'topimg tooltip topdrop expand', title: title, id: id}).insertAfter('#rightspacer');
			i.addClass(icon);
			return i;
		}
	}
}();
