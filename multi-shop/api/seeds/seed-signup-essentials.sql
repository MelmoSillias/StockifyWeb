-- Seed Data Plane Stockify Multi-Shop (RBAC essentiel)
-- DB : stockify_multishop (MariaDB / MySQL)
--
-- Prerequis pour tester la creation de comptes (signup) :
--   - role "gerant" + permissions associees (obligatoire au provisionnement)
--   - unites de mesure de base (catalogue apres creation de boutique)
--
-- Ne cree PAS de shops / users / tenant_accounts : le signup les cree.
--
-- Control Plane (sim_saas_admin) : executer aussi seed-dev.sql a la racine SAAS.
--
-- Usage :
--   mysql -h 127.0.0.1 -u root stockify_multishop < seeds/seed-signup-essentials.sql
--
-- Idempotent : re-executable (supprime puis reinsere les lignes seedes).
-- Genere depuis PermissionCatalog via seeds/generate-signup-seed.php

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- UUIDs stables (Doctrine uuid = BINARY(16), ordre RFC)
SET @p_access_users_view = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000001', '-', ''));
SET @p_access_users_create = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000002', '-', ''));
SET @p_access_users_update = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000003', '-', ''));
SET @p_access_users_suspend = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000004', '-', ''));
SET @p_access_roles_view = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000005', '-', ''));
SET @p_access_roles_manage = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000006', '-', ''));
SET @p_access_audit_view = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000007', '-', ''));
SET @p_platform_shops_view = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000008', '-', ''));
SET @p_platform_shops_manage = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000009', '-', ''));
SET @p_platform_shop_users_manage = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000010', '-', ''));
SET @p_dashboard_view = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000011', '-', ''));
SET @p_analytics_view = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000012', '-', ''));
SET @p_analytics_sales_view = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000013', '-', ''));
SET @p_analytics_inventory_view = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000014', '-', ''));
SET @p_analytics_finance_view = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000015', '-', ''));
SET @p_analytics_clients_view = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000016', '-', ''));
SET @p_analytics_suppliers_view = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000017', '-', ''));
SET @p_analytics_export = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000018', '-', ''));
SET @p_client_clients_view = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000019', '-', ''));
SET @p_client_clients_create = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000020', '-', ''));
SET @p_client_clients_update = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000021', '-', ''));
SET @p_client_clients_delete = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000022', '-', ''));
SET @p_client_creances_view = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000023', '-', ''));
SET @p_client_journal_view = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000024', '-', ''));
SET @p_catalog_categories_view = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000025', '-', ''));
SET @p_catalog_categories_manage = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000026', '-', ''));
SET @p_catalog_products_view = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000027', '-', ''));
SET @p_catalog_products_create = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000028', '-', ''));
SET @p_catalog_products_update = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000029', '-', ''));
SET @p_catalog_products_delete = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000030', '-', ''));
SET @p_catalog_variants_manage = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000031', '-', ''));
SET @p_inventory_stock_view = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000032', '-', ''));
SET @p_inventory_lots_create = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000033', '-', ''));
SET @p_inventory_stock_out = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000034', '-', ''));
SET @p_inventory_adjustments = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000035', '-', ''));
SET @p_inventory_policy_manage = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000036', '-', ''));
SET @p_inventory_movements_view = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000037', '-', ''));
SET @p_inventory_alerts_view = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000038', '-', ''));
SET @p_commerce_cart_use = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000039', '-', ''));
SET @p_commerce_ventes_view = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000040', '-', ''));
SET @p_commerce_ventes_create = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000041', '-', ''));
SET @p_commerce_ventes_cancel = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000042', '-', ''));
SET @p_commerce_commandes_view = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000043', '-', ''));
SET @p_commerce_commandes_create = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000044', '-', ''));
SET @p_commerce_commandes_confirm = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000045', '-', ''));
SET @p_commerce_commandes_cancel = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000046', '-', ''));
SET @p_commerce_devis_view = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000047', '-', ''));
SET @p_commerce_devis_create = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000048', '-', ''));
SET @p_commerce_devis_convert = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000049', '-', ''));
SET @p_commerce_devis_cancel = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000050', '-', ''));
SET @p_commerce_livraisons_view = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000051', '-', ''));
SET @p_commerce_livraisons_create = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000052', '-', ''));
SET @p_commerce_livraisons_deliver = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000053', '-', ''));
SET @p_facturation_factures_view = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000054', '-', ''));
SET @p_paiement_paiements_view = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000055', '-', ''));
SET @p_paiement_paiements_create = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000056', '-', ''));
SET @p_paiement_paiements_cancel = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000057', '-', ''));
SET @p_finance_view = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000058', '-', ''));
SET @p_finance_comptes_view = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000059', '-', ''));
SET @p_finance_comptes_manage = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000060', '-', ''));
SET @p_finance_transactions_view = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000061', '-', ''));
SET @p_finance_transactions_create = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000062', '-', ''));
SET @p_finance_transactions_cancel = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000063', '-', ''));
SET @p_finance_modes_manage = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000064', '-', ''));
SET @p_fournisseur_view = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000065', '-', ''));
SET @p_fournisseur_manage = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000066', '-', ''));
SET @p_fournisseur_commandes_view = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000067', '-', ''));
SET @p_fournisseur_commandes_create = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000068', '-', ''));
SET @p_fournisseur_commandes_confirm = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000069', '-', ''));
SET @p_fournisseur_commandes_receive = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000070', '-', ''));
SET @p_fournisseur_commandes_cancel = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000071', '-', ''));
SET @p_fournisseur_dettes_view = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000072', '-', ''));
SET @p_fournisseur_dettes_create = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000073', '-', ''));
SET @p_fournisseur_paiements_create = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000074', '-', ''));
SET @p_fournisseur_paiements_cancel = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000075', '-', ''));
SET @p_impression_settings_view = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000076', '-', ''));
SET @p_impression_settings_manage = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000077', '-', ''));
SET @p_impression_documents_print = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000078', '-', ''));
SET @p_impression_tables_export = UNHEX(REPLACE('019a0000-0000-7000-8000-000000000079', '-', ''));
SET @role_admin = UNHEX(REPLACE('019a0000-0000-7000-9000-000000000001', '-', ''));
SET @role_gerant = UNHEX(REPLACE('019a0000-0000-7000-9000-000000000002', '-', ''));
SET @role_caissier = UNHEX(REPLACE('019a0000-0000-7000-9000-000000000003', '-', ''));
SET @role_magasinier = UNHEX(REPLACE('019a0000-0000-7000-9000-000000000004', '-', ''));
SET @role_comptable = UNHEX(REPLACE('019a0000-0000-7000-9000-000000000005', '-', ''));
SET @role_consultant = UNHEX(REPLACE('019a0000-0000-7000-9000-000000000006', '-', ''));
SET @uom_piece = UNHEX(REPLACE('019a0000-0000-7000-a000-000000000001', '-', ''));
SET @uom_kg = UNHEX(REPLACE('019a0000-0000-7000-a000-000000000002', '-', ''));
SET @uom_liter = UNHEX(REPLACE('019a0000-0000-7000-a000-000000000003', '-', ''));
SET @uom_carton = UNHEX(REPLACE('019a0000-0000-7000-a000-000000000004', '-', ''));

SET @now = UTC_TIMESTAMP();

START TRANSACTION;

-- Nettoyage ciblé (ordre FK)
DELETE FROM role_permissions WHERE role_id IN (
  @role_admin, @role_gerant, @role_caissier, @role_magasinier, @role_comptable, @role_consultant
) OR role_id IN (SELECT id FROM (SELECT id FROM roles WHERE code IN ('admin', 'gerant', 'caissier', 'magasinier', 'comptable', 'consultant')) AS _r);
DELETE FROM roles WHERE id IN (
  @role_admin, @role_gerant, @role_caissier, @role_magasinier, @role_comptable, @role_consultant
) OR code IN ('admin', 'gerant', 'caissier', 'magasinier', 'comptable', 'consultant');
DELETE FROM permissions WHERE code IN ('access.users.view', 'access.users.create', 'access.users.update', 'access.users.suspend', 'access.roles.view', 'access.roles.manage', 'access.audit.view', 'platform.shops.view', 'platform.shops.manage', 'platform.shop_users.manage', 'dashboard.view', 'analytics.view', 'analytics.sales.view', 'analytics.inventory.view', 'analytics.finance.view', 'analytics.clients.view', 'analytics.suppliers.view', 'analytics.export', 'client.clients.view', 'client.clients.create', 'client.clients.update', 'client.clients.delete', 'client.creances.view', 'client.journal.view', 'catalog.categories.view', 'catalog.categories.manage', 'catalog.products.view', 'catalog.products.create', 'catalog.products.update', 'catalog.products.delete', 'catalog.variants.manage', 'inventory.stock.view', 'inventory.lots.create', 'inventory.stock_out', 'inventory.adjustments', 'inventory.policy.manage', 'inventory.movements.view', 'inventory.alerts.view', 'commerce.cart.use', 'commerce.ventes.view', 'commerce.ventes.create', 'commerce.ventes.cancel', 'commerce.commandes.view', 'commerce.commandes.create', 'commerce.commandes.confirm', 'commerce.commandes.cancel', 'commerce.devis.view', 'commerce.devis.create', 'commerce.devis.convert', 'commerce.devis.cancel', 'commerce.livraisons.view', 'commerce.livraisons.create', 'commerce.livraisons.deliver', 'facturation.factures.view', 'paiement.paiements.view', 'paiement.paiements.create', 'paiement.paiements.cancel', 'finance.view', 'finance.comptes.view', 'finance.comptes.manage', 'finance.transactions.view', 'finance.transactions.create', 'finance.transactions.cancel', 'finance.modes.manage', 'fournisseur.view', 'fournisseur.manage', 'fournisseur.commandes.view', 'fournisseur.commandes.create', 'fournisseur.commandes.confirm', 'fournisseur.commandes.receive', 'fournisseur.commandes.cancel', 'fournisseur.dettes.view', 'fournisseur.dettes.create', 'fournisseur.paiements.create', 'fournisseur.paiements.cancel', 'impression.settings.view', 'impression.settings.manage', 'impression.documents.print', 'impression.tables.export');
DELETE FROM units_of_measure WHERE id IN (@uom_piece, @uom_kg, @uom_liter, @uom_carton) OR code IN ('piece', 'kg', 'liter', 'carton');

-- Permissions catalogue
INSERT INTO permissions (id, code, label, module, action, is_critical) VALUES
  (@p_access_users_view, 'access.users.view', 'Voir les utilisateurs', 'access', 'view', 1),
  (@p_access_users_create, 'access.users.create', 'Créer des utilisateurs', 'access', 'create', 1),
  (@p_access_users_update, 'access.users.update', 'Modifier les utilisateurs', 'access', 'update', 1),
  (@p_access_users_suspend, 'access.users.suspend', 'Suspendre les utilisateurs', 'access', 'suspend', 1),
  (@p_access_roles_view, 'access.roles.view', 'Voir les rôles', 'access', 'view', 1),
  (@p_access_roles_manage, 'access.roles.manage', 'Gérer les rôles', 'access', 'manage', 1),
  (@p_access_audit_view, 'access.audit.view', 'Consulter le journal d\'audit', 'access', 'view', 0),
  (@p_platform_shops_view, 'platform.shops.view', 'Voir les boutiques', 'platform', 'view', 1),
  (@p_platform_shops_manage, 'platform.shops.manage', 'Gérer les boutiques', 'platform', 'manage', 1),
  (@p_platform_shop_users_manage, 'platform.shop_users.manage', 'Gérer les utilisateurs par boutique', 'platform', 'manage', 1),
  (@p_dashboard_view, 'dashboard.view', 'Voir le tableau de bord', 'dashboard', 'view', 0),
  (@p_analytics_view, 'analytics.view', 'Accéder aux analytics', 'analytics', 'view', 0),
  (@p_analytics_sales_view, 'analytics.sales.view', 'Voir les rapports ventes', 'analytics', 'view', 0),
  (@p_analytics_inventory_view, 'analytics.inventory.view', 'Voir les rapports stock', 'analytics', 'view', 0),
  (@p_analytics_finance_view, 'analytics.finance.view', 'Voir les rapports finance', 'analytics', 'view', 0),
  (@p_analytics_clients_view, 'analytics.clients.view', 'Voir les rapports clientèle', 'analytics', 'view', 0),
  (@p_analytics_suppliers_view, 'analytics.suppliers.view', 'Voir les rapports fournisseurs', 'analytics', 'view', 0),
  (@p_analytics_export, 'analytics.export', 'Exporter les rapports analytics', 'analytics', 'export', 0),
  (@p_client_clients_view, 'client.clients.view', 'Voir les clients', 'client', 'view', 0),
  (@p_client_clients_create, 'client.clients.create', 'Créer des clients', 'client', 'create', 0),
  (@p_client_clients_update, 'client.clients.update', 'Modifier les clients', 'client', 'update', 0),
  (@p_client_clients_delete, 'client.clients.delete', 'Supprimer les clients', 'client', 'delete', 1),
  (@p_client_creances_view, 'client.creances.view', 'Voir les créances clients', 'client', 'view', 0),
  (@p_client_journal_view, 'client.journal.view', 'Voir le journal client', 'client', 'view', 0),
  (@p_catalog_categories_view, 'catalog.categories.view', 'Voir les catégories', 'catalog', 'view', 0),
  (@p_catalog_categories_manage, 'catalog.categories.manage', 'Gérer les catégories', 'catalog', 'manage', 0),
  (@p_catalog_products_view, 'catalog.products.view', 'Voir les produits', 'catalog', 'view', 0),
  (@p_catalog_products_create, 'catalog.products.create', 'Créer des produits', 'catalog', 'create', 0),
  (@p_catalog_products_update, 'catalog.products.update', 'Modifier les produits', 'catalog', 'update', 0),
  (@p_catalog_products_delete, 'catalog.products.delete', 'Supprimer les produits', 'catalog', 'delete', 1),
  (@p_catalog_variants_manage, 'catalog.variants.manage', 'Gérer les variantes', 'catalog', 'manage', 0),
  (@p_inventory_stock_view, 'inventory.stock.view', 'Voir le stock', 'inventory', 'view', 0),
  (@p_inventory_lots_create, 'inventory.lots.create', 'Créer des lots', 'inventory', 'create', 0),
  (@p_inventory_stock_out, 'inventory.stock_out', 'Sorties de stock', 'inventory', 'stock_out', 0),
  (@p_inventory_adjustments, 'inventory.adjustments', 'Ajustements de stock', 'inventory', 'adjust', 0),
  (@p_inventory_policy_manage, 'inventory.policy.manage', 'Gérer les politiques de stock', 'inventory', 'manage', 0),
  (@p_inventory_movements_view, 'inventory.movements.view', 'Voir les mouvements', 'inventory', 'view', 0),
  (@p_inventory_alerts_view, 'inventory.alerts.view', 'Voir les alertes stock', 'inventory', 'view', 0),
  (@p_commerce_cart_use, 'commerce.cart.use', 'Utiliser le panier', 'commerce', 'use', 0),
  (@p_commerce_ventes_view, 'commerce.ventes.view', 'Voir les ventes', 'commerce', 'view', 0),
  (@p_commerce_ventes_create, 'commerce.ventes.create', 'Créer des ventes', 'commerce', 'create', 0),
  (@p_commerce_ventes_cancel, 'commerce.ventes.cancel', 'Annuler des ventes', 'commerce', 'cancel', 1),
  (@p_commerce_commandes_view, 'commerce.commandes.view', 'Voir les commandes', 'commerce', 'view', 0),
  (@p_commerce_commandes_create, 'commerce.commandes.create', 'Créer des commandes', 'commerce', 'create', 0),
  (@p_commerce_commandes_confirm, 'commerce.commandes.confirm', 'Confirmer des commandes', 'commerce', 'confirm', 0),
  (@p_commerce_commandes_cancel, 'commerce.commandes.cancel', 'Annuler des commandes', 'commerce', 'cancel', 1),
  (@p_commerce_devis_view, 'commerce.devis.view', 'Voir les devis', 'commerce', 'view', 0),
  (@p_commerce_devis_create, 'commerce.devis.create', 'Créer des devis', 'commerce', 'create', 0),
  (@p_commerce_devis_convert, 'commerce.devis.convert', 'Convertir des devis', 'commerce', 'convert', 0),
  (@p_commerce_devis_cancel, 'commerce.devis.cancel', 'Annuler des devis', 'commerce', 'cancel', 1),
  (@p_commerce_livraisons_view, 'commerce.livraisons.view', 'Voir les livraisons', 'commerce', 'view', 0),
  (@p_commerce_livraisons_create, 'commerce.livraisons.create', 'Créer des bons de livraison', 'commerce', 'create', 0),
  (@p_commerce_livraisons_deliver, 'commerce.livraisons.deliver', 'Marquer livré', 'commerce', 'deliver', 0),
  (@p_facturation_factures_view, 'facturation.factures.view', 'Voir les factures', 'facturation', 'view', 0),
  (@p_paiement_paiements_view, 'paiement.paiements.view', 'Voir les paiements', 'paiement', 'view', 0),
  (@p_paiement_paiements_create, 'paiement.paiements.create', 'Enregistrer des paiements', 'paiement', 'create', 0),
  (@p_paiement_paiements_cancel, 'paiement.paiements.cancel', 'Annuler des paiements', 'paiement', 'cancel', 1),
  (@p_finance_view, 'finance.view', 'Accéder aux finances', 'finance', 'view', 0),
  (@p_finance_comptes_view, 'finance.comptes.view', 'Voir les comptes', 'finance', 'view', 0),
  (@p_finance_comptes_manage, 'finance.comptes.manage', 'Gérer les comptes', 'finance', 'manage', 0),
  (@p_finance_transactions_view, 'finance.transactions.view', 'Voir les transactions', 'finance', 'view', 0),
  (@p_finance_transactions_create, 'finance.transactions.create', 'Créer des transactions', 'finance', 'create', 0),
  (@p_finance_transactions_cancel, 'finance.transactions.cancel', 'Annuler des transactions', 'finance', 'cancel', 1),
  (@p_finance_modes_manage, 'finance.modes.manage', 'Gérer les modes de paiement', 'finance', 'manage', 1),
  (@p_fournisseur_view, 'fournisseur.view', 'Voir les fournisseurs', 'fournisseur', 'view', 0),
  (@p_fournisseur_manage, 'fournisseur.manage', 'Gérer les fournisseurs', 'fournisseur', 'manage', 0),
  (@p_fournisseur_commandes_view, 'fournisseur.commandes.view', 'Voir les commandes fournisseur', 'fournisseur', 'view', 0),
  (@p_fournisseur_commandes_create, 'fournisseur.commandes.create', 'Créer des commandes fournisseur', 'fournisseur', 'create', 0),
  (@p_fournisseur_commandes_confirm, 'fournisseur.commandes.confirm', 'Confirmer commandes fournisseur', 'fournisseur', 'confirm', 0),
  (@p_fournisseur_commandes_receive, 'fournisseur.commandes.receive', 'Réceptionner commandes fournisseur', 'fournisseur', 'receive', 0),
  (@p_fournisseur_commandes_cancel, 'fournisseur.commandes.cancel', 'Annuler commandes fournisseur', 'fournisseur', 'cancel', 1),
  (@p_fournisseur_dettes_view, 'fournisseur.dettes.view', 'Voir les dettes fournisseur', 'fournisseur', 'view', 0),
  (@p_fournisseur_dettes_create, 'fournisseur.dettes.create', 'Créer des dettes fournisseur', 'fournisseur', 'create', 0),
  (@p_fournisseur_paiements_create, 'fournisseur.paiements.create', 'Enregistrer décaissements fournisseur', 'fournisseur', 'create', 0),
  (@p_fournisseur_paiements_cancel, 'fournisseur.paiements.cancel', 'Annuler décaissements fournisseur', 'fournisseur', 'cancel', 1),
  (@p_impression_settings_view, 'impression.settings.view', 'Voir les réglages d\'impression', 'impression', 'view', 0),
  (@p_impression_settings_manage, 'impression.settings.manage', 'Gérer les réglages d\'impression', 'impression', 'manage', 1),
  (@p_impression_documents_print, 'impression.documents.print', 'Imprimer des documents', 'impression', 'print', 0),
  (@p_impression_tables_export, 'impression.tables.export', 'Exporter des tableaux', 'impression', 'export', 0);

-- Rôles système (gerant requis pour signup / provisionnement admin boutique)
INSERT INTO roles (id, code, label, description, is_system, is_active, created_at, updated_at) VALUES
  (@role_admin, 'admin', 'Administrateur', 'Accès total et gestion des utilisateurs', 1, 1, @now, @now),
  (@role_gerant, 'gerant', 'Gérant', 'Toutes opérations métier', 1, 1, @now, @now),
  (@role_caissier, 'caissier', 'Caissier', 'Commerce et paiements clients', 1, 1, @now, @now),
  (@role_magasinier, 'magasinier', 'Magasinier', 'Stock et réceptions fournisseur', 1, 1, @now, @now),
  (@role_comptable, 'comptable', 'Comptable', 'Finances, créances et dettes', 1, 1, @now, @now),
  (@role_consultant, 'consultant', 'Consultant', 'Lecture seule', 1, 1, @now, @now);

-- Rôle ↔ permissions
INSERT INTO role_permissions (role_id, permission_id) VALUES
  (@role_admin, @p_access_users_view),
  (@role_admin, @p_access_users_create),
  (@role_admin, @p_access_users_update),
  (@role_admin, @p_access_users_suspend),
  (@role_admin, @p_access_roles_view),
  (@role_admin, @p_access_roles_manage),
  (@role_admin, @p_access_audit_view),
  (@role_admin, @p_platform_shops_view),
  (@role_admin, @p_platform_shops_manage),
  (@role_admin, @p_platform_shop_users_manage),
  (@role_admin, @p_dashboard_view),
  (@role_admin, @p_analytics_view),
  (@role_admin, @p_analytics_sales_view),
  (@role_admin, @p_analytics_inventory_view),
  (@role_admin, @p_analytics_finance_view),
  (@role_admin, @p_analytics_clients_view),
  (@role_admin, @p_analytics_suppliers_view),
  (@role_admin, @p_analytics_export),
  (@role_admin, @p_client_clients_view),
  (@role_admin, @p_client_clients_create),
  (@role_admin, @p_client_clients_update),
  (@role_admin, @p_client_clients_delete),
  (@role_admin, @p_client_creances_view),
  (@role_admin, @p_client_journal_view),
  (@role_admin, @p_catalog_categories_view),
  (@role_admin, @p_catalog_categories_manage),
  (@role_admin, @p_catalog_products_view),
  (@role_admin, @p_catalog_products_create),
  (@role_admin, @p_catalog_products_update),
  (@role_admin, @p_catalog_products_delete),
  (@role_admin, @p_catalog_variants_manage),
  (@role_admin, @p_inventory_stock_view),
  (@role_admin, @p_inventory_lots_create),
  (@role_admin, @p_inventory_stock_out),
  (@role_admin, @p_inventory_adjustments),
  (@role_admin, @p_inventory_policy_manage),
  (@role_admin, @p_inventory_movements_view),
  (@role_admin, @p_inventory_alerts_view),
  (@role_admin, @p_commerce_cart_use),
  (@role_admin, @p_commerce_ventes_view),
  (@role_admin, @p_commerce_ventes_create),
  (@role_admin, @p_commerce_ventes_cancel),
  (@role_admin, @p_commerce_commandes_view),
  (@role_admin, @p_commerce_commandes_create),
  (@role_admin, @p_commerce_commandes_confirm),
  (@role_admin, @p_commerce_commandes_cancel),
  (@role_admin, @p_commerce_devis_view),
  (@role_admin, @p_commerce_devis_create),
  (@role_admin, @p_commerce_devis_convert),
  (@role_admin, @p_commerce_devis_cancel),
  (@role_admin, @p_commerce_livraisons_view),
  (@role_admin, @p_commerce_livraisons_create),
  (@role_admin, @p_commerce_livraisons_deliver),
  (@role_admin, @p_facturation_factures_view),
  (@role_admin, @p_paiement_paiements_view),
  (@role_admin, @p_paiement_paiements_create),
  (@role_admin, @p_paiement_paiements_cancel),
  (@role_admin, @p_finance_view),
  (@role_admin, @p_finance_comptes_view),
  (@role_admin, @p_finance_comptes_manage),
  (@role_admin, @p_finance_transactions_view),
  (@role_admin, @p_finance_transactions_create),
  (@role_admin, @p_finance_transactions_cancel),
  (@role_admin, @p_finance_modes_manage),
  (@role_admin, @p_fournisseur_view),
  (@role_admin, @p_fournisseur_manage),
  (@role_admin, @p_fournisseur_commandes_view),
  (@role_admin, @p_fournisseur_commandes_create),
  (@role_admin, @p_fournisseur_commandes_confirm),
  (@role_admin, @p_fournisseur_commandes_receive),
  (@role_admin, @p_fournisseur_commandes_cancel),
  (@role_admin, @p_fournisseur_dettes_view),
  (@role_admin, @p_fournisseur_dettes_create),
  (@role_admin, @p_fournisseur_paiements_create),
  (@role_admin, @p_fournisseur_paiements_cancel),
  (@role_admin, @p_impression_settings_view),
  (@role_admin, @p_impression_settings_manage),
  (@role_admin, @p_impression_documents_print),
  (@role_admin, @p_impression_tables_export),
  (@role_gerant, @p_access_audit_view),
  (@role_gerant, @p_platform_shops_view),
  (@role_gerant, @p_platform_shops_manage),
  (@role_gerant, @p_platform_shop_users_manage),
  (@role_gerant, @p_dashboard_view),
  (@role_gerant, @p_analytics_view),
  (@role_gerant, @p_analytics_sales_view),
  (@role_gerant, @p_analytics_inventory_view),
  (@role_gerant, @p_analytics_finance_view),
  (@role_gerant, @p_analytics_clients_view),
  (@role_gerant, @p_analytics_suppliers_view),
  (@role_gerant, @p_analytics_export),
  (@role_gerant, @p_client_clients_view),
  (@role_gerant, @p_client_clients_create),
  (@role_gerant, @p_client_clients_update),
  (@role_gerant, @p_client_clients_delete),
  (@role_gerant, @p_client_creances_view),
  (@role_gerant, @p_client_journal_view),
  (@role_gerant, @p_catalog_categories_view),
  (@role_gerant, @p_catalog_categories_manage),
  (@role_gerant, @p_catalog_products_view),
  (@role_gerant, @p_catalog_products_create),
  (@role_gerant, @p_catalog_products_update),
  (@role_gerant, @p_catalog_products_delete),
  (@role_gerant, @p_catalog_variants_manage),
  (@role_gerant, @p_inventory_stock_view),
  (@role_gerant, @p_inventory_lots_create),
  (@role_gerant, @p_inventory_stock_out),
  (@role_gerant, @p_inventory_adjustments),
  (@role_gerant, @p_inventory_policy_manage),
  (@role_gerant, @p_inventory_movements_view),
  (@role_gerant, @p_inventory_alerts_view),
  (@role_gerant, @p_commerce_cart_use),
  (@role_gerant, @p_commerce_ventes_view),
  (@role_gerant, @p_commerce_ventes_create),
  (@role_gerant, @p_commerce_ventes_cancel),
  (@role_gerant, @p_commerce_commandes_view),
  (@role_gerant, @p_commerce_commandes_create),
  (@role_gerant, @p_commerce_commandes_confirm),
  (@role_gerant, @p_commerce_commandes_cancel),
  (@role_gerant, @p_commerce_devis_view),
  (@role_gerant, @p_commerce_devis_create),
  (@role_gerant, @p_commerce_devis_convert),
  (@role_gerant, @p_commerce_devis_cancel),
  (@role_gerant, @p_commerce_livraisons_view),
  (@role_gerant, @p_commerce_livraisons_create),
  (@role_gerant, @p_commerce_livraisons_deliver),
  (@role_gerant, @p_facturation_factures_view),
  (@role_gerant, @p_paiement_paiements_view),
  (@role_gerant, @p_paiement_paiements_create),
  (@role_gerant, @p_paiement_paiements_cancel),
  (@role_gerant, @p_finance_view),
  (@role_gerant, @p_finance_comptes_view),
  (@role_gerant, @p_finance_comptes_manage),
  (@role_gerant, @p_finance_transactions_view),
  (@role_gerant, @p_finance_transactions_create),
  (@role_gerant, @p_finance_transactions_cancel),
  (@role_gerant, @p_finance_modes_manage),
  (@role_gerant, @p_fournisseur_view),
  (@role_gerant, @p_fournisseur_manage),
  (@role_gerant, @p_fournisseur_commandes_view),
  (@role_gerant, @p_fournisseur_commandes_create),
  (@role_gerant, @p_fournisseur_commandes_confirm),
  (@role_gerant, @p_fournisseur_commandes_receive),
  (@role_gerant, @p_fournisseur_commandes_cancel),
  (@role_gerant, @p_fournisseur_dettes_view),
  (@role_gerant, @p_fournisseur_dettes_create),
  (@role_gerant, @p_fournisseur_paiements_create),
  (@role_gerant, @p_fournisseur_paiements_cancel),
  (@role_gerant, @p_impression_settings_view),
  (@role_gerant, @p_impression_settings_manage),
  (@role_gerant, @p_impression_documents_print),
  (@role_gerant, @p_impression_tables_export),
  (@role_gerant, @p_access_roles_view),
  (@role_caissier, @p_dashboard_view),
  (@role_caissier, @p_analytics_view),
  (@role_caissier, @p_analytics_sales_view),
  (@role_caissier, @p_analytics_clients_view),
  (@role_caissier, @p_client_clients_view),
  (@role_caissier, @p_client_creances_view),
  (@role_caissier, @p_client_journal_view),
  (@role_caissier, @p_catalog_categories_view),
  (@role_caissier, @p_catalog_products_view),
  (@role_caissier, @p_inventory_stock_view),
  (@role_caissier, @p_inventory_alerts_view),
  (@role_caissier, @p_commerce_cart_use),
  (@role_caissier, @p_commerce_ventes_view),
  (@role_caissier, @p_commerce_ventes_create),
  (@role_caissier, @p_commerce_commandes_view),
  (@role_caissier, @p_commerce_commandes_create),
  (@role_caissier, @p_commerce_devis_view),
  (@role_caissier, @p_commerce_devis_create),
  (@role_caissier, @p_commerce_devis_convert),
  (@role_caissier, @p_facturation_factures_view),
  (@role_caissier, @p_paiement_paiements_view),
  (@role_caissier, @p_paiement_paiements_create),
  (@role_caissier, @p_impression_settings_view),
  (@role_caissier, @p_impression_documents_print),
  (@role_caissier, @p_impression_tables_export),
  (@role_magasinier, @p_dashboard_view),
  (@role_magasinier, @p_analytics_view),
  (@role_magasinier, @p_analytics_inventory_view),
  (@role_magasinier, @p_analytics_suppliers_view),
  (@role_magasinier, @p_catalog_categories_view),
  (@role_magasinier, @p_catalog_products_view),
  (@role_magasinier, @p_catalog_variants_manage),
  (@role_magasinier, @p_inventory_stock_view),
  (@role_magasinier, @p_inventory_lots_create),
  (@role_magasinier, @p_inventory_stock_out),
  (@role_magasinier, @p_inventory_adjustments),
  (@role_magasinier, @p_inventory_policy_manage),
  (@role_magasinier, @p_inventory_movements_view),
  (@role_magasinier, @p_inventory_alerts_view),
  (@role_magasinier, @p_commerce_livraisons_view),
  (@role_magasinier, @p_commerce_livraisons_create),
  (@role_magasinier, @p_commerce_livraisons_deliver),
  (@role_magasinier, @p_fournisseur_view),
  (@role_magasinier, @p_fournisseur_commandes_view),
  (@role_magasinier, @p_fournisseur_commandes_create),
  (@role_magasinier, @p_fournisseur_commandes_confirm),
  (@role_magasinier, @p_fournisseur_commandes_receive),
  (@role_magasinier, @p_impression_settings_view),
  (@role_magasinier, @p_impression_documents_print),
  (@role_magasinier, @p_impression_tables_export),
  (@role_comptable, @p_dashboard_view),
  (@role_comptable, @p_analytics_view),
  (@role_comptable, @p_analytics_sales_view),
  (@role_comptable, @p_analytics_finance_view),
  (@role_comptable, @p_analytics_clients_view),
  (@role_comptable, @p_analytics_export),
  (@role_comptable, @p_client_clients_view),
  (@role_comptable, @p_client_creances_view),
  (@role_comptable, @p_client_journal_view),
  (@role_comptable, @p_catalog_categories_view),
  (@role_comptable, @p_catalog_products_view),
  (@role_comptable, @p_inventory_movements_view),
  (@role_comptable, @p_commerce_ventes_view),
  (@role_comptable, @p_commerce_commandes_view),
  (@role_comptable, @p_commerce_devis_view),
  (@role_comptable, @p_facturation_factures_view),
  (@role_comptable, @p_paiement_paiements_view),
  (@role_comptable, @p_paiement_paiements_create),
  (@role_comptable, @p_paiement_paiements_cancel),
  (@role_comptable, @p_finance_view),
  (@role_comptable, @p_finance_comptes_view),
  (@role_comptable, @p_finance_transactions_view),
  (@role_comptable, @p_finance_transactions_create),
  (@role_comptable, @p_finance_transactions_cancel),
  (@role_comptable, @p_fournisseur_view),
  (@role_comptable, @p_fournisseur_commandes_view),
  (@role_comptable, @p_fournisseur_dettes_view),
  (@role_comptable, @p_fournisseur_dettes_create),
  (@role_comptable, @p_fournisseur_paiements_create),
  (@role_comptable, @p_fournisseur_paiements_cancel),
  (@role_comptable, @p_impression_settings_view),
  (@role_comptable, @p_impression_documents_print),
  (@role_comptable, @p_impression_tables_export),
  (@role_consultant, @p_access_users_view),
  (@role_consultant, @p_access_roles_view),
  (@role_consultant, @p_access_audit_view),
  (@role_consultant, @p_platform_shops_view),
  (@role_consultant, @p_dashboard_view),
  (@role_consultant, @p_analytics_view),
  (@role_consultant, @p_analytics_sales_view),
  (@role_consultant, @p_analytics_inventory_view),
  (@role_consultant, @p_analytics_finance_view),
  (@role_consultant, @p_analytics_clients_view),
  (@role_consultant, @p_analytics_suppliers_view),
  (@role_consultant, @p_client_clients_view),
  (@role_consultant, @p_client_creances_view),
  (@role_consultant, @p_client_journal_view),
  (@role_consultant, @p_catalog_categories_view),
  (@role_consultant, @p_catalog_products_view),
  (@role_consultant, @p_inventory_stock_view),
  (@role_consultant, @p_inventory_movements_view),
  (@role_consultant, @p_inventory_alerts_view),
  (@role_consultant, @p_commerce_cart_use),
  (@role_consultant, @p_commerce_ventes_view),
  (@role_consultant, @p_commerce_commandes_view),
  (@role_consultant, @p_commerce_devis_view),
  (@role_consultant, @p_commerce_livraisons_view),
  (@role_consultant, @p_facturation_factures_view),
  (@role_consultant, @p_paiement_paiements_view),
  (@role_consultant, @p_finance_view),
  (@role_consultant, @p_finance_comptes_view),
  (@role_consultant, @p_finance_transactions_view),
  (@role_consultant, @p_fournisseur_view),
  (@role_consultant, @p_fournisseur_commandes_view),
  (@role_consultant, @p_fournisseur_dettes_view),
  (@role_consultant, @p_impression_settings_view);

-- Unites de mesure de base (catalogue apres creation de boutique)
INSERT INTO units_of_measure (id, code, label, decimal_places, is_system) VALUES
  (@uom_piece, 'piece', 'Pièce', 0, 1),
  (@uom_kg, 'kg', 'Kilogramme', 3, 1),
  (@uom_liter, 'liter', 'Litre', 3, 1),
  (@uom_carton, 'carton', 'Carton', 0, 1);

COMMIT;

-- Verification rapide
SELECT 'roles' AS entity, code AS k, label AS v, CAST(is_system AS CHAR) AS extra FROM roles WHERE code IN ('admin','gerant','caissier','magasinier','comptable','consultant')
UNION ALL
SELECT 'permissions', CAST(COUNT(*) AS CHAR), '', '' FROM permissions
UNION ALL
SELECT 'role_permissions', CAST(COUNT(*) AS CHAR), '', '' FROM role_permissions
UNION ALL
SELECT 'units', code, label, CAST(decimal_places AS CHAR) FROM units_of_measure WHERE code IN ('piece','kg','liter','carton');
