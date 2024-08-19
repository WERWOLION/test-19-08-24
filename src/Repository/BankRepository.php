<?php

namespace App\Repository;

use App\Entity\Bank;
use App\Entity\Offer;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @method Bank|null find($id, $lockMode = null, $lockVersion = null)
 * @method Bank|null findOneBy(array $criteria, array $orderBy = null)
 * @method Bank[]    findAll()
 * @method Bank[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BankRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Bank::class);
    }

    // /**
    //  * @return Bank[] Returns an array of Bank objects
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

    public function findResultBanks(Offer $offer)
    {
        return $this->createQueryBuilder('b')
            ->join('b.towns', 'twn')
            ->andWhere('twn = :town')
            ->andWhere('b = :val')
            ->setParameter('town', $offer->getTown())
            ->getQuery()
            ->getResult()
        ;
    }
}
