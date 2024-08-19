<?php

namespace App\Repository;

use App\Entity\BankCheck;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method BankCheck|null find($id, $lockMode = null, $lockVersion = null)
 * @method BankCheck|null findOneBy(array $criteria, array $orderBy = null)
 * @method BankCheck[]    findAll()
 * @method BankCheck[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BankCheckRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BankCheck::class);
    }

    // /**
    //  * @return BankCheck[] Returns an array of BankCheck objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('b.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?BankCheck
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
