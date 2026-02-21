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
            // Vérifie l'utilisateur connecté
            $this->getUserFromRequest($request);

            // Pour multipart/form-data, on utilise $request->request et $request->files
            $data = $request->request->all();
            $file = $request->files->get('fichier');

            // Validation des champs requis
            $this->validator->validateRequiredFields($data, ['mail', 'description', 'object']);

            // Validation de la taille du fichier (5 Mo max)
            if ($file && $file->getSize() > 5 * 1024 * 1024) {
                throw new \Exception("Le fichier est trop volumineux (max 5 Mo).", 400);
            }

            $courrier = new Courriers();
            $courrier->setMail($data['mail'])
                ->setDescription($data['description'])
                ->setObject($data['object']);

            // Sauvegarde via le service (gère la référence automatique et le fichier)
            $this->courriersService->save($courrier, $file);

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

            // Pour multipart/form-data
            $data = $request->request->all();
            $file = $request->files->get('fichier');

            // Validation des champs requis (incluant destId)
            $this->validator->validateRequiredFields($data, ['mail', 'description', 'object', 'destId']);

            // Validation de la taille du fichier (5 Mo max)
            if ($file && $file->getSize() > 5 * 1024 * 1024) {
                throw new \Exception("Le fichier est trop volumineux (max 5 Mo).", 400);
            }

            $result = $this->entityManager->wrapInTransaction(function () use ($data, $user, $file) {
                // 1. Création du courrier
                $courrier = new Courriers();
                $courrier->setMail($data['mail'])
                    ->setDescription($data['description'])
                    ->setObject($data['object']);

                // Sauvegarde via le service (gère le fichier)
                $this->courriersService->save($courrier, $file);

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
            });''

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

    /**
     * Récupère le fichier associé à un courrier
     */
    #[Route('/{id}/fichier', name: 'api_courriers_fichier', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[TokenRequired]
    public function getFile(int $id, Request $request): Response
    {
        try {
            $this->getUserFromRequest($request);
            $courrier = $this->courriersService->getCourrierById($id);

            $this->validator->throwIfNull($courrier, "Courrier introuvable.");
            $fichier = $courrier->getFichier();

            if (!$fichier || !$fichier->getBinaire()) {
                throw new \Exception("Aucun fichier n'est associé à ce courrier.", 404);
            }

            $response = new Response(stream_get_contents($fichier->getBinaire()));
            $response->headers->set('Content-Type', $fichier->getType());
            $response->headers->set('Content-Disposition', 'inline; filename="' . $fichier->getNom() . '"');

            return $response;

        } catch (\Throwable $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ], $e->getCode() ?: 400);
        }
    }



}
