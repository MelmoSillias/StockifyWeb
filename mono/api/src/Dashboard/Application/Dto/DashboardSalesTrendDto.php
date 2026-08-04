<?php

namespace App\Dashboard\Application\Dto;

final readonly class DashboardSalesTrendDto
{
    /**
     * @param list<array{date: string, total_amount: string, count: int}> $points
     */
    public function __construct(
        public array $points,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'points' => $this->points,
        ];
    }
}
