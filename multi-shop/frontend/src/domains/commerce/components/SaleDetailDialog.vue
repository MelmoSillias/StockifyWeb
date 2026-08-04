<template>
  <Dialog
    :visible="visible"
    header="Détail de la vente"
    modal
    :style="{ width: '48rem' }"
    @update:visible="$emit('update:visible', $event)"
  >
    <div v-if="sale" class="sale-detail">
      <div class="sale-detail__meta">
        <div>
          <span class="sale-detail__label">Référence</span>
          <strong>{{ sale.reference }}</strong>
        </div>
        <div>
          <span class="sale-detail__label">Acheteur</span>
          <span>{{ formatBuyerLabel(sale?.acheteur) }}</span>
        </div>
        <div>
          <span class="sale-detail__label">Date</span>
          <span>{{ formatDateTime(sale.created_at) }}</span>
        </div>
        <div v-if="isCancelled">
          <span class="sale-detail__label">Statut</span>
          <Tag value="Annulée" severity="danger" rounded />
        </div>
      </div>

      <Tabs v-model:value="activeTab">
        <TabList>
          <Tab value="articles">Articles</Tab>
          <Tab value="billing">Facturation</Tab>
        </TabList>

        <TabPanels>
          <TabPanel value="articles">
            <DataTable :value="sale.lines || []" size="small" striped-rows>
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

            <div class="sale-detail__total">
              <span>Total</span>
              <strong>{{ formatMoney(sale.total_amount) }}</strong>
            </div>
          </TabPanel>

          <TabPanel value="billing">
            <div v-if="sale.facture" class="sale-detail__section">
              <h4 class="sale-detail__section-title">Facture</h4>
              <div class="sale-detail__info-grid">
                <div>
                  <span class="sale-detail__label">Numéro</span>
                  <span>{{ sale.facture.numero }}</span>
                </div>
                <div>
                  <span class="sale-detail__label">Date d'émission</span>
                  <span>{{ formatDateTime(sale.facture.issued_at) }}</span>
                </div>
                <div>
                  <span class="sale-detail__label">Montant</span>
                  <span>{{ formatMoney(sale.facture.total_amount) }}</span>
                </div>
              </div>
            </div>
            <p v-else class="sale-detail__empty">Aucune facture associée.</p>

            <div v-if="sale.avoir" class="sale-detail__section">
              <h4 class="sale-detail__section-title">Avoir</h4>
              <div class="sale-detail__info-grid">
                <div>
                  <span class="sale-detail__label">Numéro</span>
                  <span>{{ sale.avoir.numero }}</span>
                </div>
                <div>
                  <span class="sale-detail__label">Date d'émission</span>
                  <span>{{ formatDateTime(sale.avoir.issued_at) }}</span>
                </div>
                <div>
                  <span class="sale-detail__label">Montant</span>
                  <span>{{ formatMoney(sale.avoir.total_amount) }}</span>
                </div>
              </div>
            </div>

            <div class="sale-detail__section">
              <h4 class="sale-detail__section-title">État du paiement</h4>
              <div class="sale-detail__payment-summary">
                <Tag
                  :value="paymentStatusLabel(sale.payment_status)"
                  :severity="paymentStatusSeverity(sale.payment_status)"
                  rounded
                />
                <div v-if="!isCancelled" class="sale-detail__amounts">
                  <div>
                    <span class="sale-detail__label">Payé</span>
                    <span>{{ formatMoney(sale.paid_amount) }}</span>
                  </div>
                  <div>
                    <span class="sale-detail__label">Reste</span>
                    <strong>{{ formatMoney(sale.balance) }}</strong>
                  </div>
                </div>
              </div>
              <Button
                v-if="canPay"
                label="Encaisser"
                icon="pi pi-wallet"
                size="small"
                @click="paymentVisible = true"
              />
            </div>

            <div class="sale-detail__section">
              <h4 class="sale-detail__section-title">Paiements</h4>
              <DataTable
                v-if="sale.paiements?.length"
                :value="sale.paiements"
                size="small"
                striped-rows
              >
                <Column field="reference" header="Référence" />
                <Column header="Date">
                  <template #body="{ data }">{{ formatDateTime(data.paid_at) }}</template>
                </Column>
                <Column header="Montant">
                  <template #body="{ data }">{{ formatMoney(data.amount) }}</template>
                </Column>
                <Column header="Méthode">
                  <template #body="{ data }">
                    <Tag :value="resolveMethodLabel(data.method)" severity="secondary" rounded />
                  </template>
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
              </DataTable>
              <p v-else class="sale-detail__empty">Aucun paiement enregistré.</p>
            </div>
          </TabPanel>
        </TabPanels>
      </Tabs>
    </div>

    <template #footer>
      <Button
        v-if="canPrint"
        icon="pi pi-print"
        label="Imprimer"
        severity="secondary"
        outlined
        @click="printSale"
      />
      <Button
        v-if="!isCancelled"
        v-can="'commerce.ventes.cancel'"
        label="Annuler la vente"
        severity="danger"
        text
        :loading="cancelling"
        @click="confirmCancel($event)"
      />
      <Button label="Fermer" severity="secondary" @click="$emit('update:visible', false)" />
    </template>

    <RecordPaymentDialog
      v-model:visible="paymentVisible"
      :balance="sale?.balance"
      :sale-date="sale?.created_at"
      :loading="paying"
      @confirm="onPaymentConfirm"
    />
  </Dialog>
</template>

<script setup>
import Button from 'primevue/button'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import Dialog from 'primevue/dialog'
import Tab from 'primevue/tab'
import TabList from 'primevue/tablist'
import TabPanel from 'primevue/tabpanel'
import TabPanels from 'primevue/tabpanels'
import Tabs from 'primevue/tabs'
import Tag from 'primevue/tag'
import { computed, ref, watch } from 'vue'

import RecordPaymentDialog from '@/domains/commerce/components/RecordPaymentDialog.vue'
import { commerceService } from '@/domains/commerce/services/commerceService'
import { usePaymentMethods } from '@/domains/finance/composables/usePaymentMethods'
import { useDisplayFormatters } from '@/domains/shared/composables/useDisplayFormatters'
import { useEntityActions } from '@/domains/shared/composables/useEntityActions'
import { usePrintDocument } from '@/domains/impression/composables/usePrintDocument'
import { useAuthStore } from '@/domains/auth/stores/auth'
import { toIsoDateTime } from '@/domains/shared/services/createCrudService'

const props = defineProps({
  visible: { type: Boolean, default: false },
  sale: { type: Object, default: null }
})

const emit = defineEmits(['update:visible', 'updated'])

const { formatMoney, formatCompactNumber, formatDateTime, formatBuyerLabel } = useDisplayFormatters()
const { resolveMethodLabel } = usePaymentMethods()
const { showSuccess, showError, confirmPopup } = useEntityActions()
const { printDocument } = usePrintDocument()
const authStore = useAuthStore()

const canPrint = computed(
  () => authStore.hasPermission('impression.documents.print') && Boolean(props.sale?.id),
)

const activeTab = ref('articles')
const paymentVisible = ref(false)
const paying = ref(false)
const cancelling = ref(false)

const paymentStatusMap = {
  impaye: { label: 'Impayé', severity: 'danger' },
  partiellement_paye: { label: 'Partiellement payé', severity: 'warn' },
  paye: { label: 'Payé', severity: 'success' },
  annulee: { label: 'Annulée', severity: 'danger' }
}

const isCancelled = computed(() => Boolean(props.sale?.cancelled_at))
const canPay = computed(
  () => !isCancelled.value && props.sale?.facture && Number(props.sale?.balance) > 0
)

const paymentStatusLabel = (status) => paymentStatusMap[status]?.label || status
const paymentStatusSeverity = (status) => paymentStatusMap[status]?.severity || 'secondary'

const refreshSale = async () => {
  if (!props.sale?.id) {
    return
  }
  const updated = await commerceService.getVente(props.sale.id)
  emit('updated', updated)
}

const onPaymentConfirm = async ({ amount, mode_de_paiement_id, paymentDate }) => {
  if (!props.sale?.facture?.id) {
    return
  }

  paying.value = true
  try {
    await commerceService.createPaiement({
      facture_id: props.sale.facture.id,
      amount: String(amount),
      mode_de_paiement_id,
      paid_at: toIsoDateTime(paymentDate)
    })
    paymentVisible.value = false
    await refreshSale()
    showSuccess('Paiement enregistré.')
  } catch (error) {
    showError(error?.message || 'Le paiement a échoué.')
  } finally {
    paying.value = false
  }
}

const printSale = () => {
  if (!props.sale?.id) {
    return
  }
  printDocument('vente_ticket', props.sale.id)
}

const confirmCancel = (event) => {
  confirmPopup({
    event,
    header: 'Annuler la vente',
    message: `Annuler la vente ${props.sale.reference} ? Un avoir sera créé, les paiements actifs seront annulés et le stock sera remis.`,
    acceptLabel: 'Annuler la vente',
    onAccept: async () => {
      cancelling.value = true
      try {
        const updated = await commerceService.cancelVente(props.sale.id)
        emit('updated', updated)
        showSuccess(`Vente ${props.sale.reference} annulée.`)
      } catch (error) {
        showError(error?.message || 'L\'annulation a échoué.')
      } finally {
        cancelling.value = false
      }
    }
  })
}

watch(
  () => props.visible,
  (isVisible) => {
    if (isVisible) {
      activeTab.value = 'articles'
    }
  }
)
</script>

<style scoped>
.sale-detail {
  display: grid;
  gap: 1rem;
}

.sale-detail__meta {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap: 1rem;
}

.line-label-cell {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.sale-detail__label {
  display: block;
  font-size: 0.78rem;
  color: var(--pv-text-muted);
}

.sale-detail__total {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-top: 0.75rem;
  margin-top: 0.5rem;
  border-top: 1px solid color-mix(in srgb, var(--pv-surface-border) 70%, transparent);
  font-size: 1.05rem;
}

.sale-detail__section {
  display: grid;
  gap: 0.75rem;
  margin-bottom: 1.25rem;
}

.sale-detail__section:last-child {
  margin-bottom: 0;
}

.sale-detail__section-title {
  margin: 0;
  font-size: 0.92rem;
  font-weight: 600;
}

.sale-detail__info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap: 1rem;
}

.sale-detail__payment-summary {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 1rem;
}

.sale-detail__amounts {
  display: flex;
  gap: 1.5rem;
}

.sale-detail__empty {
  margin: 0;
  font-size: 0.88rem;
  color: var(--pv-text-muted);
}
</style>
