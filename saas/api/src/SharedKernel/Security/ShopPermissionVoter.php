<?php

namespace App\SharedKernel\Security;

use App\SharedKernel\Domain\ValueObject\TenantContext;
use App\SharedKernel\Infrastructure\Tenant\TenantContextHolder;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, mixed>
 */
final class ShopPermissionVoter extends Voter
{
    public const VIEW = 'SHOP_VIEW';
    public const MANAGE_CATALOG = 'SHOP_MANAGE_CATALOG';
    public const MANAGE_STOCK = 'SHOP_MANAGE_STOCK';

    public function __construct(
        private readonly TenantContextHolder $tenantContextHolder,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::MANAGE_CATALOG, self::MANAGE_STOCK], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        if (!$this->tenantContextHolder->has()) {
            return false;
        }

        $context = $this->tenantContextHolder->get();

        return match ($attribute) {
            self::VIEW => $context->canView(),
            self::MANAGE_CATALOG => $context->canManageCatalog(),
            self::MANAGE_STOCK => $context->canManageStock(),
            default => false,
        };
    }
}
