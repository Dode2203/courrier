<?php

namespace App\Service\utils;

use App\Entity\courriers\Courriers;
use App\Entity\messages\Messages;
use App\Entity\utilisateurs\Utilisateurs;
use App\Repository\messages\MessagesRepository;

class SecurityService
{
    public function __construct(
        private readonly MessagesRepository $messagesRepository
    ) {
    }

    /**
     * Vérifie si un utilisateur a accès à une entité (Courrier ou Message)
     */
    public function canAccess(Utilisateurs $user, mixed $entity): bool
    {
        $userId = $user->getId();

        if ($entity instanceof Courriers) {
            // Accès si créateur
            if ($entity->getCreateur() && $entity->getCreateur()->getId() === $userId) {
                return true;
            }

            // Accès si impliqué dans un message (expéditeur ou destinataire)
            return $this->messagesRepository->isUserInvolvedInCourrier($entity->getId(), $userId);
        }

        if ($entity instanceof Messages) {
            // Accès si expéditeur ou destinataire
            return $entity->getExpediteur()->getId() === $userId ||
                $entity->getDestinataire()->getId() === $userId;
        }

        return false;
    }
}
