<template>
  <Dialog
    :visible="visible"
    header="Détail du devis"
    modal
    :style="{ width: '42rem' }"
    @update:visible="$emit('update:visible', $event)"
  >
    <div v-if="devis" class="devis-detail">
      <div class="devis-detail__meta">
        <div>
          <span class="devis-detail__label">Référence</span>
          <strong>{{ devis.reference }}</strong>
        </div>
        <div>
          <span class="devis-detail__label">Acheteur</span>
          <span>{{ formatBuyerLabel(devis.acheteur) }}</span>
        </div>
        <div>
          <span class="devis-detail__label">Date</span>
          <span>{{ formatDateTime(devis.created_at) }}</span>
        </div>
        <div>
          <span class="devis-detail__label">Validité</span>
          <span>{{ devis.valid_until ? formatDate(devis.valid_until) : '—' }}</span>
        </div>
        <div>
          <span class="devis-detail__label">Statut</span>
          <Tag :value="statusLabel(devis.status)" :severity="statusSeverity(devis.status)" rounded />
        </div>
      </div>

      <DataTable :value="devis.lines || []" size="small" striped-rows>
        <Column header="Article">
          <template #body="{ data }">
            <div class="line-label-cell">
              <Tag
                v-if="data.line_type === 'libre'"
                value="Libre"
                severity="secondary"
                rounded
              />
              <span>{{ data.label }}</span>
            </div>
          </template>
        </Column>
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

      <div class="devis-detail__total">
        <span>Total</span>
        <strong>{{ formatMoney(devis.total_amount) }}</strong>
      </div>
    </div>

    <template #footer>
      <Button label="Fermer" severity="secondary" text @click="$emit('update:visible', false)" />
      <Button
        v-if="canConvertToOrder"
        v-can="'commerce.devis.convert'"
        label="Convertir en commande"
        icon="pi pi-list"
        severity="secondary"
        @click="$emit('convert-order', devis)"
      />
      <Button
        v-if="canConvert"
        v-can="'commerce.devis.convert'"
        label="Convertir en vente"
        icon="pi pi-shopping-bag"
        @click="$emit('convert-sale', devis)"
      />
    </template>
  </Dialog>
</template>

<script setup>
import Button from 'primevue/button'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import Dialog from 'primevue/dialog'
import Tag from 'primevue/tag'
import { computed } from 'vue'

import { usePermissions } from '@/domains/auth/composables/usePermissions'
import { useDisplayFormatters } from '@/domains/shared/composables/useDisplayFormatters'

const props = defineProps({
  visible: { type: Boolean, default: false },
  devis: { type: Object, default: null }
})

defineEmits(['update:visible', 'convert-sale', 'convert-order'])

const { hasFeature } = usePermissions()
const { formatDateTime, formatDate, formatMoney, formatCompactNumber, formatBuyerLabel } = useDisplayFormatters()

const statusMap = {
  actif: { label: 'Actif', severity: 'success' },
  converti_vente: { label: 'Converti en vente', severity: 'info' },
  converti_commande: { label: 'Converti en commande', severity: 'info' },
  annule: { label: 'Annulé', severity: 'danger' },
  expire: { label: 'Expiré', severity: 'warn' }
}

const statusLabel = (status) => statusMap[status]?.label || status
const statusSeverity = (status) => statusMap[status]?.severity || 'secondary'

const canConvert = computed(() => props.devis?.status === 'actif')
const canConvertToOrder = computed(() => canConvert.value && hasFeature('stockify.orders'))
</script>

<style scoped>
.devis-detail {
  display: grid;
  gap: 1rem;
}

.devis-detail__meta {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.75rem 1rem;
}

.line-label-cell {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.devis-detail__label {
  display: block;
  color: var(--pv-text-muted);
  font-size: 0.85rem;
  margin-bottom: 0.15rem;
}

.devis-detail__total {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-top: 0.75rem;
  border-top: 1px solid var(--pv-surface-border);
}
</style>
