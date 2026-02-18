<?php

namespace App\Service\messages;

use App\Entity\messages\Messages;
use App\Repository\messages\MessagesRepository;
use App\Service\courriers\CourriersService;
use App\Service\utilisateurs\UtilisateursService;
use Exception;

class MessagesService
{
    public function __construct(
        private readonly MessagesRepository $repo,
        private readonly UtilisateursService $utilisateursService,
        private readonly CourriersService $courriersService
    ) {
    }

    /**
     * Envoie un message concernant un courrier
     */
    public function envoyerMessage(int $expId, int $destId, int $courrierId): void
    {
        $expediteur = $this->utilisateursService->getUserById($expId);
        $destinataire = $this->utilisateursService->getUserById($destId);
        $courrier = $this->courriersService->getCourrierById($courrierId);

        if (!$expediteur || !$destinataire || !$courrier) {
            throw new Exception("L'expéditeur, le destinataire ou le courrier est introuvable.");
        }

        $message = new Messages();
        $message->setExpediteur($expediteur);
        $message->setDestinataire($destinataire);
        $message->setCourrier($courrier);
        $message->setIsReadAt(null);

        $this->repo->save($message);
    }

    /**
     * Marque un message comme lu
     */
    public function lireMessage(int $messageId): void
    {
        $message = $this->repo->getById($messageId);
        if (!$message) {
            throw new Exception("Message introuvable.");
        }

        $message->setIsReadAt(new \DateTimeImmutable());
        $this->repo->save($message);
    }

    /**
     * Supprime logiquement un message
     */
    public function supprimerMessage(int $messageId): void
    {
        $message = $this->repo->getById($messageId);
        if (!$message) {
            throw new Exception("Message introuvable.");
        }

        $message->delete(); // Méthode héritée de BaseEntite
        $this->repo->save($message);
    }

    /**
     * Récupère tous les messages reçus par un utilisateur
     */
    public function getAllMessage(int $userId): array
    {
        return $this->repo->findByUtilisateur($userId);
    }
}
