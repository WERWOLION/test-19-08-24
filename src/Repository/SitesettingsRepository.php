<?php

namespace App\Repository;

use App\Entity\Sitesettings;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Sitesettings|null find($id, $lockMode = null, $lockVersion = null)
 * @method Sitesettings|null findOneBy(array $criteria, array $orderBy = null)
 * @method Sitesettings[]    findAll()
 * @method Sitesettings[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SitesettingsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sitesettings::class);
    }

    public function findSitesettingByLabel($label): ?Sitesettings
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.label = :val')
            ->setParameter('val', $label)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
