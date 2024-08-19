<?php

namespace App\Repository;

use App\Entity\PreUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PreUser|null find($id, $lockMode = null, $lockVersion = null)
 * @method PreUser|null findOneBy(array $criteria, array $orderBy = null)
 * @method PreUser[]    findAll()
 * @method PreUser[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PreUserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PreUser::class);
    }

    // /**
    //  * @return PreUser[] Returns an array of PreUser objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */


    public function findByPhone($phone)
    {
        $result = $this->createQueryBuilder('p')
            ->andWhere('p.phone = :phoneNum')
            ->andWhere('p.bitrixLeadId IS NOT NULL')
            ->andWhere('p.isConfirm != 1')
            ->setParameter('phoneNum', $phone)
            ->getQuery()
            ->getResult();
        ;
        return $result;
    }

    // Найти пре юзера по юзер id через поле Other
    public function findByOtherUserid($userId): PreUser
    {
        return $this->createQueryBuilder('p')
            ->where('p.other LIKE :userId')
            ->setParameter('userId', '%"user": ' . $userId . '%')
            ->getQuery()
            ->getSingleResult();
    }
}
