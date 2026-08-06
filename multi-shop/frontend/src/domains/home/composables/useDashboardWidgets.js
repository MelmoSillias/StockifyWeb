import { computed } from 'vue'

import { usePermissions } from '@/domains/auth/composables/usePermissions'
import {
  DASHBOARD_CAROUSEL_SLIDE_DEFINITIONS,
  DASHBOARD_FINANCE_WIDGETS,
  DASHBOARD_KPI_DEFINITIONS,
  DASHBOARD_QUICK_ACTIONS
} from '@/domains/home/config/dashboardWidgets'

export function useDashboardWidgets() {
  const { hasPermission, hasFeature } = usePermissions()

  const canAccessWidget = (item) => (
    hasPermission(item.permission)
    && (!item.feature || hasFeature(item.feature))
  )

  const filterByAccess = (items) => items.filter((item) => canAccessWidget(item))

  const visibleKpis = computed(() => filterByAccess(DASHBOARD_KPI_DEFINITIONS))
  const visibleSlides = computed(() => filterByAccess(DASHBOARD_CAROUSEL_SLIDE_DEFINITIONS))
  const visibleQuickActions = computed(() => filterByAccess(DASHBOARD_QUICK_ACTIONS))
  const visibleFinanceWidgets = computed(() => filterByAccess(DASHBOARD_FINANCE_WIDGETS))

  const showKpiGrid = computed(() => visibleKpis.value.length > 0)
  const showCarousel = computed(() => visibleSlides.value.length > 0)
  const showSalesChart = computed(() => hasPermission('commerce.ventes.view'))
  const showPendingDeliveries = computed(() => (
    hasPermission('commerce.livraisons.view') && hasFeature('stockify.orders')
  ))
  const showPendingSupplierOrders = computed(() => (
    hasPermission('fournisseur.commandes.view') && hasFeature('stockify.suppliers')
  ))
  const showRecentAudit = computed(() => hasPermission('access.audit.view'))
  const showFinancePanel = computed(() => visibleFinanceWidgets.value.length > 0)

  const showInsights = computed(() => showCarousel.value || showSalesChart.value)

  const insightsLayoutClass = computed(() => {
    if (showCarousel.value && showSalesChart.value) {
      return ''
    }

    return 'dashboard-home__insights--single'
  })

  const kpiGridClass = computed(() => {
    const count = visibleKpis.value.length
    if (count <= 1) {
      return 'dashboard-kpis--home dashboard-kpis--cols-1'
    }
    if (count === 2) {
      return 'dashboard-kpis--home dashboard-kpis--cols-2'
    }
    if (count === 3) {
      return 'dashboard-kpis--home dashboard-kpis--cols-3'
    }

    return 'dashboard-kpis--home dashboard-kpis--cols-4'
  })

  const isEmpty = computed(() => (
    !showKpiGrid.value
    && !showInsights.value
    && !showPendingDeliveries.value
    && !showPendingSupplierOrders.value
    && !showFinancePanel.value
    && !showRecentAudit.value
  ))

  const fetchOptions = computed(() => ({
    fetchSummary: showKpiGrid.value,
    fetchFeed: showCarousel.value,
    fetchSalesTrend: showSalesChart.value,
    fetchPendingDeliveries: showPendingDeliveries.value,
    fetchPendingSupplierOrders: showPendingSupplierOrders.value,
    fetchFinanceSummary: showFinancePanel.value,
    fetchRecentAudit: showRecentAudit.value
  }))

  return {
    visibleKpis,
    visibleSlides,
    visibleQuickActions,
    visibleFinanceWidgets,
    showKpiGrid,
    showCarousel,
    showSalesChart,
    showPendingDeliveries,
    showPendingSupplierOrders,
    showRecentAudit,
    showFinancePanel,
    showInsights,
    insightsLayoutClass,
    kpiGridClass,
    isEmpty,
    fetchOptions
  }
}
