<?php

namespace App\IdentityAccess\Application\Command\ChangePassword;

use App\IdentityAccess\Domain\Entity\User;

final class ChangePasswordCommand
{
    public function __construct(
        public readonly User $user,
        public readonly string $currentPassword,
        public readonly string $newPassword,
    ) {
    }
}
