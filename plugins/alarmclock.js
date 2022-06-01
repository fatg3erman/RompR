jQuery.fn.setAlarmDays = function(alarm) {
	return this.each(function() {
		if (alarm.Repeat == 1) $(this).html(alarm.Days.split(',').join(', '));
	});
}

var alarmclock = function() {

	var notification = null;
	var holder = null;
	var alarms = [];
	var newalarms = [];
	var frug = 0;
	var alarm_running = false;

	var alarm_editor = null;
	var editor_popup = null;

	function fillWindow() {
		var alarm_enabled = false;
		var alarm_snoozing = false;
		alarm_running = false;
		for (var a in alarms) {
			createAlarmHeader(alarms[a], a);
			if (alarms[a].Alarmindex != 'NEW') {
				if (alarms[a].Pid)
					alarm_enabled = true;

				if (alarms[a].Running == 1)
					alarm_running = a;

				if (alarms[a].SnoozePid)
					alarm_snoozing = true;
			}
		}
		$('#alarmclock_icon').removeClass('icon-alarm').removeClass('icon-alarm-on').addClass(alarm_enabled ? 'icon-alarm-on' : 'icon-alarm');
		infobar.playbutton.flash(alarm_snoozing);
	}

	function createAlarmHeader(alarm, ourindex) {
		var container = $('<div>', {class: 'item playlistalbum playlisttitle'}).appendTo(holder);
		var lego = $('<table width="100%">').appendTo(container);
		var row1 = $('<tr>').appendTo(lego);
		var froggy = $('<td rowspan="2">').appendTo(row1);
		// Note rompr_index - this is OUR array index, whereas Alarmindex is the database table AUTO_INCREMENT index.
		$('<input>', {type: "time", style: 'width:5em', class: "fixed snapclientname alarmtimeedit alarmnumbers", rompr_index: ourindex, name: "Time"}).val(alarm.Time).appendTo(froggy);

		var toady = $('<td width="99%" rowspan="2">').appendTo(row1);
		$('<div>', {class: 'playlistrow2'}).setAlarmDays(alarm).appendTo(toady);

		var onbutton = $('<td width="99%" align="right">').appendTo(row1);
		var row2 = $('<tr>').appendTo(lego);
		var delbutton = $('<td align="right">').appendTo(row2);

		if (alarm.Running == 0) {
			$('<i>', {class: "smallicon icon-cog-alt editalarm clickicon", rompr_index: ourindex, name: alarm.Alarmindex, style: "width: 2.5em"}).appendTo(delbutton);
		} else {
			// Don't allow alarms to be edited while they're running, it's too complicated
			$('<i>', {class: "smallicon icon-cog-alt", rompr_index: ourindex, name: alarm.Alarmindex, style: "width: 2.5em;opacity:0.3"}).appendTo(delbutton);
		}

		var onbut = $('<i>', {class: "smallicon", rompr_index: ourindex, name: alarm.Alarmindex, style: "width: 2.5em"}).appendTo(onbutton);
		if (alarm.Alarmindex != 'NEW') {
			if (alarm.Pid) {
				onbut.addClass('icon-alarm-on enablealarm clickicon');
			} else {
				onbut.addClass('icon-alarm enablealarm clickicon');
			}
		}
		if (alarm.Running == 1)
			onbut.addClass('spinner');
	}

	function makeACheckbox(container, label, value, name, isspaced, auto) {
		var cls = isspaced ? 'tracktime styledinputs' : 'toomanyclasses styledinputs';
		if (auto) {
			bx = 'alarmvalue'
		} else {
			bx = 'alarmday';
		}
		let pd = $('<div>', {class: cls}).appendTo(container);
		let id = 'alarmbananga_'+frug;
		frug++;
		var c = $('<input>', {type: 'checkbox', id: id, name: name, class: bx}).appendTo(pd);
		var l = $('<label>', {for: id}).appendTo(pd);
		l.html(label);
		c.prop('checked', (value == 1 ? true: false));
	}

	function buttonOpacity(div, full) {
		if (full) {
			div.css({opacity: '1'});
		} else {
			div.css({opacity: '0.3'});
		}
	}

	function putAlarmDropPlayItem(stuff, dropper) {
		dropper.empty().removeClass('alarmdropempty').html(stuff.replace(/\\"/g, '"'));
	}

	return {

		populate_alarms: async function() {
			newalarms = await $.ajax({
				type: 'GET',
				url: 'api/alarmclock/?populate=1'
			});
			newalarms.push({
				'Alarmindex': 'NEW',
				'Time': '00:00',
				'Days': '',
				'ItemToPlay': '',
				'PlayCommands': '',
				'StopMins': 60,
				'Running': 0
			});
			if (newalarms.equals(alarms)) {
				debug.debug('ALARMS', 'Alarm state has not changed');
			} else {
				alarms = newalarms;
				debug.debug('ALARMS', alarms);
				holder.empty();
				fillWindow();
			}
		},

		// We don't do this on reaction to a player state change
		// because the backend sometimes stop playback before it
		// starts an alarm going, which makes us cancel it immediately.
		// Also this means that only the browser that pressed stop or
		// pause sends a command to cancel or sleep
		pre_stop_actions: function() {
			if (alarm_running !== false) {
				$.get({
					type: 'GET',
					url: 'api/alarmclock/?stop=1'
				});
			}
		},

		pre_pause_actions: function() {
			if (alarm_running !== false) {
				$.get({
					type: 'GET',
					url: 'api/alarmclock/?snooze=1'
				});
			}
		},

		dropped: async function(event, element) {
			if (event) {
				event.stopImmediatePropagation();
			}
			var index = element.attr('rompr_index');
			var items = $('.selected').filter(onlyAlbums).removeClass('selected').clone();
			var elements = items.wrapAll('<div></div>').parent();
			$('.selected').removeClass('selected');
			items.find('.menu').remove();
			items.find('.icon-menu').remove();
			items.find('.clickable.clickicon').remove();
			$('input.alarmvalue[name="ItemToPlay"]').val(items.html());
			// We want a return value but it's an async function
			// so we have to call it this way otherwise pc is just
			// set to the Promise and that's not what we want.
			let pc = await player.controller.addTracks(
				playlist.ui_elements_to_rompr_commands(items),
				null,
				null,
				false,
				true
			);

			$('input.alarmvalue[name="PlayCommands"]').val(JSON.stringify(pc));
			putAlarmDropPlayItem(items.html(), element);
		},

		deleteAlarm: async function(event, button) {
			var index = editor_popup.find('input.alarmvalue[name="Alarmindex"]').val();
			debug.log('ALARMS', 'Deleting Alarm', index);
			await $.get({
				type: 'GET',
				url: 'api/alarmclock/?index='+index+'&remove=1'
			});
			alarmclock.populate_alarms();
		},

		enableAlarm: async function(event, button) {
			var index = button.attr('name');
			debug.log('ALARMS', 'Toggling Alarm', index);
			var enable = button.hasClass('icon-alarm-on') ? 0 : 1;
			await $.get({
				type: 'GET',
				url: 'api/alarmclock/?index='+index+'&enable='+enable
			});
			alarmclock.populate_alarms();
		},

		editAlarm: function(event, button) {
			var index = button.attr('name');
			var ourindex = button.attr('rompr_index');
			debug.log('ALARMS', 'Editing Alarm', index, ourindex);
			var alarm = alarms[ourindex];
			if (alarm_editor !== null)
				alarm_editor.close(null);

			alarm_editor = new popup({
				css: {
					width: 500,
				},
				title: 'Alarm For Player '+prefs.currenthost,
				fitheight: true,
				hasscrollbar: true,
				atmousepos: true,
				mousevent: event
			});

			editor_popup = alarm_editor.create();

			editor_popup.append($('<input>', {type: 'hidden', class: 'alarmvalue', name: 'Player', value: prefs.currenthost}));
			editor_popup.append($('<input>', {type: 'hidden', class: 'alarmvalue', name: 'Alarmindex', value: alarm.Alarmindex}));

			// Time
			var td = $('<div>', {class: 'containerbox snapgrouptitle vertical-centre'}).appendTo(editor_popup);
			$('<input>', {type: "time", style: 'width:5em', class: "fixed snapclientname alarmnumbers alarmvalue", name: "Time"}).val(alarm.Time).appendTo(td);

			var dopebox = $('<div>', {class: 'fixed'}).appendTo(td);

			// Ramp
			makeACheckbox(dopebox, language.gettext('config_alarm_ramp'), alarm.Ramp, 'Ramp', true, true);

			// Stopafter
			var twott = $("<div>", {class: 'containerbox vertical-centre'}).appendTo(dopebox);
			var twitt = $('<div>', {class: 'fixed'}).appendTo(twott);
			makeACheckbox(twitt, language.gettext('config_alarm_stopafter'), alarm.Stopafter, 'Stopafter', true, true);
			// StopMins
			$('<input>', {type: 'number', class: 'expand alarmvalue', name: 'StopMins', style: 'margin-left:1em;width:5em'}).val(alarm.StopMins).appendTo(twott);

			var soapbox = $('<div>', {class: 'containerbox'}).appendTo(editor_popup);
			var repbox = $('<div>', {class: 'fixed'}).appendTo(soapbox);

			// Repeat
			makeACheckbox(repbox, language.gettext('button_repeat'), alarm.Repeat, 'Repeat', false, true);

			//Days
			// NOTE Alarm.Days must NOT be NULL
			var shepbox = $('<div>').appendTo(repbox);
			var adhd = alarm.Days.split(',');
			['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'].forEach(function(v) {
				makeACheckbox(shepbox, v, (adhd.indexOf(v) > -1), v, true, false);
			});
			buttonOpacity(shepbox, (alarm.Repeat == 1 ? true : false));

			var ropebox = $('<div>', {class: 'expand ropeybit'}).appendTo(soapbox);

			// PlayItem
			makeACheckbox(ropebox, language.gettext('label_alarm_play_specific'), alarm.PlayItem, 'PlayItem', false, true);

			// ItemToPlay
			var alarmdropper = $('<div>', {id: 'alarmdropper', rompr_index: ourindex, style: 'margin-left:4px', class: 'alarmdropempty canbefaded containerbox menuitem'}).appendTo(ropebox);

			if (alarm.ItemToPlay == '') {
				alarmdropper.html('<div class="containerbox menuitem fullwidth" style="height:100%"><div class="expand textcentre">'+language.gettext('label_alarm_to_play')+'</div></div>');
			} else {
				putAlarmDropPlayItem(alarm.ItemToPlay, alarmdropper);
			}
			buttonOpacity(alarmdropper, (alarm.PlayItem == 1 ? true : false));
			alarmdropper.acceptDroppedTracks({ ondrop: alarmclock.dropped });

			editor_popup.append($('<input>', {type: 'hidden', class: 'alarmvalue', name: 'ItemToPlay', value: alarm.ItemToPlay}));
			editor_popup.append($('<input>', {type: 'hidden', class: 'alarmvalue', name: 'PlayCommands', value: alarm.PlayCommands}));

			editor_popup.on('click', 'label', alarmclock.labelclick);

			alarm_editor.addCloseButton('Save', alarmclock.close_editor);
			if (alarm.Alarmindex != 'NEW')
				alarm_editor.addCloseButton('Delete', alarmclock.deleteAlarm);

			alarm_editor.open();

		},

		edit_alarm_time: async function(event) {
			let index = parseInt($(this).attr('rompr_index'));
			debug.log('ALARMS', 'Edit alarm time', index);
			alarms[index].Time = $(this).val();
			await $.ajax({
				type: 'POST',
				url: 'api/alarmclock/',
				data: JSON.stringify(alarms[index]),
				contentType: false
			});
			alarmclock.populate_alarms();
		},

		close_editor: async function() {
			debug.log('ALARMS', 'Editor Closed');
			var options = {};
			editor_popup.find('input.alarmvalue').each(function() {
				let i = $(this);
				if (i.attr('type') == 'checkbox' && i.prop('checked')) {
					options[i.attr('name')] = 1;
				} else if (i.attr('type') == 'checkbox' && !i.prop('checked')) {
					options[i.attr('name')] = 0;
				} else {
					options[i.attr('name')] = i.val();
				}
			});
			let days = [];
			editor_popup.find('input.alarmday').each(function() {
				let i = $(this);
				if (i.prop('checked')) {
					days.push(i.attr('name'));
				}
			});
			options.Days = days.join(',');
			await $.ajax({
				type: 'POST',
				url: 'api/alarmclock/',
				data: JSON.stringify(options),
				contentType: false
			});
			alarmclock.populate_alarms();
		},

		labelclick: function(event) {
			var element = $(event.target).prev();
			if (element.attr('name') == 'Repeat' || element.attr('name') == 'PlayItem') {
				buttonOpacity(element.parent().next(), !element.prop('checked'));
			}
		},

		setup: function() {
			var d = uiHelper.createPluginHolder('icon-alarm', language.gettext('button_alarm'), 'alarmclock_icon', 'alarmpanel');
			if (d === false) {
				return false;
			}
			var outer = uiHelper.makeDropHolder('alarmpanel', d, true, true, true);
			if ($('body').hasClass('phone')) {
				// Give it a close button so it can be closed on small screens when
				// the opener icon is in the onlyverysmall menu
				outer.append(uiHelper.ui_config_header({label: 'button_alarm', icon_size: 'smallicon', righticon: 'topbarmenu icon-cancel-circled'}));
			} else {
				outer.append(uiHelper.ui_config_header({label: 'button_alarm', icon_size: 'smallicon'}));
			}
			outer.append($('<input>', {type: "hidden", class: "helplink", value: "https://fatg3erman.github.io/RompR/Alarm-And-Sleep"}));
			holder = $('<div>', {class: 'fullwidth'}).appendTo(outer);

			alarmclock.populate_alarms();

			var html = '<table width="100%">';
			html += '<tr><td colspan="2"><div class="podcastitem"></div></td></tr>';
			html += '<tr><td class="altablebit">'+language.gettext('config_ramptime')+'</td><td><input class="saveotron prefinput" id="alarm_ramptime" type="number" size="2" /></td></tr>';
			html += '<tr><td class="altablebit">'+language.gettext('config_snoozetime')+'</td><td><input class="saveotron prefinput" id="alarm_snoozetime" type="number" min="0" size="2" /></td></tr>';
			html += '</table>';
			outer.append(html);

			$('#alarm_ramptime').html(prefs.alarm_ramptime);
			$('#alarm_snoozetime').html(prefs.alarm_snoozetime);

			$('#alarmpanel').on('click', function(event) {
				// We need to use a single click event handler for the whole panel:
				// We must prevent propagation of clicks anywhere on the panel, otherwise it will close
				event.stopPropagation();
				var element = $(event.target);
				if (element.hasClass('enablealarm')) {
					alarmclock.enableAlarm(event, element);
				} else if (element.hasClass('editalarm')) {
					alarmclock.editAlarm(event, element);
				}
			});

			$('#alarmpanel').on('change', '.alarmtimeedit', alarmclock.edit_alarm_time);
		}
	}
}();

pluginManager.addPlugin("Alarm Clock", null, alarmclock.setup, null, false);
player.controller.addStateChangeCallback({state: 'play', callback: alarmclock.populate_alarms});
player.controller.addStateChangeCallback({state: 'pause', callback: alarmclock.populate_alarms});
player.controller.addStateChangeCallback({state: 'stop', callback: alarmclock.populate_alarms});
sleepHelper.addWakeHelper(alarmclock.populate_alarms);

