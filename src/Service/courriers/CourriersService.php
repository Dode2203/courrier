<?php

namespace App\Service\courriers;

use App\Entity\courriers\Courriers;
use App\Repository\courriers\CourriersRepository;
use App\Service\utils\ValidationService;
use App\Service\utils\FichiersService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;



class CourriersService
{
    public function __construct(
        private readonly CourriersRepository $repo,
        private readonly ValidationService $validator,
        private readonly EntityManagerInterface $entityManager,
        private readonly FichiersService $fichiersService
    ) {
    }



    /**
     * Génère une référence automatique au format JJMMAAAA/REFN
     */
    public function generateReference(): string
    {
        $date = new \DateTimeImmutable();
        $dateStr = $date->format('dmY');
        $count = $this->repo->countDailyCourriers($date);

        return $dateStr . '/REF' . ($count + 1);
    }

    /**
     * Récupère un courrier par son ID
     */
    public function getCourrierById(int $id): ?Courriers
    {
        return $this->repo->getById($id);
    }

    /**
     * Supprime logiquement un courrier
     */
    public function supprimerCourrier(int $id): void
    {
        $courrier = $this->getByIdRaw($id);
        $this->validator->throwIfNull($courrier, "Courrier avec l'id $id introuvable.");

        $courrier->delete();
        $this->repo->save($courrier);
    }

    /**
     * Récupère un courrier même s'il est supprimé (interne)
     */
    private function getByIdRaw(int $id): ?Courriers
    {
        return $this->repo->find($id);
    }

    /**
     * Clôture un courrier
     */
    public function cloturerCourrier(int $id): void
    {
        $courrier = $this->getCourrierById($id);
        $this->validator->throwIfNull($courrier, "Courrier avec l'id $id introuvable.");

        $courrier->setDateFin(new \DateTimeImmutable());
        $this->repo->save($courrier);
    }

    /**
     * Sauvegarde un courrier avec génération de référence si nécessaire
     * 
     * @param Courriers $courrier
     */
    public function save(Courriers $courrier): void
    {
        $this->validator->throwIfNull($courrier->getNom(), "Le nom du déposant est obligatoire.");

        // Transaction
        $this->entityManager->wrapInTransaction(function () use ($courrier) {
            if ($courrier->getReference() === null) {
                $courrier->setReference($this->generateReference());
            }

            // Normalisation des identités
            if ($courrier->getNom()) {
                $courrier->setNom(mb_strtoupper($courrier->getNom()));
            }

            if ($courrier->getPrenom()) {
                $courrier->setPrenom(mb_convert_case($courrier->getPrenom(), MB_CASE_TITLE));
            }

            $this->repo->save($courrier);
        });
    }

    /**
     * Recherche des courriers par nom ou prénom
     * @return Courriers[]
     */
    public function recherche(?string $nom, ?string $prenom): array
    {
        return $this->repo->searchByCriteria($nom, $prenom);
    }

    /**
     * Liste paginée des courriers
     */
    public function getAllPaginated(int $page, int $limit): array
    {
        $paginator = $this->repo->findAllPaginated($page, $limit);
        $totalItems = count($paginator);
        $lastPage = ceil($totalItems / $limit);

        $items = [];
        foreach ($paginator as $courrier) {
            $items[] = $courrier->toArray();
        }

        return [
            'items' => $items,
            'pagination' => [
                'total' => $totalItems,
                'page' => $page,
                'lastPage' => (int) $lastPage,
                'limit' => $limit
            ]
        ];
    }
}
