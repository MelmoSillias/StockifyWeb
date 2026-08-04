<template>
  <section class="dashboard-page">
    <AppFiltersCard>
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
    </AppFiltersCard>

    <Card class="dashboard-panel">
      <template #title>
        <AppTablePanelHeader
          title="Variantes"
          :count-label="`${filteredVariants.length} variante(s)`"
          create-label="Nouvelle variante"
          :show-create="Boolean(selectedProductId)"
          :search-term="searchTerm"
          search-placeholder="Rechercher une variante..."
          show-search
          :reloading="variantsStore.loading"
          @update:search-term="searchTerm = $event"
          @create="dialog.openCreate()"
          @reload="loadVariants(selectedProductId)"
        />
      </template>
      <template #content>
        <AppTableState
          :loading="variantsStore.loading"
          :error="variantsStore.error"
          :is-empty="!variantsStore.loading && !selectedProductId"
          empty-title="Sélectionnez un produit"
          empty-text="Choisissez un produit pour afficher et gérer ses variantes."
          @retry="loadVariants(selectedProductId)"
        >
          <DataTable
            v-if="selectedProductId"
            :value="filteredVariants"
            data-key="id"
            striped-rows
            :responsive-layout="tableLayout"
          >
            <Column v-if="!isMobile" field="sku" header="SKU" />
            <Column header="Unité">
              <template #body="{ data }">
                {{ unitLabel(data.unit_of_measure_id) }}
              </template>
            </Column>
            <Column v-if="!isMobile" field="sale_mode" header="Mode vente" />
            <Column header="Prix">
              <template #body="{ data }">
                {{ formatCompactNumber(data.default_price) }}
              </template>
            </Column>
            <Column v-if="!isMobile" header="Seuil alerte">
              <template #body="{ data }">
                {{ formatCompactNumber(data.alert_threshold) }}
              </template>
            </Column>
            <Column header="Actions" style="width: 90px">
              <template #body="{ data }">
                <AppTableActionsMenu
                  :actions="variantRowActions(data)"
                  aria-label="Actions variante"
                />
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

import AppFiltersCard from '@/domains/shared/components/AppFiltersCard.vue'
import AppCrudDialog from '@/domains/shared/components/AppCrudDialog.vue'
import AppTableActionsMenu from '@/domains/shared/components/AppTableActionsMenu.vue'
import AppTablePanelHeader from '@/domains/shared/components/AppTablePanelHeader.vue'
import AppTableState from '@/domains/shared/components/AppTableState.vue'
import { useBreakpoint } from '@/domains/layout/composables/useBreakpoint'
import { useCrudDialog } from '@/domains/shared/composables/useCrudDialog'
import { useDisplayFormatters } from '@/domains/shared/composables/useDisplayFormatters'
import { useEntityActions } from '@/domains/shared/composables/useEntityActions'
import { useProductsStore } from '@/domains/catalog/stores/products'
import { useUnitsStore } from '@/domains/catalog/stores/units'
import { useVariantsStore } from '@/domains/catalog/stores/variants'

const productsStore = useProductsStore()
const unitsStore = useUnitsStore()
const variantsStore = useVariantsStore()
const { isMobile } = useBreakpoint()
const tableLayout = computed(() => (isMobile.value ? 'stack' : 'scroll'))
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

const variantRowActions = (variant) => [
  {
    label: 'Modifier',
    icon: 'pi pi-pencil',
    command: () => dialog.openEdit(normalizeForEdit(variant))
  },
  {
    label: 'Supprimer',
    icon: 'pi pi-trash',
    severity: 'danger',
    loading: variantsStore.isDeleting(variant.id),
    command: () => confirmDelete(variant)
  }
]

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
