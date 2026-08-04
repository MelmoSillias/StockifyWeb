<template>
  <section class="dashboard-page">
    <CommerceFiltersCard>
      <div class="commerce-filter-field">
        <label class="commerce-filter-label" for="payments-method-filter">Méthode</label>
        <Select
          id="payments-method-filter"
          v-model="filters.method"
          :options="methodFilterOptions"
          option-label="label"
          option-value="value"
          placeholder="Toutes"
          fluid
          show-clear
        />
      </div>
      <div class="commerce-filter-field">
        <label class="commerce-filter-label" for="payments-status-filter">Statut</label>
        <Select
          id="payments-status-filter"
          v-model="filters.status"
          :options="statusOptions"
          option-label="label"
          option-value="value"
          placeholder="Actifs"
          fluid
          show-clear
        />
      </div>
      <div class="commerce-filter-field">
        <label class="commerce-filter-label" for="payments-link-filter">Lié à</label>
        <Select
          id="payments-link-filter"
          v-model="filters.linkType"
          :options="linkOptions"
          option-label="label"
          option-value="value"
          placeholder="Tous"
          fluid
          show-clear
        />
      </div>
      <div class="commerce-filter-field">
        <label class="commerce-filter-label" for="payments-date-range">Période</label>
        <DatePicker
          id="payments-date-range"
          v-model="filters.dateRange"
          selection-mode="range"
          date-format="dd/mm/yy"
          show-icon
          fluid
          hide-on-range-selection
          placeholder="Choisir une période"
        />
      </div>
    </CommerceFiltersCard>

    <Card class="dashboard-panel">
      <template #title>
        <AppTablePanelHeader
          title="Paiements"
          :count-label="`${filteredItems.length} paiement(s)`"
          :show-create="false"
          :search-term="searchTerm"
          search-placeholder="Rechercher un paiement..."
          show-search
          @update:search-term="searchTerm = $event"
        >
          <template #actions>
            <AppTablePrintExportBar table-type="paiements" :search-term="searchTerm" />
          </template>
        </AppTablePanelHeader>
      </template>
      <template #content>
        <AppTableState
          :loading="paymentsStore.loading"
          :is-empty="!paymentsStore.loading && filteredItems.length === 0"
          empty-title="Aucun paiement"
          empty-text="Aucun paiement ne correspond aux filtres sélectionnés."
        >
          <DataTable
            :value="filteredItems"
            data-key="id"
            striped-rows
            :responsive-layout="tableLayout"
            paginator
            :rows="10"
          >
            <Column v-if="!isMobile" field="reference" header="Référence" />
            <Column header="Date">
              <template #body="{ data }">{{ formatDateTime(data.paid_at) }}</template>
            </Column>
            <Column header="Montant">
              <template #body="{ data }">{{ formatMoney(data.amount) }}</template>
            </Column>
            <Column v-if="!isMobile" header="Méthode">
              <template #body="{ data }">
                <Tag :value="resolveMethodLabel(data.method)" severity="secondary" rounded />
              </template>
            </Column>
            <Column v-if="!isMobile" header="Lié à">
              <template #body="{ data }">{{ linkLabel(data) }}</template>
            </Column>
            <Column header="Statut">
              <template #body="{ data }">
                <Tag
                  :value="data.is_cancelled ? 'Annulé' : 'Actif'"
                  :severity="data.is_cancelled ? 'danger' : 'success'"
                  rounded
                />
              </template>
            </Column>
            <Column header="Actions" style="width: 90px">
              <template #body="{ data }">
                <AppTableActionsMenu
                  :actions="paymentRowActions(data)"
                  aria-label="Actions paiement"
                />
              </template>
            </Column>
          </DataTable>
        </AppTableState>
      </template>
    </Card>
  </section>
</template>

<script setup>
import Button from 'primevue/button'
import Card from 'primevue/card'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import DatePicker from 'primevue/datepicker'
import Select from 'primevue/select'
import Tag from 'primevue/tag'
import { computed, onMounted, reactive, ref } from 'vue'

import CommerceFiltersCard from '@/domains/commerce/components/CommerceFiltersCard.vue'
import {
  matchesCancelledFilter,
  matchesDateRange,
  matchesSearch
} from '@/domains/commerce/composables/useCommerceTableFilters'
import { usePaymentsStore } from '@/domains/commerce/stores/payments'
import { usePaymentMethods } from '@/domains/finance/composables/usePaymentMethods'
import AppTableActionsMenu from '@/domains/shared/components/AppTableActionsMenu.vue'
import AppTablePanelHeader from '@/domains/shared/components/AppTablePanelHeader.vue'
import AppTablePrintExportBar from '@/domains/impression/components/AppTablePrintExportBar.vue'
import AppTableState from '@/domains/shared/components/AppTableState.vue'
import { usePrintDocument } from '@/domains/impression/composables/usePrintDocument'
import { usePermissions } from '@/domains/auth/composables/usePermissions'
import { useBreakpoint } from '@/domains/layout/composables/useBreakpoint'
import { useDisplayFormatters } from '@/domains/shared/composables/useDisplayFormatters'
import { useEntityActions } from '@/domains/shared/composables/useEntityActions'

const paymentsStore = usePaymentsStore()
const { isMobile } = useBreakpoint()
const tableLayout = computed(() => (isMobile.value ? 'stack' : 'scroll'))
const { methodOptions: paymentMethodOptions, resolveMethodLabel, load } = usePaymentMethods()
const { formatDateTime, formatMoney } = useDisplayFormatters()
const { showSuccess, showError, confirmRemoval } = useEntityActions()
const { printDocument } = usePrintDocument()
const { hasPermission } = usePermissions()

const searchTerm = ref('')

const filters = reactive({
  method: null,
  status: null,
  linkType: null,
  dateRange: null
})

const methodFilterOptions = computed(() => [
  { label: 'Toutes', value: null },
  ...paymentMethodOptions.value.map((item) => ({
    label: item.label,
    value: item.code
  }))
])

const statusOptions = [
  { label: 'Actif', value: 'active' },
  { label: 'Annulé', value: 'cancelled' }
]

const linkOptions = [
  { label: 'Facture', value: 'facture' },
  { label: 'Commande (acompte)', value: 'commande' }
]


const filteredItems = computed(() =>
  paymentsStore.items.filter((item) => {
    if (!matchesCancelledFilter(item.is_cancelled, filters.status)) {
      return false
    }

    if (filters.method && item.method !== filters.method) {
      return false
    }

    if (filters.status === 'active' && item.is_cancelled) {
      return false
    }

    if (filters.status === 'cancelled' && !item.is_cancelled) {
      return false
    }

    if (filters.linkType === 'facture' && !item.facture_id) {
      return false
    }

    if (filters.linkType === 'commande' && !item.commande_id) {
      return false
    }

    if (!matchesDateRange(item.paid_at, filters.dateRange)) {
      return false
    }

    return matchesSearch(item, searchTerm.value, (entry) => [entry.reference])
  })
)

const linkLabel = (payment) => {
  if (payment.facture_id) {
    return 'Facture'
  }
  if (payment.commande_id) {
    return 'Commande (acompte)'
  }
  return '—'
}

const cancelPayment = (payment) => {
  confirmRemoval({
    header: 'Annuler le paiement',
    message: `Annuler le paiement ${payment.reference} ? Le paiement est conservé mais marqué comme annulé.`,
    onAccept: async () => {
      try {
        await paymentsStore.cancelPayment(payment.id)
        showSuccess(`Paiement ${payment.reference} annulé.`)
      } catch (error) {
        showError(error?.message || 'L\'annulation a échoué.')
      }
    }
  })
}

const paymentRowActions = (payment) => [
  {
    label: 'Imprimer reçu',
    icon: 'pi pi-print',
    visible: hasPermission('impression.documents.print'),
    command: () => printDocument('paiement', payment.id)
  },
  {
    label: 'Annuler le paiement',
    icon: 'pi pi-times',
    severity: 'danger',
    visible: !payment.is_cancelled,
    loading: paymentsStore.isPending(payment.id),
    command: () => cancelPayment(payment)
  }
]

onMounted(async () => {
  await load().catch(() => {})
  paymentsStore.fetchAll().catch(() => {})
})
</script>
