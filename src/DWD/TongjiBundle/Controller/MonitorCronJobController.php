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
    protected $codeHash = array( '0' => array( 'color' => 'text-success', 'label' => '成功' ), '99' => array( 'color' => 'text-danger', 'label' => '失败' ), '98' => array( 'color' => 'text-warning', 'label' => '超时' ) );
    protected $_default_time_interval = 86400;

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

        $em = $this->getDoctrine()->getManager();
        $qb = $em->createQueryBuilder();
        $qb
            ->select('cd')
            ->from('DWD\DataBundle\Entity\CronJobData', 'cd')
            ->where('cd.startTime >= :startTime')
            ->andWhere('cd.startTime <= :endTime')
            ->setParameter('startTime', $startTime)
            ->setParameter('endTime', $endTime);
        if (isset($code) && $code != '') {
            $qb
                ->andWhere('cd.code = :code')
                ->setParameter('code', $code);
        }
        if (isset($owner) && $owner != '') {
            $qb
                ->leftJoin("cd.cronjob", 'c')
                ->andWhere('c.owner = :owner')
                ->setParameter('owner', $owner);
        }

        $cronjobList = $qb->getQuery()->getArrayResult();

        return $this->render('DWDTongjiBundle:Analyse:cronjob_list.html.twig', array(
            'cronjobList'       => $cronjobList,
            'codeHash'          => $this->codeHash,
            'startTime'         => $startTime,
            'endTime'           => $endTime,
            'code'              => $code,
            'owner'             => $owner
        ));
    }
}