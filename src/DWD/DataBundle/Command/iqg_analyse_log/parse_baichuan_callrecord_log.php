<?php

require_once ('tools.php');

class CallRecordLogParse
{
	private $_data = array();
	private $_connection;
	private $_subject = 'callRecord';
	private $_subjectPath = '';
	private $_serverIp = '127.0.0.1';
	private $_serverId = 0;
	private $_config;	

	function __construct()
	{
		$this->_config = parse_ini_file("config.ini", true);
		$this->_subject = $this->_config['baichuan_callrecord']['prefix_file_name'];
		$this->_subjectPath = $this->_config['common']['subject_path'];
		$this->_serverIp = $this->_config['common']['server_ip'];

		$this->connectMongo($this->_config['mongo']['server'], $this->_config['mongo']['db_name']);
	}

	protected function connectMongo($server = "mongodb://localhost:27017", $db_name = "iqianggou_analyse")
	{
		$m = new MongoClient($server);
		$this->_connection = $m->selectDB($db_name);
	}

	public function AnalyseRequestInfo( $requestInfo )
	{
		if (isset( $requestInfo['request']['serviceName'] )) {
			$requestInfo['request']['serviceName'] = preg_replace('/(.+)\\\(.+)/', '$2', $requestInfo['request']['serviceName']);
		}

		if (isset( $requestInfo['response']['messages']['tmc_message'] )) {
			$tmcMessageList = $requestInfo['response']['messages']['tmc_message'];
			foreach ($tmcMessageList as $key => $value) {
				if (isset( $value['content'] )) {
					$tmcMessageList[$key]['content'] = json_decode($value['content'], true);
				}
			}
			$requestInfo['response']['messages']['tmc_message'] = $tmcMessageList;
		}
		$requestInfo['server_ip'] = $this->_serverIp;
		if (0 != $this->_serverId) {
			$requestInfo['server_ip'] = $this->_config['server_ip']['server' . $this->_serverId];
		}	
		$this->_data = $requestInfo;
	}

	public function GetRequestInfo( $msg )
	{
		$pattern = '/^\[(.+)\] \[.+\]: \[(.+)\]/';
		preg_match( $pattern, $msg, $matches );
		if (count($matches) < 3) {
			return false;
		}
		$currentTimestamp = strtotime( $matches[1] );
		$requestInfo = json_decode( $matches[2], true );
		$requestInfo['request_time'] = $currentTimestamp;
		return $requestInfo;
	}

	public function GetYesterday()
	{
		return date("Y-m-d", strtotime("-1 day"));
	}

	public function GetYesterdayLogFile()
	{
		return $this->_subjectPath . $this->_subject . '-' . $this->GetYesterday() . '.log';
	}

	public function SetSubjectPath( $subServerFile )
	{
		if (preg_match('/[a-zA-Z_]+([0-9]+)/', $subServerFile, $matches)) {
			$this->_serverId = intval($matches[1]);
		}
		$this->_subjectPath .= ($subServerFile . '/');
	}

	public function SaveRequestInfo()
	{
		$coll = $this->_connection->baichuan_callrecords_logs;
		if (isset( $this->_data ) && !empty( $this->_data ) ) {
			$coll->insert($this->_data);
		}
	}

	public function RunAction( $fileName )
	{
		$fp 				= fopen( $fileName, "r" ); 
		$error				= error_get_last();
		if (NULL != $error) {
			exit();
		}

		echo "Start parse baichuan callrecord log[" . $fileName . "] at" . date('Y-m-d H:i:s') . "\n";

		while( !feof( $fp ) ) 
		{ 
		   	$lineMsg = fgets( $fp );
		   	$requestInfo = $this->GetRequestInfo( $lineMsg );
		   	$this->AnalyseRequestInfo( $requestInfo );
		   	$this->SaveRequestInfo();
		}

		echo "End parse baichuan callrecord log[" . $fileName . "] at" . date('Y-m-d H:i:s') . "\n";

		fclose( $fp );
	}

	public function RunYesterdayLog()
	{
		$fileName 			= $this->GetYesterdayLogFile(); 
		$this->RunAction($fileName);
	}
}

try{
	$startTime = time();
	$job =  __dir__ . '/' . implode(" ", $argv);
	$tool =  new Tool();
	$tool->SetStartTime( $startTime );
	$tool->SetJob( $job );
	$tool->appendCrontabMonitorLog($tool->getCrontabMonitorLogBootstrapJSON('', 0));

	$callRecordLogParse = new CallRecordLogParse();

	foreach ( $argv as $key => $value ) {
		if ( $key == 0 ) {
			continue;
		}
		$callRecordLogParse->SetSubjectPath($value);
		$callRecordLogParse->RunYesterdayLog();
	}

	$tool->appendCrontabMonitorLog($tool->getCrontabMonitorLogEndJSON('', 0));
} catch (Exception $e) {
	$tool->appendCrontabMonitorLog($tool->getCrontabMonitorLogEndJSON($e->getMessage(), 99));
	exit();
}
