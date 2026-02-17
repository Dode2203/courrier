<?php

namespace App\Repository\utils;

use App\Entity\utils\Fichiers;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FichiersRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Fichiers::class);
    }

    // Exemples de méthodes personnalisées

    /**
     * Récupère tous les fichiers actifs (non supprimés)
     */
    public function findActiveFiles(): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.deletedAt IS NULL')
            ->orderBy('f.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les fichiers expirés
     */
    public function findExpiredFiles(): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.dateFin IS NOT NULL')
            ->andWhere('f.dateFin <= :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getResult();
    }
}
