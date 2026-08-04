<?php

namespace App\Inventory\Infrastructure\Persistence\Doctrine;

use App\Catalog\Domain\Entity\ProductVariant;
use App\Inventory\Domain\Entity\StockLot;
use App\Inventory\Domain\Enum\StockPolicyStrategy;
use App\Inventory\Domain\Repository\StockLotRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<StockLot>
 */
class DoctrineStockLotRepository extends ServiceEntityRepository implements StockLotRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StockLot::class);
    }

    public function findAvailableByVariant(ProductVariant $variant, StockPolicyStrategy $strategy): array
    {
        $lots = $this->findBy(['variant' => $variant]);
        $available = array_values(array_filter(
            $lots,
            static fn (StockLot $lot) => bccomp($lot->getQuantityRemaining(), '0', 3) > 0,
        ));

        usort($available, static function (StockLot $a, StockLot $b) use ($strategy): int {
            return match ($strategy) {
                StockPolicyStrategy::Lifo => $b->getReceivedAt() <=> $a->getReceivedAt(),
                StockPolicyStrategy::Fefo => ($a->getExpiryDate() ?? new \DateTimeImmutable('9999-12-31'))
                    <=> ($b->getExpiryDate() ?? new \DateTimeImmutable('9999-12-31'))
                    ?: $a->getReceivedAt() <=> $b->getReceivedAt(),
                default => $a->getReceivedAt() <=> $b->getReceivedAt(),
            };
        });

        return $available;
    }

    public function findByVariantOrderedByReceivedAt(ProductVariant $variant): array
    {
        return $this->findBy(['variant' => $variant], ['receivedAt' => 'ASC']);
    }

    public function findById(Uuid $id): ?StockLot
    {
        return $this->find($id);
    }

    public function sumAvailableStock(ProductVariant $variant): string
    {
        $lots = $this->findBy(['variant' => $variant]);
        $total = '0';
        foreach ($lots as $lot) {
            if (bccomp($lot->getQuantityRemaining(), '0', 3) > 0) {
                $total = bcadd($total, $lot->getQuantityRemaining(), 3);
            }
        }

        return $total;
    }

    public function sumAvailableStockByVariant(): array
    {
        /** @var list<array{variantId: mixed, available: mixed}> $rows */
        $rows = $this->createQueryBuilder('l')
            ->select('v.id AS variantId, SUM(l.quantityRemaining) AS available')
            ->innerJoin('l.variant', 'v')
            ->andWhere('l.quantityRemaining > 0')
            ->groupBy('v.id')
            ->getQuery()
            ->getArrayResult();

        $map = [];
        foreach ($rows as $row) {
            $map[(string) $row['variantId']] = number_format((float) ($row['available'] ?? 0), 3, '.', '');
        }

        return $map;
    }

    public function countLotsByVariant(): array
    {
        /** @var list<array{variantId: mixed, lotCount: mixed}> $rows */
        $rows = $this->createQueryBuilder('l')
            ->select('v.id AS variantId, COUNT(l.id) AS lotCount')
            ->innerJoin('l.variant', 'v')
            ->groupBy('v.id')
            ->getQuery()
            ->getArrayResult();

        $map = [];
        foreach ($rows as $row) {
            $map[(string) $row['variantId']] = (int) $row['lotCount'];
        }

        return $map;
    }

    public function averageUnitCostByVariant(): array
    {
        /** @var list<array{variantId: mixed, weightedCost: mixed, remaining: mixed}> $rows */
        $rows = $this->createQueryBuilder('l')
            ->select('v.id AS variantId, SUM(l.quantityRemaining * l.unitCost) AS weightedCost, SUM(l.quantityRemaining) AS remaining')
            ->innerJoin('l.variant', 'v')
            ->andWhere('l.quantityRemaining > 0')
            ->groupBy('v.id')
            ->getQuery()
            ->getArrayResult();

        $map = [];
        foreach ($rows as $row) {
            $remaining = (float) ($row['remaining'] ?? 0);
            if ($remaining <= 0) {
                continue;
            }
            $map[(string) $row['variantId']] = number_format((float) ($row['weightedCost'] ?? 0) / $remaining, 4, '.', '');
        }

        return $map;
    }

    public function save(StockLot $lot, bool $flush = true): void
    {
        $this->getEntityManager()->persist($lot);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
