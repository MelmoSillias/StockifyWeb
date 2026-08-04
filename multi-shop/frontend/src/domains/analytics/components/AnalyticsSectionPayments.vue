<template>
  <div class="analytics-section">
    <div class="analytics-section__grid analytics-section__grid--2">
      <AnalyticsChartPanel
        title="Encaissements"
        subtitle="Évolution quotidienne"
        icon="pi pi-wallet"
        accent-var="--analytics-accent-finance"
        :labels="trendLabels"
        :datasets="trendDatasets"
        :header-value="formatMoney(data?.summary?.total_amount)"
        empty-text="Aucun encaissement."
      />
      <AnalyticsChartPanel
        title="Modes de paiement"
        icon="pi pi-credit-card"
        type="doughnut"
        accent-var="--analytics-accent-finance"
        :labels="modeLabels"
        :datasets="modeDatasets"
        :show-legend="true"
        empty-text="Aucun paiement."
      />
    </div>

    <AnalyticsChartPanel
      title="Aging créances"
      subtitle="Répartition par ancienneté"
      icon="pi pi-clock"
      type="bar"
      accent-var="--analytics-accent-finance"
      :labels="agingLabels"
      :datasets="agingDatasets"
      empty-text="Aucune créance ouverte."
    />

    <Card class="analytics-panel">
      <template #title>Top débiteurs</template>
      <template #content>
        <DataTable :value="data?.top_debtors ?? []" size="small" striped-rows>
          <Column field="label" header="Client" />
          <Column field="balance" header="Solde">
            <template #body="{ data: row }">{{ formatMoney(row.balance) }}</template>
          </Column>
        </DataTable>
      </template>
    </Card>
  </div>
</template>

<script setup>
import { computed } from 'vue'

import Card from 'primevue/card'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'

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

const trend = computed(() => props.data?.trend ?? [])
const trendLabels = computed(() => trend.value.map((p) => formatDateLabel(p.date)))
const trendDatasets = computed(() => [{
  label: 'Encaissements',
  data: trend.value.map((p) => Number(p.total_amount ?? 0))
}])

const modes = computed(() => props.data?.by_mode ?? [])
const modeLabels = computed(() => modes.value.map((m) => m.mode_label))
const modeDatasets = computed(() => [{
  data: modes.value.map((m) => Number(m.total_amount ?? 0)),
  backgroundColor: CHART_PALETTE
}])

const aging = computed(() => props.data?.creances?.aging ?? [])
const agingLabels = computed(() => aging.value.map((a) => a.bucket))
const agingDatasets = computed(() => [{
  label: 'Solde',
  data: aging.value.map((a) => Number(a.balance ?? 0))
}])
</script>
