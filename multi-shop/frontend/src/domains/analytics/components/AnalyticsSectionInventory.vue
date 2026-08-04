<template>
  <div class="analytics-section">
    <div class="analytics-stats-row analytics-stats-row--cards">
      <Card class="analytics-stat-card">
        <template #content>
          <p class="analytics-stat__label">Valeur du stock</p>
          <p class="analytics-stat__value">{{ formatMoney(data?.stock_value) }}</p>
        </template>
      </Card>
      <Card class="analytics-stat-card">
        <template #content>
          <p class="analytics-stat__label">Alertes bas stock</p>
          <p class="analytics-stat__value">{{ data?.low_stock_count ?? 0 }}</p>
        </template>
      </Card>
      <Card class="analytics-stat-card">
        <template #content>
          <p class="analytics-stat__label">Lots proches expiration</p>
          <p class="analytics-stat__value">{{ data?.expiring_lots_count ?? 0 }}</p>
        </template>
      </Card>
    </div>

    <AnalyticsChartPanel
      title="Mouvements de stock"
      subtitle="Par type et direction"
      icon="pi pi-history"
      type="bar"
      value-format="number"
      accent-var="--analytics-accent-inventory"
      :labels="movementLabels"
      :datasets="movementDatasets"
      :show-legend="true"
      empty-text="Aucun mouvement."
    />

    <Card class="analytics-panel">
      <template #title>Top marges (prix vs coût moyen)</template>
      <template #content>
        <DataTable :value="data?.top_margins ?? []" size="small" striped-rows>
          <Column field="label" header="Produit" />
          <Column field="sale_price" header="Prix vente">
            <template #body="{ data: row }">{{ formatMoney(row.sale_price) }}</template>
          </Column>
          <Column field="average_cost" header="Coût moyen">
            <template #body="{ data: row }">{{ formatMoney(row.average_cost) }}</template>
          </Column>
          <Column field="margin_percent" header="Marge %">
            <template #body="{ data: row }">
              {{ row.margin_percent != null ? `${row.margin_percent}%` : '—' }}
            </template>
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

const movements = computed(() => props.data?.movements ?? [])
const movementLabels = computed(() =>
  movements.value.map((m) => `${m.type} (${m.direction})`)
)
const movementDatasets = computed(() => [{
  label: 'Quantité',
  data: movements.value.map((m) => Number(m.total_quantity ?? 0)),
  backgroundColor: CHART_PALETTE
}])
</script>
