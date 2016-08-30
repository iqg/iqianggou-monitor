<?php
/***************************************************************************
 *
 * Copyright (c) 2013 Baidu.com, Inc. All Rights Reserved
 *
**************************************************************************/

namespace DWD\TongjiBundle\Util\OfflineLog;

use DWD\TongjiBundle\Util\OfflineLog\Service_Data_Iqg_Des;

/**
 * @file Addlog.php
 * @author caowei(caowei@baidu.com)
 * @date 2014-3-26 下午4:44:23
 * @brief 描述
**/

class Service_Data_Iqg_Addlog{
    protected $_key       = '';
    protected $_logprefix = 'appserverlogdata';
    protected $_subjectpath = '/data/logs/app_offline_log_new/';
    /*
     * @params $key 加密key
     * @params $logprefix 打印在日志的标志key
     */
    public function __construct($key = 'ddff862c1de0c70ceeb4b4ea', $logprefix = 'appserverlogdata'){
        $this->_key       = $key;
        $this->_logprefix = $logprefix;
        if (empty($this->_logprefix) || empty($this->_key)) {
            return array(
                    'errno'  => 901,
                    'errmsg' => 'Server Error'
            );
        }
    }

    public function addLog($arrParams){
        if(empty($arrParams['logdata'])){
            return array(
                    'errno'  => 902,
                    'errmsg' => 'logdata Missing'
            );
        }
        try {
            $en_method   = new Service_Data_Iqg_Des($this->_key);
            $logdata_urldecode = urldecode($arrParams['logdata']);
            $logdata_des = $en_method->decrypt($logdata_urldecode);
            $logdata     = json_decode(trim($logdata_des), true);

            if(!is_array($logdata)){
                return array(
                        'errno'  => 903,
                        'errmsg' => 'logdata Content Error'
                );
            }
            $arrParams['logdata'] = $logdata;
            $this->saveLog($arrParams);

            return array('errno'  => 0);
        } catch(Exception $e ){
            return array(
                    'errno'  => $e->getCode(),
                    'errmsg' => $e->getMessage()
            );
        }
        return array('errno' => 0);
    }

    protected function saveLog($data){
        $now                = time();
        $currentTimeFormat  = date("Y-m-d H:i:s", $now);
        $filename           = $this->_subjectpath . $this->_logprefix . '-' . date('Y-m-d', $now) . '.log';
        $dataLine           = "[$currentTimeFormat] [INFO]: [" . json_encode($data) . "]\n";
        file_put_contents($filename, $dataLine, FILE_APPEND);
    }
}