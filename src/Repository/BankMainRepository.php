<?php

namespace App\Repository;

use App\Entity\BankMain;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method BankMain|null find($id, $lockMode = null, $lockVersion = null)
 * @method BankMain|null findOneBy(array $criteria, array $orderBy = null)
 * @method BankMain[]    findAll()
 * @method BankMain[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BankMainRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BankMain::class);
    }

    // /**
    //  * @return BankMain[] Returns an array of BankMain objects
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
    public function findOneBySomeField($value): ?BankMain
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
