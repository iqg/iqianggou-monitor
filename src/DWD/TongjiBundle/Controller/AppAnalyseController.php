<?php
/**
 * Created by PhpStorm.
 * User: jokeikusunoki
 * Date: 15/10/28
 * Time: 上午11:03
 */

namespace DWD\TongjiBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Acl\Exception\Exception;

use DWD\TongjiBundle\Util\OfflineLog\Service_Data_Iqg_Addlog;

/**
 * Class AppAnalyseController
 * @package DWD\TongjiBundle\Controller
 * @Route("analyse/app")
 */
class AppAnalyseController extends Controller
{
    /**
     * Get the app offline report data
     *
     * @Route("/report",name="dwd_tongji_app_test_dashboard")
     */
    public function reportAction()
    {
        $data = @file_get_contents('php://input');
        $data = json_decode($data, true);

        $addAppLog      = new Service_Data_Iqg_Addlog();
        $res            = $addAppLog->addLog($data);

        $response = new Response();
        if ($res['errno'] == 0) {
            $response->setStatusCode(200);
        } else {
            $response->setStatusCode(500);
        }
        return $response;
    }
}