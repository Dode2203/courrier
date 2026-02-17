<?php

namespace App\EventListener;

use App\Service\utils\JwtTokenManager;
use App\Annotation\TokenRequired;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Common\Annotations\AnnotationReader;
use Lcobucci\JWT\Token\Plain;

class TokenListener
{
    private $jwtTokenManager;
    private $reader;

    public function __construct(JwtTokenManager $jwtTokenManager)
    {
        $this->jwtTokenManager = $jwtTokenManager;
        $this->reader = new AnnotationReader(); // Initialisez le reader ici
    }
    // Dans votre listener ou service
    private function checkRoles(array $tokenRoles, array $requiredRoles): bool
    {
        // Vérifie si les rôles requis sont présents dans les rôles du token
        return !empty($requiredRoles) && !empty(array_intersect($requiredRoles, $tokenRoles));
    }


    public function onKernelController(ControllerEvent $event): void
    {
        $controller = $event->getController();

        // Vérifier si le contrôleur est un tableau [ControllerObject, method]
        if (!is_array($controller)) {
            return;
        }

        $reflectionObject = new \ReflectionObject($controller[0]);
        $reflectionMethod = $reflectionObject->getMethod($controller[1]);

        // Récupérer les attributs TokenRequired
        $attributes = $reflectionMethod->getAttributes(TokenRequired::class);

        if (empty($attributes)) {
            return; // Pas besoin de token
        }

        $request = $event->getRequest();

        // Extraire le token
        $tokenString = $this->jwtTokenManager->extractTokenFromRequest($request);

        if (!$tokenString) {
            $event->setController(fn() => new JsonResponse(
                ['error' => 'Token is required'], 
                Response::HTTP_UNAUTHORIZED
            ));
            return;
        }

        // Parser et valider le token
        $parsedToken = $this->jwtTokenManager->parseToken($tokenString);

        if (!$parsedToken instanceof Plain || !$this->jwtTokenManager->validateToken($parsedToken)) {
            $event->setController(fn() => new JsonResponse(
                ['error' => 'Invalid or expired token'], 
                Response::HTTP_UNAUTHORIZED
            ));
            return;
        }

        // Vérifier les rôles
        $tokenRoles = $parsedToken->claims()->get('role', []);
        if (!is_array($tokenRoles)) {
            $tokenRoles = [$tokenRoles];
        }

        /** @var TokenRequired $tokenRequiredInstance */
        $tokenRequiredInstance = $attributes[0]->newInstance();
        $requiredRoles = $tokenRequiredInstance->getRoles();

        if (!empty($requiredRoles) && !$this->checkRoles($tokenRoles, $requiredRoles)) {
            $event->setController(fn() => new JsonResponse(
                ['error' => 'Access denied: insufficient roles'], 
                Response::HTTP_FORBIDDEN
            ));
            return;
        }

        // Si tout est ok, le contrôleur original s’exécute normalement
    }


}
