<?php

namespace App\SharedKernel\Infrastructure\Shop;

use App\IdentityAccess\Domain\Entity\User;
use App\Integration\Domain\Repository\TenantAccountRepositoryInterface;
use App\SharedKernel\Domain\ValueObject\ShopContext;
use App\Shop\Domain\Entity\Shop;
use App\Shop\Domain\Repository\ShopRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Uid\Uuid;

final class ShopContextResolver
{
    public function __construct(
        private readonly ShopRepositoryInterface $shopRepository,
        private readonly TenantAccountRepositoryInterface $tenantAccountRepository,
    ) {
    }

    public function resolve(User $user, Request $request): ShopContext
    {
        $shopId = $this->resolveHeader($request, 'X-Shop-Id');
        $shop = $this->findShop($shopId);

        if ($user->isPlatformOwner()) {
            return new ShopContext($shop->getId(), $shop->getName(), $shop->getSlug());
        }

        if (null !== $user->getTenantAccountId() && null !== $shop->getTenantAccountId()
            && $user->getTenantAccountId()->equals($shop->getTenantAccountId())) {
            return new ShopContext($shop->getId(), $shop->getName(), $shop->getSlug());
        }

        if (!$user->belongsToShop($shopId)) {
            throw new AccessDeniedHttpException('Access denied for this shop.');
        }

        return new ShopContext($shop->getId(), $shop->getName(), $shop->getSlug());
    }

    private function resolveHeader(Request $request, string $name): Uuid
    {
        $value = $request->headers->get($name);
        if (null === $value || '' === $value) {
            throw new BadRequestHttpException(sprintf('Missing header %s.', $name));
        }

        if (!Uuid::isValid($value)) {
            throw new BadRequestHttpException(sprintf('Invalid UUID in header %s.', $name));
        }

        return Uuid::fromString($value);
    }

    private function findShop(Uuid $shopId): Shop
    {
        $shop = $this->shopRepository->findById($shopId);
        if (null === $shop) {
            throw new AccessDeniedHttpException('Shop not found.');
        }

        $tenantAccountId = $shop->getTenantAccountId();
        if (null !== $tenantAccountId) {
            $tenantAccount = $this->tenantAccountRepository->findById($tenantAccountId);
            if (null !== $tenantAccount && $tenantAccount->isSuspended()) {
                throw new AccessDeniedHttpException('Tenant account is suspended.');
            }
        }

        return $shop;
    }
}
