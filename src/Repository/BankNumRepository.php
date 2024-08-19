<?php

namespace App\Repository;

use App\Entity\BankNum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method BankNum|null find($id, $lockMode = null, $lockVersion = null)
 * @method BankNum|null findOneBy(array $criteria, array $orderBy = null)
 * @method BankNum[]    findAll()
 * @method BankNum[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BankNumRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BankNum::class);
    }

    // /**
    //  * @return BankNum[] Returns an array of BankNum objects
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
    public function findOneBySomeField($value): ?BankNum
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
