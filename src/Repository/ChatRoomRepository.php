<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\ChatRoom;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @method ChatRoom|null find($id, $lockMode = null, $lockVersion = null)
 * @method ChatRoom|null findOneBy(array $criteria, array $orderBy = null)
 * @method ChatRoom[]    findAll()
 * @method ChatRoom[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ChatRoomRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChatRoom::class);
    }

    /**
     * @return ChatRoom[] Returns an array of ChatRoom objects
     */
    public function getMyChatRooms(User $user)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.user = :val')
            ->setParameter('val', $user)
            ->addOrderBy('c.isGenericDialog','DESC')
            ->addOrderBy('c.isOpen','DESC')
            ->addOrderBy('c.createdAt', 'ASC')
            ->setMaxResults(20)
            ->getQuery()
            ->getResult()
        ;
    }

    /*
    public function findOneBySomeField($value): ?ChatRoom
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
