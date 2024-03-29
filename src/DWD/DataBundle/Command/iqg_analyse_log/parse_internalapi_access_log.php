<?php

require_once ('tools.php');

class InternalAccessLogParse
{
	private $_data = array();
	private $_removedKey = array( 'content-type', 'content-length', 'web-server-type', 'cookie', 'accept-encoding', 'x-php-ob-level', 'accept', 'accept-language' );
	private $_connection;
	private $_subject = 'iqg_access_internal_api.log';
	private $_subjectParentPath = '';
	private $_subjectPath = '';
	private $_serverIp = '127.0.0.1';
	private $_validHost = array( 'internalapi.iqianggou.dev.lab:12306' );
	private $_serverId = 0;
	public   $_config;

	function __construct()
	{
		$this->_config = parse_ini_file("config.ini", true);
		$this->_subject = $this->_config['internalapi_access']['prefix_file_name'];
		$this->_subjectParentPath = $this->_config['internalapi_access']['internalapi_subject_path'];
		$this->_serverIp = $this->_config['common']['server_ip'];

		$this->connectMongo($this->_config['mongo']['server'], $this->_config['mongo']['db_name']);
	}

	protected function connectMongo( $server = "mongodb://localhost:27017", $db_name = "iqianggou_analyse" )
	{
		$m = new MongoClient($server);
		$this->_connection = $m->selectDB($db_name);
	}

    //分析json数据
	public function AnalyseRequestInfo( $requestInfo )
	{
		$this->_data = array();

		if (false == isset( $requestInfo['server'] )) {
			return false;
		}

		if (isset( $requestInfo['errno'] )) {
			$this->_data['ResponseStatusCode'] = $requestInfo['errno'];
		}

		if (isset( $requestInfo['cost'] )) {
			$this->_data['cost'] = $requestInfo['cost'];
		}
		$this->_data['request_time'] = $requestInfo['server']['REQUEST_TIME'];

        $this->_data['method'] = $requestInfo['server']['REQUEST_METHOD'];
        $this->_data['path'] = key($requestInfo['get']);

		$this->_data['server_ip'] = $this->_serverIp;
		if (0 != $this->_serverId) {
			$this->_data['server_ip'] = $this->_config['server_ip']['server' . $this->_serverId];
		}
		return true;
	}

	public function GetYesterday()
	{
		return date("Ymd", strtotime("-1 day"));
	}

	public function GetYesterdayLogFile()
	{
		return $this->_subjectPath . $this->_subject . '.' . $this->GetYesterday() ;
	}

	public function SetSubjectPath( $subServerFile )
	{
        try{
    //		if (preg_match('/[a-zA-Z_]+([0-9]+)/', $subServerFile, $matches)) {
    //			$this->_serverId = intval($matches[1]);
    //		}
            $this->_subjectPath = $this->_subjectParentPath . ($subServerFile . '/');
//                var_dump($this->_subjectPath,$this->_serverId);exit;
        }catch ( Exception $e){
            var_dump($e->getMessage());
        }
	}

	public function SaveRequestInfo()
	{
		$coll = $this->_connection->internalapi_access_logs;
		$coll->insert( $this->_data );
	}

	public function RunAction( $fileName )
	{
        ini_set('memory_limit','-1');
        if(!file_exists($fileName)) {
            var_dump($fileName .'文件不存在');
            return false;
        }

        $fp    = fopen( $fileName, "r" );
		$error = error_get_last();

		if (NULL != $error) {
			echo $fileName ."读取文件异常,系统退出\n";
            return false;
		}

		echo "Start parse Internal_API access log[" . $fileName . "] at " . date('Y-m-d H:i:s') . "\n";

		while( !feof( $fp ) ) 
		{ 
			$lineMsg = fgets( $fp );

            $requestInfo =  json_decode($lineMsg,true);
			if (!is_array($requestInfo)) {
				continue;
			}

			if ($this->AnalyseRequestInfo( $requestInfo )) {
				$this->SaveRequestInfo();
			}
		}

		echo "End parse Internal_API access log [" . $fileName . "] at " . date('Y-m-d H:i:s') . "\n";
		fclose( $fp );	
	}

    //入口
	public function RunYesterdayLog()
	{
		$fileName 		= $this->GetYesterdayLogFile();
        var_dump('internal_api解析的文件: ' . $fileName);
		$this->RunAction($fileName);
	}

}

try{
	$startTime = time();

    $internalAccessLogParse = new InternalAccessLogParse();

    $serverIpArr = $internalAccessLogParse->_config['server_ip'];

	foreach ( $serverIpArr as $key => $value ) {

        $start = time();
        var_dump('处理第几台服务器====>' .$value);
        $internalAccessLogParse->SetSubjectPath($value);
        $internalAccessLogParse->RunYesterdayLog();
        $totalt = time() - $start;
        var_dump($value .'　服务器处理时间:'. $totalt);
	}

    $totalTime = time() - $startTime;
     var_dump('总的处理时间:'. $totalTime);

} catch (Exception $e) {
    var_dump($e->getMessage(),$e->getCode());
}

