<?php

namespace App\Facturation\Presentation\Api\Controller;

use App\Commerce\Domain\Repository\VenteRepositoryInterface;
use App\Facturation\Domain\Entity\Facture;
use App\Facturation\Domain\Repository\FactureRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[Route('/api')]
final class FactureController extends AbstractController
{
    public function __construct(
        private readonly FactureRepositoryInterface $factureRepository,
        private readonly VenteRepositoryInterface $venteRepository,
    ) {
    }

    #[Route('/factures', name: 'api_factures_list', methods: ['GET'])]
    #[IsGranted('facturation.factures.view')]
    public function list(): JsonResponse
    {
        return $this->json(array_map([$this, 'serialize'], $this->factureRepository->findAll()));
    }

    #[Route('/factures/{id}', name: 'api_factures_show', methods: ['GET'])]
    #[IsGranted('facturation.factures.view')]
    public function show(string $id): JsonResponse
    {
        $facture = $this->factureRepository->findById(Uuid::fromString($id));
        if (null === $facture) {
            throw $this->createNotFoundException();
        }

        return $this->json($this->serialize($facture));
    }

    /** @return array<string, mixed> */
    private function serialize(Facture $facture): array
    {
        return [
            'id' => (string) $facture->getId(),
            'numero' => $facture->getNumero(),
            'vente_id' => $facture->getVenteId() ? (string) $facture->getVenteId() : null,
            'commande_id' => $facture->getCommandeId() ? (string) $facture->getCommandeId() : null,
            'source_reference' => $facture->getSourceReference(),
            'origin' => null !== $facture->getVenteId() ? 'vente' : 'commande',
            'acheteur' => [
                'client_id' => $facture->getClientId() ? (string) $facture->getClientId() : null,
                'anonymous_info' => $facture->getAnonymousInfo(),
            ],
            'total_amount' => $facture->getTotalAmount(),
            'issued_at' => $facture->getIssuedAt()->format(\DateTimeInterface::ATOM),
            'is_creance' => $facture->isCreance(),
            'credit_closed_at' => $facture->getCreditClosedAt()?->format(\DateTimeInterface::ATOM),
            'is_cancelled' => $this->isSourceCancelled($facture),
            'lines' => array_map(static fn ($line) => [
                'variant_id' => (string) $line->getVariantId(),
                'label' => $line->getLabel(),
                'quantity' => $line->getQuantity(),
                'unit_price' => $line->getUnitPrice(),
                'line_total' => $line->getLineTotal(),
            ], $facture->getLines()->toArray()),
        ];
    }

    private function isSourceCancelled(Facture $facture): bool
    {
        $venteId = $facture->getVenteId();
        if (null === $venteId) {
            return false;
        }

        $vente = $this->venteRepository->findById($venteId);

        return null !== $vente && $vente->isCancelled();
    }
}
