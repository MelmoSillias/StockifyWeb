<?php

namespace App\Fournisseur\Presentation\Api\Controller;

use App\Fournisseur\Application\Service\AchatsService;
use App\Fournisseur\Domain\Entity\CommandeFournisseur;
use App\Fournisseur\Domain\Repository\CommandeFournisseurRepositoryInterface;
use App\Integration\Application\Service\TenantFeatureGuard;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[Route('/api')]
final class CommandeFournisseurController extends AbstractController
{
    public function __construct(
        private readonly AchatsService $achatsService,
        private readonly CommandeFournisseurRepositoryInterface $commandeRepository,
        private readonly TenantFeatureGuard $tenantFeatureGuard,
    ) {
    }

    #[Route('/commandes-fournisseur', name: 'api_commandes_fournisseur_list', methods: ['GET'])]
    #[IsGranted('fournisseur.commandes.view')]
    public function list(): JsonResponse
    {
        $this->tenantFeatureGuard->assertFeatureForActiveShop('stockify.suppliers');

        return $this->json(array_map(
            [$this, 'serialize'],
            $this->commandeRepository->findAll(),
        ));
    }

    #[Route('/commandes-fournisseur/{id}', name: 'api_commandes_fournisseur_show', methods: ['GET'])]
    #[IsGranted('fournisseur.commandes.view')]
    public function show(string $id): JsonResponse
    {
        return $this->json($this->serialize($this->getCommande($id)));
    }

    #[Route('/commandes-fournisseur', name: 'api_commandes_fournisseur_create', methods: ['POST'])]
    #[IsGranted('fournisseur.commandes.create')]
    public function create(Request $request): JsonResponse
    {
        $this->tenantFeatureGuard->assertFeatureForActiveShop('stockify.suppliers');

        try {
            $commande = $this->achatsService->creer($request->toArray());
        } catch (\InvalidArgumentException | \ValueError $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json($this->serialize($commande), Response::HTTP_CREATED);
    }

    #[Route('/commandes-fournisseur/{id}/confirm', name: 'api_commandes_fournisseur_confirm', methods: ['POST'])]
    #[IsGranted('fournisseur.commandes.confirm')]
    public function confirm(string $id, Request $request): JsonResponse
    {
        $commande = $this->getCommande($id);
        $data = '' !== $request->getContent() ? $request->toArray() : [];
        $expectedAt = null;
        if (!empty($data['expected_at'])) {
            try {
                $expectedAt = new \DateTimeImmutable((string) $data['expected_at']);
            } catch (\Exception) {
                return $this->json(['error' => 'Invalid expected_at date.'], Response::HTTP_BAD_REQUEST);
            }
        }

        try {
            $this->achatsService->confirmer($commande, $expectedAt);
        } catch (\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_CONFLICT);
        }

        return $this->json($this->serialize($commande));
    }

    #[Route('/commandes-fournisseur/{id}/recevoir', name: 'api_commandes_fournisseur_recevoir', methods: ['POST'])]
    #[IsGranted('fournisseur.commandes.receive')]
    public function recevoir(string $id, Request $request): JsonResponse
    {
        $commande = $this->getCommande($id);

        try {
            $this->achatsService->recevoir($commande, $request->toArray());
        } catch (\InvalidArgumentException | \ValueError $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_CONFLICT);
        }

        return $this->json($this->serialize($commande));
    }

    #[Route('/commandes-fournisseur/{id}/cancel', name: 'api_commandes_fournisseur_cancel', methods: ['POST'])]
    #[IsGranted('fournisseur.commandes.cancel')]
    public function cancel(string $id): JsonResponse
    {
        $commande = $this->getCommande($id);

        try {
            $this->achatsService->annuler($commande);
        } catch (\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_CONFLICT);
        }

        return $this->json($this->serialize($commande));
    }

    private function getCommande(string $id): CommandeFournisseur
    {
        $this->tenantFeatureGuard->assertFeatureForActiveShop('stockify.suppliers');

        $commande = $this->commandeRepository->findById(Uuid::fromString($id));
        if (null === $commande) {
            throw $this->createNotFoundException();
        }

        return $commande;
    }

    /** @return array<string, mixed> */
    private function serialize(CommandeFournisseur $commande): array
    {
        return [
            'id' => (string) $commande->getId(),
            'reference' => $commande->getReference(),
            'fournisseur_id' => (string) $commande->getFournisseurId(),
            'status' => $commande->getStatus()->value,
            'total_amount' => $commande->getTotalAmount(),
            'deposit_paid' => $commande->getDepositPaid(),
            'confirmed_at' => $commande->getConfirmedAt()?->format(\DateTimeInterface::ATOM),
            'cancelled_at' => $commande->getCancelledAt()?->format(\DateTimeInterface::ATOM),
            'expected_at' => $commande->getExpectedAt()?->format('Y-m-d'),
            'received_at' => $commande->getReceivedAt()?->format(\DateTimeInterface::ATOM),
            'created_at' => $commande->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'lines' => array_map(static fn ($line) => [
                'variant_id' => (string) $line->getVariantId(),
                'label' => $line->getLabel(),
                'quantity' => $line->getQuantity(),
                'unit_cost' => $line->getUnitCost(),
                'line_total' => $line->getLineTotal(),
            ], $commande->getLines()->toArray()),
        ];
    }
}
