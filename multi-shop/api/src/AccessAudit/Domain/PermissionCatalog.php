<?php

namespace App\AccessAudit\Domain;

/**
 * Catalogue fixe des permissions Stockify V5.
 *
 * @phpstan-type PermissionDef array{code: string, label: string, module: string, action: string, is_critical: bool}
 */
final class PermissionCatalog
{
    /** @return list<PermissionDef> */
    public static function all(): array
    {
        return [
            ['code' => 'access.users.view', 'label' => 'Voir les utilisateurs', 'module' => 'access', 'action' => 'view', 'is_critical' => true],
            ['code' => 'access.users.create', 'label' => 'Créer des utilisateurs', 'module' => 'access', 'action' => 'create', 'is_critical' => true],
            ['code' => 'access.users.update', 'label' => 'Modifier les utilisateurs', 'module' => 'access', 'action' => 'update', 'is_critical' => true],
            ['code' => 'access.users.suspend', 'label' => 'Suspendre les utilisateurs', 'module' => 'access', 'action' => 'suspend', 'is_critical' => true],
            ['code' => 'access.roles.view', 'label' => 'Voir les rôles', 'module' => 'access', 'action' => 'view', 'is_critical' => true],
            ['code' => 'access.roles.manage', 'label' => 'Gérer les rôles', 'module' => 'access', 'action' => 'manage', 'is_critical' => true],
            ['code' => 'access.audit.view', 'label' => 'Consulter le journal d\'audit', 'module' => 'access', 'action' => 'view', 'is_critical' => false],
            ['code' => 'platform.shops.view', 'label' => 'Voir les boutiques', 'module' => 'platform', 'action' => 'view', 'is_critical' => true],
            ['code' => 'platform.shops.manage', 'label' => 'Gérer les boutiques', 'module' => 'platform', 'action' => 'manage', 'is_critical' => true],
            ['code' => 'platform.shop_users.manage', 'label' => 'Gérer les utilisateurs par boutique', 'module' => 'platform', 'action' => 'manage', 'is_critical' => true],
            ['code' => 'dashboard.view', 'label' => 'Voir le tableau de bord', 'module' => 'dashboard', 'action' => 'view', 'is_critical' => false],
            ['code' => 'analytics.view', 'label' => 'Accéder aux analytics', 'module' => 'analytics', 'action' => 'view', 'is_critical' => false],
            ['code' => 'analytics.sales.view', 'label' => 'Voir les rapports ventes', 'module' => 'analytics', 'action' => 'view', 'is_critical' => false],
            ['code' => 'analytics.inventory.view', 'label' => 'Voir les rapports stock', 'module' => 'analytics', 'action' => 'view', 'is_critical' => false],
            ['code' => 'analytics.finance.view', 'label' => 'Voir les rapports finance', 'module' => 'analytics', 'action' => 'view', 'is_critical' => false],
            ['code' => 'analytics.clients.view', 'label' => 'Voir les rapports clientèle', 'module' => 'analytics', 'action' => 'view', 'is_critical' => false],
            ['code' => 'analytics.suppliers.view', 'label' => 'Voir les rapports fournisseurs', 'module' => 'analytics', 'action' => 'view', 'is_critical' => false],
            ['code' => 'analytics.export', 'label' => 'Exporter les rapports analytics', 'module' => 'analytics', 'action' => 'export', 'is_critical' => false],
            ['code' => 'client.clients.view', 'label' => 'Voir les clients', 'module' => 'client', 'action' => 'view', 'is_critical' => false],
            ['code' => 'client.clients.create', 'label' => 'Créer des clients', 'module' => 'client', 'action' => 'create', 'is_critical' => false],
            ['code' => 'client.clients.update', 'label' => 'Modifier les clients', 'module' => 'client', 'action' => 'update', 'is_critical' => false],
            ['code' => 'client.clients.delete', 'label' => 'Supprimer les clients', 'module' => 'client', 'action' => 'delete', 'is_critical' => true],
            ['code' => 'client.creances.view', 'label' => 'Voir les créances clients', 'module' => 'client', 'action' => 'view', 'is_critical' => false],
            ['code' => 'client.journal.view', 'label' => 'Voir le journal client', 'module' => 'client', 'action' => 'view', 'is_critical' => false],
            ['code' => 'catalog.categories.view', 'label' => 'Voir les catégories', 'module' => 'catalog', 'action' => 'view', 'is_critical' => false],
            ['code' => 'catalog.categories.manage', 'label' => 'Gérer les catégories', 'module' => 'catalog', 'action' => 'manage', 'is_critical' => false],
            ['code' => 'catalog.products.view', 'label' => 'Voir les produits', 'module' => 'catalog', 'action' => 'view', 'is_critical' => false],
            ['code' => 'catalog.products.create', 'label' => 'Créer des produits', 'module' => 'catalog', 'action' => 'create', 'is_critical' => false],
            ['code' => 'catalog.products.update', 'label' => 'Modifier les produits', 'module' => 'catalog', 'action' => 'update', 'is_critical' => false],
            ['code' => 'catalog.products.delete', 'label' => 'Supprimer les produits', 'module' => 'catalog', 'action' => 'delete', 'is_critical' => true],
            ['code' => 'catalog.variants.manage', 'label' => 'Gérer les variantes', 'module' => 'catalog', 'action' => 'manage', 'is_critical' => false],
            ['code' => 'inventory.stock.view', 'label' => 'Voir le stock', 'module' => 'inventory', 'action' => 'view', 'is_critical' => false],
            ['code' => 'inventory.lots.create', 'label' => 'Créer des lots', 'module' => 'inventory', 'action' => 'create', 'is_critical' => false],
            ['code' => 'inventory.stock_out', 'label' => 'Sorties de stock', 'module' => 'inventory', 'action' => 'stock_out', 'is_critical' => false],
            ['code' => 'inventory.adjustments', 'label' => 'Ajustements de stock', 'module' => 'inventory', 'action' => 'adjust', 'is_critical' => false],
            ['code' => 'inventory.policy.manage', 'label' => 'Gérer les politiques de stock', 'module' => 'inventory', 'action' => 'manage', 'is_critical' => false],
            ['code' => 'inventory.movements.view', 'label' => 'Voir les mouvements', 'module' => 'inventory', 'action' => 'view', 'is_critical' => false],
            ['code' => 'inventory.alerts.view', 'label' => 'Voir les alertes stock', 'module' => 'inventory', 'action' => 'view', 'is_critical' => false],
            ['code' => 'commerce.cart.use', 'label' => 'Utiliser le panier', 'module' => 'commerce', 'action' => 'use', 'is_critical' => false],
            ['code' => 'commerce.ventes.view', 'label' => 'Voir les ventes', 'module' => 'commerce', 'action' => 'view', 'is_critical' => false],
            ['code' => 'commerce.ventes.create', 'label' => 'Créer des ventes', 'module' => 'commerce', 'action' => 'create', 'is_critical' => false],
            ['code' => 'commerce.ventes.cancel', 'label' => 'Annuler des ventes', 'module' => 'commerce', 'action' => 'cancel', 'is_critical' => true],
            ['code' => 'commerce.commandes.view', 'label' => 'Voir les commandes', 'module' => 'commerce', 'action' => 'view', 'is_critical' => false],
            ['code' => 'commerce.commandes.create', 'label' => 'Créer des commandes', 'module' => 'commerce', 'action' => 'create', 'is_critical' => false],
            ['code' => 'commerce.commandes.confirm', 'label' => 'Confirmer des commandes', 'module' => 'commerce', 'action' => 'confirm', 'is_critical' => false],
            ['code' => 'commerce.commandes.cancel', 'label' => 'Annuler des commandes', 'module' => 'commerce', 'action' => 'cancel', 'is_critical' => true],
            ['code' => 'commerce.devis.view', 'label' => 'Voir les devis', 'module' => 'commerce', 'action' => 'view', 'is_critical' => false],
            ['code' => 'commerce.devis.create', 'label' => 'Créer des devis', 'module' => 'commerce', 'action' => 'create', 'is_critical' => false],
            ['code' => 'commerce.devis.convert', 'label' => 'Convertir des devis', 'module' => 'commerce', 'action' => 'convert', 'is_critical' => false],
            ['code' => 'commerce.devis.cancel', 'label' => 'Annuler des devis', 'module' => 'commerce', 'action' => 'cancel', 'is_critical' => true],
            ['code' => 'commerce.livraisons.view', 'label' => 'Voir les livraisons', 'module' => 'commerce', 'action' => 'view', 'is_critical' => false],
            ['code' => 'commerce.livraisons.create', 'label' => 'Créer des bons de livraison', 'module' => 'commerce', 'action' => 'create', 'is_critical' => false],
            ['code' => 'commerce.livraisons.deliver', 'label' => 'Marquer livré', 'module' => 'commerce', 'action' => 'deliver', 'is_critical' => false],
            ['code' => 'facturation.factures.view', 'label' => 'Voir les factures', 'module' => 'facturation', 'action' => 'view', 'is_critical' => false],
            ['code' => 'paiement.paiements.view', 'label' => 'Voir les paiements', 'module' => 'paiement', 'action' => 'view', 'is_critical' => false],
            ['code' => 'paiement.paiements.create', 'label' => 'Enregistrer des paiements', 'module' => 'paiement', 'action' => 'create', 'is_critical' => false],
            ['code' => 'paiement.paiements.cancel', 'label' => 'Annuler des paiements', 'module' => 'paiement', 'action' => 'cancel', 'is_critical' => true],
            ['code' => 'finance.view', 'label' => 'Accéder aux finances', 'module' => 'finance', 'action' => 'view', 'is_critical' => false],
            ['code' => 'finance.comptes.view', 'label' => 'Voir les comptes', 'module' => 'finance', 'action' => 'view', 'is_critical' => false],
            ['code' => 'finance.comptes.manage', 'label' => 'Gérer les comptes', 'module' => 'finance', 'action' => 'manage', 'is_critical' => false],
            ['code' => 'finance.transactions.view', 'label' => 'Voir les transactions', 'module' => 'finance', 'action' => 'view', 'is_critical' => false],
            ['code' => 'finance.transactions.create', 'label' => 'Créer des transactions', 'module' => 'finance', 'action' => 'create', 'is_critical' => false],
            ['code' => 'finance.transactions.cancel', 'label' => 'Annuler des transactions', 'module' => 'finance', 'action' => 'cancel', 'is_critical' => true],
            ['code' => 'finance.modes.manage', 'label' => 'Gérer les modes de paiement', 'module' => 'finance', 'action' => 'manage', 'is_critical' => true],
            ['code' => 'fournisseur.view', 'label' => 'Voir les fournisseurs', 'module' => 'fournisseur', 'action' => 'view', 'is_critical' => false],
            ['code' => 'fournisseur.manage', 'label' => 'Gérer les fournisseurs', 'module' => 'fournisseur', 'action' => 'manage', 'is_critical' => false],
            ['code' => 'fournisseur.commandes.view', 'label' => 'Voir les commandes fournisseur', 'module' => 'fournisseur', 'action' => 'view', 'is_critical' => false],
            ['code' => 'fournisseur.commandes.create', 'label' => 'Créer des commandes fournisseur', 'module' => 'fournisseur', 'action' => 'create', 'is_critical' => false],
            ['code' => 'fournisseur.commandes.confirm', 'label' => 'Confirmer commandes fournisseur', 'module' => 'fournisseur', 'action' => 'confirm', 'is_critical' => false],
            ['code' => 'fournisseur.commandes.receive', 'label' => 'Réceptionner commandes fournisseur', 'module' => 'fournisseur', 'action' => 'receive', 'is_critical' => false],
            ['code' => 'fournisseur.commandes.cancel', 'label' => 'Annuler commandes fournisseur', 'module' => 'fournisseur', 'action' => 'cancel', 'is_critical' => true],
            ['code' => 'fournisseur.dettes.view', 'label' => 'Voir les dettes fournisseur', 'module' => 'fournisseur', 'action' => 'view', 'is_critical' => false],
            ['code' => 'fournisseur.dettes.create', 'label' => 'Créer des dettes fournisseur', 'module' => 'fournisseur', 'action' => 'create', 'is_critical' => false],
            ['code' => 'fournisseur.paiements.create', 'label' => 'Enregistrer décaissements fournisseur', 'module' => 'fournisseur', 'action' => 'create', 'is_critical' => false],
            ['code' => 'fournisseur.paiements.cancel', 'label' => 'Annuler décaissements fournisseur', 'module' => 'fournisseur', 'action' => 'cancel', 'is_critical' => true],
            ['code' => 'impression.settings.view', 'label' => 'Voir les réglages d\'impression', 'module' => 'impression', 'action' => 'view', 'is_critical' => false],
            ['code' => 'impression.settings.manage', 'label' => 'Gérer les réglages d\'impression', 'module' => 'impression', 'action' => 'manage', 'is_critical' => true],
            ['code' => 'impression.documents.print', 'label' => 'Imprimer des documents', 'module' => 'impression', 'action' => 'print', 'is_critical' => false],
            ['code' => 'impression.tables.export', 'label' => 'Exporter des tableaux', 'module' => 'impression', 'action' => 'export', 'is_critical' => false],
        ];
    }

    /** @return list<string> */
    public static function allCodes(): array
    {
        return array_column(self::all(), 'code');
    }

    /**
     * @return array<string, list<string>>
     */
    public static function rolePermissions(): array
    {
        $all = self::allCodes();

        $gerant = array_values(array_filter($all, static fn (string $code): bool => !str_starts_with($code, 'access.users')
            && !str_starts_with($code, 'access.roles')));

        $analyticsAll = [
            'analytics.view', 'analytics.sales.view', 'analytics.inventory.view',
            'analytics.finance.view', 'analytics.clients.view', 'analytics.suppliers.view', 'analytics.export',
        ];

        $analyticsComptable = [
            'analytics.view', 'analytics.sales.view', 'analytics.finance.view',
            'analytics.clients.view', 'analytics.export',
        ];

        $analyticsCaissier = [
            'analytics.view', 'analytics.sales.view', 'analytics.clients.view',
        ];

        $analyticsMagasinier = [
            'analytics.view', 'analytics.inventory.view', 'analytics.suppliers.view',
        ];

        $analyticsConsultant = [
            'analytics.view', 'analytics.sales.view', 'analytics.inventory.view',
            'analytics.finance.view', 'analytics.clients.view', 'analytics.suppliers.view',
        ];

        $caissier = [
            'dashboard.view', ...$analyticsCaissier,
            'client.clients.view', 'client.creances.view', 'client.journal.view',
            'catalog.categories.view', 'catalog.products.view', 'inventory.stock.view', 'inventory.alerts.view',
            'commerce.cart.use', 'commerce.ventes.view', 'commerce.ventes.create',
            'commerce.commandes.view', 'commerce.commandes.create',
            'commerce.devis.view', 'commerce.devis.create', 'commerce.devis.convert',
            'facturation.factures.view', 'paiement.paiements.view', 'paiement.paiements.create',
            'impression.settings.view', 'impression.documents.print', 'impression.tables.export',
        ];

        $magasinier = [
            'dashboard.view', ...$analyticsMagasinier,
            'catalog.categories.view', 'catalog.products.view', 'catalog.variants.manage',
            'inventory.stock.view', 'inventory.lots.create', 'inventory.stock_out', 'inventory.adjustments',
            'inventory.policy.manage', 'inventory.movements.view', 'inventory.alerts.view',
            'commerce.livraisons.view', 'commerce.livraisons.create', 'commerce.livraisons.deliver',
            'fournisseur.view', 'fournisseur.commandes.view', 'fournisseur.commandes.create',
            'fournisseur.commandes.confirm', 'fournisseur.commandes.receive',
            'impression.settings.view', 'impression.documents.print', 'impression.tables.export',
        ];

        $comptable = [
            'dashboard.view', ...$analyticsComptable,
            'client.clients.view', 'client.creances.view', 'client.journal.view',
            'catalog.categories.view', 'catalog.products.view', 'inventory.movements.view',
            'commerce.ventes.view', 'commerce.commandes.view', 'commerce.devis.view',
            'facturation.factures.view', 'paiement.paiements.view', 'paiement.paiements.create', 'paiement.paiements.cancel',
            'finance.view', 'finance.comptes.view', 'finance.transactions.view', 'finance.transactions.create',
            'finance.transactions.cancel',
            'fournisseur.view', 'fournisseur.commandes.view', 'fournisseur.dettes.view', 'fournisseur.dettes.create',
            'fournisseur.paiements.create', 'fournisseur.paiements.cancel',
            'impression.settings.view', 'impression.documents.print', 'impression.tables.export',
        ];

        $consultant = array_values(array_filter($all, static fn (string $code): bool => str_ends_with($code, '.view')
            || $code === 'finance.view'
            || $code === 'commerce.cart.use'
            || $code === 'impression.settings.view'));
        $consultant = array_values(array_unique(array_merge($consultant, $analyticsConsultant)));

        return [
            'admin' => $all,
            'gerant' => array_values(array_unique(array_merge($gerant, ['access.audit.view'], $analyticsAll))),
            'caissier' => $caissier,
            'magasinier' => $magasinier,
            'comptable' => $comptable,
            'consultant' => $consultant,
        ];
    }

    /** @return list<array{code: string, label: string, description: string, is_system: bool}> */
    public static function predefinedRoles(): array
    {
        return [
            ['code' => 'admin', 'label' => 'Administrateur', 'description' => 'Accès total et gestion des utilisateurs', 'is_system' => true],
            ['code' => 'gerant', 'label' => 'Gérant', 'description' => 'Toutes opérations métier', 'is_system' => true],
            ['code' => 'caissier', 'label' => 'Caissier', 'description' => 'Commerce et paiements clients', 'is_system' => true],
            ['code' => 'magasinier', 'label' => 'Magasinier', 'description' => 'Stock et réceptions fournisseur', 'is_system' => true],
            ['code' => 'comptable', 'label' => 'Comptable', 'description' => 'Finances, créances et dettes', 'is_system' => true],
            ['code' => 'consultant', 'label' => 'Consultant', 'description' => 'Lecture seule', 'is_system' => true],
        ];
    }

    public static function symfonyRole(string $roleCode): string
    {
        return 'ROLE_'.strtoupper($roleCode);
    }
}
