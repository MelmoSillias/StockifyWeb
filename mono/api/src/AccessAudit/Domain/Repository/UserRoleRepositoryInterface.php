<?php

namespace App\AccessAudit\Domain\Repository;

use App\AccessAudit\Domain\Entity\UserRole;
use App\IdentityAccess\Domain\Entity\User;

interface UserRoleRepositoryInterface
{
    /** @return list<UserRole> */
    public function findByUser(User $user): array;

    public function save(UserRole $userRole, bool $flush = true): void;

    public function remove(UserRole $userRole, bool $flush = true): void;

    public function removeAllForUser(User $user, bool $flush = true): void;
}
