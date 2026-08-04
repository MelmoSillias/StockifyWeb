<?php

namespace App\Dashboard\Infrastructure\Persistence\Doctrine;

use App\Fournisseur\Domain\Entity\CommandeFournisseur;
use App\Fournisseur\Domain\Enum\CommandeFournisseurStatus;
use App\Commerce\Domain\Entity\Commande;
use App\Commerce\Domain\Entity\Vente;
use App\Commerce\Domain\Entity\VenteLine;
use App\Commerce\Domain\Enum\CommandeStatus;
use App\Inventory\Domain\Entity\StockMovement;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final class DashboardReadRepository
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /** @return array{count: int, total_amount: string} */
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

        return [
            'count' => (int) ($result['saleCount'] ?? 0),
            'total_amount' => (string) ($result['totalAmount'] ?? '0.00'),
        ];
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

    /** @return array{pending_count: int, overdue_count: int} */
    public function getDeliveryCounts(\DateTimeImmutable $today): array
    {
        $todayDate = $today->setTime(0, 0);
        $statuses = [CommandeStatus::Confirmee, CommandeStatus::PartiellementLivree];

        $pendingCount = (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(c.id)')
            ->from(Commande::class, 'c')
            ->where('c.status IN (:statuses)')
            ->andWhere('c.deliveryDate IS NOT NULL')
            ->andWhere('c.deliveryDate <= :today')
            ->setParameter('statuses', $statuses)
            ->setParameter('today', $todayDate)
            ->getQuery()
            ->getSingleScalarResult();

        $overdueCount = (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(c.id)')
            ->from(Commande::class, 'c')
            ->where('c.status IN (:statuses)')
            ->andWhere('c.deliveryDate IS NOT NULL')
            ->andWhere('c.deliveryDate < :today')
            ->setParameter('statuses', $statuses)
            ->setParameter('today', $todayDate)
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'pending_count' => $pendingCount,
            'overdue_count' => $overdueCount,
        ];
    }

    /** @return list<array<string, mixed>> */
    public function findRecentOrders(\DateTimeImmutable $from, \DateTimeImmutable $to, int $limit): array
    {
        /** @var list<Commande> $commandes */
        $commandes = $this->entityManager->createQueryBuilder()
            ->select('c')
            ->from(Commande::class, 'c')
            ->where('c.createdAt >= :from')
            ->andWhere('c.createdAt <= :to')
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getResult();

        return array_map(
            static fn (Commande $commande): array => [
                'id' => (string) $commande->getId(),
                'reference' => $commande->getReference(),
                'status' => $commande->getStatus()->value,
                'total_amount' => $commande->getTotalAmount(),
                'created_at' => $commande->getCreatedAt()->format(\DateTimeInterface::ATOM),
                'client_id' => $commande->getAcheteur()->clientId()
                    ? (string) $commande->getAcheteur()->clientId()
                    : null,
                'anonymous_info' => $commande->getAcheteur()->anonymousInfo(),
            ],
            $commandes,
        );
    }

    /** @return list<array<string, mixed>> */
    public function findRecentSales(\DateTimeImmutable $from, \DateTimeImmutable $to, int $limit): array
    {
        /** @var list<Vente> $ventes */
        $ventes = $this->entityManager->createQueryBuilder()
            ->select('v')
            ->from(Vente::class, 'v')
            ->where('v.cancelledAt IS NULL')
            ->andWhere('v.createdAt >= :from')
            ->andWhere('v.createdAt <= :to')
            ->orderBy('v.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getResult();

        return array_map(
            static fn (Vente $vente): array => [
                'id' => (string) $vente->getId(),
                'reference' => $vente->getReference(),
                'total_amount' => $vente->getTotalAmount(),
                'created_at' => $vente->getCreatedAt()->format(\DateTimeInterface::ATOM),
                'client_id' => $vente->getAcheteur()->clientId()
                    ? (string) $vente->getAcheteur()->clientId()
                    : null,
                'anonymous_info' => $vente->getAcheteur()->anonymousInfo(),
            ],
            $ventes,
        );
    }

    /** @return list<array<string, mixed>> */
    public function findTopProducts(\DateTimeImmutable $from, \DateTimeImmutable $to, int $limit): array
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
            ->orderBy('totalQuantity', 'DESC')
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

    /** @return list<array<string, mixed>> */
    public function findRecentMovements(\DateTimeImmutable $from, \DateTimeImmutable $to, int $limit): array
    {
        /** @var list<StockMovement> $movements */
        $movements = $this->entityManager->createQueryBuilder()
            ->select('m', 'variant', 'product')
            ->from(StockMovement::class, 'm')
            ->innerJoin('m.variant', 'variant')
            ->innerJoin('variant.product', 'product')
            ->where('m.occurredAt >= :from')
            ->andWhere('m.occurredAt <= :to')
            ->orderBy('m.occurredAt', 'DESC')
            ->setMaxResults($limit)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getResult();

        return array_map(
            static function (StockMovement $movement): array {
                $variant = $movement->getVariant();
                $product = $variant->getProduct();

                return [
                    'id' => (string) $movement->getId(),
                    'type' => $movement->getType()->value,
                    'direction' => $movement->getDirection()->value,
                    'quantity' => $movement->getQuantity(),
                    'occurred_at' => $movement->getOccurredAt()->format(\DateTimeInterface::ATOM),
                    'product_name' => $product->getName(),
                    'variant_id' => (string) $variant->getId(),
                    'sku' => $variant->getSku(),
                ];
            },
            $movements,
        );
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

        $pointsByDate = [];
        foreach ($rows as $row) {
            /** @var \DateTimeImmutable $createdAt */
            $createdAt = $row['createdAt'];
            $dateKey = $createdAt->format('Y-m-d');
            if (!isset($pointsByDate[$dateKey])) {
                $pointsByDate[$dateKey] = [
                    'date' => $dateKey,
                    'count' => 0,
                    'total_amount' => '0.00',
                ];
            }

            $pointsByDate[$dateKey]['count']++;
            $pointsByDate[$dateKey]['total_amount'] = bcadd(
                $pointsByDate[$dateKey]['total_amount'],
                (string) $row['totalAmount'],
                2,
            );
        }

        $cursor = $from->setTime(0, 0);
        $end = $to->setTime(0, 0);
        $points = [];

        while ($cursor <= $end) {
            $dateKey = $cursor->format('Y-m-d');
            $points[] = $pointsByDate[$dateKey] ?? [
                'date' => $dateKey,
                'count' => 0,
                'total_amount' => '0.00',
            ];
            $cursor = $cursor->modify('+1 day');
        }

        return $points;
    }

    /** @return list<Commande> */
    public function findPendingDeliveries(int $limit, \DateTimeImmutable $today): array
    {
        $todayDate = $today->setTime(0, 0);
        $maxDate = $todayDate->modify('+7 days');
        $statuses = [CommandeStatus::Confirmee, CommandeStatus::PartiellementLivree];

        /** @var list<Commande> $commandes */
        $commandes = $this->entityManager->createQueryBuilder()
            ->select('c')
            ->from(Commande::class, 'c')
            ->where('c.status IN (:statuses)')
            ->andWhere('c.deliveryDate IS NOT NULL')
            ->andWhere('c.deliveryDate <= :maxDate')
            ->orderBy('c.deliveryDate', 'ASC')
            ->addOrderBy('c.createdAt', 'ASC')
            ->setMaxResults($limit)
            ->setParameter('statuses', $statuses)
            ->setParameter('maxDate', $maxDate)
            ->getQuery()
            ->getResult();

        return $commandes;
    }

    /** @return list<array<string, mixed>> */
    public function findPendingSupplierOrders(int $limit): array
    {
        $statuses = [CommandeFournisseurStatus::Initiee, CommandeFournisseurStatus::Confirmee];

        /** @var list<CommandeFournisseur> $commandes */
        $commandes = $this->entityManager->createQueryBuilder()
            ->select('c')
            ->from(CommandeFournisseur::class, 'c')
            ->where('c.status IN (:statuses)')
            ->orderBy('c.expectedAt', 'ASC')
            ->addOrderBy('c.createdAt', 'ASC')
            ->setMaxResults($limit)
            ->setParameter('statuses', $statuses)
            ->getQuery()
            ->getResult();

        return array_map(
            static fn (CommandeFournisseur $commande): array => [
                'id' => (string) $commande->getId(),
                'reference' => $commande->getReference(),
                'fournisseur_id' => (string) $commande->getFournisseurId(),
                'status' => $commande->getStatus()->value,
                'total_amount' => $commande->getTotalAmount(),
                'expected_at' => $commande->getExpectedAt()?->format('Y-m-d'),
            ],
            $commandes,
        );
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
