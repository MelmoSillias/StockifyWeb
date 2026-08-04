<template>
  <div class="analytics-section">
    <div class="analytics-stats-row analytics-stats-row--cards">
      <Card class="analytics-stat-card">
        <template #content>
          <p class="analytics-stat__label">Achats reçus</p>
          <p class="analytics-stat__value">{{ formatMoney(data?.summary?.total_amount) }}</p>
        </template>
      </Card>
      <Card class="analytics-stat-card">
        <template #content>
          <p class="analytics-stat__label">En attente</p>
          <p class="analytics-stat__value">{{ data?.summary?.pending_count ?? 0 }}</p>
        </template>
      </Card>
      <Card class="analytics-stat-card">
        <template #content>
          <p class="analytics-stat__label">En retard</p>
          <p class="analytics-stat__value">{{ data?.summary?.overdue_count ?? 0 }}</p>
        </template>
      </Card>
      <Card class="analytics-stat-card">
        <template #content>
          <p class="analytics-stat__label">Lead time moyen</p>
          <p class="analytics-stat__value">
            {{ data?.average_lead_time_days != null ? `${data.average_lead_time_days} j` : '—' }}
          </p>
        </template>
      </Card>
    </div>

    <div class="analytics-section__grid analytics-section__grid--2">
      <AnalyticsChartPanel
        title="Volume achats"
        subtitle="Commandes reçues"
        icon="pi pi-truck"
        accent-var="--analytics-accent-suppliers"
        :labels="trendLabels"
        :datasets="trendDatasets"
        empty-text="Aucun achat reçu."
      />
      <AnalyticsChartPanel
        title="Dépenses par fournisseur"
        icon="pi pi-building"
        type="bar"
        accent-var="--analytics-accent-suppliers"
        :labels="supplierLabels"
        :datasets="supplierDatasets"
        empty-text="Aucun achat."
      />
    </div>

    <AnalyticsChartPanel
      title="Aging dettes fournisseurs"
      icon="pi pi-clock"
      type="bar"
      accent-var="--analytics-accent-suppliers"
      :labels="agingLabels"
      :datasets="agingDatasets"
      empty-text="Aucune dette ouverte."
    />
  </div>
</template>

<script setup>
import { computed } from 'vue'

import Card from 'primevue/card'

import AnalyticsChartPanel from '@/domains/analytics/components/charts/AnalyticsChartPanel.vue'
import { useDisplayFormatters } from '@/domains/shared/composables/useDisplayFormatters'

const props = defineProps({
  data: { type: Object, default: null }
})

const { formatMoney } = useDisplayFormatters()

const formatDateLabel = (dateStr) => {
  const date = new Date(`${dateStr}T00:00:00`)
  return Number.isNaN(date.getTime()) ? dateStr : date.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' })
}

const trend = computed(() => props.data?.trend ?? [])
const trendLabels = computed(() => trend.value.map((p) => formatDateLabel(p.date)))
const trendDatasets = computed(() => [{
  label: 'Achats',
  data: trend.value.map((p) => Number(p.total_amount ?? 0))
}])

const suppliers = computed(() => props.data?.by_supplier ?? [])
const supplierLabels = computed(() => suppliers.value.map((s) => s.fournisseur_name))
const supplierDatasets = computed(() => [{
  label: 'Montant',
  data: suppliers.value.map((s) => Number(s.total_amount ?? 0))
}])

const aging = computed(() => props.data?.dettes?.aging ?? [])
const agingLabels = computed(() => aging.value.map((a) => a.bucket))
const agingDatasets = computed(() => [{
  label: 'Solde',
  data: aging.value.map((a) => Number(a.balance ?? 0))
}])
</script>
