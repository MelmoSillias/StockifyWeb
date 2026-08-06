export const DASHBOARD_KPI_DEFINITIONS = [
  {
    id: 'sales',
    permission: 'commerce.ventes.view',
    title: 'Ventes',
    icon: 'pi pi-shopping-cart',
    toneClass: 'dashboard-kpi-card--sales',
    summaryKey: 'sales',
    valueKey: 'total_amount',
    hint: (summary, formatters) => {
      const count = summary?.sales?.count ?? 0
      return `${formatters.formatCompactNumber(count)} vente(s) sur la période`
    }
  },
  {
    id: 'stock',
    permission: 'inventory.alerts.view',
    title: 'Stock',
    icon: 'pi pi-box',
    toneClass: 'dashboard-kpi-card--stock',
    summaryKey: 'stock',
    valueKey: 'low_stock_count',
    hint: () => 'Alerte(s) bas stock'
  },
  {
    id: 'deliveries',
    permission: 'commerce.livraisons.view',
    title: 'Livraisons',
    icon: 'pi pi-truck',
    toneClass: 'dashboard-kpi-card--deliveries',
    summaryKey: 'deliveries',
    valueKey: 'pending_count',
    hint: (summary, formatters) => {
      const overdueCount = Number(summary?.deliveries?.overdue_count ?? 0)
      return overdueCount > 0
        ? `${formatters.formatCompactNumber(overdueCount)} en retard`
        : 'Aucun retard signalé'
    }
  },
  {
    id: 'clients',
    permission: 'client.clients.view',
    title: 'Clients',
    icon: 'pi pi-users',
    toneClass: 'dashboard-kpi-card--clients',
    summaryKey: 'clients',
    valueKey: 'active_count',
    hint: () => 'Client(s) actif(s) sur la période'
  }
]

export const DASHBOARD_CAROUSEL_SLIDE_DEFINITIONS = [
  {
    id: 'recent_orders',
    type: 'recent_orders',
    permission: 'commerce.commandes.view',
    feature: 'stockify.orders',
    title: 'Dernières commandes',
    shortTitle: 'Commandes',
    icon: 'pi pi-list',
    routeName: 'commerce-orders',
    feedKey: 'recent_orders',
    emptyText: 'Aucune commande sur la période.'
  },
  {
    id: 'recent_sales',
    type: 'recent_sales',
    permission: 'commerce.ventes.view',
    title: 'Dernières ventes',
    shortTitle: 'Ventes',
    icon: 'pi pi-shopping-cart',
    routeName: 'commerce-sales',
    feedKey: 'recent_sales',
    emptyText: 'Aucune vente sur la période.'
  },
  {
    id: 'top_products',
    type: 'top_products',
    permission: 'commerce.ventes.view',
    title: 'Top produits',
    shortTitle: 'Top produits',
    icon: 'pi pi-star',
    routeName: 'commerce-sales',
    feedKey: 'top_products',
    emptyText: 'Aucune vente produit sur la période.'
  },
  {
    id: 'recent_movements',
    type: 'recent_movements',
    permission: 'inventory.movements.view',
    title: 'Derniers mouvements',
    shortTitle: 'Mouvements',
    icon: 'pi pi-arrows-h',
    routeName: 'inventory-movements',
    feedKey: 'recent_movements',
    emptyText: 'Aucun mouvement sur la période.'
  },
  {
    id: 'stock_alerts',
    type: 'stock_alerts',
    permission: 'inventory.alerts.view',
    title: 'Alertes stock',
    shortTitle: 'Alertes',
    icon: 'pi pi-exclamation-triangle',
    routeName: 'catalog-products',
    feedKey: 'stock_alerts',
    emptyText: 'Aucune alerte stock active.'
  }
]

export const DASHBOARD_QUICK_ACTIONS = [
  {
    id: 'cart',
    permission: 'commerce.cart.use',
    label: 'Ouvrir le panier',
    icon: 'pi pi-shopping-cart',
    routeName: 'commerce-cart'
  },
  {
    id: 'analytics',
    permission: 'analytics.view',
    feature: 'stockify.analytics',
    label: 'Voir analytics',
    icon: 'pi pi-chart-bar',
    routeName: 'analytics'
  },
  {
    id: 'creances',
    permission: 'client.creances.view',
    label: 'Créances clients',
    icon: 'pi pi-wallet',
    routeName: 'commerce-creances'
  },
  {
    id: 'products',
    permission: 'catalog.products.view',
    label: 'Voir les produits',
    icon: 'pi pi-tag',
    routeName: 'catalog-products'
  },
  {
    id: 'supplier-orders',
    permission: 'fournisseur.commandes.view',
    feature: 'stockify.suppliers',
    label: 'Commandes fournisseur',
    icon: 'pi pi-truck',
    routeName: 'fournisseurs'
  },
  {
    id: 'finance',
    permission: 'finance.view',
    label: 'Finances',
    icon: 'pi pi-chart-line',
    routeName: 'finance'
  },
  {
    id: 'payments',
    permission: 'paiement.paiements.view',
    label: 'Paiements',
    icon: 'pi pi-credit-card',
    routeName: 'commerce-payments'
  }
]

export const DASHBOARD_FINANCE_WIDGETS = [
  {
    id: 'client_creances',
    permission: 'client.creances.view',
    title: 'Encours clients',
    icon: 'pi pi-wallet',
    routeName: 'commerce-creances',
    summaryKey: 'client_creances'
  },
  {
    id: 'supplier_dettes',
    permission: 'fournisseur.dettes.view',
    feature: 'stockify.suppliers',
    title: 'Dettes fournisseurs',
    icon: 'pi pi-building',
    routeName: 'fournisseur-dettes',
    summaryKey: 'supplier_dettes'
  },
  {
    id: 'treasury',
    permission: 'finance.comptes.view',
    title: 'Trésorerie',
    icon: 'pi pi-chart-line',
    routeName: 'finance',
    summaryKey: 'treasury'
  }
]
