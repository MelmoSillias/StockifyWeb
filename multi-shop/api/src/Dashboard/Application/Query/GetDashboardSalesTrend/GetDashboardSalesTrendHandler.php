<?php

namespace App\Dashboard\Application\Query\GetDashboardSalesTrend;

use App\Dashboard\Application\Dto\DashboardSalesTrendDto;
use App\Dashboard\Infrastructure\Persistence\Doctrine\DashboardReadRepository;

final class GetDashboardSalesTrendHandler
{
    public function __construct(
        private readonly DashboardReadRepository $dashboardReadRepository,
    ) {
    }

    public function handle(GetDashboardSalesTrendQuery $query): DashboardSalesTrendDto
    {
        $points = $this->dashboardReadRepository->getSalesTrend($query->from, $query->to);

        return new DashboardSalesTrendDto(points: $points);
    }
}
