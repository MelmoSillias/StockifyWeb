<?php

namespace App\Shop\Application\Command\CreateShopUser;

use App\AccessAudit\Application\Service\UserManagementService;
use App\IdentityAccess\Domain\Entity\User;
use App\IdentityAccess\Domain\Repository\UserRepositoryInterface;
use App\Shop\Application\Service\ShopPasswordGenerator;
use App\Shop\Domain\Repository\ShopRepositoryInterface;
use App\Shop\Domain\ValueObject\ShopUsername;

final class CreateShopUserHandler
{
    public function __construct(
        private readonly ShopRepositoryInterface $shopRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly UserManagementService $userManagementService,
        private readonly ShopPasswordGenerator $passwordGenerator,
    ) {
    }

    /**
     * @return array{user: User, generated_password: string}
     */
    public function handle(CreateShopUserCommand $command): array
    {
        $shop = $this->shopRepository->findById($command->shopId);
        if (null === $shop) {
            throw new \InvalidArgumentException('Boutique introuvable.');
        }

        $username = ShopUsername::fromString($command->username)->value();

        if (null !== $this->userRepository->findByUsernameAndShop($username, $shop->getId())) {
            throw new \InvalidArgumentException('Ce nom d\'utilisateur est déjà utilisé dans cette boutique.');
        }

        $generatedPassword = $this->passwordGenerator->generate();

        $user = $this->userManagementService->createUser(
            email: null,
            username: $username,
            password: $generatedPassword->plainValue(),
            firstName: trim($command->firstName),
            lastName: trim($command->lastName),
            roleCodes: $command->roleCodes,
        );

        $user->assignToShop($shop->getId());
        $tenantAccountId = $shop->getTenantAccountId();
        if (null !== $tenantAccountId && null === $user->getTenantAccountId()) {
            $user->assignToTenantAccount($tenantAccountId);
        }
        $this->userRepository->save($user);

        return [
            'user' => $user,
            'generated_password' => $generatedPassword->plainValue(),
        ];
    }
}
