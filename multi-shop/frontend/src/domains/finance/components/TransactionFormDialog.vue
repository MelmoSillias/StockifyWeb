<template>
  <Dialog
    :visible="visible"
    header="Nouvelle transaction"
    modal
    :style="{ width: '34rem' }"
    @update:visible="$emit('update:visible', $event)"
  >
    <div class="transaction-form">
      <div class="transaction-form__field">
        <label for="tx-compte">Compte</label>
        <Select
          id="tx-compte"
          v-model="form.compte_id"
          :options="accountOptions"
          option-label="label"
          option-value="value"
          fluid
        />
      </div>

      <div class="transaction-form__field">
        <label for="tx-type">Type</label>
        <Select
          id="tx-type"
          v-model="form.type"
          :options="typeOptions"
          option-label="label"
          option-value="value"
          fluid
        />
      </div>

      <div class="transaction-form__field">
        <label for="tx-amount">Montant</label>
        <InputNumber
          id="tx-amount"
          v-model="form.amount"
          :min="0"
          :min-fraction-digits="0"
          :max-fraction-digits="2"
          mode="decimal"
          fluid
        />
      </div>

      <div class="transaction-form__field">
        <label for="tx-label">Libellé</label>
        <InputText id="tx-label" v-model="form.label" fluid />
      </div>

      <div class="transaction-form__field">
        <label for="tx-description">Description</label>
        <Textarea id="tx-description" v-model="form.description" rows="3" fluid />
      </div>

      <div class="transaction-form__field">
        <label for="tx-date">Date</label>
        <DatePicker id="tx-date" v-model="form.occurred_at" show-time hour-format="24" show-icon fluid />
      </div>
    </div>

    <template #footer>
      <Button label="Annuler" severity="secondary" text @click="$emit('update:visible', false)" />
      <Button label="Enregistrer" :loading="loading" @click="submit" />
    </template>
  </Dialog>
</template>

<script setup>
import Button from 'primevue/button'
import DatePicker from 'primevue/datepicker'
import Dialog from 'primevue/dialog'
import InputNumber from 'primevue/inputnumber'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Textarea from 'primevue/textarea'
import { computed, reactive, watch } from 'vue'

import { accountTypeLabel } from '@/domains/finance/composables/useFinanceFilters'

const props = defineProps({
  visible: { type: Boolean, default: false },
  accounts: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false }
})

const emit = defineEmits(['update:visible', 'submit'])

const typeOptions = [
  { label: 'Revenu', value: 'revenu' },
  { label: 'Dépense', value: 'depense' }
]

const createEmptyForm = () => ({
  compte_id: null,
  type: 'depense',
  amount: 0,
  label: '',
  description: '',
  occurred_at: new Date()
})

const form = reactive(createEmptyForm())

const accountOptions = computed(() =>
  props.accounts
    .filter((account) => account.is_active)
    .map((account) => ({
      label: `${account.name} (${accountTypeLabel(account.type)})`,
      value: account.id
    }))
)

watch(
  () => props.visible,
  (isVisible) => {
    if (isVisible) {
      Object.assign(form, createEmptyForm())
      form.compte_id = accountOptions.value[0]?.value || null
    }
  }
)

const submit = () => {
  if (!form.compte_id || !form.label || Number(form.amount) <= 0) {
    return
  }

  emit('submit', { ...form })
}
</script>

<style scoped>
.transaction-form {
  display: grid;
  gap: 1rem;
}

.transaction-form__field {
  display: grid;
  gap: 0.35rem;
}
</style>
