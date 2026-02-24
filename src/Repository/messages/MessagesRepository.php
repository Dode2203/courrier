<?php

namespace App\Repository\messages;

use App\Entity\messages\Messages;
use App\Entity\utilisateurs\Utilisateurs;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

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

    public function findByUtilisateur(int $userId, int $limit = 10, int $offset = 0, string $type = 'received'): array
    {
        $qb = $this->createQueryBuilder('m')
            ->andWhere('m.deletedAt IS NULL')
            ->setParameter('userId', $userId)
            ->orderBy('m.dateCreation', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        match ($type) {
            'sent' => $qb->andWhere('m.expediteur = :userId'),
            'all' => $qb->andWhere('m.destinataire = :userId OR m.expediteur = :userId'),
            default => $qb->andWhere('m.destinataire = :userId'), // 'received'
        };

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Paginator
     */
    public function findMessagesPaginated(int $userId, int $page, int $limit, string $type): Paginator
    {
        $qb = $this->createQueryBuilder('m')
            ->andWhere('m.deletedAt IS NULL')
            ->setParameter('userId', $userId)
            ->orderBy('m.dateCreation', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        match ($type) {
            'sent' => $qb->andWhere('m.expediteur = :userId'),
            'all' => $qb->andWhere('m.destinataire = :userId OR m.expediteur = :userId'),
            default => $qb->andWhere('m.destinataire = :userId'), // 'received'
        };

        return new Paginator($qb->getQuery());
    }

    /**
     * Récupère les messages d'un courrier avec gestion de la visibilité contextuelle
     */
    public function findByCourrierWithContext(int $courrierId, int $userId, bool $isCreator): array
    {
        $qb = $this->createQueryBuilder('m')
            ->andWhere('m.courrier = :courrierId')
            ->andWhere('m.deletedAt IS NULL')
            ->setParameter('courrierId', $courrierId)
            ->orderBy('m.dateCreation', 'ASC');

        if (!$isCreator) {
            $qb->andWhere('m.expediteur = :userId OR m.destinataire = :userId')
                ->setParameter('userId', $userId);
        }

        return $qb->getQuery()->getResult();
    }

    public function isUserInvolvedInCourrier(int $courrierId, int $userId): bool
    {
        $count = $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->andWhere('m.courrier = :courrierId')
            ->andWhere('m.expediteur = :userId OR m.destinataire = :userId')
            ->andWhere('m.deletedAt IS NULL')
            ->setParameter('courrierId', $courrierId)
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }
}
