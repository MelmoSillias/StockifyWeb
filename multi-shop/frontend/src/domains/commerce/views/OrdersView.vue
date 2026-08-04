<template>
  <section class="dashboard-page">
    <CommerceFiltersCard>
      <div class="commerce-filter-field">
        <label class="commerce-filter-label" for="orders-status-filter">Statut</label>
        <Select
          id="orders-status-filter"
          v-model="filters.status"
          :options="statusOptions"
          option-label="label"
          option-value="value"
          placeholder="Tous"
          fluid
          show-clear
        />
      </div>
      <div class="commerce-filter-field">
        <label class="commerce-filter-label" for="orders-date-range">Période</label>
        <DatePicker
          id="orders-date-range"
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
          title="Commandes"
          :count-label="`${filteredItems.length} commande(s)`"
          create-label="Nouvelle commande"
          :search-term="searchTerm"
          search-placeholder="Rechercher une commande..."
          show-search
          :reloading="ordersStore.loading"
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
              @click="openFreeOperation('order')"
            />
            <AppTablePrintExportBar table-type="commandes" :search-term="searchTerm" />
          </template>
        </AppTablePanelHeader>
      </template>
      <template #content>
        <AppTableState
          :loading="ordersStore.loading"
          :error="ordersStore.error"
          :is-empty="!ordersStore.loading && filteredItems.length === 0"
          empty-title="Aucune commande"
          empty-text="Aucune commande ne correspond aux filtres sélectionnés."
          @retry="load"
        >
          <DataTable
            :value="filteredItems"
            data-key="id"
            striped-rows
            :responsive-layout="tableLayout"
            paginator
            :rows="10"
            :row-class="rowClass"
          >
            <Column v-if="!isMobile" field="reference" header="Référence" />
            <Column v-if="!isMobile" header="Date">
              <template #body="{ data }">{{ formatDateTime(data.created_at) }}</template>
            </Column>
            <Column v-if="!isMobile" header="Livraison" style="width: 150px">
              <template #body="{ data }">
                <div v-if="data.delivery_date" class="orders-delivery-cell">
                  <span>{{ formatDate(data.delivery_date) }}</span>
                  <Tag
                    v-if="isDeliveryDue(data)"
                    value="À livrer"
                    severity="warn"
                    rounded
                  />
                </div>
                <span v-else class="orders-delivery-cell__empty">—</span>
              </template>
            </Column>
            <Column header="Acheteur">
              <template #body="{ data }">{{ buyerLabel(data.acheteur) }}</template>
            </Column>
            <Column header="Statut">
              <template #body="{ data }">
                <Tag :value="statusLabel(data.status)" :severity="statusSeverity(data.status)" rounded />
              </template>
            </Column>
            <Column header="Total">
              <template #body="{ data }">{{ formatMoney(data.total_amount) }}</template>
            </Column>
            <Column v-if="!isMobile" header="Paiement" style="width: 150px">
              <template #body="{ data }">
                <div v-if="data.payment_status" class="orders-payment-cell">
                  <Tag
                    :value="paymentStatusLabel(data.payment_status)"
                    :severity="paymentStatusSeverity(data.payment_status)"
                    rounded
                  />
                  <small v-if="hasBalance(data)" class="orders-payment-cell__balance">
                    Reste {{ formatMoney(data.balance) }}
                  </small>
                </div>
                <span v-else class="orders-payment-cell__empty">—</span>
              </template>
            </Column>
            <Column v-if="!isMobile" header="Acompte">
              <template #body="{ data }">{{ formatMoney(data.deposit_received) }}</template>
            </Column>
            <Column header="Actions" style="width: 230px">
              <template #body="{ data }">
                <AppTableActionsMenu
                  :actions="orderRowActions(data)"
                  aria-label="Actions commande"
                />
              </template>
            </Column>
          </DataTable>
        </AppTableState>
      </template>
    </Card>

    <BonLivraisonDialog
      v-model:visible="bonLivraisonVisible"
      :order="bonLivraisonOrder"
      @created="onBonLivraisonCreated"
      @delivered="onBonLivraisonDelivered"
    />

    <OrderConfirmDialog
      v-model:visible="confirmVisible"
      :reference="confirmOrderRef?.reference"
      :loading="confirming"
      @confirm="onConfirmDialogSubmit"
    />

    <OrderDetailDialog
      v-model:visible="detailVisible"
      :order="selectedOrder"
      @updated="onOrderUpdated"
    />

    <RecordPaymentDialog
      v-model:visible="paymentVisible"
      :balance="paymentOrder?.balance"
      :sale-date="paymentOrder?.confirmed_at || paymentOrder?.created_at"
      :loading="paying"
      @confirm="onPaymentConfirm"
    />

    <FreeOperationDialog
      v-model:visible="freeOperationVisible"
      mode="order"
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

import BonLivraisonDialog from '@/domains/commerce/components/BonLivraisonDialog.vue'
import OrderDetailDialog from '@/domains/commerce/components/OrderDetailDialog.vue'
import OrderConfirmDialog from '@/domains/commerce/components/OrderConfirmDialog.vue'
import RecordPaymentDialog from '@/domains/commerce/components/RecordPaymentDialog.vue'
import CommerceFiltersCard from '@/domains/commerce/components/CommerceFiltersCard.vue'
import FreeOperationDialog from '@/domains/commerce/components/FreeOperationDialog.vue'
import { useFreeOperationCheckout } from '@/domains/commerce/composables/useFreeOperationCheckout'
import {
  matchesDateRange,
  matchesSearch
} from '@/domains/commerce/composables/useCommerceTableFilters'
import {
  isOrderDeliveryDue,
  sortOrdersByDeliveryPriority
} from '@/domains/commerce/composables/useOrderDeliverySort'
import { useOrdersStore } from '@/domains/commerce/stores/orders'
import { useClientsStore } from '@/domains/client/stores/clients'
import { commerceService } from '@/domains/commerce/services/commerceService'
import AppTablePanelHeader from '@/domains/shared/components/AppTablePanelHeader.vue'
import AppTablePrintExportBar from '@/domains/impression/components/AppTablePrintExportBar.vue'
import AppTableActionsMenu from '@/domains/shared/components/AppTableActionsMenu.vue'
import AppTableState from '@/domains/shared/components/AppTableState.vue'
import { useBreakpoint } from '@/domains/layout/composables/useBreakpoint'
import { useDisplayFormatters } from '@/domains/shared/composables/useDisplayFormatters'
import { useEntityActions } from '@/domains/shared/composables/useEntityActions'
import { toIsoDateTime } from '@/domains/shared/services/createCrudService'

const router = useRouter()
const ordersStore = useOrdersStore()
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
const { formatDateTime, formatDate, formatMoney, formatBuyerLabel } = useDisplayFormatters()
const { showSuccess, showError, confirmRemoval } = useEntityActions()

const searchTerm = ref('')
const detailVisible = ref(false)
const selectedOrder = ref(null)
const detailLoadingId = ref(null)
const paymentVisible = ref(false)
const paymentOrder = ref(null)
const paymentLoadingId = ref(null)
const paying = ref(false)
const confirmVisible = ref(false)
const confirmOrderRef = ref(null)
const confirming = ref(false)
const bonLivraisonVisible = ref(false)
const bonLivraisonOrder = ref(null)

const filters = reactive({
  status: null,
  dateRange: null
})

const statusOptions = [
  { label: 'Initiée', value: 'initiee' },
  { label: 'Confirmée', value: 'confirmee' },
  { label: 'Part. livrée', value: 'partiellement_livree' },
  { label: 'Livrée', value: 'livree' },
  { label: 'Annulée', value: 'annulee' }
]

const statusMap = {
  initiee: { label: 'Initiée', severity: 'warn' },
  confirmee: { label: 'Confirmée', severity: 'info' },
  partiellement_livree: { label: 'Part. livrée', severity: 'info' },
  livree: { label: 'Livrée', severity: 'success' },
  annulee: { label: 'Annulée', severity: 'danger' }
}

const paymentStatusMap = {
  impaye: { label: 'Impayé', severity: 'danger' },
  partiellement_paye: { label: 'Partiellement payé', severity: 'warn' },
  paye: { label: 'Payé', severity: 'success' },
  annulee: { label: 'Annulée', severity: 'danger' }
}

const filteredItems = computed(() => {
  const items = ordersStore.items.filter((item) => {
    if (filters.status && item.status !== filters.status) {
      return false
    }

    if (!matchesDateRange(item.created_at, filters.dateRange)) {
      return false
    }

    return matchesSearch(item, searchTerm.value, (entry) => [
      entry.reference,
      entry.acheteur?.anonymous_info
    ])
  })

  return sortOrdersByDeliveryPriority(items)
})

const isDeliveryDue = (order) => isOrderDeliveryDue(order)
const rowClass = (order) => (isDeliveryDue(order) ? 'orders-row--due' : null)

const buyerLabel = (acheteur) => formatBuyerLabel(acheteur)
const statusLabel = (status) => statusMap[status]?.label || status
const statusSeverity = (status) => statusMap[status]?.severity || 'secondary'
const paymentStatusLabel = (status) => paymentStatusMap[status]?.label || status || '—'
const paymentStatusSeverity = (status) => paymentStatusMap[status]?.severity || 'secondary'
const hasBalance = (order) =>
  order.status !== 'annulee' && order.payment_status !== 'paye' && Number(order.balance) > 0
const canPayOrder = (order) => {
  if (!hasBalance(order)) {
    return false
  }
  if (order.status === 'initiee') {
    return true
  }
  return Boolean(order.facture?.id)
}
const canCancel = (status) => !['livree', 'annulee'].includes(status)
const canCreateBonLivraison = (status) => ['confirmee', 'partiellement_livree'].includes(status)

const openBonLivraisonDialog = (order) => {
  bonLivraisonOrder.value = order
  bonLivraisonVisible.value = true
}

const refreshOrderFromServer = async (orderId) => {
  const updated = await commerceService.getCommande(orderId)
  ordersStore.upsert(updated)
  if (selectedOrder.value?.id === updated.id) {
    selectedOrder.value = updated
  }
  if (bonLivraisonOrder.value?.id === updated.id) {
    bonLivraisonOrder.value = updated
  }
  return updated
}

const onBonLivraisonCreated = async (result) => {
  try {
    const updated = await refreshOrderFromServer(bonLivraisonOrder.value.id)
    if (updated.status === 'livree') {
      showSuccess(`Commande ${updated.reference} entièrement livrée.`)
    }
  } catch (error) {
    showError(error?.message || 'Impossible de rafraîchir la commande.')
  }
}

const onBonLivraisonDelivered = async () => {
  try {
    await refreshOrderFromServer(bonLivraisonOrder.value.id)
  } catch (error) {
    showError(error?.message || 'Impossible de rafraîchir la commande.')
  }
}

const openDetail = async (order) => {
  detailLoadingId.value = order.id
  try {
    selectedOrder.value = await commerceService.getCommande(order.id)
    detailVisible.value = true
  } catch {
    selectedOrder.value = order
    detailVisible.value = true
  } finally {
    detailLoadingId.value = null
  }
}

const openPayment = async (order) => {
  paymentLoadingId.value = order.id
  try {
    paymentOrder.value = await commerceService.getCommande(order.id)
    paymentVisible.value = true
  } catch (error) {
    showError(error?.message || 'Impossible de charger la commande.')
  } finally {
    paymentLoadingId.value = null
  }
}

const onPaymentConfirm = async ({ amount, mode_de_paiement_id, paymentDate }) => {
  if (!paymentOrder.value?.id) {
    return
  }

  paying.value = true
  try {
    const payload = {
      amount: String(amount),
      mode_de_paiement_id,
      paid_at: toIsoDateTime(paymentDate)
    }

    if (paymentOrder.value.status === 'initiee') {
      payload.commande_id = paymentOrder.value.id
    } else if (paymentOrder.value.facture?.id) {
      payload.facture_id = paymentOrder.value.facture.id
    } else {
      return
    }

    await commerceService.createPaiement(payload)
    const updated = await commerceService.getCommande(paymentOrder.value.id)
    ordersStore.upsert(updated)
    if (selectedOrder.value?.id === updated.id) {
      selectedOrder.value = updated
    }
    paymentVisible.value = false
    showSuccess('Paiement enregistré.')
  } catch (error) {
    showError(error?.message || 'Le paiement a échoué.')
  } finally {
    paying.value = false
  }
}

const onOrderUpdated = (order) => {
  selectedOrder.value = order
  ordersStore.upsert(order)
}

const openConfirmDialog = (order) => {
  confirmOrderRef.value = order
  confirmVisible.value = true
}

const onConfirmDialogSubmit = async ({ deliveryDate }) => {
  if (!confirmOrderRef.value) {
    return
  }

  confirming.value = true
  try {
    const updated = await ordersStore.confirmOrder(confirmOrderRef.value.id, {
      delivery_date: toIsoDateTime(deliveryDate)
    })
    if (selectedOrder.value?.id === updated.id) {
      selectedOrder.value = updated
    }
    confirmVisible.value = false
    showSuccess(`Commande ${updated.reference} confirmée.`)
  } catch (error) {
    showError(error?.message || 'La confirmation a échoué.')
  } finally {
    confirming.value = false
  }
}

const orderRowActions = (order) => [
  {
    label: 'Voir le détail',
    icon: 'pi pi-eye',
    loading: detailLoadingId.value === order.id,
    command: () => openDetail(order)
  },
  {
    label: 'Encaisser',
    icon: 'pi pi-wallet',
    severity: 'success',
    visible: canPayOrder(order),
    loading: paymentLoadingId.value === order.id,
    command: () => openPayment(order)
  },
  {
    label: 'Bon de livraison',
    icon: 'pi pi-truck',
    visible: canCreateBonLivraison(order.status),
    command: () => openBonLivraisonDialog(order)
  },
  {
    label: 'Confirmer',
    icon: 'pi pi-check',
    severity: 'success',
    visible: order.status === 'initiee',
    loading: ordersStore.isPending(order.id),
    command: () => openConfirmDialog(order)
  },
  {
    label: 'Annuler',
    icon: 'pi pi-times',
    severity: 'danger',
    visible: canCancel(order.status),
    loading: ordersStore.isPending(order.id),
    command: () => cancelOrder(order)
  }
]

const cancelOrder = (order) => {
  confirmRemoval({
    header: 'Annuler la commande',
    message: `Annuler la commande ${order.reference} ? Cette action est définitive.`,
    onAccept: async () => {
      try {
        await ordersStore.cancelOrder(order.id)
        showSuccess(`Commande ${order.reference} annulée.`)
      } catch (error) {
        showError(error?.message || 'L\'annulation a échoué.')
      }
    }
  })
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
    await ordersStore.fetchAll()
  } catch (error) {
    showError(error?.message || 'Impossible de charger les commandes.')
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
.actions-cell {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.orders-delivery-cell {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 0.25rem;
}

.orders-delivery-cell__empty {
  color: var(--pv-text-muted);
}

.orders-payment-cell {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 0.2rem;
}

.orders-payment-cell__balance {
  font-size: 0.75rem;
  color: var(--pv-text-muted);
}

.orders-payment-cell__empty {
  color: var(--pv-text-muted);
}

:deep(.orders-row--due) {
  background: color-mix(in srgb, var(--p-orange-400, #fb923c) 8%, transparent);
}
</style>
