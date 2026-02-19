<?php

namespace App\Service\messages;

use App\Entity\messages\Messages;
use App\Repository\messages\MessagesRepository;
use App\Service\courriers\CourriersService;
use App\Service\utilisateurs\UtilisateursService;
use App\Service\utils\ValidationService;
use Exception;

class MessagesService
{
    public function __construct(
        private readonly MessagesRepository $repo,
        private readonly UtilisateursService $utilisateursService,
        private readonly CourriersService $courriersService,
        private readonly ValidationService $validator
    ) {
    }

    /**
     * Envoie un message concernant un courrier
     */
    public function envoyerMessage(int $expId, int $destId, int $courrierId): void
    {
        $expediteur = $this->utilisateursService->getUserById($expId);
        $this->validator->throwIfNull($expediteur, "Expéditeur avec l'ID $expId introuvable.");

        $destinataire = $this->utilisateursService->getUserById($destId);
        $this->validator->throwIfNull($destinataire, "Destinataire avec l'ID $destId introuvable.");

        $courrier = $this->courriersService->getCourrierById($courrierId);
        $this->validator->throwIfNull($courrier, "Courrier avec l'ID $courrierId introuvable.");

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
        $this->validator->throwIfNull($message, "Message avec l'ID $messageId introuvable.");

        $message->setIsReadAt(new \DateTimeImmutable());
        $this->repo->save($message);
    }

    /**
     * Supprime logiquement un message
     */
    public function supprimerMessage(int $messageId): void
    {
        $message = $this->repo->getById($messageId);
        $this->validator->throwIfNull($message, "Message avec l'ID $messageId introuvable.");

        $message->delete(); // Méthode héritée de BaseEntite
        $this->repo->save($message);
    }

    /**
     * Récupère les messages reçus par un utilisateur avec pagination
     */
    public function getAllMessage(int $userId, int $page = 1, int $limit = 10): array
    {
        $offset = ($page - 1) * $limit;
        return $this->repo->findByUtilisateur($userId, $limit, $offset);
    }
}
