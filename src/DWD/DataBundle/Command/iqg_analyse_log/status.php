<?php
/*
 * 爱抢购 open_api接口性能分析
 *
 * */
class ApiStatus
{
	private $statusInfo = array();// 数据量比较大
	public $requestTime;
	private $_connection;
	private $_subject = 'access';
	private $_subjectPath = '';
	private $_serverIp = '127.0.0.1';

	function __construct()
	{
		$config = parse_ini_file("config.ini", true);
		$this->_subject = $config['status']['prefix_file_name'];
		$this->_subjectPath = $config['common']['subject_path'];
		$this->_serverIp = $config['common']['server_ip'];

		$this->connectMongo($config['mongo']['server'], $config['mongo']['db_name']);
	}

	protected function connectMongo($server = "mongodb://localhost:27017", $db_name = "iqianggou_analyse")
	{
		$m = new MongoClient($server);
		$this->_connection = $m->selectDB($db_name);
	}

	public function AnalysisApiInfo( $requestInfo, $source = null )
	{
		if ($source == 'mongo') {
			$path = $requestInfo['path'];
		} else {
			$path = $requestInfo['request']['path'];
		}

		$extend  = pathinfo($path);
		if( isset( $extend["extension"] ) ){
			$extend  = strtolower($extend["extension"]);
			if( preg_match( "/jpg|png|js|css|webp|ts/", $extend ) ){
				return ;
			}
		}
 
        $path    = preg_replace( "/\/\d+\w+/", "/:id", $path );
        $path    = preg_replace( "/\/\d+\/\w+/", "/:id", $path );

        if( $requestInfo['cost'] < 0 ){
        	return ;
        }

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
		if( isset( $requestInfo['ResponseStatusCode'] ) ){
			 $this->StatusCodeVerify( $requestInfo['ResponseStatusCode'] ) ? ++ $this->statusInfo[$path]['successed'] : ++ $this->statusInfo[$path]['failed'];
			// ++ $this->statusInfo[$path]['successed'];
		} else {
			++ $this->statusInfo[$path]['failed'];
		}		
	}

	public function StatusCodeVerify( $code ){
		if( intval( $code ) >= 10000 && intval( $code ) <= 10001 ){
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

	public function GetRequestTime( $msg )
	{
		$pattern = '/^\[(.+)\] \[.+\]: \[(.+)\]/';
		preg_match( $pattern, $msg, $matches );
		if (count($matches) < 3) {
			return false;
		}
		$currentTimestamp = strtotime( $matches[1] );
		$this->requestTime = $currentTimestamp;
		return $currentTimestamp;
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
		$coll = $this->_connection->openapi_access_data;
		foreach ( $this->statusInfo as $pathInfo ) {
			$pathInfo['startTimestamp'] = $startTimestamp;
			$coll->insert( $pathInfo );
		}
	}

	public function GetApiStatus( $startTime = null, $endTime = null, $saveFlag = false )
	{
		$coll = $this->_connection->openapi_access_logs;
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
		$this->DropApiAccessLogs();
	}

	public function DropApiAccessLogs()
	{
		$coll = $this->_connection->openapi_access_logs;
		$response = $coll->drop();
		return $response;
	}

}

    $startTime = time();

    $apiStatus 		= new ApiStatus();
    $apiStatus->GetApiStatus( $apiStatus->GetYesterday(), date("Y-m-d"), true );

    var_dump('解析数据总的处理时间:'. time() - $startTime);

exit();