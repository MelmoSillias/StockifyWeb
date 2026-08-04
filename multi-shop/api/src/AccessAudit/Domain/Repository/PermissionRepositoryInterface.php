<?php

namespace App\AccessAudit\Domain\Repository;

use App\AccessAudit\Domain\Entity\Permission;

interface PermissionRepositoryInterface
{
    public function findByCode(string $code): ?Permission;

    /** @return list<Permission> */
    public function findAllOrdered(): array;

    public function save(Permission $permission, bool $flush = true): void;
}
