<template>
  <AppFiltersCard :title="title" :active-count="activeCount" :default-expanded="defaultExpanded">
    <slot />
  </AppFiltersCard>
</template>

<script setup>
import AppFiltersCard from '@/domains/shared/components/AppFiltersCard.vue'

defineProps({
  title: {
    type: String,
    default: 'Filtres'
  },
  activeCount: {
    type: Number,
    default: 0
  },
  defaultExpanded: {
    type: Boolean,
    default: undefined
  }
})
</script>
