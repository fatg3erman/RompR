
jQuery.fn.animatePanel = function(options) {
	var settings = $.extend({},options);
	var panel = this.attr("id");
	var opanel = panel;
	panel = panel.replace(/controls/,'');
	// MUST use hide() and show() because I think mCustomScrollbar is adding a display: none
	// when width is 0%. Something is, anyway. And it's adding it to the OTHER panel
	if (settings[panel] > 0 && !this.is(':visible')) {
		this.show();
	}
	this.animate({width: settings[panel]+"%"},
		{
			duration: settings.speed[panel],
			always: function() {
				if (settings[panel] == 0) {
					$(this).hide();
				}
				// if (opanel == "infopane") browser.rePoint();
				if (opanel.match(/controls/)) {
					var i = (prefs.sourceshidden) ? "icon-angle-double-right" : "icon-angle-double-left";
					$("#expandleft").removeClass("icon-angle-double-right icon-angle-double-left").addClass(i);
					i = (prefs.playlisthidden) ? "icon-angle-double-left" : "icon-angle-double-right";
					$("#expandright").removeClass("icon-angle-double-right icon-angle-double-left").addClass(i);
				}
				browser.rePoint();
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
		layoutProcessor.setPanelCss(getPanelWidths());
	}

	function getPanelWidths() {
		var sourcesweight = (prefs.sourceshidden) ? 0 : 1;
		var playlistweight = (prefs.playlisthidden) ? 0 : 1;
		var sourceswidth = prefs.sourceswidthpercent*sourcesweight;
		var playlistwidth = prefs.playlistwidthpercent*playlistweight;
		return ({sources: sourceswidth, playlist: playlistwidth});
	}

	function animatePanels() {
		var widths = getPanelWidths();
		widths.speed = { sources: 400, playlist: 400 };
		if (prefs.hidebrowser) {
			$('#infopane').not('.invisible').addClass('invisible');
			$('#infopanecontrols').not('.invisible').addClass('invisible');
		} else {
			$('#infopane').removeClass('invisible');
			$('#infopanecontrols').removeClass('invisible');
		}
		$("#sources").animatePanel(widths);
		$("#sourcescontrols").animatePanel(widths);
		$("#playlist").animatePanel(widths);
		$("#playlistcontrols").animatePanel(widths);
	}

	return {

		sortFaveRadios: true,
		openOnImage: false,
		playlist_scroll_parent: '#pscroller',
		my_scrollers: [ "#sources", "#infopane", "#pscroller", ".top_drop_menu:not(.noscroll)", ".drop-box" ],

		setPanelCss: function(widths) {
			if (widths.sources) {
				$("#sources").css("width", widths.sources+"%");
				$("#sourcescontrols").css("width", widths.sources+"%");
			}
			if (widths.playlist) {
				$("#playlist").css("width", widths.playlist+"%");
				$("#playlistcontrols").css("width", widths.playlist+"%");
			}
		},

		changeCollectionSortMode: function() {
			collectionHelper.forceCollectionReload();
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

		notifyAddTracks: function() { },

		playlistControlHotKey: function(button) {
			if (!$("#playlistbuttons").is(':visible')) {
				togglePlaylistButtons()
			}
			$("#"+button).trigger(prefs.click_event);
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
			$("#sources").romprScrollTo(jq);
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
			if (prefs.use_touch_interface) {
				$(document).touchStretch({
					is_double_panel_skin: true
				});
			}
			animatePanels();
			$(".top_drop_menu").floatingMenu({
				handleClass: 'dragmenu',
				addClassTo: 'configtitle',
				siblings: '.top_drop_menu'
			});
			$("#tagadder").floatingMenu({
				handleClass: 'configtitle',
				handleshow: false
			});
			$("#pladddropdown").floatingMenu({
				handleClass: 'configtitle',
				handleshow: false
			});
			$("#bookmarkadddropdown").floatingMenu({
				handleClass: 'configtitle',
				handleshow: false
			});
			$('#volume').volumeControl({
				orientation: 'vertical',
				command: player.controller.volume
			});
		},

		postInit: function() {
			$("#sources").find('.mCSB_draggerRail').resizeHandle({
				side: 'left',
				donefunc: setBottomPanelWidths
			});
			$("#infopane").find('.mCSB_draggerRail').resizeHandle({
				side: 'right',
				donefunc: setBottomPanelWidths
			});
		},

		createPluginHolder: function(icon, title, id, panel) {
			var i = $('<i>', {class: 'topimg tooltip topdrop expand', title: title, id: id}).insertAfter('#rightspacer');
			i.addClass(icon);
			return i;
		},

		doCollectionStripyStuff: function() {
			var bit = ['#collection', '#searchresultholder', '#audiobooks'];
			for (let parts of bit) {
				$(parts+' .album2').removeClass('album2');
				$(parts+' > .menuitem').filter(':even').addClass('album2');
				$(parts+' > .album2 + .dropmenu').addClass('album2');
			}
		}
	}
}();
