
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
					}
				}
			}
		}
	);
}

jQuery.fn.menuReveal = async function(callback) {
	var id = this.prop('id');
	var element = $('i[name="'+id+'"]');
	layoutProcessor.postAlbumMenu(element, this);
	await this.slideToggle('fast').promise();
	if (callback) {
		callback();
	}
	return this;
}

jQuery.fn.menuHide = async function(callback) {
	var id = this.prop('id');
	var element = $('i[name="'+id+'"]');
	layoutProcessor.postAlbumMenu(element, this);
	await this.slideToggle('fast').promise();
	if (callback) {
		callback();
	}
	return this;
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
	}

	function getPanelWidths() {
		var sourcesweight = (prefs.sourceshidden) ? 0 : 1;
		var browserweight = (prefs.hidebrowser) ? 0 : 1;
		var sourceswidth = prefs.sourceswidthpercent*sourcesweight;
		var browserwidth = (100 - sourceswidth)*browserweight;
		if (browserwidth < 0) browserwidth = 0;
		return ({infopane: browserwidth, sources: sourceswidth});
	}

	function animatePanels() {
		var widths = getPanelWidths();
		widths.speed = { sources: 400, infopane: 400 };
		$("#sources").animatePanel(widths);
		$("#sourcescontrols").animatePanel(widths);
		$("#infopane").animatePanel(widths);
		$("#infopanecontrols").animatePanel(widths);
	}

	var my_scrollers = [ "#sources", "#infopane", ".topdropmenu", ".drop-box" ];
	var rtime = '';
	var ptime = '';
	var headers = Array();
	var currheader = 0;
	var headertimer;

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

		notifyAddTracks: function() {
			clearTimeout(headertimer);
			$('#plmode').fadeOut(500, function() {
				$('#plmode').html(language.gettext('label_addingtracks')).fadeIn(500);
			});
		},

		toggleAudioOutpts: function() {
			prefs.save({outputsvisible: !$('#outputbox').is(':visible')});
			$("#outputbox").animate({width: 'toggle'},'fast',function() {
				infobar.biggerize();
			});
		},

		setTagAdderPosition: function(position) {
			$("#tagadder").css({top: Math.min(position.y+8, $(window).height() - $('#tagadder').height()),
				left: Math.min($(window).width() - $('#tagadder').width(),  position.x-16)});
		},

		setPlaylistHeight: function() {
			// $('#phacker').fanoogleMenus();
		},

		playlistControlHotKey: function(button) {
			if (!$("#playlistbuttons").is(':visible')) {
				togglePlaylistButtons()
			}
			$("#"+button).tirgger('click');
		},

		updateInfopaneScrollbars: function() {
			$('#infopane').mCustomScrollbar('update');
		},

		scrollPlaylistToCurrentTrack: function() {
			if (prefs.scrolltocurrent) {
				var scrollto = playlist.getCurrentTrackElement();;
				if (scrollto.length > 0) {
					debug.log("LAYOUT","Scrolling Playlist To Song:",player.status.songid);
					$('#phacker').mCustomScrollbar("stop");
					$('#phacker').mCustomScrollbar("update");
					var pospixels = Math.round(scrollto.position().top - ($("#sortable").parent().parent().height()/2));
					pospixels = Math.min($("#sortable").parent().height(), Math.max(pospixels, 0));
					$('#phacker').mCustomScrollbar(
						"scrollTo",
						pospixels,
						{ scrollInertia: 0 }
					);
				}
			}
		},

		hideBrowser: function() {

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

		scrollCollectionTo: function(jq) {
			if (jq.length > 0) {
				$("#sources").mCustomScrollbar('update').mCustomScrollbar('scrollTo', jq,
					{ scrollInertia: 10,
					  scrollEasing: 'easeOut' }
				);
			} else {
				debug.warn("LAYOUT","Was asked to scroll collection to something non-existent",2);
			}
		},

		expandInfo: function(side) {
			switch(side) {
				case "left":
					var p = !prefs.sourceshidden;
					prefs.save({sourceshidden: p});
					break;
			}
			animatePanels();
			return false;
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
			var newwidth = ws.x - $('#infobar').offset().left;
			$('#infobar').css('width', newwidth+'px');
			infobar.biggerize();
			browser.rePoint();
			$('.topdropmenu').fanoogleMenus();
			setBottomPanelWidths();
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

		playlistupdate: function(upcoming) {
			var time = 0;
			for(var i in upcoming) {
				time += upcoming[i].duration;
			}
			if (time > 0) {
				headers['upcoming'] = "Up Next : "+upcoming.length+" tracks, "+formatTimeString(time);
			} else {
				headers['upcoming'] = '';
			}
			layoutProcessor.doFancyHeaderStuff();
		},

		doFancyHeaderStuff: function() {
			clearTimeout(headertimer);
			var lines = Array();
			for (var i in headers) {
				if (headers[i] != '') {
					lines.push(headers[i]);
				}
			}
			if (lines.length == 0 && $('#plmode').html() != '') {
				$('#plmode').fadeOut(500, function() {
					$('#plmode').html('').fadeIn(500);
				});
			} else if (lines.length == 1 && $('#plmode').html() != lines[0]) {
				$('#plmode').fadeOut(500, function() {
					$('#plmode').html(lines[0]).fadeIn(500);
				});
			} else {
				currheader++;
				if (currheader >= lines.length) {
					currheader = 0;
				}
				if ($('#plmode').html() != lines[currheader]) {
					$('#plmode').fadeOut(500, function() {
						$('#plmode').html(lines[currheader]).fadeIn(500, function() {
							headertimer = setTimeout(layoutProcessor.doFancyHeaderStuff, 5000);
						});
					});
				} else {
					headertimer = setTimeout(layoutProcessor.doFancyHeaderStuff, 5000);
				}
			}
		},

		setProgressTime: function(stats) {
			if (stats !== null) {
				rtime = stats.remainString;
				ptime = stats.durationString;
				$("#playposss").html(stats.progressString);
			}
			if (prefs.displayremainingtime) {
				$("#tracktimess").html(rtime);
			} else {
				$("#tracktimess").html(ptime);
			}
		},

		toggleRemainTime: function() {
			prefs.save({displayremainingtime: !prefs.displayremainingtime});
			layoutProcessor.setProgressTime(null);
		},

		setRadioModeHeader: function(html) {
			if (html != headers['radiomode']) {
				headers['radiomode'] = html;
				layoutProcessor.doFancyHeaderStuff();
			}
		},

		postAlbumMenu: function(element, dropdown) {
			if (!element) {
				return;
			}
			debug.debug("SKIN","Post Album Menu Thing",element.next());
			if (element.next().hasClass('smallcover')) {
				var imgsrc = element.next().children('img').attr('src');
				var aa = new albumart_translator(imgsrc);
				if (dropdown.is(':visible')) {
					if (imgsrc) {
						element.next().children('img').attr('src', aa.getSize('small'));
					}
					element.next().css('width','50%');
					element.next().css('width','');
					element.next().children('img').css('width', '');
				} else {
					if (imgsrc) {
						element.next().children('img').attr('src', aa.getSize('medium'));
					}
					element.next().css('width','50%');
					element.next().children('img').css('width', '100%');
				}
			}
		},

		initialise: function() {
			$("#sortable").disableSelection();
            $("#sortable").acceptDroppedTracks({
                scroll: true,
                scrollparent: '#phacker'
            });
            $("#sortable").sortableTrackList({
                items: '.sortable',
                outsidedrop: playlist.dragstopped,
                insidedrop: playlist.dragstopped,
                scroll: true,
                scrollparent: '#phacker',
                scrollspeed: 80,
                scrollzone: 120
            });
            $("#pscroller").acceptDroppedTracks({
                ondrop: playlist.draggedToEmpty,
                coveredby: '#sortable'
            });
			animatePanels();
			for (let value of my_scrollers) {
				$(value).addCustomScrollBar();
			};
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
			$("#sources").find('.mCSB_draggerRail').resizeHandle({
				adjusticons: ['#sourcescontrols', '#infopanecontrols'],
				side: 'left',
				donefunc: setBottomPanelWidths
			});
			$('#plmode').detach().appendTo('#amontobin').addClass('tright');
			$('#volume').volumeControl({
				orientation: 'vertical',
				command: player.controller.volume
			});
			$(document).on('click', '.clickaddtoplaylist', function() {
				$('#addtoplaylistmenu').parent().parent().parent().hide();
			});
		},

		createPluginHolder: function(icon, title, id, panel) {
			var d = $('<div>', {class: 'topdrop'}).prependTo('#righthandtop');
			var i = $('<i>', {class: 'tooltip', title: title, id: id}).appendTo(d);
			i.addClass(icon);
			i.addClass('smallpluginicon clickicon');
			return d;
		}

	}
}();
