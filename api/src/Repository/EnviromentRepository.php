<?php

namespace App\Repository;

use App\Entity\Enviroment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Enviroment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Enviroment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Enviroment[]    findAll()
 * @method Enviroment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EnviromentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Enviroment::class);
    }

    // /**
    //  * @return Enviroment[] Returns an array of Enviroment objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('e.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Enviroment
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
