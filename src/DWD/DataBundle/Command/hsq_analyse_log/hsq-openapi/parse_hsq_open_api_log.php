<?php

require_once ('tools.php');

class OpenAPILogParse
{
	private $_data = array();
	private $_removedKey = array();
	private $_connection;
	private $_subject = 'open-api.log.';
	private $_subjectPath = '';
	private $_serverIp = '127.0.0.1';
	private $_validHost = array( 'api.v3.iqianggou.com', 'm.iqianggou.com' );
	private $_serverId = 0;
	private $_config;

	function __construct()
	{
		$this->_config = parse_ini_file("config.ini", true);
		$this->_subjectPath = $this->_config['common']['subject_path'];
		$this->_serverIp = $this->_config['common']['server_ip'];

		$this->connectMongo($this->_config['mongo']['server'], $this->_config['mongo']['db_name']);
	}

	protected function connectMongo( $server = "mongodb://localhost:27017", $db_name = "iqianggou_analyse" )
	{
		$m = new MongoClient($server);
		$this->_connection = $m->selectDB($db_name);
	}

	public function AnalyseRequestInfo( $requestInfo )
	{
		$this->_data = array();
		if (false == isset( $requestInfo['server'] )) {
			return false;
		}
		$serverInfo = $requestInfo['server'];
		$urlParts = explode('&', $serverInfo['QUERY_STRING']);
		$this->_data = $requestInfo;
		$this->_data['path'] = current($urlParts);
		if( !$this->_data['path'] ) {
			return false;
		} else {
			$this->_data['path'] = '/' . $this->_data['path'];
		}
		return true;
	}

	public function GetRequestInfo( $msg )
	{
		$requestInfo = json_decode( $msg, true );
		return $requestInfo;
	}

	public function GetLastHour()
	{
		return date("YmdH", time() - 3600);
	}

	public function GetLastHourLogFile()
	{
		return $this->_subjectPath . $this->_subject . $this->GetLastHour();
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
		$coll = $this->_connection->hsq_open_api_logs;
		$coll->insert( $this->_data );
	}

	public function RunAction( $fileName )
	{
		if(!file_exists($fileName)) {
            var_dump($fileName .'文件不存在');
			return false;
		}
		$fp 			= fopen( $fileName, "r" );
		$error			= error_get_last();
		if (NULL != $error) {
			return false;
		}

		echo "Start parse OpenAPI access log[" . $fileName . "] at " . date('Y-m-d H:i:s') . "\n";

		while( !feof( $fp ) )
		{
			$lineMsg = fgets( $fp );
			$requestInfo = $this->GetRequestInfo( $lineMsg );
			if (false == $requestInfo) {
				continue;
			}
			if ($this->AnalyseRequestInfo( $requestInfo )) {
				$this->SaveRequestInfo();
			}
		}

		echo "End parse HsqOpenAPI access log[" . $fileName . "] at " . date('Y-m-d H:i:s') . "\n";

		fclose( $fp );
	}

	public function RunLastHourLog()
	{
		$fileName 		= $this->GetLastHourLogFile();
		$this->RunAction($fileName);
	}

	public function RunSpecificLog( $dateFormat )
	{
                for($i = 0; $i < 24; $i ++) {
                        $hour = str_pad($i, 2, "0", STR_PAD_LEFT);
                        $date = $dateFormat . $hour;
                        $fileName       = $this->_subjectPath . $this->_subject . $date;
                        $this->RunAction($fileName);
                }
	}

	public function RunYesterdayLog()
	{
		$dateFormat = date("Ymd", strtotime("-1 day"));
		for($i = 0; $i < 24; $i ++) {
			$hour = str_pad($i, 2, "0", STR_PAD_LEFT);
			$date = $dateFormat . $hour;
			$fileName       = $this->_subjectPath . $this->_subject . $date;
			$this->RunAction($fileName);
		}
	}

}

try{
    $startTime = time();
	$openAPILogParse = new OpenAPILogParse();
	$openAPILogParse->RunYesterdayLog();
    $totalTime = time() - $startTime;
    var_dump('解析数据总的处理时间:'. $totalTime);

} catch (Exception $e) {
	print $e->getMessage();
	exit();
}

