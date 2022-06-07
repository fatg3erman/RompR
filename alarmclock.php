<?php
const IS_ROMONITOR = true;
require_once ("includes/vars.php");
require_once ("includes/functions.php");
$opts = getopt('', ['alarmindex:']);
prefs::set_session_pref($opts);

logger::mark("ALARMCLOCK", "Initialising Alarm Clock For Index", prefs::get_pref('alarmindex'));

prefs::$database = new timers();
$alarm = prefs::$database->get_alarm(prefs::get_pref('alarmindex'));
prefs::$database->update_pid_for_alarm(prefs::get_pref('alarmindex'), getmypid());
prefs::$database->close_database();
prefs::$database = null;
logger::mark("ALARMCLOCK", "Player is",$alarm['Player']);
prefs::set_session_pref(['currenthost' => $alarm['Player']]);

$player = new base_mpd_player();
$player->close_mpd_connection();
$player = new player();
$player->close_mpd_connection();

while (true) {

	alarm_sleep_time();

	logger::log($alarm['Player'], 'Running Alarm',$alarm['Alarmindex'],$alarm['Name']);

	prefs::$database = new timers();
	prefs::$database->mark_alarm_running($alarm['Alarmindex'], true);
	prefs::$database->close_database();
	prefs::$database = null;

	$player->open_mpd_connection();
	$mpd_status = $player->do_command_list(['status']);
	// MPD won't report a volume while stopped, so we're just going to have to
	// ramp up to 100
	$volume = (array_key_exists('volume', $mpd_status)) ? $mpd_status['volume']  : 100;

	$playcommands = json_decode($alarm['PlayCommands'], true);
	$play_item = ($alarm['PlayItem'] == 1 && is_array($playcommands));

	if (!$play_item && $mpd_status['playlistlength'] == 0) {
		// If we haven't been given anything to play, and there's nothing in the queue, start playing all tracks at random
		prefs::set_radio_params([
			'radiomode' => 'starRadios',
			'radioparam' => 'allrandom'
		]);
		$player->prepare_smartradio();
		prefs::$database = new collection_radio();
		prefs::$database->preparePlaylist();
		prefs::$database->close_database();
		$player->check_radiomode();
		$mpd_status = $player->do_command_list(['status']);
	}

	if ($mpd_status['state'] == 'play' && ($alarm['Interrupt'] == 0 || $play_item === false)) {
		logger::log($alarm['Player'], 'Player is already playing. Alarm will not interrupt');
	} else if ($mpd_status['playlistlength'] == 0 && $play_item === false) {
		logger::log($alarm['Player'], 'There is nothing to play');
	} else {
		$seek_workaround = false;
		// There's a bug in Mopidy - if you set the volume to 0 while paused it reports
		// a volume of 0 but doesn't actualy set it. So if we're ramping from paused
		// you get a burst at full volume before the ramp starts. The workaround
		// is to stop playback first (remembering to disable consume first) and then
		// later on to start playback from where it was before you pressed stop.
		if ($alarm['Ramp'] == 1) {
			if (prefs::get_pref('player_backend') == 'mopidy' && $mpd_status['state'] == 'pause') {
				$seek_workaround = [$mpd_status['songid'], $mpd_status['elapsed']];
				$old_consume = $player->get_consume($mpd_status['consume']);
				$player->force_consume_state(0);
				$player->do_command_list(['stop']);
				$player->force_consume_state($old_consume);
			}
			$player->do_command_list(['setvol 0']);
		}

		if ($play_item) {
			if ($mpd_status['state'] != 'stop')
				$player->do_command_list(['stop']);

			$player->do_command_list(['clear']);
			prefs::$database = new music_loader();
			$mpd_status = $player->rompr_commands_to_mpd($playcommands);
			prefs::$database->close_database();
			prefs::$database = null;
		}

		$state = $player->get_status_value('state');
		if ($state != 'play') {
			if ($seek_workaround === false || $play_item) {
				$player->do_command_list(['play']);
			} else {
				$player->do_command_list(['seekid '.$seek_workaround[0].' '.$seek_workaround[1]]);
				sleep(1);
				$player->do_command_list(['playid '.$seek_workaround[0]]);
			}
		}

		if ($alarm['Ramp'] == 1)
			$player->ramp_volume(0, $volume, prefs::get_pref('alarm_ramptime'));

		$player->close_mpd_connection();

		if ($alarm['Stopafter'] == 1 && $alarm['StopMins'] > 0) {
			logger::log($alarm['Player'], 'Alarm is set to stop after',$alarm['StopMins'],'minutes');
			sleep($alarm['StopMins'] * 60);
			// In that time, somebody could have stopped the alarm already and switched over
			// to listening to something else.
			prefs::$database = new timers();
			$running = prefs::$database->check_alarm_running($alarm['Alarmindex']);
			if ($running) {
				prefs::$database->mark_alarm_running($alarm['Alarmindex'], false);
				// Possibly not necessary?
				prefs::$database->toggle_snooze($alarm['Alarmindex'], 0);
				$player->open_mpd_connection();
				$mpd_status = $player->do_command_list(['status']);
				if ($mpd_status['state'] == 'play')
					$player->do_command_list(['pause']);

				$player->close_mpd_connection();
			}
			prefs::$database->close_database();
			prefs::$database = null;
		}
	}

	if ($alarm['Rpt'] == 0) {
		logger::info($alarm['Player'], 'Alarm is not a repeat alarm so this process has done what it came here to do.');
		prefs::$database = new timers();
		prefs::$database->mark_alarm_finished($alarm['Alarmindex']);
		exit(0);
	}

	// Short sleep so that we're now in front of the time it's supposed to go off,
	// otherwise we might run it again immediately
	sleep(5);

}

function alarm_sleep_time() {
	global $alarm;
	$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
	// Alarm Time must be in the format HH:MM
	logger::log("ALARMCLOCK", "Alarm Time is",trim($alarm['Time']));

	// DateTime object for now
	$now = new DateTime('now');

	// DateTime object for the alarm, initialised to today at the time the alarm
	// is set to go off.
	$d = explode(':', $alarm['Time']);
	$alarm_run = new DateTime('now');
	$alarm_run->setTime(
		$d[0],
		trim($d[1])
	);

	// If it's not set to repeat, it could either go off today or tomorow
	// depending on whether the time is earlier or later than now
	if ($alarm['Rpt'] == 0)
		$alarm['Days'] = date('l').','.date('l', time()+86400);

	logger::trace('ALARMCLOCK', 'Alarm Days are',trim($alarm['Days']));
	$alarmdays = array_map('trim', explode(',', $alarm['Days']));

	$today = date('l');
	logger::log('ALARMCLOCK', 'Today is',$today);

	// Create an ordered list of days of the week that starts at today
	while ($days[0] != $today) {
		$t = array_shift($days);
		$days[] = $t;
	}
	// If the timestamp we've set, which is at the set time but to run today, is earlier
	// than now, then we need to run it on the next available day.

	if ($alarm_run->getTimeStamp() < $now->getTimeStamp() && in_array($today, $alarmdays)) {
		$alarm_run->modify('+1 day');
		$t = array_shift($days);
		$days[] = $t;
	}
	logger::debug('ALARMCLOCK', 'Days list is now', implode(',', $days));
	// Go through the days of the week list in order until we arrive at a day the
	// alarm is set to go off. Increment the alarm time by one day every time a day
	// doesn't match
	foreach ($days as $day) {
		if (in_array($day, $alarmdays))
			break;

		$alarm_run->modify('+1 day');
	}

	// Convert the date/time we just generated into a UNIX timestamp we can pass to time_sleep_until()
	$sleeptime = $alarm_run->getTimestamp();
    $os = php_uname();
    // This crashes with no error on macOS for reasons I don't understand
    if (strpos($os, 'Darwin') === false)
		logger::info('ALARMCLOCK', 'Alarm will run at', $alarm_run->format(DateTimeInterface::COOKIE));

	logger::log('ALARMCLOCK', 'Sleeping Until', $sleeptime);
	time_sleep_until($sleeptime);

}


?>
