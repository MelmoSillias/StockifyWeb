<template>
  <Dialog
    :visible="visible"
    :header="commande ? `Commande ${commande.reference}` : 'Commande achat'"
    modal
    :style="{ width: '42rem' }"
    @update:visible="$emit('update:visible', $event)"
  >
    <div v-if="commande" class="commande-detail-dialog">
      <div class="commande-detail-dialog__meta">
        <Tag :value="statusLabel(commande.status)" :severity="statusSeverity(commande.status)" rounded />
        <span>Total : {{ formatMoney(commande.total_amount) }}</span>
      </div>

      <DataTable :value="commande.lines || []" data-key="variant_id" size="small" striped-rows>
        <Column field="label" header="Article" />
        <Column header="Quantité">
          <template #body="{ data }">{{ data.quantity }}</template>
        </Column>
        <Column header="Coût unitaire">
          <template #body="{ data }">{{ formatMoney(data.unit_cost) }}</template>
        </Column>
        <Column header="Total ligne">
          <template #body="{ data }">{{ formatMoney(data.line_total) }}</template>
        </Column>
      </DataTable>
    </div>
  </Dialog>
</template>

<script setup>
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import Dialog from 'primevue/dialog'
import Tag from 'primevue/tag'

import { useDisplayFormatters } from '@/domains/shared/composables/useDisplayFormatters'

defineProps({
  visible: { type: Boolean, default: false },
  commande: { type: Object, default: null }
})

defineEmits(['update:visible'])

const { formatMoney } = useDisplayFormatters()

const statusMap = {
  initiee: { label: 'Initiée', severity: 'secondary' },
  confirmee: { label: 'Confirmée', severity: 'info' },
  recue: { label: 'Reçue', severity: 'success' },
  annulee: { label: 'Annulée', severity: 'danger' }
}

const statusLabel = (status) => statusMap[status]?.label || status
const statusSeverity = (status) => statusMap[status]?.severity || 'secondary'
</script>

<style scoped>
.commande-detail-dialog__meta {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 1rem;
}
</style>
