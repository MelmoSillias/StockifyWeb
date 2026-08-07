<?php

namespace App\IdentityAccess\Application\Command\ChangePassword;

use App\AccessAudit\Application\Service\AuditLoggerService;
use App\AccessAudit\Domain\Enum\AuditStatus;
use App\IdentityAccess\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class ChangePasswordHandler
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly UserRepositoryInterface $userRepository,
        private readonly AuditLoggerService $auditLogger,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function handle(ChangePasswordCommand $command): void
    {
        $user = $command->user;

        if (null !== $user->getIdentityId()) {
            throw new \DomainException('Password is managed by your global identity account.');
        }

        if (!$this->passwordHasher->isPasswordValid($user, $command->currentPassword)) {
            throw new \DomainException('Mot de passe actuel incorrect.');
        }

        $user->setPasswordHash($this->passwordHasher->hashPassword($user, $command->newPassword));
        $this->userRepository->save($user);

        $this->auditLogger->logAction(
            action: 'auth.password.change',
            status: AuditStatus::Success,
            user: $user,
            resourceType: 'auth',
            request: $this->requestStack->getCurrentRequest(),
        );
    }
}
