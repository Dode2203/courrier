<?php

namespace App\Controller\Api\messages;

use App\Controller\Api\utils\BaseApiController;
use App\Service\messages\MessagesService;
use App\Service\utils\ApiResponseService;
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
        ApiResponseService $responseService,
        private readonly MessagesService $messagesService
    ) {
        parent::__construct($jwtManager, $utilisateursService, $validator, $responseService);
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

            $page = (int) $request->query->get('page', 1);
            $limit = (int) $request->query->get('limit', 20);
            $type = $request->query->get('type', 'all'); // 'received' | 'sent' | 'all'

            $result = $this->messagesService->getPaginatedMessages($user->getId(), $page, $limit, $type);

            return $this->jsonSuccess(
                data: $result['items'],
                message: "Messages récupérés avec succès.",
                extras: $result['pagination']
            );
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

            // On supporte maintenant multipart/form-data pour les fichiers
            $data = $request->request->all();
            $uploadedFiles = $request->files->get('fichiers', []);

            $this->validator->validateRequiredFields($data, ['destId', 'courrierId']);

            $message = $this->messagesService->envoyerMessage(
                expId: $user->getId(),
                destId: (int) $data['destId'],
                courrierId: (int) $data['courrierId'],
                observation: $data['observation'] ?? null,
                files: is_array($uploadedFiles) ? $uploadedFiles : [$uploadedFiles]
            );

            $this->messagesService->marquerCommePartage($message);

            return $this->jsonSuccess(
                data: null,
                message: 'Courrier transféré avec succès.'
            );
        } catch (\Throwable $e) {
            return $this->jsonError($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Marque un message comme lu
     */
    #[Route('/{id}/lire', name: 'api_messages_lire', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    #[TokenRequired]
    public function lire(int $id, Request $request): JsonResponse
    {
        try {
            $this->getUserFromRequest($request);
            $this->messagesService->lireMessage($id);

            return $this->jsonSuccess(
                data: null,
                message: 'Message marqué comme lu.'
            );
        } catch (\Throwable $e) {
            return $this->jsonError($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Marque un message comme non lu (réinitialise isReadAt à null)
     */
    #[Route('/{id}/non-lu', name: 'api_messages_non_lu', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    #[TokenRequired]
    public function nonLu(int $id, Request $request): JsonResponse
    {
        try {
            $this->getUserFromRequest($request);
            $this->messagesService->marquerNonLu($id);

            return $this->jsonSuccess(
                data: null,
                message: 'Message marqué comme non lu.'
            );
        } catch (\Throwable $e) {
            return $this->jsonError($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Supprime logiquement un message (Soft Delete)
     */
    #[Route('/{id}', name: 'api_messages_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[TokenRequired]
    public function delete(int $id, Request $request): JsonResponse
    {
        try {
            $this->getUserFromRequest($request);
            $this->messagesService->supprimerMessage($id);

            return $this->jsonSuccess(
                data: null,
                message: 'Message supprimé avec succès.'
            );
        } catch (\Throwable $e) {
            return $this->jsonError($e->getMessage(), $e->getCode() ?: 400);
        }
    }
}
