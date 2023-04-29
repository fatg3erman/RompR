var sleepTimer = function() {

	var inctimer = null;
	var inctime = 500;
	var incamount = null;
	var timeset = 0;
	var polltimer;
	var boxtimer;

	return {

		startInc: function(amount) {
			debug.log('SLEEPTIMER', 'StartINC');
			incamount = amount;
			inctime = 500;
			sleepTimer.runIncrement();
		},

		runIncrement: function() {
			clearTimeout(inctimer);
			prefs.sleeptime += incamount;
			if (prefs.sleeptime < 0) {
				prefs.sleeptime = 0;
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
			prefs.save({sleeptime: prefs.sleeptime});
			sleepTimer.setTimer(false);
		},

		setBoxes: function() {
			clearTimeout(boxtimer);
			$("#sleepminutes").html(prefs.sleeptime.toString());
			if (timeset == 0) {
				$('#sleepruntime').html('');
			} else {
				let html = '<i class="icon-sleep svg-square"></i>';
				let runtime = Math.round((prefs.sleeptime * 60) - ((Date.now() / 1000) - timeset));
				if (runtime > 0) {
					let mins = Math.floor(runtime/60);
					let seconds = zeroPad((runtime % 60), 2);
					html = mins+':'+seconds;
					boxtimer = setTimeout(sleepTimer.setBoxes, 1000);
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
				let state = await $.ajax({
					type: 'GET',
					url: 'api/sleeptimer/?enable='+enable+'&sleeptime='+prefs.sleeptime.toString()
				});
				sleepTimer.process_state(state);
			}
			sleepTimer.set_poll_timer();
		},

		pollState: async function() {
			clearTimeout(polltimer);
			debug.debug('SLEEPTIMER', 'Polling');
			let state = await $.ajax({
				type: 'GET',
				url: 'api/sleeptimer/?poll=1'
			});
			sleepTimer.process_state(state);
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
			timeset = parseInt(state.timeset);
			sleepTimer.setButton();
			sleepTimer.setBoxes();
		},

		browser_sleep: function() {
			clearTimeout(polltimer);
			clearTimeout(boxtimer);
		},

		fakeClick: function() {
			$('#sleepon').trigger('click');
		},

		setup: function() {
			var d = uiHelper.createPluginHolder('icon-sleep', language.gettext('button_sleep'), 'sleeptimer_icon', 'sleeppanel');
			if (d === false) {
				return false;
			}
			var holder = uiHelper.makeDropHolder('sleeppanel', d, false, false, false);
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
				'<td align="left"><i id="sleepon" class="medicon icon-sleep clickicon"></i></td>'+
				'<td align="center"><i id="sleepinc" class="icon-increase smallicon clickicon"></i></td>'+
				'<td align="center" class="alarmnumbers" id="sleepminutes">12</td>'+
				'<td align="center"><i id="sleepdec" class="icon-decrease smallicon clickicon"></i></td>'+
				'<td align="right" class="alarmnumbers" id="sleepruntime"</td>' +
				'</tr>'+
				'</table>';
			html += '</div>';
			holder.html(html);
			sleepTimer.pollState();
			$('#sleepon').on('click', sleepTimer.toggle);
			$('#sleepinc').on('pointerdown', function() { sleepTimer.startInc(1) });
			$('#sleepinc').on('pointerup', function() { sleepTimer.stopInc() });
			$('#sleepdec').on('pointerdown', function() { sleepTimer.startInc(-1) });
			$('#sleepdec').on('pointerup', function() { sleepTimer.stopInc() });
			if (typeof(shortcuts) != 'undefined')
				shortcuts.add('button_sleep', sleepTimer.fakeClick, "Q");
		}

	}

}();

pluginManager.addPlugin("Sleep Timer", null, sleepTimer.setup, null, false);
sleepHelper.addWakeHelper(sleepTimer.pollState);
sleepHelper.addSleepHelper(sleepTimer.browser_sleep);
player.controller.addStateChangeCallback({state: 'pause', callback: sleepTimer.pollState});


