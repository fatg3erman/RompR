var alarmclock = function() {

	var inctimer = null;
	var inctime = 500;
	var incamount = null;
	var incindex = null;
	var alarmtimer = null;
	var currentalarm = null;
	var uservol = 100;
	var volinc = 1;
	var ramptimer = null;
	var notification = null;
	var snoozing = false;
	var alarminprogress = false;
	var topofwindow = null;
	var waitingforwake = false;
	var autostoptimer;
	var autosavetimer;

	function fillWindow() {
		var key = topofwindow;
		for (var a in prefs.alarms) {
			key = createAlarmHeader(key, prefs.alarms[a], a);
			key = createAlarmDropdown(key, prefs.alarms[a], a);
		}
	}

	function createNewAlarmBox(holder) {
		var container = $('<div>', {class: 'containerbox menuitem newalarmholder'}).appendTo(holder);
		$('<i>', {class: "mh menu fixed icon-plus createnewalarm"}).appendTo(container);
		$('<div>', {class: 'expand'}).html(language.gettext('label_new_alarm')).appendTo(container);
	}

	function createAlarmHeader(holder, alarm, index) {
		var container = $('<div>', {class: 'menuitem cheesemaster'}).insertAfter(holder);
		var lego = $('<table width="100%">').appendTo(container);
		var row1 = $('<tr>').appendTo(lego);
		var opener = $('<td rowspan="2">').appendTo(row1);
		$('<i>', {class: "mh menu openmenu fixed icon-toggle-closed", name: 'alarmpanel_'+index}).appendTo(opener);
		var hoursup = $('<td>', {class: 'timespinholder'}).appendTo(row1);
		var froggy = $('<td rowspan="2">').appendTo(row1);
		$('<div>', {class: 'alarmnumbers', id: 'alarm_time_'+index}).appendTo(froggy);
		var minsup = $('<td>', {class: 'timespinholder'}).appendTo(row1);
		var onbutton = $('<td rowspan="2" width="99%" align="right">').appendTo(row1);
		var ondiv = $('<div>', {class: 'styledinputs'}).appendTo(onbutton);
		var row2 = $('<tr>').appendTo(lego);
		var hoursdown = $('<td>', {class: 'timespinholder'}).appendTo(row2);
		var minsdown = $('<td>', {class: 'timespinholder'}).appendTo(row2);

		$('<i>', {class: 'playlisticon clickicon icon-increase expand timespinner', id: 'alarmhoursup_'+index}).appendTo(hoursup);
		$('<i>', {class: 'playlisticon clickicon icon-decrease expand timespinner', id: 'alarmhoursdown_'+index}).appendTo(hoursdown);
		$('<i>', {class: 'playlisticonr clickicon icon-increase expand timespinner', id: 'alarmminsup_'+index}).appendTo(minsup);
		$('<i>', {class: 'playlisticonr clickicon icon-decrease expand timespinner', id: 'alarmminsdown_'+index}).appendTo(minsdown);

		$('<input>', {type: 'checkbox', id: 'alarmon_'+index}).appendTo(ondiv);
		$('<label>', {for: 'alarmon_'+index, class: 'alarmclock', style: 'display:inline'}).appendTo(ondiv);

		$('<div>', {class: 'fixed playlistrow2 menuitem fullwidth alarmdescription', id: 'alarm_desc_'+index}).appendTo(container).css({'font-weight': 'normal'});

		$('#alarmon_'+index).prop('checked', alarm.alarmon);
		update_timebox(index);
		update_descbox(index);
		alarmclock.hideControls(index);
		return container;
	}

	function update_timebox(index) {
		var alarm = prefs.alarms[index];
		var mins = (alarm.alarmtime/60)%60;
		var hours = alarm.alarmtime/3600;
		$('#alarm_time_'+index).html(zeroPad(parseInt(hours.toString()), 2)+':'+zeroPad(parseInt(mins.toString()), 2));
	}

	function update_descbox(index) {
		var alarm = prefs.alarms[index];
		var descbox = $('#alarm_desc_'+index);
		var days = language.gettext('label_daylabels');
		if (alarm.alarmrepeat) {
			if (alarm.repeatdays[0] && alarm.repeatdays[1] && alarm.repeatdays[2] && alarm.repeatdays[3] && alarm.repeatdays[4] && alarm.repeatdays[5] && alarm.repeatdays[6]) {
				descbox.html(language.gettext('label_every_day'));
			} else if (alarm.repeatdays[1] && alarm.repeatdays[2] && alarm.repeatdays[3] && alarm.repeatdays[4] && alarm.repeatdays[5] && !alarm.repeatdays[0] && !alarm.repeatdays[6]) {
				descbox.html(language.gettext('label_every_wday'));
			} else if (!alarm.repeatdays[1] && !alarm.repeatdays[2] && !alarm.repeatdays[3] && !alarm.repeatdays[4] && !alarm.repeatdays[4] && alarm.repeatdays[0] && alarm.repeatdays[6]) {
				descbox.html(language.gettext('label_every_wend'));
			} else {
				var dy = new Array();
				for (var d in alarm.repeatdays) {
					if (alarm.repeatdays[d]) {
						dy.push(days[d]);
					}
				}
				descbox.html(dy.join(', '));
			}
		} else {
			descbox.html('');
		}
	}

	function createAlarmDropdown(holder, alarm, index) {
		var container = $('<div>', {id: 'alarmpanel_'+index, class: 'toggledown invisible cheesemaster'}).insertAfter(holder);

		var twatt = $('<div>', {class: 'containerbox dropdown-container'}).appendTo(container);
		$('<i>', {class: "mh menu fixed icon-cancel-circled deletealarm", id: 'deletealarm_'+index}).appendTo(twatt);
		$('<div>', {class: 'expand'}).html(language.gettext('label_delete_alarm')).appendTo(twatt);
		twatt.css({'margin-left': '4px', 'margin-bottom': '4px'});

		makeACheckbox('alarmramp_'+index, language.gettext('config_alarm_ramp'), container, alarm.alarmramp, false);

		var twott = $("<div>", {class: 'containerbox dropdown-container'}).appendTo(container);
		var twitt = $('<div>', {class: 'fixed'}).appendTo(twott);
		makeACheckbox('alarmstopafter_'+index, language.gettext('config_alarm_stopafter'), twitt, alarm.alarmstopafter, false);
		$('<input>', {type: 'text', class: 'expand alarmclock', id: 'alarmstopmins_'+index, style: 'margin-left:1em'}).val(alarm.alarmstopmins).appendTo(twott);

		makeACheckbox('alarmrepeat_'+index, language.gettext('button_repeat'), container, alarm.alarmrepeat, true);
		var reps =  $('<div>', {class: 'indent canbefaded'}).appendTo(container);
		var days = language.gettext('label_daylabels');
		for (var i in alarm.repeatdays) {
			makeACheckbox('repeatdays_'+index+'_'+i, days[i], reps, alarm.repeatdays[i], false);
		}
		buttonOpacity(reps, alarm.alarmrepeat);
		makeACheckbox('alarmplayitem_'+index, language.gettext('label_alarm_play_specific'), container, alarm.alarmplayitem, true);
		var alarmdropper = $('<div>', {id: 'alarmdropper_'+index, class: 'alarmdropempty canbefaded'}).appendTo(container);
		if (alarm.alarm_itemtoplay == '') {
			alarmdropper.html('<div class="containerbox menuitem" style="height:100%"><div class="expand textcentre">'+language.gettext('label_alarm_to_play')+'</div></div>');
		} else {
			putAlarmDropPlayItem(alarm, alarmdropper);
		}
		buttonOpacity(alarmdropper, alarm.alarmplayitem);
		alarmdropper.acceptDroppedTracks({ ondrop: alarmclock.dropped });
		return container;
	}

	function makeACheckbox(id, label, container, ischecked, isspaced) {
		var cls = isspaced ? 'toomanyclasses styledinputs' : 'styledinputs';
		var pd = $('<div>', {class: cls}).appendTo(container);
		var c = $('<input>', {type: 'checkbox', id: id}).appendTo(pd);
		var l = $('<label>', {for: id, class: 'alarmclock'}).appendTo(pd);
		l.html(label);
		c.prop('checked', ischecked);
	}

	function buttonOpacity(div, full) {
		if (full) {
			div.css({opacity: '1'});
		} else {
			div.css({opacity: '0.3'});
		}
	}

	function putAlarmDropPlayItem(alarm, dropper) {
		dropper.empty().removeClass('alarmdropempty').html(alarm.alarm_itemtoplay.replace(/\\"/g, '"'));
	}

	return {

		whatAHack: function() {
			$('#alarmpanel').fanoogleMenus();
		},

		showControls: function(index) {
			$('#alarmhoursup_'+index).fadeIn('fast');
			$('#alarmhoursdown_'+index).fadeIn('fast');
			$('#alarmminsup_'+index).fadeIn('fast');
			$('#alarmminsdown_'+index).fadeIn('fast');
		},

		hideControls: function(index) {
			$('#alarmhoursup_'+index).hide();
			$('#alarmhoursdown_'+index).hide();
			$('#alarmminsup_'+index).hide();
			$('#alarmminsdown_'+index).hide();
		},

		checkboxClicked: function(event, element) {
			event.stopImmediatePropagation();
			var box = element.prev();
			var cb = box.attr('id').split('_');
			var param = cb[0];
			var index = cb[1];
			if (cb.length == 3) {
				var subindex = cb[2];
				debug.log('ALARM',param,index,subindex, !box.is(':checked'));
				prefs.alarms[index][param][subindex] = !box.is(':checked');
				switch (param) {
					case 'repeatdays':
						update_descbox(index);
						break;
				}
			} else {
				debug.log('ALARM',param,index,!box.is(':checked'));
				prefs.alarms[index][param] = !box.is(':checked');
				switch (param) {
					case 'alarmrepeat':
						update_descbox(index);
						// fall through
					case 'alarmplayitem':
						buttonOpacity(element.parent().next(), !box.is(':checked'));
						break;
				}
			}
			prefs.save({alarms: prefs.alarms});
			alarmclock.setAlarm();
		},


		inputChanged: function(event) {
			clearTimeout(autosavetimer);
			var element = $(this);
			var cb = element.attr('id').split('_');
			var param = cb[0];
			var index = cb[1];
			var val = parseFloat(element.val());
			debug.log('ALARMCLOCK', 'Setting',param,'of alarm',index,'to',val);
			prefs.alarms[index][param] = val;
			autosavetimer = setTimeout(alarmclock.saveAlarms, 1000);
		},

		saveAlarms: function() {
			prefs.save({alarms: prefs.alarms});

		},

		startInc: function(element) {
			var cb = element.attr('id').split('_');
			incindex = cb[1];
			switch (cb[0]) {
				case 'alarmhoursup':
					incamount = 3600;
					break;
				case 'alarmhoursdown':
					incamount = -3600;
					break;
				case 'alarmminsup':
					incamount = 60;
					break;
				case 'alarmminsdown':
					incamount = -60;
					break;
			}
			inctime = 500;
			alarmclock.runIncrement();
		},

		runIncrement: function() {
			clearTimeout(inctimer);
			prefs.alarms[incindex].alarmtime += incamount;
			if (prefs.alarms[incindex].alarmtime > 86340) {
				prefs.alarms[incindex].alarmtime = prefs.alarms[incindex].alarmtime - 86400;
			} else if (prefs.alarms[incindex].alarmtime < 0) {
				prefs.alarms[incindex].alarmtime = 86400 + prefs.alarms[incindex].alarmtime;
			}
			update_timebox(incindex)
			inctimer = setTimeout(alarmclock.runIncrement, inctime);
			inctime -= 50;
			if (inctime < 50) {
				inctime = 50;
			}
		},

		stopInc: function() {
			clearTimeout(inctimer);
			prefs.save({alarms: prefs.alarms});
			alarmclock.setAlarm();
		},

		volRamp: function() {
			clearTimeout(ramptimer);
			var v = parseInt(player.status.volume) + volinc;
			debug.trace("ALARM","Setting volume to",v,player.status.volume,volinc);
			if (v >= uservol) {
				player.controller.volume(uservol);
			} else {
				player.controller.volume(Math.round(v));
				ramptimer = setTimeout(alarmclock.volRamp, 1000);
			}
		},

		disable: function() {
			var alarm = prefs.alarms[currentalarm];
			if (!alarm.alarmrepeat) {
				alarm.alarmon = false;
				prefs.save({alarms: prefs.alarms});
				$('#alarmon_'+currentalarm).prop('checked', false);
			}
			$('i.play-button').off('click', alarmclock.snooze);
			$('i.stop-button').off('click', alarmclock.snooze);
			setPlayClicks();
			infobar.removenotify(notification);
			notification = null;
			snoozing = false;
			alarminprogress = false;
			alarmclock.setAlarm();
		},

		setAlarm: function() {
			clearTimeout(alarmtimer);
			if (alarminprogress) {
				return false;
			}
			currentalarm = null;
			var alarmtime = 86400*7;
			var d = new Date();
			var currentTime = d.getSeconds() + (d.getMinutes() * 60) + (d.getHours() * 3600);
			debug.log("ALARM", "Current Time Is",currentTime);
			for (var i in prefs.alarms) {
				var alarm = prefs.alarms[i];
				if (typeof alarm.alarmstopmins == 'undefined') {
					alarm.alarmstopmins = 60;
					alarm.alarmstopafter = false;
					prefs.save({alarms: prefs.alarms});
				}
				if (alarm.alarmon) {
					var t;
					if (!alarm.alarmrepeat) {
						if (alarm.alarmtime > currentTime) {
							t = alarm.alarmtime - currentTime;
						} else {
							t = 86400 - (currentTime - alarm.alarmtime);
						}
					} else {
						// Calculate number of seconds until next alarm time
						var today = d.getDay();
						if (alarm.repeatdays[today] && alarm.alarmtime > currentTime) {
							debug.log('ALARM','Alarm',i,'is set for later today');
							t = alarm.alarmtime - currentTime;
						} else {
							today++;
							var daycounter = 1;
							var daytimer = 0;
							while (!alarm.repeatdays[today] && daycounter < 8) {
								today++;
								daycounter++;
								if (today > 6) {
									today = 0
								}
							}
							debug.log('ALARM','Found next repeat day is day',today);
							debug.log('ALARM',daycounter,currentTime,alarmtime);
							t = (daycounter*86400) - (currentTime - alarm.alarmtime);
						}
					}
					if (t < alarmtime) {
						alarmtime = t;
						currentalarm = i;
					}
				}
			}
			if (currentalarm !== null) {
				debug.log("ALARM","Alarm",currentalarm,"will go off in",alarmtime,"seconds");
				alarmtimer = setTimeout(alarmclock.Ding, alarmtime*1000);
				$("#alarmclock_icon").removeClass("icon-alarm icon-alarm-on").addClass("icon-alarm-on");
				if (!waitingforwake) {
					// try to re-set the alarm if we wake from sleep
					window.addEventListener('online', alarmclock.setAlarm);
					waitingforwake = true;
				}
			} else {
				$("#alarmclock_icon").removeClass("icon-alarm icon-alarm-on").addClass("icon-alarm");
				if (waitingforwake) {
					window.removeEventListener('online', alarmclock.setAlarm);
					waitingforwake = false;
				}
			}
			if (notification !== null) {
				infobar.removenotify(notification);
				notification = null;
			}
		},

		Ding: function() {
			alarminprogress = true;
			var alarm = prefs.alarms[currentalarm];
			if (player.status.state != "play") {
				if (alarm.alarmramp) {
					player.controller.addStateChangeCallback({state: 'play', callback: alarmclock.volRamp});
					if (snoozing) {
						player.controller.play();
					} else {
						uservol = parseInt(player.status.volume);
						volinc = uservol/prefs.alarm_ramptime;
						debug.log("ALARM","User Volume is",uservol,"increment step is",volinc);
						player.controller.volume(0, alarmclock.startItOff);
					}
				} else {
					alarmclock.startItOff();
				}
			}
			snoozing = false;
			if (notification == null) {
				notification = infobar.permnotify('<div class="containerbox"><i class="icon-alarm-on alarmbutton fixed clickicon"></i><div class="expand"></div></div>');
			}
		},

		startItOff: function() {
			offPlayClicks();
			clearTimeout(autostoptimer);
			$('i.play-button').on('click', alarmclock.snooze);
			$('i.stop-button').on('click', alarmclock.snooze);
			var alarm = prefs.alarms[currentalarm];
			if (alarm.alarmstopafter) {
				debug.mark('ALARMCLOCK', 'Alarm will auto-stop in',alarm.alarmstopmins,'minutes');
				autostoptimer = setTimeout(alarmclock.autoStop, alarm.alarmstopmins*60000);
			}
			if (alarm.alarmplayitem) {
				var items = $('#alarmdropper_'+currentalarm).children();
				// For neatness - don't keep putting radio stations back in the playlist, it's silly.
				// For other items well it'd be nice but we can't cover all eventualities
				if (items.length == 1 && items.first().hasClass('clickstream')) {
					var is_already_there = playlist.findIdByUri(decodeURIComponent(items.first().attr('name')));
					if (is_already_there !== false) {
						debug.log('ALARM', 'Alarm Item is already in playlist');
						player.controller.do_command_list([
							['playid', is_already_there]
						]);
						return true;
					}
				}
				playlist.addItems(items, null);
			} else {
				player.controller.play();
			}
		},

		autoStop: function() {
			alarmclock.disable();
			player.controller.stop();
		},

		snooze: function() {
			debug.log("ALARM","Snoozing");
			clearTimeout(alarmtimer);
			clearTimeout(ramptimer);
			$('.icon-sleep.alarmbutton').stopFlasher();
			snoozing = true;
			if (player.status.state == "play") {
				player.controller.pause();
			}
			alarmtimer = setTimeout(alarmclock.Ding, prefs.alarm_snoozetime*60000);
			$('.icon-sleep.alarmbutton').makeFlasher({flashtime: 10, repeats: prefs.alarm_snoozetime*6});
			debug.log("ALARM","Alarm will go off in",prefs.alarm_snoozetime,"minutes");
		},

		dropped: function(event, element) {
			if (event) {
				event.stopImmediatePropagation();
			}
			debug.log("ALARM", "Dropped",element.attr('id'));
			var cb = element.attr('id').split('_');
			var index = cb[1];
			var items = $('.selected').filter(onlyAlbums).removeClass('selected').clone().wrapAll('<div></div>').parent();
			$('.selected').removeClass('selected');
			items.find('.menu').remove();
			items.find('.icon-menu').remove();
			prefs.alarms[index].alarm_itemtoplay = items.html();
			prefs.save({alarms: prefs.alarms});
			putAlarmDropPlayItem(prefs.alarms[index], element);
			$('#alarmpanel').fanoogleMenus();
		},

		newAlarm: function() {
			var key = $('#alarmpanel').find('.toggledown').last();
			if (key.length == 0) {
				key = topofwindow;
			}
			var i = prefs.alarms.length;
			prefs.alarms[i] = {
				alarmtime: 43200,
				alarmon: false,
				alarmramp: false,
				alarmstopafter: false,
				alarmstopmins: 60,
				alarmrepeat: false,
				repeatdays: [false, false, false, false, false, false, false],
				alarmplayitem: false,
				alarm_itemtoplay: ''
			}
			prefs.save({alarms: prefs.alarms});
			key = createAlarmHeader(key, prefs.alarms[i], i);
			key = createAlarmDropdown(key, prefs.alarms[i], i);
			$('#alarmpanel').fanoogleMenus();
		},

		deleteAlarm: function(event, element) {
			var cb = element.attr('id').split('_');
			var index = cb[1];
			debug.log('ALARM','Deleting Alarm',index);
			prefs.alarms.splice(index, 1);
			prefs.save({alarms: prefs.alarms});
			$('.cheesemaster').remove();
			fillWindow();
			$('#alarmpanel').fanoogleMenus();
			alarmclock.setAlarm();
		},

		setup: function() {
			var d = uiHelper.createPluginHolder('icon-alarm', language.gettext('button_alarm'), 'alarmclock_icon', 'alarmpanel');
			if (d === false) {
				return false;
			}
			var holder = uiHelper.makeDropHolder('alarmpanel', d, true);
			holder.append('<div class="dropdown-container configtitle"><div class="textcentre expand"><b>'+language.gettext('button_alarm')+'</b></div></div>');
			topofwindow = $('<input>', {type: "hidden", class: "helplink", value: "https://fatg3erman.github.io/RompR/Alarm-And-Sleep"}).appendTo(holder);
			fillWindow();
			createNewAlarmBox(holder);

			var html = '<table width="98%">';
			html += '<tr><td colspan="2"><div class="podcastitem"></div></td></tr>';
			html += '<tr><td class="altablebit">'+language.gettext('config_ramptime')+'</td><td><input class="saveotron prefinput" id="alarm_ramptime" type="text" size="2" /></td></tr>';
			html += '<tr><td class="altablebit">'+language.gettext('config_snoozetime')+'</td><td><input class="saveotron prefinput" id="alarm_snoozetime" type="text" size="2" /></td></tr>';
			html += '</table>';
			holder.append(html);

			menuOpeners['alarmpanel'] = alarmclock.showControls;
			menuClosers['alarmpanel'] = alarmclock.hideControls;

			$('#alarmpanel').on('click', function(event) {
				// We need to use a single click event handler for the whole panel:
				// We must prevent propagation of clicks anywhere on the panel, otherwise it will close
				// But we obvioously need to react to clicks on checkboxes (because we're handling those, not the generic prefs mechanism)
				event.stopPropagation();
				var element = $(event.target);
				if (element.is('label') && element.hasClass('alarmclock')) {
					alarmclock.checkboxClicked(event, element);
				} else if (element.hasClass('openmenu')) {
					doMenu(event, element);
				} else if (element.hasClass('createnewalarm')) {
					alarmclock.newAlarm();
				} else if (element.hasClass('deletealarm')) {
					alarmclock.deleteAlarm(event, element);
				}
			});

			$('#alarmpanel').on('mousedown', function(event) {
				var element = $(event.target);
				if (element.hasClass('timespinner')) {
					event.stopPropagation();
					alarmclock.startInc(element);
				}
			});

			$('#alarmpanel').on('mouseup', function(event) {
				var element = $(event.target);
				if (element.hasClass('timespinner')) {
					event.stopPropagation();
					alarmclock.stopInc();
				}
			});

			$('#alarmpanel').on('keyup', 'input.alarmclock', alarmclock.inputChanged);

			$(document).on('click', '.icon-alarm-on.alarmbutton', alarmclock.disable);

			alarmclock.setAlarm();
		}

	}

}();

pluginManager.addPlugin("Alarm Clock", null, alarmclock.setup, null, false);
