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

    public function save(Messages $message, bool $flush = true): void
    {
        $this->getEntityManager()->persist($message);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getById(int $id): ?Messages
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.id = :id')
            ->andWhere('m.deletedAt IS NULL')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByUtilisateur(int $userId, int $limit = 10, int $offset = 0): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.destinataire = :userId')
            ->andWhere('m.deletedAt IS NULL')
            ->setParameter('userId', $userId)
            ->orderBy('m.dateCreation', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

}
