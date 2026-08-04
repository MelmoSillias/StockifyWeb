<template>
  <section class="dashboard-page">
    <CommerceFiltersCard>
      <div class="commerce-filter-field">
        <label class="commerce-filter-label" for="quotes-status-filter">Statut</label>
        <Select
          id="quotes-status-filter"
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
        <label class="commerce-filter-label" for="quotes-date-range">Période</label>
        <DatePicker
          id="quotes-date-range"
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
          title="Devis"
          :count-label="`${filteredItems.length} devis`"
          create-label="Nouveau devis"
          :search-term="searchTerm"
          search-placeholder="Rechercher un devis..."
          show-search
          :reloading="quotesStore.loading"
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
              @click="openFreeOperation('quote')"
            />
          </template>
        </AppTablePanelHeader>
      </template>
      <template #content>
        <AppTableState
          :loading="quotesStore.loading"
          :error="quotesStore.error"
          :is-empty="!quotesStore.loading && filteredItems.length === 0"
          empty-title="Aucun devis"
          empty-text="Aucun devis ne correspond aux filtres sélectionnés."
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
            <Column v-if="!isMobile" field="reference" header="Référence" />
            <Column v-if="!isMobile" header="Date">
              <template #body="{ data }">{{ formatDateTime(data.created_at) }}</template>
            </Column>
            <Column v-if="!isMobile" header="Validité">
              <template #body="{ data }">
                {{ data.valid_until ? formatDate(data.valid_until) : '—' }}
              </template>
            </Column>
            <Column header="Acheteur">
              <template #body="{ data }">{{ formatBuyerLabel(data.acheteur) }}</template>
            </Column>
            <Column header="Statut">
              <template #body="{ data }">
                <Tag :value="statusLabel(data.status)" :severity="statusSeverity(data.status)" rounded />
              </template>
            </Column>
            <Column header="Total">
              <template #body="{ data }">{{ formatMoney(data.total_amount) }}</template>
            </Column>
            <Column header="Actions" style="width: 230px">
              <template #body="{ data }">
                <AppTableActionsMenu
                  :actions="quoteRowActions(data)"
                  aria-label="Actions devis"
                />
              </template>
            </Column>
          </DataTable>
        </AppTableState>
      </template>
    </Card>

    <DevisDetailDialog
      v-model:visible="detailVisible"
      :devis="selectedQuote"
      @convert-sale="openSaleConversion"
      @convert-order="openOrderConversion"
    />

    <CheckoutDialog
      :visible="checkoutVisible"
      :mode="checkoutMode"
      :payment-hint="checkoutMode === 'sale'
        ? 'Enregistrer le paiement de la vente immédiatement.'
        : 'Enregistrer un acompte sur la commande.'"
      :total="conversionTotal()"
      :loading="submitting"
      @update:visible="checkoutVisible = $event"
      @confirm="onConversionConfirm"
    />

    <FreeOperationDialog
      v-model:visible="freeOperationVisible"
      mode="quote"
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

import CheckoutDialog from '@/domains/commerce/components/CheckoutDialog.vue'
import DevisDetailDialog from '@/domains/commerce/components/DevisDetailDialog.vue'
import CommerceFiltersCard from '@/domains/commerce/components/CommerceFiltersCard.vue'
import FreeOperationDialog from '@/domains/commerce/components/FreeOperationDialog.vue'
import { useFreeOperationCheckout } from '@/domains/commerce/composables/useFreeOperationCheckout'
import {
  matchesDateRange,
  matchesSearch
} from '@/domains/commerce/composables/useCommerceTableFilters'
import { useDevisConversion } from '@/domains/commerce/composables/useDevisConversion'
import { useQuotesStore } from '@/domains/commerce/stores/quotes'
import { useClientsStore } from '@/domains/client/stores/clients'
import AppTablePanelHeader from '@/domains/shared/components/AppTablePanelHeader.vue'
import AppTableActionsMenu from '@/domains/shared/components/AppTableActionsMenu.vue'
import AppTableState from '@/domains/shared/components/AppTableState.vue'
import { useBreakpoint } from '@/domains/layout/composables/useBreakpoint'
import { useDisplayFormatters } from '@/domains/shared/composables/useDisplayFormatters'
import { useEntityActions } from '@/domains/shared/composables/useEntityActions'

const router = useRouter()
const quotesStore = useQuotesStore()
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
const {
  checkoutVisible,
  checkoutMode,
  submitting,
  openConversion,
  onCheckoutConfirm: onConversionConfirm,
  conversionTotal
} = useDevisConversion()

const searchTerm = ref('')
const detailVisible = ref(false)
const selectedQuote = ref(null)
const detailLoadingId = ref(null)
const cancelLoadingId = ref(null)

const filters = reactive({
  status: null,
  dateRange: null
})

const statusOptions = [
  { label: 'Actif', value: 'actif' },
  { label: 'Converti en vente', value: 'converti_vente' },
  { label: 'Converti en commande', value: 'converti_commande' },
  { label: 'Annulé', value: 'annule' },
  { label: 'Expiré', value: 'expire' }
]

const statusMap = {
  actif: { label: 'Actif', severity: 'success' },
  converti_vente: { label: 'Converti en vente', severity: 'info' },
  converti_commande: { label: 'Converti en commande', severity: 'info' },
  annule: { label: 'Annulé', severity: 'danger' },
  expire: { label: 'Expiré', severity: 'warn' }
}

const statusLabel = (status) => statusMap[status]?.label || status
const statusSeverity = (status) => statusMap[status]?.severity || 'secondary'

const filteredItems = computed(() =>
  quotesStore.items.filter((item) => {
    if (filters.status && item.status !== filters.status) {
      return false
    }
    if (!matchesDateRange(item.created_at, filters.dateRange)) {
      return false
    }
    return matchesSearch(item, searchTerm.value, ['reference'])
  })
)

const openDetail = async (quote) => {
  detailLoadingId.value = quote.id
  try {
    selectedQuote.value = quote
    detailVisible.value = true
  } finally {
    detailLoadingId.value = null
  }
}

const openSaleConversion = (quote) => {
  detailVisible.value = false
  openConversion(quote, 'sale')
}

const openOrderConversion = (quote) => {
  detailVisible.value = false
  openConversion(quote, 'order')
}

const cancelQuote = (quote) => {
  confirmRemoval({
    header: 'Annuler le devis',
    message: `Annuler le devis ${quote.reference} ? Cette action est définitive.`,
    onAccept: async () => {
      cancelLoadingId.value = quote.id
      try {
        await quotesStore.cancelQuote(quote.id)
        if (selectedQuote.value?.id === quote.id) {
          selectedQuote.value = quotesStore.items.find((entry) => entry.id === quote.id) || null
        }
        showSuccess(`Devis ${quote.reference} annulé.`)
      } catch (error) {
        showError(error?.message || 'L\'annulation a échoué.')
      } finally {
        cancelLoadingId.value = null
      }
    }
  })
}

const quoteRowActions = (quote) => [
  {
    label: 'Voir le détail',
    icon: 'pi pi-eye',
    loading: detailLoadingId.value === quote.id,
    command: () => openDetail(quote)
  },
  {
    label: 'Convertir en vente',
    icon: 'pi pi-shopping-bag',
    severity: 'success',
    visible: quote.status === 'actif',
    command: () => openSaleConversion(quote)
  },
  {
    label: 'Convertir en commande',
    icon: 'pi pi-list',
    visible: quote.status === 'actif',
    command: () => openOrderConversion(quote)
  },
  {
    label: 'Annuler le devis',
    icon: 'pi pi-times',
    severity: 'danger',
    visible: quote.status === 'actif',
    loading: cancelLoadingId.value === quote.id,
    command: () => cancelQuote(quote)
  }
]

const goToCart = () => router.push({ name: 'commerce-cart' })

const onFreeOperationSubmit = async (checkoutPayload) => {
  const success = await submitFreeOperation(checkoutPayload)
  if (success) {
    await load()
  }
}

const load = async () => {
  try {
    await quotesStore.fetchAll()
  } catch (error) {
    showError(error?.message || 'Impossible de charger les devis.')
  }
}

onMounted(() => {
  load()
  if (clientsStore.items.length === 0) {
    clientsStore.fetchAll().catch(() => {})
  }
})
</script>
