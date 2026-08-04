<?php

namespace App\Analytics\Infrastructure\Persistence\Doctrine;

use App\Catalog\Domain\Entity\ProductVariant;
use App\Client\Domain\Entity\Client;
use App\Commerce\Domain\Entity\Commande;
use App\Commerce\Domain\Entity\Vente;
use App\Commerce\Domain\Entity\VenteLine;
use App\Commerce\Domain\Enum\CommandeStatus;
use App\Facturation\Domain\Entity\Avoir;
use App\Facturation\Domain\Entity\Facture;
use App\Finance\Domain\Entity\Compte;
use App\Finance\Domain\Entity\ModeDePaiement;
use App\Finance\Domain\Entity\Transaction;
use App\Finance\Domain\Enum\TransactionSourceType;
use App\Finance\Domain\Enum\TransactionType;
use App\Fournisseur\Domain\Entity\CommandeFournisseur;
use App\Fournisseur\Domain\Entity\DetteFournisseur;
use App\Fournisseur\Domain\Entity\Fournisseur;
use App\Fournisseur\Domain\Enum\CommandeFournisseurStatus;
use App\Inventory\Domain\Entity\StockLot;
use App\Inventory\Domain\Entity\StockMovement;
use App\Inventory\Domain\Enum\MovementDirection;
use App\Inventory\Domain\Enum\MovementType;
use App\Paiement\Domain\Entity\Paiement;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final class AnalyticsReadRepository
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /** @return array{count: int, total_amount: string, cancelled_count: int, net_amount: string} */
    public function getSalesSummary(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $result = $this->entityManager->createQueryBuilder()
            ->select('COUNT(v.id) AS saleCount', 'COALESCE(SUM(v.totalAmount), 0) AS totalAmount')
            ->from(Vente::class, 'v')
            ->where('v.cancelledAt IS NULL')
            ->andWhere('v.createdAt >= :from')
            ->andWhere('v.createdAt <= :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getSingleResult();

        $cancelledCount = (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(v.id)')
            ->from(Vente::class, 'v')
            ->where('v.cancelledAt IS NOT NULL')
            ->andWhere('v.createdAt >= :from')
            ->andWhere('v.createdAt <= :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getSingleScalarResult();

        $avoirTotal = (string) ($this->entityManager->createQueryBuilder()
            ->select('COALESCE(SUM(a.totalAmount), 0)')
            ->from(Avoir::class, 'a')
            ->where('a.issuedAt >= :from')
            ->andWhere('a.issuedAt <= :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getSingleScalarResult() ?? '0.00');

        $totalAmount = (string) ($result['totalAmount'] ?? '0.00');
        $netAmount = bcsub($totalAmount, $avoirTotal, 2);

        return [
            'count' => (int) ($result['saleCount'] ?? 0),
            'total_amount' => $totalAmount,
            'cancelled_count' => $cancelledCount,
            'net_amount' => $netAmount,
            'avoir_amount' => $avoirTotal,
        ];
    }

    /** @return list<array{date: string, total_amount: string, count: int}> */
    public function getSalesTrend(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $rows = $this->entityManager->createQueryBuilder()
            ->select('v.createdAt AS createdAt', 'v.totalAmount AS totalAmount')
            ->from(Vente::class, 'v')
            ->where('v.cancelledAt IS NULL')
            ->andWhere('v.createdAt >= :from')
            ->andWhere('v.createdAt <= :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getArrayResult();

        return $this->buildDailyPoints($rows, $from, $to, 'createdAt', 'totalAmount');
    }

    /** @return list<array<string, mixed>> */
    public function findTopProducts(\DateTimeImmutable $from, \DateTimeImmutable $to, int $limit = 10): array
    {
        $rows = $this->entityManager->createQueryBuilder()
            ->select(
                'vl.variantId AS variantId',
                'vl.label AS label',
                'SUM(vl.quantity) AS totalQuantity',
                'SUM(vl.lineTotal) AS totalAmount',
            )
            ->from(VenteLine::class, 'vl')
            ->innerJoin('vl.vente', 'v')
            ->where('v.cancelledAt IS NULL')
            ->andWhere('v.createdAt >= :from')
            ->andWhere('v.createdAt <= :to')
            ->groupBy('vl.variantId', 'vl.label')
            ->orderBy('totalAmount', 'DESC')
            ->setMaxResults($limit)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getScalarResult();

        return array_map(
            fn (array $row): array => [
                'variant_id' => $this->stringifyUuid($row['variantId']),
                'label' => (string) $row['label'],
                'total_quantity' => (string) $row['totalQuantity'],
                'total_amount' => (string) $row['totalAmount'],
            ],
            $rows,
        );
    }

    /** @return list<array{category_id: ?string, category_name: string, total_amount: string, total_quantity: string}> */
    public function getSalesByCategory(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $rows = $this->entityManager->createQueryBuilder()
            ->select(
                'cat.id AS categoryId',
                'cat.name AS categoryName',
                'SUM(vl.lineTotal) AS totalAmount',
                'SUM(vl.quantity) AS totalQuantity',
            )
            ->from(VenteLine::class, 'vl')
            ->innerJoin('vl.vente', 'v')
            ->innerJoin(ProductVariant::class, 'variant', 'WITH', 'variant.id = vl.variantId')
            ->innerJoin('variant.product', 'product')
            ->leftJoin('product.category', 'cat')
            ->where('v.cancelledAt IS NULL')
            ->andWhere('v.createdAt >= :from')
            ->andWhere('v.createdAt <= :to')
            ->groupBy('cat.id', 'cat.name')
            ->orderBy('totalAmount', 'DESC')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getScalarResult();

        return array_map(
            fn (array $row): array => [
                'category_id' => null !== $row['categoryId'] ? $this->stringifyUuid($row['categoryId']) : null,
                'category_name' => null !== $row['categoryName'] ? (string) $row['categoryName'] : 'Sans catégorie',
                'total_amount' => (string) ($row['totalAmount'] ?? '0.00'),
                'total_quantity' => (string) ($row['totalQuantity'] ?? '0'),
            ],
            $rows,
        );
    }

    /** @return array{client_count: int, anonymous_count: int} */
    public function getSalesClientSplit(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $clientCount = (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(v.id)')
            ->from(Vente::class, 'v')
            ->where('v.cancelledAt IS NULL')
            ->andWhere('v.clientId IS NOT NULL')
            ->andWhere('v.createdAt >= :from')
            ->andWhere('v.createdAt <= :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getSingleScalarResult();

        $anonymousCount = (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(v.id)')
            ->from(Vente::class, 'v')
            ->where('v.cancelledAt IS NULL')
            ->andWhere('v.clientId IS NULL')
            ->andWhere('v.createdAt >= :from')
            ->andWhere('v.createdAt <= :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getSingleScalarResult();

        return ['client_count' => $clientCount, 'anonymous_count' => $anonymousCount];
    }

    /** @return list<array{status: string, count: int, total_amount: string}> */
    public function getOrderPipeline(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $rows = $this->entityManager->createQueryBuilder()
            ->select('c.status AS status', 'COUNT(c.id) AS orderCount', 'COALESCE(SUM(c.totalAmount), 0) AS totalAmount')
            ->from(Commande::class, 'c')
            ->where('c.createdAt >= :from')
            ->andWhere('c.createdAt <= :to')
            ->groupBy('c.status')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getScalarResult();

        return array_map(
            static fn (array $row): array => [
                'status' => $row['status'] instanceof CommandeStatus ? $row['status']->value : (string) $row['status'],
                'count' => (int) $row['orderCount'],
                'total_amount' => (string) $row['totalAmount'],
            ],
            $rows,
        );
    }

    /** @return array{count: int, total_amount: string} */
    public function getPaymentsSummary(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $result = $this->entityManager->createQueryBuilder()
            ->select('COUNT(p.id) AS paymentCount', 'COALESCE(SUM(p.amount), 0) AS totalAmount')
            ->from(Paiement::class, 'p')
            ->where('p.cancelledAt IS NULL')
            ->andWhere('p.paidAt >= :from')
            ->andWhere('p.paidAt <= :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getSingleResult();

        return [
            'count' => (int) ($result['paymentCount'] ?? 0),
            'total_amount' => (string) ($result['totalAmount'] ?? '0.00'),
        ];
    }

    /** @return list<array{date: string, total_amount: string, count: int}> */
    public function getPaymentsTrend(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $rows = $this->entityManager->createQueryBuilder()
            ->select('p.paidAt AS paidAt', 'p.amount AS amount')
            ->from(Paiement::class, 'p')
            ->where('p.cancelledAt IS NULL')
            ->andWhere('p.paidAt >= :from')
            ->andWhere('p.paidAt <= :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getArrayResult();

        return $this->buildDailyPoints($rows, $from, $to, 'paidAt', 'amount');
    }

    /** @return list<array{mode_id: string, mode_label: string, total_amount: string, count: int}> */
    public function getPaymentsByMode(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $rows = $this->entityManager->createQueryBuilder()
            ->select(
                'm.id AS modeId',
                'm.label AS modeLabel',
                'SUM(p.amount) AS totalAmount',
                'COUNT(p.id) AS paymentCount',
            )
            ->from(Paiement::class, 'p')
            ->innerJoin(ModeDePaiement::class, 'm', 'WITH', 'm.id = p.modeDePaiementId')
            ->where('p.cancelledAt IS NULL')
            ->andWhere('p.paidAt >= :from')
            ->andWhere('p.paidAt <= :to')
            ->groupBy('m.id', 'm.label')
            ->orderBy('totalAmount', 'DESC')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getScalarResult();

        return array_map(
            fn (array $row): array => [
                'mode_id' => $this->stringifyUuid($row['modeId']),
                'mode_label' => (string) $row['modeLabel'],
                'total_amount' => (string) $row['totalAmount'],
                'count' => (int) $row['paymentCount'],
            ],
            $rows,
        );
    }

    /** @return array{open_count: int, open_balance: string, aging: list<array{bucket: string, count: int, balance: string}>} */
    public function getCreancesAging(): array
    {
        $today = new \DateTimeImmutable('today');
        $factures = $this->entityManager->createQueryBuilder()
            ->select('f')
            ->from(Facture::class, 'f')
            ->where('f.isCreance = true')
            ->andWhere('f.creditClosedAt IS NULL')
            ->getQuery()
            ->getResult();

        $buckets = [
            '0_30' => ['bucket' => '0-30 j', 'count' => 0, 'balance' => '0.00'],
            '31_60' => ['bucket' => '31-60 j', 'count' => 0, 'balance' => '0.00'],
            '60_plus' => ['bucket' => '60+ j', 'count' => 0, 'balance' => '0.00'],
        ];
        $openBalance = '0.00';
        $openCount = 0;

        foreach ($factures as $facture) {
            if (!$facture instanceof Facture) {
                continue;
            }
            $balance = $this->computeFactureBalance($facture);
            if (bccomp($balance, '0', 2) <= 0) {
                continue;
            }
            ++$openCount;
            $openBalance = bcadd($openBalance, $balance, 2);
            $days = (int) $facture->getIssuedAt()->diff($today)->days;
            if ($days <= 30) {
                $key = '0_30';
            } elseif ($days <= 60) {
                $key = '31_60';
            } else {
                $key = '60_plus';
            }
            ++$buckets[$key]['count'];
            $buckets[$key]['balance'] = bcadd($buckets[$key]['balance'], $balance, 2);
        }

        return [
            'open_count' => $openCount,
            'open_balance' => $openBalance,
            'aging' => array_values($buckets),
        ];
    }

    /** @return list<array{id: string, label: string, balance: string}> */
    public function getTopCreancesDebtors(int $limit = 10): array
    {
        $factures = $this->entityManager->createQueryBuilder()
            ->select('f')
            ->from(Facture::class, 'f')
            ->where('f.isCreance = true')
            ->andWhere('f.creditClosedAt IS NULL')
            ->getQuery()
            ->getResult();

        $items = [];
        foreach ($factures as $facture) {
            if (!$facture instanceof Facture) {
                continue;
            }
            $balance = $this->computeFactureBalance($facture);
            if (bccomp($balance, '0', 2) <= 0) {
                continue;
            }
            $clientId = $facture->getClientId();
            $label = $facture->getAnonymousInfo() ?? 'Client';
            if (null !== $clientId) {
                $client = $this->entityManager->find(Client::class, $clientId);
                if ($client instanceof Client) {
                    $label = $client->getName();
                }
            }
            $items[] = [
                'id' => (string) $facture->getId(),
                'label' => $label,
                'balance' => $balance,
            ];
        }

        usort($items, static fn (array $a, array $b): int => bccomp($b['balance'], $a['balance'], 2));

        return \array_slice($items, 0, $limit);
    }

    public function getStockValuation(): string
    {
        $result = $this->entityManager->createQueryBuilder()
            ->select('COALESCE(SUM(l.quantityRemaining * l.unitCost), 0) AS totalValue')
            ->from(StockLot::class, 'l')
            ->where('l.quantityRemaining > 0')
            ->getQuery()
            ->getSingleScalarResult();

        return (string) ($result ?? '0.00');
    }

    public function countLowStockAlerts(): int
    {
        $variants = $this->entityManager->createQueryBuilder()
            ->select('v.id AS variantId', 'v.alertThreshold AS alertThreshold')
            ->from(ProductVariant::class, 'v')
            ->where('v.alertThreshold IS NOT NULL')
            ->andWhere('v.alertThreshold > 0')
            ->getQuery()
            ->getScalarResult();

        if ([] === $variants) {
            return 0;
        }

        $availableByVariant = $this->getAvailableStockByVariant();
        $count = 0;
        foreach ($variants as $row) {
            $variantId = $this->stringifyUuid($row['variantId']);
            $threshold = (string) $row['alertThreshold'];
            $available = $availableByVariant[$variantId] ?? '0';
            if (bccomp($available, $threshold, 3) <= 0) {
                ++$count;
            }
        }

        return $count;
    }

    /** @return list<array{type: string, direction: string, total_quantity: string, count: int}> */
    public function getMovementsSummary(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $rows = $this->entityManager->createQueryBuilder()
            ->select(
                'm.type AS movementType',
                'm.direction AS direction',
                'SUM(m.quantity) AS totalQuantity',
                'COUNT(m.id) AS movementCount',
            )
            ->from(StockMovement::class, 'm')
            ->where('m.occurredAt >= :from')
            ->andWhere('m.occurredAt <= :to')
            ->groupBy('m.type', 'm.direction')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getScalarResult();

        return array_map(
            static fn (array $row): array => [
                'type' => $row['movementType'] instanceof MovementType ? $row['movementType']->value : (string) $row['movementType'],
                'direction' => $row['direction'] instanceof MovementDirection ? $row['direction']->value : (string) $row['direction'],
                'total_quantity' => (string) $row['totalQuantity'],
                'count' => (int) $row['movementCount'],
            ],
            $rows,
        );
    }

    public function countExpiringLots(int $withinDays = 30): int
    {
        $deadline = (new \DateTimeImmutable('today'))->modify("+{$withinDays} days");

        return (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(l.id)')
            ->from(StockLot::class, 'l')
            ->where('l.expiryDate IS NOT NULL')
            ->andWhere('l.expiryDate <= :deadline')
            ->andWhere('l.quantityRemaining > 0')
            ->setParameter('deadline', $deadline)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @return list<array{variant_id: string, label: string, sale_price: string, average_cost: string, margin_percent: ?string}> */
    public function getTopMargins(int $limit = 10): array
    {
        $variants = $this->entityManager->createQueryBuilder()
            ->select('v', 'product')
            ->from(ProductVariant::class, 'v')
            ->innerJoin('v.product', 'product')
            ->getQuery()
            ->getResult();

        $averageCostByVariant = $this->getAverageCostByVariant();
        $items = [];

        foreach ($variants as $variant) {
            if (!$variant instanceof ProductVariant) {
                continue;
            }
            $variantId = (string) $variant->getId();
            $salePrice = $variant->getDefaultPrice();
            $avgCost = $averageCostByVariant[$variantId] ?? '0.0000';
            if (bccomp($salePrice, '0', 2) <= 0) {
                continue;
            }
            $marginPercent = null;
            if (bccomp($avgCost, '0', 4) > 0) {
                $margin = bcsub($salePrice, $avgCost, 4);
                $marginPercent = bcmul(bcdiv($margin, $salePrice, 4), '100', 2);
            }
            $items[] = [
                'variant_id' => $variantId,
                'label' => $variant->getProduct()->getName().' ('.$variant->getSku().')',
                'sale_price' => $salePrice,
                'average_cost' => bcadd($avgCost, '0', 4),
                'margin_percent' => $marginPercent,
            ];
        }

        usort($items, static function (array $a, array $b): int {
            $aMargin = $a['margin_percent'] ?? '-999';
            $bMargin = $b['margin_percent'] ?? '-999';

            return bccomp($bMargin, $aMargin, 2);
        });

        return \array_slice($items, 0, $limit);
    }

    /** @return array{count: int, total_amount: string, pending_count: int, overdue_count: int} */
    public function getPurchasesSummary(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $result = $this->entityManager->createQueryBuilder()
            ->select('COUNT(c.id) AS orderCount', 'COALESCE(SUM(c.totalAmount), 0) AS totalAmount')
            ->from(CommandeFournisseur::class, 'c')
            ->where('c.cancelledAt IS NULL')
            ->andWhere('c.receivedAt >= :from')
            ->andWhere('c.receivedAt <= :to')
            ->andWhere('c.status = :received')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->setParameter('received', CommandeFournisseurStatus::Recue)
            ->getQuery()
            ->getSingleResult();

        $today = new \DateTimeImmutable('today');
        $pendingCount = (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(c.id)')
            ->from(CommandeFournisseur::class, 'c')
            ->where('c.status IN (:statuses)')
            ->andWhere('c.cancelledAt IS NULL')
            ->setParameter('statuses', [CommandeFournisseurStatus::Initiee, CommandeFournisseurStatus::Confirmee])
            ->getQuery()
            ->getSingleScalarResult();

        $overdueCount = (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(c.id)')
            ->from(CommandeFournisseur::class, 'c')
            ->where('c.status IN (:statuses)')
            ->andWhere('c.cancelledAt IS NULL')
            ->andWhere('c.expectedAt IS NOT NULL')
            ->andWhere('c.expectedAt < :today')
            ->setParameter('statuses', [CommandeFournisseurStatus::Initiee, CommandeFournisseurStatus::Confirmee])
            ->setParameter('today', $today)
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'count' => (int) ($result['orderCount'] ?? 0),
            'total_amount' => (string) ($result['totalAmount'] ?? '0.00'),
            'pending_count' => $pendingCount,
            'overdue_count' => $overdueCount,
        ];
    }

    /** @return list<array{date: string, total_amount: string, count: int}> */
    public function getPurchasesTrend(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $rows = $this->entityManager->createQueryBuilder()
            ->select('c.receivedAt AS receivedAt', 'c.totalAmount AS totalAmount')
            ->from(CommandeFournisseur::class, 'c')
            ->where('c.cancelledAt IS NULL')
            ->andWhere('c.receivedAt >= :from')
            ->andWhere('c.receivedAt <= :to')
            ->andWhere('c.status = :received')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->setParameter('received', CommandeFournisseurStatus::Recue)
            ->getQuery()
            ->getArrayResult();

        return $this->buildDailyPoints($rows, $from, $to, 'receivedAt', 'totalAmount');
    }

    /** @return list<array{fournisseur_id: string, fournisseur_name: string, total_amount: string, count: int}> */
    public function getPurchasesBySupplier(\DateTimeImmutable $from, \DateTimeImmutable $to, int $limit = 10): array
    {
        $rows = $this->entityManager->createQueryBuilder()
            ->select(
                'c.fournisseurId AS fournisseurId',
                'SUM(c.totalAmount) AS totalAmount',
                'COUNT(c.id) AS orderCount',
            )
            ->from(CommandeFournisseur::class, 'c')
            ->where('c.cancelledAt IS NULL')
            ->andWhere('c.receivedAt >= :from')
            ->andWhere('c.receivedAt <= :to')
            ->andWhere('c.status = :received')
            ->groupBy('c.fournisseurId')
            ->orderBy('totalAmount', 'DESC')
            ->setMaxResults($limit)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->setParameter('received', CommandeFournisseurStatus::Recue)
            ->getQuery()
            ->getScalarResult();

        return array_map(
            function (array $row): array {
                $fournisseurId = $this->stringifyUuid($row['fournisseurId']);
                $fournisseur = $this->entityManager->find(Fournisseur::class, Uuid::fromString($fournisseurId));
                $name = $fournisseur instanceof Fournisseur ? $fournisseur->getName() : 'Fournisseur';

                return [
                    'fournisseur_id' => $fournisseurId,
                    'fournisseur_name' => $name,
                    'total_amount' => (string) $row['totalAmount'],
                    'count' => (int) $row['orderCount'],
                ];
            },
            $rows,
        );
    }

    public function getAverageLeadTimeDays(\DateTimeImmutable $from, \DateTimeImmutable $to): ?string
    {
        $rows = $this->entityManager->createQueryBuilder()
            ->select('c.confirmedAt AS confirmedAt', 'c.receivedAt AS receivedAt')
            ->from(CommandeFournisseur::class, 'c')
            ->where('c.cancelledAt IS NULL')
            ->andWhere('c.confirmedAt IS NOT NULL')
            ->andWhere('c.receivedAt IS NOT NULL')
            ->andWhere('c.receivedAt >= :from')
            ->andWhere('c.receivedAt <= :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getArrayResult();

        if ([] === $rows) {
            return null;
        }

        $totalDays = 0;
        foreach ($rows as $row) {
            /** @var \DateTimeImmutable $confirmedAt */
            $confirmedAt = $row['confirmedAt'];
            /** @var \DateTimeImmutable $receivedAt */
            $receivedAt = $row['receivedAt'];
            $totalDays += (int) $confirmedAt->diff($receivedAt)->days;
        }

        return bcdiv((string) $totalDays, (string) \count($rows), 1);
    }

    /** @return array{open_count: int, open_balance: string, aging: list<array{bucket: string, count: int, balance: string}>} */
    public function getDettesAging(): array
    {
        $today = new \DateTimeImmutable('today');
        $dettes = $this->entityManager->createQueryBuilder()
            ->select('d')
            ->from(DetteFournisseur::class, 'd')
            ->where('d.creditClosedAt IS NULL')
            ->getQuery()
            ->getResult();

        $buckets = [
            '0_30' => ['bucket' => '0-30 j', 'count' => 0, 'balance' => '0.00'],
            '31_60' => ['bucket' => '31-60 j', 'count' => 0, 'balance' => '0.00'],
            '60_plus' => ['bucket' => '60+ j', 'count' => 0, 'balance' => '0.00'],
        ];
        $openBalance = '0.00';
        $openCount = 0;

        foreach ($dettes as $dette) {
            if (!$dette instanceof DetteFournisseur) {
                continue;
            }
            $balance = $this->computeDetteBalance($dette);
            if (bccomp($balance, '0', 2) <= 0) {
                continue;
            }
            ++$openCount;
            $openBalance = bcadd($openBalance, $balance, 2);
            $days = (int) $dette->getIssuedAt()->diff($today)->days;
            if ($days <= 30) {
                $key = '0_30';
            } elseif ($days <= 60) {
                $key = '31_60';
            } else {
                $key = '60_plus';
            }
            ++$buckets[$key]['count'];
            $buckets[$key]['balance'] = bcadd($buckets[$key]['balance'], $balance, 2);
        }

        return [
            'open_count' => $openCount,
            'open_balance' => $openBalance,
            'aging' => array_values($buckets),
        ];
    }

    /** @return array{revenu: string, depense: string, net: string} */
    public function getCashFlowSummary(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $revenu = (string) ($this->entityManager->createQueryBuilder()
            ->select('COALESCE(SUM(t.amount), 0)')
            ->from(Transaction::class, 't')
            ->where('t.cancelledAt IS NULL')
            ->andWhere('t.type = :revenu')
            ->andWhere('t.occurredAt >= :from')
            ->andWhere('t.occurredAt <= :to')
            ->setParameter('revenu', TransactionType::Revenu)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getSingleScalarResult() ?? '0.00');

        $depense = (string) ($this->entityManager->createQueryBuilder()
            ->select('COALESCE(SUM(t.amount), 0)')
            ->from(Transaction::class, 't')
            ->where('t.cancelledAt IS NULL')
            ->andWhere('t.type = :depense')
            ->andWhere('t.occurredAt >= :from')
            ->andWhere('t.occurredAt <= :to')
            ->setParameter('depense', TransactionType::Depense)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getSingleScalarResult() ?? '0.00');

        return [
            'revenu' => $revenu,
            'depense' => $depense,
            'net' => bcsub($revenu, $depense, 2),
        ];
    }

    /** @return list<array{date: string, revenu: string, depense: string}> */
    public function getCashFlowTrend(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $rows = $this->entityManager->createQueryBuilder()
            ->select('t.occurredAt AS occurredAt', 't.type AS type', 't.amount AS amount')
            ->from(Transaction::class, 't')
            ->where('t.cancelledAt IS NULL')
            ->andWhere('t.occurredAt >= :from')
            ->andWhere('t.occurredAt <= :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getArrayResult();

        $pointsByDate = [];
        foreach ($rows as $row) {
            /** @var \DateTimeImmutable $occurredAt */
            $occurredAt = $row['occurredAt'];
            $dateKey = $occurredAt->format('Y-m-d');
            if (!isset($pointsByDate[$dateKey])) {
                $pointsByDate[$dateKey] = ['date' => $dateKey, 'revenu' => '0.00', 'depense' => '0.00'];
            }
            $type = $row['type'] instanceof TransactionType ? $row['type'] : TransactionType::from((string) $row['type']);
            $field = TransactionType::Revenu === $type ? 'revenu' : 'depense';
            $pointsByDate[$dateKey][$field] = bcadd($pointsByDate[$dateKey][$field], (string) $row['amount'], 2);
        }

        $cursor = $from->setTime(0, 0);
        $end = $to->setTime(0, 0);
        $points = [];
        while ($cursor <= $end) {
            $dateKey = $cursor->format('Y-m-d');
            $points[] = $pointsByDate[$dateKey] ?? ['date' => $dateKey, 'revenu' => '0.00', 'depense' => '0.00'];
            $cursor = $cursor->modify('+1 day');
        }

        return $points;
    }

    /** @return list<array{compte_id: string, compte_name: string, compte_type: string, balance: string}> */
    public function getAccountBalances(): array
    {
        $comptes = $this->entityManager->createQueryBuilder()
            ->select('c')
            ->from(Compte::class, 'c')
            ->where('c.isActive = true')
            ->getQuery()
            ->getResult();

        $items = [];
        foreach ($comptes as $compte) {
            if (!$compte instanceof Compte) {
                continue;
            }
            $balance = $this->computeCompteBalance($compte->getId());
            $items[] = [
                'compte_id' => (string) $compte->getId(),
                'compte_name' => $compte->getName(),
                'compte_type' => $compte->getType()->value,
                'balance' => $balance,
            ];
        }

        usort($items, static fn (array $a, array $b): int => bccomp($b['balance'], $a['balance'], 2));

        return $items;
    }

    /** @return array{manual_count: int, auto_count: int} */
    public function getTransactionSourceSplit(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $rows = $this->entityManager->createQueryBuilder()
            ->select('t.sourceType AS sourceType', 'COUNT(t.id) AS txCount')
            ->from(Transaction::class, 't')
            ->where('t.cancelledAt IS NULL')
            ->andWhere('t.occurredAt >= :from')
            ->andWhere('t.occurredAt <= :to')
            ->groupBy('t.sourceType')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getScalarResult();

        $manual = 0;
        $auto = 0;
        foreach ($rows as $row) {
            $sourceType = $row['sourceType'] instanceof TransactionSourceType
                ? $row['sourceType']
                : TransactionSourceType::from((string) $row['sourceType']);
            $count = (int) $row['txCount'];
            if (TransactionSourceType::Manuel === $sourceType) {
                $manual += $count;
            } else {
                $auto += $count;
            }
        }

        return ['manual_count' => $manual, 'auto_count' => $auto];
    }

    public function countActiveClients(\DateTimeImmutable $from, \DateTimeImmutable $to): int
    {
        $venteClients = $this->entityManager->createQueryBuilder()
            ->select('DISTINCT v.clientId AS clientId')
            ->from(Vente::class, 'v')
            ->where('v.clientId IS NOT NULL')
            ->andWhere('v.cancelledAt IS NULL')
            ->andWhere('v.createdAt >= :from')
            ->andWhere('v.createdAt <= :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getScalarResult();

        $commandeClients = $this->entityManager->createQueryBuilder()
            ->select('DISTINCT c.clientId AS clientId')
            ->from(Commande::class, 'c')
            ->where('c.clientId IS NOT NULL')
            ->andWhere('c.cancelledAt IS NULL')
            ->andWhere('c.createdAt >= :from')
            ->andWhere('c.createdAt <= :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getScalarResult();

        $clientIds = [];
        foreach ([...$venteClients, ...$commandeClients] as $row) {
            $clientId = $row['clientId'] ?? null;
            if (null !== $clientId && '' !== $clientId) {
                $clientIds[(string) $clientId] = true;
            }
        }

        return \count($clientIds);
    }

    public function countNewClients(\DateTimeImmutable $from, \DateTimeImmutable $to): int
    {
        return (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(c.id)')
            ->from(Client::class, 'c')
            ->where('c.deletedAt IS NULL')
            ->andWhere('c.createdAt >= :from')
            ->andWhere('c.createdAt <= :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @return list<array{client_id: string, client_name: string, total_amount: string, sale_count: int}> */
    public function getTopClientsByRevenue(\DateTimeImmutable $from, \DateTimeImmutable $to, int $limit = 10): array
    {
        $rows = $this->entityManager->createQueryBuilder()
            ->select(
                'v.clientId AS clientId',
                'SUM(v.totalAmount) AS totalAmount',
                'COUNT(v.id) AS saleCount',
            )
            ->from(Vente::class, 'v')
            ->where('v.cancelledAt IS NULL')
            ->andWhere('v.clientId IS NOT NULL')
            ->andWhere('v.createdAt >= :from')
            ->andWhere('v.createdAt <= :to')
            ->groupBy('v.clientId')
            ->orderBy('totalAmount', 'DESC')
            ->setMaxResults($limit)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getScalarResult();

        return array_map(
            function (array $row): array {
                $clientId = $this->stringifyUuid($row['clientId']);
                $client = $this->entityManager->find(Client::class, Uuid::fromString($clientId));
                $name = $client instanceof Client ? $client->getName() : 'Client';

                return [
                    'client_id' => $clientId,
                    'client_name' => $name,
                    'total_amount' => (string) $row['totalAmount'],
                    'sale_count' => (int) $row['saleCount'],
                ];
            },
            $rows,
        );
    }

    /** @return array<string, string> */
    private function getAvailableStockByVariant(): array
    {
        $rows = $this->entityManager->createQueryBuilder()
            ->select('IDENTITY(l.variant) AS variantId', 'SUM(l.quantityRemaining) AS totalQty')
            ->from(StockLot::class, 'l')
            ->groupBy('l.variant')
            ->getQuery()
            ->getScalarResult();

        $result = [];
        foreach ($rows as $row) {
            $result[$this->stringifyUuid($row['variantId'])] = (string) ($row['totalQty'] ?? '0');
        }

        return $result;
    }

    /** @return array<string, string> */
    private function getAverageCostByVariant(): array
    {
        $rows = $this->entityManager->createQueryBuilder()
            ->select(
                'IDENTITY(l.variant) AS variantId',
                'SUM(l.quantityRemaining * l.unitCost) AS totalCost',
                'SUM(l.quantityRemaining) AS totalQty',
            )
            ->from(StockLot::class, 'l')
            ->where('l.quantityRemaining > 0')
            ->groupBy('l.variant')
            ->getQuery()
            ->getScalarResult();

        $result = [];
        foreach ($rows as $row) {
            $qty = (string) ($row['totalQty'] ?? '0');
            if (bccomp($qty, '0', 3) <= 0) {
                continue;
            }
            $result[$this->stringifyUuid($row['variantId'])] = bcdiv((string) $row['totalCost'], $qty, 4);
        }

        return $result;
    }

    private function computeFactureBalance(Facture $facture): string
    {
        $paid = (string) ($this->entityManager->createQueryBuilder()
            ->select('COALESCE(SUM(p.amount), 0)')
            ->from(Paiement::class, 'p')
            ->where('p.factureId = :factureId')
            ->andWhere('p.cancelledAt IS NULL')
            ->setParameter('factureId', $facture->getId())
            ->getQuery()
            ->getSingleScalarResult() ?? '0.00');

        return bcsub($facture->getTotalAmount(), $paid, 2);
    }

    private function computeDetteBalance(DetteFournisseur $dette): string
    {
        $paid = (string) ($this->entityManager->createQueryBuilder()
            ->select('COALESCE(SUM(p.amount), 0)')
            ->from('App\Fournisseur\Domain\Entity\PaiementFournisseur', 'p')
            ->where('p.detteFournisseurId = :detteId')
            ->andWhere('p.cancelledAt IS NULL')
            ->setParameter('detteId', $dette->getId())
            ->getQuery()
            ->getSingleScalarResult() ?? '0.00');

        return bcsub($dette->getTotalAmount(), $paid, 2);
    }

    private function computeCompteBalance(Uuid $compteId): string
    {
        $revenu = (string) ($this->entityManager->createQueryBuilder()
            ->select('COALESCE(SUM(t.amount), 0)')
            ->from(Transaction::class, 't')
            ->where('t.compteId = :compteId')
            ->andWhere('t.cancelledAt IS NULL')
            ->andWhere('t.type = :revenu')
            ->setParameter('compteId', $compteId)
            ->setParameter('revenu', TransactionType::Revenu)
            ->getQuery()
            ->getSingleScalarResult() ?? '0.00');

        $depense = (string) ($this->entityManager->createQueryBuilder()
            ->select('COALESCE(SUM(t.amount), 0)')
            ->from(Transaction::class, 't')
            ->where('t.compteId = :compteId')
            ->andWhere('t.cancelledAt IS NULL')
            ->andWhere('t.type = :depense')
            ->setParameter('compteId', $compteId)
            ->setParameter('depense', TransactionType::Depense)
            ->getQuery()
            ->getSingleScalarResult() ?? '0.00');

        return bcsub($revenu, $depense, 2);
    }

    /**
     * @param list<array<string, mixed>> $rows
     *
     * @return list<array{date: string, total_amount: string, count: int}>
     */
    private function buildDailyPoints(
        array $rows,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
        string $dateField,
        string $amountField,
    ): array {
        $pointsByDate = [];
        foreach ($rows as $row) {
            /** @var \DateTimeImmutable $dateValue */
            $dateValue = $row[$dateField];
            $dateKey = $dateValue->format('Y-m-d');
            if (!isset($pointsByDate[$dateKey])) {
                $pointsByDate[$dateKey] = ['date' => $dateKey, 'count' => 0, 'total_amount' => '0.00'];
            }
            ++$pointsByDate[$dateKey]['count'];
            $pointsByDate[$dateKey]['total_amount'] = bcadd(
                $pointsByDate[$dateKey]['total_amount'],
                (string) $row[$amountField],
                2,
            );
        }

        $cursor = $from->setTime(0, 0);
        $end = $to->setTime(0, 0);
        $points = [];
        while ($cursor <= $end) {
            $dateKey = $cursor->format('Y-m-d');
            $points[] = $pointsByDate[$dateKey] ?? ['date' => $dateKey, 'count' => 0, 'total_amount' => '0.00'];
            $cursor = $cursor->modify('+1 day');
        }

        return $points;
    }

    private function stringifyUuid(mixed $value): string
    {
        if ($value instanceof Uuid) {
            return $value->toRfc4122();
        }

        if (!\is_string($value)) {
            return (string) $value;
        }

        if (Uuid::isValid($value)) {
            return $value;
        }

        if (16 === \strlen($value)) {
            return Uuid::fromBinary($value)->toRfc4122();
        }

        return $value;
    }
}
