<?php

class timers extends database {

	private $player;

	public function __construct() {
		$this->player = prefs::$prefs['currenthost'];
		parent::__construct();
	}

	public function set_sleep_timer($enable, $sleeptime) {
		if ($enable == 0) {
			$this->kill_sleep_timer();
		} else {
			$this->start_sleep_timer($sleeptime);
		}
	}

	public function sleep_timer_finished() {
		$this->sql_prepare_query(true, null, null, null,
			'DELETE FROM Sleeptimers WHERE Player = ?',
			$this->player
		);
	}

	public function get_sleep_timer() {
		$default = [['Pid' => null, 'TimeSet' => 0, 'SleepTime' => 0]];
		$t = $this->sql_prepare_query(false, PDO::FETCH_ASSOC, null, $default,
			"SELECT * FROM Sleeptimers WHERE Player = ?",
			$this->player
		);
		if (count($t) == 0)
			$t = $default;

		return [
			'sleeptime' => $t[0]['SleepTime'],
			'timeset' => $t[0]['TimeSet'],
			'state' => ($t[0]['Pid'] === null) ? 0 : 1
		];
	}

	private function kill_sleep_timer() {
		logger::log($this->player, 'Cancelling Sleep Timer');
		$pid = $this->simple_query('Pid', 'Sleeptimers', 'Player', $this->player, null);
		if ($pid !== null) {
			kill_process($pid);
		}
		$this->sleep_timer_finished();
	}

	private function start_sleep_timer($sleeptime) {
		$pwd = getcwd();

		$t = $this->get_sleep_timer();
		if ($t['state'] === 1) {
			logger::log($this->player, 'Timeout was adjusted while sleep timer was running',$t['timeset'], ($sleeptime * 60), time());
			$timeout = max(1, ($t['timeset'] + ($sleeptime * 60) - time()));
			$this->kill_sleep_timer();
			$timeset = $t['timeset'];
		} else {
			$timeout = $sleeptime * 60;
			$timeset = time();
		}

		$cmd = $pwd.'/sleeptimer.php --currenthost '.$this->player.' --sleeptime '.$timeout;
		$pid = start_process($cmd);
		$this->sql_prepare_query(true, null, null, null,
			'INSERT INTO Sleeptimers (Pid, Player, TimeSet, SleepTime) VALUES (?, ?, ?, ?)',
			$pid, $this->player, $timeset, $sleeptime
		);
	}

	public function get_all_alarms() {
		return $this->sql_prepare_query(false, PDO::FETCH_ASSOC, null, null,
			"SELECT * FROM Alarms WHERE Player = ?",
			$this->player
		);
	}

	public function get_alarm($alarmindex) {
		$cheese = $this->sql_prepare_query(false, PDO::FETCH_ASSOC, null, null,
			"SELECT * FROM Alarms WHERE Alarmindex = ?",
			$alarmindex
		);
		return $cheese[0];
	}

	public function toggle_alarm($alarmindex, $enable) {
		// This is called in response to a click on the enable/disable icon in the UI.
		// So it works as follows:
		// If the alarm in Running, this stops it running.
		// If it's Running and a non-Repeat alarm this also marks it inactive
		// If it's running ot also toggles the snooze state.
		// If it's not running it either stops or starts it.
		// The stop/start value is toggled based on what the UI is showing it as,
		// which shouldn't be out of sync with the database but might be, so it
		// makes more sense to a user to honour the setting they can see.
		logger::log('ALARMCLOCK', 'Toggling Alarm',$alarmindex,'New State is',$enable);

		$alarm = $this->get_alarm($alarmindex);

		if ($alarm['Running'] == 1) {
			$this->toggle_snooze($alarmindex, 0);
			$this->mark_alarm_running($alarmindex, false);
			if ($alarm['Repeat'] == 0 && $alarm['Pid'] !== null) {
				// A Non-Repeat alarm might still have a Pid if it has a Stopafter setting
				kill_process($alarm['Pid']);
				$this->update_pid_for_alarm($alarmindex, null);
			}
			return;
		}

		if ($alarm['Pid'] !== null && $enable == 1) {
			logger::error('ALARMCLOCK', 'Request to enable already-enabled alarm', $alarmindex);
		} else if ($alarm['Pid'] === null && $enable == 0) {
			logger::error('ALARMCLOCK', 'Request to disable already-disabled alarm', $alarmindex);
		} else if ($enable == 0) {
			kill_process($alarm['Pid']);
			$this->update_pid_for_alarm($alarmindex, null);
		} else {
			$pwd = getcwd();
			$cmd = $pwd.'/alarmclock.php --alarmindex '.$alarmindex;
			$pid = start_process($cmd);
			// We need to set it here, even though the process will do it too.
			// The UI will need to know this info as soon as we finish.
			// having the process do it is a belt-and braces approach so we can
			// be certain we have the correct value in the table when we come to kill it.
			$this->update_pid_for_alarm($alarmindex, $pid);
		}
	}

	public function toggle_snooze($alarmindex, $enable) {
		logger::log('ALARMCLOCK', 'Toggling Snooze',$alarmindex,'New State is',$enable);
		$alarm = $this->get_alarm($alarmindex);
		if ($alarm['SnoozePid'] !== null && $enable == 1) {
			logger::error('ALARMCLOCK', 'Request to snooze already-snoozing alarm', $alarmindex);
		} else if ($alarm['SnoozePid'] === null && $enable == 0) {
			logger::error('ALARMCLOCK', 'Request to unsnooze already-unsnoozed alarm', $alarmindex);
		} else if ($enable == 0) {
			kill_process($alarm['SnoozePid']);
			$this->update_snooze_pid_for_alarm($alarmindex, null);
			// I think this makes sense.
			$this->mark_alarm_running($alarmindex, false);
		} else {
			$pwd = getcwd();
			$cmd = $pwd.'/snoozer.php --snooze '.$alarmindex;
			$pid = start_process($cmd);
			// See note above
			$this->update_snooze_pid_for_alarm($alarmindex, $pid);
		}
	}

	public function update_pid_for_alarm($alarmindex, $pid) {
		$this->sql_prepare_query(true, null, null, null,
			"UPDATE Alarms SET Pid = ? WHERE Alarmindex = ?",
			$pid, $alarmindex
		);
	}

	public function update_snooze_pid_for_alarm($alarmindex, $pid) {
		$this->sql_prepare_query(true, null, null, null,
			"UPDATE Alarms SET SnoozePid = ? WHERE Alarmindex = ?",
			$pid, $alarmindex
		);
	}

	public function remove_alarm($alarmindex) {
		logger::log('ALARMCLOCK', 'Deleting Alarm',$alarmindex);
		$alarm = $this->get_alarm($alarmindex);
		if ($alarm['Pid'] !== null)
			kill_process($alarm['Pid']);

		if ($alarm['SnoozePid'] !== null)
			kill_process($alarm['SnoozePid']);

		$this->sql_prepare_query(true, null, null, null,
			"DELETE FROM Alarms WHERE Alarmindex = ?",
			$alarmindex
		);
	}

	public function edit_alarm($alarm) {
		logger::core('ALARMS', 'Editing', print_r($alarm, true));
		// It's possible to enable Repeat but not select any days.
		if ($alarm['Days'] == '')
			$alarm['Repeat'] = 0;

		if ($alarm['Alarmindex'] == 'NEW') {
			$command = 'INSERT ';
			unset($alarm['Alarmindex']);
		} else {
			$command = 'REPLACE ';
			$current_state = $this->get_alarm($alarm['Alarmindex']);
			// We NEED to do this, REPLACE INTO will set Pid to the default (of NULL)
			// if we don't explicitly set it to something
			$alarm['Pid'] = $current_state['Pid'];
			$alarm['SnoozePid'] = $current_state['SnoozePid'];
		}
		$columns = array_keys($alarm);
		$qm	= array_fill(0, count($columns), '?');
		$command .= 'INTO Alarms ('.implode(', ', $columns).') VALUES ('.implode(', ', $qm).')';
		$this->sql_prepare_query(true, null, null, null,
			$command, array_values($alarm)
		);

		if ($alarm['Alarmindex'] == 'NEW') {
			$alarm['Alarmindex'] = $this->mysqlc->lastInsertId();
			$this->toggle_alarm($alarm['Alarmindex'], 1);
		} else if ($current_state['Pid'] !== null) {
			$this->toggle_alarm($alarm['Alarmindex'], 0);
		}
	}

	public function mark_alarm_running($alarmindex, $running) {
		$this->sql_prepare_query(true, null, null, null,
			"UPDATE Alarms SET Running = ? WHERE Alarmindex = ?",
			($running ? 1 : 0),
			$alarmindex
		);
	}

	public function check_alarm_running($alarmindex) {
		$flag = $this->simple_query('Running', 'Alarms', 'Alarmindex', $alarmindex, 0);
		return ($flag == 1);
	}

	public function mark_alarm_finished($alarmindex) {
		$this->sql_prepare_query(true, null, null, null,
			"UPDATE Alarms SET Pid = NULL WHERE Alarmindex = ?",
			$alarmindex
		);
	}

	public function stop_alarms_for_player() {
		$alarms = $this->sql_prepare_query(false, PDO::FETCH_ASSOC, null, [],
			"SELECT * FROM Alarms WHERE Player = ? AND Running = 1",
			$this->player
		);
		foreach ($alarms as $alarm) {
			$this->mark_alarm_running($alarm['Alarmindex'], false);
			// If it's not a Repeat alarm, kill the process. It might have already done that
			// but not necessarily if it has a Stopafter setting - we want Pid to get NULLed
			// so the UI knows it isn't set any more.
			if ($alarm['Pid'] !== null && $alarm['Repeat'] == 0)
				$this->toggle_alarm($alarm['Alarmindex'], 0);

			if ($alarm['SnoozePid'] !== null)
				$this->toggle_snooze($alarm['Alarmindex'], 0);

		}
	}

	public function snooze_alarms_for_player() {
		$alarms = $this->sql_prepare_query(false, PDO::FETCH_ASSOC, null, [],
			"SELECT * FROM Alarms WHERE Player = ? AND Running = 1 AND SnoozePid IS NULL",
			$this->player
		);
		foreach ($alarms as $alarm) {
			$this->toggle_snooze($alarm['Alarmindex'], 1);
		}
	}

}

?>