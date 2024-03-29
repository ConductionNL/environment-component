<?php

namespace App\Repository;

use App\Entity\Cluster;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Cluster|null find($id, $lockMode = null, $lockVersion = null)
 * @method Cluster|null findOneBy(array $criteria, array $orderBy = null)
 * @method Cluster[]    findAll()
 * @method Cluster[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClusterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cluster::class);
    }

    /*
     * Get the clusters that are up for configuration
     *
     * @param integer $maxResults
     */
    public function findConfigurable($maxResults = 100)
    {
        return $this->createQueryBuilder('c')
            ->where('c.dateConfigured IS NULL')
            ->orderBy('c.dateConfigured', 'ASC')
            ->setMaxResults($maxResults)
            ->getQuery()
            ->getResult();
    }

    // /**
    //  * @return Cluster[] Returns an array of Cluster objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Cluster
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
