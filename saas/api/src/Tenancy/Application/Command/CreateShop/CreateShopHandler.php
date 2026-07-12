<?php

namespace App\Tenancy\Application\Command\CreateShop;

use App\IdentityAccess\Domain\Entity\User;
use App\Tenancy\Application\Service\AccountAuthorizationService;
use App\Tenancy\Domain\Entity\Shop;
use App\Tenancy\Domain\Repository\ShopRepositoryInterface;

final class CreateShopHandler
{
    public function __construct(
        private readonly AccountAuthorizationService $authorizationService,
        private readonly ShopRepositoryInterface $shopRepository,
    ) {
    }

    public function handle(CreateShopCommand $command, User $user): Shop
    {
        if ('' === trim($command->name) || '' === trim($command->slug)) {
            throw new \InvalidArgumentException('name and slug are required');
        }

        $account = $this->authorizationService->getAuthorizedAccount($command->accountId, $user);
        $shop = new Shop($account, $command->name, $command->slug);
        $this->shopRepository->save($shop);

        return $shop;
    }
}
