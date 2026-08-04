<template>
  <Dialog
    :visible="visible"
    header="Réception commande achat"
    modal
    :style="{ width: '32rem' }"
    @update:visible="$emit('update:visible', $event)"
  >
    <div class="recevoir-commande-dialog">
      <p class="recevoir-commande-dialog__total">
        Total commande : <strong>{{ formatMoney(commande?.total_amount) }}</strong>
      </p>

      <div class="recevoir-commande-dialog__field">
        <label for="recevoir-paid-amount">Montant payé à la réception</label>
        <InputNumber
          id="recevoir-paid-amount"
          v-model="paidAmount"
          :min="0"
          :max="maxAmount"
          :min-fraction-digits="0"
          :max-fraction-digits="2"
          mode="decimal"
          fluid
        />
      </div>

      <div v-if="paidAmount > 0" class="recevoir-commande-dialog__field">
        <label for="recevoir-payment-method">Méthode de paiement</label>
        <Select
          id="recevoir-payment-method"
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
      <Button label="Réceptionner" :loading="loading" @click="onConfirm" />
    </template>
  </Dialog>
</template>

<script setup>
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import InputNumber from 'primevue/inputnumber'
import Select from 'primevue/select'
import { computed, ref, watch } from 'vue'

import { usePaymentMethods } from '@/domains/finance/composables/usePaymentMethods'
import { useDisplayFormatters } from '@/domains/shared/composables/useDisplayFormatters'

const props = defineProps({
  visible: { type: Boolean, default: false },
  commande: { type: Object, default: null },
  loading: { type: Boolean, default: false }
})

const emit = defineEmits(['update:visible', 'confirm'])

const { formatMoney } = useDisplayFormatters()
const { methodOptions, loading: methodsLoading, load } = usePaymentMethods({ encashmentOnly: true })

const paidAmount = ref(0)
const methodId = ref(null)

const maxAmount = computed(() => Number(props.commande?.total_amount || 0))

watch(
  () => props.visible,
  async (isVisible) => {
    if (isVisible) {
      await load()
      paidAmount.value = 0
      methodId.value = methodOptions.value[0]?.value || null
    }
  }
)

const onConfirm = () => {
  const value = Number(paidAmount.value || 0)
  if (value < 0 || value > maxAmount.value) {
    return
  }
  if (value > 0 && !methodId.value) {
    return
  }

  emit('confirm', {
    paid_amount: String(value),
    mode_de_paiement_id: value > 0 ? methodId.value : null
  })
}
</script>

<style scoped>
.recevoir-commande-dialog {
  display: grid;
  gap: 1rem;
}

.recevoir-commande-dialog__total {
  margin: 0;
  font-size: 1.05rem;
}

.recevoir-commande-dialog__field {
  display: grid;
  gap: 0.4rem;
}
</style>
