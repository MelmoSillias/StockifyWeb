<?php

namespace App\Catalog\Presentation\Api\Controller;

use App\Catalog\Domain\Repository\UnitOfMeasureRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/units-of-measure')]
final class UnitOfMeasureController extends AbstractController
{
    public function __construct(
        private readonly UnitOfMeasureRepositoryInterface $unitOfMeasureRepository,
    ) {
    }

    #[Route('', name: 'api_units_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $units = $this->unitOfMeasureRepository->findAllOrdered();

        return $this->json(array_map(static fn ($u) => [
            'id' => (string) $u->getId(),
            'code' => $u->getCode(),
            'label' => $u->getLabel(),
            'decimal_places' => $u->getDecimalPlaces(),
        ], $units));
    }
}
