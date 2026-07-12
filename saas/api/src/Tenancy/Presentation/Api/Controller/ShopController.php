<?php

namespace App\Tenancy\Presentation\Api\Controller;

use App\IdentityAccess\Domain\Entity\User;
use App\Tenancy\Domain\Repository\AccountMemberRepositoryInterface;
use App\Tenancy\Domain\Repository\ShopRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

#[Route('/api/shops')]
final class ShopController extends AbstractController
{
    public function __construct(
        private readonly ShopRepositoryInterface $shopRepository,
        private readonly AccountMemberRepositoryInterface $accountMemberRepository,
    ) {
    }

    #[Route('/{id}', name: 'api_shops_show', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        if (!Uuid::isValid($id)) {
            throw $this->createNotFoundException();
        }

        $shop = $this->shopRepository->findById(Uuid::fromString($id));
        if (null === $shop) {
            throw $this->createNotFoundException();
        }

        /** @var User $user */
        $user = $this->getUser();
        if (null === $this->accountMemberRepository->findActiveMembership($shop->getAccount(), $user)) {
            throw $this->createAccessDeniedException();
        }

        return $this->json([
            'id' => (string) $shop->getId(),
            'account_id' => (string) $shop->getAccount()->getId(),
            'name' => $shop->getName(),
            'slug' => $shop->getSlug(),
            'status' => $shop->getStatus()->value,
            'currency' => $shop->getCurrency(),
            'address' => $shop->getAddress(),
            'phone' => $shop->getPhone(),
        ]);
    }
}
