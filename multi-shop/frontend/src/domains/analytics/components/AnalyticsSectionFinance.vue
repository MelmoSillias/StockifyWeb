<template>
  <div class="analytics-section">
    <div class="analytics-stats-row analytics-stats-row--cards">
      <Card class="analytics-stat-card">
        <template #content>
          <p class="analytics-stat__label">Revenus</p>
          <p class="analytics-stat__value analytics-stat__value--up">{{ formatMoney(data?.cash_flow?.revenu) }}</p>
        </template>
      </Card>
      <Card class="analytics-stat-card">
        <template #content>
          <p class="analytics-stat__label">Dépenses</p>
          <p class="analytics-stat__value analytics-stat__value--down">{{ formatMoney(data?.cash_flow?.depense) }}</p>
        </template>
      </Card>
      <Card class="analytics-stat-card">
        <template #content>
          <p class="analytics-stat__label">Trésorerie totale</p>
          <p class="analytics-stat__value">{{ formatMoney(data?.treasury_total) }}</p>
        </template>
      </Card>
      <Card class="analytics-stat-card analytics-stat-card--highlight">
        <template #content>
          <p class="analytics-stat__label">Position nette</p>
          <p class="analytics-stat__value">{{ formatMoney(data?.net_position) }}</p>
          <p class="analytics-stat__hint">Trésorerie + créances − dettes</p>
        </template>
      </Card>
    </div>

    <AnalyticsChartPanel
      title="Flux de trésorerie"
      subtitle="Revenus vs dépenses"
      icon="pi pi-chart-line"
      type="bar"
      accent-var="--analytics-accent-finance"
      :labels="trendLabels"
      :datasets="cashFlowDatasets"
      :show-legend="true"
      empty-text="Aucune transaction."
    />

    <div class="analytics-section__grid analytics-section__grid--2">
      <AnalyticsChartPanel
        title="Soldes par compte"
        icon="pi pi-building-columns"
        type="bar"
        accent-var="--analytics-accent-finance"
        :labels="accountLabels"
        :datasets="accountDatasets"
        empty-text="Aucun compte actif."
      />
      <AnalyticsChartPanel
        title="Origine des transactions"
        icon="pi pi-sync"
        type="doughnut"
        accent-var="--analytics-accent-finance"
        :labels="['Automatiques', 'Manuelles']"
        :datasets="sourceDatasets"
        :show-legend="true"
        empty-text="Aucune transaction."
      />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

import Card from 'primevue/card'

import AnalyticsChartPanel from '@/domains/analytics/components/charts/AnalyticsChartPanel.vue'
import { useChartTheme } from '@/domains/analytics/composables/useChartTheme'
import { useDisplayFormatters } from '@/domains/shared/composables/useDisplayFormatters'

const props = defineProps({
  data: { type: Object, default: null }
})

const { formatMoney } = useDisplayFormatters()
const { CHART_PALETTE } = useChartTheme()

const formatDateLabel = (dateStr) => {
  const date = new Date(`${dateStr}T00:00:00`)
  return Number.isNaN(date.getTime()) ? dateStr : date.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' })
}

const trend = computed(() => props.data?.cash_flow_trend ?? [])
const trendLabels = computed(() => trend.value.map((p) => formatDateLabel(p.date)))
const cashFlowDatasets = computed(() => [
  { label: 'Revenus', data: trend.value.map((p) => Number(p.revenu ?? 0)), backgroundColor: CHART_PALETTE[0] },
  { label: 'Dépenses', data: trend.value.map((p) => Number(p.depense ?? 0)), backgroundColor: CHART_PALETTE[3] }
])

const accounts = computed(() => props.data?.accounts ?? [])
const accountLabels = computed(() => accounts.value.map((a) => a.compte_name))
const accountDatasets = computed(() => [{
  label: 'Solde',
  data: accounts.value.map((a) => Number(a.balance ?? 0))
}])

const sourceSplit = computed(() => props.data?.transaction_source_split ?? {})
const sourceDatasets = computed(() => [{
  data: [
    Number(sourceSplit.value.auto_count ?? 0),
    Number(sourceSplit.value.manual_count ?? 0)
  ],
  backgroundColor: [CHART_PALETTE[0], CHART_PALETTE[4]]
}])
</script>
