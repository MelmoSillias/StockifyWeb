<template>
  <div>
    <div v-if="loading" class="app-table-state app-table-state--loading">
      <ProgressSpinner strokeWidth="4" />
      <p>{{ loadingText }}</p>
    </div>
    <div v-else-if="error" class="app-table-state app-table-state--error">
      <i class="pi pi-exclamation-triangle"></i>
      <h3>{{ errorTitle }}</h3>
      <p>{{ error }}</p>
      <Button
        label="Réessayer"
        icon="pi pi-refresh"
        severity="warn"
        :loading="retrying"
        @click="$emit('retry')"
      />
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
import Button from 'primevue/button'
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
  error: {
    type: String,
    default: null
  },
  errorTitle: {
    type: String,
    default: 'Erreur de chargement'
  },
  retrying: {
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

defineEmits(['retry'])
</script>

<style scoped>
.app-table-state {
  display: grid;
  justify-items: center;
  gap: 1rem;
  padding: 3rem 1.5rem;
  border: 1px dashed color-mix(in srgb, var(--pv-surface-border) 78%, transparent);
  border-radius: var(--layout-radius-lg, 0.5rem);
  background: var(--content-surface-empty, var(--content-surface-panel));
  text-align: center;
  color: var(--pv-text);
  box-shadow: var(--layout-shadow-panel);
}

.app-table-state--empty i {
  font-size: 2rem;
  color: var(--pv-accent);
}

.app-table-state--error {
  border-color: color-mix(in srgb, var(--p-orange-500, #f59e0b) 55%, transparent);
}

.app-table-state--error i {
  font-size: 2rem;
  color: var(--p-orange-500, #f59e0b);
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
