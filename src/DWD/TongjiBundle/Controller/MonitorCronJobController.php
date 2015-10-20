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

    /**
     * Uri list of Analyse statistics
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
}