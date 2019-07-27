var sleepTimer = function() {

	var inctimer = null;
	var inctime = 500;
	var incamount = null;
	var sleeptime = parseInt(prefs.sleeptime);
	var sleeptimer = null;
	var uservol = 100;
	var volinc = 1;
	var ramptimer = null;
	var ramptime = 60;

	return {

		startInc: function(amount) {
			debug.log("SLEEP","startInc",amount);
			incamount = amount;
			inctime = 500;
			sleepTimer.runIncrement();
		},

		runIncrement: function() {
			clearTimeout(inctimer);
			sleeptime += incamount;
			if (sleeptime < 0) {
				sleeptime = 0;
			}
			sleepTimer.setBoxes();
	        inctimer = setTimeout(sleepTimer.runIncrement, inctime);
	        inctime -= 50;
	        if (inctime < 50) {
	        	inctime = 50;
	        }
		},

		stopInc: function() {
			clearTimeout(inctimer);
			prefs.save({sleeptime: sleeptime});
			sleepTimer.setTimer();
		},

		setBoxes: function() {
	        $("#sleepminutes").html(sleeptime.toString());
		},

		toggle: function() {
			sleepTimer.setButton();
			sleepTimer.setTimer();
		},

		setButton: function() {
			if (prefs.sleepon) {
				$("#sleeptimer").makeFlasher({flashtime: 10, repeats: prefs.sleeptime*6});
			} else {
				$("#sleeptimer").stopFlasher();
			}
		},

		setTimer: function() {
			clearTimeout(sleeptimer);
			if (prefs.sleepon) {
				var t = prefs.sleeptime*60;
				debug.log("SLEEP","Sleeping in",t,"seconds");
				sleeptimer = setTimeout(sleepTimer.startSleep, t*1000);
			} else {
				debug.log("SLEEP","Sleep Disabled");
			}
		},

		startSleep: function() {
			debug.log("SLEEP","STARTING");
			if (player.status.state == "play") {
				uservol = parseInt(player.status.volume);
				volinc = uservol/ramptime;
				debug.log("SLEEP","User Volume is",uservol,"increment step is",volinc);
				ramptimer = setTimeout(sleepTimer.volRamp, 1000);
			}
			infobar.notify(language.gettext('label_sleepon'));
		},

		fakeClick: function() {
			$('#sleepon').trigger('click');
		},

		resetVolume: function() {
			player.controller.volume(uservol);
		},

		volRamp: function() {
			clearTimeout(ramptimer);
			var v = parseInt(player.status.volume) - volinc;
			if (v <= 0) {
				player.controller.stop();
				setTimeout(sleepTimer.resetVolume, 2000);
				if (prefs.sleepon) {
					sleepTimer.fakeClick();
				}
			} else {
				debug.log("SLEEP","Setting volume to",v,player.status.volume,volinc);
				player.controller.volume(Math.round(v));
				ramptimer = setTimeout(sleepTimer.volRamp, 1000);
			}
		},

		setup: function() {
			var d = uiHelper.createPluginHolder('icon-sleep', language.gettext('button_sleep'), 'sleeptimer_icon', 'sleeppanel');
			if (d === false) {
				return false;
			}
			var holder = uiHelper.makeDropHolder('sleeppanel', d, false);
			// var holder = $('<div>', {class: 'topdropmenu dropshadow rightmenu normalmenu stayopen', id: 'sleeppanel'}).appendTo(d);
			var html = '<div class="textcentre configtitle"><b>'+language.gettext('button_sleep')+'</b></div>'+
				'<input type="hidden" class="helplink" value="https://fatg3erman.github.io/RompR/Alarm-And-Sleep#sleep-timer" />'+
				'<div class="noselection">'+
				'<table align="center"><tr>'+
				'<td align="center"><i class="icon-increase smallicon clickicon" onmousedown="sleepTimer.startInc(1)" onmouseup="sleepTimer.stopInc()" onmouseout="sleepTimer.stopInc()"></i></td>'+
				'</tr><tr>'+
				'<td align="center" class="alarmnumbers" id="sleepminutes">12</td></tr><tr>'+
				'<td align="center"><i class="icon-decrease smallicon clickicon" onmousedown="sleepTimer.startInc(-1)" onmouseup="sleepTimer.stopInc()" onmouseout="sleepTimer.stopInc()" /></td>'+
				'<tr><td align="center">Minutes</td></tr>'+
				'</tr></table>';
			html += '<table align="center" width="95%">';
			html += '<tr>';
			html += '<td colspan="3"><div class="styledinputs textcentre"><input type="checkbox" class="autoset toggle" id="sleepon"><label for="sleepon">ON</label></div></td>';
			html += '</tr>';
			html += '</table>';
			html += '</div>';
			holder.html(html);
			sleepTimer.setBoxes();
			sleepTimer.setButton();
			sleepTimer.setTimer();
			shortcuts.add('button_sleep', sleepTimer.fakeClick, "Q");
		}

	}

}();

pluginManager.addPlugin("Sleep Timer", null, sleepTimer.setup, null, false);
