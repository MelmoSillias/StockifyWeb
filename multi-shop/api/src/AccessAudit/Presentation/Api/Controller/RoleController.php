<?php

namespace App\AccessAudit\Presentation\Api\Controller;

use App\AccessAudit\Application\Service\RoleManagementService;
use App\AccessAudit\Domain\Repository\PermissionRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[Route('/api')]
final class RoleController extends AbstractController
{
    public function __construct(
        private readonly RoleManagementService $roleManagementService,
        private readonly PermissionRepositoryInterface $permissionRepository,
    ) {
    }

    #[Route('/roles', name: 'api_roles_list', methods: ['GET'])]
    #[IsGranted('access.roles.view')]
    public function list(): JsonResponse
    {
        $roles = array_map(
            fn ($role) => $this->roleManagementService->serializeRole($role),
            $this->roleManagementService->listRoles(),
        );

        return $this->json(['data' => $roles]);
    }

    #[Route('/permissions', name: 'api_permissions_list', methods: ['GET'])]
    #[IsGranted('access.roles.view')]
    public function listPermissions(): JsonResponse
    {
        $permissions = array_map(static fn ($p) => [
            'id' => (string) $p->getId(),
            'code' => $p->getCode(),
            'label' => $p->getLabel(),
            'module' => $p->getModule(),
            'action' => $p->getAction(),
            'is_critical' => $p->isCritical(),
        ], $this->permissionRepository->findAllOrdered());

        return $this->json(['data' => $permissions]);
    }

    #[Route('/roles/{id}', name: 'api_roles_show', methods: ['GET'])]
    #[IsGranted('access.roles.view')]
    public function show(string $id): JsonResponse
    {
        $role = $this->roleManagementService->getRole(Uuid::fromString($id));
        if ($role === null) {
            throw $this->createNotFoundException('Rôle introuvable.');
        }

        return $this->json(['data' => $this->roleManagementService->serializeRole($role)]);
    }

    #[Route('/roles', name: 'api_roles_create', methods: ['POST'])]
    #[IsGranted('access.roles.manage')]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data) || empty($data['code']) || empty($data['label'])) {
            return $this->json(['error' => 'code et label sont requis.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $role = $this->roleManagementService->createRole(
                (string) $data['code'],
                (string) $data['label'],
                isset($data['description']) ? (string) $data['description'] : null,
                is_array($data['permissions'] ?? null) ? $data['permissions'] : [],
            );
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(['data' => $this->roleManagementService->serializeRole($role)], Response::HTTP_CREATED);
    }

    #[Route('/roles/{id}', name: 'api_roles_update', methods: ['PUT'])]
    #[IsGranted('access.roles.manage')]
    public function update(string $id, Request $request): JsonResponse
    {
        $role = $this->roleManagementService->getRole(Uuid::fromString($id));
        if ($role === null) {
            throw $this->createNotFoundException('Rôle introuvable.');
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['error' => 'Corps JSON invalide.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $role = $this->roleManagementService->updateRole(
                $role,
                isset($data['label']) ? (string) $data['label'] : null,
                array_key_exists('description', $data) ? (is_string($data['description']) ? $data['description'] : null) : null,
                is_array($data['permissions'] ?? null) ? $data['permissions'] : null,
            );
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(['data' => $this->roleManagementService->serializeRole($role)]);
    }

    #[Route('/roles/{id}', name: 'api_roles_delete', methods: ['DELETE'])]
    #[IsGranted('access.roles.manage')]
    public function delete(string $id): JsonResponse
    {
        $role = $this->roleManagementService->getRole(Uuid::fromString($id));
        if ($role === null) {
            throw $this->createNotFoundException('Rôle introuvable.');
        }

        try {
            $this->roleManagementService->deleteRole($role);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
