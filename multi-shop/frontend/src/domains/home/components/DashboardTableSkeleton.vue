<template>
  <Card class="dashboard-panel">
    <template #title>
      <Skeleton width="12rem" height="1.25rem" />
    </template>
    <template #content>
      <div class="dashboard-table-skeleton">
        <Skeleton v-for="index in rows" :key="index" width="100%" height="2.5rem" border-radius="0.5rem" />
      </div>
    </template>
  </Card>
</template>

<script setup>
import Card from 'primevue/card'
import Skeleton from 'primevue/skeleton'

defineProps({
  rows: {
    type: Number,
    default: 5
  }
})
</script>

<style scoped>
.dashboard-table-skeleton {
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
}
</style>
