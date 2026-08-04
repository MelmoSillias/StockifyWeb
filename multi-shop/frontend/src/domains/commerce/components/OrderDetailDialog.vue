<template>
  <Dialog
    :visible="visible"
    header="Détail de la commande"
    modal
    :style="{ width: '48rem' }"
    @update:visible="$emit('update:visible', $event)"
  >
    <div v-if="order" class="order-detail">
      <div class="order-detail__meta">
        <div>
          <span class="order-detail__label">Référence</span>
          <strong>{{ order.reference }}</strong>
        </div>
        <div>
          <span class="order-detail__label">Acheteur</span>
          <span>{{ formatBuyerLabel(order.acheteur) }}</span>
        </div>
        <div>
          <span class="order-detail__label">Statut</span>
          <Tag :value="statusLabel(order.status)" :severity="statusSeverity(order.status)" rounded />
        </div>
        <div v-if="order.delivery_date">
          <span class="order-detail__label">Livraison prévue</span>
          <span>{{ formatDate(order.delivery_date) }}</span>
        </div>
      </div>

      <Tabs v-model:value="activeTab">
        <TabList>
          <Tab value="articles">Articles</Tab>
          <Tab value="billing">Facturation</Tab>
        </TabList>

        <TabPanels>
          <TabPanel value="articles">
            <DataTable :value="order.lines || []" size="small" striped-rows>
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

            <div class="order-detail__total">
              <span>Total</span>
              <strong>{{ formatMoney(order.total_amount) }}</strong>
            </div>
          </TabPanel>

          <TabPanel value="billing">
            <div v-if="order.facture" class="order-detail__section">
              <h4 class="order-detail__section-title">Facture</h4>
              <div class="order-detail__info-grid">
                <div>
                  <span class="order-detail__label">Numéro</span>
                  <span>{{ order.facture.numero }}</span>
                </div>
                <div>
                  <span class="order-detail__label">Date d'émission</span>
                  <span>{{ formatDateTime(order.facture.issued_at) }}</span>
                </div>
                <div>
                  <span class="order-detail__label">Montant</span>
                  <span>{{ formatMoney(order.facture.total_amount) }}</span>
                </div>
              </div>
            </div>
            <p v-else-if="order.status === 'initiee'" class="order-detail__empty">
              La facture sera générée à la confirmation. Les acomptes sont enregistrés sur la commande.
            </p>
            <p v-else class="order-detail__empty">Aucune facture associée.</p>

            <div class="order-detail__section">
              <h4 class="order-detail__section-title">État du paiement</h4>
              <div class="order-detail__payment-summary">
                <Tag
                  :value="paymentStatusLabel(order.payment_status)"
                  :severity="paymentStatusSeverity(order.payment_status)"
                  rounded
                />
                <div v-if="!isCancelled" class="order-detail__amounts">
                  <div>
                    <span class="order-detail__label">Payé</span>
                    <span>{{ formatMoney(order.paid_amount) }}</span>
                  </div>
                  <div v-if="Number(order.deposit_received) > 0">
                    <span class="order-detail__label">Acompte</span>
                    <span>{{ formatMoney(order.deposit_received) }}</span>
                  </div>
                  <div>
                    <span class="order-detail__label">Reste</span>
                    <strong>{{ formatMoney(order.balance) }}</strong>
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

            <div class="order-detail__section">
              <h4 class="order-detail__section-title">Paiements</h4>
              <DataTable
                v-if="order.paiements?.length"
                :value="order.paiements"
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
              <p v-else class="order-detail__empty">Aucun paiement enregistré.</p>
            </div>
          </TabPanel>
        </TabPanels>
      </Tabs>
    </div>

    <template #footer>
      <Button label="Fermer" severity="secondary" @click="$emit('update:visible', false)" />
    </template>

    <RecordPaymentDialog
      v-model:visible="paymentVisible"
      :balance="order?.balance"
      :sale-date="paymentReferenceDate"
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
import { toIsoDateTime } from '@/domains/shared/services/createCrudService'

const props = defineProps({
  visible: { type: Boolean, default: false },
  order: { type: Object, default: null }
})

const emit = defineEmits(['update:visible', 'updated'])

const { formatMoney, formatCompactNumber, formatDateTime, formatDate, formatBuyerLabel } = useDisplayFormatters()
const { resolveMethodLabel } = usePaymentMethods()
const { showSuccess, showError } = useEntityActions()

const activeTab = ref('articles')
const paymentVisible = ref(false)
const paying = ref(false)

const paymentStatusMap = {
  impaye: { label: 'Impayé', severity: 'danger' },
  partiellement_paye: { label: 'Partiellement payé', severity: 'warn' },
  paye: { label: 'Payé', severity: 'success' },
  annulee: { label: 'Annulée', severity: 'danger' }
}

const statusMap = {
  initiee: { label: 'Initiée', severity: 'warn' },
  confirmee: { label: 'Confirmée', severity: 'info' },
  partiellement_livree: { label: 'Part. livrée', severity: 'info' },
  livree: { label: 'Livrée', severity: 'success' },
  annulee: { label: 'Annulée', severity: 'danger' }
}

const statusLabel = (status) => statusMap[status]?.label || status
const statusSeverity = (status) => statusMap[status]?.severity || 'secondary'

const isCancelled = computed(() => props.order?.status === 'annulee' || Boolean(props.order?.cancelled_at))
const paymentReferenceDate = computed(() => props.order?.confirmed_at || props.order?.created_at)
const canPay = computed(() => {
  if (isCancelled.value || Number(props.order?.balance) <= 0) {
    return false
  }
  if (props.order?.status === 'initiee') {
    return true
  }
  return Boolean(props.order?.facture?.id)
})

const paymentStatusLabel = (status) => paymentStatusMap[status]?.label || status
const paymentStatusSeverity = (status) => paymentStatusMap[status]?.severity || 'secondary'

const refreshOrder = async () => {
  if (!props.order?.id) {
    return
  }
  const updated = await commerceService.getCommande(props.order.id)
  emit('updated', updated)
}

const onPaymentConfirm = async ({ amount, mode_de_paiement_id, paymentDate }) => {
  if (!props.order?.id) {
    return
  }

  paying.value = true
  try {
    const payload = {
      amount: String(amount),
      mode_de_paiement_id,
      paid_at: toIsoDateTime(paymentDate)
    }

    if (props.order.status === 'initiee') {
      payload.commande_id = props.order.id
    } else if (props.order.facture?.id) {
      payload.facture_id = props.order.facture.id
    } else {
      return
    }

    await commerceService.createPaiement(payload)
    paymentVisible.value = false
    await refreshOrder()
    showSuccess('Paiement enregistré.')
  } catch (error) {
    showError(error?.message || 'Le paiement a échoué.')
  } finally {
    paying.value = false
  }
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
.order-detail {
  display: grid;
  gap: 1rem;
}

.order-detail__meta {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap: 1rem;
}

.line-label-cell {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.order-detail__label {
  display: block;
  font-size: 0.78rem;
  color: var(--pv-text-muted);
}

.order-detail__total {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-top: 0.75rem;
  margin-top: 0.5rem;
  border-top: 1px solid color-mix(in srgb, var(--pv-surface-border) 70%, transparent);
  font-size: 1.05rem;
}

.order-detail__section {
  display: grid;
  gap: 0.75rem;
  margin-bottom: 1.25rem;
}

.order-detail__section:last-child {
  margin-bottom: 0;
}

.order-detail__section-title {
  margin: 0;
  font-size: 0.92rem;
  font-weight: 600;
}

.order-detail__info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap: 1rem;
}

.order-detail__payment-summary {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 1rem;
}

.order-detail__amounts {
  display: flex;
  gap: 1.5rem;
  flex-wrap: wrap;
}

.order-detail__empty {
  margin: 0;
  font-size: 0.88rem;
  color: var(--pv-text-muted);
}
</style>
