<?php
/**
 * Created by PhpStorm.
 * User: jokeikusunoki
 * Date: 15/10/20
 * Time: 下午2:41
 */
namespace DWD\DataBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * CronJobDataRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class CronJobDataRepository extends EntityRepository
{
    public function getCronJobDataList($startTime, $endTime, $code = null, $owner = null, $start = 0, $count = 10, $name = null, $sortCol = null, $sortDir = null)
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        $qb
            ->select('cd')
            ->from('DWD\DataBundle\Entity\CronJobData', 'cd')
            ->where('cd.startTime >= :startTime')
            ->andWhere('cd.startTime <= :endTime')
            ->setParameter('startTime', $startTime)
            ->setParameter('endTime', $endTime)
            ->setFirstResult($start)
            ->setMaxResults($count);

        if (isset($code) && $code != '') {
            if (intval($code) == 100) {
                $qb
                    ->andWhere('cd.flag = :flag')
                    ->setParameter('flag', false);
            } else {
                $qb
                    ->andWhere('cd.code = :code')
                    ->setParameter('code', $code);
            }
        }
        if (isset($owner) && $owner != '') {
            $qb
                ->leftJoin("cd.cronjob", 'c')
                ->andWhere('c.owner = :owner')
                ->setParameter('owner', $owner);
        }
        if (isset($name) && $name != '') {
            $qb
                ->andWhere($qb->expr()->like('cd.name', ':name'))
                ->setParameter('name', $name);
        }
        if (isset($sortCol) && $sortCol != '' && isset($sortDir) && $sortDir != '') {
            $qb
                ->addOrderBy('cd.'.$sortCol, $sortDir);
        }
        return $qb->getQuery()->getArrayResult();
    }

    public function getCronJobDataCount($startTime, $endTime, $code = null, $owner = null, $name = null)
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        $qb
            ->select('count(cd)')
            ->from('DWD\DataBundle\Entity\CronJobData', 'cd')
            ->where('cd.startTime >= :startTime')
            ->andWhere('cd.startTime <= :endTime')
            ->setParameter('startTime', $startTime)
            ->setParameter('endTime', $endTime);

        if (isset($code) && $code != '') {
            if (intval($code) == 100) {
                $qb
                    ->andWhere('cd.flag = :flag')
                    ->setParameter('flag', false);
            } else {
                $qb
                    ->andWhere('cd.code = :code')
                    ->setParameter('code', $code);
            }
        }
        if (isset($owner) && $owner != '') {
            $qb
                ->leftJoin("cd.cronjob", 'c')
                ->andWhere('c.owner = :owner')
                ->setParameter('owner', $owner);
        }
        if (isset($name) && $name != '') {
            $qb
                ->andWhere($qb->expr()->like('cd.name', ':name'))
                ->setParameter('name', $name);
        }
        $cronJobDataCount = $qb->getQuery()->getResult();
        if( count($cronJobDataCount) ) {
            return intval($cronJobDataCount[0][1]);
        } else {
            return 0;
        }
    }
}
