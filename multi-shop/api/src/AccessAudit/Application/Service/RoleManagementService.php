<?php

namespace App\AccessAudit\Application\Service;

use App\AccessAudit\Domain\Entity\Role;
use App\AccessAudit\Domain\Enum\AuditStatus;
use App\AccessAudit\Domain\Repository\PermissionRepositoryInterface;
use App\AccessAudit\Domain\Repository\RoleRepositoryInterface;
use Symfony\Component\Uid\Uuid;

final class RoleManagementService
{
    public function __construct(
        private readonly RoleRepositoryInterface $roleRepository,
        private readonly PermissionRepositoryInterface $permissionRepository,
        private readonly AuditLoggerService $auditLogger,
    ) {
    }

    /** @return list<Role> */
    public function listRoles(): array
    {
        return $this->roleRepository->findAllActive();
    }

    public function getRole(Uuid $id): ?Role
    {
        return $this->roleRepository->findById($id);
    }

    /**
     * @param list<string> $permissionCodes
     */
    public function createRole(string $code, string $label, ?string $description, array $permissionCodes): Role
    {
        $normalizedCode = strtolower(trim($code));
        if ($this->roleRepository->findByCode($normalizedCode) !== null) {
            throw new \InvalidArgumentException('Ce code de rôle existe déjà.');
        }

        $role = new Role($normalizedCode, $label, $description, false);
        $this->applyPermissions($role, $permissionCodes);
        $this->roleRepository->save($role);

        $this->auditLogger->logAction('access.role.create', AuditStatus::Success, null, 'role', $role->getId());

        return $role;
    }

    /**
     * @param list<string>|null $permissionCodes
     */
    public function updateRole(Role $role, ?string $label = null, ?string $description = null, ?array $permissionCodes = null): Role
    {
        if ($label !== null) {
            $role->setLabel($label);
        }

        if ($description !== null) {
            $role->setDescription($description);
        }

        if ($permissionCodes !== null) {
            $this->applyPermissions($role, $permissionCodes);
        }

        $this->roleRepository->save($role);
        $this->auditLogger->logAction('access.role.update', AuditStatus::Success, null, 'role', $role->getId());

        return $role;
    }

    public function deleteRole(Role $role): void
    {
        if ($role->isSystem()) {
            throw new \InvalidArgumentException('Les rôles système ne peuvent pas être supprimés.');
        }

        $this->roleRepository->remove($role);
        $this->auditLogger->logAction('access.role.delete', AuditStatus::Success, null, 'role', $role->getId());
    }

    /** @param list<string> $permissionCodes */
    private function applyPermissions(Role $role, array $permissionCodes): void
    {
        $permissions = [];
        foreach ($permissionCodes as $code) {
            $permission = $this->permissionRepository->findByCode($code);
            if ($permission !== null) {
                $permissions[] = $permission;
            }
        }

        $role->setPermissions($permissions);
    }

    /** @return array<string, mixed> */
    public function serializeRole(Role $role): array
    {
        return [
            'id' => (string) $role->getId(),
            'code' => $role->getCode(),
            'label' => $role->getLabel(),
            'description' => $role->getDescription(),
            'is_system' => $role->isSystem(),
            'is_active' => $role->isActive(),
            'permissions' => $role->getPermissionCodes(),
            'created_at' => $role->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updated_at' => $role->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}
