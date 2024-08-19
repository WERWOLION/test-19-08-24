<?php

namespace App\Repository;

use App\Entity\BankOption;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method BankOption|null find($id, $lockMode = null, $lockVersion = null)
 * @method BankOption|null findOneBy(array $criteria, array $orderBy = null)
 * @method BankOption[]    findAll()
 * @method BankOption[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BankOptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BankOption::class);
    }

    // /**
    //  * @return BankOption[] Returns an array of BankOption objects
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
    public function findOneBySomeField($value): ?BankOption
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
