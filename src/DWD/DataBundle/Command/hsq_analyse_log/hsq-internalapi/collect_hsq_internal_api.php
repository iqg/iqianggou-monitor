<?php

class HsqInternalApiStatus
{
	private $statusInfo = array();
	public $requestTime;
	private $_connection;
	private $_subject = 'access';
	private $_subjectPath = '';
	private $_serverIp = '127.0.0.1';
	private $_now = '';

	function __construct()
	{
		$config = parse_ini_file("config.ini", true);
		$this->_subject = $config['status']['prefix_file_name'];
		$this->_subjectPath = $config['common']['subject_path'];
		$this->_serverIp = $config['common']['server_ip'];
		$this->_now = time();

		$this->connectMongo($config['mongo']['server'], $config['mongo']['db_name']);
	}

	protected function connectMongo($server = "mongodb://localhost:27017", $db_name = "iqianggou_analyse")
	{
		$m = new MongoClient($server);
		$this->_connection = $m->selectDB($db_name);
	}

	public function AnalysisApiInfo( $requestInfo )
	{
		$path = $requestInfo['path'];

        $path    = preg_replace( "/\/\d+\w+/", "/:id", $path );
        $path    = preg_replace( "/\/\d+\/\w+/", "/:id", $path );

        if( $requestInfo['cost'] < 0 ){
			return ;
		}
		$requestInfo['cost'] = round($requestInfo['cost'], 2);

		if( isset( $this->statusInfo[$path] ) ){
			++ $this->statusInfo[$path]['called'];
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
		if( isset( $requestInfo['errno'] ) ){
			$this->StatusCodeVerify( $requestInfo['errno'] ) ? ++ $this->statusInfo[$path]['successed'] : ++ $this->statusInfo[$path]['failed'];
		} else {
			++ $this->statusInfo[$path]['failed'];
		}
	}

	public function GetLastHour()
	{
		return date('Y-m-d: H:', $this->_now - 3600) . '00:00';
	}

	public function GetCurrentHour()
	{
		return date('Y-m-d: H:', $this->_now) . '00:00';
	}

	public function StatusCodeVerify( $code ){
		if( intval( $code ) == 0 ){
			return true;
		}

		return false;
	}

	public function Output()
	{
		usort( $this->statusInfo, array( 'ApiStatus', 'calledCmpSort' ) );
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

	public function CheckValidRequestTime( $leftRequiredTime = null, $rightRequiredTime = null )
	{
		if (isset( $leftRequiredTime )) {
			$leftRequiredTime = strtotime($leftRequiredTime);
			if ($this->requestTime < $leftRequiredTime)
				return false;
		}
		if (isset( $rightRequiredTime )) {
			$rightRequiredTime = strtotime($rightRequiredTime);
			if ($this->requestTime > $rightRequiredTime)
				return false;
		}
		return true;
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
		echo $startTimestamp;
		$coll = $this->_connection->hsq_internal_api_data;

		foreach ( $this->statusInfo as $pathInfo ) {
			$pathInfo['startTimestamp'] = $startTimestamp;
			if( NULL === $coll->findOne( array( 'path' => $pathInfo['path'], 'startTimestamp' => $startTimestamp ) )) {
				$coll->insert( $pathInfo );
			}
		}
	}

	public function GetApiStatus( $startTime = null, $endTime = null, $saveFlag = false )
	{
		$coll = $this->_connection->hsq_internal_api_logs;
		$query = array();
		if (isset( $startTime )) {
			$startTime = strtotime($startTime);
			$query['server.REQUEST_TIME_FLOAT']['$gte'] = intval($startTime);
		}
		if (isset( $endTime )) {
			$endTime = strtotime($endTime);
			$query['server.REQUEST_TIME_FLOAT']['$lt'] = intval($endTime);
		}
		$cursor = $coll->find( $query );
		while ($cursor->hasNext()) {
			$requestInfo = $cursor->getNext();
			$this->AnalysisApiInfo( $requestInfo );
		}

        $this->SaveApiInfo($query['server.REQUEST_TIME_FLOAT']['$gte']);

		$this->DropApiAccessLogs();
	}

	public function DropApiAccessLogs()
	{
		$coll = $this->_connection->hsq_internal_api_logs;
		$response = $coll->drop();
        var_dump('删除　hsq_internal_api_logs 数据是否成功2：',$response);
		return $response;
	}
}
    $startTime = time();

    $apiStatus 		= new HsqInternalApiStatus();
    $apiStatus->GetApiStatus($apiStatus->GetYesterday(), date("Y-m-d"), true);

    var_dump('生成正式数据总的处理时间:'. time() - $startTime);

exit();
