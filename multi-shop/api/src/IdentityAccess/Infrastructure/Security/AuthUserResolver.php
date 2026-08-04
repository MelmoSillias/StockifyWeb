<?php

namespace App\IdentityAccess\Infrastructure\Security;

use App\IdentityAccess\Domain\Entity\User;
use App\Shop\Domain\Entity\Shop;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class AuthUserResolver
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        #[Autowire('%env(AUTH_IDENTIFIER)%')]
        private readonly string $authIdentifier = 'email',
    ) {
    }

    public function resolve(string $identifier, ?string $shopSlug = null): ?User
    {
        $normalized = strtolower(trim($identifier));
        if ('' === $normalized) {
            return null;
        }

        if (str_contains($normalized, '@')) {
            return $this->findByEmail($normalized);
        }

        if (null !== $shopSlug && '' !== trim($shopSlug)) {
            $shop = $this->findShopBySlug($shopSlug);
            if (null !== $shop) {
                $shopUser = $this->findByUsernameAndShop($normalized, $shop);
                if (null !== $shopUser) {
                    return $shopUser;
                }
            }
        }

        return match ($this->authIdentifier) {
            'username', 'username_or_email' => $this->findByUsername($normalized),
            default => $this->findByEmail($normalized) ?? $this->findByUsername($normalized),
        };
    }

    private function findByEmail(string $email): ?User
    {
        return $this->entityManager->getRepository(User::class)->findOneBy(['email' => strtolower($email)]);
    }

    private function findByUsername(string $username): ?User
    {
        return $this->entityManager->getRepository(User::class)->findOneBy(['username' => strtolower($username)]);
    }

    private function findByUsernameAndShop(string $username, Shop $shop): ?User
    {
        return $this->entityManager->getRepository(User::class)->findOneBy([
            'username' => strtolower($username),
            'shopId' => $shop->getId(),
        ]);
    }

    private function findShopBySlug(string $shopSlug): ?Shop
    {
        return $this->entityManager->getRepository(Shop::class)->findOneBy(['slug' => strtolower(trim($shopSlug))]);
    }
}
