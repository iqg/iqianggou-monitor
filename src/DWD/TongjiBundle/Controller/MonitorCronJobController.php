<?php

namespace DWD\TongjiBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class MonitorCronJobController
 * @package DWD\TongjiBundle\Controller
 * @Route("/analyse/cronjob")
 */
class MonitorCronJobController extends Controller
{
    protected $_default_time_interval = 5184000;
    protected $_cronjob_table_columns = array( 'name', 'job', 'msg', 'startTime', 'stopTime', 'cost', 'code' );

    /**
     * Uri list of monitor cronjob statistics
     *
     * @Route("/",name="dwd_tongji_monitor_cronjob_dashboard")
     */
    public function indexAction(Request $request)
    {
        $currentTimestamp = time();
        $currentTimestamp = $currentTimestamp - $currentTimestamp%60;
        $startTime = $currentTimestamp - $this->_default_time_interval;
        $endTime = $currentTimestamp;
        if ($request->get('startTime')) {
            $startTime = strtotime($request->get('startTime'));
        }
        if ($request->get('endTime')) {
            $endTime = strtotime($request->get('endTime'));
        }
        $code = $request->get('status');
        $owner = $request->get('owner');

        $cronjobDataRepository = $this->getDoctrine()->getRepository('DWDDataBundle:CronJobData');
        $cronjobDataList = $cronjobDataRepository->getCronJobDataList( $startTime, $endTime, $code, $owner );

        $cronjobRepository = $this->getDoctrine()->getRepository('DWDDataBundle:CronJob');
        $cronjobList = $cronjobRepository->findAll();

        return $this->render('DWDTongjiBundle:Analyse:cronjob_list.html.twig', array(
            'cronjobList'       => $cronjobList,
            'cronjobDataList'   => $cronjobDataList,
            'startTime'         => $startTime,
            'endTime'           => $endTime,
            'code'              => $code,
            'owner'             => $owner,
            'subject'           => 'tongji_monitor_cronjob'
        ));
    }

    /**
     * JSON DATA of monitor cronjob statistics
     *
     * @Route("/list",name="dwd_tongji_monitor_cronjobs_show")
     */
    public function cronjobsDataAction(Request $request)
    {
        $iDisplayStart   = $request->get('iDisplayStart');
        $iDisplayLength  = $request->get('iDisplayLength');
        $sEcho           = $request->get('sEcho');
        $sSearch         = $request->get('sSearch', null);
        $iSortCol_0      = $request->get('iSortCol_0', null);
        $sSortDir_0      = $request->get('sSortDir_0', null);
        $startTime       = $request->get('startTime');
        $endTime         = $request->get('endTime');
        $code            = $request->get('code');
        $owner           = $request->get('owner');

        $cronjobDataRepository = $this->getDoctrine()->getRepository('DWDDataBundle:CronJobData');
        $cronjobDataList = $cronjobDataRepository->getCronJobDataList($startTime, $endTime, $code, $owner, $iDisplayStart, $iDisplayLength, $sSearch, $this->_cronjob_table_columns[$iSortCol_0], $sSortDir_0);
        $total = $cronjobDataRepository->getCronJobDataCount( $startTime, $endTime, $code, $owner, $sSearch );

//        $cronjobList = [];
//        foreach( $cronjobDataList as $cronjobDataKey => $cronjobDataValue ) {
//            $cronjobList []= array(
//                                $cronjobDataValue['name'],
//                                $cronjobDataValue['job'],
//                                $cronjobDataValue['msg'],
//                                $cronjobDataValue['startTime'],
//                                $cronjobDataValue['stopTime'],
//                                $cronjobDataValue['cost'],
//                                $cronjobDataValue['code'],
//                                ''
//                             );
//        }



        $res             = array
        (
            "sEcho"                => $sEcho,
            "aaData"               => $cronjobDataList,
            "iTotalRecords"        => $total,
            "iTotalDisplayRecords" => $total,
        );
        $response        = new Response();
        $response->setContent( json_encode( $res ) );
        return $response;
    }
}