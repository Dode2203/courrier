<?php

namespace App\Repository\utilisateurs;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use App\Entity\utilisateurs\Utilisateurs;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Utilisateurs>
 */
class UtilisateursRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Utilisateurs::class);
    }

    //    /**
    //     * @return Utilisateur[] Returns an array of Utilisateur objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('u.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Utilisateur
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
    public function login(string $email, string $plainPassword): ?Utilisateurs
    {
        $user = $this->findOneBy(['email' => $email]);

        if ($user && password_verify($plainPassword, $user->getMdp())) {
            return $user;
        }

        return null; 
    }
    public function getAllParOrdre(): array
       {
           return $this->createQueryBuilder('u')
               
               ->orderBy('u.dateCreation', 'ASC')
               ->getQuery()
               ->getResult()
           ;
       }
    

}
