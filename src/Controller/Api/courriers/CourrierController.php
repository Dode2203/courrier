<?php

namespace App\Controller\Api\courriers;

use App\Controller\Api\utils\BaseApiController;
use App\Entity\courriers\Courriers;
use App\Service\courriers\CourriersService;
use App\Service\messages\MailService;
use App\Service\utilisateurs\UtilisateursService;
use App\Service\utils\JwtTokenManager;
use App\Service\utils\ValidationService;
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
        private readonly MailService $mailService
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

    #[Route('/{id}', name: 'api_courriers_show', methods: ['GET'])]
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

    #[Route('/{id}/cloturer', name: 'api_courriers_cloturer', methods: ['POST'])]
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
