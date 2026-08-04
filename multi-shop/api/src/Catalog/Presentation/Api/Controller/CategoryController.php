<?php

namespace App\Catalog\Presentation\Api\Controller;

use App\Catalog\Application\Query\ListCategoriesCatalog\ListCategoriesCatalogHandler;
use App\Catalog\Application\Query\ListCategoriesCatalog\ListCategoriesCatalogQuery;
use App\Catalog\Domain\Entity\ProductCategory;
use App\Catalog\Domain\Repository\ProductCategoryRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[Route('/api')]
final class CategoryController extends AbstractController
{
    public function __construct(
        private readonly ProductCategoryRepositoryInterface $categoryRepository,
        private readonly ListCategoriesCatalogHandler $listCategoriesCatalogHandler,
    ) {
    }

    #[Route('/categories', name: 'api_categories_list', methods: ['GET'])]
    #[IsGranted('catalog.categories.view')]
    public function list(): JsonResponse
    {
        return $this->json($this->listCategoriesCatalogHandler->handle(new ListCategoriesCatalogQuery()));
    }

    #[Route('/categories', name: 'api_categories_create', methods: ['POST'])]
    #[IsGranted('catalog.categories.manage')]
    public function create(Request $request): JsonResponse
    {
        $data = $request->toArray();
        if (empty($data['name'])) {
            return $this->json(['error' => 'name is required'], Response::HTTP_BAD_REQUEST);
        }

        $parent = null;
        if (!empty($data['parent_id'])) {
            $parent = $this->categoryRepository->findById(Uuid::fromString($data['parent_id']));
        }

        $category = new ProductCategory($data['name'], $parent);
        $this->categoryRepository->save($category);

        return $this->json($this->serializeCategory($category), Response::HTTP_CREATED);
    }

    #[Route('/categories/{id}', name: 'api_categories_update', methods: ['PUT'])]
    #[IsGranted('catalog.categories.manage')]
    public function update(string $id, Request $request): JsonResponse
    {
        $category = $this->getCategory($id);
        $data = $request->toArray();
        if (!empty($data['name'])) {
            $category->setName($data['name']);
        }
        $this->categoryRepository->save($category);

        return $this->json($this->serializeCategory($category));
    }

    #[Route('/categories/{id}', name: 'api_categories_delete', methods: ['DELETE'])]
    #[IsGranted('catalog.categories.manage')]
    public function delete(string $id): JsonResponse
    {
        $category = $this->getCategory($id);
        $this->categoryRepository->remove($category);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    private function getCategory(string $id): ProductCategory
    {
        $category = $this->categoryRepository->findById(Uuid::fromString($id));
        if (null === $category) {
            throw $this->createNotFoundException();
        }

        return $category;
    }

    /** @return array<string, mixed> */
    private function serializeCategory(ProductCategory $category): array
    {
        return [
            'id' => (string) $category->getId(),
            'name' => $category->getName(),
            'parent_id' => $category->getParent() ? (string) $category->getParent()->getId() : null,
            'sort_order' => $category->getSortOrder(),
            'status' => $category->getStatus()->value,
            'product_count' => 0,
        ];
    }
}
