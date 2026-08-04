<?php

namespace App\Analytics\Application\Service;

final class AnalyticsPeriodHelper
{
    /**
     * @return array{0: \DateTimeImmutable, 1: \DateTimeImmutable}
     */
    public static function previousPeriod(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $days = (int) $from->diff($to)->days + 1;
        $prevTo = $from->modify('-1 day')->setTime(23, 59, 59);
        $prevFrom = $prevTo->modify('-'.($days - 1).' days')->setTime(0, 0, 0);

        return [$prevFrom, $prevTo];
    }

    public static function computeDeltaPercent(string $current, string $previous): ?string
    {
        if (bccomp($previous, '0', 2) === 0) {
            if (bccomp($current, '0', 2) === 0) {
                return '0.00';
            }

            return null;
        }

        $delta = bcsub($current, $previous, 4);

        return bcmul(bcdiv($delta, $previous, 4), '100', 2);
    }

    public static function computeDeltaPercentInt(int $current, int $previous): ?string
    {
        return self::computeDeltaPercent((string) $current, (string) $previous);
    }

    /**
     * @return array{projected_amount: string, daily_average: string, remaining_days: int, elapsed_days: int}
     */
    public static function computeLinearProjection(
        string $totalAmount,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
        ?\DateTimeImmutable $now = null,
    ): array {
        $now = ($now ?? new \DateTimeImmutable())->setTime(23, 59, 59);
        $periodEnd = min($to->getTimestamp(), $now->getTimestamp()) === $to->getTimestamp() ? $to : $now;
        $periodStart = $from->setTime(0, 0, 0);
        $periodEndDay = $periodEnd->setTime(0, 0, 0);
        $toDay = $to->setTime(0, 0, 0);

        $elapsedDays = max(1, (int) $periodStart->diff($periodEndDay)->days + 1);
        $totalDays = max(1, (int) $periodStart->diff($toDay)->days + 1);
        $remainingDays = max(0, $totalDays - $elapsedDays);

        $dailyAverage = bcdiv($totalAmount, (string) $elapsedDays, 4);
        $projectedAmount = bcadd($totalAmount, bcmul($dailyAverage, (string) $remainingDays, 2), 2);

        return [
            'projected_amount' => $projectedAmount,
            'daily_average' => bcadd($dailyAverage, '0', 2),
            'remaining_days' => $remainingDays,
            'elapsed_days' => $elapsedDays,
        ];
    }
}
