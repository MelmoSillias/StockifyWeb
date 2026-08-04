<template>
  <Dialog
    :visible="visible"
    header="Décaisser"
    modal
    :style="{ width: '32rem' }"
    @update:visible="$emit('update:visible', $event)"
  >
    <div class="record-supplier-payment-dialog">
      <p class="record-supplier-payment-dialog__balance">
        Solde restant : <strong>{{ formatMoney(balance) }}</strong>
      </p>

      <div class="record-supplier-payment-dialog__field">
        <label for="record-supplier-payment-date">Date de paiement</label>
        <DatePicker
          id="record-supplier-payment-date"
          v-model="paymentDate"
          show-time
          hour-format="24"
          show-icon
          :min-date="minDate"
          fluid
        />
      </div>

      <div class="record-supplier-payment-dialog__field">
        <label for="record-supplier-payment-amount">Montant</label>
        <InputNumber
          id="record-supplier-payment-amount"
          v-model="amount"
          :min="0"
          :max="maxAmount"
          :min-fraction-digits="0"
          :max-fraction-digits="2"
          mode="decimal"
          fluid
        />
      </div>

      <div class="record-supplier-payment-dialog__field">
        <label for="record-supplier-payment-method">Méthode</label>
        <Select
          id="record-supplier-payment-method"
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
      <Button label="Décaisser" :loading="loading" @click="onConfirm" />
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
  issuedAt: { type: String, default: null },
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
  if (!props.issuedAt) {
    return undefined
  }
  return new Date(props.issuedAt)
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
.record-supplier-payment-dialog {
  display: grid;
  gap: 1rem;
}

.record-supplier-payment-dialog__balance {
  margin: 0;
  font-size: 1.05rem;
}

.record-supplier-payment-dialog__field {
  display: grid;
  gap: 0.4rem;
}
</style>
