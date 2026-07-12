<?php

namespace App\Tenancy\Presentation\Api\Controller;

use App\IdentityAccess\Domain\Entity\User;
use App\Tenancy\Application\Command\CreateShop\CreateShopCommand;
use App\Tenancy\Application\Command\CreateShop\CreateShopHandler;
use App\Tenancy\Application\Service\AccountAuthorizationService;
use App\Tenancy\Application\Service\CreateAccountService;
use App\Tenancy\Domain\Entity\Account;
use App\Tenancy\Domain\Entity\Shop;
use App\Tenancy\Domain\Repository\AccountMemberRepositoryInterface;
use App\Tenancy\Presentation\Api\Serializer\AccountSerializer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
final class AccountController extends AbstractController
{
    public function __construct(
        private readonly CreateAccountService $createAccountService,
        private readonly CreateShopHandler $createShopHandler,
        private readonly AccountAuthorizationService $authorizationService,
        private readonly AccountMemberRepositoryInterface $accountMemberRepository,
        private readonly AccountSerializer $serializer,
    ) {
    }

    #[Route('/accounts', name: 'api_accounts_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = $request->toArray();
        foreach (['name', 'slug', 'shop_name', 'shop_slug'] as $field) {
            if (empty($data[$field])) {
                return $this->json(['error' => sprintf('Field %s is required.', $field)], Response::HTTP_BAD_REQUEST);
            }
        }

        /** @var User $user */
        $user = $this->getUser();
        $account = $this->createAccountService->create(
            $user,
            $data['name'],
            $data['slug'],
            $data['shop_name'],
            $data['shop_slug'],
        );

        return $this->json($this->serializer->serializeAccount($account), Response::HTTP_CREATED);
    }

    #[Route('/accounts', name: 'api_accounts_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $memberships = $this->accountMemberRepository->findActiveByUser($user);

        return $this->json(array_map(
            fn ($m) => $this->serializer->serializeAccount($m->getAccount()),
            $memberships,
        ));
    }

    #[Route('/accounts/{id}', name: 'api_accounts_show', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        try {
            $account = $this->authorizationService->getAuthorizedAccount($id, $user);
        } catch (\DomainException $e) {
            if ('Access denied' === $e->getMessage()) {
                throw $this->createAccessDeniedException();
            }
            throw $this->createNotFoundException();
        }

        return $this->json($this->serializer->serializeAccount($account));
    }

    #[Route('/accounts/{accountId}/shops', name: 'api_shops_create', methods: ['POST'])]
    public function createShop(string $accountId, Request $request): JsonResponse
    {
        $data = $request->toArray();
        /** @var User $user */
        $user = $this->getUser();

        try {
            $shop = $this->createShopHandler->handle(new CreateShopCommand(
                accountId: $accountId,
                name: $data['name'] ?? '',
                slug: $data['slug'] ?? '',
            ), $user);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\DomainException $e) {
            if ('Access denied' === $e->getMessage()) {
                throw $this->createAccessDeniedException();
            }
            throw $this->createNotFoundException();
        }

        return $this->json($this->serializer->serializeShop($shop), Response::HTTP_CREATED);
    }
}
