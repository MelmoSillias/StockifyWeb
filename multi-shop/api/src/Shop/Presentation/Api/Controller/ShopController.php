<?php

namespace App\Shop\Presentation\Api\Controller;

use App\AccessAudit\Application\Service\UserManagementService;
use App\IdentityAccess\Domain\Entity\User;
use App\IdentityAccess\Domain\Repository\UserRepositoryInterface;
use App\Integration\Application\Service\TenantEntitlementResolver;
use App\Integration\Domain\Repository\TenantAccountRepositoryInterface;
use App\Shop\Application\Command\AddShopMembership\AddShopMembershipCommand;
use App\Shop\Application\Command\AddShopMembership\AddShopMembershipHandler;
use App\Shop\Application\Command\CreateOwnedShop\CreateOwnedShopHandler;
use App\Shop\Application\Command\CreateShop\CreateShopCommand;
use App\Shop\Application\Command\CreateShopUser\CreateShopUserCommand;
use App\Shop\Application\Command\CreateShopUser\CreateShopUserHandler;
use App\Shop\Application\Command\DeleteShop\DeleteShopCommand;
use App\Shop\Application\Command\DeleteShop\DeleteShopHandler;
use App\Shop\Application\Command\RemoveShopMembership\RemoveShopMembershipCommand;
use App\Shop\Application\Command\RemoveShopMembership\RemoveShopMembershipHandler;
use App\Shop\Application\Command\UpdateShop\UpdateShopCommand;
use App\Shop\Application\Command\UpdateShop\UpdateShopHandler;
use App\Shop\Domain\Entity\Shop;
use App\Shop\Domain\Repository\ShopRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[Route('/api')]
final class ShopController extends AbstractController
{
    public function __construct(
        private readonly ShopRepositoryInterface $shopRepository,
        private readonly CreateOwnedShopHandler $createOwnedShopHandler,
        private readonly UpdateShopHandler $updateShopHandler,
        private readonly DeleteShopHandler $deleteShopHandler,
        private readonly CreateShopUserHandler $createShopUserHandler,
        private readonly AddShopMembershipHandler $addShopMembershipHandler,
        private readonly RemoveShopMembershipHandler $removeShopMembershipHandler,
        private readonly UserRepositoryInterface $userRepository,
        private readonly UserManagementService $userManagementService,
        private readonly TenantAccountRepositoryInterface $tenantAccountRepository,
        private readonly TenantEntitlementResolver $entitlementResolver,
    ) {
    }

    #[Route('/shops', name: 'api_shops_list', methods: ['GET'])]
    #[IsGranted('platform.shops.view')]
    public function list(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->json([
            'data' => array_map([$this, 'serializeShop'], $this->listManageableShops($user)),
        ]);
    }

    #[Route('/shops', name: 'api_shops_create', methods: ['POST'])]
    #[IsGranted('platform.shops.manage')]
    public function create(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return $this->json(['error' => 'Corps JSON invalide.'], Response::HTTP_BAD_REQUEST);
        }

        /** @var User $user */
        $user = $this->getUser();

        try {
            $shop = $this->createOwnedShopHandler->handle(
                $user,
                CreateShopCommand::fromPayload($payload),
            );
        } catch (\InvalidArgumentException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\DomainException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_CONFLICT);
        }

        return $this->json(['data' => $this->serializeShop($shop)], Response::HTTP_CREATED);
    }

    #[Route('/shops/{id}', name: 'api_shops_update', methods: ['PUT'])]
    #[IsGranted('platform.shops.manage')]
    public function update(string $id, Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return $this->json(['error' => 'Corps JSON invalide.'], Response::HTTP_BAD_REQUEST);
        }

        /** @var User $user */
        $user = $this->getUser();
        $this->assertCanManageShop($user, Uuid::fromString($id));

        try {
            $shop = $this->updateShopHandler->handle(UpdateShopCommand::fromPayload(Uuid::fromString($id), $payload));
        } catch (\InvalidArgumentException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(['data' => $this->serializeShop($shop)]);
    }

    #[Route('/shops/{id}', name: 'api_shops_delete', methods: ['DELETE'])]
    #[IsGranted('platform.shops.manage')]
    public function delete(string $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $this->assertCanManageShop($user, Uuid::fromString($id));

        try {
            $this->deleteShopHandler->handle(new DeleteShopCommand(Uuid::fromString($id)));
        } catch (\InvalidArgumentException|\DomainException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/shops/{id}/users', name: 'api_shop_users_list', methods: ['GET'])]
    #[IsGranted('platform.shop_users.manage')]
    public function listUsers(string $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $shopId = Uuid::fromString($id);
        $this->assertCanManageShop($user, $shopId);

        $users = $this->userRepository->findByShopId($shopId);

        return $this->json([
            'data' => array_map(
                fn (User $shopUser) => $this->userManagementService->serializeUser($shopUser),
                $users,
            ),
        ]);
    }

    #[Route('/shops/{id}/users', name: 'api_shop_users_create', methods: ['POST'])]
    #[IsGranted('platform.shop_users.manage')]
    public function createUser(string $id, Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return $this->json(['error' => 'Corps JSON invalide.'], Response::HTTP_BAD_REQUEST);
        }

        /** @var User $user */
        $user = $this->getUser();
        $this->assertCanManageShop($user, Uuid::fromString($id));

        try {
            $result = $this->createShopUserHandler->handle(
                CreateShopUserCommand::fromPayload(Uuid::fromString($id), $payload),
            );
        } catch (\InvalidArgumentException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\DomainException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_CONFLICT);
        }

        return $this->json([
            'data' => $this->userManagementService->serializeUser($result['user']),
            'generated_password' => $result['generated_password'],
        ], Response::HTTP_CREATED);
    }

    #[Route('/shops/{shopId}/users/{userId}/membership', name: 'api_shop_membership_add', methods: ['POST'])]
    #[IsGranted('platform.shop_users.manage')]
    public function addMembership(string $shopId, string $userId): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $this->assertCanManageShop($user, Uuid::fromString($shopId));

        try {
            $member = $this->addShopMembershipHandler->handle(new AddShopMembershipCommand(
                Uuid::fromString($shopId),
                Uuid::fromString($userId),
            ));
        } catch (\InvalidArgumentException|\DomainException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json([
            'data' => $this->userManagementService->serializeUser($member),
        ]);
    }

    #[Route('/shops/{shopId}/users/{userId}/membership', name: 'api_shop_membership_remove', methods: ['DELETE'])]
    #[IsGranted('platform.shop_users.manage')]
    public function removeMembership(string $shopId, string $userId): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $this->assertCanManageShop($user, Uuid::fromString($shopId));

        try {
            $member = $this->removeShopMembershipHandler->handle(new RemoveShopMembershipCommand(
                Uuid::fromString($shopId),
                Uuid::fromString($userId),
            ));
        } catch (\InvalidArgumentException|\DomainException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json([
            'data' => $this->userManagementService->serializeUser($member),
        ]);
    }

    #[Route('/me/shops', name: 'api_me_shops', methods: ['GET'])]
    public function myShops(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->isPlatformOwner()) {
            return $this->json([
                'data' => array_map([$this, 'serializeShop'], $this->shopRepository->findAllOrderedByName()),
            ]);
        }

        $shops = [];
        foreach ($user->getShopIds() as $shopId) {
            $shop = $this->shopRepository->findById($shopId);
            if (null !== $shop) {
                $shops[] = $this->serializeShop($shop);
            }
        }

        usort($shops, static fn (array $a, array $b): int => strcmp($a['name'], $b['name']));

        return $this->json(['data' => $shops]);
    }

    /** @return list<Shop> */
    private function listManageableShops(User $user): array
    {
        if ($user->isPlatformOwner()) {
            return $this->shopRepository->findAllOrderedByName();
        }

        $tenantAccountId = $user->getTenantAccountId();
        if (null === $tenantAccountId) {
            return [];
        }

        return $this->shopRepository->findByTenantAccountId($tenantAccountId);
    }

    private function assertCanManageShop(User $user, Uuid $shopId): Shop
    {
        $shop = $this->shopRepository->findById($shopId);
        if (null === $shop) {
            throw $this->createNotFoundException('Boutique introuvable.');
        }

        if ($user->isPlatformOwner()) {
            return $shop;
        }

        $userTenantId = $user->getTenantAccountId();
        $shopTenantId = $shop->getTenantAccountId();

        if (null === $userTenantId || null === $shopTenantId || !$userTenantId->equals($shopTenantId)) {
            throw new AccessDeniedHttpException('Access denied for this shop.');
        }

        return $shop;
    }

    /** @return array<string, mixed> */
    private function serializeShop(Shop $shop): array
    {
        return [
            'id' => (string) $shop->getId(),
            'name' => $shop->getName(),
            'slug' => $shop->getSlug(),
            'status' => $shop->getStatus()->value,
            'currency' => $shop->getCurrency(),
            'address' => $shop->getAddress(),
            'phone' => $shop->getPhone(),
            'users_count' => count($this->userRepository->findByShopId($shop->getId())),
            'features' => $this->resolveFeaturesForShop($shop),
            'created_at' => $shop->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updated_at' => $shop->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }

    /** @return list<string>|null */
    private function resolveFeaturesForShop(Shop $shop): ?array
    {
        $tenantAccountId = $shop->getTenantAccountId();
        if (null === $tenantAccountId) {
            return null;
        }

        $account = $this->tenantAccountRepository->findById($tenantAccountId);
        if (null === $account) {
            return [];
        }

        return $this->entitlementResolver->getFeatures($account);
    }
}
