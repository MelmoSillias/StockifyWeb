<?php

namespace App\IdentityAccess\Application\Query\GetUserProfile;

use App\AccessAudit\Application\Service\PermissionResolverService;

final class GetUserProfileHandler
{
    public function __construct(
        private readonly PermissionResolverService $permissionResolver,
    ) {
    }

    /** @return array{user: array<string, mixed>, permissions: list<string>} */
    public function handle(GetUserProfileQuery $query): array
    {
        $user = $query->user;
        $roleCodes = $this->permissionResolver->resolveRoleCodes($user);
        $permissions = $this->permissionResolver->resolvePermissions($user);

        return [
            'user' => [
                'id' => (string) $user->getId(),
                'email' => $user->getEmail(),
                'username' => $user->getUsername(),
                'first_name' => $user->getFirstName(),
                'last_name' => $user->getLastName(),
                'status' => $user->getStatus()->value,
                'roles' => $roleCodes,
                'symfony_roles' => $user->getRoles(),
                'last_login_at' => $user->getLastLoginAt()?->format(\DateTimeInterface::ATOM),
                'created_at' => $user->getCreatedAt()->format(\DateTimeInterface::ATOM),
            ],
            'permissions' => $permissions,
        ];
    }
}
