<template>
  <Dialog
    :visible="visible"
    header="Confirmer la commande"
    modal
    :style="{ width: '28rem' }"
    @update:visible="$emit('update:visible', $event)"
  >
    <div class="order-confirm-dialog">
      <p v-if="reference" class="order-confirm-dialog__ref">
        Commande <strong>{{ reference }}</strong>
      </p>

      <div class="order-confirm-dialog__field">
        <label for="order-delivery-date">Date de livraison</label>
        <DatePicker
          id="order-delivery-date"
          v-model="deliveryDate"
          date-format="dd/mm/yy"
          show-icon
          fluid
        />
        <small class="order-confirm-dialog__hint">Date prévue de livraison au client.</small>
      </div>
    </div>

    <template #footer>
      <Button label="Annuler" severity="secondary" text @click="$emit('update:visible', false)" />
      <Button label="Confirmer" icon="pi pi-check" :loading="loading" :disabled="!deliveryDate" @click="onConfirm" />
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

const deliveryDate = ref(null)

const defaultDeliveryDate = () => {
  const date = new Date()
  date.setDate(date.getDate() + 7)
  date.setHours(0, 0, 0, 0)
  return date
}

watch(
  () => props.visible,
  (isVisible) => {
    if (isVisible) {
      deliveryDate.value = defaultDeliveryDate()
    }
  }
)

const onConfirm = () => {
  if (!deliveryDate.value) {
    return
  }

  emit('confirm', { deliveryDate: deliveryDate.value })
}
</script>

<style scoped>
.order-confirm-dialog {
  display: grid;
  gap: 1rem;
}

.order-confirm-dialog__ref {
  margin: 0;
}

.order-confirm-dialog__field {
  display: grid;
  gap: 0.4rem;
}

.order-confirm-dialog__hint {
  color: var(--pv-text-muted);
}
</style>
