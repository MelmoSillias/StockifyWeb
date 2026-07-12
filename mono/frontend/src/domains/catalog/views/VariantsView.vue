<template>
  <section class="dashboard-page">
    <div class="dashboard-hero dashboard-hero--compact">
      <div>
        <p class="dashboard-eyebrow">Catalogue</p>
        <h1 class="dashboard-title">Variantes</h1>
        <p class="dashboard-description">Formats de vente, prix et seuils d'alerte.</p>
      </div>
    </div>

    <Card class="dashboard-panel filter-card">
      <template #content>
        <label class="filter-label" for="product-select">Produit</label>
        <Select
          id="product-select"
          v-model="selectedProductId"
          :options="productOptions"
          option-label="label"
          option-value="value"
          placeholder="Sélectionner un produit"
          filter
          fluid
          @update:model-value="loadVariants"
        />
      </template>
    </Card>

    <AppEntityToolbar
      :search-term="searchTerm"
      search-placeholder="Rechercher une variante..."
      create-label="Nouvelle variante"
      :count-label="`${filteredVariants.length} variante(s)`"
      :show-create="Boolean(selectedProductId)"
      @update:search-term="searchTerm = $event"
      @create="dialog.openCreate()"
    />

    <Card class="dashboard-panel">
      <template #content>
        <AppTableState
          :loading="variantsStore.loading"
          :is-empty="!variantsStore.loading && !selectedProductId"
          empty-title="Sélectionnez un produit"
          empty-text="Choisissez un produit pour afficher et gérer ses variantes."
        >
          <DataTable
            v-if="selectedProductId"
            :value="filteredVariants"
            data-key="id"
            striped-rows
            responsive-layout="scroll"
          >
            <Column field="sku" header="SKU" />
            <Column header="Unité">
              <template #body="{ data }">
                {{ unitLabel(data.unit_of_measure_id) }}
              </template>
            </Column>
            <Column field="sale_mode" header="Mode vente" />
            <Column header="Prix">
              <template #body="{ data }">
                {{ formatCompactNumber(data.default_price) }}
              </template>
            </Column>
            <Column header="Seuil alerte">
              <template #body="{ data }">
                {{ formatCompactNumber(data.alert_threshold) }}
              </template>
            </Column>
            <Column header="Actions" style="width: 170px">
              <template #body="{ data }">
                <div class="actions-cell">
                  <Button icon="pi pi-pencil" text rounded @click="dialog.openEdit(normalizeForEdit(data))" />
                  <Button
                    icon="pi pi-trash"
                    text
                    rounded
                    severity="danger"
                    :loading="variantsStore.isDeleting(data.id)"
                    @click="confirmDelete(data)"
                  />
                </div>
              </template>
            </Column>
          </DataTable>
        </AppTableState>
      </template>
    </Card>

    <AppCrudDialog
      :visible="dialog.visible"
      :model-value="dialog.formData"
      :title="dialog.mode === 'create' ? 'Nouvelle variante' : 'Modifier variante'"
      subtitle="Référence, unité de vente et paramètres associés."
      :fields="formFields"
      :loading="variantsStore.submitting"
      :general-error="variantsStore.error"
      @update:visible="dialog.visible = $event"
      @update:model-value="dialog.formData = $event"
      @submit="saveVariant"
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
import AppEntityToolbar from '@/domains/shared/components/AppEntityToolbar.vue'
import AppTableState from '@/domains/shared/components/AppTableState.vue'
import { useCrudDialog } from '@/domains/shared/composables/useCrudDialog'
import { useDisplayFormatters } from '@/domains/shared/composables/useDisplayFormatters'
import { useEntityActions } from '@/domains/shared/composables/useEntityActions'
import { useProductsStore } from '@/domains/catalog/stores/products'
import { useUnitsStore } from '@/domains/catalog/stores/units'
import { useVariantsStore } from '@/domains/catalog/stores/variants'

const productsStore = useProductsStore()
const unitsStore = useUnitsStore()
const variantsStore = useVariantsStore()
const { showSuccess, showError, confirmRemoval } = useEntityActions()
const { formatCompactNumber } = useDisplayFormatters()

const searchTerm = ref('')
const selectedProductId = ref(null)

const saleModeOptions = [
  { label: 'Unité', value: 'unit' },
  { label: 'Poids', value: 'weight' },
  { label: 'Volume', value: 'volume' },
  { label: 'Lot', value: 'bundle' }
]

const createEmptyForm = () => ({
  id: null,
  sku: '',
  unit_of_measure_id: null,
  sale_mode: 'unit',
  default_price: '',
  alert_threshold: ''
})

const dialog = useCrudDialog(createEmptyForm)

const productOptions = computed(() => productsStore.items.map((product) => ({
  label: product.name,
  value: product.id
})))

const unitOptions = computed(() => unitsStore.items.map((unit) => ({
  label: `${unit.code} — ${unit.label}`,
  value: unit.id
})))

const formFields = computed(() => [
  { name: 'sku', label: 'SKU', type: 'text', placeholder: 'Ex: CAFE-250G', icon: 'pi pi-barcode' },
  {
    name: 'unit_of_measure_id',
    label: 'Unité',
    type: 'select',
    options: unitOptions.value,
    optionLabel: 'label',
    optionValue: 'value',
    placeholder: 'Sélectionner',
    icon: 'pi pi-sliders-h'
  },
  {
    name: 'sale_mode',
    label: 'Mode de vente',
    type: 'select',
    options: saleModeOptions,
    optionLabel: 'label',
    optionValue: 'value',
    icon: 'pi pi-shopping-cart'
  },
  { name: 'default_price', label: 'Prix par défaut', type: 'text', placeholder: 'Ex: 12.50', icon: 'pi pi-money-bill' },
  { name: 'alert_threshold', label: 'Seuil alerte', type: 'text', placeholder: 'Ex: 5', icon: 'pi pi-exclamation-triangle' }
])

const filteredVariants = computed(() => {
  const query = searchTerm.value.trim().toLowerCase()
  if (!query) {
    return variantsStore.items
  }

  return variantsStore.items.filter((variant) => {
    return [variant.sku, variant.sale_mode].some((value) => String(value || '').toLowerCase().includes(query))
  })
})

const unitLabel = (unitId) => {
  const unit = unitsStore.items.find((entry) => entry.id === unitId)
  return unit ? `${unit.code} — ${unit.label}` : '—'
}

const normalizeForEdit = (variant) => ({
  ...createEmptyForm(),
  ...variant
})

const loadVariants = async (productId) => {
  if (!productId) {
    variantsStore.items = []
    return
  }

  try {
    await variantsStore.fetchForProduct(productId)
  } catch (error) {
    showError(error?.message || 'Impossible de charger les variantes.')
  }
}

const saveVariant = async () => {
  try {
    await variantsStore.saveItem(dialog.formData)
    showSuccess('Variante enregistrée.')
    dialog.close()
  } catch (error) {
    showError(error?.message || "Impossible d'enregistrer la variante.")
  }
}

const confirmDelete = (variant) => {
  confirmRemoval({
    header: 'Supprimer cette variante ?',
    message: `La variante ${variant.sku} sera supprimée définitivement.`,
    onAccept: async () => {
      await variantsStore.removeItem(variant.id)
      showSuccess('Variante supprimée.')
    }
  })
}

onMounted(async () => {
  try {
    await Promise.all([productsStore.fetchAll(), unitsStore.fetchAll()])
    if (productsStore.items.length) {
      selectedProductId.value = productsStore.items[0].id
      await loadVariants(selectedProductId.value)
    }
  } catch (error) {
    showError(error?.message || 'Impossible de charger le catalogue.')
  }
})
</script>

<style scoped>
.filter-card {
  margin-bottom: 1rem;
}

.filter-label {
  display: block;
  margin-bottom: 0.5rem;
  color: var(--p-text-muted-color);
  font-size: 0.9rem;
}

.actions-cell {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}
</style>
