<template>
  <section class="dashboard-page">
    <Card class="dashboard-panel">
      <template #title>
        <AppTablePanelHeader
          title="Carnet de dettes"
          :count-label="`${items.length} créance(s)`"
          :search-term="searchTerm"
          search-placeholder="Rechercher client ou référence..."
          show-search
          @update:search-term="searchTerm = $event"
          :reloading="loading"
          @reload="loadItems"
        >
          <template #actions>
            <AppTablePrintExportBar table-type="creances" :search-term="searchTerm" />
          </template>
        </AppTablePanelHeader>
      </template>
      <template #content>
        <AppFiltersCard embedded :active-count="statusFilter ? 1 : 0">
          <div class="creances-view__filter-field">
            <label class="creances-view__filter-label" for="creances-status-filter">Statut</label>
            <Select
              id="creances-status-filter"
              v-model="statusFilter"
              :options="statusOptions"
              option-label="label"
              option-value="value"
              placeholder="En cours"
              fluid
              show-clear
              @update:model-value="loadItems"
            />
          </div>
        </AppFiltersCard>

        <CreancesTable
          :items="items"
          :loading="loading"
          :error="error"
          :search-term="searchTerm"
          :status-filter="localStatusFilter"
          :payment-loading-id="paymentLoadingId"
          @pay="openPayment"
          @retry="loadItems"
        />
      </template>
    </Card>

    <RecordPaymentDialog
      v-model:visible="paymentVisible"
      :balance="paymentCreance?.balance"
      :sale-date="paymentCreance?.issued_at"
      :loading="paying"
      @confirm="onPaymentConfirm"
    />
  </section>
</template>

<script setup>
import Card from 'primevue/card'
import Select from 'primevue/select'
import { computed, onMounted, ref } from 'vue'

import CreancesTable from '@/domains/commerce/components/CreancesTable.vue'
import RecordPaymentDialog from '@/domains/commerce/components/RecordPaymentDialog.vue'
import { creancesService } from '@/domains/commerce/services/creancesService'
import { commerceService } from '@/domains/commerce/services/commerceService'
import AppFiltersCard from '@/domains/shared/components/AppFiltersCard.vue'
import AppTablePanelHeader from '@/domains/shared/components/AppTablePanelHeader.vue'
import AppTablePrintExportBar from '@/domains/impression/components/AppTablePrintExportBar.vue'
import { useEntityActions } from '@/domains/shared/composables/useEntityActions'
import { toIsoDateTime } from '@/domains/shared/services/createCrudService'

const { showSuccess, showError } = useEntityActions()

const items = ref([])
const loading = ref(false)
const error = ref(null)
const searchTerm = ref('')
const statusFilter = ref('open')
const paymentVisible = ref(false)
const paymentCreance = ref(null)
const paymentLoadingId = ref(null)
const paying = ref(false)

const statusOptions = [
  { label: 'En cours', value: 'open' },
  { label: 'Soldées', value: 'closed' },
  { label: 'Toutes', value: 'all' }
]

const localStatusFilter = computed(() => {
  if (statusFilter.value === 'open') {
    return 'en_cours'
  }
  if (statusFilter.value === 'closed') {
    return 'soldee'
  }
  return null
})

const loadItems = async () => {
  loading.value = true
  error.value = null
  try {
    items.value = await creancesService.list({ status: statusFilter.value || 'open' })
  } catch (err) {
    error.value = err?.message || 'Impossible de charger les créances.'
    showError(err?.message || 'Impossible de charger les créances.')
  } finally {
    loading.value = false
  }
}

const openPayment = (creance) => {
  paymentLoadingId.value = creance.id
  paymentCreance.value = creance
  paymentVisible.value = true
  paymentLoadingId.value = null
}

const onPaymentConfirm = async ({ amount, mode_de_paiement_id, paymentDate }) => {
  if (!paymentCreance.value?.id) {
    return
  }

  if (paying.value) return
  paying.value = true
  try {
    await commerceService.createPaiement({
      facture_id: paymentCreance.value.id,
      amount: String(amount),
      mode_de_paiement_id,
      paid_at: toIsoDateTime(paymentDate)
    })
    paymentVisible.value = false
    showSuccess('Paiement enregistré.')
    await loadItems()
  } catch (error) {
    showError(error?.message || 'Le paiement a échoué.')
  } finally {
    paying.value = false
  }
}

onMounted(() => {
  loadItems()
})
</script>

<style scoped>
.creances-view__filter-field {
  min-width: 0;
}

.creances-view__filter-label {
  display: block;
  margin-bottom: 0.35rem;
  font-size: 0.85rem;
  color: var(--p-text-muted-color);
}
</style>
