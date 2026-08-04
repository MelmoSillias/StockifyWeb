<template>
  <Card class="dashboard-panel dashboard-section-skeleton">
    <template #content>
      <Skeleton width="40%" height="1.25rem" class="dashboard-section-skeleton__title" />
      <div class="dashboard-section-skeleton__lines">
        <Skeleton v-for="index in lines" :key="index" width="100%" height="1rem" />
      </div>
    </template>
  </Card>
</template>

<script setup>
import Card from 'primevue/card'
import Skeleton from 'primevue/skeleton'

defineProps({
  lines: {
    type: Number,
    default: 5
  }
})
</script>

<style scoped>
.dashboard-section-skeleton__title {
  margin-bottom: 1rem;
}

.dashboard-section-skeleton__lines {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}
</style>
