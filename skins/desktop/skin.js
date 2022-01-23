
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

	var my_scrollers = [ "#sources", "#infopane", "#pscroller", ".top_drop_menu", ".drop-box" ];

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
				onclick: "browser.switch_source('"+name+"')",
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
							uiHelper.sourceControl(s[i]);
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
			// var newheight = $("#bottompage").height() - $("#playlist_top").outerHeight();
			// if ($("#playlistbuttons").is(":visible")) {
			// 	newheight -= $("#playlistbuttons").outerHeight();
			// }
			// $("#pscroller").css("height", newheight.toString()+"px");
			// $('#pscroller').mCustomScrollbar("update");
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
				infobar.rejigTheText();
			});
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

		scrollCollectionTo: function(jq) {
			if (jq.length > 0) {
				debug.trace('UI', 'Scrolling Collection To',jq);
				$("#sources").mCustomScrollbar('update').mCustomScrollbar('scrollTo', jq,
					{ scrollInertia: 10,
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
			// var ws = getWindowSize();
			// // Height of the bottom pane (chooser, info, playlist container)
			// var newheight = ws.y - $("#bottompage").offset().top;
			// $("#bottompage").css("height", newheight+"px");

			// layoutProcessor.setPlaylistHeight();
			infobar.rejigTheText();
			browser.rePoint();
			$('.top_drop_menu').fanoogleMenus();
		},

		displayCollectionInsert: async function(details) {
			debug.mark("COLLECTION","Displaying New Insert");
			debug.debug('COLLECTION', details);
			if (details.isaudiobook > 0) {
				var holder = '#audiobooks';
				uiHelper.sourceControl('audiobooklist');
				var artistmenu = '.openmenu[name="zartist'+details.artistindex+'"]';
				var albummenu = '.openmenu[name="zalbum'+details.albumindex+'"]';
			} else {
				var holder = '#collection';
				uiHelper.sourceControl('albumlist');
				var artistmenu = '.openmenu[name="aartist'+details.artistindex+'"]';
				var albummenu = '.openmenu[name="aalbum'+details.albumindex+'"]';
			}
			if ($(artistmenu).isClosed()) {
				await $.proxy(clickRegistry.doMenu, $(artistmenu)).call();
			}
			if ($(albummenu).isClosed()) {
				await $.proxy(clickRegistry.doMenu, $(albummenu)).call();
			}
			layoutProcessor.scrollCollectionTo($(albummenu).parent());
		},

		setRadioModeHeader: function(html) {
			$("#plmode").html(html);
		},

		initialise: function() {
			if (prefs.outputsvisible) {
				layoutProcessor.toggleAudioOutpts();
			}
			$("#sortable").disableSelection();
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
			for (let value of my_scrollers) {
				debug.log('INIT', 'Adding custom scroll bar to',value);
				$(value).addCustomScrollBar();
			};
			$(".top_drop_menu").floatingMenu({
				handleClass: 'dragmenu',
				addClassTo: 'configtitle',
				siblings: '.top_drop_menu'
			});
			$("#tagadder").floatingMenu({
				handleClass: 'configtitle',
				handleshow: false
			});
			$(".stayopen").not('.dontstealmyclicks').on('click', function(ev) {ev.stopPropagation() });
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
