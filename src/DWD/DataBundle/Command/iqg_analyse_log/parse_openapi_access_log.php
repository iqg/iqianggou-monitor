<?php
/*
 * 爱抢购 open_api接口性能分析
 * */
require_once ('tools.php');

class AccessLogParse
{
	private $_data = array();
	private $_removedKey = array( 'content-type', 'content-length', 'web-server-type', 'cookie', 'accept-encoding', 'x-php-ob-level', 'accept', 'accept-language' );
	private $_connection;
	private $_subject = 'access';
	private $_subjectParentPath = '';
	private $_subjectPath = '';
	private $_subjectLastPath = '';
	private $_serverIp = '127.0.0.1';
	private $_validHost = array( 'api.v3.iqianggou.com', 'm.iqianggou.com' );
	private $_serverId = 0;
	public  $_config;

	function __construct()
	{
		$this->_config = parse_ini_file("config.ini", true);
		$this->_subject = $this->_config['openapi_access']['prefix_file_name'];
		$this->_subjectParentPath = $this->_config['common']['subject_path'];
		$this->_serverIp = $this->_config['common']['server_ip'];
		$this->_subjectLastPath = $this->_config['openapi_access']['last_path'];

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
		if (false == isset( $requestInfo['request'] )) {
			return false;
		}
		if ( false == isset( $requestInfo['request']['header']['host'][0] ) || false == in_array( $requestInfo['request']['header']['host'][0], $this->_validHost ) ) {
			return false;
		}
		$request = $requestInfo['request'];
		foreach ( $request as $key => $value ) {
			if (in_array( $key, $this->_removedKey )) {
				continue;
			}
			if ($key == 'header') {
				foreach ( $value as $headerKey => $valueKey ) {
					if (in_array( $headerKey, $this->_removedKey )) {
						continue;
					}
					$this->_data['header'][$headerKey] = $valueKey[0];
				}
				continue;
			}
			$this->_data[$key] = $value;
		}
		if (isset( $requestInfo['ResponseStatusCode'] )) {
			$this->_data['ResponseStatusCode'] = $requestInfo['ResponseStatusCode'];
		}
		if (isset( $requestInfo['cost'] )) {
			$this->_data['cost'] = $requestInfo['cost'];
		}
		$this->_data['request_time'] = $requestInfo['request_time'];
		$this->_data['server_ip'] = $this->_serverIp;
		if (0 != $this->_serverId) {
			$this->_data['server_ip'] = $this->_config['server_ip']['server' . $this->_serverId];
		}
		return true;
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
		$this->_subjectPath = $this->_subjectParentPath . $subServerFile . '/'. $this->_subjectLastPath ;

	}

	public function SaveRequestInfo()
	{
		$coll = $this->_connection->openapi_access_logs;
		$coll->insert( $this->_data );
	}

	public function RunAction( $fileName )
	{
		$fp 			= fopen( $fileName, "r" );
		$error			= error_get_last();
		if (NULL != $error) {
			echo $fileName.'读取文件异常,系统退出';
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

		echo "End parse OpenAPI access log[" . $fileName . "] at " . date('Y-m-d H:i:s') . "\n";

		fclose( $fp );	
	}

	public function RunYesterdayLog()
	{
		$fileName 		= $this->GetYesterdayLogFile();
         var_dump('解析的文件: ' . $fileName);
		$this->RunAction($fileName);
	}

	public function RunCatelogLog()
	{
		$thisCatelog = scandir($this->_subjectPath);
		foreach ( $thisCatelog as $thisFile ) {
			if ( 0 == preg_match('/^access.+\.log/', $thisFile) ) {
				continue;
			}
			$thisFilePath = $this->_subjectPath . $thisFile;
			$this->RunAction($thisFilePath);
		}
	}

	public function RunSpecificLog( $date )
	{
		$fileName       = $this->_subjectPath . $this->_subject . '-' . $date . '.log';
		$this->RunAction($fileName);
	}
}

try{
	$startTime = time();

	$accessLogParse = new AccessLogParse();

    $serverIpArr = $accessLogParse->_config['server_ip'];

	foreach ( $serverIpArr as $key => $value ) {
        var_dump('处理第几台服务器====>' .$value);
		$accessLogParse->SetSubjectPath($value);
		$accessLogParse->RunYesterdayLog();
	}

    $totalTime = time() - $startTime;
    var_dump('总的处理时间:'. $totalTime);

} catch (Exception $e) {

	exit();
}

