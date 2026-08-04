<template>
  <Dialog
    :visible="visible"
    modal
    :header="`Stock — ${variantLabel}`"
    class="variant-stock-dialog"
    :style="{ width: 'min(760px, 94vw)' }"
    dismissable-mask
    @update:visible="$emit('update:visible', $event)"
  >
    <AppTableState
      :loading="loading"
      :error="error"
      :retrying="loading"
      :is-empty="lots.length === 0"
      empty-title="Aucun lot"
      empty-text="Aucune réception enregistrée pour cette variante."
      @retry="load"
    >
      <div class="variant-stock-dialog__summary">
        <div class="variant-stock-dialog__stat">
          <span class="variant-stock-dialog__stat-label">Quantité totale</span>
          <span class="variant-stock-dialog__stat-value">{{ formatCompactNumber(stock?.available, '0') }}</span>
        </div>
        <div class="variant-stock-dialog__stat">
          <span class="variant-stock-dialog__stat-label">Nombre de lots</span>
          <span class="variant-stock-dialog__stat-value">{{ lots.length }}</span>
        </div>
      </div>

      <DataTable :value="lots" data-key="id" striped-rows responsive-layout="scroll" size="small">
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
        <Column field="received_at" header="Reçu le">
          <template #body="{ data }">
            {{ formatDate(data.received_at) }}
          </template>
        </Column>
        <Column field="expiry_date" header="Expiration">
          <template #body="{ data }">
            {{ data.expiry_date || '—' }}
          </template>
        </Column>
      </DataTable>
    </AppTableState>

    <template #footer>
      <Button
        icon="pi pi-refresh"
        text
        rounded
        severity="secondary"
        :loading="loading"
        aria-label="Actualiser"
        v-tooltip.top="'Actualiser'"
        @click="load"
      />
      <Button label="Fermer" severity="secondary" @click="$emit('update:visible', false)" />
    </template>
  </Dialog>
</template>

<script setup>
import { ref, watch } from 'vue'

import Button from 'primevue/button'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import Dialog from 'primevue/dialog'

import AppTableState from '@/domains/shared/components/AppTableState.vue'
import { useDisplayFormatters } from '@/domains/shared/composables/useDisplayFormatters'
import { inventoryService } from '@/domains/inventory/services/inventoryService'

const props = defineProps({
  visible: { type: Boolean, default: false },
  variantId: { type: String, default: null },
  variantLabel: { type: String, default: 'Variante' }
})

defineEmits(['update:visible'])

const { formatCompactNumber } = useDisplayFormatters()
const loading = ref(false)
const error = ref(null)
const lots = ref([])
const stock = ref(null)

const formatDate = (value) => {
  if (!value) return '—'
  try {
    return new Date(value).toLocaleString('fr-FR')
  } catch {
    return value
  }
}

const load = async () => {
  if (!props.variantId) {
    lots.value = []
    stock.value = null
    return
  }

  loading.value = true
  error.value = null
  try {
    const [lotsData, stockData] = await Promise.all([
      inventoryService.listLots(props.variantId),
      inventoryService.getStock(props.variantId)
    ])
    lots.value = lotsData
    stock.value = stockData
  } catch (err) {
    error.value = err?.message || 'Impossible de charger le stock de cette variante.'
  } finally {
    loading.value = false
  }
}

watch(
  () => [props.visible, props.variantId],
  ([visible]) => {
    if (visible) {
      load()
    }
  }
)
</script>

<style scoped>
.variant-stock-dialog__loading {
  display: flex;
  justify-content: center;
  padding: 2rem;
}

.variant-stock-dialog__summary {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.75rem;
  margin-bottom: 1rem;
}

.variant-stock-dialog__stat {
  border: 1px solid var(--p-content-border-color);
  border-radius: 0.75rem;
  padding: 0.85rem 1rem;
  background: color-mix(in srgb, var(--p-content-background) 80%, transparent);
}

.variant-stock-dialog__stat-label {
  display: block;
  font-size: 0.8rem;
  color: var(--p-text-muted-color);
  margin-bottom: 0.25rem;
}

.variant-stock-dialog__stat-value {
  font-size: 1.35rem;
  font-weight: 700;
}
</style>
