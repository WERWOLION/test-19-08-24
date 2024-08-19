<?php

namespace App\Repository;

use App\Entity\Savedcontact;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Savedcontact|null find($id, $lockMode = null, $lockVersion = null)
 * @method Savedcontact|null findOneBy(array $criteria, array $orderBy = null)
 * @method Savedcontact[]    findAll()
 * @method Savedcontact[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SavedcontactRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Savedcontact::class);
    }

    // /**
    //  * @return Savedcontact[] Returns an array of Savedcontact objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Savedcontact
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
