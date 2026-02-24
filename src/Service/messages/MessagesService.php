<?php

namespace App\Service\messages;

use App\Entity\messages\Messages;
use App\Repository\messages\MessagesRepository;
use App\Service\courriers\CourriersService;
use App\Service\utilisateurs\UtilisateursService;
use App\Service\utils\ValidationService;
use Exception;
use Doctrine\ORM\EntityManagerInterface;


use App\Service\utils\FichiersService;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MessagesService
{
    public function __construct(
        private readonly MessagesRepository $repo,
        private readonly UtilisateursService $utilisateursService,
        private readonly CourriersService $courriersService,
        private readonly ValidationService $validator,
        private readonly EntityManagerInterface $entityManager,
        private readonly FichiersService $fichiersService
    ) {
    }


    /**
     * Envoie un message concernant un courrier (avec upload optionnel de fichiers)
     * 
     * @param UploadedFile[] $files
     */
    public function envoyerMessage(int $expId, int $destId, int $courrierId, ?string $observation = null, array $files = []): Messages
    {
        return $this->entityManager->wrapInTransaction(function () use ($expId, $destId, $courrierId, $observation, $files) {
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
            $message->setObservation($observation);
            $message->setIsReadAt(null);

            $this->repo->save($message);

            // Persistance de chaque fichier lié
            foreach ($files as $file) {
                if ($file instanceof UploadedFile) {
                    $fichierEntity = $this->fichiersService->saveToBlob($file);
                    $fichierEntity->setMessage($message);
                    $this->entityManager->persist($fichierEntity);
                }
            }

            return $message;
        });
    }

    /**
     * Marque un message comme partagé (transféré)
     */
    public function marquerCommePartage(Messages $message): void
    {
        $message->setPartagerAt(new \DateTimeImmutable());
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
     * Marque un message comme non lu (réinitialise isReadAt à null)
     */
    public function marquerNonLu(int $messageId): void
    {
        $message = $this->repo->getById($messageId);
        $this->validator->throwIfNull($message, "Message avec l'ID $messageId introuvable.");

        $message->setIsReadAt(null);
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
     * Récupère les messages d'un utilisateur avec pagination et filtre de type
     *
     * @param string $type 'received' (défaut), 'sent', ou 'all'
     */
    public function getPaginatedMessages(int $userId, int $page = 1, int $limit = 20, string $type = 'all'): array
    {
        $paginator = $this->repo->findMessagesPaginated($userId, $page, $limit, $type);
        $totalItems = count($paginator);
        $lastPage = ceil($totalItems / $limit);

        $items = [];
        foreach ($paginator as $message) {
            $items[] = $message->toArray();
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

    /**
     * Récupère les messages associés à un courrier avec gestion des droits
     */
    public function getMessagesByCourrier(int $courrierId, int $userId): array
    {
        $courrier = $this->courriersService->getCourrierById($courrierId);
        $this->validator->throwIfNull($courrier, "Courrier avec l'ID $courrierId introuvable.");

        $isCreator = $courrier->getCreateur() && $courrier->getCreateur()->getId() === $userId;

        $messages = $this->repo->findByCourrierWithContext($courrierId, $userId, $isCreator);

        return array_map(fn(Messages $m) => $m->toArray(), $messages);
    }

    /**
     * Récupère le détail d'un message avec vérification des droits
     */
    public function getMessageDetail(int $messageId, int $userId): array
    {
        $message = $this->repo->getById($messageId);
        $this->validator->throwIfNull($message, "Message avec l'ID $messageId introuvable.");

        // Sécurité : Seul l'expéditeur ou le destinataire peut voir les détails
        if (
            $message->getExpediteur()->getId() !== $userId &&
            $message->getDestinataire()->getId() !== $userId
        ) {
            throw new \Exception("Vous n'êtes pas autorisé à consulter ce message.", 403);
        }

        return $message->toArray();
    }
}
