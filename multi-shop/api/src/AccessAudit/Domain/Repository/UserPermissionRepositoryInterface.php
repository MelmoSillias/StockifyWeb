<?php

namespace App\AccessAudit\Domain\Repository;

use App\AccessAudit\Domain\Entity\UserPermission;
use App\IdentityAccess\Domain\Entity\User;

interface UserPermissionRepositoryInterface
{
    /** @return list<UserPermission> */
    public function findByUser(User $user): array;

    public function save(UserPermission $userPermission, bool $flush = true): void;

    public function remove(UserPermission $userPermission, bool $flush = true): void;

    public function removeAllForUser(User $user, bool $flush = true): void;
}
