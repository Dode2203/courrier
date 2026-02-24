<?php

namespace App\Controller\Api\utils;

use App\Entity\utilisateurs\Utilisateurs;
use App\Service\utilisateurs\UtilisateursService;
use App\Service\utils\ApiResponseService;
use App\Service\utils\JwtTokenManager;
use App\Service\utils\ValidationService;
use App\Service\utils\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

abstract class BaseApiController extends AbstractController
{
    public function __construct(
        protected readonly JwtTokenManager $jwtManager,
        protected readonly UtilisateursService $utilisateursService,
        protected readonly ValidationService $validator,
        protected readonly ApiResponseService $responseService,
        protected readonly SecurityService $securityService
    ) {
    }

    /**
     * Vérifie l'accès d'un utilisateur à une entité
     */
    protected function checkAccess(mixed $entity, Utilisateurs $user): void
    {
        if (!$this->securityService->canAccess($user, $entity)) {
            throw new AccessDeniedHttpException("Accès refusé.");
        }
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
     * Retourne une réponse JSON de succès standardisée
     */
    protected function jsonSuccess(mixed $data = null, string $message = "Success", array $extras = [], int $code = 200): JsonResponse
    {
        return $this->json(
            $this->responseService->format(true, $message, $data, $extras),
            $code
        );
    }

    /**
     * Retourne une réponse JSON d'erreur standardisée
     */
    protected function jsonError(string $message, int $code = 400): JsonResponse
    {
        return $this->json(
            $this->responseService->format(false, $message),
            $code
        );
    }
}
