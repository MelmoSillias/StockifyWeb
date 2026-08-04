<template>
  <Dialog
    :visible="visible"
    header="Encaisser"
    modal
    :style="{ width: '32rem' }"
    @update:visible="$emit('update:visible', $event)"
  >
    <div class="record-payment-dialog">
      <p class="record-payment-dialog__balance">
        Solde restant : <strong>{{ formatMoney(balance) }}</strong>
      </p>

      <div class="record-payment-dialog__field">
        <label for="record-payment-date">Date de paiement</label>
        <DatePicker
          id="record-payment-date"
          v-model="paymentDate"
          show-time
          hour-format="24"
          show-icon
          :min-date="minDate"
          fluid
        />
      </div>

      <div class="record-payment-dialog__field">
        <label for="record-payment-amount">Montant</label>
        <InputNumber
          id="record-payment-amount"
          v-model="amount"
          :min="0"
          :max="maxAmount"
          :min-fraction-digits="0"
          :max-fraction-digits="2"
          mode="decimal"
          fluid
        />
      </div>

      <div class="record-payment-dialog__field">
        <label for="record-payment-method">Méthode</label>
        <Select
          id="record-payment-method"
          v-model="methodId"
          :options="methodOptions"
          option-label="label"
          option-value="value"
          :loading="methodsLoading"
          fluid
        />
      </div>
    </div>

    <template #footer>
      <Button label="Annuler" severity="secondary" text @click="$emit('update:visible', false)" />
      <Button label="Encaisser" :loading="loading" @click="onConfirm" />
    </template>
  </Dialog>
</template>

<script setup>
import Button from 'primevue/button'
import DatePicker from 'primevue/datepicker'
import Dialog from 'primevue/dialog'
import InputNumber from 'primevue/inputnumber'
import Select from 'primevue/select'
import { computed, ref, watch } from 'vue'

import { usePaymentMethods } from '@/domains/finance/composables/usePaymentMethods'
import { useDisplayFormatters } from '@/domains/shared/composables/useDisplayFormatters'

const props = defineProps({
  visible: { type: Boolean, default: false },
  balance: { type: [String, Number], default: '0' },
  saleDate: { type: String, default: null },
  loading: { type: Boolean, default: false }
})

const emit = defineEmits(['update:visible', 'confirm'])

const { formatMoney } = useDisplayFormatters()
const { methodOptions, loading: methodsLoading, load } = usePaymentMethods({ encashmentOnly: true })

const amount = ref(0)
const methodId = ref(null)
const paymentDate = ref(new Date())

const maxAmount = computed(() => Number(props.balance || 0))

const minDate = computed(() => {
  if (!props.saleDate) {
    return undefined
  }
  return new Date(props.saleDate)
})

watch(
  () => props.visible,
  async (isVisible) => {
    if (isVisible) {
      await load()
      const now = new Date()
      amount.value = maxAmount.value
      methodId.value = methodOptions.value[0]?.value || null
      paymentDate.value = now
      if (minDate.value && paymentDate.value.getTime() < minDate.value.getTime()) {
        paymentDate.value = new Date(minDate.value)
      }
    }
  }
)

watch(paymentDate, () => {
  if (minDate.value && paymentDate.value?.getTime() < minDate.value.getTime()) {
    paymentDate.value = new Date(minDate.value)
  }
})

const onConfirm = () => {
  const value = Number(amount.value || 0)
  if (value <= 0 || !methodId.value) {
    return
  }

  emit('confirm', {
    amount: value,
    mode_de_paiement_id: methodId.value,
    paymentDate: paymentDate.value
  })
}
</script>

<style scoped>
.record-payment-dialog {
  display: grid;
  gap: 1rem;
}

.record-payment-dialog__balance {
  margin: 0;
  font-size: 1.05rem;
}

.record-payment-dialog__field {
  display: grid;
  gap: 0.4rem;
}
</style>
