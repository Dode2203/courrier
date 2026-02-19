<?php

namespace App\Controller\Api\messages;

use App\Controller\Api\utils\BaseApiController;
use App\Service\messages\MessagesService;
use App\Service\utilisateurs\UtilisateursService;
use App\Service\utils\JwtTokenManager;
use App\Service\utils\ValidationService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Annotation\TokenRequired;

#[Route('/api/messages')]
class MessageController extends BaseApiController
{
    public function __construct(
        JwtTokenManager $jwtManager,
        UtilisateursService $utilisateursService,
        ValidationService $validator,
        private readonly MessagesService $messagesService
    ) {
        parent::__construct($jwtManager, $utilisateursService, $validator);
    }

    /**
     * Liste tous les messages reçus par l'utilisateur connecté
     */
    #[Route('', name: 'api_messages_list', methods: ['GET'])]
    #[TokenRequired]
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $this->getUserFromRequest($request);
            $messages = $this->messagesService->getAllMessage($user->getId());

            $data = array_map(fn($m) => $m->toArray(), $messages);

            return $this->jsonSuccess($data);
        } catch (\Throwable $e) {
            return $this->jsonError($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Transfère un courrier à un autre utilisateur (envoie un message)
     */
    #[Route('/transferer', name: 'api_messages_transferer', methods: ['POST'])]
    #[TokenRequired]
    public function transferer(Request $request): JsonResponse
    {
        try {
            $user = $this->getUserFromRequest($request);
            $data = json_decode($request->getContent(), true);

            $this->validator->validateRequiredFields($data, ['destId', 'courrierId']);

            $this->messagesService->envoyerMessage(
                $user->getId(),
                (int) $data['destId'],
                (int) $data['courrierId']
            );

            return $this->jsonSuccess(['message' => 'Courrier transféré avec succès.']);
        } catch (\Throwable $e) {
            return $this->jsonError($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Marque un message comme lu
     */
    #[Route('/{id}/lire', name: 'api_messages_lire', methods: ['PATCH'])]
    #[TokenRequired]
    public function lire(int $id, Request $request): JsonResponse
    {
        try {
            $this->getUserFromRequest($request);
            $this->messagesService->lireMessage($id);

            return $this->jsonSuccess(['message' => 'Message marqué comme lu.']);
        } catch (\Throwable $e) {
            return $this->jsonError($e->getMessage(), $e->getCode() ?: 400);
        }
    }
}
