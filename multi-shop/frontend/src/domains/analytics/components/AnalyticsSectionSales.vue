<template>
  <div class="analytics-section">
    <div class="analytics-section__grid analytics-section__grid--2">
      <AnalyticsChartPanel
        title="Chiffre d'affaires"
        subtitle="Évolution quotidienne"
        icon="pi pi-chart-line"
        :labels="trendLabels"
        :datasets="revenueDatasets"
        :header-value="formatMoney(totalRevenue)"
        empty-text="Aucune vente sur la période."
      />
      <AnalyticsChartPanel
        title="Volume de ventes"
        subtitle="Nombre de ventes par jour"
        icon="pi pi-hashtag"
        type="bar"
        value-format="number"
        accent-var="--analytics-accent-sales"
        :labels="trendLabels"
        :datasets="countDatasets"
        :header-value="String(data?.summary?.count ?? 0)"
        empty-text="Aucune vente sur la période."
      />
    </div>

    <div class="analytics-section__grid analytics-section__grid--2">
      <AnalyticsChartPanel
        title="Top produits"
        subtitle="Par chiffre d'affaires"
        icon="pi pi-star"
        type="bar"
        :labels="topProductLabels"
        :datasets="topProductDatasets"
        empty-text="Aucun produit vendu."
      />
      <AnalyticsChartPanel
        title="Répartition par catégorie"
        icon="pi pi-sitemap"
        type="doughnut"
        :labels="categoryLabels"
        :datasets="categoryDatasets"
        :show-legend="true"
        empty-text="Aucune vente par catégorie."
      />
    </div>

    <div class="analytics-section__grid analytics-section__grid--2">
      <AnalyticsChartPanel
        title="Ventes client vs anonyme"
        icon="pi pi-users"
        type="doughnut"
        :labels="['Clients identifiés', 'Ventes anonymes']"
        :datasets="clientSplitDatasets"
        :show-legend="true"
        empty-text="Aucune vente."
      />
      <AnalyticsChartPanel
        title="Pipeline commandes"
        subtitle="Par statut"
        icon="pi pi-list"
        type="bar"
        value-format="number"
        :labels="pipelineLabels"
        :datasets="pipelineDatasets"
        empty-text="Aucune commande."
      />
    </div>

    <Card class="analytics-panel">
      <template #title>Résumé ventes</template>
      <template #content>
        <div class="analytics-stats-row">
          <div class="analytics-stat">
            <span class="analytics-stat__label">CA brut</span>
            <span class="analytics-stat__value">{{ formatMoney(data?.summary?.total_amount) }}</span>
          </div>
          <div class="analytics-stat">
            <span class="analytics-stat__label">Avoirs</span>
            <span class="analytics-stat__value">{{ formatMoney(data?.summary?.avoir_amount) }}</span>
          </div>
          <div class="analytics-stat">
            <span class="analytics-stat__label">Panier moyen</span>
            <span class="analytics-stat__value">{{ formatMoney(data?.summary?.average_ticket) }}</span>
          </div>
          <div class="analytics-stat">
            <span class="analytics-stat__label">Annulations</span>
            <span class="analytics-stat__value">{{ data?.summary?.cancelled_count ?? 0 }}</span>
          </div>
        </div>
      </template>
    </Card>
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
  return Number.isNaN(date.getTime())
    ? dateStr
    : date.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' })
}

const trend = computed(() => props.data?.trend ?? [])
const trendLabels = computed(() => trend.value.map((p) => formatDateLabel(p.date)))
const totalRevenue = computed(() =>
  trend.value.reduce((sum, p) => sum + Number(p.total_amount ?? 0), 0)
)

const revenueDatasets = computed(() => [{
  label: "Chiffre d'affaires",
  data: trend.value.map((p) => Number(p.total_amount ?? 0))
}])

const countDatasets = computed(() => [{
  label: 'Ventes',
  data: trend.value.map((p) => Number(p.count ?? 0))
}])

const topProducts = computed(() => props.data?.top_products ?? [])
const topProductLabels = computed(() => topProducts.value.map((p) => p.label))
const topProductDatasets = computed(() => [{
  label: 'CA',
  data: topProducts.value.map((p) => Number(p.total_amount ?? 0))
}])

const categories = computed(() => props.data?.by_category ?? [])
const categoryLabels = computed(() => categories.value.map((c) => c.category_name))
const categoryDatasets = computed(() => [{
  data: categories.value.map((c) => Number(c.total_amount ?? 0)),
  backgroundColor: CHART_PALETTE
}])

const clientSplit = computed(() => props.data?.client_split ?? {})
const clientSplitDatasets = computed(() => [{
  data: [
    Number(clientSplit.value.client_count ?? 0),
    Number(clientSplit.value.anonymous_count ?? 0)
  ],
  backgroundColor: [CHART_PALETTE[0], CHART_PALETTE[1]]
}])

const pipeline = computed(() => props.data?.order_pipeline ?? [])
const pipelineLabels = computed(() => pipeline.value.map((p) => p.status))
const pipelineDatasets = computed(() => [{
  label: 'Commandes',
  data: pipeline.value.map((p) => Number(p.count ?? 0))
}])
</script>
