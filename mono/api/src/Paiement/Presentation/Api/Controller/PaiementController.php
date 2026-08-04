<?php

namespace App\Paiement\Presentation\Api\Controller;

use App\Paiement\Application\Service\PaiementSerializer;
use App\Paiement\Application\Service\PaiementService;
use App\Paiement\Domain\Entity\Paiement;
use App\Paiement\Domain\Repository\PaiementRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[Route('/api')]
final class PaiementController extends AbstractController
{
    public function __construct(
        private readonly PaiementService $paiementService,
        private readonly PaiementRepositoryInterface $paiementRepository,
        private readonly PaiementSerializer $paiementSerializer,
    ) {
    }

    #[Route('/paiements', name: 'api_paiements_list', methods: ['GET'])]
    #[IsGranted('paiement.paiements.view')]
    public function list(): JsonResponse
    {
        return $this->json(array_map([$this, 'serialize'], $this->paiementRepository->findAll()));
    }

    #[Route('/paiements/{id}', name: 'api_paiements_show', methods: ['GET'])]
    #[IsGranted('paiement.paiements.view')]
    public function show(string $id): JsonResponse
    {
        $paiement = $this->paiementRepository->findById(Uuid::fromString($id));
        if (null === $paiement) {
            throw $this->createNotFoundException();
        }

        return $this->json($this->serialize($paiement));
    }

    #[Route('/paiements', name: 'api_paiements_create', methods: ['POST'])]
    #[IsGranted('paiement.paiements.create')]
    public function create(Request $request): JsonResponse
    {
        try {
            $paiement = $this->paiementService->enregistrer($request->toArray());
        } catch (\InvalidArgumentException | \ValueError $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json($this->serialize($paiement), Response::HTTP_CREATED);
    }

    #[Route('/paiements/{id}/cancel', name: 'api_paiements_cancel', methods: ['POST'])]
    #[IsGranted('paiement.paiements.cancel')]
    public function cancel(string $id): JsonResponse
    {
        $paiement = $this->paiementRepository->findById(Uuid::fromString($id));
        if (null === $paiement) {
            throw $this->createNotFoundException();
        }

        try {
            $this->paiementService->annuler($paiement);
        } catch (\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_CONFLICT);
        }

        return $this->json($this->serialize($paiement));
    }

    /** @return array<string, mixed> */
    private function serialize(Paiement $paiement): array
    {
        return $this->paiementSerializer->serialize($paiement);
    }
}
