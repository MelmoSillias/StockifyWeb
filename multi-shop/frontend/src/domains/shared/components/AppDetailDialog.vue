<template>
  <Dialog
    :visible="visible"
    modal
    class="detail-dialog"
    :style="{ width }"
    dismissable-mask
    @update:visible="$emit('update:visible', $event)"
  >
    <template #header>
      <div class="detail-dialog__header">
        <span class="detail-dialog__icon">
          <i :class="icon"></i>
        </span>
        <div class="detail-dialog__copy">
          <h2 class="detail-dialog__title">{{ title }}</h2>
          <p v-if="subtitle" class="detail-dialog__subtitle">{{ subtitle }}</p>
        </div>
      </div>
    </template>

    <AppTableState
      :loading="loading"
      :error="error"
      :retrying="retrying"
      :is-empty="isEmpty"
      :loading-text="loadingText"
      :empty-title="emptyTitle"
      :empty-text="emptyText"
      @retry="$emit('retry')"
    >
      <div class="detail-dialog__content">
        <slot />
      </div>
    </AppTableState>

    <template #footer>
      <Button label="Fermer" icon="pi pi-times" severity="secondary" text @click="$emit('update:visible', false)" />
    </template>
  </Dialog>
</template>

<script setup>
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'

import AppTableState from '@/domains/shared/components/AppTableState.vue'

defineProps({
  visible: {
    type: Boolean,
    default: false
  },
  title: {
    type: String,
    required: true
  },
  subtitle: {
    type: String,
    default: ''
  },
  icon: {
    type: String,
    default: 'pi pi-eye'
  },
  loading: {
    type: Boolean,
    default: false
  },
  error: {
    type: String,
    default: null
  },
  retrying: {
    type: Boolean,
    default: false
  },
  isEmpty: {
    type: Boolean,
    default: false
  },
  loadingText: {
    type: String,
    default: 'Chargement...'
  },
  emptyTitle: {
    type: String,
    default: 'Aucune donnée'
  },
  emptyText: {
    type: String,
    default: 'Aucun détail disponible.'
  },
  width: {
    type: String,
    default: 'min(960px, 96vw)'
  }
})

defineEmits(['update:visible', 'retry'])
</script>

<style scoped>
.detail-dialog__header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.detail-dialog__icon {
  display: grid;
  place-items: center;
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 999px;
  background: color-mix(in srgb, var(--pv-accent-soft) 80%, white);
  color: var(--pv-accent-strong);
}

.detail-dialog__copy {
  display: grid;
  gap: 0.125rem;
}

.detail-dialog__title,
.detail-dialog__subtitle {
  margin: 0;
}

.detail-dialog__title {
  color: var(--pv-accent-strong);
  font-size: 1.05rem;
  font-weight: 700;
}

.detail-dialog__subtitle {
  color: var(--pv-text-muted);
}

.detail-dialog__content {
  display: grid;
  gap: 1rem;
}
</style>