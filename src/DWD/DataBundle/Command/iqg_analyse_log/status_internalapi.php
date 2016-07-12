<?php

class InternalApiStatus
{
	private $statusInfo =  []; //数据量比较大
	public  $requestTime;
	private $_connection;
	private $_subject = 'iqg_access_internal_api.log';
	private $_subjectPath = '';
	private $_serverIp = '127.0.0.1';
    private $_config;

    function __construct(){
        $this->_config = parse_ini_file("config.ini", true);

		$this->_subject =  $this->_config['internalapi_status']['prefix_file_name'];
        $this->_subjectPath = $this->_config['internalapi_access']['internalapi_subject_path'];
		$this->_serverIp =  $this->_config['common']['server_ip'];

		$this->connectMongo( $this->_config['mongo']['server'],  $this->_config['mongo']['db_name']);
	}

	protected function connectMongo($server = "mongodb://localhost:27017", $db_name = "iqianggou_analyse")
	{
		$m = new MongoClient($server);
		$this->_connection = $m->selectDB($db_name);
	}
    //分析每个接口的数据
	public function AnalysisApiInfo( $requestInfo, $source = null )
	{
    	$path = $requestInfo['path'];
		$extend  = pathinfo($path);

		if( isset( $extend["extension"] ) ){
			$extend  = strtolower($extend["extension"]);
			if( preg_match( "/jpg|png|js|css|webp|ts/", $extend ) ){
				return ;
			}
		}
 
        $path    = preg_replace( "/\/\d+/", "/:id", $path );

        if( $requestInfo['cost'] < 0 ){
        	return ;
        }

		if( isset( $this->statusInfo[$path] ) ){  //相同的接口第二次调用
			++ $this->statusInfo[$path]['called'];//总的调用次数
			$this->statusInfo[$path]['totalCost'] += $requestInfo['cost'];
			$this->statusInfo[$path]['minCost'] > $requestInfo['cost'] ? $this->statusInfo[$path]['minCost'] = $requestInfo['cost']:true;
			$this->statusInfo[$path]['maxCost'] < $requestInfo['cost'] ? $this->statusInfo[$path]['maxCost'] = $requestInfo['cost']:true;
		} else {
			$this->statusInfo[$path] 		       = array();
			$this->statusInfo[$path]['path']       = $path;
			$this->statusInfo[$path]['called']     = 1;
			$this->statusInfo[$path]['totalCost']  = $requestInfo['cost'];
			$this->statusInfo[$path]['minCost']    = $requestInfo['cost'];
			$this->statusInfo[$path]['maxCost']    = $requestInfo['cost'];
			$this->statusInfo[$path]['successed']  = 0;
			$this->statusInfo[$path]['failed']     = 0;
		}
		if( isset( $requestInfo['ResponseStatusCode'] ) ){
			 $this->StatusCodeVerify( $requestInfo['ResponseStatusCode'] ) ? ++ $this->statusInfo[$path]['successed'] : ++ $this->statusInfo[$path]['failed'];
			// ++ $this->statusInfo[$path]['successed'];
		} else {
			++ $this->statusInfo[$path]['failed'];
		}		
	}

	public function StatusCodeVerify( $code ){
		if( intval( $code ) == 0 ){
			return true;
		}
		return false;
	}

	public function Output()
	{
//		usort( $this->statusInfo, array( 'ApiStatus', 'calledCmpSort' ) );
		usort( $this->statusInfo, array( 'InternalApiStatus', 'calledCmpSort' ) );
		$txt = sprintf("|%50s| %12s |  %12s | %12s | %12s | %12s | %12s |\r\n", 'PATH', 'maxCost','minCost','avgCost','successed','failed','called' );
		echo $txt;

		foreach( $this->statusInfo as  $pathInfo ){
			$txt = sprintf("| %50s | %12.2f | %12.2f | %12.2f | %12d | %12d | %12d |\r\n", $pathInfo['path'], $pathInfo['maxCost'], $pathInfo['minCost'], $pathInfo['totalCost'] / $pathInfo['called'], $pathInfo['successed'], $pathInfo['failed'], $pathInfo['called']);
			echo $txt;
		}
	}

	static public function calledCmpSort( $fir, $sec )
	{
		if( $fir['called'] == $sec['called'] ){
			return 0;
		}

		return ($fir['called'] > $sec['called']) ? -1 : 1;
	} 

	public function GetYesterday()
	{
		return date("Y-m-d", strtotime("-1 day"));
	}

	public function GetYesterdayLogFile()
	{
		return $this->_subjectPath . $this->_subject . '-' . $this->GetYesterday() . '.log';
	}
	
	public function SaveApiInfo( $startTimestamp )
	{
		$coll = $this->_connection->internalapi_access_data;//写的数据库

		foreach ( $this->statusInfo as $pathInfo ) {
			$pathInfo['startTimestamp'] = $startTimestamp;
			$coll->insert( $pathInfo );
		}
	}

    //入口,从mogodb里取得数据
	public function GetApiStatus( $startTime = null, $endTime = null, $saveFlag = false )
	{
        print_r('开始时间' .$startTime);
		$coll = $this->_connection->internalapi_access_logs;
		$query = array();
		if (isset( $startTime )) {
			$startTime = strtotime($startTime);
			$query['request_time']['$gte'] = intval($startTime); 
		}
		if (isset( $endTime )) {
			$endTime = strtotime($endTime);
			$query['request_time']['$lt'] = intval($endTime);
		}
		$cursor = $coll->find( $query );

		while ($cursor->hasNext()) {
			$requestInfo = $cursor->getNext();
			$this->AnalysisApiInfo( $requestInfo, 'mongo' );
		}
		if ($saveFlag) {
			$this->SaveApiInfo($query['request_time']['$gte']);
		} else {
			$this->Output();
		}
        echo '执行结束';
		$this->DropApiAccessLogs();
	}

	public function DropApiAccessLogs()
	{
		$coll = $this->_connection->internalapi_access_logs;
		$response = $coll->drop();
		return $response;
	}
}

try{
    ini_set('memory_limit','-1');
    $startTime = time();
    $apiStatus 		= new InternalApiStatus();
    $apiStatus->GetApiStatus( $apiStatus->GetYesterday(), date("Y-m-d"), true );
    $totalTime = time() - $startTime;
    var_dump('生成正式数据总的处理时间:'. $totalTime);
} catch (Exception $e) {
    var_dump($e->getMessage(),$e->getCode());
}

