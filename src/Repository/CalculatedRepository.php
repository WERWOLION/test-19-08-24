<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\Calculated;
use App\Service\Filter\EntityQueryService;
use App\Service\Filter\RequestFilterDto;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @method Calculated|null find($id, $lockMode = null, $lockVersion = null)
 * @method Calculated|null findOneBy(array $criteria, array $orderBy = null)
 * @method Calculated[]    findAll()
 * @method Calculated[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CalculatedRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private EntityQueryService $entityQueryService,
    ){
        parent::__construct($registry, Calculated::class);
    }


    /**
     * @return Calculated[] Returns an array of Calculated objects
     */
    public function findMyCalculatedWithStatus(array $status, User $user)
    {
        return $this->createQueryBuilder('c')
            ->join('c.offer', 'ofr')
            ->andWhere('c.status IN (:status)')
            ->andWhere('ofr.user = :user')
            ->setParameter('status', $status)
            ->setParameter('user', $user)
            ->orderBy('c.updatedAt', 'DESC')
            ->setMaxResults(100)
            ->getQuery()
            ->getResult();
    }

    public function findMyNewCalcs(User $me)
    {
        return $this->createQueryBuilder('c')
            ->join('c.offer', 'ofr')
            ->andWhere('ofr.user = :user')
            ->andWhere('c.newEventType = 1')
            ->setParameter('user', $me)
            ->orderBy('c.updatedAt', 'DESC')
            ->setMaxResults(100)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return [] Возвращает список лотов с учетом фильтров
     */
    public function findMyFiltred(RequestFilterDto $filterDto, User $user, array $statuses): array
    {
        $legend = [
            'created' => [10],
            'sended' => [20,30,40,50],
            'accepted' => [60,70,80,90,100,110,115,120],
            'issued' => [130],
            'canceled' => [-30],
            'rejected' => [-10, -20],
        ];
        $needStatuses = [];
        foreach ($statuses as $st) {
            $needStatuses = array_merge($needStatuses, $legend[$st]);
        }
        $builder = $this->createQueryBuilder('e')
            ->join('e.offer', 'offer')
            ->join('offer.buyer', 'buyer')
            ->andWhere('offer.user = :user')
            ->andWhere('e.status != 0')
            ->setParameter('user', $user);

        if(count($needStatuses)) {
            $builder = $builder
                ->andWhere('e.status IN (:statuses)')
                ->setParameter('statuses', $needStatuses);
        }
        return $this->entityQueryService->filter($builder, $filterDto, $this->getEntityName());
    }

    /*
    public function findOneBySomeField($value): ?Calculated
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
