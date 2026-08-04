<template>
  <Dialog
    :visible="visible"
    :header="dialogTitle"
    modal
    :style="{ width: '40rem' }"
    @update:visible="$emit('update:visible', $event)"
  >
    <div class="free-operation-dialog">
      <div class="free-operation-dialog__section">
        <label class="free-operation-dialog__label">Acheteur</label>
        <AcheteurSelector
          :model-value="acheteur"
          :clients="clients"
          :clients-loading="clientsLoading"
          @update:model-value="$emit('update:acheteur', $event)"
        />
      </div>

      <div class="free-operation-dialog__section">
        <label class="free-operation-dialog__label">Nouvelle ligne</label>
        <div class="free-operation-dialog__line-form">
          <InputText
            v-model="draftLabel"
            placeholder="Nom / description"
            fluid
          />
          <InputNumber
            v-model="draftQuantity"
            :min="0"
            :min-fraction-digits="0"
            :max-fraction-digits="3"
            mode="decimal"
            placeholder="Qté"
            fluid
          />
          <InputNumber
            v-model="draftUnitPrice"
            :min="0"
            :min-fraction-digits="0"
            :max-fraction-digits="2"
            mode="decimal"
            placeholder="Prix unitaire"
            fluid
          />
          <Button
            label="Ajouter"
            icon="pi pi-plus"
            :disabled="!canAddLine"
            @click="addDraftLine"
          />
        </div>
      </div>

      <div v-if="lines.length > 0" class="free-operation-dialog__section">
        <DataTable :value="lines" data-key="id" size="small" striped-rows>
          <Column header="Libellé">
            <template #body="{ data }">{{ data.label }}</template>
          </Column>
          <Column header="Qté" style="width: 80px">
            <template #body="{ data }">{{ data.quantity }}</template>
          </Column>
          <Column header="P.U." style="width: 110px">
            <template #body="{ data }">{{ formatMoney(data.unitPrice) }}</template>
          </Column>
          <Column header="Total" style="width: 110px">
            <template #body="{ data }">{{ formatMoney(data.quantity * data.unitPrice) }}</template>
          </Column>
          <Column style="width: 56px">
            <template #body="{ data }">
              <Button
                icon="pi pi-times"
                text
                rounded
                severity="danger"
                @click="removeLine(data.id)"
              />
            </template>
          </Column>
        </DataTable>
      </div>

      <p class="free-operation-dialog__total">
        Total : <strong>{{ formatMoney(total) }}</strong>
      </p>

      <Divider />

      <div v-if="isOrderMode" class="free-operation-dialog__field">
        <div class="free-operation-dialog__row">
          <label for="free-op-confirm-order">Confirmer la commande</label>
          <ToggleSwitch id="free-op-confirm-order" v-model="confirmOrder" />
        </div>
      </div>

      <div v-if="showDeliveryDate" class="free-operation-dialog__field">
        <label for="free-op-delivery-date">Date de livraison</label>
        <DatePicker
          id="free-op-delivery-date"
          v-model="deliveryDate"
          date-format="dd/mm/yy"
          show-icon
          :min-date="operationDate"
          fluid
        />
      </div>

      <div v-if="isQuoteMode" class="free-operation-dialog__field">
        <label for="free-op-valid-until">Valable jusqu'au</label>
        <DatePicker
          id="free-op-valid-until"
          v-model="validUntil"
          date-format="dd/mm/yy"
          show-icon
          :min-date="operationDate"
          fluid
        />
      </div>

      <div class="free-operation-dialog__field">
        <label for="free-op-operation-date">{{ operationDateLabel }}</label>
        <DatePicker
          id="free-op-operation-date"
          v-model="operationDate"
          :show-time="!isQuoteMode"
          hour-format="24"
          show-icon
          fluid
        />
      </div>

      <template v-if="!isQuoteMode">
        <div class="free-operation-dialog__field">
          <div class="free-operation-dialog__row">
            <label for="free-op-add-payment">Ajouter un paiement</label>
            <ToggleSwitch id="free-op-add-payment" v-model="addPayment" />
          </div>
        </div>

        <template v-if="addPayment">
          <div class="free-operation-dialog__field">
            <label for="free-op-payment-date">Date de paiement</label>
            <DatePicker
              id="free-op-payment-date"
              v-model="paymentDate"
              show-time
              hour-format="24"
              show-icon
              :min-date="operationDate"
              fluid
            />
          </div>
          <div class="free-operation-dialog__field">
            <label for="free-op-amount">Montant</label>
            <InputNumber
              id="free-op-amount"
              v-model="amount"
              :min="0"
              :min-fraction-digits="0"
              :max-fraction-digits="2"
              mode="decimal"
              fluid
            />
          </div>
          <div class="free-operation-dialog__field">
            <label for="free-op-method">Méthode</label>
            <Select
              id="free-op-method"
              v-model="methodId"
              :options="methodOptions"
              option-label="label"
              option-value="value"
              fluid
            />
          </div>
        </template>
      </template>
    </div>

    <template #footer>
      <Button label="Annuler" severity="secondary" text @click="$emit('update:visible', false)" />
      <Button
        :label="confirmLabel"
        icon="pi pi-check"
        :loading="loading"
        :disabled="lines.length === 0 || (showDeliveryDate && !deliveryDate) || (isQuoteMode && !validUntil)"
        @click="onSubmit"
      />
    </template>
  </Dialog>
</template>

<script setup>
import Button from 'primevue/button'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import DatePicker from 'primevue/datepicker'
import Dialog from 'primevue/dialog'
import Divider from 'primevue/divider'
import InputNumber from 'primevue/inputnumber'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import ToggleSwitch from 'primevue/toggleswitch'
import { computed, ref, watch } from 'vue'

import AcheteurSelector from '@/domains/commerce/components/AcheteurSelector.vue'
import { usePaymentMethods } from '@/domains/finance/composables/usePaymentMethods'
import { useDisplayFormatters } from '@/domains/shared/composables/useDisplayFormatters'

const props = defineProps({
  visible: { type: Boolean, default: false },
  mode: { type: String, default: 'sale' },
  lines: { type: Array, default: () => [] },
  acheteur: { type: Object, required: true },
  total: { type: Number, default: 0 },
  loading: { type: Boolean, default: false },
  clients: { type: Array, default: () => [] },
  clientsLoading: { type: Boolean, default: false }
})

const emit = defineEmits(['update:visible', 'update:lines', 'update:acheteur', 'submit'])

const { formatMoney } = useDisplayFormatters()
const { methodOptions, load } = usePaymentMethods({ encashmentOnly: true })

const draftLabel = ref('')
const draftQuantity = ref(1)
const draftUnitPrice = ref(null)
const addPayment = ref(false)
const amount = ref(0)
const methodId = ref(null)
const operationDate = ref(new Date())
const paymentDate = ref(new Date())
const deliveryDate = ref(null)
const validUntil = ref(null)
const confirmOrder = ref(true)

const isOrderMode = computed(() => props.mode === 'order')
const isQuoteMode = computed(() => props.mode === 'quote')
const showDeliveryDate = computed(() => isOrderMode.value && confirmOrder.value)

const dialogTitle = computed(() => {
  if (props.mode === 'quote') return 'Opération libre — Devis'
  if (props.mode === 'order') return 'Opération libre — Commande'
  return 'Opération libre — Vente'
})

const confirmLabel = computed(() => {
  if (props.mode === 'quote') return 'Enregistrer le devis'
  if (props.mode === 'sale') return 'Valider la vente'
  return confirmOrder.value ? 'Confirmer la commande' : 'Créer la commande'
})

const operationDateLabel = computed(() => {
  if (props.mode === 'quote') return 'Date du devis'
  return props.mode === 'order' ? 'Date de la commande' : 'Date de la vente'
})

const canAddLine = computed(() =>
  String(draftLabel.value || '').trim().length > 0
  && Number(draftQuantity.value) > 0
  && Number(draftUnitPrice.value) >= 0
  && draftUnitPrice.value !== null
  && draftUnitPrice.value !== ''
)

const defaultDeliveryDate = () => {
  const date = new Date()
  date.setDate(date.getDate() + 7)
  date.setHours(0, 0, 0, 0)
  return date
}

const defaultValidUntil = () => {
  const date = new Date()
  date.setDate(date.getDate() + 30)
  date.setHours(0, 0, 0, 0)
  return date
}

const createLineId = () => {
  if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
    return crypto.randomUUID()
  }
  return `free-${Date.now()}-${Math.random().toString(36).slice(2, 9)}`
}

const addDraftLine = () => {
  if (!canAddLine.value) return

  emit('update:lines', [
    ...props.lines,
    {
      id: createLineId(),
      label: String(draftLabel.value).trim(),
      quantity: Number(draftQuantity.value),
      unitPrice: Number(draftUnitPrice.value)
    }
  ])

  draftLabel.value = ''
  draftQuantity.value = 1
  draftUnitPrice.value = null
}

const removeLine = (lineId) => {
  emit(
    'update:lines',
    props.lines.filter((line) => line.id !== lineId)
  )
}

const onSubmit = () => {
  emit('submit', {
    operationDate: operationDate.value,
    paymentDate: paymentDate.value,
    deliveryDate: confirmOrder.value ? deliveryDate.value : null,
    validUntil: validUntil.value,
    confirmOrder: isOrderMode.value ? confirmOrder.value : false,
    payment: addPayment.value
      ? { amount: Number(amount.value || 0), mode_de_paiement_id: methodId.value }
      : null
  })
}

watch(
  () => props.visible,
  async (isVisible) => {
    if (isVisible) {
      if (!isQuoteMode.value) {
        await load()
      }
      const now = new Date()
      addPayment.value = false
      amount.value = Number(props.total || 0)
      methodId.value = methodOptions.value[0]?.value || null
      operationDate.value = now
      paymentDate.value = new Date(now)
      confirmOrder.value = true
      deliveryDate.value = defaultDeliveryDate()
      validUntil.value = defaultValidUntil()
      draftLabel.value = ''
      draftQuantity.value = 1
      draftUnitPrice.value = null
    }
  }
)

watch(
  () => props.total,
  (value) => {
    if (props.visible && !addPayment.value) {
      amount.value = Number(value || 0)
    }
  }
)
</script>

<style scoped>
.free-operation-dialog {
  display: grid;
  gap: 1rem;
}

.free-operation-dialog__section,
.free-operation-dialog__field {
  display: grid;
  gap: 0.5rem;
}

.free-operation-dialog__label {
  font-size: 0.85rem;
  color: var(--pv-text-muted);
}

.free-operation-dialog__line-form {
  display: grid;
  grid-template-columns: 1.5fr 0.7fr 0.9fr auto;
  gap: 0.5rem;
  align-items: end;
}

.free-operation-dialog__total {
  margin: 0;
  font-size: 1.05rem;
}

.free-operation-dialog__row {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

@media (max-width: 767px) {
  .free-operation-dialog__line-form {
    grid-template-columns: 1fr;
  }
}
</style>
