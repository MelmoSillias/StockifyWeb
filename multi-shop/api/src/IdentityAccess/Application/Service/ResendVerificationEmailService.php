<?php

namespace App\IdentityAccess\Application\Service;

use App\IdentityAccess\Domain\Entity\User;
use App\Onboarding\Application\Service\ControlPlaneGatewayInterface;

final class ResendVerificationEmailService
{
    public function __construct(
        private readonly ControlPlaneGatewayInterface $controlPlaneClient,
    ) {
    }

    public function resend(User $user): void
    {
        $email = $user->getEmail();
        if (null === $email || '' === trim($email)) {
            throw new \InvalidArgumentException('User email is required to resend verification.');
        }

        if ($user->isEmailVerified()) {
            return;
        }

        $this->controlPlaneClient->resendVerificationEmail($email);
    }
}
