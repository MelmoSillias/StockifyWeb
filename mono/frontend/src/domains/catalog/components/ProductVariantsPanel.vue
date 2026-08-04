<template>
  <div class="product-variants-panel">
    <div class="product-variants-panel__header">
      <span class="product-variants-panel__title">Variantes (formats de vente)</span>
      <Button
        label="Ajouter une variante"
        icon="pi pi-plus"
        size="small"
        text
        @click="$emit('add-variant', product)"
      />
    </div>

    <AppTableState
      :loading="loading"
      :is-empty="!loading && variants.length === 0"
      empty-title="Aucune variante"
      empty-text="Ajoutez un format de vente (ex. 1L, 5L) pour ce produit."
    >
      <DataTable
        :value="variants"
        data-key="id"
        size="small"
        striped-rows
        :responsive-layout="tableLayout"
      >
        <Column header="Format">
          <template #body="{ data }">
            <div class="variant-name-cell">
              <span>{{ labelFor(data) }}</span>
              <i
                v-if="data.is_low_stock"
                class="pi pi-exclamation-circle variant-alert-icon"
                v-tooltip.top="'Stock faible'"
              />
            </div>
          </template>
        </Column>
        <Column v-if="!isMobile" header="Prix">
          <template #body="{ data }">
            <span class="variant-price-cell font-bold">
              {{ formatMoney(data.default_price) }}
            </span>
          </template>
        </Column>
        <Column v-if="!isMobile" field="alert_threshold" header="Seuil">
          <template #body="{ data }">
            {{ formatCompactNumber(data.alert_threshold) }}
          </template>
        </Column>
        <Column header="Stock">
          <template #body="{ data }">
            <Tag :value="formatCompactNumber(data.available)" icon="pi pi-inbox" :severity="Number(data.available) <= Number(data.alert_threshold) ? 'danger' : 'success'" rounded />
          </template>
        </Column>
        <Column header="Actions" style="width: 280px">
          <template #body="{ data }">
            <AppTableActionsMenu
              :actions="variantRowActions(data)"
              aria-label="Actions variante"
            />
          </template>
        </Column>
      </DataTable>
    </AppTableState>
  </div>
</template>

<script setup>
import { computed } from 'vue'

import Button from 'primevue/button'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import Tag from 'primevue/tag'

import AppTableActionsMenu from '@/domains/shared/components/AppTableActionsMenu.vue'
import AppTableState from '@/domains/shared/components/AppTableState.vue'
import { useBreakpoint } from '@/domains/layout/composables/useBreakpoint'
import { useDisplayFormatters } from '@/domains/shared/composables/useDisplayFormatters'
import { variantDisplayLabel } from '@/domains/catalog/utils/variantLabel'

defineProps({
  product: { type: Object, required: true },
  variants: { type: Array, default: () => [] },
  units: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false }
})

const emit = defineEmits([
  'add-variant',
  'view-stock',
  'receive',
  'stock-out',
  'adjust',
  'edit-variant',
  'delete-variant'
])

const { isMobile } = useBreakpoint()
const tableLayout = computed(() => (isMobile.value ? 'stack' : 'scroll'))
const { formatCompactNumber, formatMoney } = useDisplayFormatters()

const labelFor = (variant) => {
  if (variant.unit_label) {
    return `${variant.unit_label} · ${variant.sale_mode}`
  }
  return variantDisplayLabel(variant, [])
}

const variantRowActions = (variant) => [
  {
    label: 'Voir le stock',
    icon: 'pi pi-eye',
    command: () => emit('view-stock', variant)
  },
  {
    label: 'Réception',
    icon: 'pi pi-inbox',
    command: () => emit('receive', variant)
  },
  {
    label: 'Ajustement',
    icon: 'pi pi-sliders-h',
    command: () => emit('adjust', variant)
  },
  {
    label: 'Modifier',
    icon: 'pi pi-pencil',
    command: () => emit('edit-variant', variant)
  },
  {
    label: 'Supprimer',
    icon: 'pi pi-trash',
    severity: 'danger',
    command: () => emit('delete-variant', variant)
  }
]
</script>

<style scoped>
.product-variants-panel {
  padding: 0.35rem 0.5rem 0.65rem;
}

.product-variants-panel__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  margin-bottom: 0.5rem;
  flex-wrap: wrap;
}

.product-variants-panel__title {
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--p-text-muted-color);
}

.variant-name-cell {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
}

.variant-alert-icon {
  color: var(--p-red-500, #ef4444);
  font-size: 0.95rem;
}

@media (max-width: 767px) {
  .product-variants-panel__header .p-button {
    width: 100%;
    justify-content: center;
  }
}
</style>
