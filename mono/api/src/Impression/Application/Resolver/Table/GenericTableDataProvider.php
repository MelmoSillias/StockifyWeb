<?php

namespace App\Impression\Application\Resolver\Table;

use App\AccessAudit\Application\Service\UserManagementService;
use App\Catalog\Domain\Repository\ProductRepositoryInterface;
use App\Client\Domain\Repository\ClientRepositoryInterface;
use App\Commerce\Application\Service\AcheteurPresenter;
use App\Commerce\Domain\Repository\CommandeRepositoryInterface;
use App\Commerce\Domain\Repository\VenteRepositoryInterface;
use App\Facturation\Domain\Repository\FactureRepositoryInterface;
use App\Finance\Domain\Repository\CompteRepositoryInterface;
use App\Finance\Domain\Repository\TransactionRepositoryInterface;
use App\Fournisseur\Domain\Enum\DetteFilterStatus;
use App\Fournisseur\Domain\Repository\DetteFournisseurRepositoryInterface;
use App\Fournisseur\Domain\Repository\FournisseurRepositoryInterface;
use App\IdentityAccess\Domain\Enum\UserStatus;
use App\Impression\Domain\Enum\TableType;
use App\Inventory\Domain\Repository\StockMovementRepositoryInterface;
use App\Paiement\Application\Service\PaiementSerializer;
use App\Paiement\Domain\Repository\PaiementRepositoryInterface;

final class GenericTableDataProvider implements TableDataProviderInterface
{
    public function __construct(
        private readonly VenteRepositoryInterface $venteRepository,
        private readonly CommandeRepositoryInterface $commandeRepository,
        private readonly PaiementRepositoryInterface $paiementRepository,
        private readonly PaiementSerializer $paiementSerializer,
        private readonly ClientRepositoryInterface $clientRepository,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly TransactionRepositoryInterface $transactionRepository,
        private readonly CompteRepositoryInterface $compteRepository,
        private readonly StockMovementRepositoryInterface $stockMovementRepository,
        private readonly FactureRepositoryInterface $factureRepository,
        private readonly DetteFournisseurRepositoryInterface $detteFournisseurRepository,
        private readonly FournisseurRepositoryInterface $fournisseurRepository,
        private readonly UserManagementService $userManagementService,
        private readonly AcheteurPresenter $acheteurPresenter,
    ) {
    }

    public function supports(TableType $type): bool
    {
        return true;
    }

    public function provide(TableType $type, array $payload): array
    {
        $columns = $this->normalizeColumns($payload['columns'] ?? []);
        $search = isset($payload['search']) ? strtolower(trim((string) $payload['search'])) : '';

        $rows = match ($type) {
            TableType::Ventes => $this->venteRows($search),
            TableType::Commandes => $this->commandeRows($search),
            TableType::Paiements => $this->paiementRows($search),
            TableType::Clients => $this->clientRows($search),
            TableType::Products => $this->productRows($search),
            TableType::Transactions => $this->transactionRows($payload, $search),
            TableType::Movements => $this->movementRows($search),
            TableType::Creances => $this->creanceRows($search),
            TableType::Dettes => $this->detteRows($search),
            TableType::Factures => $this->factureRows($search),
            TableType::Users => $this->userRows($search),
        };

        if ([] !== $columns) {
            $columnKeys = array_column($columns, 'key');
            $rows = array_map(
                static fn (array $row) => array_intersect_key($row, array_flip($columnKeys)),
                $rows,
            );
        }

        return [
            'title' => $this->titleFor($type),
            'filename' => $type->value.'-'.date('Y-m-d'),
            'columns' => $columns ?: $this->defaultColumns($type),
            'rows' => $rows,
            'filters_summary' => $this->filtersSummary($payload),
        ];
    }

    /** @param list<array{key?: string, label?: string}|string> $columns @return list<array{key: string, label: string}> */
    private function normalizeColumns(array $columns): array
    {
        $normalized = [];
        foreach ($columns as $column) {
            if (is_string($column)) {
                $normalized[] = ['key' => $column, 'label' => ucfirst(str_replace('_', ' ', $column))];
                continue;
            }
            if (isset($column['key'])) {
                $normalized[] = [
                    'key' => (string) $column['key'],
                    'label' => (string) ($column['label'] ?? $column['key']),
                ];
            }
        }

        return $normalized;
    }

    /** @return list<array<string, mixed>> */
    private function venteRows(string $search): array
    {
        return array_values(array_filter(array_map(function ($vente) {
            $buyer = $this->acheteurPresenter->present($vente->getAcheteur());

            return [
                'reference' => $vente->getReference(),
                'date' => $vente->getCreatedAt()->format('d/m/Y H:i'),
                'buyer' => $buyer['client_name'] ?? $buyer['anonymous_info'] ?? '—',
                'total' => $vente->getTotalAmount(),
                'status' => $vente->isCancelled() ? 'Annulée' : 'Active',
            ];
        }, $this->venteRepository->findAll()), fn (array $row) => $this->matchesSearch($row, $search)));
    }

    /** @return list<array<string, mixed>> */
    private function commandeRows(string $search): array
    {
        return array_values(array_filter(array_map(function ($commande) {
            $buyer = $this->acheteurPresenter->present($commande->getAcheteur());

            return [
                'reference' => $commande->getReference(),
                'date' => $commande->getCreatedAt()->format('d/m/Y H:i'),
                'buyer' => $buyer['client_name'] ?? $buyer['anonymous_info'] ?? '—',
                'total' => $commande->getTotalAmount(),
                'status' => $commande->getStatus()->value,
            ];
        }, $this->commandeRepository->findAll()), fn (array $row) => $this->matchesSearch($row, $search)));
    }

    /** @return list<array<string, mixed>> */
    private function paiementRows(string $search): array
    {
        return array_values(array_filter(array_map(function ($paiement) {
            $serialized = $this->paiementSerializer->serialize($paiement);

            return [
                'reference' => $serialized['reference'],
                'date' => $paiement->getPaidAt()->format('d/m/Y H:i'),
                'amount' => $serialized['amount'],
                'method' => $serialized['method_label'] ?? '—',
                'status' => $paiement->isCancelled() ? 'Annulé' : 'Validé',
            ];
        }, $this->paiementRepository->findAll()), fn (array $row) => $this->matchesSearch($row, $search)));
    }

    /** @return list<array<string, mixed>> */
    private function clientRows(string $search): array
    {
        return array_values(array_filter(array_map(static fn ($client) => [
            'name' => $client->getName(),
            'phone' => $client->getPhone() ?? '—',
            'email' => $client->getEmail() ?? '—',
        ], $this->clientRepository->findAll()), fn (array $row) => $this->matchesSearch($row, $search)));
    }

    /** @return list<array<string, mixed>> */
    private function productRows(string $search): array
    {
        return array_values(array_filter(array_map(static fn ($product) => [
            'name' => $product->getName(),
            'reference' => $product->getReference() ?? '—',
            'status' => $product->getStatus()->value,
        ], $this->productRepository->findAll()), fn (array $row) => $this->matchesSearch($row, $search)));
    }

    /** @param array<string, mixed> $payload @return list<array<string, mixed>> */
    private function transactionRows(array $payload, string $search): array
    {
        $compteId = isset($payload['filters']['compte_id']) ? (string) $payload['filters']['compte_id'] : null;
        $transactions = $this->transactionRepository->findAll(
            null !== $compteId ? \Symfony\Component\Uid\Uuid::fromString($compteId) : null,
        );

        return array_values(array_filter(array_map(function ($transaction) {
            $compte = $this->compteRepository->findById($transaction->getCompteId());

            return [
                'date' => $transaction->getOccurredAt()->format('d/m/Y H:i'),
                'label' => $transaction->getLabel(),
                'type' => $transaction->getType()->value,
                'amount' => $transaction->getAmount(),
                'compte' => $compte?->getName() ?? '—',
            ];
        }, $transactions), fn (array $row) => $this->matchesSearch($row, $search)));
    }

    /** @return list<array<string, mixed>> */
    private function movementRows(string $search): array
    {
        return array_values(array_filter(array_map(static fn ($movement) => [
            'date' => $movement->getOccurredAt()->format('d/m/Y H:i'),
            'type' => $movement->getType()->value,
            'quantity' => $movement->getQuantity(),
            'reason' => $movement->getDirection()->value,
        ], $this->stockMovementRepository->findAll()), fn (array $row) => $this->matchesSearch($row, $search)));
    }

    /** @return list<array<string, mixed>> */
    private function creanceRows(string $search): array
    {
        return array_values(array_filter(array_map(function ($facture) {
            if (!$facture->isCreance() || null !== $facture->getCreditClosedAt()) {
                return null;
            }
            $clientName = $facture->getAnonymousInfo() ?? '—';
            if (null !== $facture->getClientId()) {
                $client = $this->clientRepository->findById($facture->getClientId());
                $clientName = $client?->getName() ?? $clientName;
            }

            return [
                'numero' => $facture->getNumero(),
                'client' => $clientName,
                'amount' => $facture->getTotalAmount(),
                'issued_at' => $facture->getIssuedAt()->format('d/m/Y'),
            ];
        }, $this->factureRepository->findAll()), fn ($row) => null !== $row && $this->matchesSearch($row, $search)));
    }

    /** @return list<array<string, mixed>> */
    private function detteRows(string $search): array
    {
        return array_values(array_filter(array_map(function ($dette) {
            $fournisseur = $this->fournisseurRepository->findById($dette->getFournisseurId());

            return [
                'reference' => $dette->getReference(),
                'fournisseur' => $fournisseur?->getName() ?? '—',
                'amount' => $dette->getTotalAmount(),
                'issued_at' => $dette->getIssuedAt()->format('d/m/Y'),
            ];
        }, $this->detteFournisseurRepository->findDettes(null, DetteFilterStatus::All)), fn (array $row) => $this->matchesSearch($row, $search)));
    }

    /** @return list<array<string, mixed>> */
    private function factureRows(string $search): array
    {
        return array_values(array_filter(array_map(fn ($facture) => [
            'numero' => $facture->getNumero(),
            'source' => $facture->getSourceReference(),
            'amount' => $facture->getTotalAmount(),
            'issued_at' => $facture->getIssuedAt()->format('d/m/Y H:i'),
        ], $this->factureRepository->findAll()), fn (array $row) => $this->matchesSearch($row, $search)));
    }

    /** @return list<array<string, mixed>> */
    private function userRows(string $search): array
    {
        return array_values(array_filter(array_map(static fn ($user) => [
            'email' => $user->getEmail(),
            'name' => trim($user->getFirstName().' '.$user->getLastName()),
            'status' => UserStatus::Active === $user->getStatus() ? 'Actif' : $user->getStatus()->value,
        ], $this->userManagementService->listUsers()), fn (array $row) => $this->matchesSearch($row, $search)));
    }

    /** @param array<string, mixed> $row */
    private function matchesSearch(array $row, string $search): bool
    {
        if ('' === $search) {
            return true;
        }

        foreach ($row as $value) {
            if (is_string($value) && str_contains(strtolower($value), $search)) {
                return true;
            }
        }

        return false;
    }

    private function titleFor(TableType $type): string
    {
        return match ($type) {
            TableType::Ventes => 'Liste des ventes',
            TableType::Commandes => 'Liste des commandes',
            TableType::Paiements => 'Liste des paiements',
            TableType::Clients => 'Liste des clients',
            TableType::Products => 'Liste des produits',
            TableType::Transactions => 'Liste des transactions',
            TableType::Movements => 'Liste des mouvements de stock',
            TableType::Creances => 'Carnet de créances',
            TableType::Dettes => 'Carnet de dettes fournisseur',
            TableType::Factures => 'Liste des factures',
            TableType::Users => 'Liste des utilisateurs',
        };
    }

    /** @return list<array{key: string, label: string}> */
    private function defaultColumns(TableType $type): array
    {
        return match ($type) {
            TableType::Ventes => [
                ['key' => 'reference', 'label' => 'Référence'],
                ['key' => 'date', 'label' => 'Date'],
                ['key' => 'buyer', 'label' => 'Acheteur'],
                ['key' => 'total', 'label' => 'Total'],
                ['key' => 'status', 'label' => 'Statut'],
            ],
            TableType::Commandes => [
                ['key' => 'reference', 'label' => 'Référence'],
                ['key' => 'date', 'label' => 'Date'],
                ['key' => 'buyer', 'label' => 'Acheteur'],
                ['key' => 'total', 'label' => 'Total'],
                ['key' => 'status', 'label' => 'Statut'],
            ],
            TableType::Paiements => [
                ['key' => 'reference', 'label' => 'Référence'],
                ['key' => 'date', 'label' => 'Date'],
                ['key' => 'amount', 'label' => 'Montant'],
                ['key' => 'method', 'label' => 'Mode'],
                ['key' => 'status', 'label' => 'Statut'],
            ],
            TableType::Clients => [
                ['key' => 'name', 'label' => 'Nom'],
                ['key' => 'phone', 'label' => 'Téléphone'],
                ['key' => 'email', 'label' => 'Email'],
            ],
            TableType::Products => [
                ['key' => 'name', 'label' => 'Produit'],
                ['key' => 'reference', 'label' => 'Référence'],
                ['key' => 'status', 'label' => 'Statut'],
            ],
            TableType::Transactions => [
                ['key' => 'date', 'label' => 'Date'],
                ['key' => 'label', 'label' => 'Libellé'],
                ['key' => 'type', 'label' => 'Type'],
                ['key' => 'amount', 'label' => 'Montant'],
                ['key' => 'compte', 'label' => 'Compte'],
            ],
            TableType::Movements => [
                ['key' => 'date', 'label' => 'Date'],
                ['key' => 'type', 'label' => 'Type'],
                ['key' => 'quantity', 'label' => 'Quantité'],
                ['key' => 'reason', 'label' => 'Motif'],
            ],
            TableType::Creances => [
                ['key' => 'numero', 'label' => 'Facture'],
                ['key' => 'client', 'label' => 'Client'],
                ['key' => 'amount', 'label' => 'Montant'],
                ['key' => 'issued_at', 'label' => 'Date'],
            ],
            TableType::Dettes => [
                ['key' => 'reference', 'label' => 'Référence'],
                ['key' => 'fournisseur', 'label' => 'Fournisseur'],
                ['key' => 'amount', 'label' => 'Montant'],
                ['key' => 'issued_at', 'label' => 'Date'],
            ],
            TableType::Factures => [
                ['key' => 'numero', 'label' => 'Numéro'],
                ['key' => 'source', 'label' => 'Source'],
                ['key' => 'amount', 'label' => 'Montant'],
                ['key' => 'issued_at', 'label' => 'Date'],
            ],
            TableType::Users => [
                ['key' => 'email', 'label' => 'Email'],
                ['key' => 'name', 'label' => 'Nom'],
                ['key' => 'status', 'label' => 'Statut'],
            ],
        };
    }

    /** @param array<string, mixed> $payload */
    private function filtersSummary(array $payload): string
    {
        $parts = [];
        if (!empty($payload['search'])) {
            $parts[] = 'Recherche : '.$payload['search'];
        }
        if (!empty($payload['filters']) && is_array($payload['filters'])) {
            foreach ($payload['filters'] as $key => $value) {
                if (null !== $value && '' !== $value) {
                    $parts[] = $key.' : '.$value;
                }
            }
        }

        return implode(' · ', $parts);
    }
}
