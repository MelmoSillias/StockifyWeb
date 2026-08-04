import { onMounted, onUnmounted, ref } from 'vue'

export function resolveCssColor(color) {
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

export function useChartTheme(accentVar = '--layout-accent-strong') {
  const accentColor = ref('#047857')
  const accentFillTop = ref('rgba(4, 120, 87, 0.28)')
  const accentFillBottom = ref('rgba(4, 120, 87, 0.02)')
  const panelBgColor = ref('#ffffff')
  const textMutedColor = ref('#64748b')
  const panelBorderColor = ref('#e2e8f0')
  const tooltipBgColor = ref('#ffffff')
  const textColor = ref('#0f172a')

  const syncColors = () => {
    accentColor.value = resolveCssColor(`var(${accentVar})`)
    accentFillTop.value = resolveCssColor(
      `color-mix(in srgb, var(${accentVar}) 28%, transparent)`
    )
    accentFillBottom.value = resolveCssColor(
      `color-mix(in srgb, var(${accentVar}) 2%, transparent)`
    )
    panelBgColor.value = resolveCssColor('var(--layout-panel-bg)')
    textMutedColor.value = resolveCssColor('var(--layout-text-muted)')
    textColor.value = resolveCssColor('var(--layout-text-color)')
    panelBorderColor.value = resolveCssColor(
      'color-mix(in srgb, var(--layout-panel-border) 65%, transparent)'
    )
    tooltipBgColor.value = resolveCssColor(
      'color-mix(in srgb, var(--layout-panel-bg) 95%, black)'
    )
  }

  onMounted(syncColors)

  const buildBaseOptions = (formatY = null) => ({
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
        titleColor: textColor.value,
        bodyColor: textMutedColor.value,
        borderColor: resolveCssColor('var(--layout-panel-border)'),
        borderWidth: 1,
        padding: 12
      }
    },
    scales: {
      x: {
        ticks: {
          maxRotation: 0,
          autoSkip: true,
          maxTicksLimit: 8,
          color: textMutedColor.value,
          font: { size: 11 }
        },
        grid: { display: false },
        border: { display: false }
      },
      y: {
        ticks: {
          color: textMutedColor.value,
          font: { size: 11 },
          callback: formatY
        },
        grid: { color: panelBorderColor.value },
        border: { display: false }
      }
    }
  })

  const useChartResize = (chartRef, containerRef) => {
    let resizeObserver = null

    const resizeChart = () => {
      chartRef.value?.getChart()?.resize()
    }

    onMounted(() => {
      syncColors()
      if (containerRef.value) {
        resizeObserver = new ResizeObserver(resizeChart)
        resizeObserver.observe(containerRef.value)
      }
    })

    onUnmounted(() => {
      resizeObserver?.disconnect()
    })

    return { resizeChart }
  }

  const CHART_PALETTE = [
    '#047857',
    '#2563eb',
    '#d97706',
    '#7c3aed',
    '#db2777',
    '#0891b2',
    '#65a30d',
    '#ea580c'
  ]

  return {
    accentColor,
    accentFillTop,
    accentFillBottom,
    panelBgColor,
    textMutedColor,
    panelBorderColor,
    tooltipBgColor,
    textColor,
    syncColors,
    buildBaseOptions,
    useChartResize,
    CHART_PALETTE
  }
}
