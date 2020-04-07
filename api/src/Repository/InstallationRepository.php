<?php

namespace App\Repository;

use App\Entity\Installation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Installation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Installation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Installation[]    findAll()
 * @method Installation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InstallationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Installation::class);
    }

    // /**
    //  * @return Component[] Returns an array of Component objects
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
     * Get the installitions that are uo for either installation or update
     *
     * @param integer $maxResults
     */
    public function findInstallable($maxResults = 100)
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.dateInstalled < i.dateModified')
            ->orderBy('i.dateModified', 'ASC')
            ->setMaxResults($maxResults)
            ->getQuery()
            ->getResult()
            ;
    }
}
