<?php

namespace App\Impression\Application\Resolver;

use App\Commerce\Application\Service\VenteDetailMapper;
use App\Commerce\Domain\Repository\VenteRepositoryInterface;
use App\Impression\Domain\Enum\DocumentType;
use App\Impression\Domain\ValueObject\DocumentRequest;
use Symfony\Component\Uid\Uuid;

final class VenteTicketDocumentDataResolver implements DocumentDataResolverInterface
{
    public function __construct(
        private readonly VenteRepositoryInterface $venteRepository,
        private readonly VenteDetailMapper $venteDetailMapper,
    ) {
    }

    public function supports(DocumentType $type): bool
    {
        return DocumentType::VenteTicket === $type;
    }

    public function resolve(DocumentRequest $request): array
    {
        $vente = $this->venteRepository->findById(Uuid::fromString((string) $request->entityId));
        if (null === $vente) {
            throw new \DomainException('Vente not found.');
        }

        $detail = $this->venteDetailMapper->map($vente)->toArray();
        $buyer = $detail['acheteur']['client_name'] ?? $detail['acheteur']['anonymous_info'] ?? '—';

        return [
            'filename' => $detail['reference'],
            'title' => 'Ticket de vente',
            'document_number' => $detail['reference'],
            'issued_at' => (new \DateTimeImmutable($detail['created_at']))->format('d/m/Y H:i'),
            'buyer_name' => $buyer,
            'total_amount' => $detail['total_amount'],
            'paid_amount' => $detail['paid_amount'],
            'balance' => $detail['balance'],
            'payment_status' => $detail['payment_status'],
            'facture_numero' => $detail['facture']['numero'] ?? null,
            'lines' => $detail['lines'],
            'paiements' => array_map(static fn (array $p) => [
                'reference' => $p['reference'],
                'amount' => $p['amount'],
                'method' => $p['method'],
                'paid_at' => (new \DateTimeImmutable($p['paid_at']))->format('d/m/Y H:i'),
            ], $detail['paiements']),
            'is_cancelled' => null !== $detail['cancelled_at'],
        ];
    }
}
