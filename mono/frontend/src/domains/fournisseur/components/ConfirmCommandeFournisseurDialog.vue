<template>
  <Dialog
    :visible="visible"
    header="Confirmer la commande achat"
    modal
    :style="{ width: '28rem' }"
    @update:visible="$emit('update:visible', $event)"
  >
    <div class="confirm-commande-dialog">
      <p v-if="reference" class="confirm-commande-dialog__ref">
        Commande <strong>{{ reference }}</strong>
      </p>

      <div class="confirm-commande-dialog__field">
        <label for="achat-expected-date">Date de réception prévue</label>
        <DatePicker
          id="achat-expected-date"
          v-model="expectedDate"
          date-format="dd/mm/yy"
          show-icon
          fluid
        />
        <small class="confirm-commande-dialog__hint">Date estimée de livraison par le fournisseur.</small>
      </div>
    </div>

    <template #footer>
      <Button label="Annuler" severity="secondary" text @click="$emit('update:visible', false)" />
      <Button label="Confirmer" icon="pi pi-check" :loading="loading" @click="onConfirm" />
    </template>
  </Dialog>
</template>

<script setup>
import Button from 'primevue/button'
import DatePicker from 'primevue/datepicker'
import Dialog from 'primevue/dialog'
import { ref, watch } from 'vue'

const props = defineProps({
  visible: { type: Boolean, default: false },
  reference: { type: String, default: null },
  loading: { type: Boolean, default: false }
})

const emit = defineEmits(['update:visible', 'confirm'])

const expectedDate = ref(null)

const defaultExpectedDate = () => {
  const date = new Date()
  date.setDate(date.getDate() + 7)
  date.setHours(0, 0, 0, 0)
  return date
}

watch(
  () => props.visible,
  (isVisible) => {
    if (isVisible) {
      expectedDate.value = defaultExpectedDate()
    }
  }
)

const onConfirm = () => {
  emit('confirm', { expectedDate: expectedDate.value })
}
</script>

<style scoped>
.confirm-commande-dialog {
  display: grid;
  gap: 1rem;
}

.confirm-commande-dialog__ref {
  margin: 0;
}

.confirm-commande-dialog__field {
  display: grid;
  gap: 0.4rem;
}

.confirm-commande-dialog__hint {
  color: var(--p-text-muted-color);
}
</style>
