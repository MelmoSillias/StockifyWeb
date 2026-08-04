<template>
  <span
    v-if="delta !== null && delta !== undefined"
    class="analytics-comparison-badge"
    :class="badgeClass"
  >
    <i :class="iconClass"></i>
    {{ formattedDelta }}
  </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  delta: {
    type: [String, Number, null],
    default: null
  },
  suffix: {
    type: String,
    default: 'vs période préc.'
  }
})

const numericDelta = computed(() => {
  if (props.delta === null || props.delta === undefined) return null
  return Number(props.delta)
})

const badgeClass = computed(() => {
  const value = numericDelta.value
  if (value === null || Number.isNaN(value)) return 'analytics-comparison-badge--neutral'
  if (value > 0) return 'analytics-comparison-badge--up'
  if (value < 0) return 'analytics-comparison-badge--down'
  return 'analytics-comparison-badge--neutral'
})

const iconClass = computed(() => {
  const value = numericDelta.value
  if (value === null || Number.isNaN(value)) return 'pi pi-minus'
  if (value > 0) return 'pi pi-arrow-up-right'
  if (value < 0) return 'pi pi-arrow-down-right'
  return 'pi pi-minus'
})

const formattedDelta = computed(() => {
  const value = numericDelta.value
  if (value === null || Number.isNaN(value)) return props.suffix
  const sign = value > 0 ? '+' : ''
  return `${sign}${value.toLocaleString('fr-FR', { maximumFractionDigits: 1 })}% ${props.suffix}`
})
</script>
