<?php

namespace App\AccessAudit\Security;

use App\AccessAudit\Application\Service\PermissionResolverService;
use App\AccessAudit\Domain\PermissionCatalog;
use App\IdentityAccess\Domain\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/** @extends Voter<string, mixed> */
final class PermissionVoter extends Voter
{
    public function __construct(
        private readonly PermissionResolverService $permissionResolver,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, PermissionCatalog::allCodes(), true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        return $this->permissionResolver->hasPermission($user, $attribute);
    }
}
