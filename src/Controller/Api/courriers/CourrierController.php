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
            $this->getUserFromRequest($request);
            $data = $request->request->all();

            // Récupération souple : fichier unique ou tableau
            $files = $request->files->get('fichiers');
            if ($files && !is_array($files)) {
                $files = [$files];
            }
            $uploadedFiles = $files ?? [];

            $this->validator->validateRequiredFields($data, ['mail', 'description', 'object', 'nom', 'prenom']);

            $courrier = new Courriers();
            $courrier->setMail($data['mail'])
                ->setDescription($data['description'])
                ->setObject($data['object'])
                ->setNom($data['nom'])
                ->setPrenom($data['prenom']);

            $this->courriersService->save($courrier, $uploadedFiles);

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
    #[Route('/creerTransferer', name: 'api_courriers_creer_transferer', methods: ['POST'])]
    #[TokenRequired]
    public function creerEtTransferer(Request $request): JsonResponse
    {
        try {
            $user = $this->getUserFromRequest($request);
            $data = $request->request->all();

            $files = $request->files->get('fichiers');
            if ($files && !is_array($files)) {
                $files = [$files];
            }
            $uploadedFiles = $files ?? [];

            $this->validator->validateRequiredFields($data, ['mail', 'description', 'object', 'destId', 'nom', 'prenom']);

            $result = $this->entityManager->wrapInTransaction(function () use ($data, $user, $uploadedFiles) {
                $courrier = new Courriers();
                $courrier->setMail($data['mail'])
                    ->setDescription($data['description'])
                    ->setObject($data['object'])
                    ->setNom($data['nom'])
                    ->setPrenom($data['prenom']);

                $this->courriersService->save($courrier, $uploadedFiles);

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
            $this->getUserFromRequest($request);
            $courrier = $this->courriersService->getCourrierById($id);
            $this->validator->throwIfNull($courrier, "Courrier avec l'ID $id introuvable.");

            return $this->jsonSuccess($courrier->toArray());
        } catch (\Throwable $e) {
            return $this->jsonError($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Liste les métadonnées des fichiers attachés à un courrier
     */
    #[Route('/{id}/fichiers', name: 'api_courriers_fichiers_list', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[TokenRequired]
    public function listFichiers(int $id, Request $request): JsonResponse
    {
        try {
            $this->getUserFromRequest($request);
            $courrier = $this->courriersService->getCourrierById($id);
            $this->validator->throwIfNull($courrier, "Courrier introuvable.");

            $fichiers = $courrier->getFichiers()->map(fn($f) => [
                'id' => $f->getId(),
                'nom' => $f->getNom(),
                'type' => $f->getType(),
                'dateCreation' => $f->getDateCreation() ? $f->getDateCreation()->format('Y-m-d H:i:s') : null,
            ])->toArray();

            return $this->jsonSuccess(['fichiers' => $fichiers]);
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



    /**
     * Supprime logiquement un courrier (Soft Delete)
     */
    #[Route('/{id}', name: 'api_courriers_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[TokenRequired]
    public function delete(int $id, Request $request): JsonResponse
    {
        try {
            $this->getUserFromRequest($request);
            $this->courriersService->supprimerCourrier($id);

            return $this->jsonSuccess(['message' => 'Courrier supprimé avec succès.']);
        } catch (\Throwable $e) {
            return $this->jsonError($e->getMessage(), $e->getCode() ?: 400);
        }
    }
}
