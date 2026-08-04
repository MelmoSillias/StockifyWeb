<?php

namespace App\IdentityAccess\Application\Query\GetUserProfile;

use App\IdentityAccess\Domain\Entity\User;

final readonly class GetUserProfileQuery
{
    public function __construct(
        public User $user,
    ) {
    }
}
