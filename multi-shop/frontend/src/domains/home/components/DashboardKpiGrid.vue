<template>
  <div class="dashboard-kpis" :class="gridClass">
    <Card
      v-for="item in items"
      :key="item.id"
      class="dashboard-kpi-card dashboard-kpi-card--home"
      :class="item.toneClass"
    >
      <template #content>
        <div class="dashboard-kpi-card__watermark" aria-hidden="true">
          <i :class="item.icon"></i>
        </div>
        <div class="dashboard-kpi-card__top">
          <p class="dashboard-kpi-card__label">{{ item.title }}</p>
        </div>
        <p class="dashboard-kpi-card__value">{{ item.value }}</p>
        <p class="dashboard-kpi-card__hint">{{ item.hint }}</p>
      </template>
    </Card>
  </div>
</template>

<script setup>
import { computed } from 'vue'

import Card from 'primevue/card'

import { useDisplayFormatters } from '@/domains/shared/composables/useDisplayFormatters'

const props = defineProps({
  summary: {
    type: Object,
    default: null
  },
  visibleKpis: {
    type: Array,
    default: () => []
  },
  gridClass: {
    type: String,
    default: 'dashboard-kpis--home dashboard-kpis--cols-4'
  }
})

const { formatMoney, formatCompactNumber } = useDisplayFormatters()

const formatters = {
  formatMoney,
  formatCompactNumber
}

const items = computed(() => props.visibleKpis.map((definition) => {
  const section = props.summary?.[definition.summaryKey] ?? {}
  const rawValue = section[definition.valueKey] ?? 0

  let value
  if (definition.id === 'sales') {
    value = formatMoney(rawValue)
  } else {
    value = formatCompactNumber(rawValue)
  }

  return {
    id: definition.id,
    title: definition.title,
    value,
    hint: definition.hint(props.summary, formatters),
    icon: definition.icon,
    toneClass: definition.toneClass
  }
}))
</script>
