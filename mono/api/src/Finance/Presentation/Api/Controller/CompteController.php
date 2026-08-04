<?php

namespace App\Finance\Presentation\Api\Controller;

use App\Finance\Application\Service\CompteService;
use App\Finance\Domain\Entity\Compte;
use App\Finance\Domain\Repository\CompteRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[Route('/api')]
final class CompteController extends AbstractController
{
    public function __construct(
        private readonly CompteRepositoryInterface $compteRepository,
        private readonly CompteService $compteService,
    ) {
    }

    #[Route('/comptes', name: 'api_comptes_list', methods: ['GET'])]
    #[IsGranted('finance.comptes.view')]
    public function list(): JsonResponse
    {
        return $this->json(array_map([$this, 'serialize'], $this->compteRepository->findAll()));
    }

    #[Route('/comptes/{id}', name: 'api_comptes_show', methods: ['GET'])]
    #[IsGranted('finance.comptes.view')]
    public function show(string $id): JsonResponse
    {
        return $this->json($this->serialize($this->getCompte($id)));
    }

    #[Route('/comptes', name: 'api_comptes_create', methods: ['POST'])]
    #[IsGranted('finance.comptes.manage')]
    public function create(Request $request): JsonResponse
    {
        try {
            $compte = $this->compteService->create($request->toArray());
        } catch (\InvalidArgumentException | \ValueError $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json($this->serialize($compte), Response::HTTP_CREATED);
    }

    #[Route('/comptes/{id}', name: 'api_comptes_update', methods: ['PUT'])]
    #[IsGranted('finance.comptes.manage')]
    public function update(string $id, Request $request): JsonResponse
    {
        try {
            $compte = $this->compteService->update($this->getCompte($id), $request->toArray());
        } catch (\InvalidArgumentException | \ValueError $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json($this->serialize($compte));
    }

    #[Route('/comptes/{id}/solde', name: 'api_comptes_balance', methods: ['GET'])]
    #[IsGranted('finance.comptes.view')]
    public function balance(string $id): JsonResponse
    {
        $compte = $this->getCompte($id);

        return $this->json([
            'compte_id' => (string) $compte->getId(),
            'balance' => $this->compteService->computeBalance($compte->getId()),
        ]);
    }

    private function getCompte(string $id): Compte
    {
        $compte = $this->compteRepository->findById(Uuid::fromString($id));
        if (null === $compte) {
            throw $this->createNotFoundException();
        }

        return $compte;
    }

    /** @return array<string, mixed> */
    private function serialize(Compte $compte): array
    {
        return [
            'id' => (string) $compte->getId(),
            'name' => $compte->getName(),
            'type' => $compte->getType()->value,
            'is_default' => $compte->isDefault(),
            'is_active' => $compte->isActive(),
            'balance' => $this->compteService->computeBalance($compte->getId()),
            'created_at' => $compte->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updated_at' => $compte->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}
