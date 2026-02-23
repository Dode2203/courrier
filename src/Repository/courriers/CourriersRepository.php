<?php

namespace App\Repository\courriers;

use App\Entity\courriers\Courriers;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

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
            ->andWhere('c.deletedAt IS NULL')
            ->setParameter('ref', $reference)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getById(int $id): ?Courriers
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.id = :id')
            ->andWhere('c.deletedAt IS NULL')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countDailyCourriers(\DateTimeInterface $date): int
    {
        $start = \DateTimeImmutable::createFromInterface($date)->setTime(0, 0, 0);
        $end = $start->modify('+1 day');

        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.dateCreation >= :start')
            ->andWhere('c.dateCreation < :end')
            ->andWhere('c.deletedAt IS NULL')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function save(Courriers $courrier, bool $flush = true): void
    {
        $this->getEntityManager()->persist($courrier);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Recherche de courriers par nom et/ou prénom (insensible à la casse)
     * @return Courriers[]
     */
    public function searchByCriteria(?string $nom, ?string $prenom): array
    {
        $qb = $this->createQueryBuilder('c')
            ->andWhere('c.deletedAt IS NULL');

        if ($nom) {
            $qb->andWhere('LOWER(c.nom) LIKE :nom')
                ->setParameter('nom', '%' . mb_strtolower($nom) . '%');
        }

        if ($prenom) {
            $qb->andWhere('LOWER(c.prenom) LIKE :prenom')
                ->setParameter('prenom', '%' . mb_strtolower($prenom) . '%');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Paginator
     */
    public function findAllPaginated(int $page, int $limit): Paginator
    {
        $query = $this->createQueryBuilder('c')
            ->andWhere('c.deletedAt IS NULL')
            ->orderBy('c.dateCreation', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery();

        return new Paginator($query);
    }
}
