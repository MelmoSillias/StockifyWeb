<template>
  <Card
    class="analytics-panel analytics-chart-panel analytics-chart-panel--accented"
    :style="{ '--chart-accent': `var(${accentVar})` }"
  >
    <template #title>
      <div class="analytics-chart-panel__header">
        <div class="analytics-section-header__leading">
          <span v-if="icon" class="analytics-section-header__icon">
            <i :class="icon"></i>
          </span>
          <div>
            <h3 class="analytics-section-header__title">{{ title }}</h3>
            <p v-if="subtitle" class="analytics-section-header__subtitle">{{ subtitle }}</p>
          </div>
        </div>
        <div v-if="headerValue" class="analytics-chart-panel__total">
          <p class="analytics-chart-panel__total-label">{{ headerLabel }}</p>
          <p class="analytics-chart-panel__total-value">{{ headerValue }}</p>
        </div>
      </div>
    </template>
    <template #content>
      <div ref="chartBodyRef" class="analytics-chart-panel__body">
        <Chart
          v-if="hasData"
          ref="chartRef"
          :type="type"
          :data="chartData"
          :options="chartOptions"
          class="analytics-chart-panel__canvas"
          @loaded="resizeChart"
        />
        <div v-else class="analytics-empty">
          <span class="analytics-empty__icon"><i class="pi pi-chart-line"></i></span>
          <p>{{ emptyText }}</p>
        </div>
      </div>
    </template>
  </Card>
</template>

<script setup>
import { computed, ref } from 'vue'

import Chart from 'primevue/chart'
import Card from 'primevue/card'

import { useChartTheme } from '@/domains/analytics/composables/useChartTheme'
import { useDisplayFormatters } from '@/domains/shared/composables/useDisplayFormatters'

const props = defineProps({
  title: { type: String, required: true },
  subtitle: { type: String, default: '' },
  icon: { type: String, default: 'pi pi-chart-line' },
  type: { type: String, default: 'line' },
  labels: { type: Array, default: () => [] },
  datasets: { type: Array, default: () => [] },
  emptyText: { type: String, default: 'Aucune donnée sur la période.' },
  headerLabel: { type: String, default: 'Total' },
  headerValue: { type: String, default: '' },
  valueFormat: { type: String, default: 'money' },
  accentVar: { type: String, default: '--layout-accent-strong' },
  showLegend: { type: Boolean, default: false }
})

const { formatMoney } = useDisplayFormatters()
const {
  accentColor, accentFillTop, accentFillBottom, panelBgColor,
  buildBaseOptions, useChartResize, CHART_PALETTE
} = useChartTheme(props.accentVar)

const chartRef = ref(null)
const chartBodyRef = ref(null)
const { resizeChart } = useChartResize(chartRef, chartBodyRef)

const hasData = computed(() =>
  props.datasets.some((ds) => ds.data?.some((v) => Number(v) !== 0))
)

const formatY = (value) => {
  const num = Number(value)
  if (props.valueFormat === 'money') {
    if (num >= 1_000_000) return `${(num / 1_000_000).toLocaleString('fr-FR', { maximumFractionDigits: 1 })}M`
    if (num >= 1_000) return `${(num / 1_000).toLocaleString('fr-FR', { maximumFractionDigits: 0 })}k`
  }
  return num.toLocaleString('fr-FR')
}

const chartData = computed(() => ({
  labels: props.labels,
  datasets: props.datasets.map((ds, index) => ({
    ...ds,
    tension: ds.tension ?? 0.4,
    borderWidth: ds.borderWidth ?? 2.5,
    borderColor: ds.borderColor ?? CHART_PALETTE[index % CHART_PALETTE.length],
    backgroundColor: ds.backgroundColor ?? (props.type === 'line'
      ? (context) => {
          const { chart } = context
          const { ctx, chartArea } = chart
          if (!chartArea) return accentFillTop.value
          const gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom)
          gradient.addColorStop(0, accentFillTop.value)
          gradient.addColorStop(1, accentFillBottom.value)
          return gradient
        }
      : CHART_PALETTE[index % CHART_PALETTE.length]),
    fill: ds.fill ?? props.type === 'line',
    pointBackgroundColor: panelBgColor.value,
    pointBorderColor: ds.borderColor ?? accentColor.value,
    pointBorderWidth: 2,
    pointRadius: props.type === 'line' ? 3 : 0,
    pointHoverRadius: props.type === 'line' ? 5 : 0
  }))
}))

const chartOptions = computed(() => {
  const options = buildBaseOptions(formatY)
  options.plugins.legend.display = props.showLegend
  if (props.valueFormat === 'money') {
    options.plugins.tooltip.callbacks = {
      label: (ctx) => formatMoney(ctx.parsed.y ?? ctx.parsed)
    }
  }
  if (props.type === 'bar' && props.datasets.length > 1) {
    options.scales.x.stacked = true
    options.scales.y.stacked = true
  }
  if (props.type === 'doughnut' || props.type === 'pie') {
    delete options.scales
  }
  return options
})
</script>
