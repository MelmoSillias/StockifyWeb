<?php

namespace App\IdentityAccess\Domain\Repository;

use App\IdentityAccess\Domain\Entity\RefreshToken;

interface RefreshTokenRepositoryInterface
{
    public function findValidByHash(string $tokenHash): ?RefreshToken;

    public function save(RefreshToken $token, bool $flush = true): void;
}
