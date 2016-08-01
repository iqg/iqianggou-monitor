<?php

class AppOfflineLogStatus
{
	private $statusInfo =  [];
	public  $requestTime;
	private $_connection;
	private $_subject = 'iqg_access_internal_api.log';
	private $_subjectPath = '';
	private $_serverIp = '127.0.0.1';
    private $_config;
    private $_imagesize =[ '10k' =>[ 0,10240],
                           '10k_30k' =>[ 10240,30720],
                           '30k_50k' =>[ 30720,51200],
                           '50k_70k' =>[ 51200,71680],
                           '70k_90k' =>[ 71680,92160],
                           '90k_110k' =>[ 92160,112640],
                           '110k_9999999k' =>[ 112640,9999999],
                         ];

    function __construct(){
        $this->_config = parse_ini_file("config.ini", true);

		$this->_subject =  $this->_config['internalapi_status']['prefix_file_name'];
        $this->_subjectPath = $this->_config['internalapi_access']['internalapi_subject_path'];
		$this->_serverIp =  $this->_config['common']['server_ip'];

		$this->connectMongo( $this->_config['mongo']['server'],  $this->_config['mongo']['db_name']);
	}

	protected function connectMongo($server = "mongodb://localhost:27017", $db_name = "iqianggou_analyse")
	{
		$mongo = new MongoClient($server);
		$this->_connection = $mongo->selectDB($db_name);
	}

    //分析每个接口的访问数据记录
	public function AnalysisApiInfo( $requestInfo )
	{
        if( $requestInfo['cost'] <= 0 ){
            return ;
        }
        if( !in_array($requestInfo['network'],['2G','3G','4G','Wi-Fi','otherNet'])  ){
            echo  json_encode($requestInfo);
            return ;
        }
        if($requestInfo['actionId'] == 'RequestLog'){

            $path = $requestInfo['path'];
            $path    = preg_replace( "/\/\d+/", "/:id", $path );
            $path    = explode("?",$path)[0];

            $network = $requestInfo['network'];

            if( isset( $this->statusInfo[$path][$network] ) ){   //相同的接口第二次调用
                ++ $this->statusInfo[$path][$network]['called']; //总的调用次数
                $this->statusInfo[$path][$network]['totalCost'] += $requestInfo['cost'];
                $this->statusInfo[$path][$network]['minCost'] > $requestInfo['cost'] ? $this->statusInfo[$path][$network]['minCost'] = $requestInfo['cost']:true;
                $this->statusInfo[$path][$network]['maxCost'] < $requestInfo['cost'] ? $this->statusInfo[$path][$network]['maxCost'] = $requestInfo['cost']:true;
            } else { //新的接口，新增一个path
                $this->statusInfo[$path][$network] 		       = array();
                $this->statusInfo[$path][$network]['path']       = $path;
                $this->statusInfo[$path][$network]['called']     = 1;
                $this->statusInfo[$path][$network]['totalCost']  = $requestInfo['cost'];
                $this->statusInfo[$path][$network]['minCost']    = $requestInfo['cost'];
                $this->statusInfo[$path][$network]['maxCost']    = $requestInfo['cost'];
                $this->statusInfo[$path][$network]['successed']  = 0;
                $this->statusInfo[$path][$network]['failed']     = 0;
            }
            $this->statusInfo[$path][$network]['sortShow']     = 2;
            if( isset( $requestInfo['ResponseStatusCode'] ) ){
                 $this->StatusCodeVerify( $requestInfo['ResponseStatusCode'] ) ? ++ $this->statusInfo[$path][$network]['successed'] : ++ $this->statusInfo[$path][$network]['failed'];
            } else {
                ++ $this->statusInfo[$path][$network]['failed'];
            }
        }else{ //图片接口性能分析

            if( $requestInfo['size'] <= 0 ){
                return ;
            }

            $path = $this->getSizePath($requestInfo['size']);

            $network = $requestInfo['network'];
            if( isset( $this->statusInfo[$path][$network] ) ){   //相同的接口第二次调用
                ++ $this->statusInfo[$path][$network]['called']; //总的调用次数
                $this->statusInfo[$path][$network]['totalCost'] += $requestInfo['cost'];
                $this->statusInfo[$path][$network]['minCost'] > $requestInfo['cost'] ? $this->statusInfo[$path][$network]['minCost'] = $requestInfo['cost']:true;
                $this->statusInfo[$path][$network]['maxCost'] < $requestInfo['cost'] ? $this->statusInfo[$path][$network]['maxCost'] = $requestInfo['cost']:true;
            } else {                                             //新的接口，新增一个path
                $this->statusInfo[$path][$network] 		       = array();
                $this->statusInfo[$path][$network]['path']       = $path;
                $this->statusInfo[$path][$network]['called']     = 1;
                $this->statusInfo[$path][$network]['totalCost']  = $requestInfo['cost'];
                $this->statusInfo[$path][$network]['minCost']    = $requestInfo['cost'];
                $this->statusInfo[$path][$network]['maxCost']    = $requestInfo['cost'];
                $this->statusInfo[$path][$network]['successed']  = 0;
                $this->statusInfo[$path][$network]['failed']     = 0;
            }
            $this->statusInfo[$path][$network]['sortShow']     = 1;
            if( isset( $requestInfo['ResponseStatusCode'] ) ){
                $this->StatusCodeVerify( $requestInfo['ResponseStatusCode'] ) ? ++ $this->statusInfo[$path][$network]['successed'] : ++ $this->statusInfo[$path][$network]['failed'];
            } else {
                ++ $this->statusInfo[$path][$network]['failed'];
            }

        }
	}

    public function getSizePath( $size ){
        $sizePath = 300; //默认300
        foreach( $this->_imagesize as $key => $val ){
            if( $val[0]< $size && $size <= $val[1] ){
                $sizePath =  $key;
                break;
            }
        }
        return  $sizePath;
    }

	public function StatusCodeVerify( $code ){
		if( intval( $code ) == 1 ){
			return true;
		}
		return false;
	}


	public function SaveApiInfo( $startTimestamp ,$mongolog)
	{
        echo ' time => ' . $startTimestamp . "\n" ;
        if( $mongolog == 'iqg_app_offline_logs'){
            $coll = $this->_connection->iqg_app_offline_data;
        }elseif($mongolog == 'hsq_app_offline_logs') {
            $coll = $this->_connection->hsq_app_offline_data;
        }

		foreach ( $this->statusInfo as $pathInfo ) {
          if(!empty($pathInfo)){

             foreach($pathInfo as $network => $row){

                $row['startTimestamp'] = $startTimestamp;
                $row['network'] = $network;

                $coll->insert( $row );
             }
          }
		}
	}

    //入口,从mogodb里取得数据
	public function GetApiStatus( $mongoLog )
	{

        if( $mongoLog == 'iqg_app_offline_logs'){
           $coll = $this->_connection->iqg_app_offline_logs;
        }elseif($mongoLog == 'hsq_app_offline_logs') {
		   $coll = $this->_connection->hsq_app_offline_logs;
        }

		$query = [];

		$cursor = $coll->find( $query );

		while ($cursor->hasNext()) {
			$requestInfo = $cursor->getNext();
			$this->AnalysisApiInfo( $requestInfo );
		}

        $this->SaveApiInfo( strtotime(date("Y-m-d",strtotime("-1 day"))), $mongoLog);

        echo '处理数据结束\n';
		$this->DropApiAccessLogs($mongoLog);
	}

	public function DropApiAccessLogs($mongoLog)
	{
        if( $mongoLog == 'iqg_app_offline_logs'){
            $coll = $this->_connection->iqg_app_offline_logs;
        }elseif($mongoLog == 'hsq_app_offline_logs') {
            $coll = $this->_connection->hsq_app_offline_logs;
        }
		$response = $coll->drop();
        var_dump('删除数据是否成功：',$response);
		return $response;
	}
}

try{
    ini_set('memory_limit','-1');
    $data = implode(" ", $argv);
    $mongoLog = trim($argv[1]);
    if(!in_array($mongoLog,['iqg_app_offline_logs','hsq_app_offline_logs'] )){
        print_r('参数错误，请输入 iqg_app_offline_logs , hsq_app_offline_logs  ');
        exit;
    }

    $startTime = time();

    $apiStatus 		= new AppOfflineLogStatus();
    $apiStatus->GetApiStatus( $mongoLog );

    var_dump('生成正式数据总的处理时间:'. time() - $startTime);
} catch (Exception $e) {
    var_dump($e->getMessage(),$e->getCode());
}

