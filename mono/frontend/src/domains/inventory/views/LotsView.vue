<template>
  <section class="dashboard-page">
    <AppFiltersCard>
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
        <p class="stock-summary__value">{{ formatCompactNumber(inventoryStore.stock.available, '0') }}</p>
      </div>
    </AppFiltersCard>

    <Card class="dashboard-panel">
      <template #title>
        <AppTablePanelHeader
          title="Lots"
          :count-label="`${inventoryStore.lots.length} lot(s)`"
          create-label="Réception lot"
          :show-create="Boolean(selectedVariantId)"
          :show-search="false"
          :reloading="inventoryStore.loading"
          @create="receiveDialog.openCreate()"
          @reload="loadLots(selectedVariantId)"
        />
      </template>
      <template #content>
        <AppTableState
          :loading="inventoryStore.loading"
          :error="inventoryStore.error"
          :is-empty="!inventoryStore.loading && !selectedVariantId"
          empty-title="Sélectionnez une variante"
          empty-text="Choisissez une variante pour consulter ses lots."
          @retry="loadLots(selectedVariantId)"
        >
          <DataTable
            v-if="selectedVariantId"
            :value="inventoryStore.lots"
            data-key="id"
            striped-rows
            responsive-layout="scroll"
          >
            <Column field="reference" header="Référence" />
            <Column header="Qté initiale">
              <template #body="{ data }">
                {{ formatCompactNumber(data.quantity_initial) }}
              </template>
            </Column>
            <Column header="Qté restante">
              <template #body="{ data }">
                {{ formatCompactNumber(data.quantity_remaining) }}
              </template>
            </Column>
            <Column header="Coût unitaire">
              <template #body="{ data }">
                {{ formatCompactNumber(data.unit_cost) }}
              </template>
            </Column>
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
import { computed, onMounted, ref } from 'vue'

import Card from 'primevue/card'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import Select from 'primevue/select'

import AppFiltersCard from '@/domains/shared/components/AppFiltersCard.vue'
import AppCrudDialog from '@/domains/shared/components/AppCrudDialog.vue'
import AppTablePanelHeader from '@/domains/shared/components/AppTablePanelHeader.vue'
import AppTableState from '@/domains/shared/components/AppTableState.vue'
import { useVariantOptions } from '@/domains/catalog/composables/useVariantOptions'
import { useFournisseurOptions } from '@/domains/fournisseur/composables/useFournisseurOptions'
import { useCrudDialog } from '@/domains/shared/composables/useCrudDialog'
import { useDisplayFormatters } from '@/domains/shared/composables/useDisplayFormatters'
import { useEntityActions } from '@/domains/shared/composables/useEntityActions'
import { useInventoryStore } from '@/domains/inventory/stores/inventory'

const inventoryStore = useInventoryStore()
const { options: variantOptions, loading: variantsLoading, load: loadVariantOptions } = useVariantOptions()
const { options: fournisseurOptions, load: loadFournisseurOptions } = useFournisseurOptions()
const { showSuccess, showError } = useEntityActions()
const { formatCompactNumber } = useDisplayFormatters()

const selectedVariantId = ref(null)

const createReceiveForm = () => ({
  quantity: '',
  unit_cost: '',
  reference: '',
  fournisseur_id: null,
  expiry_date: null
})

const receiveDialog = useCrudDialog(createReceiveForm)

const receiveFields = computed(() => [
  { name: 'quantity', label: 'Quantité', type: 'text', placeholder: 'Ex: 100', icon: 'pi pi-box' },
  { name: 'unit_cost', label: 'Coût unitaire', type: 'text', placeholder: 'Ex: 2.50', icon: 'pi pi-money-bill' },
  { name: 'reference', label: 'Référence lot', type: 'text', placeholder: 'Ex: LOT-2026-01', icon: 'pi pi-tag' },
  {
    name: 'fournisseur_id',
    label: 'Fournisseur',
    type: 'select',
    options: fournisseurOptions.value,
    optionLabel: 'label',
    optionValue: 'value',
    placeholder: 'Optionnel',
    icon: 'pi pi-truck'
  },
  { name: 'expiry_date', label: 'Date expiration', type: 'date', placeholder: 'Optionnel', icon: 'pi pi-calendar' }
])

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
    await Promise.all([loadVariantOptions(), loadFournisseurOptions()])
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
