<?php

namespace App\AccessAudit\Domain\Repository;

use App\AccessAudit\Domain\Entity\Role;
use Symfony\Component\Uid\Uuid;

interface RoleRepositoryInterface
{
    public function findByCode(string $code): ?Role;

    public function findById(Uuid $id): ?Role;

    /** @return list<Role> */
    public function findAllActive(): array;

    public function save(Role $role, bool $flush = true): void;

    public function remove(Role $role, bool $flush = true): void;
}
