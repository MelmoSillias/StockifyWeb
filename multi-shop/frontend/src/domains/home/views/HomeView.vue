<template>
  <section class="dashboard-page dashboard-home">
    <DashboardPeriodFilter
      :preset="preset"
      :date-range="dateRange"
      :presets="presets"
      @update:preset="setPreset"
      @update:date-range="updateDateRange"
    />

    <DashboardQuickActions :visible-actions="visibleQuickActions" />

    <div v-if="isEmpty && !dashboardStore.isLoading" class="dashboard-home__empty">
      <span class="dashboard-feed-empty__icon">
        <i class="pi pi-inbox"></i>
      </span>
      <p>Aucun indicateur disponible pour votre profil.</p>
    </div>

    <template v-else>
      <DashboardKpiSkeleton
        v-if="showKpiGrid && dashboardStore.summaryLoading && !dashboardStore.summary"
      />
      <DashboardKpiGrid
        v-else-if="showKpiGrid"
        :summary="dashboardStore.summary"
        :visible-kpis="visibleKpis"
        :grid-class="kpiGridClass"
      />

      <div
        v-if="showInsights"
        class="dashboard-home__insights"
        :class="insightsLayoutClass"
      >
        <DashboardSectionSkeleton
          v-if="showCarousel && dashboardStore.feedLoading && !dashboardStore.feed"
          :lines="6"
        />
        <DashboardCarousel
          v-else-if="showCarousel"
          :feed="dashboardStore.feed"
          :visible-slides="visibleSlides"
        />

        <DashboardSectionSkeleton
          v-if="showSalesChart && dashboardStore.salesTrendLoading && !dashboardStore.salesTrend"
          :lines="8"
        />
        <DashboardSalesChart
          v-else-if="showSalesChart"
          :sales-trend="dashboardStore.salesTrend"
        />
      </div>

      <DashboardFinancePanel
        v-if="showFinancePanel"
        :finance-summary="dashboardStore.financeSummary"
        :visible-widgets="visibleFinanceWidgets"
      />

      <DashboardTableSkeleton
        v-if="showPendingDeliveries && dashboardStore.pendingDeliveriesLoading && !dashboardStore.pendingDeliveries"
        :rows="6"
      />
      <DashboardPendingDeliveries
        v-else-if="showPendingDeliveries"
        :pending-deliveries="dashboardStore.pendingDeliveries"
      />

      <DashboardTableSkeleton
        v-if="showPendingSupplierOrders && dashboardStore.pendingSupplierOrdersLoading && !dashboardStore.pendingSupplierOrders"
        :rows="6"
      />
      <DashboardPendingSupplierOrders
        v-else-if="showPendingSupplierOrders"
        :pending-supplier-orders="dashboardStore.pendingSupplierOrders"
      />

      <DashboardTableSkeleton
        v-if="showRecentAudit && dashboardStore.recentAuditLoading && !dashboardStore.recentAudit"
        :rows="5"
      />
      <DashboardRecentAudit
        v-else-if="showRecentAudit"
        :recent-audit="dashboardStore.recentAudit"
      />
    </template>
  </section>
</template>

<script setup>
import { onMounted, watch } from 'vue'

import DashboardCarousel from '@/domains/home/components/DashboardCarousel.vue'
import DashboardFinancePanel from '@/domains/home/components/DashboardFinancePanel.vue'
import DashboardKpiGrid from '@/domains/home/components/DashboardKpiGrid.vue'
import DashboardKpiSkeleton from '@/domains/home/components/DashboardKpiSkeleton.vue'
import DashboardPendingDeliveries from '@/domains/home/components/DashboardPendingDeliveries.vue'
import DashboardPendingSupplierOrders from '@/domains/home/components/DashboardPendingSupplierOrders.vue'
import DashboardPeriodFilter from '@/domains/home/components/DashboardPeriodFilter.vue'
import DashboardQuickActions from '@/domains/home/components/DashboardQuickActions.vue'
import DashboardRecentAudit from '@/domains/home/components/DashboardRecentAudit.vue'
import DashboardSalesChart from '@/domains/home/components/DashboardSalesChart.vue'
import DashboardSectionSkeleton from '@/domains/home/components/DashboardSectionSkeleton.vue'
import DashboardTableSkeleton from '@/domains/home/components/DashboardTableSkeleton.vue'
import { useDashboardPeriod } from '@/domains/home/composables/useDashboardPeriod'
import { useDashboardWidgets } from '@/domains/home/composables/useDashboardWidgets'
import { useDashboardStore } from '@/domains/home/stores/dashboard'

const dashboardStore = useDashboardStore()
const { preset, dateRange, from, to, setPreset, presets } = useDashboardPeriod('7days')

const {
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
} = useDashboardWidgets()

const updateDateRange = (value) => {
  dateRange.value = value
}

const refreshPeriodData = () => {
  if (!from.value || !to.value) {
    return
  }

  dashboardStore.refreshPeriodData(from.value, to.value, fetchOptions.value)
}

const refreshStaticData = () => {
  dashboardStore.refreshStaticData(fetchOptions.value)
}

watch([from, to], () => {
  refreshPeriodData()
}, { immediate: false })

watch(fetchOptions, () => {
  refreshPeriodData()
  refreshStaticData()
}, { deep: true })

onMounted(async () => {
  await refreshStaticData()
  await refreshPeriodData()
})
</script>
