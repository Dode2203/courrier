<?php

namespace App\Controller\Api\utilisateurs;


use App\Entity\utilisateurs\Utilisateurs;
use App\Service\utils\JwtTokenManager;
use App\Service\utilisateurs\UtilisateursService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Annotation\TokenRequired;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[Route('/utilisateur')]
class UtilisateurController extends AbstractController
{
    private ParameterBagInterface $params;
    private EntityManagerInterface $em;
    private UtilisateursService $utilisateurService;

    private JwtTokenManager $jwtTokenManager;

    public function __construct(EntityManagerInterface $em, UtilisateursService $utilisateurService, JwtTokenManager $jwtTokenManager, ParameterBagInterface $params)
    {
        $this->em = $em;
        $this->utilisateurService = $utilisateurService;
        $this->jwtTokenManager = $jwtTokenManager;
        $this->params = $params;
    }
    #[Route('', name: 'user', methods: ['GET'])]
    #[TokenRequired(['Admin'])]
    public function getUtilisateur(Request $request): JsonResponse
    {
        try {

            $users = $this->utilisateurService->getAllUsers();

            $usersArray = array_map(function ($e) {
                return [
                    'id' => $e->getId(),
                    'email' => $e->getEmail(),
                    'nom' => $e->getNom(),
                    'prenom' => $e->getPrenom(),
                    'role' => $e->getRole()->getName(),
                ];
            }, $users);

            return new JsonResponse([
                'status' => 'success',
                'data' => $usersArray
            ], 200);

        } catch (\Exception $e) {
            if ($e->getMessage() === 'Inactif') {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Utilisateur inactif'
                ], 401); // ← renvoie bien 401
            }

            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }

    }

    #[Route('', name: 'api_utilisateur_create', methods: ['POST'])]
    #[TokenRequired(['Admin'])]
    public function createUser(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $requiredFields = ['email', 'nom', 'prenom', 'mdp', 'role'];
        $missingFields = [];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Champs requis manquants ' . implode(', ', $missingFields),
                'missingFields' => $missingFields
            ], 400);
        }

        $user = new Utilisateurs();
        $user->setEmail($data['email'])
            ->setNom($data['nom'])
            ->setPrenom($data['prenom']);

        // 🔐 Hashage simple du mot de passe
        $plainPassword = $data['mdp'];
        $user->setMdp($plainPassword);
        $role = $data['role'];
        try {
            $user = $this->utilisateurService->createUser($user, $role);
            return new JsonResponse([
                'status' => 'success',
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'nom' => $user->getNom(),
                    'prenom' => $user->getPrenom()
                ]
            ], 201);

        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    #[Route('/{id}', name: 'api_utilisateur_get_one', methods: ['GET'])]
    #[TokenRequired(['Admin'])]
    public function getOneUser(int $id): JsonResponse
    {
        $user = $this->utilisateurService->getUserById($id);

        if (!$user) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Utilisateur non trouvé'
            ], 404);
        }

        return new JsonResponse([
            'status' => 'success',
            'data' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'nom' => $user->getNom(),
                'prenom' => $user->getPrenom(),
                'role' => $user->getRole()->getName()
            ]
        ], 200);
    }

    #[Route('/{id}', name: 'api_utilisateur_update', methods: ['PUT'])]
    #[TokenRequired(['Admin'])]
    public function updateUser(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Données invalides ou JSON mal formé'
            ], 400);
        }

        try {
            $user = $this->utilisateurService->updateUser($id, $data);
            return new JsonResponse([
                'status' => 'success',
                'data' => [
                    'id' => $user->getId(),
                    'nom' => $user->getNom(),
                    'prenom' => $user->getPrenom(),
                    'email' => $user->getEmail(),
                    'role' => $user->getRole()->getName() ? $user->getRole()->getName() : null
                ]
            ], 200);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }



    #[Route('/login', name: 'api_utilisateur_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $requiredFields = ['email', 'mdp'];
        $missingFields = [];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Champs requis manquants',
                'missingFields' => $missingFields
            ], 400);
        }

        $email = $data['email'];
        $plainPassword = $data['mdp'];


        // 🔑 Vérification du login via le repository
        $user = $this->utilisateurService->login($email, $plainPassword);

        if (!$user) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Identifiants invalides'
            ], 404);
        }



        $claims = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'role' => $user->getRole()->getName(),
            'name' => $user->getNom() . ' ' . $user->getPrenom()
        ];

        $tokenDuration = $this->params->get('jwt_token_duration');

        $token = $this->jwtTokenManager->createToken($claims, $tokenDuration);
        $tokenString = $token->toString();
        $data = [
            'membre' => [
                // 'id' => $user->getId(),
                'email' => $user->getEmail(),
                'role' => $user->getRole()->getName(), // ajouter role ici
                'nom' => $user->getNom(),
                'prenom' => $user->getPrenom()
            ],
            'token' => $tokenString
        ];
        return new JsonResponse([
            'status' => 'success',
            'data' => $data
        ], 200);
    }
}
