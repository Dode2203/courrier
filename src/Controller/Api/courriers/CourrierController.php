<?php

namespace App\Controller\Api\courriers;

use App\Controller\Api\utils\BaseApiController;
use App\Entity\courriers\Courriers;
use App\Service\courriers\CourriersService;
use App\Service\messages\MailService;
use App\Service\utilisateurs\UtilisateursService;
use App\Service\utils\JwtTokenManager;
use App\Service\utils\ValidationService;
use App\Service\messages\MessagesService;
use Doctrine\ORM\EntityManagerInterface;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Annotation\TokenRequired;

#[Route('/api/courriers')]
class CourrierController extends BaseApiController
{
    public function __construct(
        JwtTokenManager $jwtManager,
        UtilisateursService $utilisateursService,
        ValidationService $validator,
        private readonly CourriersService $courriersService,
        private readonly MailService $mailService,
        private readonly MessagesService $messagesService,
        private readonly EntityManagerInterface $entityManager
    ) {
        parent::__construct($jwtManager, $utilisateursService, $validator);
    }


    #[Route('/creer', name: 'api_courriers_creer', methods: ['POST'])]
    #[TokenRequired]
    public function creer(Request $request): JsonResponse
    {
        try {
            // Vérifie l'utilisateur connecté
            $this->getUserFromRequest($request);

            $data = json_decode($request->getContent(), true);

            // Validation des champs requis
            $this->validator->validateRequiredFields($data, ['mail', 'description', 'object']);

            $courrier = new Courriers();
            $courrier->setMail($data['mail'])
                ->setDescription($data['description'])
                ->setObject($data['object']);

            // Sauvegarde via le service (gère la référence automatique)
            $this->courriersService->save($courrier);

            return $this->jsonSuccess([
                'id' => $courrier->getId(),
                'reference' => $courrier->getReference()
            ], 201);

        } catch (\Throwable $e) {
            return $this->jsonError($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Crée un courrier et le transfère immédiatement à un destinataire
     */
    #[Route('/creer-et-transferer', name: 'api_courriers_creer_transferer', methods: ['POST'])]
    #[TokenRequired]
    public function creerEtTransferer(Request $request): JsonResponse
    {
        try {
            $user = $this->getUserFromRequest($request);
            $data = json_decode($request->getContent(), true);

            // Validation des champs requis (incluant destId)
            $this->validator->validateRequiredFields($data, ['mail', 'description', 'object', 'destId']);

            $result = $this->entityManager->wrapInTransaction(function () use ($data, $user) {
                // 1. Création du courrier
                $courrier = new Courriers();
                $courrier->setMail($data['mail'])
                    ->setDescription($data['description'])
                    ->setObject($data['object']);

                $this->courriersService->save($courrier);

                // 2. Transfert immédiat
                $this->messagesService->envoyerMessage(
                    $user->getId(),
                    (int) $data['destId'],
                    $courrier->getId()
                );

                return [
                    'id' => $courrier->getId(),
                    'reference' => $courrier->getReference()
                ];
            });

            return $this->jsonSuccess($result, 201);

        } catch (\Throwable $e) {
            return $this->jsonError($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    #[Route('/{id}', name: 'api_courriers_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[TokenRequired]
    public function show(int $id, Request $request): JsonResponse
    {
        try {
            // Vérifie l'utilisateur connecté
            $this->getUserFromRequest($request);

            // Récupération via le service
            $courrier = $this->courriersService->getCourrierById($id);

            // Validation existence
            $this->validator->throwIfNull($courrier, "Courrier avec l'ID $id introuvable.");

            return $this->jsonSuccess($courrier->toArray());

        } catch (\Throwable $e) {
            return $this->jsonError($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    #[Route('/{id}/cloturer', name: 'api_courriers_cloturer', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[TokenRequired]
    public function cloturer(int $id, Request $request): JsonResponse
    {
        try {
            // Vérifie l'utilisateur connecté
            $this->getUserFromRequest($request);

            // Envoi du mail et clôture via le service spécialisé
            $this->mailService->envoyerMail($id);

            return $this->jsonSuccess(['message' => "Le dossier a été clôturé et l'étudiant a été notifié par mail."]);

        } catch (\Throwable $e) {
            return $this->jsonError($e->getMessage(), $e->getCode() ?: 400);
        }
    }


}
