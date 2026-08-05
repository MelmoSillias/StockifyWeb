<?php

namespace App\IdentityAccess\Application\Service;

use App\IdentityAccess\Domain\Enum\UserStatus;
use App\IdentityAccess\Domain\Repository\UserRepositoryInterface;
use App\IdentityAccess\Security\IdentityAssertionValidator;
use App\Onboarding\Application\Service\ControlPlaneGatewayInterface;
use App\Onboarding\Application\Service\ControlPlaneException;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Uid\Uuid;

final class GlobalAuthService
{
    public function __construct(
        private readonly bool $enabled,
        private readonly ControlPlaneGatewayInterface $controlPlaneClient,
        private readonly IdentityAssertionValidator $assertionValidator,
        private readonly UserRepositoryInterface $userRepository,
        private readonly JWTTokenManagerInterface $jwtManager,
    ) {
    }

    /**
     * @return array{token: string}
     */
    public function authenticate(string $email, string $password, string $applicationSlug = 'stockify'): array
    {
        if (!$this->enabled) {
            throw new GlobalAuthDisabledException('Global identity authentication is disabled.');
        }

        try {
            $assertion = $this->controlPlaneClient->exchangeIdentityToken($email, $password, $applicationSlug);
        } catch (ControlPlaneException $exception) {
            throw new GlobalAuthFailedException('Invalid credentials.', $exception->getCode(), $exception);
        }

        $claims = $this->assertionValidator->validate($assertion);
        $user = $this->userRepository->findByIdentityId(Uuid::fromString($claims->subject));
        if (null === $user) {
            throw new GlobalAuthFailedException('No local profile linked to this global identity.', 403);
        }

        if (UserStatus::Suspended === $user->getStatus()) {
            throw new GlobalAuthFailedException('Your account has been suspended.', 403);
        }

        if (UserStatus::Active !== $user->getStatus()) {
            throw new GlobalAuthFailedException('Your account is not active.', 403);
        }

        $user->recordLogin();
        $this->userRepository->save($user);

        return [
            'token' => $this->jwtManager->create($user),
        ];
    }
}
