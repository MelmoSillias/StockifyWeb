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
      <DataTable :value="variants" data-key="id" size="small" striped-rows responsive-layout="scroll">
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
        <Column header="Prix">
          <template #body="{ data }">
            {{ formatCompactNumber(data.default_price) }}
          </template>
        </Column>
        <Column field="alert_threshold" header="Seuil">
          <template #body="{ data }">
            {{ formatCompactNumber(data.alert_threshold) }}
          </template>
        </Column>
        <Column header="Stock">
          <template #body="{ data }">
            {{ formatCompactNumber(data.available) }}
          </template>
        </Column>
        <Column header="Actions" style="width: 280px">
          <template #body="{ data }">
            <div class="actions-cell">
              <Button
                icon="pi pi-eye"
                v-tooltip.top="'Voir le stock'"
                text
                rounded
                size="small"
                @click="$emit('view-stock', data)"
              />
              <Button
                icon="pi pi-inbox"
                v-tooltip.top="'Réception'"
                text
                rounded
                size="small"
                @click="$emit('receive', data)"
              />
              <Button
                icon="pi pi-arrow-down"
                v-tooltip.top="'Sortie'"
                text
                rounded
                size="small"
                @click="$emit('stock-out', data)"
              />
              <Button
                icon="pi pi-sliders-h"
                v-tooltip.top="'Ajustement'"
                text
                rounded
                size="small"
                @click="$emit('adjust', data)"
              />
              <Button
                icon="pi pi-pencil"
                v-tooltip.top="'Modifier'"
                text
                rounded
                size="small"
                @click="$emit('edit-variant', data)"
              />
              <Button
                icon="pi pi-trash"
                v-tooltip.top="'Supprimer'"
                text
                rounded
                size="small"
                severity="danger"
                @click="$emit('delete-variant', data)"
              />
            </div>
          </template>
        </Column>
      </DataTable>
    </AppTableState>
  </div>
</template>

<script setup>
import Button from 'primevue/button'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'

import AppTableState from '@/domains/shared/components/AppTableState.vue'
import { useDisplayFormatters } from '@/domains/shared/composables/useDisplayFormatters'
import { variantDisplayLabel } from '@/domains/catalog/utils/variantLabel'

defineProps({
  product: { type: Object, required: true },
  variants: { type: Array, default: () => [] },
  units: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false }
})

defineEmits([
  'add-variant',
  'view-stock',
  'receive',
  'stock-out',
  'adjust',
  'edit-variant',
  'delete-variant'
])

const { formatCompactNumber } = useDisplayFormatters()

const labelFor = (variant) => {
  if (variant.unit_label) {
    return `${variant.unit_label} · ${variant.sale_mode}`
  }
  return variantDisplayLabel(variant, [])
}
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

.actions-cell {
  display: flex;
  align-items: center;
  gap: 0.1rem;
  flex-wrap: wrap;
}
</style>
