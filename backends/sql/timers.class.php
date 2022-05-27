<?php

class timers extends database {

	public function set_sleep_timer($enable, $sleeptime) {
		if ($enable == 0) {
			$this->kill_sleep_timer();
		} else {
			$this->start_sleep_timer($sleeptime);
		}
	}

	public function sleep_timer_finished($player) {
		$this->sql_prepare_query(true, null, null, null,
			'DELETE FROM Sleeptimers WHERE Player = ?',
			$player
		);
	}

	public function get_sleep_timer() {
		$default = [['Pid' => null, 'TimeSet' => 0, 'SleepTime' => 0]];
		$t = $this->sql_prepare_query(false, PDO::FETCH_ASSOC, null, $default,
			"SELECT * FROM Sleeptimers WHERE Player = ?",
			prefs::$prefs['currenthost']
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
		$player = prefs::$prefs['currenthost'];
		logger::log($player, 'Cancelling Sleep Timer');
		$pid = $this->simple_query('Pid', 'Sleeptimers', 'Player', $player, null);
		if ($pid !== null) {
			kill_process($pid);
		}
		$this->sleep_timer_finished($player);
	}

	private function start_sleep_timer($sleeptime) {
		$pwd = getcwd();
		$player = prefs::$prefs['currenthost'];

		$t = $this->get_sleep_timer();
		if ($t['state'] === 1) {
			logger::log($player, 'Timeout was adjusted while sleep timer was running',$t['timeset'], ($sleeptime * 60), time());
			$timeout = max(1, ($t['timeset'] + ($sleeptime * 60) - time()));
			$this->kill_sleep_timer();
			$timeset = $t['timeset'];
		} else {
			$timeout = $sleeptime * 60;
			$timeset = time();
		}

		$cmd = $pwd.'/sleeptimer.php --currenthost '.$player.' --sleeptime '.$timeout;
		$pid = start_process($cmd);
		$this->sql_prepare_query(true, null, null, null,
			'INSERT INTO Sleeptimers (Pid, Player, TimeSet, SleepTime) VALUES (?, ?, ?, ?)',
			$pid, $player, $timeset, $sleeptime
		);
	}

}

?>