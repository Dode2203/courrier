<?php

namespace App\Controller\Api\utils;

use App\Repository\utils\FichiersRepository;
use App\Service\utilisateurs\UtilisateursService;
use App\Service\utils\JwtTokenManager;
use App\Service\utils\ValidationService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Annotation\TokenRequired;

#[Route('/api/fichiers')]
class FichierController extends BaseApiController
{
    public function __construct(
        JwtTokenManager $jwtManager,
        UtilisateursService $utilisateursService,
        ValidationService $validator,
        private readonly FichiersRepository $fichiersRepo
    ) {
        parent::__construct($jwtManager, $utilisateursService, $validator);
    }

    /**
     * Télécharge le binaire d'un fichier spécifique par son ID
     */
    #[Route('/{id}/download', name: 'api_fichiers_download', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[TokenRequired]
    public function download(int $id, Request $request): Response
    {
        try {
            $this->getUserFromRequest($request);
            $fichier = $this->fichiersRepo->find($id);

            if (!$fichier || !$fichier->getBinaire()) {
                throw new \Exception("Fichier introuvable.", 404);
            }

            // Correction pour stream_get_contents si c'est une ressource
            $content = is_resource($fichier->getBinaire()) ? stream_get_contents($fichier->getBinaire()) : $fichier->getBinaire();

            $response = new Response($content);
            $response->headers->set('Content-Type', $fichier->getType());
            $response->headers->set('Content-Disposition', 'attachment; filename="' . $fichier->getNom() . '"');

            return $response;

        } catch (\Throwable $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ], $e->getCode() ?: 400);
        }
    }
}
