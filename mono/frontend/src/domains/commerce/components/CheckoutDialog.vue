<template>
  <Dialog
    :visible="visible"
    :header="title || dialogTitle"
    modal
    :style="{ width: '32rem' }"
    @update:visible="$emit('update:visible', $event)"
  >
    <div class="checkout-dialog">
      <p class="checkout-dialog__total">
        Total : <strong>{{ formatMoney(total) }}</strong>
      </p>

      <div v-if="isOrderMode" class="checkout-dialog__field">
        <div class="checkout-dialog__row">
          <label for="checkout-confirm-order">Confirmer la commande</label>
          <ToggleSwitch id="checkout-confirm-order" v-model="confirmOrder" />
        </div>
        <small class="checkout-dialog__hint">
          Si activé, la commande est confirmée immédiatement et le stock est réservé.
        </small>
      </div>

      <div v-if="showDeliveryDate" class="checkout-dialog__field">
        <label for="checkout-delivery-date">Date de livraison</label>
        <DatePicker
          id="checkout-delivery-date"
          v-model="deliveryDate"
          date-format="dd/mm/yy"
          show-icon
          :min-date="operationDate"
          fluid
        />
        <small class="checkout-dialog__hint">Date prévue de livraison au client.</small>
      </div>

      <div class="checkout-dialog__field">
        <label for="checkout-operation-date">{{ operationDateLabel }}</label>
        <DatePicker
          id="checkout-operation-date"
          v-model="operationDate"
          show-time
          hour-format="24"
          show-icon
          fluid
        />
      </div>

      <div class="checkout-dialog__field">
        <div class="checkout-dialog__row">
          <label for="checkout-add-payment">Ajouter un paiement</label>
          <ToggleSwitch id="checkout-add-payment" v-model="addPayment" />
        </div>
        <small class="checkout-dialog__hint">{{ paymentHint }}</small>
      </div>

      <template v-if="addPayment">
        <div class="checkout-dialog__field">
          <label for="checkout-payment-date">Date de paiement</label>
          <DatePicker
            id="checkout-payment-date"
            v-model="paymentDate"
            show-time
            hour-format="24"
            show-icon
            :min-date="operationDate"
            fluid
          />
        </div>
        <div class="checkout-dialog__field">
          <label for="checkout-amount">Montant</label>
          <InputNumber
            id="checkout-amount"
            v-model="amount"
            :min="0"
            :min-fraction-digits="0"
            :max-fraction-digits="2"
            mode="decimal"
            fluid
          />
        </div>
        <div class="checkout-dialog__field">
          <label for="checkout-method">Méthode</label>
          <Select
            id="checkout-method"
            v-model="methodId"
            :options="methodOptions"
            option-label="label"
            option-value="value"
            fluid
          />
        </div>
      </template>
    </div>

    <template #footer>
      <Button label="Annuler" severity="secondary" text @click="$emit('update:visible', false)" />
      <Button
        :label="confirmLabel || dialogConfirmLabel"
        :loading="loading"
        :disabled="showDeliveryDate && !deliveryDate"
        @click="onConfirm"
      />
    </template>
  </Dialog>
</template>

<script setup>
import Button from 'primevue/button'
import DatePicker from 'primevue/datepicker'
import Dialog from 'primevue/dialog'
import InputNumber from 'primevue/inputnumber'
import Select from 'primevue/select'
import ToggleSwitch from 'primevue/toggleswitch'
import { computed, ref, watch } from 'vue'

import { usePaymentMethods } from '@/domains/finance/composables/usePaymentMethods'
import { useDisplayFormatters } from '@/domains/shared/composables/useDisplayFormatters'

const props = defineProps({
  visible: { type: Boolean, default: false },
  mode: { type: String, default: 'sale' },
  title: { type: String, default: 'Encaisser' },
  confirmLabel: { type: String, default: 'Valider' },
  total: { type: Number, default: 0 },
  loading: { type: Boolean, default: false },
  paymentHint: {
    type: String,
    default: 'Enregistrer un paiement lié à cette opération sans changer de page.'
  }
})

const emit = defineEmits(['update:visible', 'confirm'])

const { formatMoney } = useDisplayFormatters()
const { methodOptions, load } = usePaymentMethods({ encashmentOnly: true })

const addPayment = ref(false)
const amount = ref(0)
const methodId = ref(null)
const operationDate = ref(new Date())
const paymentDate = ref(new Date())
const deliveryDate = ref(null)
const confirmOrder = ref(true)

const isOrderMode = computed(() => props.mode === 'order')
const showDeliveryDate = computed(() => isOrderMode.value && confirmOrder.value)

const dialogTitle = computed(() => {
  if (props.mode === 'sale') {
    return 'Encaisser la vente'
  }
  return 'Commande'
})

const dialogConfirmLabel = computed(() => {
  if (props.mode === 'sale') {
    return 'Valider la vente'
  }
  return confirmOrder.value ? 'Confirmer la commande' : 'Créer la commande'
})

const operationDateLabel = computed(() =>
  props.mode === 'order' ? 'Date de la commande' : 'Date de la vente'
)

const defaultDeliveryDate = () => {
  const date = new Date()
  date.setDate(date.getDate() + 7)
  date.setHours(0, 0, 0, 0)
  return date
}

const ensurePaymentDate = () => {
  if (!operationDate.value || !paymentDate.value) {
    return
  }

  if (paymentDate.value.getTime() < operationDate.value.getTime()) {
    paymentDate.value = new Date(operationDate.value)
  }
}

watch(operationDate, ensurePaymentDate)
watch(paymentDate, ensurePaymentDate)
watch(addPayment, (enabled) => {
  if (enabled) {
    ensurePaymentDate()
  }
})

watch(
  () => props.visible,
  async (isVisible) => {
    if (isVisible) {
      await load()
      const now = new Date()
      addPayment.value = false
      amount.value = Number(props.total || 0)
      methodId.value = methodOptions.value[0]?.value || null
      operationDate.value = now
      paymentDate.value = new Date(now)
      confirmOrder.value = true
      deliveryDate.value = defaultDeliveryDate()
    }
  }
)

const onConfirm = () => {
  ensurePaymentDate()

  if (showDeliveryDate.value && !deliveryDate.value) {
    return
  }

  emit('confirm', {
    operationDate: operationDate.value,
    paymentDate: paymentDate.value,
    deliveryDate: confirmOrder.value ? deliveryDate.value : null,
    confirmOrder: isOrderMode.value ? confirmOrder.value : false,
    payment: addPayment.value
      ? { amount: Number(amount.value || 0), mode_de_paiement_id: methodId.value }
      : null
  })
}
</script>

<style scoped>
.checkout-dialog {
  display: grid;
  gap: 1rem;
}

.checkout-dialog__total {
  margin: 0;
  font-size: 1.05rem;
}

.checkout-dialog__field {
  display: grid;
  gap: 0.4rem;
}

.checkout-dialog__row {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.checkout-dialog__hint {
  color: var(--pv-text-muted);
}
</style>
