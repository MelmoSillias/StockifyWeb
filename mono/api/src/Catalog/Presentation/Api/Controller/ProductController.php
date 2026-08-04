<?php

namespace App\Catalog\Presentation\Api\Controller;

use App\Catalog\Application\Command\RegisterProduct\RegisterProductCommand;
use App\Catalog\Application\Command\RegisterProduct\RegisterProductHandler;
use App\Catalog\Application\Command\UpdateProduct\UpdateProductCommand;
use App\Catalog\Application\Command\UpdateProduct\UpdateProductHandler;
use App\Catalog\Application\Query\ListProductsCatalog\ListProductsCatalogHandler;
use App\Catalog\Application\Query\ListProductsCatalog\ListProductsCatalogQuery;
use App\Catalog\Domain\Entity\ProductVariant;
use App\Catalog\Domain\Repository\ProductRepositoryInterface;
use App\Catalog\Presentation\Api\Serializer\ProductSerializer;
use App\Inventory\Domain\Entity\StockLot;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[Route('/api')]
final class ProductController extends AbstractController
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly RegisterProductHandler $registerProductHandler,
        private readonly UpdateProductHandler $updateProductHandler,
        private readonly ListProductsCatalogHandler $listProductsCatalogHandler,
        private readonly ProductSerializer $serializer,
    ) {
    }

    #[Route('/products', name: 'api_products_list', methods: ['GET'])]
    #[IsGranted('catalog.products.view')]
    public function list(): JsonResponse
    {
        return $this->json($this->listProductsCatalogHandler->handle(new ListProductsCatalogQuery()));
    }

    #[Route('/products', name: 'api_products_create', methods: ['POST'])]
    #[IsGranted('catalog.products.create')]
    public function create(Request $request): JsonResponse
    {
        $data = $request->toArray();
        $lots = $data['lots'] ?? [];
        if (!\is_array($lots)) {
            return $this->json(['error' => 'lots must be an array'], Response::HTTP_BAD_REQUEST);
        }

        $variant = $data['variant'] ?? null;
        if (null !== $variant && !\is_array($variant)) {
            return $this->json(['error' => 'variant must be an object'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $result = $this->registerProductHandler->handle(new RegisterProductCommand(
                name: $data['name'] ?? '',
                reference: $data['reference'] ?? null,
                description: $data['description'] ?? null,
                categoryId: $data['category_id'] ?? null,
                variant: $variant,
                lots: array_values($lots),
            ));
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\ValueError $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        $payload = $this->serializer->serialize($result['product']);
        if ($result['variant'] instanceof ProductVariant) {
            $payload['variant'] = $this->serializeVariant($result['variant']);
        }
        if ([] !== $result['lots']) {
            $payload['lots'] = array_map($this->serializeLot(...), $result['lots']);
        }

        return $this->json($payload, Response::HTTP_CREATED);
    }

    #[Route('/products/{id}', name: 'api_products_show', methods: ['GET'])]
    #[IsGranted('catalog.products.view')]
    public function show(string $id): JsonResponse
    {
        return $this->json($this->serializer->serialize($this->getProduct($id)));
    }

    #[Route('/products/{id}', name: 'api_products_update', methods: ['PUT'])]
    #[IsGranted('catalog.products.update')]
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
    #[IsGranted('catalog.products.delete')]
    public function delete(string $id): JsonResponse
    {
        $product = $this->getProduct($id);
        $this->productRepository->remove($product);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    private function getProduct(string $id): \App\Catalog\Domain\Entity\Product
    {
        $product = $this->productRepository->findById(Uuid::fromString($id));
        if (null === $product) {
            throw $this->createNotFoundException();
        }

        return $product;
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

    /** @return array<string, mixed> */
    private function serializeLot(StockLot $lot): array
    {
        return [
            'id' => (string) $lot->getId(),
            'variant_id' => (string) $lot->getVariant()->getId(),
            'reference' => $lot->getReference() ?? $lot->getVariant()->getSku(),
            'quantity_initial' => $lot->getQuantityInitial(),
            'quantity_remaining' => $lot->getQuantityRemaining(),
            'unit_cost' => $lot->getUnitCost(),
            'received_at' => $lot->getReceivedAt()->format(\DateTimeInterface::ATOM),
            'expiry_date' => $lot->getExpiryDate()?->format('Y-m-d'),
            'supplier_ref' => $lot->getSupplierRef(),
        ];
    }
}
