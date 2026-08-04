<template>
  <div class="dashboard-kpis dashboard-kpis--home">
    <Card v-for="index in 4" :key="index" class="dashboard-kpi-card dashboard-kpi-card--home dashboard-kpi-card--skeleton">
      <template #content>
        <div class="dashboard-kpi-card__top">
          <Skeleton width="4.5rem" height="0.75rem" />
          <Skeleton width="2.5rem" height="2.5rem" border-radius="0.75rem" />
        </div>
        <Skeleton width="6.5rem" height="1.5rem" class="dashboard-kpi-skeleton__value" />
        <Skeleton width="8.5rem" height="0.75rem" />
      </template>
    </Card>
  </div>
</template>

<script setup>
import Card from 'primevue/card'
import Skeleton from 'primevue/skeleton'
</script>

<style scoped>
.dashboard-kpi-card--skeleton :deep(.p-card-content) {
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
  min-height: 8.5rem;
}

.dashboard-kpi-card__top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  margin-bottom: auto;
}

.dashboard-kpi-skeleton__value {
  margin: 0.65rem 0 0.25rem;
}
</style>
