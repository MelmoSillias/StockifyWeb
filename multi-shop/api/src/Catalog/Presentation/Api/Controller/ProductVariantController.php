<?php

namespace App\Catalog\Presentation\Api\Controller;

use App\Catalog\Application\Command\CreateProductVariant\CreateProductVariantCommand;
use App\Catalog\Application\Command\CreateProductVariant\CreateProductVariantHandler;
use App\Catalog\Application\Query\ListVariantsCatalog\ListVariantsCatalogHandler;
use App\Catalog\Application\Query\ListVariantsCatalog\ListVariantsCatalogQuery;
use App\Catalog\Domain\Entity\Product;
use App\Catalog\Domain\Entity\ProductVariant;
use App\Catalog\Domain\Enum\SaleMode;
use App\Catalog\Domain\Repository\ProductRepositoryInterface;
use App\Catalog\Domain\Repository\ProductVariantRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[Route('/api')]
final class ProductVariantController extends AbstractController
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly ProductVariantRepositoryInterface $variantRepository,
        private readonly CreateProductVariantHandler $createVariantHandler,
        private readonly ListVariantsCatalogHandler $listVariantsCatalogHandler,
    ) {
    }

    #[Route('/variants', name: 'api_variants_catalog', methods: ['GET'])]
    #[IsGranted('catalog.products.view')]
    public function catalog(): JsonResponse
    {
        return $this->json($this->listVariantsCatalogHandler->handle(new ListVariantsCatalogQuery()));
    }

    #[Route('/products/{productId}/variants', name: 'api_variants_list', methods: ['GET'])]
    #[IsGranted('catalog.products.view')]
    public function list(string $productId): JsonResponse
    {
        $product = $this->getProduct($productId);
        $variants = $this->variantRepository->findByProduct($product);

        return $this->json(array_map([$this, 'serializeVariant'], $variants));
    }

    #[Route('/products/{productId}/variants', name: 'api_variants_create', methods: ['POST'])]
    #[IsGranted('catalog.variants.manage')]
    public function create(string $productId, Request $request): JsonResponse
    {
        $product = $this->getProduct($productId);
        $data = $request->toArray();

        foreach (['sku', 'unit_of_measure_id', 'sale_mode'] as $field) {
            if (empty($data[$field])) {
                return $this->json(['error' => sprintf('%s is required', $field)], Response::HTTP_BAD_REQUEST);
            }
        }

        try {
            $variant = $this->createVariantHandler->handle(new CreateProductVariantCommand(
                product: $product,
                sku: $data['sku'],
                unitOfMeasureId: $data['unit_of_measure_id'],
                saleMode: SaleMode::from($data['sale_mode']),
                defaultPrice: !empty($data['default_price']) ? (string) $data['default_price'] : null,
                alertThreshold: array_key_exists('alert_threshold', $data) && null !== $data['alert_threshold']
                    ? (string) $data['alert_threshold'] : null,
            ));
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json($this->serializeVariant($variant), Response::HTTP_CREATED);
    }

    #[Route('/variants/{id}', name: 'api_variants_show', methods: ['GET'])]
    #[IsGranted('catalog.products.view')]
    public function show(string $id): JsonResponse
    {
        return $this->json($this->serializeVariant($this->getVariant($id)));
    }

    #[Route('/variants/{id}', name: 'api_variants_update', methods: ['PUT'])]
    #[IsGranted('catalog.variants.manage')]
    public function update(string $id, Request $request): JsonResponse
    {
        $variant = $this->getVariant($id);
        $data = $request->toArray();
        if (!empty($data['default_price'])) {
            $variant->setDefaultPrice((string) $data['default_price']);
        }
        if (array_key_exists('alert_threshold', $data)) {
            $variant->setAlertThreshold(null !== $data['alert_threshold'] ? (string) $data['alert_threshold'] : null);
        }
        $this->variantRepository->save($variant);

        return $this->json($this->serializeVariant($variant));
    }

    #[Route('/variants/{id}', name: 'api_variants_delete', methods: ['DELETE'])]
    #[IsGranted('catalog.variants.manage')]
    public function delete(string $id): JsonResponse
    {
        $variant = $this->getVariant($id);
        $this->variantRepository->remove($variant);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    private function getProduct(string $id): Product
    {
        $product = $this->productRepository->findById(Uuid::fromString($id));
        if (null === $product) {
            throw $this->createNotFoundException();
        }

        return $product;
    }

    private function getVariant(string $id): ProductVariant
    {
        $variant = $this->variantRepository->findById(Uuid::fromString($id));
        if (null === $variant) {
            throw $this->createNotFoundException();
        }

        return $variant;
    }

    /** @return array<string, mixed> */
    private function serializeVariant(ProductVariant $variant): array
    {
        return [
            'id' => (string) $variant->getId(),
            'product_id' => (string) $variant->getProduct()->getId(),
            'sku' => $variant->getSku(),
            'unit_of_measure_id' => (string) $variant->getUnitOfMeasure()->getId(),
            'sale_mode' => $variant->getSaleMode()->value,
            'default_price' => $variant->getDefaultPrice(),
            'alert_threshold' => $variant->getAlertThreshold(),
            'status' => $variant->getStatus()->value,
        ];
    }
}
