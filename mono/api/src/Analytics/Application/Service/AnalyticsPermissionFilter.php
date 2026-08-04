<?php

namespace App\Analytics\Application\Service;

use App\AccessAudit\Application\Service\PermissionResolverService;
use App\IdentityAccess\Domain\Entity\User;

final class AnalyticsPermissionFilter
{
    public function __construct(
        private readonly PermissionResolverService $permissionResolver,
    ) {
    }

    /** @param array<string, mixed> $data */
    public function filterOverview(array $data, User $user): array
    {
        $filtered = [
            'period' => $data['period'] ?? null,
            'comparison' => $data['comparison'] ?? null,
        ];

        if ($this->permissionResolver->hasPermission($user, 'analytics.sales.view')) {
            $filtered['sales'] = $data['sales'] ?? null;
            $filtered['projection'] = $data['projection'] ?? null;
        }

        if ($this->permissionResolver->hasPermission($user, 'analytics.finance.view')) {
            $filtered['payments'] = $data['payments'] ?? null;
            $filtered['cash_flow'] = $data['cash_flow'] ?? null;
        }

        if ($this->permissionResolver->hasPermission($user, 'analytics.inventory.view')) {
            $filtered['inventory'] = $data['inventory'] ?? null;
        }

        if ($this->permissionResolver->hasPermission($user, 'analytics.suppliers.view')) {
            $filtered['purchases'] = $data['purchases'] ?? null;
        }

        if ($this->permissionResolver->hasPermission($user, 'analytics.clients.view')) {
            $filtered['clients'] = $data['clients'] ?? null;
        }

        return $filtered;
    }

    public function canViewSales(User $user): bool
    {
        return $this->permissionResolver->hasPermission($user, 'analytics.sales.view');
    }

    public function canViewInventory(User $user): bool
    {
        return $this->permissionResolver->hasPermission($user, 'analytics.inventory.view');
    }

    public function canViewFinance(User $user): bool
    {
        return $this->permissionResolver->hasPermission($user, 'analytics.finance.view');
    }

    public function canViewClients(User $user): bool
    {
        return $this->permissionResolver->hasPermission($user, 'analytics.clients.view');
    }

    public function canViewSuppliers(User $user): bool
    {
        return $this->permissionResolver->hasPermission($user, 'analytics.suppliers.view');
    }
}
