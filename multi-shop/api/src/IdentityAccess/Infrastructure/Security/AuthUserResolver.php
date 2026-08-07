<?php

namespace App\IdentityAccess\Infrastructure\Security;

use App\IdentityAccess\Domain\Entity\User;
use App\IdentityAccess\Security\IdentityAssertionValidator;
use App\Onboarding\Application\Service\ControlPlaneException;
use App\Onboarding\Application\Service\ControlPlaneGatewayInterface;
use App\Shop\Domain\Entity\Shop;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class AuthUserResolver
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ControlPlaneGatewayInterface $controlPlaneClient,
        private readonly IdentityAssertionValidator $assertionValidator,
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

    public function verifyPassword(User $user, string $plainPassword): bool
    {
        if (null === $user->getIdentityId()) {
            throw new \LogicException('verifyPassword is only for identity-linked users.');
        }

        return $this->verifyIdentityPassword($user, $plainPassword);
    }

    private function verifyIdentityPassword(User $user, string $plainPassword): bool
    {
        $email = $user->getEmail();
        if (null === $email || '' === trim($email)) {
            return false;
        }

        try {
            $assertion = $this->controlPlaneClient->exchangeIdentityToken($email, $plainPassword);
            $claims = $this->assertionValidator->validate($assertion);
        } catch (ControlPlaneException|\InvalidArgumentException) {
            return false;
        }

        if ($claims->subject !== (string) $user->getIdentityId()) {
            return false;
        }

        if ($claims->emailVerified) {
            $user->syncEmailVerification(new \DateTimeImmutable());
        }

        return true;
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
        $user = $this->findByUsername($username);
        if (null === $user || !$user->belongsToShop($shop->getId())) {
            return null;
        }

        return $user;
    }

    private function findShopBySlug(string $shopSlug): ?Shop
    {
        return $this->entityManager->getRepository(Shop::class)->findOneBy(['slug' => strtolower(trim($shopSlug))]);
    }
}
