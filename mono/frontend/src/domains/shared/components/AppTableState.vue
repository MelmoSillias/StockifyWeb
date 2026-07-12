<template>
  <div>
    <div v-if="loading" class="app-table-state app-table-state--loading">
      <ProgressSpinner strokeWidth="4" />
      <p>{{ loadingText }}</p>
    </div>
    <div v-else-if="isEmpty" class="app-table-state app-table-state--empty">
      <i :class="emptyIcon"></i>
      <h3>{{ emptyTitle }}</h3>
      <p>{{ emptyText }}</p>
    </div>
    <slot v-else />
  </div>
</template>

<script setup>
import ProgressSpinner from 'primevue/progressspinner'

defineProps({
  loading: {
    type: Boolean,
    default: false
  },
  isEmpty: {
    type: Boolean,
    default: false
  },
  loadingText: {
    type: String,
    default: 'Chargement en cours...'
  },
  emptyIcon: {
    type: String,
    default: 'pi pi-inbox'
  },
  emptyTitle: {
    type: String,
    default: 'Aucun element'
  },
  emptyText: {
    type: String,
    default: 'Aucune donnee disponible pour le moment.'
  }
})
</script>

<style scoped>
.app-table-state {
  display: grid;
  justify-items: center;
  gap: 1rem;
  padding: 3rem 1.5rem;
  border: 1px dashed color-mix(in srgb, var(--pv-surface-border) 78%, transparent);
  border-radius: 1.25rem;
  background:
    radial-gradient(
      circle at top center,
      color-mix(in srgb, var(--pv-accent-soft) 72%, transparent),
      transparent 58%
    ),
    var(--pv-surface-bg);
  text-align: center;
  color: var(--pv-text);
  backdrop-filter: blur(16px);
  box-shadow: var(--layout-shadow-panel);
}

.app-table-state--empty i {
  font-size: 2rem;
  color: var(--pv-accent);
}

.app-table-state--loading :deep(.p-progressspinner-circle) {
  stroke: var(--pv-accent);
}

.app-table-state h3,
.app-table-state p {
  margin: 0;
}

.app-table-state p {
  color: var(--pv-text-muted);
}

.app-table-state--empty h3 {
  color: var(--pv-text);
}
</style>