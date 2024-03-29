<?php

namespace DWD\TongjiBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Ob\HighchartsBundle\Highcharts\Highchart;
use Zend\Json\Expr;

/**
 * Class IqgAnalyseController
 * @package DWD\TongjiBundle\Controller
 * @Route("/analyse/api/iqg")
 */
class IqgAnalyseController extends Controller
{
    /**
     * Uri list of Analyse statistics
     *
     * @Route("/",name="dwd_tongji_analyse_iqg_dashboard")
     */
    public function indexAction(Request $request)
    {
        $date = $request->get('date');

        $dm = $this->get('doctrine_mongodb')->getManager();
        $mongoConn = $dm->getConnection()->getMongo()->selectDB($this->container->getParameter('database_mongo_prod_db'));
        $oal = $mongoConn->selectCollection('openapi_access_data');

        if (isset($date) && !empty($date)) {
            $startTimestamp = strtotime($date);
        } else {
            $lastRecord = $oal->find()->sort(['startTimestamp' => -1])->limit(1)->getNext();
            $startTimestamp = $lastRecord['startTimestamp'];
        }

        $apiDataCursor = $oal->find([
            'startTimestamp' => $startTimestamp,
        ])->sort(['called' => -1]);
        $apiList = iterator_to_array($apiDataCursor);

        $sumCall = 0;
        foreach($apiList as $key => $val) {
            $sumCall +=  $val['called'];
        }

        return $this->render('DWDTongjiBundle:Analyse:api_list.html.twig', array(
            'apiList'       => $apiList,
            'sumCall'       => $sumCall,
            'title'         => date('Y-m-d', $startTimestamp) . ' API访问',
            'startTimestamp' => $startTimestamp,
            'subject'       => 'tongji_analyse_iqg',
            'project'       => 'iqg',
        ));
    }

    /**
     * Uri chart of Analyse statistics
     *
     * @Route("/urichart",name="dwd_tongji_analyse_iqg_dashboard_urichart")
     */
    public function showUriChartAction(Request $request)
    {
        $uri = $request->get('uri');
        $startTime = $request->get('startTime');
        $endTime = $request->get('endTime');
        $duration = $request->get('duration');
        $type = $request->get('type');
        $regionId = $request->get('regionId');

        $dm = $this->get('doctrine_mongodb')->getManager();
        $mongoConn = $dm->getConnection()->getMongo()->selectDB($this->container->getParameter('database_mongo_prod_db'));

        $calledArray = array();
        $costArray = array();
        $maxCostArray = array();
        $categories = array();
        $startTimestamp = intval($startTime);
        $currentTimestamp = $startTimestamp;
        $endTimestamp = intval($endTime);
        $dataCount = ($endTimestamp - $startTimestamp)/$duration;

        if ($type == 'day') {
            $oal = $mongoConn->selectCollection('openapi_access_data');
            $apiCursor = $oal->find([
                'path' => $uri,
                'startTimestamp' => [
                    '$gte' => $startTimestamp,
                    '$lte' => $endTimestamp
                ]
            ])->sort(['startTimestamp' => 1]);
            foreach ($apiCursor as $doc) {
                if ($doc['totalCost'] < 0) {
                    continue;
                }
                $calledArray []= $doc['called'];
                $costArray []= round($doc['totalCost'] / $doc['called'], 2);
                $maxCostArray []= $doc['maxCost'];
                $categories []= date("Y-m-d", $doc['startTimestamp']);
            }
            $currentTimestamp = $endTimestamp;
        } else {
            $oal = $mongoConn->selectCollection('openapi_access_logs');

            for ( $i = 0; $i < $dataCount; $i ++ ) {
                $requestCursor = $oal->find([
                    'request_time' => [
                        '$gte' => $currentTimestamp,
                        '$lt' => $currentTimestamp + $duration
                    ],
                    'path' => $uri
                ]);

                $called = 0;
                $totalCost = 0;
                foreach ($requestCursor as $doc) {
                    if ( $doc['cost'] < 0 ) {
                        continue;
                    }
                    $called ++;
                    $totalCost += $doc['cost'];
                }

                if ( $called ) {
                    $calledArray []= $called;
                    $costArray []= $totalCost / $called;
                } else {
                    $calledArray []= 0;
                }
                // 精确到分钟
                if ( $duration > 60 && $duration % 60 == 0 ) {
                    $categories []= date( 'H:i', ($currentTimestamp + $duration) );
                }
                $currentTimestamp += $duration;
            }
            $currentTimestamp = $startTimestamp;
        }

        $series = array(
            array(
                'name'  => '访问次数',
                'type'  => 'column',
                'color' => '#4572A7',
                'yAxis' => 1,
                'data'  => $calledArray,
            ),
            array(
                'name'  => '平均响应时间',
                'type'  => 'spline',
                'color' => '#AA4643',
                'data'  => $costArray,
            ),
            array(
                'name'  => '最大响应时间',
                'type'  => 'spline',
                'color' => '#000000',
                'yAxis' => 2,
                'dashStyle' => 'shortdot',
                'data'  => $maxCostArray,
            )
        );
        $yData = array(
            array(
                'labels' => array(
                    'formatter' => new Expr('function () { return this.value.toFixed(0) + "毫秒" }'),
                    'style'     => array('color' => '#AA4643')
                ),
                'title' => array(
                    'text'  => '平均响应时间',
                    'style' => array('color' => '#AA4643')
                ),
                'opposite' => true
            ),
            array(
                'labels' => array(
                    'formatter' => new Expr('function () { return this.value }'),
                    'style'     => array('color' => '#4572A7')
                ),
                'gridLineWidth' => 0,
                'title' => array(
                    'text'  => '访问次数',
                    'style' => array('color' => '#4572A7')
                ),
            ),
            array(
                'labels' => array(
                    'formatter' => new Expr('function () { return this.value.toFixed(0) + "毫秒" }'),
                    'style'     => array('color' => '#000000')
                ),
                'gridLineWidth' => 0,
                'title' => array(
                    'text'  => '最大响应时间',
                    'style' => array('color' => '#000000')
                ),
                'opposite' => true,
            ),
        );

        $ob = new Highchart();
        $ob->credits->enabled(false);
        $ob->chart->renderTo('container'); // The #id of the div where to render the chart
        $ob->chart->type('column');
        $ob->title->text('访问请求-' . $uri);
        $ob->subtitle->text(date('Y-m-d', $startTime) . '——' . date('Y-m-d', $endTime));
        $ob->xAxis->categories($categories);
        $ob->xAxis->crosshair(true);
        $ob->yAxis($yData);
        $ob->legend->enabled(true);
        $formatter = new Expr('function () {
                 var unit = {
                     "访问次数": "次",
                     "平均响应时间": "ms",
                     "最大响应时间" : "ms"
                 }[this.series.name];
                 return this.x + ": <b>" + this.y.toFixed(2) + "</b> " + unit;
             }');
        $ob->tooltip->formatter($formatter);
        $ob->series($series);

        return $this->render('DWDTongjiBundle:Analyse:url_chart.html.twig', array(
            'chart'        => $ob,
            'currentTimestamp' => $currentTimestamp,
            'uri'          => $uri,
            'regionId'     => $regionId,
            'subject' => 'tongji_analyse_iqg',
            'project' => 'iqg',
        ));
    }
}