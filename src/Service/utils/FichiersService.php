<?php

namespace App\Service\utils;

use App\Entity\utils\Fichiers;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FichiersService
{
    /**
     * Convertit un fichier uploadé en entité Fichiers avec stockage BLOB
     */
    public function saveToBlob(UploadedFile $file): Fichiers
    {
        $fichier = new Fichiers();

        // Lecture du contenu binaire
        $binaryContent = file_get_contents($file->getPathname());

        $fichier->setNom($file->getClientOriginalName())
            ->setType($file->getMimeType())
            ->setBinaire($binaryContent);

        return $fichier;
    }
}
