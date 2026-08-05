<template>
  <section class="dashboard-page">
    <Card class="dashboard-panel">
      <template #title>
        <AppTablePanelHeader
          title="Carnet de dettes fournisseurs"
          :count-label="`${items.length} dette(s)`"
          :search-term="searchTerm"
          search-placeholder="Rechercher fournisseur ou référence..."
          show-search
          @update:search-term="searchTerm = $event"
          :reloading="loading"
          @reload="loadItems"
        >
          <template #actions>
            <AppTablePrintExportBar table-type="dettes" :search-term="searchTerm" />
          </template>
        </AppTablePanelHeader>
      </template>
      <template #content>
        <AppFiltersCard embedded :active-count="statusFilter ? 1 : 0">
          <div class="dettes-view__filter-field">
            <label class="dettes-view__filter-label" for="dettes-status-filter">Statut</label>
            <Select
              id="dettes-status-filter"
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

        <DettesTable
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

    <RecordSupplierPaymentDialog
      v-model:visible="paymentVisible"
      :balance="paymentDette?.balance"
      :issued-at="paymentDette?.issued_at"
      :loading="paying"
      @confirm="onPaymentConfirm"
    />
  </section>
</template>

<script setup>
import Card from 'primevue/card'
import Select from 'primevue/select'
import { computed, onMounted, ref } from 'vue'

import DettesTable from '@/domains/fournisseur/components/DettesTable.vue'
import RecordSupplierPaymentDialog from '@/domains/fournisseur/components/RecordSupplierPaymentDialog.vue'
import { dettesService } from '@/domains/fournisseur/services/dettesService'
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
const paymentDette = ref(null)
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
    items.value = await dettesService.list({ status: statusFilter.value || 'open' })
  } catch (err) {
    error.value = err?.message || 'Impossible de charger les dettes.'
    showError(err?.message || 'Impossible de charger les dettes.')
  } finally {
    loading.value = false
  }
}

const openPayment = (dette) => {
  paymentLoadingId.value = dette.id
  paymentDette.value = dette
  paymentVisible.value = true
  paymentLoadingId.value = null
}

const onPaymentConfirm = async ({ amount, mode_de_paiement_id, paymentDate }) => {
  if (!paymentDette.value?.id) {
    return
  }

  if (paying.value) return
  paying.value = true
  try {
    await dettesService.createPaiement({
      dette_fournisseur_id: paymentDette.value.id,
      amount: String(amount),
      mode_de_paiement_id,
      paid_at: toIsoDateTime(paymentDate)
    })
    paymentVisible.value = false
    showSuccess('Décaissement enregistré.')
    await loadItems()
  } catch (error) {
    showError(error?.message || 'Le décaissement a échoué.')
  } finally {
    paying.value = false
  }
}

onMounted(() => {
  loadItems()
})
</script>

<style scoped>
.dettes-view__filter-field {
  min-width: 0;
}

.dettes-view__filter-label {
  display: block;
  margin-bottom: 0.35rem;
  font-size: 0.85rem;
  color: var(--p-text-muted-color);
}
</style>
