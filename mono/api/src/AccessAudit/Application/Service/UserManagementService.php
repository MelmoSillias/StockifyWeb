<?php

namespace App\AccessAudit\Application\Service;

use App\AccessAudit\Domain\Entity\AuditLog;
use App\AccessAudit\Domain\Entity\UserPermission;
use App\AccessAudit\Domain\Entity\UserRole;
use App\AccessAudit\Domain\Enum\AuditStatus;
use App\AccessAudit\Domain\PermissionCatalog;
use App\AccessAudit\Domain\Repository\AuditLogRepositoryInterface;
use App\AccessAudit\Domain\Repository\PermissionRepositoryInterface;
use App\AccessAudit\Domain\Repository\RoleRepositoryInterface;
use App\AccessAudit\Domain\Repository\UserPermissionRepositoryInterface;
use App\AccessAudit\Domain\Repository\UserRoleRepositoryInterface;
use App\IdentityAccess\Domain\Entity\User;
use App\IdentityAccess\Domain\Enum\UserStatus;
use App\IdentityAccess\Domain\Repository\UserRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;

final class UserManagementService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly RoleRepositoryInterface $roleRepository,
        private readonly PermissionRepositoryInterface $permissionRepository,
        private readonly UserRoleRepositoryInterface $userRoleRepository,
        private readonly UserPermissionRepositoryInterface $userPermissionRepository,
        private readonly PermissionResolverService $permissionResolver,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly EntityManagerInterface $entityManager,
        private readonly AuditLoggerService $auditLogger,
    ) {
    }

    /** @return list<User> */
    public function listUsers(): array
    {
        return $this->entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->orderBy('u.lastName', 'ASC')
            ->addOrderBy('u.firstName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getUser(Uuid $id): ?User
    {
        return $this->userRepository->findById($id);
    }

    /**
     * @param list<string> $roleCodes
     * @param array<string, bool> $permissionOverrides
     */
    public function createUser(
        string $email,
        string $username,
        string $password,
        string $firstName,
        string $lastName,
        array $roleCodes,
        array $permissionOverrides = [],
    ): User {
        if ($this->userRepository->findByEmail($email) !== null) {
            throw new \InvalidArgumentException('Cet email est déjà utilisé.');
        }

        if ($this->userRepository->findByUsername($username) !== null) {
            throw new \InvalidArgumentException('Ce nom d\'utilisateur est déjà utilisé.');
        }

        $user = new User($email, $username, 'placeholder', $firstName, $lastName);
        $user->setPasswordHash($this->passwordHasher->hashPassword($user, $password));
        $user->activate();

        $this->assignRolesAndPermissions($user, $roleCodes, $permissionOverrides);
        $this->userRepository->save($user);

        $this->auditLogger->logAction('access.user.create', AuditStatus::Success, $user, 'user', $user->getId());

        return $user;
    }

    /**
     * @param list<string>|null $roleCodes
     * @param array<string, bool>|null $permissionOverrides
     */
    public function updateUser(
        User $user,
        ?string $firstName = null,
        ?string $lastName = null,
        ?string $email = null,
        ?string $username = null,
        ?array $roleCodes = null,
        ?array $permissionOverrides = null,
    ): User {
        if ($firstName !== null) {
            $user->setFirstName($firstName);
        }

        if ($lastName !== null) {
            $user->setLastName($lastName);
        }

        if ($email !== null) {
            if (strtolower($email) !== $user->getEmail()) {
                $existing = $this->userRepository->findByEmail($email);
                if ($existing !== null && !$existing->getId()->equals($user->getId())) {
                    throw new \InvalidArgumentException('Cet email est déjà utilisé.');
                }
            }
            $user->setEmail($email);
        }

        if ($username !== null) {
            if (strtolower($username) !== $user->getUsername()) {
                $existing = $this->userRepository->findByUsername($username);
                if ($existing !== null && !$existing->getId()->equals($user->getId())) {
                    throw new \InvalidArgumentException('Ce nom d\'utilisateur est déjà utilisé.');
                }
            }
            $user->setUsername($username);
        }

        if ($roleCodes !== null || $permissionOverrides !== null) {
            $this->assignRolesAndPermissions(
                $user,
                $roleCodes ?? $this->permissionResolver->resolveRoleCodes($user),
                $permissionOverrides ?? $this->extractPermissionOverrides($user),
            );
        }

        $this->userRepository->save($user);
        $this->auditLogger->logAction('access.user.update', AuditStatus::Success, $user, 'user', $user->getId());

        return $user;
    }

    public function suspendUser(User $user): void
    {
        $user->suspend();
        $this->userRepository->save($user);
        $this->auditLogger->logAction('access.user.suspend', AuditStatus::Success, $user, 'user', $user->getId());
    }

    public function resetPassword(User $user, string $newPassword): void
    {
        $user->setPasswordHash($this->passwordHasher->hashPassword($user, $newPassword));
        $this->userRepository->save($user);
        $this->auditLogger->logAction('access.user.reset_password', AuditStatus::Success, $user, 'user', $user->getId());
    }

    /**
     * @param list<string> $roleCodes
     * @param array<string, bool> $permissionOverrides
     */
    private function assignRolesAndPermissions(User $user, array $roleCodes, array $permissionOverrides): void
    {
        foreach ($user->getUserRoles()->toArray() as $existing) {
            $user->getUserRoles()->removeElement($existing);
            $this->entityManager->remove($existing);
        }

        foreach ($user->getUserPermissions()->toArray() as $existing) {
            $user->getUserPermissions()->removeElement($existing);
            $this->entityManager->remove($existing);
        }

        $assignedRoleCodes = [];
        foreach ($roleCodes as $roleCode) {
            $role = $this->roleRepository->findByCode($roleCode);
            if ($role === null) {
                throw new \InvalidArgumentException(sprintf('Rôle inconnu : %s', $roleCode));
            }

            $userRole = new UserRole($user, $role);
            $user->getUserRoles()->add($userRole);
            $this->entityManager->persist($userRole);
            $assignedRoleCodes[] = $roleCode;
        }

        foreach ($permissionOverrides as $permissionCode => $granted) {
            $permission = $this->permissionRepository->findByCode($permissionCode);
            if ($permission === null) {
                continue;
            }

            $userPermission = new UserPermission($user, $permission, (bool) $granted);
            $user->getUserPermissions()->add($userPermission);
            $this->entityManager->persist($userPermission);
        }

        $user->syncSymfonyRoles($assignedRoleCodes);
        $this->permissionResolver->invalidateCache($user);
    }

    /** @return array<string, bool> */
    private function extractPermissionOverrides(User $user): array
    {
        $overrides = [];
        foreach ($this->userPermissionRepository->findByUser($user) as $up) {
            $overrides[$up->getPermission()->getCode()] = $up->isGranted();
        }

        return $overrides;
    }

    /** @return array<string, mixed> */
    public function serializeUser(User $user): array
    {
        return [
            'id' => (string) $user->getId(),
            'email' => $user->getEmail(),
            'username' => $user->getUsername(),
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'status' => $user->getStatus()->value,
            'roles' => $this->permissionResolver->resolveRoleCodes($user),
            'symfony_roles' => $user->getRoles(),
            'permissions' => $this->permissionResolver->resolvePermissions($user),
            'last_login_at' => $user->getLastLoginAt()?->format(\DateTimeInterface::ATOM),
            'created_at' => $user->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}
