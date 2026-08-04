import { computed, ref, watch } from 'vue'

export const DASHBOARD_PERIOD_PRESETS = [
  { id: 'today', label: "Aujourd'hui" },
  { id: '7days', label: '7 jours' },
  { id: '30days', label: '30 jours' },
  { id: 'month', label: 'Ce mois' },
  { id: 'custom', label: 'Personnalisé' }
]

const startOfDay = (date) => {
  const normalized = new Date(date)
  normalized.setHours(0, 0, 0, 0)
  return normalized
}

const endOfDay = (date) => {
  const normalized = new Date(date)
  normalized.setHours(23, 59, 59, 999)
  return normalized
}

const resolvePresetRange = (presetId) => {
  const today = startOfDay(new Date())

  switch (presetId) {
    case 'today':
      return [today, endOfDay(today)]
    case '7days': {
      const from = new Date(today)
      from.setDate(from.getDate() - 6)
      return [startOfDay(from), endOfDay(today)]
    }
    case '30days': {
      const from = new Date(today)
      from.setDate(from.getDate() - 29)
      return [startOfDay(from), endOfDay(today)]
    }
    case 'month': {
      const from = new Date(today.getFullYear(), today.getMonth(), 1)
      return [startOfDay(from), endOfDay(today)]
    }
    default:
      return [today, endOfDay(today)]
  }
}

const toDateKey = (value) => {
  const date = startOfDay(new Date(value))
  return Number.isNaN(date.getTime()) ? '' : date.toISOString().slice(0, 10)
}

export const useDashboardPeriod = (initialPreset = '7days') => {
  const preset = ref(initialPreset)
  const dateRange = ref(resolvePresetRange(initialPreset))

  watch(preset, (nextPreset) => {
    if (nextPreset !== 'custom') {
      dateRange.value = resolvePresetRange(nextPreset)
    }
  })

  watch(dateRange, (nextRange) => {
    if (!Array.isArray(nextRange) || nextRange.length !== 2 || !nextRange[0] || !nextRange[1]) {
      return
    }

    const fromKey = toDateKey(nextRange[0])
    const toKey = toDateKey(nextRange[1])
    const matchingPreset = DASHBOARD_PERIOD_PRESETS
      .filter((entry) => entry.id !== 'custom')
      .find((entry) => {
        const [presetFrom, presetTo] = resolvePresetRange(entry.id)
        return toDateKey(presetFrom) === fromKey && toDateKey(presetTo) === toKey
      })

    preset.value = matchingPreset?.id ?? 'custom'
  }, { deep: true })

  const from = computed(() => dateRange.value?.[0] ?? new Date())
  const to = computed(() => dateRange.value?.[1] ?? new Date())

  const setPreset = (nextPreset) => {
    preset.value = nextPreset
  }

  return {
    preset,
    dateRange,
    from,
    to,
    setPreset,
    presets: DASHBOARD_PERIOD_PRESETS
  }
}
