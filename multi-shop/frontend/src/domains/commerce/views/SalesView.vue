<template>
  <section class="dashboard-page">
    <CommerceFiltersCard>
      <div class="commerce-filter-field">
        <label class="commerce-filter-label" for="sales-payment-filter">Statut paiement</label>
        <Select
          id="sales-payment-filter"
          v-model="filters.paymentStatus"
          :options="paymentStatusOptions"
          option-label="label"
          option-value="value"
          placeholder="Actives"
          fluid
          show-clear
        />
      </div>
      <div class="commerce-filter-field">
        <label class="commerce-filter-label" for="sales-date-range">Période</label>
        <DatePicker
          id="sales-date-range"
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
          title="Ventes"
          :count-label="`${filteredItems.length} vente(s)`"
          create-label="Nouvelle vente"
          :search-term="searchTerm"
          search-placeholder="Rechercher une vente..."
          show-search
          :reloading="salesStore.loading"
          @update:search-term="searchTerm = $event"
          @create="goToCart"
          @reload="load"
        >
          <template #actions>
            <Button
              label="Opération libre"
              icon="pi pi-file-edit"
              severity="secondary"
              outlined
              @click="openFreeOperation('sale')"
            />
            <AppTablePrintExportBar
              table-type="ventes"
              :filters="tableExportFilters"
              :columns="tableExportColumns"
              :search-term="searchTerm"
            />
          </template>
        </AppTablePanelHeader>
      </template>
      <template #content>
        <AppTableState
          :loading="salesStore.loading"
          :error="salesStore.error"
          :is-empty="!salesStore.loading && filteredItems.length === 0"
          empty-title="Aucune vente"
          empty-text="Aucune vente ne correspond aux filtres sélectionnés."
          @retry="load"
        >
          <DataTable
            :value="filteredItems"
            data-key="id"
            striped-rows
            :responsive-layout="tableLayout"
            paginator
            :rows="10"
          >
            <Column v-if="!isMobile" header="Référence" style="width: 120px">
              <template #body="{ data }">
                <span class="sales-ref" :class="{ 'sales-ref--cancelled': data.cancelled_at }">
                  {{ data.reference }}
                </span>
              </template>
            </Column>
            <Column v-if="!isMobile" header="Date">
              <template #body="{ data }">{{ formatDateTime(data.created_at) }}</template>
            </Column>
            <Column v-if="!isMobile" header="Acheteur">
              <template #body="{ data }">{{ formatBuyerLabel(data.acheteur) }}</template>
            </Column>
            <Column header="Total">
              <template #body="{ data }">{{ formatMoney(data.total_amount) }}</template>
            </Column>
            <Column header="Paiement" style="width: 150px">
              <template #body="{ data }">
                <div class="sales-payment-cell">
                  <Tag
                    :value="paymentStatusLabel(data.payment_status)"
                    :severity="paymentStatusSeverity(data.payment_status)"
                    rounded
                  />
                  <small v-if="hasBalance(data)" class="sales-payment-cell__balance">
                    Reste {{ formatMoney(data.balance) }}
                  </small>
                </div>
              </template>
            </Column>
            <Column header="Actions" style="width: 160px">
              <template #body="{ data }">
                <AppTableActionsMenu
                  :actions="saleRowActions(data)"
                  aria-label="Actions vente"
                />
              </template>
            </Column>
          </DataTable>
        </AppTableState>
      </template>
    </Card>

    <SaleDetailDialog
      v-model:visible="detailVisible"
      :sale="selectedSale"
      @updated="onSaleUpdated"
    />

    <RecordPaymentDialog
      v-model:visible="paymentVisible"
      :balance="paymentSale?.balance"
      :sale-date="paymentSale?.created_at"
      :loading="paying"
      @confirm="onPaymentConfirm"
    />

    <FreeOperationDialog
      v-model:visible="freeOperationVisible"
      mode="sale"
      :lines="freeLines"
      :acheteur="freeAcheteur"
      :total="freeTotal"
      :loading="freeSubmitting"
      :clients="clientsStore.items"
      :clients-loading="clientsStore.loading"
      @update:lines="freeLines = $event"
      @update:acheteur="freeAcheteur = $event"
      @submit="onFreeOperationSubmit"
    />
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
import { useRouter } from 'vue-router'

import CommerceFiltersCard from '@/domains/commerce/components/CommerceFiltersCard.vue'
import FreeOperationDialog from '@/domains/commerce/components/FreeOperationDialog.vue'
import RecordPaymentDialog from '@/domains/commerce/components/RecordPaymentDialog.vue'
import SaleDetailDialog from '@/domains/commerce/components/SaleDetailDialog.vue'
import { useFreeOperationCheckout } from '@/domains/commerce/composables/useFreeOperationCheckout'
import {
  matchesCancelledFilter,
  matchesDateRange,
  matchesSearch
} from '@/domains/commerce/composables/useCommerceTableFilters'
import { commerceService } from '@/domains/commerce/services/commerceService'
import { useSalesStore } from '@/domains/commerce/stores/sales'
import { useClientsStore } from '@/domains/client/stores/clients'
import AppTablePanelHeader from '@/domains/shared/components/AppTablePanelHeader.vue'
import AppTableActionsMenu from '@/domains/shared/components/AppTableActionsMenu.vue'
import AppTablePrintExportBar from '@/domains/impression/components/AppTablePrintExportBar.vue'
import AppTableState from '@/domains/shared/components/AppTableState.vue'
import { usePrintDocument } from '@/domains/impression/composables/usePrintDocument'
import { usePermissions } from '@/domains/auth/composables/usePermissions'
import { useBreakpoint } from '@/domains/layout/composables/useBreakpoint'
import { useDisplayFormatters } from '@/domains/shared/composables/useDisplayFormatters'
import { useEntityActions } from '@/domains/shared/composables/useEntityActions'
import { toIsoDateTime } from '@/domains/shared/services/createCrudService'

const router = useRouter()
const salesStore = useSalesStore()
const clientsStore = useClientsStore()
const {
  checkoutVisible: freeOperationVisible,
  submitting: freeSubmitting,
  freeLines,
  acheteur: freeAcheteur,
  total: freeTotal,
  openFreeOperation,
  submitFreeOperation
} = useFreeOperationCheckout()
const { isMobile } = useBreakpoint()
const tableLayout = computed(() => (isMobile.value ? 'stack' : 'scroll'))
const { formatDateTime, formatMoney, formatBuyerLabel } = useDisplayFormatters()
const { showSuccess, showError, confirmPopup } = useEntityActions()
const { printDocument } = usePrintDocument()
const { hasPermission } = usePermissions()

const tableExportColumns = [
  { key: 'reference', label: 'Référence' },
  { key: 'date', label: 'Date' },
  { key: 'buyer', label: 'Acheteur' },
  { key: 'total', label: 'Total' },
  { key: 'status', label: 'Statut' }
]

const tableExportFilters = computed(() => ({
  payment_status: filters.paymentStatus,
  from: filters.dateRange?.[0] ? toIsoDateTime(filters.dateRange[0]) : null,
  to: filters.dateRange?.[1] ? toIsoDateTime(filters.dateRange[1]) : null
}))

const searchTerm = ref('')
const detailVisible = ref(false)
const selectedSale = ref(null)
const detailLoadingId = ref(null)
const paymentVisible = ref(false)
const paymentSale = ref(null)
const paymentLoadingId = ref(null)
const paying = ref(false)
const cancelLoadingId = ref(null)

const filters = reactive({
  paymentStatus: null,
  dateRange: null
})

const paymentStatusOptions = [
  { label: 'Impayé', value: 'impaye' },
  { label: 'Partiellement payé', value: 'partiellement_paye' },
  { label: 'Payé', value: 'paye' },
  { label: 'Annulée', value: 'annulee' }
]

const paymentStatusMap = {
  impaye: { label: 'Impayé', severity: 'danger' },
  partiellement_paye: { label: 'Partiellement payé', severity: 'warn' },
  paye: { label: 'Payé', severity: 'success' },
  annulee: { label: 'Annulée', severity: 'danger' }
}

const filteredItems = computed(() =>
  salesStore.items.filter((item) => {
    const isCancelled = Boolean(item.cancelled_at) || item.payment_status === 'annulee'
    if (!matchesCancelledFilter(isCancelled, filters.paymentStatus)) {
      return false
    }

    if (filters.paymentStatus && item.payment_status !== filters.paymentStatus) {
      return false
    }

    if (!matchesDateRange(item.created_at, filters.dateRange)) {
      return false
    }

    return matchesSearch(item, searchTerm.value, (entry) => [
      entry.reference,
      entry.acheteur?.anonymous_info,
      entry.acheteur?.client_name
    ])
  })
)

const paymentStatusLabel = (status) => paymentStatusMap[status]?.label || status || '—'
const paymentStatusSeverity = (status) => paymentStatusMap[status]?.severity || 'secondary'
const hasBalance = (sale) =>
  !sale.cancelled_at && sale.payment_status !== 'paye' && Number(sale.balance) > 0
const canPaySale = (sale) => hasBalance(sale) && Boolean(sale.facture?.id)
const canCancelSale = (sale) => !sale.cancelled_at && sale.payment_status !== 'annulee'

const loadSale = async (sale) => {
  if (sale.facture?.id) {
    return sale
  }

  return commerceService.getVente(sale.id)
}

const openDetail = async (sale) => {
  detailLoadingId.value = sale.id
  try {
    selectedSale.value = await commerceService.getVente(sale.id)
    detailVisible.value = true
  } catch {
    selectedSale.value = sale
    detailVisible.value = true
  } finally {
    detailLoadingId.value = null
  }
}

const openPayment = async (sale) => {
  paymentLoadingId.value = sale.id
  try {
    paymentSale.value = await loadSale(sale)
    paymentVisible.value = true
  } catch (error) {
    showError(error?.message || 'Impossible de charger la vente.')
  } finally {
    paymentLoadingId.value = null
  }
}

const onPaymentConfirm = async ({ amount, mode_de_paiement_id, paymentDate }) => {
  if (!paymentSale.value?.facture?.id) {
    return
  }

  if (paying.value) return
  paying.value = true
  try {
    await commerceService.createPaiement({
      facture_id: paymentSale.value.facture.id,
      amount: String(amount),
      mode_de_paiement_id,
      paid_at: toIsoDateTime(paymentDate)
    })
    const updated = await commerceService.getVente(paymentSale.value.id)
    salesStore.upsertItem(updated)
    if (selectedSale.value?.id === updated.id) {
      selectedSale.value = updated
    }
    paymentVisible.value = false
    showSuccess('Paiement enregistré.')
  } catch (error) {
    showError(error?.message || 'Le paiement a échoué.')
  } finally {
    paying.value = false
  }
}

const saleRowActions = (sale) => [
  {
    label: 'Voir le détail',
    icon: 'pi pi-eye',
    loading: detailLoadingId.value === sale.id,
    command: () => openDetail(sale)
  },
  {
    label: 'Imprimer ticket',
    icon: 'pi pi-print',
    visible: hasPermission('impression.documents.print'),
    command: () => printDocument('vente_ticket', sale.id)
  },
  {
    label: 'Imprimer facture',
    icon: 'pi pi-print',
    visible: hasPermission('impression.documents.print') && Boolean(sale.facture?.id),
    command: () => printDocument('facture', sale.facture.id)
  },
  {
    label: 'Encaisser',
    icon: 'pi pi-wallet',
    severity: 'success',
    visible: canPaySale(sale),
    loading: paymentLoadingId.value === sale.id,
    command: () => openPayment(sale)
  },
  {
    label: 'Annuler la vente',
    icon: 'pi pi-times',
    severity: 'danger',
    visible: canCancelSale(sale),
    loading: cancelLoadingId.value === sale.id,
    command: (menuEvent) => confirmCancelSale(menuEvent?.originalEvent, sale)
  }
]

const confirmCancelSale = (event, sale) => {
  confirmPopup({
    event,
    header: 'Annuler la vente',
    message: `Annuler la vente ${sale.reference} ? Un avoir sera créé, les paiements actifs seront annulés et le stock sera remis.`,
    acceptLabel: 'Annuler la vente',
    onAccept: async () => {
      cancelLoadingId.value = sale.id
      try {
        const updated = await commerceService.cancelVente(sale.id)
        salesStore.upsertItem(updated)
        if (selectedSale.value?.id === updated.id) {
          selectedSale.value = updated
        }
        showSuccess(`Vente ${sale.reference} annulée.`)
      } catch (error) {
        showError(error?.message || 'L\'annulation a échoué.')
      } finally {
        cancelLoadingId.value = null
      }
    }
  })
}

const onSaleUpdated = (sale) => {
  selectedSale.value = sale
  salesStore.upsertItem(sale)
}

const goToCart = () => router.push({ name: 'commerce-cart' })

const onFreeOperationSubmit = async (checkoutPayload) => {
  const success = await submitFreeOperation(checkoutPayload)
  if (success) {
    await load()
  }
}

const load = async () => {
  try {
    await salesStore.fetchAll()
  } catch (error) {
    showError(error?.message || 'Impossible de charger les ventes.')
  }
}

onMounted(() => {
  load()
  if (clientsStore.items.length === 0) {
    clientsStore.fetchAll().catch(() => {})
  }
})
</script>

<style scoped>
.sales-ref {
  font-size: 0.88rem;
  word-break: break-all;
}

.sales-ref--cancelled {
  opacity: 0.55;
  text-decoration: line-through;
}

.sales-payment-cell {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 0.2rem;
}

.sales-payment-cell__balance {
  font-size: 0.75rem;
  color: var(--pv-text-muted);
}

.actions-cell {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}
</style>
