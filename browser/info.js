var browser = function() {

	var displaypointer = 0;
	var panelclosed = {artist: false, album: false, track: false};
	var extraPlugins = [];
	var sources = nowplaying.getAllPlugins();
	var history = [];
	const MAX_HISTORY_LENGTH = 20;

	function toggleSection(section, element) {
		var foldup = element.parent().parent().next();
		$(foldup).slideToggle('slow', function() {
			if ($(this).is(':visible')) {
				browser.rePoint();
			}
		});
		panelclosed[section] = !panelclosed[section];
	}

	function removeSection(section) {
		extraPlugins[section].parent.close();
		extraPlugins[section].div.fadeOut('fast', function() {
			extraPlugins[section].div.empty();
			extraPlugins[section].div.remove();
			extraPlugins[section].div = null;
			if ($('#pluginholder').length > 0 && openPlugins() == 0) {
				uiHelper.sourceControl('specialplugins');
			}
		});
	}

	function openPlugins() {
		var c = 0;
		for (var i in extraPlugins) {
			if (extraPlugins[i].div !== null) {
				c++;
			}
		}
		return c;
	}

	function historyClicked(event) {
		var clickedRow = $(event.target);
		while (!clickedRow.hasClass('clickable') && !clickedRow.is('#historypanel')) {
			clickedRow = clickedRow.parent();
		}
		if (clickedRow.hasAttr('name')) {
			browser.doHistory(parseInt(clickedRow.attr('name')));
		}
	}

	function check_history() {

		if (history.length > MAX_HISTORY_LENGTH) {
			item_to_remove = (displaypointer == 0) ? 1 : 0;
			let removed_item = history.splice(item_to_remove, 1);
			debug.mark('BROWSER', 'History limit exceeded. Removing item nmber',item_to_remove,removed_item);
			nowplaying.truncate_item(removed_item.nowplayingindex);
			if (displaypointer > 0)
				displaypointer--;
		}

		var hpanel = $('#historypanel').empty().off('click');
		var t = $('<table>', {class: 'histable', width: '100%'}).appendTo('#historypanel');
		history.forEach(function(h, i) {
			var r = $('<tr>', {class: 'top clickable clickicon', name: i}).appendTo(t);
			r.append($('<td>').append($('<i>', {class: sources[h.source].icon+' medicon'})));
			var td = $('<td>').appendTo(r);
			["artist","album","track"].forEach(function(n) {
				var tit = nowplaying.getTitle(n, h.source, h.nowplayingindex, h.special[n]);
				if (tit) {
					td.append(tit+'<br />');
				}
			});
		});
		browser.update_forward_back_buttons();

		hpanel.on('click', historyClicked);

	}

	return {

		is_displaying_current_track: function() {
			return (history.length == 0 || displaypointer == history.length - 1);
		},

		get_current_displayed_track: function() {
			return history[displaypointer].nowplayingindex;
		},

		createButtons: function() {
			for (var i in sources) {
				if (sources[i].icon !== null) {
					debug.info("BROWSER", "Found plugin", i,sources[i].icon);
					layoutProcessor.addInfoSource(i, sources[i]);
				}
			}
			layoutProcessor.setupInfoButtons();
		},

		nextSource: function(direction) {
			var s = new Array();
			for (var i in sources) {
				if (sources[i].icon !== null) {
					s.push(i);
				}
			}
			var cursourceidx = s.indexOf(prefs.infosource);
			var newsourceidx = cursourceidx+direction;
			if (newsourceidx >= s.length) newsourceidx = 0;
			if (newsourceidx < 0) newsourceidx = s.length-1;
			browser.switch_source(s[newsourceidx]);
		},

		switch_source: function(source) {
			debug.info("BROWSER","Switching info source to",source);
			if (history[history.length - 1]) {
				var idx = history[history.length - 1].nowplayingindex;
				nowplaying.switch_source(idx, source);
			}
		},

		dataIsComing: function(historyindex, hist, browser_showing_current, force, scrollto, splice) {

			debug.log('BROWSER', 'Incoming', historyindex, hist, browser_showing_current, force, scrollto);

			if (prefs.hidebrowser)
				return;

			// If we're not currently showing the last item in the history, do nothing unless this is a source switch
			if (browser_showing_current || hist.source != prefs.infosource) {
				// The metadata for each track includes the backend's playlist ID.
				// In nowplaying we copy metadata if the artist, album, or track are the same
				// Hence if the id field in the data we're been given is different from the one we're diplaying, we need to display the new one.
				['artist', 'album', 'track'].forEach(function(thing) {
					if (force[thing] ||
						(history.length == 0) ||
						(hist.source != prefs.infosource) ||
						nowplaying.compare_ids(thing, history[displaypointer].nowplayingindex, hist.nowplayingindex))
					{
						debug.mark('BROWSER','Displaying',thing);
						// Detaching the layout (rather than emptying the holder) ensures that masonry layouts continue to work
						// if/when we put them back in - provided those layouts are not destroyed eg in a _destroy widget method
						if (history[displaypointer]) {
							nowplaying.detachLayout(thing, history[displaypointer].source, history[displaypointer].nowplayingindex, history[displaypointer].special[thing]);
						}
						// This is only here to remove the 'this is the information panel' div
						$('#'+thing+'information .infobanner').remove();
						$('#'+thing+'information').append(nowplaying.getLayout(thing, hist.source, hist.nowplayingindex, hist.special[thing]));
					}
				});
				nowplaying.doArtistChoices(hist.nowplayingindex);
				// We're cloning the object here, for reasons that it might perhaps contain
				// references to parent object that can be cleaned up. Certainly we don't need those references if they exist.
				if (historyindex === null) {
					if (splice) {
						displaypointer++;
						history.splice(displaypointer, 0, cloneObject(hist));
					} else {
						displaypointer = history.length;
						history[displaypointer] = cloneObject(hist);
					}
				} else {
					displaypointer = historyindex;
				}
				if (hist.source != prefs.infosource) {
					$("#button_source"+prefs.infosource).removeClass("currentbun");
					prefs.save({infosource: hist.source});
					debug.log("BROWSER", "Source switched to",prefs.infosource);
					$("#button_source"+prefs.infosource).addClass("currentbun");
				}
				if (scrollto)
					uihelper.goToBrowserPanel(scrollto);

			} else {
				history.push(cloneObject(hist));
			}
			browser.rePoint();
			check_history();
		},

		get_icon: function(source) {
			return sources[source].icon;
		},

		update_forward_back_buttons: function() {
			if (displaypointer <= 0) {
				$("#backbutton").off('click').addClass('button-disabled');
			} else if ($("#backbutton").hasClass('button-disabled')) {
				$("#backbutton").on('click', browser.back).removeClass('button-disabled');
			}

			if (history.length == 0 || displaypointer == history.length - 1) {
				$("#forwardbutton").off('click').addClass('button-disabled');
			} else if ($("#forwardbutton").hasClass('button-disabled')) {
				$("#forwardbutton").on('click', browser.forward).removeClass('button-disabled');
			}

			$('#historypanel').find('tr.current').removeClass('current');
			$('#historypanel').find('tr[name="'+displaypointer+'"]').addClass('current');
		},

		handleClick: function(panel, element, event) {
			debug.debug("BROWSER","Was clicked on",panel,element);
			if (element.hasClass('frog')) {
				toggleSection(panel, element);
			} else if (element.hasClass('tadpole')) {
				removeSection(panel);
			} else if (element.hasClass('plugclickable')) {
				extraPlugins[panel].parent.handleClick(element, event);
			} else if (element.hasClass('clickartistchoose')) {
				nowplaying.switchArtist(history[displaypointer].nowplayingindex, element.next().val());
			} else {
				nowplaying.handleClick(history[displaypointer].nowplayingindex, history[displaypointer].source, panel, element, event);
			}
		},

		doHistory: function(index) {
			debug.trace('BROWSER', 'Doing history', index, history[index]);
			browser.dataIsComing(index,
				{	nowplayingindex: history[index].nowplayingindex,
					source: history[index].source,
					special: history[index].special
				},
				true, {artist: true, album: true, track: true}, false, false
			);
			uihelper.afterHistory();
			browser.update_forward_back_buttons();
		},

		forward: function() {
			debug.trace('BROWSER', 'Forwards', displaypointer);
			browser.doHistory(displaypointer+1);
			return false;
		},

		back: function() {
			browser.doHistory(displaypointer-1);
			return false;
		},

		registerExtraPlugin: function(id, name, parent, help) {
			var displayer;
			if (prefs.hidebrowser) {
				$("#hidebrowser").prop("checked", !$("#hidebrowser").is(':checked'));
				prefs.save({hidebrowser: $("#hidebrowser").is(':checked')}).then(layoutProcessor.hideBrowser);
			}
			if ($('#pluginholder').length > 0 && !($('#pluginholder').is(':visible'))) {
				displayer = $('<div>', {id: id+"information", class: "infotext invisible"}).appendTo('#pluginholder');
			} else {
				displayer = $('<div>', {id: id+"information", class: "infotext invisible"}).insertBefore('#artistchooser');
			}
			var opts = {
				name: name,
				withfoldup: true
			};
			if (help) {
				opts.help = help;
			}
			displayer.html(browser.info_banner(opts, false, true));
			displayer.append($('<div>', {id: id+'foldup', class: 'extraplugin-foldup'}));
			panelclosed[id] = false;
			displayer.off('click');
			extraPlugins[id] = { div: displayer, parent: parent };
			displayer.on('click', '.infoclick', onBrowserClicked);
			return displayer;
		},

		goToPlugin: function(id) {
			uihelper.goToBrowserPlugin(id);
		},

		info_banner: function(data, source, close) {
			var holder = $('<div>', {class: 'infobanner containerbox infosection'});
			var h = $('<h2>', {class: 'expand'}).appendTo(holder);
			h.html(data.name);
			if (data.withfoldup) {
				holder.append($('<div>', {class: 'fixed alignmid'})
					.append($('<i>', {class: 'icon-menu svg-square infoclick clickicon frog tooltip', title: language.gettext('label_hidepanel')})));
			}
			if (data.help) {
				holder.append($('<div>', {class: 'fixed alignmid'})
					.append($('<a>', {href: data.help, target: '_blank'})
					.append($('<i>', {class: 'icon-info-circled svg-square tooltip', title: language.gettext('label_gethelp')}))));
			}
			if (source) {
				if (typeof data.link == 'undefined') {
					holder.append($('<div>', {class: 'fixed alignmid'}).append($('<i>', {class: 'icon-spin6 spinner svg-square'})));
				} else if (data.link == null) {
					holder.append($('<div>', {class: 'fixed alignmid'}).append($('<i>', {class: sources[source].icon+' svg-square'})));
				} else {
					holder.append($('<div>', {class: 'fixed alignmid'}).append($('<a>', {href: data.link, target: '_blank'}).append($('<i>', {class: sources[source].icon+' svg-square', title: language.gettext('info_newtab')}))));
				}
			}
			if (close) {
				holder.append($('<div>', {class: 'fixed alignmid'})
					.append($('<i>', {class: 'icon-cancel-circled svg-square infoclick clickicon tadpole tooltip', title: language.gettext('label_closepanel')})));
			}
			return holder;
		},

		rePoint: function(panel, params) {
			const main_pane = $('#infopane');
			var iw = main_pane.width();
			if (prefs.hidebrowser || iw == 0) { return }

			uiHelper.updateInfopaneScrollbars();

			if (iw > 1280) {
				main_pane.addClass('width_5').removeClass('width_1 width_2 width_3 width_4');
			} else if (iw > 960) {
				main_pane.addClass('width_4').removeClass('width_1 width_2 width_3 width_5');
			} else if (iw > 720) {
				main_pane.addClass('width_3').removeClass('width_1 width_2 width_4 width_5');
			} else if (iw < 480) {
				main_pane.addClass('width_1').removeClass('width_2 width_3 width_4 width_5');
			} else {
				main_pane.addClass('width_2').removeClass('width_1 width_3 width_4 width_5');
			}

			// make sure we masonify panels that are open spotify artist widgets first,
			// because if the height changes the other closed ones around them need to
			// know about that.

			if (typeof(params) == 'undefined') {
				$('#infopane .spotify_artist_albums.masonry-initialised:visible').masonry();
				$('#infopane .masonry-initialised:not(.spotify_artist_albums):visible').masonry();
			} else {
				params.gutter = masonry_gutter;
				panel.masonry(params);
				panel.addClass('masonry-initialised');
			}
		}
	}
}();
