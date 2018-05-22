var alarm = function() {

	var inctimer = null;
	var inctime = 500;
	var incamount = null;
	var alarmtime = parseInt(prefs.alarmtime);
	var alarmtimer = null;
	var uservol = 100;
	var volinc = 1;
	var ramptimer = null;
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
			}
		},

		setAlarm: function() {
			clearTimeout(alarmtimer);
			if (prefs.alarmon && !snoozing) {
				var d = new Date();
				var currentTime = d.getSeconds() + (d.getMinutes() * 60) + (d.getHours() * 3600);
				debug.log("ALARM", "Current Time Is",currentTime);
				if (alarmtime > currentTime) {
					var t = alarmtime - currentTime;
				} else {
					var t = 86400 - (currentTime - alarmtime);
				}
				debug.log("ALARM","Alarm will go off in",t,"seconds");
				alarmtimer = setTimeout(alarm.Ding, t*1000);
			} else {
				debug.log("ALARM","Alarm Disabled or snoozing");
			}
		},

		Ding: function() {
			debug.log("ALARM","WAKEY WAKEY!");
			snoozing = false;
			if (player.status.state != "play") {
				if (prefs.alarmramp) {
					uservol = parseInt(player.status.volume);
					volinc = uservol/prefs.alarm_ramptime;
					debug.log("ALARM","User Volume is",uservol,"increment step is",volinc);
					player.controller.volume(0, player.controller.play);
					ramptimer = setTimeout(alarm.volRamp, 1000);
				} else {
					player.controller.play();
				}
			}
			infobar.notify(infobar.NOTIFY,'<i class="icon-alarm-on bigalarm"></i>');
			alarm.setAlarm();
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
			if (snoozing) {
				debug.log("ALARM","Snoozing OFF");
				clearTimeout(alarmtimer);
				player.controller.pause();
				snoozing = false;
				infobar.notify(infobar.NOTIFY, "Snooze OFF");
				$("#alarmclock").stopFlasher();
				alarm.setAlarm();
				$("#freddibnah").text('Snooze...');
			} else {
				if (player.status.state == "play") {
					debug.log("ALARM","Snoozing");
					player.controller.pause();
					clearTimeout(alarmtimer);
					clearTimeout(inctimer);
					if (prefs.alarmramp) {
						player.controller.volume(uservol);
					}
					alarmtimer = setTimeout(alarm.Ding, prefs.alarm_snoozetime*60000);
					$("#alarmclock").makeFlasher({flashtime: 10, repeats: prefs.alarm_snoozetime*6});
					snoozing = true;
					debug.log("ALARM","Alarm will go off in",prefs.alarm_snoozetime,"minutes");
					infobar.notify(infobar.NOTIFY, "Snoozing....");
					$("#freddibnah").text('UnSnooze...');
				}
			}
		},

		setup: function() {
			var html = '<div class="topdrop">';
			if (small_plugin_icons) {
				html += '<i id="alarmclock" class="icon-alarm smallpluginicon tooltip clickicon" title="'+language.gettext('button_alarm')+'"></i>'
			} else {
				html += '<i id="alarmclock" class="icon-alarm topimg tooltip" title="'+language.gettext('button_alarm')+'"></i>';
			}
			html += '<div class="topdropmenu dropshadow rightmenu normalmenu stayopen" id="alarmpanel">'+
				'<div class="textcentre configtitle"><b>'+language.gettext('button_alarm')+'</b></div>'+
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
			html += '<table align="center" width="95%">';
			html += '<tr>';
			html += '<td colspan="3"><div class="styledinputs textcentre"><input type="checkbox" class="autoset toggle" id="alarmon"><label for="alarmon">ON</label></div></td>';
			html += '</tr>';
			html += '<tr>';
			html += '<td colspan="3"><div class="styledinputs textcentre"><input type="checkbox" class="autoset toggle" id="alarmramp"><label for="alarmramp">'+language.gettext('config_alarm_ramp')+'</label></div></td>';
			html += '</tr>';
			html += '<tr><td colspan="2">'+language.gettext('config_ramptime')+'</td><td><input class="saveotron prefinput" id="alarm_ramptime" type="text" size="2" /></td></tr>';
			html += '<tr><td colspan="2">'+language.gettext('config_snoozetime')+'</td><td><input class="saveotron prefinput" id="alarm_snoozetime" type="text" size="2" /></td></tr>';
			html += '<tr><td colspan="3" align="center" colspan="2"><button id="freddibnah" onclick="alarm.snooze()">Snooze...</button></td></tr>';
			html += '</table>';
			html += '</div></div></div>';

			$("#righthandtop").prepend(html);
			html = null;
			alarm.setBoxes();
			alarm.setButton();
			alarm.setAlarm();
			shortcuts.add('button_alarmsnooze', alarm.snooze, "B");
		}

	}

}();

pluginManager.addPlugin("Alarm Clock", null, alarm.setup, null, null);
