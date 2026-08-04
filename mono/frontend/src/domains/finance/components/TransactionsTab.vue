<template>
  <div class="transactions-tab">
    <AppFiltersCard :active-count="activeFilterCount">
      <div class="transactions-tab__field">
        <label for="tx-filter-compte">Compte</label>
        <Select
          id="tx-filter-compte"
          v-model="filters.compteId"
          :options="compteOptions"
          option-label="label"
          option-value="value"
          show-clear
          placeholder="Tous"
          fluid
        />
      </div>
      <div class="transactions-tab__field">
        <label for="tx-filter-type">Type</label>
        <Select
          id="tx-filter-type"
          v-model="filters.type"
          :options="typeOptions"
          option-label="label"
          option-value="value"
          show-clear
          placeholder="Tous"
          fluid
        />
      </div>
      <div class="transactions-tab__field">
        <label for="tx-filter-from">Du</label>
        <DatePicker id="tx-filter-from" v-model="filters.from" show-icon fluid />
      </div>
      <div class="transactions-tab__field">
        <label for="tx-filter-to">Au</label>
        <DatePicker id="tx-filter-to" v-model="filters.to" show-icon fluid />
      </div>
      <Button label="Filtrer" icon="pi pi-filter" @click="loadTransactions" />
    </AppFiltersCard>

    <Card class="dashboard-panel dashboard-panel--flush">
      <template #title>
        <AppTablePanelHeader
          title="Transactions"
          :count-label="`${transactionsStore.items.length} transaction(s)`"
          create-label="Nouvelle transaction"
          show-create
          @create="dialogVisible = true"
        >
          <template #actions>
            <AppTablePrintExportBar
              table-type="transactions"
              :filters="{ compte_id: filters.compteId }"
            />
          </template>
        </AppTablePanelHeader>
      </template>
      <template #content>
        <AppTableState
          :loading="transactionsStore.loading"
          :is-empty="!transactionsStore.loading && transactionsStore.items.length === 0"
          empty-title="Aucune transaction"
          empty-text="Enregistrez une transaction manuelle ou encaissez une vente."
        >
          <DataTable
            :value="transactionsStore.items"
            data-key="id"
            striped-rows
            :responsive-layout="tableLayout"
            paginator
            :rows="15"
          >
            <Column v-if="!isMobile" header="Date" style="width: 170px">
              <template #body="{ data }">{{ formatDateTime(data.occurred_at) }}</template>
            </Column>
            <Column field="label" header="Libellé" />
            <Column v-if="!isNarrow" header="Type" style="width: 110px">
              <template #body="{ data }">
                <Tag
                  :value="transactionTypeLabel(data.type)"
                  :severity="data.type === 'revenu' ? 'success' : 'danger'"
                  rounded
                />
              </template>
            </Column>
            <Column header="Montant" style="width: 130px">
              <template #body="{ data }">{{ formatMoney(data.amount) }}</template>
            </Column>
            <Column v-if="!isMobile" header="Source" style="width: 110px">
              <template #body="{ data }">{{ transactionSourceLabel(data.source_type) }}</template>
            </Column>
            <Column v-if="!isMobile" header="Statut" style="width: 110px">
              <template #body="{ data }">
                <Tag
                  :value="data.is_cancelled ? 'Annulée' : 'Active'"
                  :severity="data.is_cancelled ? 'secondary' : 'info'"
                  rounded
                />
              </template>
            </Column>
            <Column header="Actions" style="width: 90px">
              <template #body="{ data }">
                <AppTableActionsMenu
                  :actions="transactionRowActions(data)"
                  aria-label="Actions transaction"
                />
              </template>
            </Column>
          </DataTable>
        </AppTableState>
      </template>
    </Card>

    <TransactionFormDialog
      v-model:visible="dialogVisible"
      :accounts="accounts"
      :loading="transactionsStore.submitting"
      @submit="createTransaction"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'

import Button from 'primevue/button'
import Card from 'primevue/card'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import DatePicker from 'primevue/datepicker'
import Select from 'primevue/select'
import Tag from 'primevue/tag'

import TransactionFormDialog from '@/domains/finance/components/TransactionFormDialog.vue'
import AppFiltersCard from '@/domains/shared/components/AppFiltersCard.vue'
import AppTableActionsMenu from '@/domains/shared/components/AppTableActionsMenu.vue'
import AppTablePanelHeader from '@/domains/shared/components/AppTablePanelHeader.vue'
import AppTablePrintExportBar from '@/domains/impression/components/AppTablePrintExportBar.vue'
import AppTableState from '@/domains/shared/components/AppTableState.vue'
import { usePrintDocument } from '@/domains/impression/composables/usePrintDocument'
import { usePermissions } from '@/domains/auth/composables/usePermissions'
import { useBreakpoint } from '@/domains/layout/composables/useBreakpoint'
import {
  accountTypeLabel,
  transactionSourceLabel,
  transactionTypeLabel
} from '@/domains/finance/composables/useFinanceFilters'
import { useTransactionsStore } from '@/domains/finance/stores/transactions'
import { useDisplayFormatters } from '@/domains/shared/composables/useDisplayFormatters'
import { useEntityActions } from '@/domains/shared/composables/useEntityActions'

const props = defineProps({
  accounts: { type: Array, default: () => [] }
})

const emit = defineEmits(['refresh-accounts'])

const transactionsStore = useTransactionsStore()
const { isMobile, isNarrow } = useBreakpoint()
const tableLayout = computed(() => (isMobile.value ? 'stack' : 'scroll'))
const { formatMoney, formatDateTime } = useDisplayFormatters()
const { showSuccess, showError, confirmRemoval } = useEntityActions()
const { printDocument } = usePrintDocument()
const { hasPermission } = usePermissions()

const dialogVisible = ref(false)
const filters = reactive({
  compteId: null,
  type: null,
  from: null,
  to: null
})

const typeOptions = [
  { label: 'Revenu', value: 'revenu' },
  { label: 'Dépense', value: 'depense' }
]

const compteOptions = computed(() =>
  props.accounts.map((account) => ({
    label: `${account.name} (${accountTypeLabel(account.type)})`,
    value: account.id
  }))
)

const activeFilterCount = computed(() => {
  let count = 0
  if (filters.compteId) count += 1
  if (filters.type) count += 1
  if (filters.from) count += 1
  if (filters.to) count += 1
  return count
})

const loadTransactions = async () => {
  try {
    await transactionsStore.fetchAll({
      compte_id: filters.compteId || undefined,
      type: filters.type || undefined,
      from: filters.from ? filters.from.toISOString() : undefined,
      to: filters.to ? filters.to.toISOString() : undefined
    })
  } catch (error) {
    showError(error?.message || 'Impossible de charger les transactions.')
  }
}

const createTransaction = async (payload) => {
  try {
    await transactionsStore.createItem(payload)
    showSuccess('Transaction enregistrée.')
    dialogVisible.value = false
    emit('refresh-accounts')
    await loadTransactions()
  } catch (error) {
    showError(error?.message || "Impossible d'enregistrer la transaction.")
  }
}

const confirmCancel = (transaction) => {
  confirmRemoval({
    header: 'Annuler cette transaction ?',
    message: `La transaction « ${transaction.label} » sera annulée.`,
    onAccept: async () => {
      try {
        await transactionsStore.cancelItem(transaction.id)
        showSuccess('Transaction annulée.')
        emit('refresh-accounts')
      } catch (error) {
        showError(error?.message || "Impossible d'annuler la transaction.")
      }
    }
  })
}

const transactionRowActions = (transaction) => [
  {
    label: 'Imprimer',
    icon: 'pi pi-print',
    visible: hasPermission('impression.documents.print'),
    command: () => printDocument('transaction', transaction.id)
  },
  {
    label: 'Annuler',
    icon: 'pi pi-times',
    severity: 'danger',
    visible: transaction.source_type === 'manuel' && !transaction.is_cancelled,
    loading: transactionsStore.isCancelling(transaction.id),
    command: () => confirmCancel(transaction)
  }
]

onMounted(loadTransactions)

defineExpose({ loadTransactions })
</script>

<style scoped>
.transactions-tab {
  display: grid;
  gap: 1rem;
  min-width: 0;
}

.transactions-tab__field {
  display: grid;
  gap: 0.35rem;
  min-width: 0;
}

.transactions-tab__field label {
  color: var(--p-text-muted-color);
  font-size: 0.85rem;
}

@media (max-width: 767px) {
  .transactions-tab {
    gap: 0.5rem;
  }
}
</style>
