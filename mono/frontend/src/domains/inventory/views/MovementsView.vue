<template>
  <section class="dashboard-page">
    <div class="dashboard-hero dashboard-hero--compact">
      <div>
        <p class="dashboard-eyebrow">Catalogue</p>
        <h1 class="dashboard-title">Mouvements</h1>
        <p class="dashboard-description">Historique des entrées et sorties de stock.</p>
      </div>
    </div>

    <Card class="dashboard-panel filter-card">
      <template #content>
        <div class="filter-grid">
          <div>
            <label class="filter-label" for="category-filter">Catégorie</label>
            <Select
              id="category-filter"
              v-model="filters.categoryId"
              :options="categoryOptions"
              option-label="label"
              option-value="value"
              placeholder="Toutes"
              filter
              fluid
              show-clear
            />
          </div>
          <div>
            <label class="filter-label" for="product-filter">Produit</label>
            <Select
              id="product-filter"
              v-model="filters.productId"
              :options="productOptions"
              option-label="label"
              option-value="value"
              placeholder="Tous"
              filter
              fluid
              show-clear
            />
          </div>
          <div>
            <label class="filter-label" for="variant-filter">Variante</label>
            <Select
              id="variant-filter"
              v-model="filters.variantId"
              :options="variantFilterOptions"
              option-label="label"
              option-value="value"
              placeholder="Toutes"
              filter
              fluid
              show-clear
              :loading="variantsLoading"
              @update:model-value="loadMovements"
            />
          </div>
          <div>
            <label class="filter-label" for="date-range">Période</label>
            <DatePicker
              id="date-range"
              v-model="filters.dateRange"
              selection-mode="range"
              date-format="dd/mm/yy"
              show-icon
              fluid
              hide-on-range-selection
              placeholder="Choisir une période"
            />
          </div>
        </div>
      </template>
    </Card>

    <Card class="dashboard-panel">
      <template #content>
        <div class="movements-toolbar">
          <Tag :value="`${filteredMovements.length} mouvement(s)`" icon="pi pi-history" severity="contrast" rounded />
        </div>

        <AppTableState
          :loading="inventoryStore.loading || variantsLoading"
          :is-empty="!inventoryStore.loading && filteredMovements.length === 0"
          empty-title="Aucun mouvement"
          empty-text="Aucun mouvement ne correspond aux filtres sélectionnés."
        >
          <DataTable
            v-model:first="first"
            :value="filteredMovements"
            data-key="id"
            striped-rows
            responsive-layout="scroll"
            sort-field="occurred_at"
            :sort-order="-1"
            paginator
            :rows="rows"
            :rows-per-page-options="rowsPerPageOptions"
            paginator-template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown CurrentPageReport"
            current-page-report-template="{first}-{last} sur {totalRecords}"
            @page="onPage"
          >
            <Column field="occurred_at" header="Date" sortable style="min-width: 10rem">
              <template #body="{ data }">
                {{ formatDate(data.occurred_at) }}
              </template>
            </Column>
            <Column header="Type" style="min-width: 8rem">
              <template #body="{ data }">
                <Tag :value="typeLabel(data.type)" :severity="typeSeverity(data.type)" rounded />
              </template>
            </Column>
            <Column header="Sens" style="width: 7rem">
              <template #body="{ data }">
                <span class="direction-cell" :class="`direction-cell--${data.direction}`">
                  <i :class="data.direction === 'in' ? 'pi pi-arrow-up' : 'pi pi-arrow-down'" />
                  {{ data.direction === 'in' ? 'Entrée' : 'Sortie' }}
                </span>
              </template>
            </Column>
            <Column field="quantity" header="Quantité" style="width: 7rem">
              <template #body="{ data }">
                <span class="qty-cell" :class="`qty-cell--${data.direction}`">
                  {{ data.direction === 'in' ? '+' : '−' }}{{ formatCompactNumber(data.quantity, '0') }}
                </span>
              </template>
            </Column>
            <Column header="Produit" style="min-width: 10rem">
              <template #body="{ data }">
                {{ data.product_name || '—' }}
              </template>
            </Column>
            <Column header="Variante" style="min-width: 9rem">
              <template #body="{ data }">
                {{ data.variant_label || '—' }}
              </template>
            </Column>
            <Column header="Catégorie" style="min-width: 8rem">
              <template #body="{ data }">
                {{ data.category_name || '—' }}
              </template>
            </Column>
          </DataTable>
        </AppTableState>
      </template>
    </Card>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'

import Card from 'primevue/card'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import DatePicker from 'primevue/datepicker'
import Select from 'primevue/select'
import Tag from 'primevue/tag'

import AppTableState from '@/domains/shared/components/AppTableState.vue'
import { useVariantOptions } from '@/domains/catalog/composables/useVariantOptions'
import { useDisplayFormatters } from '@/domains/shared/composables/useDisplayFormatters'
import { useEntityActions } from '@/domains/shared/composables/useEntityActions'
import { useInventoryStore } from '@/domains/inventory/stores/inventory'

const DEFAULT_PERIOD_DAYS = 10
const rowsPerPageOptions = [10, 20, 50, 100, 200, 500]

const createDefaultDateRange = () => {
  const to = new Date()
  const from = new Date()
  from.setDate(from.getDate() - (DEFAULT_PERIOD_DAYS - 1))
  from.setHours(0, 0, 0, 0)
  to.setHours(23, 59, 59, 999)
  return [from, to]
}

const inventoryStore = useInventoryStore()
const { options: variantOptions, loading: variantsLoading, load: loadVariantOptions } = useVariantOptions()
const { showError } = useEntityActions()
const { formatCompactNumber } = useDisplayFormatters()

const first = ref(0)
const rows = ref(10)

const filters = reactive({
  categoryId: null,
  productId: null,
  variantId: null,
  dateRange: createDefaultDateRange()
})

const onPage = (event) => {
  first.value = event.first
  rows.value = event.rows
}

const categoryOptions = computed(() => {
  const map = new Map()
  for (const entry of variantOptions.value) {
    if (entry.categoryId && entry.categoryName) {
      map.set(entry.categoryId, entry.categoryName)
    }
  }
  return [
    { label: 'Toutes', value: null },
    ...[...map.entries()].map(([value, label]) => ({ label, value }))
  ]
})

const productOptions = computed(() => {
  const map = new Map()
  for (const entry of variantOptions.value) {
    if (filters.categoryId && entry.categoryId !== filters.categoryId) {
      continue
    }
    map.set(entry.productId, entry.productName)
  }
  return [
    { label: 'Tous', value: null },
    ...[...map.entries()].map(([value, label]) => ({ label, value }))
  ]
})

const variantFilterOptions = computed(() => {
  let entries = variantOptions.value

  if (filters.categoryId) {
    entries = entries.filter((entry) => entry.categoryId === filters.categoryId)
  }

  if (filters.productId) {
    entries = entries.filter((entry) => entry.productId === filters.productId)
  }

  return [{ label: 'Toutes', value: null }, ...entries]
})

const startOfDay = (date) => {
  const value = new Date(date)
  value.setHours(0, 0, 0, 0)
  return value
}

const endOfDay = (date) => {
  const value = new Date(date)
  value.setHours(23, 59, 59, 999)
  return value
}

const filteredMovements = computed(() => {
  return inventoryStore.movements.filter((movement) => {
    const categoryId = movement.category_id
    const productId = movement.product_id

    if (filters.categoryId && categoryId !== filters.categoryId) {
      return false
    }

    if (filters.productId && productId !== filters.productId) {
      return false
    }

    if (filters.variantId && movement.variant_id !== filters.variantId) {
      return false
    }

    const [from, to] = filters.dateRange || []

    if (from || to) {
      const occurred = movement.occurred_at ? new Date(movement.occurred_at) : null
      if (!occurred || Number.isNaN(occurred.getTime())) {
        return false
      }

      if (from && occurred < startOfDay(from)) {
        return false
      }

      if (to && occurred > endOfDay(to)) {
        return false
      }
    }

    return true
  })
})

const TYPE_LABELS = {
  purchase: 'Achat',
  sale: 'Vente',
  adjustment: 'Ajustement',
  transfer: 'Transfert'
}

const typeLabel = (type) => TYPE_LABELS[type] || type || '—'

const typeSeverity = (type) => {
  if (type === 'purchase') return 'success'
  if (type === 'sale') return 'info'
  if (type === 'transfer') return 'warn'
  return 'secondary'
}

const formatDate = (value) => {
  if (!value) return '—'
  try {
    return new Date(value).toLocaleString('fr-FR')
  } catch {
    return value
  }
}

const loadMovements = async () => {
  try {
    await inventoryStore.fetchMovements(filters.variantId || null)
  } catch (error) {
    showError(error?.message || 'Impossible de charger les mouvements.')
  }
}

watch(
  () => [filters.categoryId, filters.productId, filters.variantId, filters.dateRange],
  () => {
    first.value = 0
  },
  { deep: true }
)

watch(
  () => filters.categoryId,
  () => {
    if (
      filters.productId &&
      !variantOptions.value.some(
        (entry) =>
          entry.productId === filters.productId &&
          (!filters.categoryId || entry.categoryId === filters.categoryId)
      )
    ) {
      filters.productId = null
    }

    if (
      filters.variantId &&
      !variantOptions.value.some(
        (entry) =>
          entry.value === filters.variantId &&
          (!filters.categoryId || entry.categoryId === filters.categoryId)
      )
    ) {
      filters.variantId = null
      loadMovements()
    }
  }
)

watch(
  () => filters.productId,
  () => {
    if (
      filters.variantId &&
      !variantOptions.value.some(
        (entry) => entry.value === filters.variantId && entry.productId === filters.productId
      )
    ) {
      filters.variantId = null
      loadMovements()
    }
  }
)

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
  margin-bottom: 0;
}

.filter-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 0.85rem;
  align-items: end;
}

.filter-label {
  display: block;
  margin-bottom: 0.4rem;
  color: var(--p-text-muted-color);
  font-size: 0.85rem;
}

.movements-toolbar {
  display: flex;
  justify-content: flex-end;
  margin-bottom: 0.75rem;
}

.direction-cell {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  font-weight: 600;
  font-size: 0.9rem;
}

.direction-cell--in {
  color: var(--p-green-600, #16a34a);
}

.direction-cell--out {
  color: var(--p-orange-600, #ea580c);
}

.qty-cell {
  font-weight: 700;
  font-variant-numeric: tabular-nums;
}

.qty-cell--in {
  color: var(--p-green-600, #16a34a);
}

.qty-cell--out {
  color: var(--p-orange-600, #ea580c);
}

@media (max-width: 1100px) {
  .filter-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 640px) {
  .filter-grid {
    grid-template-columns: 1fr;
  }
}
</style>
