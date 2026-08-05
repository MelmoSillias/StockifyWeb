<template>
  <Dialog
    :visible="visible"
    header="Nouvelle dette fournisseur"
    modal
    :style="{ width: '28rem' }"
    @update:visible="$emit('update:visible', $event)"
  >
    <div class="create-dette-dialog">
      <p v-if="fournisseurName" class="create-dette-dialog__supplier">
        Fournisseur : <strong>{{ fournisseurName }}</strong>
      </p>

      <div class="create-dette-dialog__field">
        <label for="dette-label">Libellé</label>
        <InputText id="dette-label" v-model="label" placeholder="Ex: Facture hors commande" fluid />
      </div>

      <div class="create-dette-dialog__field">
        <label for="dette-amount">Montant (XOF)</label>
        <InputNumber
          id="dette-amount"
          v-model="totalAmount"
          :min="0"
          :min-fraction-digits="0"
          :max-fraction-digits="2"
          mode="decimal"
          fluid
        />
      </div>
    </div>

    <template #footer>
      <Button label="Annuler" severity="secondary" text @click="$emit('update:visible', false)" />
      <Button
        label="Enregistrer"
        icon="pi pi-check"
        :loading="loading"
        :disabled="loading || !canSubmit"
        @click="onSubmit"
      />
    </template>
  </Dialog>
</template>

<script setup>
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import InputNumber from 'primevue/inputnumber'
import InputText from 'primevue/inputtext'
import { computed, ref, watch } from 'vue'

const props = defineProps({
  visible: { type: Boolean, default: false },
  fournisseurId: { type: String, default: null },
  fournisseurName: { type: String, default: null },
  loading: { type: Boolean, default: false }
})

const emit = defineEmits(['update:visible', 'submit'])

const label = ref('')
const totalAmount = ref(null)

const canSubmit = computed(() => Boolean(props.fournisseurId) && Number(totalAmount.value) > 0)

const onSubmit = () => {
  if (props.loading) return
  if (!canSubmit.value) {
    return
  }

  emit('submit', {
    fournisseur_id: props.fournisseurId,
    total_amount: String(totalAmount.value),
    label: label.value.trim() || undefined
  })
}

watch(
  () => props.visible,
  (isVisible) => {
    if (isVisible) {
      label.value = ''
      totalAmount.value = null
    }
  }
)
</script>

<style scoped>
.create-dette-dialog {
  display: grid;
  gap: 1rem;
}

.create-dette-dialog__supplier {
  margin: 0;
}

.create-dette-dialog__field {
  display: grid;
  gap: 0.35rem;
}
</style>
