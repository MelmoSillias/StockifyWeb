<?php

namespace App\Integration\Application\Command\SyncIdentityState;

use App\IdentityAccess\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Uid\Uuid;

final class SyncIdentityStateHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {
    }

    public function handle(SyncIdentityStateCommand $command): void
    {
        if (!Uuid::isValid($command->identityId)) {
            throw new \InvalidArgumentException('Invalid identity id.');
        }

        $user = $this->userRepository->findByIdentityId(Uuid::fromString($command->identityId));
        if (null === $user) {
            throw new \InvalidArgumentException('User not found for identity id.');
        }

        $verifiedAt = null;
        if (null !== $command->emailVerifiedAt && '' !== trim($command->emailVerifiedAt)) {
            $verifiedAt = \DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, trim($command->emailVerifiedAt));
            if (false === $verifiedAt) {
                $verifiedAt = new \DateTimeImmutable(trim($command->emailVerifiedAt));
            }
        }

        $user->syncEmailVerification($verifiedAt);
        $this->userRepository->save($user);
    }
}
