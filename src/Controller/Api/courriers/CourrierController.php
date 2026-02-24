<?php

namespace App\Controller\Api\courriers;

use App\Controller\Api\utils\BaseApiController;
use App\Entity\courriers\Courriers;
use App\Service\courriers\CourriersService;
use App\Service\utils\ApiResponseService;
use App\Service\messages\MailService;
use App\Service\utilisateurs\UtilisateursService;
use App\Service\utils\JwtTokenManager;
use App\Service\utils\ValidationService;
use App\Service\messages\MessagesService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;


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
        ApiResponseService $responseService,
        private readonly CourriersService $courriersService,
        private readonly MailService $mailService,
        private readonly MessagesService $messagesService,
        private readonly EntityManagerInterface $entityManager
    ) {
        parent::__construct($jwtManager, $utilisateursService, $validator, $responseService);
    }

    #[Route('', name: 'api_courriers_list', methods: ['GET'])]
    #[TokenRequired]
    public function index(Request $request): JsonResponse
    {
        try {
            $this->getUserFromRequest($request);

            $page = (int) $request->query->get('page', 1);
            $limit = (int) $request->query->get('limit', 20);

            $result = $this->courriersService->getAllPaginated($page, $limit);

            return $this->jsonSuccess(
                data: $result['items'],
                message: "Liste des courriers récupérée avec succès.",
                extras: $result['pagination']
            );
        } catch (\Throwable $e) {
            return $this->jsonError($e->getMessage(), $e->getCode() ?: 400);
        }
    }


    #[Route('/creer', name: 'api_courriers_creer', methods: ['POST'])]
    #[TokenRequired]
    public function creer(Request $request): JsonResponse
    {
        try {
            $this->getUserFromRequest($request);
            $data = $request->request->all();

            $this->validator->validateRequiredFields($data, ['mail', 'description', 'object', 'nom']);

            $courrier = new Courriers();
            $courrier->setMail($data['mail'])
                ->setDescription($data['description'])
                ->setObject($data['object'])
                ->setNom($data['nom'])
                ->setPrenom($data['prenom'] ?? null)
                ->setTelephone($data['telephone'] ?? null);

            $this->courriersService->save($courrier);

            return $this->jsonSuccess(
                data: [
                    'id' => $courrier->getId(),
                    'reference' => $courrier->getReference()
                ],
                message: 'Courrier créé avec succès.',
                code: 201
            );

        } catch (\Throwable $e) {
            return $this->jsonError($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Crée un courrier et le transfère immédiatement à un destinataire
     */
    #[Route('/creerTransferer', name: 'api_courriers_creer_transferer', methods: ['POST'])]
    #[TokenRequired]
    public function creerEtTransferer(Request $request): JsonResponse
    {
        try {
            $user = $this->getUserFromRequest($request);
            $data = $request->request->all();

            // Récupération souple : fichier unique ou tableau
            $files = $request->files->get('fichiers');
            if ($files && !is_array($files)) {
                $files = [$files];
            }
            $uploadedFiles = $files ?? [];

            $this->validator->validateRequiredFields($data, ['mail', 'description', 'object', 'destId', 'nom']);

            $this->entityManager->beginTransaction();
            try {
                $courrier = new Courriers();
                $courrier->setMail($data['mail'])
                    ->setDescription($data['description'])
                    ->setObject($data['object'])
                    ->setNom($data['nom'])
                    ->setPrenom($data['prenom'] ?? null)
                    ->setTelephone($data['telephone'] ?? null);

                $this->courriersService->save($courrier);

                $this->messagesService->envoyerMessage(
                    expId: $user->getId(),
                    destId: (int) $data['destId'],
                    courrierId: $courrier->getId(),
                    observation: $data['observation'] ?? null,
                    files: $uploadedFiles
                );

                $this->entityManager->commit();

                return $this->jsonSuccess(
                    data: [
                        'id' => $courrier->getId(),
                        'reference' => $courrier->getReference()
                    ],
                    message: "Courrier créé et transféré avec succès.",
                    code: 201
                );
            } catch (\Throwable $e) {
                $this->entityManager->rollback();
                throw $e;
            }
        } catch (\Throwable $e) {
            return $this->jsonError($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    #[Route('/recherche', name: 'api_courriers_recherche', methods: ['GET'])]
    #[TokenRequired]
    public function recherche(Request $request): JsonResponse
    {
        try {
            $this->getUserFromRequest($request);
            $nom = $request->query->get('nom');
            $prenom = $request->query->get('prenom');

            if (!$nom && !$prenom) {
                return $this->jsonError("Veuillez fournir au moins un critère de recherche (nom ou prénom).", 400);
            }

            $courriers = $this->courriersService->recherche($nom, $prenom);
            $items = array_map(fn(Courriers $c) => $c->toArray(), $courriers);

            return $this->jsonSuccess(
                data: $items,
                message: "Résultats de recherche récupérés."
            );
        } catch (\Throwable $e) {
            return $this->jsonError($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    #[Route('/{id}', name: 'api_courriers_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[TokenRequired]
    public function show(int $id, Request $request): JsonResponse
    {
        try {
            $this->getUserFromRequest($request);
            $courrier = $this->courriersService->getCourrierById($id);
            $this->validator->throwIfNull($courrier, "Courrier avec l'ID $id introuvable.");

            return $this->jsonSuccess(
                data: $courrier->toArray(),
                message: "Détails du courrier récupérés."
            );
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

            return $this->jsonSuccess(
                data: null,
                message: "Le dossier a été clôturé et l'étudiant a été notifié par mail."
            );

        } catch (\Throwable $e) {
            return $this->jsonError($e->getMessage(), $e->getCode() ?: 400);
        }
    }


    #[Route('/{id}', name: 'api_courriers_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[TokenRequired]
    public function delete(int $id, Request $request): JsonResponse
    {
        try {
            $this->getUserFromRequest($request);
            $this->courriersService->supprimerCourrier($id);

            return $this->jsonSuccess(
                data: null,
                message: 'Courrier supprimé avec succès.'
            );
        } catch (\Throwable $e) {
            return $this->jsonError($e->getMessage(), $e->getCode() ?: 400);
        }
    }
}
