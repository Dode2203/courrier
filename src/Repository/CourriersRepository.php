<?php

namespace App\Repository;

use App\Entity\Courriers;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CourriersRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Courriers::class);
    }


    public function findByReference(string $reference): ?Courriers
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.reference = :ref')
            ->setParameter('ref', $reference)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
