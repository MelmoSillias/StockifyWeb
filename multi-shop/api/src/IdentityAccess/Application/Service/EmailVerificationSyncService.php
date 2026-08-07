<?php

namespace App\IdentityAccess\Application\Service;

use App\IdentityAccess\Domain\Entity\User;
use App\IdentityAccess\Domain\Repository\UserRepositoryInterface;
use App\Onboarding\Application\Service\ControlPlaneException;
use App\Onboarding\Application\Service\ControlPlaneGatewayInterface;
use Psr\Log\LoggerInterface;

final class EmailVerificationSyncService
{
    public function __construct(
        private readonly ControlPlaneGatewayInterface $controlPlaneClient,
        private readonly UserRepositoryInterface $userRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function syncFromControlPlane(User $user): bool
    {
        if (null === $user->getIdentityId()) {
            return $user->isEmailVerified();
        }

        if ($user->isEmailVerified()) {
            return true;
        }

        return $this->trySyncFromVerificationPull($user);
    }

    private function trySyncFromVerificationPull(User $user): bool
    {
        $identityId = $user->getIdentityId();
        if (null === $identityId) {
            return false;
        }

        try {
            $state = $this->controlPlaneClient->pullIdentityVerification((string) $identityId);
        } catch (ControlPlaneException $exception) {
            $this->logger->warning('Failed to pull identity verification state from control plane.', [
                'identityId' => (string) $identityId,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }

        if (!($state['emailVerified'] ?? false)) {
            return false;
        }

        $verifiedAt = $this->parseVerifiedAt($state['emailVerifiedAt'] ?? null);

        return $this->markVerified($user, $verifiedAt ?? new \DateTimeImmutable());
    }

    private function markVerified(User $user, \DateTimeImmutable $verifiedAt): bool
    {
        if ($user->isEmailVerified()) {
            return true;
        }

        $user->syncEmailVerification($verifiedAt);
        $this->userRepository->save($user);

        return true;
    }

    private function parseVerifiedAt(mixed $value): ?\DateTimeImmutable
    {
        if (!is_string($value) || '' === trim($value)) {
            return null;
        }

        $verifiedAt = \DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, trim($value));
        if (false !== $verifiedAt) {
            return $verifiedAt;
        }

        try {
            return new \DateTimeImmutable(trim($value));
        } catch (\Exception) {
            return null;
        }
    }
}
