<?php

namespace App\Integration\Domain\Repository;

use App\Integration\Domain\Entity\IntegrationRequestLog;

interface IntegrationRequestLogRepositoryInterface
{
    public function findByIdempotencyKey(string $idempotencyKey, string $method, string $path): ?IntegrationRequestLog;

    public function save(IntegrationRequestLog $log, bool $flush = true): void;
}
