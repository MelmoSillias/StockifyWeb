<?php

namespace App\AccessAudit\Presentation\Api\Controller;

use App\AccessAudit\Application\Service\UserManagementService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[Route('/api')]
final class UserController extends AbstractController
{
    public function __construct(
        private readonly UserManagementService $userManagementService,
    ) {
    }

    #[Route('/users', name: 'api_users_list', methods: ['GET'])]
    #[IsGranted('access.users.view')]
    public function list(): JsonResponse
    {
        $users = array_map(
            fn ($user) => $this->userManagementService->serializeUser($user),
            $this->userManagementService->listUsers(),
        );

        return $this->json(['data' => $users]);
    }

    #[Route('/users/{id}', name: 'api_users_show', methods: ['GET'])]
    #[IsGranted('access.users.view')]
    public function show(string $id): JsonResponse
    {
        $user = $this->userManagementService->getUser(Uuid::fromString($id));
        if ($user === null) {
            throw $this->createNotFoundException('Utilisateur introuvable.');
        }

        return $this->json(['data' => $this->userManagementService->serializeUser($user)]);
    }

    #[Route('/users', name: 'api_users_create', methods: ['POST'])]
    #[IsGranted('access.users.create')]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['error' => 'Corps JSON invalide.'], Response::HTTP_BAD_REQUEST);
        }

        foreach (['email', 'username', 'password', 'first_name', 'last_name'] as $field) {
            if (empty($data[$field])) {
                return $this->json(['error' => sprintf('Le champ %s est requis.', $field)], Response::HTTP_BAD_REQUEST);
            }
        }

        try {
            $user = $this->userManagementService->createUser(
                (string) $data['email'],
                (string) $data['username'],
                (string) $data['password'],
                (string) $data['first_name'],
                (string) $data['last_name'],
                is_array($data['roles'] ?? null) ? $data['roles'] : [],
                is_array($data['permission_overrides'] ?? null) ? $data['permission_overrides'] : [],
            );
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(['data' => $this->userManagementService->serializeUser($user)], Response::HTTP_CREATED);
    }

    #[Route('/users/{id}', name: 'api_users_update', methods: ['PUT'])]
    #[IsGranted('access.users.update')]
    public function update(string $id, Request $request): JsonResponse
    {
        $user = $this->userManagementService->getUser(Uuid::fromString($id));
        if ($user === null) {
            throw $this->createNotFoundException('Utilisateur introuvable.');
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['error' => 'Corps JSON invalide.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $user = $this->userManagementService->updateUser(
                $user,
                isset($data['first_name']) ? (string) $data['first_name'] : null,
                isset($data['last_name']) ? (string) $data['last_name'] : null,
                isset($data['email']) ? (string) $data['email'] : null,
                isset($data['username']) ? (string) $data['username'] : null,
                is_array($data['roles'] ?? null) ? $data['roles'] : null,
                is_array($data['permission_overrides'] ?? null) ? $data['permission_overrides'] : null,
            );
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(['data' => $this->userManagementService->serializeUser($user)]);
    }

    #[Route('/users/{id}/suspend', name: 'api_users_suspend', methods: ['POST'])]
    #[IsGranted('access.users.suspend')]
    public function suspend(string $id): JsonResponse
    {
        $user = $this->userManagementService->getUser(Uuid::fromString($id));
        if ($user === null) {
            throw $this->createNotFoundException('Utilisateur introuvable.');
        }

        $this->userManagementService->suspendUser($user);

        return $this->json(['data' => $this->userManagementService->serializeUser($user)]);
    }

    #[Route('/users/{id}/reset-password', name: 'api_users_reset_password', methods: ['POST'])]
    #[IsGranted('access.users.update')]
    public function resetPassword(string $id, Request $request): JsonResponse
    {
        $user = $this->userManagementService->getUser(Uuid::fromString($id));
        if ($user === null) {
            throw $this->createNotFoundException('Utilisateur introuvable.');
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data) || empty($data['password'])) {
            return $this->json(['error' => 'password est requis.'], Response::HTTP_BAD_REQUEST);
        }

        $this->userManagementService->resetPassword($user, (string) $data['password']);

        return $this->json(['message' => 'Mot de passe réinitialisé.']);
    }
}
