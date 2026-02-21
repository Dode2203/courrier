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
     * Sauvegarde un courrier avec génération de référence si nécessaire et multi-fichiers
     * 
     * @param Courriers $courrier
     * @param UploadedFile[] $files
     */
    public function save(Courriers $courrier, array $files = []): void
    {
        if (!$courrier->getNom() || !$courrier->getPrenom()) {
            throw new \Exception("Le nom et le prénom du déposant sont obligatoires.", 400);
        }

        // 1. Validation pré-transaction (Atomicité métier)
        foreach ($files as $file) {
            if ($file && $file->getSize() > 5 * 1024 * 1024) {
                throw new \Exception("Le fichier '" . $file->getClientOriginalName() . "' est trop volumineux (max 5 Mo).", 400);
            }
        }

        // 2. Transaction
        $this->entityManager->wrapInTransaction(function () use ($courrier, $files) {
            if ($courrier->getReference() === null) {
                $courrier->setReference($this->generateReference());
            }

            // Persistance du courrier d'abord
            $this->repo->save($courrier);

            // Persistance de chaque fichier lié
            foreach ($files as $file) {
                if ($file instanceof UploadedFile) {
                    $fichierEntity = $this->fichiersService->saveToBlob($file);
                    $fichierEntity->setCourrier($courrier);
                    $this->entityManager->persist($fichierEntity);
                }
            }
        });
    }

}
