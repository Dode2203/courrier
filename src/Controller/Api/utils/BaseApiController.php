<?php

namespace App\Controller\Api\utils;

use App\Entity\utilisateurs\Utilisateurs;
use App\Service\utilisateurs\UtilisateursService;
use App\Service\utils\JwtTokenManager;
use App\Service\utils\ValidationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

abstract class BaseApiController extends AbstractController
{
    public function __construct(
        protected readonly JwtTokenManager $jwtManager,
        protected readonly UtilisateursService $utilisateursService,
        protected readonly ValidationService $validator
    ) {
    }

    /**
     * Récupère l'utilisateur à partir du token JWT présent dans la requête
     */
    protected function getUserFromRequest(Request $request): Utilisateurs
    {
        $token = $this->jwtManager->extractTokenFromRequest($request);
        if (!$token) {
            throw new AccessDeniedHttpException("Token manquant.");
        }

        $claims = $this->jwtManager->extractClaimsFromToken($token);
        if (!$claims || !isset($claims['id'])) {
            throw new AccessDeniedHttpException("Token invalide ou corrompu.");
        }

        $userId = (int) $claims['id'];
        $user = $this->utilisateursService->getUserById($userId);

        $this->validator->throwIfNull($user, "Utilisateur introuvable pour l'ID $userId.");

        return $user;
    }

    /**
     * Retourne une réponse JSON de succès
     */
    protected function jsonSuccess(mixed $data, int $code = 200): JsonResponse
    {
        return $this->json([
            'status' => 'success',
            'data' => $data
        ], $code);
    }

    /**
     * Retourne une réponse JSON d'erreur
     */
    protected function jsonError(string $message, int $code = 400): JsonResponse
    {
        return $this->json([
            'status' => 'error',
            'message' => $message
        ], $code);
    }
}
