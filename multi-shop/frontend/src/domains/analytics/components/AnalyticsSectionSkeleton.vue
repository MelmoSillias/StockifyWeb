<template>
  <div class="analytics-skeleton">
    <template v-if="variant === 'overview'">
      <div class="analytics-skeleton__kpis">
        <div v-for="n in 4" :key="`kpi-${n}`" class="analytics-skeleton__kpi analytics-skeleton__block"></div>
      </div>
    </template>

    <template v-else-if="variant === 'section'">
      <div class="analytics-skeleton__kpis">
        <div v-for="n in 3" :key="`stat-${n}`" class="analytics-skeleton__kpi analytics-skeleton__block" style="height: 5.5rem"></div>
      </div>
      <div class="analytics-skeleton__charts">
        <div v-for="n in 2" :key="`chart-${n}`" class="analytics-skeleton__chart analytics-skeleton__block"></div>
      </div>
    </template>

    <div v-else class="analytics-skeleton__lines">
      <div
        v-for="n in lines"
        :key="n"
        class="analytics-skeleton__line analytics-skeleton__block"
        :style="{ width: widths[n % widths.length] }"
      ></div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  lines: { type: Number, default: 6 },
  variant: {
    type: String,
    default: 'lines',
    validator: (value) => ['lines', 'overview', 'section'].includes(value)
  }
})

const widths = ['100%', '85%', '92%', '78%', '88%', '70%']
</script>
