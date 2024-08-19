<?php

namespace App\Repository;

use App\Entity\MoneyRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method MoneyRequest|null find($id, $lockMode = null, $lockVersion = null)
 * @method MoneyRequest|null findOneBy(array $criteria, array $orderBy = null)
 * @method MoneyRequest[]    findAll()
 * @method MoneyRequest[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MoneyRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MoneyRequest::class);
    }

    // /**
    //  * @return MoneyRequest[] Returns an array of MoneyRequest objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('m.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?MoneyRequest
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
