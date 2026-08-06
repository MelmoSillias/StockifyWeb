<template>
  <div v-if="embedded" :class="rootClass">
    <button
      type="button"
      class="app-filters-card__toggle"
      :aria-expanded="expanded"
      @click="toggle"
    >
      <span class="app-filters-card__toggle-leading">
        <i class="pi pi-filter app-filters-card__icon" aria-hidden="true" />
        <span class="app-filters-card__title">{{ title }}</span>
        <Tag
          v-if="activeCount > 0"
          :value="String(activeCount)"
          severity="info"
          rounded
          class="app-filters-card__badge"
        />
      </span>
      <i
        class="pi app-filters-card__chevron"
        :class="expanded ? 'pi-chevron-up' : 'pi-chevron-down'"
        aria-hidden="true"
      />
    </button>

    <div v-show="expanded" class="app-filters-card__body">
      <div class="app-filters-card__grid">
        <slot />
      </div>
    </div>
  </div>

  <Card v-else :class="rootClass">
    <template #content>
      <button
        type="button"
        class="app-filters-card__toggle"
        :aria-expanded="expanded"
        @click="toggle"
      >
        <span class="app-filters-card__toggle-leading">
          <i class="pi pi-filter app-filters-card__icon" aria-hidden="true" />
          <span class="app-filters-card__title">{{ title }}</span>
          <Tag
            v-if="activeCount > 0"
            :value="String(activeCount)"
            severity="info"
            rounded
            class="app-filters-card__badge"
          />
        </span>
        <i
          class="pi app-filters-card__chevron"
          :class="expanded ? 'pi-chevron-up' : 'pi-chevron-down'"
          aria-hidden="true"
        />
      </button>

      <div v-show="expanded" class="app-filters-card__body">
        <div class="app-filters-card__grid">
          <slot />
        </div>
      </div>
    </template>
  </Card>
</template>

<script setup>
import Card from 'primevue/card'
import Tag from 'primevue/tag'
import { computed, onMounted, ref } from 'vue'

import { useBreakpoint } from '@/domains/layout/composables/useBreakpoint'

const props = defineProps({
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
  },
  embedded: {
    type: Boolean,
    default: false
  }
})

const { isMobile } = useBreakpoint()
const expanded = ref(false)

const rootClass = computed(() => [
  props.embedded ? 'app-filters-card app-filters-card--embedded' : 'dashboard-panel app-filters-card dashboard-panel--compact',
  { 'app-filters-card--collapsed': !expanded.value }
])

onMounted(() => {
  expanded.value = props.defaultExpanded ?? !isMobile.value
})

const toggle = () => {
  expanded.value = !expanded.value
}
</script>

<style scoped>
.app-filters-card {
  min-width: 0;
}

.app-filters-card--embedded {
  margin-bottom: 0.75rem;
  border-bottom: 1px solid color-mix(in srgb, var(--layout-panel-border) 70%, transparent);
}

.app-filters-card--embedded.app-filters-card--collapsed {
  margin-bottom: 0.5rem;
}

.app-filters-card :deep(.p-card-body),
.app-filters-card :deep(.p-card-content) {
  padding: 0;
}

.app-filters-card__toggle {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  width: 100%;
  padding: 0.65rem 0.75rem;
  border: 0;
  background: transparent;
  color: var(--layout-text);
  cursor: pointer;
  text-align: left;
}

.app-filters-card--embedded .app-filters-card__toggle {
  padding: 0.45rem 0;
}

.app-filters-card__toggle-leading {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  min-width: 0;
}

.app-filters-card__icon {
  color: var(--layout-accent-strong);
  font-size: 0.9rem;
}

.app-filters-card__title {
  font-weight: 600;
  font-size: 0.9rem;
}

.app-filters-card__badge {
  flex-shrink: 0;
}

.app-filters-card__chevron {
  flex-shrink: 0;
  color: var(--layout-text-muted);
  font-size: 0.85rem;
}

.app-filters-card__body {
  padding: 0 0.75rem 0.75rem;
}

.app-filters-card--embedded .app-filters-card__body {
  padding: 0.5rem 0 0.75rem;
}

.app-filters-card__grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(min(100%, 140px), 1fr));
  gap: 0.85rem;
  align-items: end;
  min-width: 0;
}

:deep(.commerce-filter-field),
:deep(.filter-field) {
  display: grid;
  gap: 0.4rem;
  min-width: 0;
}

:deep(.commerce-filter-label),
:deep(.filter-label) {
  color: var(--layout-text-muted);
  font-size: 0.85rem;
}

@media (max-width: 767px) {
  .app-filters-card__toggle {
    padding: 0.55rem 0.5rem;
  }

  .app-filters-card__body {
    padding: 0 0.5rem 0.65rem;
  }

  .app-filters-card__grid {
    grid-template-columns: 1fr;
    gap: 0.65rem;
  }
}

@media (max-width: 360px) {
  .app-filters-card__toggle {
    padding: 0.45rem 0.35rem;
  }

  .app-filters-card__body {
    padding: 0 0.35rem 0.5rem;
  }

  .app-filters-card__title {
    font-size: 0.85rem;
  }
}
</style>
