<template>
  <section class="dashboard-page">
    <div class="dashboard-hero dashboard-hero--compact">
      <div>
        <p class="dashboard-eyebrow">Stock</p>
        <h1 class="dashboard-title">Mouvements</h1>
        <p class="dashboard-description">Historique des entrées et sorties de stock.</p>
      </div>
    </div>

    <Card class="dashboard-panel filter-card">
      <template #content>
        <div class="filter-grid">
          <div>
            <label class="filter-label" for="variant-filter">Filtrer par variante</label>
            <Select
              id="variant-filter"
              v-model="selectedVariantId"
              :options="variantFilterOptions"
              option-label="label"
              option-value="value"
              placeholder="Toutes les variantes"
              filter
              fluid
              show-clear
              :loading="variantsLoading"
              @update:model-value="loadMovements"
            />
          </div>
          <div class="filter-actions">
            <Button label="Sortie stock" icon="pi pi-arrow-down" :disabled="!selectedVariantId" @click="stockOutDialog.openCreate()" />
            <Button label="Ajustement" icon="pi pi-sliders-h" severity="secondary" :disabled="!selectedVariantId" @click="adjustDialog.openCreate()" />
          </div>
        </div>
      </template>
    </Card>

    <Card class="dashboard-panel">
      <template #content>
        <AppTableState
          :loading="inventoryStore.loading"
          :is-empty="!inventoryStore.loading && inventoryStore.movements.length === 0"
          empty-title="Aucun mouvement"
          empty-text="Les mouvements de stock apparaîtront ici."
        >
          <DataTable :value="inventoryStore.movements" data-key="id" striped-rows responsive-layout="scroll">
            <Column field="type" header="Type" />
            <Column field="direction" header="Direction" />
            <Column field="quantity" header="Quantité" />
            <Column field="variant_id" header="Variante" />
            <Column field="occurred_at" header="Date" />
          </DataTable>
        </AppTableState>
      </template>
    </Card>

    <AppCrudDialog
      :visible="stockOutDialog.visible"
      :model-value="stockOutDialog.formData"
      title="Sortie de stock"
      subtitle="Les articles les plus anciens sont sortis en premier."
      :fields="stockOutFields"
      :loading="inventoryStore.submitting"
      :general-error="inventoryStore.error"
      @update:visible="stockOutDialog.visible = $event"
      @update:model-value="stockOutDialog.formData = $event"
      @submit="submitStockOut"
    />

    <AppCrudDialog
      :visible="adjustDialog.visible"
      :model-value="adjustDialog.formData"
      title="Ajustement de stock"
      subtitle="Correction manuelle du stock."
      :fields="adjustFields"
      :loading="inventoryStore.submitting"
      :general-error="inventoryStore.error"
      @update:visible="adjustDialog.visible = $event"
      @update:model-value="adjustDialog.formData = $event"
      @submit="submitAdjust"
    />
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'

import Button from 'primevue/button'
import Card from 'primevue/card'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import Select from 'primevue/select'

import AppCrudDialog from '@/domains/shared/components/AppCrudDialog.vue'
import AppTableState from '@/domains/shared/components/AppTableState.vue'
import { useVariantOptions } from '@/domains/catalog/composables/useVariantOptions'
import { useCrudDialog } from '@/domains/shared/composables/useCrudDialog'
import { useEntityActions } from '@/domains/shared/composables/useEntityActions'
import { useInventoryStore } from '@/domains/inventory/stores/inventory'

const inventoryStore = useInventoryStore()
const { options: variantOptions, loading: variantsLoading, load: loadVariantOptions } = useVariantOptions()
const { showSuccess, showError } = useEntityActions()

const selectedVariantId = ref(null)

const variantFilterOptions = computed(() => [
  { label: 'Toutes les variantes', value: null },
  ...variantOptions.value
])

const createStockOutForm = () => ({
  quantity: '',
  type: 'sale',
  reason: ''
})

const createAdjustForm = () => ({
  quantity: '',
  direction: 'in',
  reason: ''
})

const stockOutDialog = useCrudDialog(createStockOutForm)
const adjustDialog = useCrudDialog(createAdjustForm)

const stockOutFields = [
  { name: 'quantity', label: 'Quantité', type: 'text', placeholder: 'Ex: 5', icon: 'pi pi-box' },
  {
    name: 'type',
    label: 'Type',
    type: 'select',
    options: [
      { label: 'Vente', value: 'sale' },
      { label: 'Ajustement', value: 'adjustment' },
      { label: 'Transfert', value: 'transfer' }
    ],
    optionLabel: 'label',
    optionValue: 'value',
    icon: 'pi pi-tag'
  },
  { name: 'reason', label: 'Motif', type: 'text', placeholder: 'Optionnel', icon: 'pi pi-comment' }
]

const adjustFields = [
  { name: 'quantity', label: 'Quantité', type: 'text', placeholder: 'Ex: 3', icon: 'pi pi-box' },
  {
    name: 'direction',
    label: 'Direction',
    type: 'select',
    options: [
      { label: 'Entrée', value: 'in' },
      { label: 'Sortie', value: 'out' }
    ],
    optionLabel: 'label',
    optionValue: 'value',
    icon: 'pi pi-arrows-h'
  },
  { name: 'reason', label: 'Motif', type: 'text', placeholder: 'Optionnel', icon: 'pi pi-comment' }
]

const loadMovements = async () => {
  try {
    await inventoryStore.fetchMovements(selectedVariantId.value || null)
  } catch (error) {
    showError(error?.message || 'Impossible de charger les mouvements.')
  }
}

const submitStockOut = async () => {
  if (!selectedVariantId.value) {
    return
  }

  try {
    await inventoryStore.stockOut(selectedVariantId.value, stockOutDialog.formData)
    showSuccess('Sortie de stock enregistrée.')
    stockOutDialog.close()
    await loadMovements()
  } catch (error) {
    showError(error?.message || 'Stock insuffisant ou erreur lors de la sortie.')
  }
}

const submitAdjust = async () => {
  if (!selectedVariantId.value) {
    return
  }

  try {
    await inventoryStore.adjust(selectedVariantId.value, adjustDialog.formData)
    showSuccess('Ajustement enregistré.')
    adjustDialog.close()
    await loadMovements()
  } catch (error) {
    showError(error?.message || "Impossible d'ajuster le stock.")
  }
}

onMounted(async () => {
  try {
    await loadVariantOptions()
    await loadMovements()
  } catch (error) {
    showError(error?.message || 'Impossible de charger les mouvements.')
  }
})
</script>

<style scoped>
.filter-card {
  margin-bottom: 1rem;
}

.filter-grid {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 1rem;
  align-items: end;
}

.filter-label {
  display: block;
  margin-bottom: 0.5rem;
  color: var(--p-text-muted-color);
  font-size: 0.9rem;
}

.filter-actions {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

@media (max-width: 768px) {
  .filter-grid {
    grid-template-columns: 1fr;
  }
}
</style>
