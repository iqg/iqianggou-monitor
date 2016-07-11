<?php

require_once ('tools.php');

class ParseAppOfflineLog
{
	private $_data = array();
	private $_removedKey = array( 'content-type', 'content-length', 'web-server-type', 'cookie', 'accept-encoding', 'x-php-ob-level', 'accept', 'accept-language' );
	private $_connection;
	private $_subject = 'iqg_access_internal_api.log';
	private $_subjectParentPath = '';
	private $_subjectPath = '';
	private $_serverIp = '127.0.0.1';
	private $_validHost = array( 'internalapi.iqianggou.dev.lab:12306' );
	private $_iqianggouApp = array( 'iqianggou','爱抢购' );
	private $_haoshiqiApp   = array( 'haoshiqi','好食期' );
	private $_serverId = 0;
	public   $_config;

	function __construct()
	{
		$this->_config = parse_ini_file("config.ini", true);
		$this->_subject = $this->_config['app_offline_log']['prefix_file_name'];
		$this->_subjectParentPath = $this->_config['app_offline_log']['app_offline_subject_path'];
		$this->_serverIp = $this->_config['common']['server_ip'];

		$this->connectMongo($this->_config['mongo']['server'], $this->_config['mongo']['db_name']);
	}

	protected function connectMongo( $server = "mongodb://localhost:27017", $db_name = "iqianggou_analyse" )
	{
		$m = new MongoClient($server);
		$this->_connection = $m->selectDB($db_name);
	}

    //分析json数据
	public function AnalyseRequestInfo( $requestInfos )
	{
		$this->_data = [];
//        var_dump($requestInfo);exit;
		if ( empty( $requestInfos['logdata']) ) {
			return false;
		}

        foreach($requestInfos['logdata'] as $requestInfo){

            if(in_array($requestInfo['actionId'],['RequestLog','NetImageSpeed']) ){
                $this->_data['actionId'] = $requestInfo['actionId'];

                $this->_data['path'] = isset($requestInfo['actionExt']) ? $requestInfo['actionExt'] : '/';
                $this->_data['network'] = isset($requestInfo['network']) ? $requestInfo['network'] : 'otherNet';

                $this->_data['cost'] = isset($requestInfo['note']['runloop']) ? $requestInfo['note']['runloop']:0;
                $this->_data['size'] = isset( $requestInfo['note']['size'] ) ? $requestInfo['note']['size']:0;
                $this->_data['ResponseStatusCode'] = isset($requestInfo['note']['success']) ? $requestInfo['note']['success']:0;
            }
        }

        if( !empty($this->_data) && in_array($requestInfos['appName'],$this->_iqianggouApp)  ){
            $this->SaveIQGRequestInfo();
        }elseif(!empty($this->_data) &&  in_array($requestInfos['appName'],$this->_haoshiqiApp) ){
            $this->SaveHSQRequestInfo();
        }

		return true;
	}
    protected function convert($size)
    {
        $unit = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');
        return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 1) . ' ' . $unit[$i];
    }

    protected function writeln($text = '')
    {
        $prefix = '[' . (new \DateTime('now'))->format('Y-m-d H:i:s') . "][" . $this->convert(memory_get_usage(true)) . "]" .$text ."\t\n";
        print_r($prefix);
    }

	public function GetYesterday()
	{
		return date("Y-m-d", strtotime("-1 day"));
	}

	public function GetYesterdayLogFile()
	{
		return $this->_subjectPath . $this->_subject .'-' . $this->GetYesterday() .'.log' ;
	}

	public function SetSubjectPath(  )
	{
        try{
            $this->_subjectPath = $this->_subjectParentPath ;
        }catch ( Exception $e){
            var_dump($e->getMessage());
        }
	}

    public function SaveIQGRequestInfo()
	{
		$coll = $this->_connection->iqg_app_offline_logs;
		$coll->insert( $this->_data );
	}

    public function SaveHSQRequestInfo()
    {
        $coll = $this->_connection->hsq_app_offline_logs;
        $coll->insert( $this->_data );
    }

	public function RunAction( $fileName )
	{
        ini_set('memory_limit','-1');
		$fp 			= fopen( $fileName, "r" );
		$error			= error_get_last();

		if (NULL != $error) {
			exit('读取文件异常,系统退出');
		}
		echo "Start parse app_offline_log[" . $fileName . "] at " . date('Y-m-d H:i:s') . "\n\n";

		while( !feof( $fp ) ) 
		{ 
			$lineMsg = fgets( $fp );

            $line = explode('[INFO]:',$lineMsg);
            if(empty($line[1]) ){
                continue;
            }
            $line = trim($line[1]);
            $requestInfo =  json_decode($line,true);
			if ( !isset($requestInfo[0]) || !is_array($requestInfo[0]['logdata']) ) {
				continue;
			}
            $requestInfo = $requestInfo[0];
			if ($this->AnalyseRequestInfo( $requestInfo )) {
//				$this->SaveRequestInfo();
			}
            $this->writeln();
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

    $ParseAppOfflineLog = new ParseAppOfflineLog();

    $ParseAppOfflineLog->SetSubjectPath();
    $ParseAppOfflineLog->RunYesterdayLog();

    $totalTime = time() - $startTime;
    var_dump('总的处理时间:'. $totalTime);
} catch (Exception $e) {
    var_dump($e->getMessage(),$e->getCode());
}

