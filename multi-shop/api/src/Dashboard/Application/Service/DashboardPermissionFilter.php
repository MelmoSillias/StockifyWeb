<?php

namespace App\Dashboard\Application\Service;

use App\AccessAudit\Application\Service\PermissionResolverService;
use App\IdentityAccess\Domain\Entity\User;

final class DashboardPermissionFilter
{
    public function __construct(
        private readonly PermissionResolverService $permissionResolver,
    ) {
    }

    /** @return array<string, mixed> */
    public function filterSummary(array $data, User $user): array
    {
        $filtered = [];

        if ($this->permissionResolver->hasPermission($user, 'commerce.ventes.view')) {
            $filtered['sales'] = $data['sales'];
        }

        if ($this->permissionResolver->hasPermission($user, 'inventory.alerts.view')) {
            $filtered['stock'] = $data['stock'];
        }

        if ($this->permissionResolver->hasPermission($user, 'commerce.livraisons.view')) {
            $filtered['deliveries'] = $data['deliveries'];
        }

        if ($this->permissionResolver->hasPermission($user, 'client.clients.view')) {
            $filtered['clients'] = $data['clients'];
        }

        return $filtered;
    }

    /** @return array<string, mixed> */
    public function filterFeed(array $data, User $user): array
    {
        $filtered = [];

        if ($this->permissionResolver->hasPermission($user, 'commerce.commandes.view')) {
            $filtered['recent_orders'] = $data['recent_orders'];
        }

        if ($this->permissionResolver->hasPermission($user, 'commerce.ventes.view')) {
            $filtered['recent_sales'] = $data['recent_sales'];
            $filtered['top_products'] = $data['top_products'];
        }

        if ($this->permissionResolver->hasPermission($user, 'inventory.movements.view')) {
            $filtered['recent_movements'] = $data['recent_movements'];
        }

        if ($this->permissionResolver->hasPermission($user, 'inventory.alerts.view')) {
            $filtered['stock_alerts'] = $data['stock_alerts'];
        }

        return $filtered;
    }

    /** @return array<string, mixed> */
    public function filterFinanceSummary(array $data, User $user): array
    {
        $filtered = [];

        if ($this->permissionResolver->hasPermission($user, 'client.creances.view')
            && isset($data['client_creances'])) {
            $filtered['client_creances'] = $data['client_creances'];
        }

        if ($this->permissionResolver->hasPermission($user, 'fournisseur.dettes.view')
            && isset($data['supplier_dettes'])) {
            $filtered['supplier_dettes'] = $data['supplier_dettes'];
        }

        if ($this->permissionResolver->hasPermission($user, 'finance.comptes.view')
            && isset($data['treasury'])) {
            $filtered['treasury'] = $data['treasury'];
        }

        return $filtered;
    }

    public function canViewSalesTrend(User $user): bool
    {
        return $this->permissionResolver->hasPermission($user, 'commerce.ventes.view');
    }

    public function canViewPendingDeliveries(User $user): bool
    {
        return $this->permissionResolver->hasPermission($user, 'commerce.livraisons.view');
    }

    public function canViewPendingSupplierOrders(User $user): bool
    {
        return $this->permissionResolver->hasPermission($user, 'fournisseur.commandes.view');
    }

    public function canViewFinanceSummary(User $user): bool
    {
        return $this->permissionResolver->hasPermission($user, 'client.creances.view')
            || $this->permissionResolver->hasPermission($user, 'fournisseur.dettes.view')
            || $this->permissionResolver->hasPermission($user, 'finance.comptes.view');
    }

    public function canViewRecentAudit(User $user): bool
    {
        return $this->permissionResolver->hasPermission($user, 'access.audit.view');
    }
}
