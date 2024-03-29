<?php

namespace App\Repository;

use App\Entity\HealthLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method HealthLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method HealthLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method HealthLog[]    findAll()
 * @method HealthLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HealthLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HealthLog::class);
    }

    public function removeOld($date)
    {
        $date = new \DateTime($date);

        $qd = $this->createQueryBuilder('h');
        $qd->delete()
            ->where('h.dateCreated < :date')
            ->setParameter('date', $date);
        $query = $qd->getQuery();
        return $query->getResult();
    }

    // /**
    //  * @return HealthLog[] Returns an array of HealthLog objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('h.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?HealthLog
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
