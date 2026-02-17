<?php

namespace App\Repository\messages;

use App\Entity\messages\Messages;
use App\Entity\utilisateurs\Utilisateurs;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MessagesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Messages::class);
    }

    public function findUnreadByUser(Utilisateurs $user): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.destinataire = :user')
            ->andWhere('m.deletedAt IS NULL')
            ->setParameter('user', $user)
            ->orderBy('m.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findSentByUser(Utilisateurs $user): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.expediteur = :user')
            ->andWhere('m.deletedAt IS NULL')
            ->setParameter('user', $user)
            ->orderBy('m.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }
    public function findAllByUser(Utilisateurs $user): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.expediteur = :user OR m.destinataire = :user')
            ->andWhere('m.deletedAt IS NULL')
            ->setParameter('user', $user)
            ->orderBy('m.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

}
