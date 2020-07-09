<?php

namespace App\Repository;

use App\Entity\OpenStackTemplate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method OpenStackTemplate|null find($id, $lockMode = null, $lockVersion = null)
 * @method OpenStackTemplate|null findOneBy(array $criteria, array $orderBy = null)
 * @method OpenStackTemplate[]    findAll()
 * @method OpenStackTemplate[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OpenStackTemplateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OpenStackTemplate::class);
    }

    // /**
    //  * @return OpenStackTemplate[] Returns an array of OpenStackTemplate objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('o.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?OpenStackTemplate
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
