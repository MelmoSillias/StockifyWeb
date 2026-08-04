<template>
  <AppTableState
    :loading="loading"
    :error="error"
    :is-empty="!loading && filteredItems.length === 0"
    empty-title="Aucune créance"
    :empty-text="emptyText"
    @retry="emit('retry')"
  >
    <DataTable
      v-model:expandedRows="expandedRows"
      :value="filteredItems"
      data-key="id"
      striped-rows
      :responsive-layout="tableLayout"
      paginator
      :rows="10"
    >
      <Column v-if="!isMobile" expander style="width: 3rem" />
      <Column v-if="showClientColumn && !isMobile" header="Client">
        <template #body="{ data }">{{ data.client_name || '—' }}</template>
      </Column>
      <Column header="Référence vente">
        <template #body="{ data }">{{ data.source_reference }}</template>
      </Column>
      <Column v-if="!isMobile" header="Ouverture">
        <template #body="{ data }">{{ formatDateTime(data.issued_at) }}</template>
      </Column>
      <Column header="Montant">
        <template #body="{ data }">{{ formatMoney(data.total_amount) }}</template>
      </Column>
      <Column v-if="!isMobile" header="Payé">
        <template #body="{ data }">{{ formatMoney(data.paid_amount) }}</template>
      </Column>
      <Column header="Reste">
        <template #body="{ data }">{{ formatMoney(data.balance) }}</template>
      </Column>
      <Column header="Statut" style="width: 130px">
        <template #body="{ data }">
          <Tag
            :value="statutLabel(data.statut)"
            :severity="statutSeverity(data.statut)"
            rounded
          />
        </template>
      </Column>
      <Column v-if="!isMobile" header="Soldée le">
        <template #body="{ data }">
          {{ data.credit_closed_at ? formatDateTime(data.credit_closed_at) : '—' }}
        </template>
      </Column>
      <Column header="Actions" style="width: 90px">
        <template #body="{ data }">
          <AppTableActionsMenu
            :actions="creanceRowActions(data)"
            aria-label="Actions créance"
          />
        </template>
      </Column>

      <template #expansion="{ data }">
        <div class="creances-table__expansion">
          <h4 class="creances-table__expansion-title">Paiements</h4>
          <DataTable
            v-if="data.paiements?.length"
            :value="data.paiements"
            data-key="id"
            size="small"
            striped-rows
            responsive-layout="scroll"
          >
            <Column field="reference" header="Référence" />
            <Column header="Date">
              <template #body="{ data: payment }">{{ formatDateTime(payment.paid_at) }}</template>
            </Column>
            <Column header="Montant">
              <template #body="{ data: payment }">{{ formatMoney(payment.amount) }}</template>
            </Column>
            <Column header="Méthode">
              <template #body="{ data: payment }">
                <Tag :value="paymentMethodLabel(payment.method)" severity="secondary" rounded />
              </template>
            </Column>
            <Column header="Statut">
              <template #body="{ data: payment }">
                <Tag
                  :value="payment.is_cancelled ? 'Annulé' : 'Actif'"
                  :severity="payment.is_cancelled ? 'danger' : 'success'"
                  rounded
                />
              </template>
            </Column>
          </DataTable>
          <p v-else class="creances-table__empty">Aucun paiement enregistré.</p>
        </div>
      </template>
    </DataTable>
  </AppTableState>
</template>

<script setup>
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import Tag from 'primevue/tag'
import { computed, ref } from 'vue'

import AppTableActionsMenu from '@/domains/shared/components/AppTableActionsMenu.vue'
import AppTableState from '@/domains/shared/components/AppTableState.vue'
import { useBreakpoint } from '@/domains/layout/composables/useBreakpoint'
import { usePaymentMethods } from '@/domains/finance/composables/usePaymentMethods'
import { useDisplayFormatters } from '@/domains/shared/composables/useDisplayFormatters'

const props = defineProps({
  items: {
    type: Array,
    default: () => []
  },
  loading: {
    type: Boolean,
    default: false
  },
  error: {
    type: String,
    default: null
  },
  showClientColumn: {
    type: Boolean,
    default: true
  },
  searchTerm: {
    type: String,
    default: ''
  },
  statusFilter: {
    type: String,
    default: null
  },
  paymentLoadingId: {
    type: String,
    default: null
  },
  emptyText: {
    type: String,
    default: 'Aucune créance ne correspond aux filtres sélectionnés.'
  }
})

const emit = defineEmits(['pay', 'retry'])

const { isMobile } = useBreakpoint()
const tableLayout = computed(() => (isMobile.value ? 'stack' : 'scroll'))
const { formatDateTime, formatMoney } = useDisplayFormatters()
const { resolveMethodLabel: paymentMethodLabel } = usePaymentMethods()
const expandedRows = ref({})

const statutMap = {
  en_cours: { label: 'En cours', severity: 'warn' },
  soldee: { label: 'Soldée', severity: 'success' },
  annulee: { label: 'Annulée', severity: 'danger' }
}

const statutLabel = (statut) => statutMap[statut]?.label || statut
const statutSeverity = (statut) => statutMap[statut]?.severity || 'secondary'
const canPay = (creance) => creance.statut === 'en_cours' && Number(creance.balance) > 0

const creanceRowActions = (creance) => [
  {
    label: 'Encaisser',
    icon: 'pi pi-wallet',
    severity: 'success',
    visible: canPay(creance),
    loading: props.paymentLoadingId === creance.id,
    command: () => emit('pay', creance)
  }
]

const filteredItems = computed(() => {
  const term = props.searchTerm.trim().toLowerCase()

  return props.items.filter((item) => {
    if (props.statusFilter && item.statut !== props.statusFilter) {
      return false
    }

    if (!term) {
      return true
    }

    return [
      item.client_name,
      item.source_reference,
      item.numero
    ].some((value) => String(value || '').toLowerCase().includes(term))
  })
})
</script>

<style scoped>
.creances-table__expansion {
  padding: 0.5rem 0.75rem 0.75rem;
}

.creances-table__expansion-title {
  margin: 0 0 0.75rem;
  font-size: 0.95rem;
}

.creances-table__empty {
  margin: 0;
  color: var(--p-text-muted-color);
  font-size: 0.9rem;
}
</style>
