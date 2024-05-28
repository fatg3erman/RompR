var sleepTimer = function() {

	var inctimer = null;
	var inctime = 500;
	var incamount = null;
	var timeset = 0;
	var polltimer;
	var boxtimer;
	var fading = false;

	async function sleep_timer_command(cmd) {
		let response = await fetch(cmd,
			{
				signal: AbortSignal.timeout(5000),
				cache: 'no-store',
				method: 'GET',
				priority: 'low',
			}
		);
		let state = await response.json();
		sleepTimer.process_state(state);
	}

	return {

		startInc: function(amount) {
			incamount = amount;
			inctime = 500;
			sleepTimer.runIncrement();
		},

		stopInc: function() {
			clearTimeout(inctimer);
			prefs.save({sleeptime: prefs.sleeptime});
			sleepTimer.setTimer(false);
		},

		runIncrement: function() {
			clearTimeout(inctimer);
			prefs.sleeptime = Math.max(prefs.sleeptime+incamount, 1);
			sleepTimer.setBoxes();
			inctimer = setTimeout(sleepTimer.runIncrement, inctime);
			inctime = Math.max(50, inctime-50);
		},

		startFInc: function(amount) {
			incamount = amount;
			inctime = 500;
			sleepTimer.runFIncrement();
		},

		stopFInc: function() {
			clearTimeout(inctimer);
			prefs.save({fadetime: prefs.fadetime});
			sleepTimer.setTimer(false);
		},

		runFIncrement: function() {
			clearTimeout(inctimer);
			prefs.fadetime = Math.max(prefs.fadetime+incamount, 1);
			sleepTimer.setBoxes();
			inctimer = setTimeout(sleepTimer.runFIncrement, inctime);
			inctime = Math.max(50, inctime-50);
		},

		setBoxes: function() {
			clearTimeout(boxtimer);
			$("#sleepminutes").html(prefs.sleeptime.toString());
			$("#fadeseconds").html(prefs.fadetime.toString());
			if (timeset == 0) {
				$('#sleepruntime').html('');
				if (fading) {
					sleepTimer.setIncButtons();
					fading = false;
				}
			} else {
				let runtime = Math.round((prefs.sleeptime * 60) - ((Date.now() / 1000) - timeset));
				let html = '<i class="icon-sleep svg-square"></i>';
				if (runtime > 0) {
					let mins = Math.floor(runtime/60);
					let seconds = zeroPad((runtime % 60), 2);
					html = mins+':'+seconds;
					boxtimer = setTimeout(sleepTimer.setBoxes, 1000);
				} else {
					// Once the fade starts we can't permit any of the parameters to be changed
					// otherwise the new fade will start from wherever the current one has got to
					// and will only restore the volume to that value afterwards
					fading = true;
					sleepTimer.unsetIncButtons();
				}
				$('#sleepruntime').html(html);
			}
		},

		toggle: function() {
			prefs.save({sleepon: !prefs.sleepon});
			sleepTimer.setButton();
			sleepTimer.setTimer(true);
		},

		setButton: function() {
			if (prefs.sleepon) {
				$("#sleeptimer_icon").removeClass('currentbun').addClass('currentbun');
				$('#sleepon').removeClass('currentbun').addClass('currentbun');
			} else {
				$("#sleeptimer_icon").removeClass('currentbun');
				$('#sleepon').removeClass('currentbun');
			}
		},

		setTimer: async function(toggled) {
			clearTimeout(polltimer);
			let enable = (prefs.sleepon) ? '1' : '0';
			if (toggled || prefs.sleepon) {
				await sleep_timer_command(
					'api/sleeptimer/?enable='+enable+'&sleeptime='+prefs.sleeptime.toString()+'&fadetime='+prefs.fadetime.toString()
				);
			}
			sleepTimer.set_poll_timer();
		},

		pollState: async function() {
			clearTimeout(polltimer);
			debug.debug('SLEEPTIMER', 'Polling');
			await sleep_timer_command('api/sleeptimer/?poll=1');
			sleepTimer.set_poll_timer();
		},

		set_poll_timer: function() {
			polltimer = setTimeout(sleepTimer.pollState, 60000);
		},

		process_state: function(state) {
			debug.debug('SLEEPTIMER', state);
			prefs.save({sleepon: (state.state == 1)});
			if (state.sleeptime) {
				prefs.save({sleeptime: parseInt(state.sleeptime)});
			}
			if (state.fadetime) {
				prefs.save({fadetime: parseInt(state.fadetime)});
			}
			timeset = parseInt(state.timeset);
			sleepTimer.setButton();
			sleepTimer.setBoxes();
		},

		browser_sleep: function() {
			clearTimeout(polltimer);
			clearTimeout(boxtimer);
		},

		fakeClick: function() {
			$('#sleepon').trigger(prefs.click_event);
		},

		setIncButtons: function() {
			$('#sleepinc').on('pointerdown', sleepTimer.upSleep);
			$('#sleepinc').on('pointerup', sleepTimer.stopInc);
			$('#sleepdec').on('pointerdown', sleepTimer.downSleep);
			$('#sleepdec').on('pointerup', sleepTimer.stopInc);
			$('#fadeinc').on('pointerdown', sleepTimer.upFade);
			$('#fadeinc').on('pointerup', sleepTimer.stopFInc);
			$('#fadedec').on('pointerdown', sleepTimer.downFade);
			$('#fadedec').on('pointerup', sleepTimer.stopFInc);
		},

		unsetIncButtons: function() {
			$('#sleepinc').off('pointerdown');
			$('#sleepinc').off('pointerup');
			$('#sleepdec').off('pointerdown');
			$('#sleepdec').off('pointerup');
			$('#fadeinc').off('pointerdown');
			$('#fadeinc').off('pointerup');
			$('#fadedec').off('pointerdown');
			$('#fadedec').off('pointerup');
		},

		upSleep: function() {
			sleepTimer.startInc(1);
		},

		downSleep: function() {
			sleepTimer.startInc(-1);
		},

		upFade: function() {
			sleepTimer.startFInc(1);
		},

		downFade: function() {
			sleepTimer.startFInc(-1);
		},

		setup: function() {
			var d = uiHelper.createPluginHolder('icon-sleep', language.gettext('button_sleep'), 'sleeptimer_icon', 'sleeppanel');
			if (d === false) {
				return false;
			}
			var holder = uiHelper.makeDropHolder('sleeppanel', d, false, false, true);
			if ($('body').hasClass('phone')) {
				// Give it a close button so it can be closed on small screens when
				// the opener icon is in the onlyverysmall menu
				var html = uiHelper.ui_config_header({label: 'button_sleep', icon_size: 'smallicon', righticon: 'topbarmenu icon-cancel-circled'});
			} else {
				var html = uiHelper.ui_config_header({label: 'button_sleep', icon_size: 'smallicon'});
			}
			html += '<input type="hidden" class="helplink" value="https://fatg3erman.github.io/RompR/Alarm-And-Sleep#sleep-timer" />'+
				'<div class="noselection">'+
				'<table width="90%" align="center">'+
				'<tr>'+
				'<td align="left" rowspan="2"><i id="sleepon" class="medicon icon-sleep clickicon"></i></td>'+
				'<td align="right">Time</td>'+
				'<td align="center"><i id="sleepinc" class="icon-increase smallicon clickicon"></i></td>'+
				'<td align="center" class="alarmnumbers" id="sleepminutes">12</td>'+
				'<td align="center"><i id="sleepdec" class="icon-decrease smallicon clickicon"></i></td>'+
				'<td rowspan="2" align="right" class="alarmnumbers" id="sleepruntime"</td>' +
				'</tr>'+

				'<tr>'+
				'<td align="right">Fade</td>'+
				'<td align="center"><i id="fadeinc" class="icon-increase smallicon clickicon"></i></td>'+
				'<td align="center" id="fadeseconds">1</td>'+
				'<td align="center"><i id="fadedec" class="icon-decrease smallicon clickicon"></i></td>'+
				'</tr>'+

				'</table>';
			html += '</div>';
			holder.html(html);
			sleepTimer.pollState();
			$('#sleepon').on(prefs.click_event, sleepTimer.toggle);
			sleepTimer.setIncButtons();
			if (typeof(shortcuts) != 'undefined')
				shortcuts.add('button_sleep', sleepTimer.fakeClick, "Q");
		}

	}

}();

pluginManager.addPlugin("Sleep Timer", null, sleepTimer.setup, null, false);
sleepHelper.addWakeHelper(sleepTimer.pollState);
sleepHelper.addSleepHelper(sleepTimer.browser_sleep);
player.controller.addStateChangeCallback({state: 'pause', callback: sleepTimer.pollState});


