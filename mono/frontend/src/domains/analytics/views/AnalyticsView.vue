<template>
  <section class="analytics-page">
    <header class="analytics-page__header">
      <div class="analytics-page__header-leading">
        <span class="analytics-page__badge" aria-hidden="true">
          <i class="pi pi-chart-bar"></i>
        </span>
        <div>
          <h1 class="analytics-page__title">Analytics</h1>
          <p class="analytics-page__subtitle">Évaluez votre boutique et projetez vos performances</p>
        </div>
      </div>
      <span v-if="periodLabel" class="analytics-page__period-chip">
        <i class="pi pi-calendar"></i>
        {{ periodLabel }}
      </span>
    </header>

    <AnalyticsPeriodFilter
      :preset="preset"
      :date-range="dateRange"
      :presets="presets"
      :compare-enabled="compareEnabled"
      @update:preset="setPreset"
      @update:date-range="updateDateRange"
      @update:compare-enabled="compareEnabled = $event"
    />

    <Message v-if="analyticsStore.error" severity="error" class="analytics-page__error">
      {{ analyticsStore.error }}
    </Message>

    <AnalyticsSectionSkeleton v-if="analyticsStore.overviewLoading && !analyticsStore.overview" variant="overview" />
    <AnalyticsKpiRow
      v-else-if="visibleKpis.length"
      :overview="analyticsStore.overview"
      :visible-kpis="visibleKpis"
      :projection="analyticsStore.overview?.projection"
    />

    <div
      v-if="visibleSections.length"
      class="analytics-tabs"
      :class="`analytics-tabs--${activeTab}`"
    >
      <Tabs :value="activeTab" @update:value="onTabChange">
        <TabList>
          <Tab
            v-for="section in visibleSections"
            :key="section.id"
            :value="section.id"
            :class="`analytics-tab--${section.id}`"
          >
            <i :class="section.icon"></i>
            <span>{{ section.label }}</span>
          </Tab>
        </TabList>
        <TabPanels>
          <TabPanel v-for="section in visibleSections" :key="section.id" :value="section.id">
            <AnalyticsExportBar
              v-if="hasPermission('analytics.export')"
              :section-label="section.label"
              :data="sectionData(section.id)"
            />

            <AnalyticsSectionSkeleton v-if="isSectionLoading(section.id)" variant="section" />

            <AnalyticsSectionSales v-else-if="section.id === 'sales'" :data="analyticsStore.sales" />
            <AnalyticsSectionPayments v-else-if="section.id === 'payments'" :data="analyticsStore.payments" />
            <AnalyticsSectionInventory v-else-if="section.id === 'inventory'" :data="analyticsStore.inventory" />
            <AnalyticsSectionPurchases v-else-if="section.id === 'purchases'" :data="analyticsStore.purchases" />
            <AnalyticsSectionFinance v-else-if="section.id === 'finance'" :data="analyticsStore.finance" />
            <AnalyticsSectionClients v-else-if="section.id === 'clients'" :data="analyticsStore.clients" />
          </TabPanel>
        </TabPanels>
      </Tabs>
    </div>

    <div v-else-if="!analyticsStore.isLoading" class="analytics-empty analytics-empty--page">
      <span class="analytics-empty__icon"><i class="pi pi-chart-bar"></i></span>
      <p>Aucune section analytics disponible pour votre profil.</p>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'

import Message from 'primevue/message'
import Tab from 'primevue/tab'
import TabList from 'primevue/tablist'
import TabPanel from 'primevue/tabpanel'
import TabPanels from 'primevue/tabpanels'
import Tabs from 'primevue/tabs'

import AnalyticsExportBar from '@/domains/analytics/components/AnalyticsExportBar.vue'
import AnalyticsKpiRow from '@/domains/analytics/components/AnalyticsKpiRow.vue'
import AnalyticsPeriodFilter from '@/domains/analytics/components/AnalyticsPeriodFilter.vue'
import AnalyticsSectionClients from '@/domains/analytics/components/AnalyticsSectionClients.vue'
import AnalyticsSectionFinance from '@/domains/analytics/components/AnalyticsSectionFinance.vue'
import AnalyticsSectionInventory from '@/domains/analytics/components/AnalyticsSectionInventory.vue'
import AnalyticsSectionPayments from '@/domains/analytics/components/AnalyticsSectionPayments.vue'
import AnalyticsSectionPurchases from '@/domains/analytics/components/AnalyticsSectionPurchases.vue'
import AnalyticsSectionSales from '@/domains/analytics/components/AnalyticsSectionSales.vue'
import AnalyticsSectionSkeleton from '@/domains/analytics/components/AnalyticsSectionSkeleton.vue'
import { ANALYTICS_PERIOD_PRESETS, useAnalyticsPeriod } from '@/domains/analytics/composables/useAnalyticsPeriod'
import { useAnalyticsWidgets } from '@/domains/analytics/composables/useAnalyticsWidgets'
import { useAnalyticsStore } from '@/domains/analytics/stores/analytics'

const analyticsStore = useAnalyticsStore()
const { preset, dateRange, from, to, setPreset } = useAnalyticsPeriod('30days')
const presets = ANALYTICS_PERIOD_PRESETS
const compareEnabled = ref(true)

const { visibleSections, visibleKpis, hasPermission } = useAnalyticsWidgets()

const activeTab = ref(visibleSections.value[0]?.id ?? 'sales')

watch(visibleSections, (sections) => {
  if (!sections.some((s) => s.id === activeTab.value)) {
    activeTab.value = sections[0]?.id ?? 'sales'
  }
}, { immediate: true })

const updateDateRange = (value) => {
  dateRange.value = value
}

const sectionIds = computed(() => visibleSections.value.map((s) => s.id))

const periodLabel = computed(() => {
  if (preset.value !== 'custom') {
    return presets.find((entry) => entry.id === preset.value)?.label ?? ''
  }
  if (dateRange.value?.[0] && dateRange.value?.[1]) {
    const formatDate = (value) =>
      new Date(value).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' })
    return `${formatDate(dateRange.value[0])} – ${formatDate(dateRange.value[1])}`
  }
  return 'Période personnalisée'
})

const refreshAll = async () => {
  if (!from.value || !to.value) return
  await analyticsStore.refreshAll(from.value, to.value, compareEnabled.value, sectionIds.value)
  activeTab.value = analyticsStore.activeSection || activeTab.value
}

const onTabChange = async (sectionId) => {
  activeTab.value = sectionId
  analyticsStore.setActiveSection(sectionId)
  if (!sectionData(sectionId)) {
    await analyticsStore.refreshSection(sectionId)
  }
}

const sectionData = (sectionId) => {
  const map = {
    sales: analyticsStore.sales,
    payments: analyticsStore.payments,
    inventory: analyticsStore.inventory,
    purchases: analyticsStore.purchases,
    finance: analyticsStore.finance,
    clients: analyticsStore.clients
  }
  return map[sectionId] ?? null
}

const isSectionLoading = (sectionId) => {
  const loadingMap = {
    sales: analyticsStore.salesLoading,
    payments: analyticsStore.paymentsLoading,
    inventory: analyticsStore.inventoryLoading,
    purchases: analyticsStore.purchasesLoading,
    finance: analyticsStore.financeLoading,
    clients: analyticsStore.clientsLoading
  }
  return loadingMap[sectionId] && !sectionData(sectionId)
}

watch([from, to, compareEnabled], () => {
  refreshAll()
})

onMounted(refreshAll)
</script>
