<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\Offer;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @method Offer|null find($id, $lockMode = null, $lockVersion = null)
 * @method Offer|null findOneBy(array $criteria, array $orderBy = null)
 * @method Offer[]    findAll()
 * @method Offer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OfferRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Offer::class);
    }

    /**
     * @return Offer[] Возвращает заявки по номеру телефона заёмщика
     */
    public function findByBuyerPhone(User $user, string $phone)
    {
        return $this->createQueryBuilder('o')
            ->join('o.buyer', 'byuer')
            ->andWhere('o.user = :user')
            ->andWhere('byuer.phone = :phone')
            ->setParameter('user', $user)
            ->setParameter('phone', $phone)
            ->orderBy('o.id', 'ASC')
            ->setMaxResults(200)
            ->getQuery()
            ->getResult()
        ;
    }

    /*
    public function findOneBySomeField($value): ?Offer
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
