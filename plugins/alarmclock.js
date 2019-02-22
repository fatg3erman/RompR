var alarm = function() {

	var inctimer = null;
	var inctime = 500;
	var incamount = null;
	var alarmtime = parseInt(prefs.alarmtime);
	var alarmtimer = null;
	var uservol = 100;
	var volinc = 1;
	var ramptimer = null;
	var notification = null;
	var snoozing = false;

	return {

		startInc: function(amount) {
			debug.log("ALARM","startInc",amount);
			incamount = amount;
			inctime = 500;
			alarm.runIncrement();
		},

		runIncrement: function() {
			clearTimeout(inctimer);
			alarmtime += incamount;
			if (alarmtime > 86340) {
				alarmtime = alarmtime - 86400;
			} else if (alarmtime < 0) {
				alarmtime = 86400 + alarmtime;
			}
			alarm.setBoxes();
	        inctimer = setTimeout(alarm.runIncrement, inctime);
	        inctime -= 50;
	        if (inctime < 50) {
	        	inctime = 50;
	        }
		},

		stopInc: function() {
			clearTimeout(inctimer);
			prefs.save({alarmtime: alarmtime});
			alarm.setAlarm();
		},

		setBoxes: function() {
	        var mins = (alarmtime/60)%60;
	        var hours = alarmtime/3600;
	        $("#alarmhours").html(zeroPad(parseInt(hours.toString()), 2));
	        $("#alarmmins").html(zeroPad(parseInt(mins.toString()), 2));
		},

		toggle: function() {
			alarm.setButton();
			alarm.setAlarm();
		},

		setButton: function() {
			if (prefs.alarmon) {
				$("#alarmclock").removeClass("icon-alarm icon-alarm-on").addClass("icon-alarm-on");
			} else {
				$("#alarmclock").removeClass("icon-alarm icon-alarm-on").addClass("icon-alarm");
				if (notification !== null) {
					infobar.removenotify(notification);
					notification = null;
				}
			}
		},

		disable: function() {
			$('i.play-button').off('click', alarm.snooze);
		    $('i.stop-button').off('click', alarm.snooze);
			setPlayClicks();
			infobar.removenotify(notification);
			notification = null;
			snoozing = false;
			if (prefs.alarmrepeat) {
				alarm.setAlarm();
			} else {
				if (prefs.alarmon) {
					$('#alarmonbutton').click();
				}
			}
		},

		setAlarm: function() {
			clearTimeout(alarmtimer);
			if (prefs.alarmon) {
				var d = new Date();
				var currentTime = d.getSeconds() + (d.getMinutes() * 60) + (d.getHours() * 3600);
				debug.log("ALARM", "Current Time Is",currentTime);
				var t;
				if (!prefs.alarmrepeat) {
					if (alarmtime > currentTime) {
						t = alarmtime - currentTime;
					} else {
						t = 86400 - (currentTime - alarmtime);
					}
				} else {
					// Calculate number of seconds until next alarm time
					var today = d.getDay();
					var repeats = [
						prefs.alarmrepeat_sunday,
						prefs.alarmrepeat_monday,
						prefs.alarmrepeat_tuesday,
						prefs.alarmrepeat_wednesday,
						prefs.alarmrepeat_thursday,
						prefs.alarmrepeat_friday,
						prefs.alarmrepeat_saturday
					];
					if (repeats[today] && alarmtime > currentTime) {
						debug.log('ALARM','Alarm is set for later today');
						t = alarmtime - currentTime;
					} else {
						today++;
						var daycounter = 1;
						var daytimer = 0;
						while (!repeats[today] && daycounter < 8) {
							today++;
							daycounter++;
							if (today > 6) {
								today = 0
							}
						}
						debug.log('ALARM','Found next repeat day is day',today);
						if (daycounter == 8) {
							debug.warn('ALARM','Appears repeat was set but no days selected');
							return false;
						}
						debug.log('ALARM',daycounter,currentTime,alarmtime);
						t = (daycounter*86400) - (currentTime - alarmtime);
					}
				}
				debug.log("ALARM","Alarm will go off in",t,"seconds");
				alarmtimer = setTimeout(alarm.Ding, t*1000);
			} else {
				debug.log("ALARM","Alarm Disabled");
			}
		},

		Ding: function() {
			debug.log("ALARM","WAKEY WAKEY!");
			if (player.status.state != "play") {
				if (prefs.alarmramp) {
					if (snoozing) {
						player.controller.play();
					} else {
						uservol = parseInt(player.status.volume);
						player.controller.volume(0, alarm.startItOff);
					}
					volinc = uservol/prefs.alarm_ramptime;
					debug.log("ALARM","User Volume is",uservol,"increment step is",volinc);
				} else {
					alarm.startItOff();
				}
			}
			snoozing = false;
			if (notification == null) {
				notification = infobar.permnotify('<div class="containerbox"><i class="icon-alarm-on alarmbutton fixed"></i><div class="expand"></div><i class="icon-sleep alarmbutton fixed"></i></div>');
			}
		},

		startItOff: function() {
			offPlayClicks();
			$('i.play-button').on('click', alarm.snooze);
		    $('i.stop-button').on('click', alarm.snooze);
			player.controller.addStateChangeCallback({state: 'play', callback: alarm.volRamp});
			if (prefs.alarmplayitem) {
				playlist.addItems($('#alarmdropper').children(), null);
			} else {
				player.controller.play();
			}
		},

		volRamp: function() {
			clearTimeout(ramptimer);
			var v = parseInt(player.status.volume) + volinc;
			debug.log("ALARM","Setting volume to",v,player.status.volume,volinc);
			if (v >= uservol) {
				player.controller.volume(uservol);
			} else {
				player.controller.volume(Math.round(v));
				ramptimer = setTimeout(alarm.volRamp, 1000);
			}
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
			alarmtimer = setTimeout(alarm.Ding, prefs.alarm_snoozetime*60000);
			$('.icon-sleep.alarmbutton').makeFlasher({flashtime: 10, repeats: prefs.alarm_snoozetime*6});
			debug.log("ALARM","Alarm will go off in",prefs.alarm_snoozetime,"minutes");
		},

		doAlarmRepeat: function() {
			if ($('#alarmrepeatoptions').hasClass('disabledbuttons')) {
				$('#alarmrepeatoptions').removeClass('disabledbuttons');
			} else {
				$('#alarmrepeatoptions').addClass('disabledbuttons');
			}
		},

		doAlarmPlayItem: function() {
			if ($('#smeary').hasClass('disabledbuttons')) {
				$('#smeary').removeClass('disabledbuttons');
			} else {
				$('#smeary').addClass('disabledbuttons');
			}
		},

		dropped: function(event, element) {
			if (event) {
                event.stopImmediatePropagation();
            }
			debug.log("ALARM", "Dropped",$('.selected').filter(removeOpenItems));
			$('#alarmdropper').empty().removeClass('alarmdropempty');
			$('.selected').filter(onlyAlbums).clone().appendTo('#alarmdropper');
			$('.selected').removeClass('selected');
			$('#alarmdropper').find('.menu').remove();
			$('#alarmdropper').find('.icon-menu').remove();
			var s = $('#alarmdropper').html();
			prefs.save({alarm_itemtoplay: s});
			$('#alarmpanel').fanoogleMenus();
		},

		setup: function() {
			var d = uiHelper.createPluginHolder('icon-alarm', language.gettext('button_alarm'));
			if (d === false) {
				return false;
			}
			var holder = $('<div>', {class: 'topdropmenu dropshadow rightmenu normalmenu stayopen', id: 'alarmpanel'}).appendTo(d);
			var html = '<div class="textcentre configtitle"><b>'+language.gettext('button_alarm')+'</b></div>'+
				'<input type="hidden" class="helplink" value="https://fatg3erman.github.io/RompR/Alarm-And-Sleep" />'+
				'<div class="noselection">'+
				'<table align="center"><tr>'+
				'<td align="center"><i class="icon-increase smallicon clickicon" onmousedown="alarm.startInc(3600)" onmouseup="alarm.stopInc()" onmouseout="alarm.stopInc()"></i></td>'+
				'<td width="2%"></td><td align="center"><i class="icon-increase smallicon clickicon" onmousedown="alarm.startInc(60)" onmouseup="alarm.stopInc()" onmouseout="alarm.stopInc()" /></i></td>'+
				'</tr><tr>'+
				'<td align="center" class="alarmnumbers" id="alarmhours">12</td><td align="center" width="2%" class="alarmnumbers">:</td>'+
				'<td align="center" class="alarmnumbers" id="alarmmins">00</td></tr><tr>'+
				'<td align="center"><i class="icon-decrease smallicon clickicon" onmousedown="alarm.startInc(-3600)" onmouseup="alarm.stopInc()" onmouseout="alarm.stopInc()" /></td>'+
				'<td width="2%"></td><td align="center"><i class="icon-decrease smallicon clickicon" onmousedown="alarm.startInc(-60)" onmouseup="alarm.stopInc()" onmouseout="alarm.stopInc()" /></td>'+
				'</tr></table>';
			html += '<table width="98%">';

			html += '<tr><td><div class="styledinputs"><input type="checkbox" class="autoset toggle" id="alarmon"><label for="alarmon" id="alarmonbutton">'+language.gettext('config_alarm_on')+'</label></div></td></tr>'+
					'<tr><td><div class="styledinputs"><input type="checkbox" class="autoset toggle" id="alarmramp"><label for="alarmramp">'+language.gettext('config_alarm_ramp')+'</label></div></td></tr>'

			html += '<tr><td><div class="styledinputs"><input type="checkbox" class="autoset toggle" id="alarmrepeat"><label id="doalarmrepeat" for="alarmrepeat">'+language.gettext('button_repeat')+'</label></div></td></tr>';
			html += '<tr><td id="alarmrepeatoptions">';
			html += '<div class="styledinputs indent"><input type="checkbox" class="autoset toggle" id="alarmrepeat_monday"><label for="alarmrepeat_monday">'+language.gettext('label_monday')+'</label></div>';
			html += '<div class="styledinputs indent"><input type="checkbox" class="autoset toggle" id="alarmrepeat_tuesday"><label for="alarmrepeat_tuesday">'+language.gettext('label_tuesday')+'</label></div>';
			html += '<div class="styledinputs indent"><input type="checkbox" class="autoset toggle" id="alarmrepeat_wednesday"><label for="alarmrepeat_wednesday">'+language.gettext('label_wednesday')+'</label></div>';
			html += '<div class="styledinputs indent"><input type="checkbox" class="autoset toggle" id="alarmrepeat_thursday"><label for="alarmrepeat_thursday">'+language.gettext('label_thursday')+'</label></div>';
			html += '<div class="styledinputs indent"><input type="checkbox" class="autoset toggle" id="alarmrepeat_friday"><label for="alarmrepeat_friday">'+language.gettext('label_friday')+'</label></div>';
			html += '<div class="styledinputs indent"><input type="checkbox" class="autoset toggle" id="alarmrepeat_saturday"><label for="alarmrepeat_saturday">'+language.gettext('label_saturday')+'</label></div>';
			html += '<div class="styledinputs indent"><input type="checkbox" class="autoset toggle" id="alarmrepeat_sunday"><label for="alarmrepeat_sunday">'+language.gettext('label_sunday')+'</label></div>';
			html += '</td></tr>';

			html += '<tr><td><div class="styledinputs"><input type="checkbox" class="autoset toggle" id="alarmplayitem"><label id="doalarmplayitem" for="alarmplayitem">'+language.gettext('label_alarm_play_specific')+'</label></div></td></tr>';
			html += '<tr><td id="smeary"><div id="alarmdropper"><div class="containerbox menuitem" style="height:100%"><div class="expand textcentre">'+language.gettext('label_alarm_to_play')+'</div></div></div></td></tr>';

			html += '<tr><td><div class="podcastitem"></div></td></tr>';
			html += '</table>';
			html += '<table width="98%">';
			html += '<tr><td class="altablebit">'+language.gettext('config_ramptime')+'</td><td><input class="saveotron prefinput" id="alarm_ramptime" type="text" size="2" /></td></tr>';
			html += '<tr><td class="altablebit">'+language.gettext('config_snoozetime')+'</td><td><input class="saveotron prefinput" id="alarm_snoozetime" type="text" size="2" /></td></tr>';
			html += '</table>';
			html += '</div>';
			holder.html(html);
			alarm.setBoxes();
			alarm.setButton();
			alarm.setAlarm();
			shortcuts.add('button_alarmsnooze', alarm.snooze, "B");
			$('#doalarmrepeat').on('click', alarm.doAlarmRepeat);
			$('#doalarmplayitem').on('click', alarm.doAlarmPlayItem);
			if (!prefs.alarmrepeat) {
				$('#alarmrepeatoptions').addClass('disabledbuttons');
			}
			if (!prefs.alarmplayitem) {
				$('#smeary').addClass('disabledbuttons');
			}
			$(document).on('click', '.icon-alarm-on.alarmbutton', alarm.disable);
			$(document).on('click', '.icon-sleep.alarmbutton', alarm.snooze);
			$('#alarmdropper').acceptDroppedTracks({ ondrop: alarm.dropped });
			if (prefs.alarm_itemtoplay != '') {
				$('#alarmdropper').html(prefs.alarm_itemtoplay.replace(/\\"/g, '"'));
			} else {
				$('#alarmdropper').addClass('alarmdropempty')
			}
		}

	}

}();

pluginManager.addPlugin("Alarm Clock", null, alarm.setup, null, false);
