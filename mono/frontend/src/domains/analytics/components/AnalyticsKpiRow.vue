<template>
  <div class="analytics-kpis">
    <Card
      v-for="item in items"
      :key="item.id"
      class="analytics-kpi-card"
      :class="item.toneClass"
    >
      <template #content>
        <div class="analytics-kpi-card__watermark" aria-hidden="true">
          <i :class="item.icon"></i>
        </div>
        <div class="analytics-kpi-card__top">
          <p class="analytics-kpi-card__label">{{ item.title }}</p>
          <AnalyticsComparisonBadge v-if="item.delta !== undefined" :delta="item.delta" />
        </div>
        <p class="analytics-kpi-card__value">{{ item.value }}</p>
        <p v-if="item.hint" class="analytics-kpi-card__hint">{{ item.hint }}</p>
      </template>
    </Card>

    <Card v-if="projection" class="analytics-kpi-card analytics-kpi-card--projection">
      <template #content>
        <div class="analytics-kpi-card__watermark" aria-hidden="true">
          <i class="pi pi-sparkles"></i>
        </div>
        <p class="analytics-kpi-card__label">Projection fin de période</p>
        <p class="analytics-kpi-card__value">{{ formatMoney(projection.projected_amount) }}</p>
        <p class="analytics-kpi-card__hint">
          Moy. {{ formatMoney(projection.daily_average) }}/j · {{ projection.remaining_days }} j restant(s)
        </p>
      </template>
    </Card>
  </div>
</template>

<script setup>
import { computed } from 'vue'

import Card from 'primevue/card'

import AnalyticsComparisonBadge from '@/domains/analytics/components/charts/AnalyticsComparisonBadge.vue'
import { useDisplayFormatters } from '@/domains/shared/composables/useDisplayFormatters'

const props = defineProps({
  overview: { type: Object, default: null },
  visibleKpis: { type: Array, default: () => [] },
  projection: { type: Object, default: null }
})

const { formatMoney, formatCompactNumber } = useDisplayFormatters()

const getNestedValue = (obj, path) =>
  path.split('.').reduce((acc, key) => acc?.[key], obj)

const items = computed(() => props.visibleKpis.map((def) => {
  const sectionData = props.overview?.[def.section] ?? {}
  const rawValue = sectionData[def.valueKey] ?? 0
  const value = def.format === 'money' ? formatMoney(rawValue) : formatCompactNumber(rawValue)
  const delta = def.comparisonKey
    ? getNestedValue(props.overview?.comparison, def.comparisonKey)
    : undefined

  return {
    id: def.id,
    title: def.title,
    value,
    hint: def.hint ?? null,
    icon: def.icon,
    toneClass: def.toneClass,
    delta
  }
}))
</script>
