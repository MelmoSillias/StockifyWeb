<?php

namespace App\IdentityAccess\Application\Query\GetLoginHistory;

use App\IdentityAccess\Domain\Entity\User;

final class GetLoginHistoryQuery
{
    public function __construct(
        public readonly User $user,
        public readonly int $page = 1,
        public readonly int $limit = 10,
    ) {
    }
}
