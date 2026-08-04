<?php

namespace App\Fournisseur\Presentation\Api\Controller;

use App\Fournisseur\Application\Service\AchatsService;
use App\Fournisseur\Application\Service\DetteDetailMapper;
use App\Fournisseur\Domain\Enum\DetteFilterStatus;
use App\Fournisseur\Domain\Repository\DetteFournisseurRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[Route('/api')]
final class DetteFournisseurController extends AbstractController
{
    public function __construct(
        private readonly DetteDetailMapper $detteDetailMapper,
        private readonly DetteFournisseurRepositoryInterface $detteRepository,
        private readonly AchatsService $achatsService,
    ) {
    }

    #[Route('/dettes-fournisseur', name: 'api_dettes_fournisseur_list', methods: ['GET'])]
    #[IsGranted('fournisseur.dettes.view')]
    public function list(Request $request): JsonResponse
    {
        $fournisseurId = $request->query->get('fournisseur_id');
        $status = $this->resolveStatus($request->query->get('status'));

        try {
            $fournisseurUuid = null !== $fournisseurId && '' !== $fournisseurId
                ? Uuid::fromString((string) $fournisseurId)
                : null;
        } catch (\InvalidArgumentException) {
            return $this->json(['error' => 'Invalid fournisseur_id.'], Response::HTTP_BAD_REQUEST);
        }

        $items = $this->detteDetailMapper->mapAll($fournisseurUuid, $status);

        return $this->json(array_map(
            static fn ($item) => $item->toArray(),
            $items,
        ));
    }

    #[Route('/dettes-fournisseur/{id}', name: 'api_dettes_fournisseur_show', methods: ['GET'])]
    #[IsGranted('fournisseur.dettes.view')]
    public function show(string $id): JsonResponse
    {
        $dette = $this->detteRepository->findById(Uuid::fromString($id));
        if (null === $dette) {
            throw $this->createNotFoundException();
        }

        return $this->json($this->detteDetailMapper->map($dette)->toArray());
    }

    #[Route('/dettes-fournisseur', name: 'api_dettes_fournisseur_create', methods: ['POST'])]
    #[IsGranted('fournisseur.dettes.create')]
    public function create(Request $request): JsonResponse
    {
        try {
            $dette = $this->achatsService->creerDetteManuelle($request->toArray());
        } catch (\InvalidArgumentException | \ValueError $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json($this->detteDetailMapper->map($dette)->toArray(), Response::HTTP_CREATED);
    }

    private function resolveStatus(mixed $value): DetteFilterStatus
    {
        if (null === $value || '' === $value) {
            return DetteFilterStatus::Open;
        }

        return DetteFilterStatus::from((string) $value);
    }
}
