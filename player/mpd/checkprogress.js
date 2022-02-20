function disable_player_events() {

}

function enable_player_events() {

}

async function checkProgress() {
	var AlanPartridge = 5;
	var safetytimer = 250;
	var waittime = 1000;
	while (true) {
		if (sleepHelper.isVisible()) {
			await playlist.is_valid();
			if (AlanPartridge >= 5) {
				AlanPartridge = 0;
				await player.controller.do_command_list([]);
				updateStreamInfo();
			}
			if (player.status.state == 'play') {
				player.status.progress = (Date.now()/1000) - player.controller.trackstarttime;
			} else {
				player.status.progress = player.status.elapsed;
			}
			var duration = playlist.getCurrent('Time') || 0;
			infobar.setProgress(player.status.progress, duration);
			if (player.status.songid !== player.controller.previoussongid) {
				safetytimer = 250;
			}
			if (player.status.state == 'play' && duration > 0 && player.status.progress >= (duration - 1)) {
				AlanPartridge = 5;
				safetytimer = Math.min(safetytimer + 100, 5000);
				waittime = safetytimer;
			} else {
				AlanPartridge++;
				waittime = 1000;
			}
		}
		await new Promise(t => setTimeout(t, waittime));
	}
}
