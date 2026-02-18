<?php

namespace App\Service\courriers;

use App\Entity\courriers\Courriers;
use App\Repository\courriers\CourriersRepository;

class CourriersService
{
    public function __construct(
        private readonly CourriersRepository $repo
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
     * Sauvegarde un courrier avec génération de référence si nécessaire
     */
    public function save(Courriers $courrier): void
    {
        if ($courrier->getReference() === null) {
            $courrier->setReference($this->generateReference());
        }

        $this->repo->save($courrier);
    }
}
