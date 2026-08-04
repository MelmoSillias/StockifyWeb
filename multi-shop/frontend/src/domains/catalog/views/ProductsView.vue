<template>
  <section class="dashboard-page">
    <AppFiltersCard>
      <div>
        <label class="filter-label" for="product-search">Recherche</label>
            <IconField>
              <InputIcon class="pi pi-search" />
              <InputText
                id="product-search"
                v-model="searchTerm"
                placeholder="Rechercher un produit..."
                fluid
              />
            </IconField>
          </div>
          <div>
            <label class="filter-label" for="category-filter">Catégorie</label>
            <Select
              id="category-filter"
              v-model="categoryFilter"
              :options="categoryFilterOptions"
              option-label="label"
              option-value="value"
              placeholder="Toutes les catégories"
              filter
              fluid
              show-clear
            />
          </div>
          <div class="filter-toggle">
            <label class="filter-label" for="low-stock-filter">Stock faible</label>
            <div class="filter-toggle__row">
              <ToggleSwitch
                id="low-stock-filter"
                v-model="lowStockOnly"
                v-tooltip.top="'Afficher uniquement les produits en alerte'"
              />
              <span class="filter-toggle__hint">Afficher uniquement les produits en alerte</span>
            </div>
          </div>
    </AppFiltersCard>

    <Card class="dashboard-panel">
      <template #title>
        <AppTablePanelHeader
          title="Produits"
          :count-label="`${filteredProducts.length} produit(s)`"
          create-label="Nouveau produit"
          hide-create-on-mobile
          :reloading="productsStore.loading"
          @create="openCreateProduct()"
          @reload="load"
        >
          <template #actions>
            <AppTablePrintExportBar table-type="products" />
          </template>
        </AppTablePanelHeader>
      </template>
      <template #content>
        <AppTableState
          :loading="productsStore.loading"
          :error="productsStore.error"
          :is-empty="!productsStore.loading && filteredProducts.length === 0"
          empty-title="Aucun produit"
          empty-text="Créez un produit pour alimenter votre catalogue."
          @retry="load"
        >
          <DataTable
            v-model:expandedRows="expandedRows"
            v-model:first="first"
            :value="filteredProducts"
            data-key="id"
            striped-rows
            :responsive-layout="tableLayout"
            :row-class="productRowClass"
            paginator
            :rows="rows"
            :rows-per-page-options="rowsPerPageOptions"
            paginator-template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown CurrentPageReport"
            current-page-report-template="{first}-{last} sur {totalRecords}"
            @page="onPage"
          >
            <Column expander style="width: 3rem" />
            <Column header="Nom">
              <template #body="{ data }">
                <div class="product-name-cell">
                  <span>{{ data.name }}</span>
                  <i
                    v-if="data.has_low_stock"
                    class="pi pi-exclamation-circle product-alert-icon"
                    v-tooltip.top="'Variante(s) en stock faible'"
                  />
                </div>
              </template>
            </Column>
            <Column v-if="!isMobile" field="reference" header="Référence">
              <template #body="{ data }">
                <small>{{ '#' + data.reference || '—' }}</small>
              </template>
            </Column>
            <Column v-if="!isMobile" header="Catégorie">
              <template #body="{ data }">
                <Tag :value="data.category_name || '—'" severity="secondary" rounded />
              </template>
            </Column>
            <Column v-if="!isMobile" header="Variantes" style="width: 110px">
              <template #body="{ data }">
                <Tag :value="String(data.variant_count ?? data.variants?.length ?? 0)" severity="secondary" rounded />
              </template>
            </Column>
            <Column header="Actions" style="width: 140px">
              <template #body="{ data }">
                <AppTableActionsMenu
                  :actions="productRowActions(data)"
                  aria-label="Actions produit"
                />
              </template>
            </Column>
            <template #expansion="{ data }">
              <ProductVariantsPanel
                :product="data"
                :variants="data.variants || []"
                :units="unitsStore.items"
                @add-variant="openAddVariant"
                @view-stock="openStockDialog"
                @receive="openReceive"
                @stock-out="openStockOut"
                @adjust="openAdjust"
                @edit-variant="openEditVariant"
                @delete-variant="confirmDeleteVariant"
              />
            </template>
          </DataTable>
        </AppTableState>
      </template>
    </Card>

    <Teleport to="body">
      <Button
        class="products-fab"
        icon="pi pi-plus" 
        aria-label="Nouveau produit"
        v-tooltip.left="'Nouveau produit'"
        @click="openCreateProduct()"
      />
    </Teleport>

    <CreateProductDialog
      ref="createProductDialogRef"
      v-model:visible="createProductVisible"
      :category-options="categoryOptions"
      :unit-options="unitOptions"
      :loading="productsStore.submitting"
      :general-error="productsStore.error"
      @submit="submitCreateProduct"
    />

    <AppCrudDialog
      :visible="productDialog.visible"
      :model-value="productDialog.formData"
      title="Modifier produit"
      subtitle="Informations du produit."
      :fields="productEditFields"
      :loading="productsStore.submitting"
      :general-error="productsStore.error"
      @update:visible="productDialog.visible = $event"
      @update:model-value="productDialog.formData = $event"
      @submit="saveProduct"
    />

    <AppCrudDialog
      :visible="variantDialog.visible"
      :model-value="variantDialog.formData"
      :title="variantDialog.mode === 'create' ? 'Nouvelle variante' : 'Modifier variante'"
      :subtitle="
        variantDialog.mode === 'create'
          ? 'Format de vente — le nom est généré selon l’unité et le mode.'
          : 'Prix et seuil d’alerte.'
      "
      :fields="variantFormFields"
      :loading="variantsStore.submitting"
      :general-error="variantsStore.error"
      @update:visible="variantDialog.visible = $event"
      @update:model-value="variantDialog.formData = $event"
      @submit="saveVariant"
    />

    <AppCrudDialog
      :visible="receiveDialog.visible"
      :model-value="receiveDialog.formData"
      title="Réception de lot"
      subtitle="Entrée de stock pour cette variante."
      :fields="receiveFields"
      :loading="inventoryStore.submitting"
      :general-error="inventoryStore.error"
      @update:visible="receiveDialog.visible = $event"
      @update:model-value="receiveDialog.formData = $event"
      @submit="submitReceive"
    />

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
      subtitle="Choisissez le lot à corriger, puis la quantité."
      :fields="adjustFields"
      :loading="inventoryStore.submitting || adjustLotsLoading"
      :general-error="inventoryStore.error || adjustLotsError"
      @update:visible="adjustDialog.visible = $event"
      @update:model-value="adjustDialog.formData = $event"
      @submit="submitAdjust"
    />

    <VariantStockDialog
      v-model:visible="stockDialogVisible"
      :variant-id="stockDialogVariant?.id"
      :variant-label="stockDialogLabel"
    />
  </section>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useRoute } from 'vue-router'

import Button from 'primevue/button'
import Card from 'primevue/card'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Tag from 'primevue/tag'
import ToggleSwitch from 'primevue/toggleswitch'

import ProductVariantsPanel from '@/domains/catalog/components/ProductVariantsPanel.vue'
import CreateProductDialog from '@/domains/catalog/components/CreateProductDialog.vue'
import VariantStockDialog from '@/domains/catalog/components/VariantStockDialog.vue'
import AppFiltersCard from '@/domains/shared/components/AppFiltersCard.vue'
import AppCrudDialog from '@/domains/shared/components/AppCrudDialog.vue'
import AppTablePanelHeader from '@/domains/shared/components/AppTablePanelHeader.vue'
import AppTablePrintExportBar from '@/domains/impression/components/AppTablePrintExportBar.vue'
import AppTableActionsMenu from '@/domains/shared/components/AppTableActionsMenu.vue'
import AppTableState from '@/domains/shared/components/AppTableState.vue'
import { useCrudDialog } from '@/domains/shared/composables/useCrudDialog'
import { useDisplayFormatters } from '@/domains/shared/composables/useDisplayFormatters'
import { useEntityActions } from '@/domains/shared/composables/useEntityActions'
import { useCategoriesStore } from '@/domains/catalog/stores/categories'
import { useProductsStore } from '@/domains/catalog/stores/products'
import { useUnitsStore } from '@/domains/catalog/stores/units'
import { useVariantsStore } from '@/domains/catalog/stores/variants'
import { useInventoryStore } from '@/domains/inventory/stores/inventory'
import { useFournisseurOptions } from '@/domains/fournisseur/composables/useFournisseurOptions'
import { inventoryService } from '@/domains/inventory/services/inventoryService'
import { variantsService } from '@/domains/catalog/services/variantsService'
import { useLayoutStore } from '@/domains/layout/stores/layout'
import { useBreakpoint } from '@/domains/layout/composables/useBreakpoint'
import {
  buildVariantSku,
  saleModeOptions
} from '@/domains/catalog/utils/variantLabel'

const HIGHLIGHT_DURATION_MS = 3000

const route = useRoute()
const productsStore = useProductsStore()
const categoriesStore = useCategoriesStore()
const unitsStore = useUnitsStore()
const variantsStore = useVariantsStore()
const inventoryStore = useInventoryStore()
const { options: fournisseurOptions, load: loadFournisseurOptions } = useFournisseurOptions()
const layoutStore = useLayoutStore()
const { motionPreset } = storeToRefs(layoutStore)
const { isMobile } = useBreakpoint()
const tableLayout = computed(() => (isMobile.value ? 'stack' : 'scroll'))
const { showSuccess, showError, confirmRemoval } = useEntityActions()
const { formatCompactNumber } = useDisplayFormatters()

const rowsPerPageOptions = [10, 20, 50, 100]
const first = ref(0)
const rows = ref(10)

const searchTerm = ref('')
const categoryFilter = ref(null)
const lowStockOnly = ref(false)
const expandedRows = ref({})
const activeProductId = ref(null)

const onPage = (event) => {
  first.value = event.first
  rows.value = event.rows
}
const activeVariant = ref(null)
const stockDialogVisible = ref(false)
const stockDialogVariant = ref(null)
const createProductVisible = ref(false)
const createProductDialogRef = ref(null)
const highlightedProductId = ref(null)
const adjustLots = ref([])
const adjustLotsLoading = ref(false)
const adjustLotsError = ref('')
let highlightTimer = null

const createProductForm = () => ({
  id: null,
  name: '',
  reference: '',
  description: '',
  category_id: null
})

const createVariantForm = () => ({
  id: null,
  unit_of_measure_id: null,
  sale_mode: 'unit',
  default_price: '',
  alert_threshold: ''
})

const productDialog = useCrudDialog(createProductForm)
const variantDialog = useCrudDialog(createVariantForm)
const receiveDialog = useCrudDialog(() => ({
  quantity: '',
  unit_cost: '',
  reference: '',
  fournisseur_id: null,
  expiry_date: null
}))
const stockOutDialog = useCrudDialog(() => ({
  quantity: '',
  type: 'sale',
  reason: ''
}))
const adjustDialog = useCrudDialog(() => ({
  lot_id: null,
  quantity: '',
  direction: 'in',
  reason: ''
}))

const categoryFilterOptions = computed(() => [
  { label: 'Toutes les catégories', value: null },
  ...categoriesStore.items.map((category) => ({
    label: category.name,
    value: category.id
  }))
])

const categoryOptions = computed(() => [
  { label: 'Aucune', value: null },
  ...categoriesStore.items.map((category) => ({
    label: category.name,
    value: category.id
  }))
])

const unitOptions = computed(() =>
  unitsStore.items.map((unit) => ({
    label: `${unit.label} (${unit.code})`,
    value: unit.id
  }))
)

const productEditFields = computed(() => [
  { name: 'name', label: 'Nom', type: 'text', placeholder: 'Ex: Eau minérale Cristal', icon: 'pi pi-tag' },
  { name: 'reference', label: 'Référence', type: 'text', placeholder: 'Ex: EAU', icon: 'pi pi-barcode' },
  { name: 'description', label: 'Description', type: 'textarea', placeholder: 'Description du produit', icon: 'pi pi-align-left' },
  {
    name: 'category_id',
    label: 'Catégorie',
    type: 'select',
    options: categoryOptions.value,
    optionLabel: 'label',
    optionValue: 'value',
    placeholder: 'Sélectionner',
    icon: 'pi pi-sitemap'
  }
])

const variantFormFields = computed(() => {
  if (variantDialog.mode === 'edit') {
    return [
      { name: 'default_price', label: 'Prix par défaut', type: 'text', placeholder: 'Ex: 500', icon: 'pi pi-money-bill' },
      { name: 'alert_threshold', label: 'Seuil alerte', type: 'text', placeholder: 'Ex: 10', icon: 'pi pi-exclamation-triangle' }
    ]
  }

  return [
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
    { name: 'default_price', label: 'Prix par défaut', type: 'text', placeholder: 'Ex: 500', icon: 'pi pi-money-bill' },
    { name: 'alert_threshold', label: 'Seuil alerte', type: 'text', placeholder: 'Ex: 10', icon: 'pi pi-exclamation-triangle' }
  ]
})

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

const adjustLotOptions = computed(() => {
  const direction = adjustDialog.formData.direction
  const lots =
    direction === 'out'
      ? adjustLots.value.filter((lot) => Number(lot.quantity_remaining) > 0)
      : adjustLots.value

  return lots.map((lot) => ({
    label: `${lot.reference || 'Lot'} — reste ${formatCompactNumber(lot.quantity_remaining)}`,
    value: lot.id
  }))
})

const adjustFields = computed(() => [
  {
    name: 'lot_id',
    label: 'Lot',
    type: 'select',
    options: adjustLotOptions.value,
    optionLabel: 'label',
    optionValue: 'value',
    placeholder: adjustLotsLoading.value ? 'Chargement des lots…' : 'Sélectionner un lot',
    icon: 'pi pi-inbox',
    helpText: adjustLotOptions.value.length === 0 && !adjustLotsLoading.value
      ? 'Aucun lot disponible pour cette correction.'
      : ''
  },
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
])

const stockDialogLabel = computed(() => {
  const variant = stockDialogVariant.value
  if (!variant) return ''
  if (variant.unit_label) return `${variant.unit_label} · ${variant.sale_mode}`
  return variant.sku || 'Variante'
})

const filteredProducts = computed(() => {
  const query = searchTerm.value.trim().toLowerCase()

  const products = productsStore.items.filter((product) => {
    if (categoryFilter.value && product.category_id !== categoryFilter.value) {
      return false
    }

    if (lowStockOnly.value && !product.has_low_stock) {
      return false
    }

    if (!query) {
      return true
    }

    return [product.name, product.reference, product.category_name].some((value) =>
      String(value || '')
        .toLowerCase()
        .includes(query)
    )
  })

  const pinnedId = highlightedProductId.value
  if (!pinnedId) {
    return products
  }

  const pinnedIndex = products.findIndex((product) => product.id === pinnedId)
  if (pinnedIndex <= 0) {
    return products
  }

  const pinned = products[pinnedIndex]
  return [pinned, ...products.slice(0, pinnedIndex), ...products.slice(pinnedIndex + 1)]
})

const productRowClass = (data) => {
  if (!data?.id || data.id !== highlightedProductId.value) {
    return ''
  }

  if (motionPreset.value === 'reduced') {
    return 'product-row--highlight product-row--highlight-static'
  }

  return motionPreset.value === 'calm'
    ? 'product-row--highlight product-row--highlight-calm'
    : 'product-row--highlight product-row--highlight-expressive'
}

const clearProductHighlight = () => {
  if (highlightTimer) {
    clearTimeout(highlightTimer)
    highlightTimer = null
  }
  highlightedProductId.value = null
}

const highlightCreatedProduct = (productId) => {
  clearProductHighlight()
  first.value = 0
  highlightedProductId.value = productId
  highlightTimer = setTimeout(() => {
    highlightedProductId.value = null
    highlightTimer = null
  }, HIGHLIGHT_DURATION_MS)
}

const unitCodeFor = (unitId) => {
  return unitsStore.items.find((unit) => unit.id === unitId)?.code || 'unit'
}

const resolveSku = (productLike, form) => {
  return buildVariantSku({
    reference: productLike.reference,
    name: productLike.name,
    unitCode: unitCodeFor(form.unit_of_measure_id),
    saleMode: form.sale_mode
  })
}

const reloadCatalog = async () => {
  await productsStore.fetchAll()
}

const openCreateProduct = () => {
  createProductVisible.value = true
}

const openEditProduct = (product) => {
  productDialog.openEdit({
    ...createProductForm(),
    id: product.id,
    name: product.name,
    reference: product.reference || '',
    description: product.description || '',
    category_id: product.category_id
  })
}

const submitCreateProduct = async (form) => {
  try {
    const hasVariant = Boolean(form.unit_of_measure_id)
    const hasLots = Array.isArray(form.lots) && form.lots.length > 0

    if (hasLots && !hasVariant) {
      showError('Renseignez la variante avant d’ajouter des lots.')
      return
    }

    const payload = {
      name: form.name,
      reference: form.reference,
      description: form.description,
      category_id: form.category_id
    }

    if (hasVariant) {
      payload.sku = resolveSku(
        { name: form.name, reference: form.reference },
        form
      )
      payload.unit_of_measure_id = form.unit_of_measure_id
      payload.sale_mode = form.sale_mode
      payload.default_price = form.default_price
      payload.alert_threshold = form.alert_threshold
    }

    if (hasLots) {
      payload.lots = form.lots
    }

    const product = await productsStore.createItem(payload)
    await reloadCatalog()
    highlightCreatedProduct(product.id)

    if (hasVariant && hasLots) {
      showSuccess('Produit, variante et lots créés.')
    } else if (hasVariant) {
      showSuccess('Produit et variante créés.')
    } else {
      showSuccess('Produit créé.')
    }

    createProductDialogRef.value?.clearDraft()
    createProductVisible.value = false
  } catch (error) {
    showError(error?.message || "Impossible d'enregistrer le produit.")
  }
}

const saveProduct = async () => {
  try {
    await productsStore.saveItem(productDialog.formData)
    await reloadCatalog()
    showSuccess('Produit enregistré.')
    productDialog.close()
  } catch (error) {
    showError(error?.message || "Impossible d'enregistrer le produit.")
  }
}

const productRowActions = (product) => [
  {
    label: 'Modifier',
    icon: 'pi pi-pencil',
    command: () => openEditProduct(product)
  },
  {
    label: 'Supprimer',
    icon: 'pi pi-trash',
    severity: 'danger',
    loading: productsStore.isDeleting(product.id),
    command: () => confirmDeleteProduct(product)
  }
]

const confirmDeleteProduct = (product) => {
  confirmRemoval({
    header: 'Supprimer ce produit ?',
    message: `Le produit ${product.name} sera supprimé définitivement.`,
    onAccept: async () => {
      await productsStore.removeItem(product.id)
      showSuccess('Produit supprimé.')
    }
  })
}

const openAddVariant = (product) => {
  activeProductId.value = product.id
  variantDialog.openCreate()
}

const openEditVariant = (variant) => {
  activeProductId.value = variant.product_id
  activeVariant.value = variant
  variantDialog.openEdit({
    ...createVariantForm(),
    id: variant.id,
    unit_of_measure_id: variant.unit_of_measure_id,
    sale_mode: variant.sale_mode,
    default_price: variant.default_price ?? '',
    alert_threshold: variant.alert_threshold ?? ''
  })
}

const saveVariant = async () => {
  try {
    const form = variantDialog.formData
    const productId = activeProductId.value
    const product = productsStore.items.find((entry) => entry.id === productId)

    if (variantDialog.mode === 'create') {
      if (!form.unit_of_measure_id) {
        showError('Choisissez une unité.')
        return
      }

      await variantsService.create(productId, {
        sku: resolveSku(product || { name: 'PROD' }, form),
        unit_of_measure_id: form.unit_of_measure_id,
        sale_mode: form.sale_mode,
        default_price: form.default_price,
        alert_threshold: form.alert_threshold
      })
    } else {
      await variantsService.update(form.id, {
        default_price: form.default_price,
        alert_threshold: form.alert_threshold
      })
    }

    await reloadCatalog()
    showSuccess('Variante enregistrée.')
    variantDialog.close()
  } catch (error) {
    showError(error?.message || "Impossible d'enregistrer la variante.")
  }
}

const confirmDeleteVariant = (variant) => {
  const label = variant.unit_label
    ? `${variant.unit_label} · ${variant.sale_mode}`
    : variant.sku
  confirmRemoval({
    header: 'Supprimer cette variante ?',
    message: `La variante ${label} sera supprimée.`,
    onAccept: async () => {
      await variantsService.remove(variant.id)
      await reloadCatalog()
      showSuccess('Variante supprimée.')
    }
  })
}

const openStockDialog = (variant) => {
  stockDialogVariant.value = variant
  stockDialogVisible.value = true
}

const openReceive = (variant) => {
  activeVariant.value = variant
  receiveDialog.openCreate()
}

const openStockOut = (variant) => {
  activeVariant.value = variant
  stockOutDialog.openCreate()
}

const openAdjust = async (variant) => {
  activeVariant.value = variant
  adjustLots.value = []
  adjustLotsError.value = ''
  adjustDialog.openCreate()
  adjustLotsLoading.value = true

  try {
    const lots = await inventoryService.listLots(variant.id)
    adjustLots.value = lots

    if (lots.length === 0) {
      adjustLotsError.value = 'Aucun lot disponible. Réceptionnez du stock avant de corriger.'
      return
    }

    adjustDialog.formData = {
      ...adjustDialog.formData,
      lot_id: lots[0].id
    }
  } catch (error) {
    adjustLotsError.value = error?.message || 'Impossible de charger les lots.'
  } finally {
    adjustLotsLoading.value = false
  }
}

const submitReceive = async () => {
  if (!activeVariant.value) return

  try {
    const payload = {
      ...receiveDialog.formData,
      expiry_date: receiveDialog.formData.expiry_date
        ? new Date(receiveDialog.formData.expiry_date).toISOString().slice(0, 10)
        : null
    }
    await inventoryStore.receiveLot(activeVariant.value.id, payload)
    await reloadCatalog()
    showSuccess('Lot reçu avec succès.')
    receiveDialog.close()
  } catch (error) {
    showError(error?.message || 'Impossible de recevoir le lot.')
  }
}

const submitStockOut = async () => {
  if (!activeVariant.value) return

  try {
    await inventoryStore.stockOut(activeVariant.value.id, stockOutDialog.formData)
    await reloadCatalog()
    showSuccess('Sortie de stock enregistrée.')
    stockOutDialog.close()
  } catch (error) {
    showError(error?.message || 'Stock insuffisant ou erreur lors de la sortie.')
  }
}

const submitAdjust = async () => {
  if (!activeVariant.value) return

  const { lot_id: lotId, quantity, direction, reason } = adjustDialog.formData
  if (!lotId) {
    showError('Sélectionnez un lot.')
    return
  }

  if (!quantity || Number(quantity) <= 0) {
    showError('Indiquez une quantité valide.')
    return
  }

  try {
    await inventoryStore.adjust(activeVariant.value.id, {
      lot_id: lotId,
      quantity: String(quantity),
      direction,
      reason
    })
    await reloadCatalog()
    showSuccess('Ajustement enregistré.')
    adjustDialog.close()
  } catch (error) {
    showError(error?.message || "Impossible d'ajuster le stock.")
  }
}

watch(
  () => adjustDialog.formData.direction,
  () => {
    const selectedId = adjustDialog.formData.lot_id
    const options = adjustLotOptions.value
    if (!options.some((option) => option.value === selectedId)) {
      adjustDialog.formData = {
        ...adjustDialog.formData,
        lot_id: options[0]?.value ?? null
      }
    }
  }
)

watch(
  () => [searchTerm.value, categoryFilter.value, lowStockOnly.value],
  () => {
    first.value = 0
  }
)

watch(
  () => route.query.category,
  (categoryId) => {
    if (categoryId) {
      categoryFilter.value = String(categoryId)
    }
  },
  { immediate: true }
)

const load = async () => {
  try {
    await Promise.all([
      categoriesStore.fetchAll(),
      productsStore.fetchAll(),
      unitsStore.fetchAll(),
      loadFournisseurOptions()
    ])
  } catch (error) {
    showError(error?.message || 'Impossible de charger les produits.')
  }
}

onMounted(load)

onBeforeUnmount(() => {
  clearProductHighlight()
})
</script>

<style scoped>
.filter-toggle__row {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  min-height: 2.5rem;
}

.filter-toggle__hint {
  font-size: 0.85rem;
  color: var(--p-text-muted-color);
}

.filter-count {
  display: flex;
  align-items: center;
  min-height: 2.5rem;
}

.products-fab {
  position: fixed !important;
  right: max(1.25rem, env(safe-area-inset-right, 0px));
  bottom: max(1.25rem, env(safe-area-inset-bottom, 0px));
  z-index: 1100;
  width: 3.5rem;
  height: 3.5rem;
  box-shadow: var(--layout-shadow-soft, 0 10px 30px rgba(0, 0, 0, 0.18));
}

@media (max-width: 768px) {
  .filter-grid {
    grid-template-columns: 1fr;
  }

  .products-fab {
    right: max(1rem, env(safe-area-inset-right, 0px));
    bottom: max(1rem, env(safe-area-inset-bottom, 0px));
  }

  .dashboard-page {
    padding-bottom: calc(4.5rem + env(safe-area-inset-bottom, 0px));
  }
}

@media (max-width: 479px) {
  .filter-toggle__hint {
    display: none;
  }
}

.product-name-cell {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
}

.product-alert-icon {
  color: var(--p-red-500, #ef4444);
}

.actions-cell {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

:deep(tr.product-row--highlight > td) {
  background: color-mix(in srgb, var(--p-primary-color) 16%, transparent);
}

:deep(tr.product-row--highlight-static > td) {
  background: color-mix(in srgb, var(--p-primary-color) 12%, transparent);
}

:deep(tr.product-row--highlight-calm > td) {
  animation: product-row-highlight-calm 3s ease forwards;
}

:deep(tr.product-row--highlight-expressive > td) {
  animation: product-row-highlight-expressive 3s cubic-bezier(0.4, 0, 0.2, 1) forwards;
}

@keyframes product-row-highlight-calm {
  0%,
  70% {
    background: color-mix(in srgb, var(--p-primary-color) 14%, transparent);
  }
  100% {
    background: transparent;
  }
}

@keyframes product-row-highlight-expressive {
  0% {
    background: color-mix(in srgb, var(--p-primary-color) 28%, transparent);
  }
  55% {
    background: color-mix(in srgb, var(--p-primary-color) 16%, transparent);
  }
  100% {
    background: transparent;
  }
}

@media (prefers-reduced-motion: reduce) {
  :deep(tr.product-row--highlight > td) {
    animation: none !important;
    background: color-mix(in srgb, var(--p-primary-color) 12%, transparent);
  }
}
</style>
