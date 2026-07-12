<template>
  <section class="dashboard-page">
    <div class="dashboard-hero dashboard-hero--compact">
      <div>
        <p class="dashboard-eyebrow">Stock</p>
        <h1 class="dashboard-title">Lots</h1>
        <p class="dashboard-description">Réceptions et stock disponible par variante.</p>
      </div>
    </div>

    <Card class="dashboard-panel filter-card">
      <template #content>
        <div class="filter-grid">
          <div>
            <label class="filter-label" for="variant-select">Variante</label>
            <Select
              id="variant-select"
              v-model="selectedVariantId"
              :options="variantOptions"
              option-label="label"
              option-value="value"
              placeholder="Sélectionner une variante"
              filter
              fluid
              :loading="variantsLoading"
              @update:model-value="loadLots"
            />
          </div>
          <div v-if="inventoryStore.stock" class="stock-summary">
            <p class="filter-label">Stock disponible</p>
            <p class="stock-summary__value">{{ inventoryStore.stock.available }}</p>
          </div>
        </div>
      </template>
    </Card>

    <AppEntityToolbar
      create-label="Réception lot"
      :count-label="`${inventoryStore.lots.length} lot(s)`"
      :show-create="Boolean(selectedVariantId)"
      @create="receiveDialog.openCreate()"
    />

    <Card class="dashboard-panel">
      <template #content>
        <AppTableState
          :loading="inventoryStore.loading"
          :is-empty="!inventoryStore.loading && !selectedVariantId"
          empty-title="Sélectionnez une variante"
          empty-text="Choisissez une variante pour consulter ses lots."
        >
          <DataTable
            v-if="selectedVariantId"
            :value="inventoryStore.lots"
            data-key="id"
            striped-rows
            responsive-layout="scroll"
          >
            <Column field="reference" header="Référence" />
            <Column field="quantity_initial" header="Qté initiale" />
            <Column field="quantity_remaining" header="Qté restante" />
            <Column field="unit_cost" header="Coût unitaire" />
            <Column field="received_at" header="Reçu le" />
            <Column field="expiry_date" header="Expiration" />
          </DataTable>
        </AppTableState>
      </template>
    </Card>

    <AppCrudDialog
      :visible="receiveDialog.visible"
      :model-value="receiveDialog.formData"
      title="Réception de lot"
      subtitle="Enregistrez une nouvelle réception de stock."
      :fields="receiveFields"
      :loading="inventoryStore.submitting"
      :general-error="inventoryStore.error"
      @update:visible="receiveDialog.visible = $event"
      @update:model-value="receiveDialog.formData = $event"
      @submit="receiveLot"
    />
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue'

import Card from 'primevue/card'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import Select from 'primevue/select'

import AppCrudDialog from '@/domains/shared/components/AppCrudDialog.vue'
import AppEntityToolbar from '@/domains/shared/components/AppEntityToolbar.vue'
import AppTableState from '@/domains/shared/components/AppTableState.vue'
import { useVariantOptions } from '@/domains/catalog/composables/useVariantOptions'
import { useCrudDialog } from '@/domains/shared/composables/useCrudDialog'
import { useEntityActions } from '@/domains/shared/composables/useEntityActions'
import { useInventoryStore } from '@/domains/inventory/stores/inventory'

const inventoryStore = useInventoryStore()
const { options: variantOptions, loading: variantsLoading, load: loadVariantOptions } = useVariantOptions()
const { showSuccess, showError } = useEntityActions()

const selectedVariantId = ref(null)

const createReceiveForm = () => ({
  quantity: '',
  unit_cost: '',
  reference: '',
  supplier_ref: '',
  expiry_date: null
})

const receiveDialog = useCrudDialog(createReceiveForm)

const receiveFields = [
  { name: 'quantity', label: 'Quantité', type: 'text', placeholder: 'Ex: 100', icon: 'pi pi-box' },
  { name: 'unit_cost', label: 'Coût unitaire', type: 'text', placeholder: 'Ex: 2.50', icon: 'pi pi-money-bill' },
  { name: 'reference', label: 'Référence lot', type: 'text', placeholder: 'Ex: LOT-2026-01', icon: 'pi pi-tag' },
  { name: 'supplier_ref', label: 'Réf. fournisseur', type: 'text', placeholder: 'Optionnel', icon: 'pi pi-truck' },
  { name: 'expiry_date', label: 'Date expiration', type: 'date', placeholder: 'Optionnel', icon: 'pi pi-calendar' }
]

const loadLots = async (variantId) => {
  if (!variantId) {
    inventoryStore.lots = []
    inventoryStore.stock = null
    return
  }

  try {
    await inventoryStore.fetchLots(variantId)
  } catch (error) {
    showError(error?.message || 'Impossible de charger les lots.')
  }
}

const receiveLot = async () => {
  try {
    const payload = {
      ...receiveDialog.formData,
      expiry_date: receiveDialog.formData.expiry_date
        ? new Date(receiveDialog.formData.expiry_date).toISOString().slice(0, 10)
        : null
    }
    await inventoryStore.receiveLot(selectedVariantId.value, payload)
    showSuccess('Lot reçu avec succès.')
    receiveDialog.close()
  } catch (error) {
    showError(error?.message || 'Impossible de recevoir le lot.')
  }
}

onMounted(async () => {
  try {
    await loadVariantOptions()
    if (variantOptions.value.length) {
      selectedVariantId.value = variantOptions.value[0].value
      await loadLots(selectedVariantId.value)
    }
  } catch (error) {
    showError(error?.message || 'Impossible de charger les variantes.')
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

.stock-summary__value {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 700;
}

@media (max-width: 768px) {
  .filter-grid {
    grid-template-columns: 1fr;
  }
}
</style>
