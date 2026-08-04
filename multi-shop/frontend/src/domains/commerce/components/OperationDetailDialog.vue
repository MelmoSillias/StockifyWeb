<template>
  <Dialog
    :visible="visible"
    :header="header"
    modal
    :style="{ width: '40rem' }"
    @update:visible="$emit('update:visible', $event)"
  >
    <div v-if="operation" class="operation-detail">
      <div class="operation-detail__meta">
        <div>
          <span class="operation-detail__label">Référence</span>
          <strong>{{ operation.reference || operation.numero }}</strong>
        </div>
        <div>
          <span class="operation-detail__label">Acheteur</span>
          <span>{{ buyerLabel }}</span>
        </div>
        <div v-if="operation.status">
          <span class="operation-detail__label">Statut</span>
          <span>{{ operation.status }}</span>
        </div>
        <div v-if="operation.delivery_date">
          <span class="operation-detail__label">Livraison prévue</span>
          <span>{{ formatDate(operation.delivery_date) }}</span>
        </div>
      </div>

      <DataTable :value="operation.lines || []" size="small" striped-rows>
        <Column field="label" header="Article" />
        <Column header="Qté" style="width: 80px">
          <template #body="{ data }">{{ formatCompactNumber(data.quantity) }}</template>
        </Column>
        <Column header="P.U." style="width: 120px">
          <template #body="{ data }">{{ formatMoney(data.unit_price) }}</template>
        </Column>
        <Column header="Total" style="width: 120px">
          <template #body="{ data }">{{ formatMoney(data.line_total) }}</template>
        </Column>
      </DataTable>

      <div class="operation-detail__total">
        <span>Total</span>
        <strong>{{ formatMoney(operation.total_amount) }}</strong>
      </div>
    </div>
  </Dialog>
</template>

<script setup>
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import Dialog from 'primevue/dialog'
import { computed } from 'vue'

import { useDisplayFormatters } from '@/domains/shared/composables/useDisplayFormatters'

const props = defineProps({
  visible: { type: Boolean, default: false },
  header: { type: String, default: 'Détail' },
  operation: { type: Object, default: null }
})

defineEmits(['update:visible'])

const { formatMoney, formatCompactNumber, formatDate } = useDisplayFormatters()

const buyerLabel = computed(() => {
  const acheteur = props.operation?.acheteur
  if (!acheteur) {
    return '—'
  }
  return acheteur.anonymous_info || (acheteur.client_id ? 'Client enregistré' : '—')
})
</script>

<style scoped>
.operation-detail {
  display: grid;
  gap: 1rem;
}

.operation-detail__meta {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap: 1rem;
}

.operation-detail__label {
  display: block;
  font-size: 0.78rem;
  color: var(--pv-text-muted);
}

.operation-detail__total {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-top: 0.5rem;
  border-top: 1px solid color-mix(in srgb, var(--pv-surface-border) 70%, transparent);
  font-size: 1.05rem;
}
</style>
