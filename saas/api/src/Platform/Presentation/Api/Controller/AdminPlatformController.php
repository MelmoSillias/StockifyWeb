<?php

namespace App\Platform\Presentation\Api\Controller;

use App\Platform\Application\Query\GetPlatformStats\GetPlatformStatsHandler;
use App\Platform\Application\Query\GetPlatformStats\GetPlatformStatsQuery;
use App\Platform\Application\Query\ListAllAccounts\ListAllAccountsHandler;
use App\Platform\Application\Query\ListAllAccounts\ListAllAccountsQuery;
use App\Tenancy\Domain\Repository\AccountRepositoryInterface;
use App\Tenancy\Domain\Repository\ShopRepositoryInterface;
use App\Tenancy\Presentation\Api\Serializer\AccountSerializer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[Route('/api/admin')]
#[IsGranted('ROLE_SUPER_ADMIN')]
final class AdminPlatformController extends AbstractController
{
    public function __construct(
        private readonly GetPlatformStatsHandler $statsHandler,
        private readonly ListAllAccountsHandler $listAccountsHandler,
        private readonly AccountRepositoryInterface $accountRepository,
        private readonly ShopRepositoryInterface $shopRepository,
        private readonly AccountSerializer $serializer,
    ) {
    }

    #[Route('/stats', name: 'api_admin_stats', methods: ['GET'])]
    public function stats(): JsonResponse
    {
        return $this->json($this->statsHandler->handle(new GetPlatformStatsQuery()));
    }

    #[Route('/accounts', name: 'api_admin_accounts_list', methods: ['GET'])]
    public function listAccounts(): JsonResponse
    {
        $accounts = $this->listAccountsHandler->handle(new ListAllAccountsQuery());

        return $this->json(array_map(
            fn ($account) => $this->serializer->serializeAccountSummary($account),
            $accounts,
        ));
    }

    #[Route('/accounts/{id}', name: 'api_admin_accounts_show', methods: ['GET'])]
    public function showAccount(string $id): JsonResponse
    {
        $account = $this->getAccount($id);

        return $this->json($this->serializer->serializeAccount($account));
    }

    #[Route('/shops', name: 'api_admin_shops_list', methods: ['GET'])]
    public function listShops(): JsonResponse
    {
        $shops = $this->shopRepository->findAllOrderedByName();

        return $this->json(array_map(
            fn ($shop) => $this->serializer->serializeShopWithAccount($shop),
            $shops,
        ));
    }

    private function getAccount(string $id): \App\Tenancy\Domain\Entity\Account
    {
        if (!Uuid::isValid($id)) {
            throw $this->createNotFoundException();
        }

        $account = $this->accountRepository->findById(Uuid::fromString($id));
        if (null === $account) {
            throw $this->createNotFoundException();
        }

        return $account;
    }
}
