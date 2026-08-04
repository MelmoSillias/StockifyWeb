export const ANALYTICS_SECTION_DEFINITIONS = [
  {
    id: 'sales',
    label: 'Ventes',
    icon: 'pi pi-shopping-cart',
    permission: 'analytics.sales.view'
  },
  {
    id: 'payments',
    label: 'Paiements',
    icon: 'pi pi-wallet',
    permission: 'analytics.finance.view'
  },
  {
    id: 'inventory',
    label: 'Stock',
    icon: 'pi pi-box',
    permission: 'analytics.inventory.view'
  },
  {
    id: 'purchases',
    label: 'Achats',
    icon: 'pi pi-truck',
    permission: 'analytics.suppliers.view'
  },
  {
    id: 'finance',
    label: 'Finance',
    icon: 'pi pi-chart-line',
    permission: 'analytics.finance.view'
  },
  {
    id: 'clients',
    label: 'Clients',
    icon: 'pi pi-users',
    permission: 'analytics.clients.view'
  }
]

export const ANALYTICS_KPI_DEFINITIONS = [
  {
    id: 'revenue',
    permission: 'analytics.sales.view',
    title: "Chiffre d'affaires net",
    icon: 'pi pi-shopping-cart',
    toneClass: 'analytics-kpi-card--sales',
    valueKey: 'net_amount',
    section: 'sales',
    format: 'money',
    comparisonKey: 'sales.total_amount_delta'
  },
  {
    id: 'sales_count',
    permission: 'analytics.sales.view',
    title: 'Ventes',
    icon: 'pi pi-hashtag',
    toneClass: 'analytics-kpi-card--sales',
    valueKey: 'count',
    section: 'sales',
    format: 'number',
    comparisonKey: 'sales.count_delta'
  },
  {
    id: 'payments',
    permission: 'analytics.finance.view',
    title: 'Encaissements',
    icon: 'pi pi-wallet',
    toneClass: 'analytics-kpi-card--finance',
    valueKey: 'total_amount',
    section: 'payments',
    format: 'money',
    comparisonKey: 'payments.total_amount_delta'
  },
  {
    id: 'stock_value',
    permission: 'analytics.inventory.view',
    title: 'Valeur stock',
    icon: 'pi pi-box',
    toneClass: 'analytics-kpi-card--inventory',
    valueKey: 'stock_value',
    section: 'inventory',
    format: 'money'
  },
  {
    id: 'purchases',
    permission: 'analytics.suppliers.view',
    title: 'Achats reçus',
    icon: 'pi pi-truck',
    toneClass: 'analytics-kpi-card--suppliers',
    valueKey: 'total_amount',
    section: 'purchases',
    format: 'money',
    comparisonKey: 'purchases.total_amount_delta'
  },
  {
    id: 'cash_flow',
    permission: 'analytics.finance.view',
    title: 'Flux net',
    icon: 'pi pi-chart-line',
    toneClass: 'analytics-kpi-card--finance',
    valueKey: 'net',
    section: 'cash_flow',
    format: 'money',
    comparisonKey: 'cash_flow.net_delta'
  },
  {
    id: 'active_clients',
    permission: 'analytics.clients.view',
    title: 'Clients actifs',
    icon: 'pi pi-users',
    toneClass: 'analytics-kpi-card--clients',
    valueKey: 'active_count',
    section: 'clients',
    format: 'number',
    comparisonKey: 'clients.active_count_delta'
  }
]
