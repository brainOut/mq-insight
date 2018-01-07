<?php

namespace Okvpn\Bundle\MQInsightBundle\Entity\Repository;

/**
 * ProcessorStatRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ProcessorStatRepository extends \Doctrine\ORM\EntityRepository
{
    public function summaryStat(\DateTime $from)
    {
        $qb = $this->createQueryBuilder('p')
            ->select(
                [
                    'SUM(p.avgTime*(p.ack + p.reject + p.requeue)) as totalTime',
                    'p.name'
                ]
            )
            ->where('p.created > :from')
            ->andWhere("p.name NOT IN ('idle')")
            ->groupBy('p.name')
            ->setParameter('from', $from);

        return $qb->getQuery()->getResult();
    }
}
