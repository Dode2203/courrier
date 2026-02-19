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
     */
    public function save(Courriers $courrier, ?UploadedFile $file = null): void
    {
        $this->entityManager->wrapInTransaction(function () use ($courrier, $file) {
            if ($courrier->getReference() === null) {
                $courrier->setReference($this->generateReference());
            }

            if ($file) {
                $fichierEntity = $this->fichiersService->saveToBlob($file);
                $this->entityManager->persist($fichierEntity);
                $courrier->setFichier($fichierEntity);
            }

            $this->repo->save($courrier);
        });
    }

}
