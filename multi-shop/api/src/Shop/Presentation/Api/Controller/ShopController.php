<?php

namespace App\Shop\Presentation\Api\Controller;

use App\AccessAudit\Application\Service\UserManagementService;
use App\IdentityAccess\Domain\Entity\User;
use App\IdentityAccess\Domain\Repository\UserRepositoryInterface;
use App\Shop\Application\Command\CreateShop\CreateShopCommand;
use App\Shop\Application\Command\CreateShop\CreateShopHandler;
use App\Shop\Application\Command\CreateShopUser\CreateShopUserCommand;
use App\Shop\Application\Command\CreateShopUser\CreateShopUserHandler;
use App\Shop\Application\Command\DeleteShop\DeleteShopCommand;
use App\Shop\Application\Command\DeleteShop\DeleteShopHandler;
use App\Shop\Application\Command\UpdateShop\UpdateShopCommand;
use App\Shop\Application\Command\UpdateShop\UpdateShopHandler;
use App\Shop\Domain\Entity\Shop;
use App\Shop\Domain\Repository\ShopRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[Route('/api')]
final class ShopController extends AbstractController
{
    public function __construct(
        private readonly ShopRepositoryInterface $shopRepository,
        private readonly CreateShopHandler $createShopHandler,
        private readonly UpdateShopHandler $updateShopHandler,
        private readonly DeleteShopHandler $deleteShopHandler,
        private readonly CreateShopUserHandler $createShopUserHandler,
        private readonly UserRepositoryInterface $userRepository,
        private readonly UserManagementService $userManagementService,
    ) {
    }

    #[Route('/shops', name: 'api_shops_list', methods: ['GET'])]
    #[IsGranted('platform.shops.view')]
    public function list(): JsonResponse
    {
        return $this->json([
            'data' => array_map([$this, 'serializeShop'], $this->shopRepository->findAllOrderedByName()),
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

        try {
            $shop = $this->createShopHandler->handle(CreateShopCommand::fromPayload($payload));
        } catch (\InvalidArgumentException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
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
        $shopId = Uuid::fromString($id);
        $users = $this->userRepository->findByShopId($shopId);

        return $this->json([
            'data' => array_map(
                fn (User $user) => $this->userManagementService->serializeUser($user),
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

        try {
            $result = $this->createShopUserHandler->handle(
                CreateShopUserCommand::fromPayload(Uuid::fromString($id), $payload),
            );
        } catch (\InvalidArgumentException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json([
            'data' => $this->userManagementService->serializeUser($result['user']),
            'generated_password' => $result['generated_password'],
        ], Response::HTTP_CREATED);
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

        if (null !== $user->getTenantAccountId()) {
            return $this->json([
                'data' => array_map(
                    [$this, 'serializeShop'],
                    $this->shopRepository->findByTenantAccountId($user->getTenantAccountId()),
                ),
            ]);
        }

        if (null === $user->getShopId()) {
            return $this->json(['data' => []]);
        }

        $shop = $this->shopRepository->findById($user->getShopId());

        return $this->json([
            'data' => null !== $shop ? [$this->serializeShop($shop)] : [],
        ]);
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
            'created_at' => $shop->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updated_at' => $shop->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}
