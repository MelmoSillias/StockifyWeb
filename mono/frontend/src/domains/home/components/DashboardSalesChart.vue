<template>
  <Card class="dashboard-panel dashboard-sales-chart">
    <template #title>
      <div class="dashboard-sales-chart__header">
        <div class="dashboard-section-header__leading">
          <span class="dashboard-section-header__icon">
            <i class="pi pi-chart-line"></i>
          </span>
          <div>
            <h2 class="dashboard-section-header__title">Chiffre d'affaires</h2>
            <p class="dashboard-section-header__subtitle">Évolution quotidienne</p>
          </div>
        </div>
        <div v-if="hasData" class="dashboard-sales-chart__total">
          <p class="dashboard-sales-chart__total-label">Total période</p>
          <p class="dashboard-sales-chart__total-value">{{ formatMoney(periodTotal) }}</p>
        </div>
      </div>
    </template>
    <template #content>
      <div ref="chartBodyRef" class="dashboard-sales-chart__body">
        <Chart
          v-if="hasData"
          ref="chartRef"
          type="line"
          :data="chartData"
          :options="chartOptions"
          class="dashboard-sales-chart__canvas"
          @loaded="resizeChart"
        />
        <div v-else class="dashboard-feed-empty">
          <span class="dashboard-feed-empty__icon">
            <i class="pi pi-chart-line"></i>
          </span>
          <p>Aucune vente enregistrée sur la période sélectionnée.</p>
        </div>
      </div>
    </template>
  </Card>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'

import Chart from 'primevue/chart'
import Card from 'primevue/card'

import { useDisplayFormatters } from '@/domains/shared/composables/useDisplayFormatters'

function resolveCssColor(color) {
  if (typeof document === 'undefined') {
    return color
  }

  const probe = document.createElement('span')
  probe.style.color = color
  probe.style.display = 'none'
  document.documentElement.appendChild(probe)
  const resolved = getComputedStyle(probe).color
  probe.remove()
  return resolved || color
}

const props = defineProps({
  salesTrend: {
    type: Object,
    default: null
  }
})

const { formatMoney } = useDisplayFormatters()

const accentColor = ref('#047857')
const accentFillTop = ref('rgba(4, 120, 87, 0.28)')
const accentFillBottom = ref('rgba(4, 120, 87, 0.02)')
const panelBgColor = ref('#ffffff')
const textMutedColor = ref('#64748b')
const panelBorderColor = ref('#e2e8f0')
const tooltipBgColor = ref('#ffffff')

function syncChartColors() {
  accentColor.value = resolveCssColor('var(--layout-accent-strong)')
  accentFillTop.value = resolveCssColor(
    'color-mix(in srgb, var(--layout-accent-strong) 28%, transparent)'
  )
  accentFillBottom.value = resolveCssColor(
    'color-mix(in srgb, var(--layout-accent-strong) 2%, transparent)'
  )
  panelBgColor.value = resolveCssColor('var(--layout-panel-bg)')
  textMutedColor.value = resolveCssColor('var(--layout-text-muted)')
  panelBorderColor.value = resolveCssColor(
    'color-mix(in srgb, var(--layout-panel-border) 65%, transparent)'
  )
  tooltipBgColor.value = resolveCssColor(
    'color-mix(in srgb, var(--layout-panel-bg) 95%, black)'
  )
}

const chartRef = ref(null)
const chartBodyRef = ref(null)
let resizeObserver = null

const resizeChart = () => {
  chartRef.value?.getChart()?.resize()
}

onMounted(() => {
  syncChartColors()

  if (chartBodyRef.value) {
    resizeObserver = new ResizeObserver(() => {
      resizeChart()
    })
    resizeObserver.observe(chartBodyRef.value)
  }
})

onUnmounted(() => {
  resizeObserver?.disconnect()
})

const points = computed(() => props.salesTrend?.points ?? [])
const hasData = computed(() => points.value.some((point) => Number(point.count) > 0))

const periodTotal = computed(() =>
  points.value.reduce((sum, point) => sum + Number(point.total_amount ?? 0), 0)
)

const chartData = computed(() => ({
  labels: points.value.map((point) => {
    const date = new Date(`${point.date}T00:00:00`)
    return Number.isNaN(date.getTime())
      ? point.date
      : date.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' })
  }),
  datasets: [
    {
      label: "Chiffre d'affaires",
      data: points.value.map((point) => Number(point.total_amount ?? 0)),
      fill: true,
      tension: 0.4,
      borderWidth: 2.5,
      borderColor: accentColor.value,
      backgroundColor: (context) => {
        const { chart } = context
        const { ctx, chartArea } = chart
        if (!chartArea) {
          return accentFillTop.value
        }

        const gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom)
        gradient.addColorStop(0, accentFillTop.value)
        gradient.addColorStop(1, accentFillBottom.value)
        return gradient
      },
      pointBackgroundColor: panelBgColor.value,
      pointBorderColor: accentColor.value,
      pointBorderWidth: 2,
      pointRadius: 4,
      pointHoverRadius: 6
    }
  ]
}))

const chartOptions = computed(() => ({
  maintainAspectRatio: false,
  interaction: {
    intersect: false,
    mode: 'index'
  },
  plugins: {
    legend: {
      display: false
    },
    tooltip: {
      backgroundColor: tooltipBgColor.value,
      titleColor: resolveCssColor('var(--layout-text-color)'),
      bodyColor: textMutedColor.value,
      borderColor: resolveCssColor('var(--layout-panel-border)'),
      borderWidth: 1,
      padding: 12,
      callbacks: {
        label: (context) => formatMoney(context.parsed.y)
      }
    }
  },
  scales: {
    x: {
      ticks: {
        maxRotation: 0,
        autoSkip: true,
        maxTicksLimit: 7,
        color: textMutedColor.value,
        font: { size: 11 }
      },
      grid: {
        display: false
      },
      border: {
        display: false
      }
    },
    y: {
      ticks: {
        color: textMutedColor.value,
        font: { size: 11 },
        callback: (value) => {
          const num = Number(value)
          if (num >= 1_000_000) {
            return `${(num / 1_000_000).toLocaleString('fr-FR', { maximumFractionDigits: 1 })}M`
          }
          if (num >= 1_000) {
            return `${(num / 1_000).toLocaleString('fr-FR', { maximumFractionDigits: 0 })}k`
          }
          return num.toLocaleString('fr-FR')
        }
      },
      grid: {
        color: panelBorderColor.value
      },
      border: {
        display: false
      }
    }
  }
}))
</script>
