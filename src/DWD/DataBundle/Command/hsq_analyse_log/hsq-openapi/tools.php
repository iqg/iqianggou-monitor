<?php
	// file_put_contents( 'crontab-monitor-2015-09-24.log', '[2015-09-24 12:20:22] [INFO]: {JSON}', FILE_APPEND );

Class Tool
{
	private $_cron_name;
	private $_cron_job;
	private $_cron_start_time;
	private $_cron_end_time;
	private $_current_time;
	private $_subjectPath = '';

	function __construct()
	{
		$this->_config = parse_ini_file("config.ini", true);
		$this->_cron_name = $this->_config['openapi_access']['name'];
		$this->_subjectPath = $this->_config['common']['subject_path'];
	}

	public function SetJob( $job )
	{
		$this->_cron_job = $job;
	}

	public function SetStartTime( $startTime )
	{
		$this->_cron_start_time = $startTime;
		$this->_current_time = $startTime;
	}

	public function SetEndTime( $endTime )
	{
		$this->_cron_end_time = $endTime;
		$this->_current_time = $endTime;
	}

	public function appendCrontabMonitorLog($data)
	{
		$filename = $this->_subjectPath . 'crontab-monitor-' . date('Y-m-d', $this->_current_time) . '.log';
		$dataLine = $this->getCrontabMonitorLog($data);
		file_put_contents($filename, $dataLine, FILE_APPEND);
	}

	public function getCrontabMonitorLog($jsonData)
	{
		if( $jsonData['code'] == 0 ) {
			$mark = 'INFO';
		} else {
			$mark = 'ERROR';
		}
		$currentTimeFormat = date("Y-m-d H:i:s", $this->_current_time);
		return "[$currentTimeFormat] [$mark]: " . json_encode($jsonData) . "\n";
	}

	public function getCrontabMonitorLogBootstrapJSON($msg, $code)
	{
		$data = array(
			'name' => $this->_cron_name,
			'job' => $this->_cron_job,
			'msg' => $msg,
			'start_time' => $this->_cron_start_time,
			'flag' => 0,
			'code' => $code
		);
		return $data;
	}

	public function getCrontabMonitorLogEndJSON($msg, $code)
	{
		$this->SetEndTime(time());
		$data = array(
			'name' => $this->_cron_name,
			'job' => $this->_cron_job,
			'msg' => $msg,
			'start_time' => $this->_cron_start_time,
			'end_time' => $this->_cron_end_time,
			'flag' => 1,
			'code' => $code
		);
		return $data;
	}
}