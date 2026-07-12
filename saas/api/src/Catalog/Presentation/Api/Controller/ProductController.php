<?php

namespace App\Catalog\Presentation\Api\Controller;

use App\Catalog\Application\Command\CreateProduct\CreateProductCommand;
use App\Catalog\Application\Command\CreateProduct\CreateProductHandler;
use App\Catalog\Application\Command\UpdateProduct\UpdateProductCommand;
use App\Catalog\Application\Command\UpdateProduct\UpdateProductHandler;
use App\Catalog\Domain\Repository\ProductRepositoryInterface;
use App\Catalog\Presentation\Api\Serializer\ProductSerializer;
use App\SharedKernel\Infrastructure\Tenant\TenantContextHolder;
use App\SharedKernel\Security\ShopPermissionVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[Route('/api/shops/{shopId}')]
final class ProductController extends AbstractController
{
    public function __construct(
        private readonly TenantContextHolder $tenantContextHolder,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly CreateProductHandler $createProductHandler,
        private readonly UpdateProductHandler $updateProductHandler,
        private readonly ProductSerializer $serializer,
    ) {
    }

    #[Route('/products', name: 'api_products_list', methods: ['GET'])]
    #[IsGranted(ShopPermissionVoter::VIEW)]
    public function list(): JsonResponse
    {
        $products = $this->productRepository->findByShop($this->tenantContextHolder->get()->getShop()->getId());

        return $this->json($this->serializer->serializeList($products));
    }

    #[Route('/products', name: 'api_products_create', methods: ['POST'])]
    #[IsGranted(ShopPermissionVoter::MANAGE_CATALOG)]
    public function create(Request $request): JsonResponse
    {
        $data = $request->toArray();
        try {
            $product = $this->createProductHandler->handle(new CreateProductCommand(
                name: $data['name'] ?? '',
                reference: $data['reference'] ?? null,
                description: $data['description'] ?? null,
                categoryId: $data['category_id'] ?? null,
            ));
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json($this->serializer->serialize($product), Response::HTTP_CREATED);
    }

    #[Route('/products/{id}', name: 'api_products_show', methods: ['GET'])]
    #[IsGranted(ShopPermissionVoter::VIEW)]
    public function show(string $id): JsonResponse
    {
        return $this->json($this->serializer->serialize($this->getProduct($id)));
    }

    #[Route('/products/{id}', name: 'api_products_update', methods: ['PUT'])]
    #[IsGranted(ShopPermissionVoter::MANAGE_CATALOG)]
    public function update(string $id, Request $request): JsonResponse
    {
        $data = $request->toArray();
        try {
            $product = $this->updateProductHandler->handle(new UpdateProductCommand(
                productId: $id,
                name: $data['name'] ?? null,
                reference: array_key_exists('reference', $data) ? $data['reference'] : null,
                description: array_key_exists('description', $data) ? $data['description'] : null,
                categoryId: $data['category_id'] ?? null,
            ));
        } catch (\DomainException) {
            throw $this->createNotFoundException();
        }

        return $this->json($this->serializer->serialize($product));
    }

    #[Route('/products/{id}', name: 'api_products_delete', methods: ['DELETE'])]
    #[IsGranted(ShopPermissionVoter::MANAGE_CATALOG)]
    public function delete(string $id): JsonResponse
    {
        $product = $this->getProduct($id);
        $this->productRepository->remove($product);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    private function getProduct(string $id): \App\Catalog\Domain\Entity\Product
    {
        $product = $this->productRepository->findById(Uuid::fromString($id));
        if (null === $product || !$product->getShopId()->equals($this->tenantContextHolder->get()->getShop()->getId())) {
            throw $this->createNotFoundException();
        }

        return $product;
    }
}
