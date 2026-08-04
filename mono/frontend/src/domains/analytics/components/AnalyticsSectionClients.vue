<template>
  <div class="analytics-section">
    <div class="analytics-stats-row analytics-stats-row--cards">
      <Card class="analytics-stat-card">
        <template #content>
          <p class="analytics-stat__label">Clients actifs</p>
          <p class="analytics-stat__value">{{ data?.active_count ?? 0 }}</p>
        </template>
      </Card>
      <Card class="analytics-stat-card">
        <template #content>
          <p class="analytics-stat__label">Nouveaux clients</p>
          <p class="analytics-stat__value">{{ data?.new_count ?? 0 }}</p>
        </template>
      </Card>
      <Card class="analytics-stat-card">
        <template #content>
          <p class="analytics-stat__label">Fréquence d'achat moy.</p>
          <p class="analytics-stat__value">{{ data?.average_purchase_frequency ?? 0 }}</p>
        </template>
      </Card>
    </div>

    <AnalyticsChartPanel
      title="Top clients (LTV)"
      subtitle="Par chiffre d'affaires"
      icon="pi pi-users"
      type="bar"
      accent-var="--analytics-accent-clients"
      :labels="topClientLabels"
      :datasets="topClientDatasets"
      empty-text="Aucun client actif."
    />

    <Card class="analytics-panel">
      <template #title>Top débiteurs</template>
      <template #content>
        <DataTable :value="data?.top_debtors ?? []" size="small" striped-rows>
          <Column field="label" header="Client" />
          <Column field="balance" header="Encours">
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
import { useDisplayFormatters } from '@/domains/shared/composables/useDisplayFormatters'

const props = defineProps({
  data: { type: Object, default: null }
})

const { formatMoney } = useDisplayFormatters()

const topClients = computed(() => props.data?.top_clients ?? [])
const topClientLabels = computed(() => topClients.value.map((c) => c.client_name))
const topClientDatasets = computed(() => [{
  label: 'CA',
  data: topClients.value.map((c) => Number(c.total_amount ?? 0))
}])
</script>
