<?php

namespace App\Integration\Domain\Repository;

use App\Integration\Domain\Entity\TenantAccount;
use Symfony\Component\Uid\Uuid;

interface TenantAccountRepositoryInterface
{
    public function findById(Uuid $id): ?TenantAccount;

    public function findByExternalAccountId(string $externalAccountId): ?TenantAccount;

    public function save(TenantAccount $account, bool $flush = true): void;

    public function remove(TenantAccount $account, bool $flush = true): void;
}
