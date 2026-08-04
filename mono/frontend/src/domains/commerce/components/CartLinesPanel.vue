<template>
  <div class="cart-lines">
    <DataView :value="lines" data-key="variantId" class="cart-lines__view">
      <template #empty>
        <div class="cart-lines__empty">
          <i class="pi pi-shopping-cart" />
          <p>Le panier est vide. Ajoutez des variantes depuis la colonne de gauche.</p>
        </div>
      </template>
      <template #list="slotProps">
        <div class="cart-lines__list">
          <div v-for="line in slotProps.items" :key="line.variantId" class="cart-lines__item">
            <div class="cart-lines__item-header">
              <span class="cart-lines__item-label">{{ line.label }}</span>
              <Button
                icon="pi pi-times"
                text
                rounded
                severity="danger"
                :disabled="disabled"
                @click="$emit('remove', line.variantId)"
              />
            </div>
            <div class="cart-lines__item-controls">
              <div class="cart-lines__control">
                <span class="cart-lines__control-label">Qté</span>
                <InputNumber
                  :model-value="line.quantity"
                  :min="0"
                  :min-fraction-digits="0"
                  :max-fraction-digits="3"
                  mode="decimal"
                  fluid
                  :disabled="disabled"
                  @update:model-value="$emit('update-quantity', line.variantId, $event)"
                />
              </div>
              <div class="cart-lines__control">
                <span class="cart-lines__control-label">Prix</span>
                <InputNumber
                  :model-value="line.unitPrice"
                  :min="0"
                  :min-fraction-digits="0"
                  :max-fraction-digits="2"
                  mode="decimal"
                  fluid
                  :disabled="disabled"
                  @update:model-value="$emit('update-price', line.variantId, $event)"
                />
              </div>
              <div class="cart-lines__subtotal">
                <span class="cart-lines__control-label">Sous-total</span>
                <strong>{{ formatMoney(line.quantity * line.unitPrice) }}</strong>
              </div>
            </div>
          </div>
        </div>
      </template>
    </DataView>
  </div>
</template>

<script setup>
import Button from 'primevue/button'
import DataView from 'primevue/dataview'
import InputNumber from 'primevue/inputnumber'

import { useDisplayFormatters } from '@/domains/shared/composables/useDisplayFormatters'

defineProps({
  lines: {
    type: Array,
    default: () => []
  },
  disabled: {
    type: Boolean,
    default: false
  }
})

defineEmits(['remove', 'update-quantity', 'update-price'])

const { formatMoney } = useDisplayFormatters()
</script>

<style scoped>
.cart-lines {
  min-width: 0;
}

.cart-lines__list {
  display: grid;
  gap: 0.75rem;
  min-width: 0;
}

.cart-lines__item {
  display: grid;
  gap: 0.75rem;
  padding: 1rem;
  border: 1px solid color-mix(in srgb, var(--pv-surface-border) 70%, transparent);
  border-radius: 1rem;
  background: var(--pv-surface-bg);
  min-width: 0;
  overflow: hidden;
}

.cart-lines__item-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 0.5rem;
  min-width: 0;
}

.cart-lines__item-label {
  font-weight: 600;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  line-height: 1.35;
  min-width: 0;
  flex: 1;
}

.cart-lines__item-controls {
  display: grid;
  grid-template-columns: 1fr 1fr auto;
  gap: 0.75rem;
  align-items: end;
  min-width: 0;
}

.cart-lines__control,
.cart-lines__subtotal {
  display: grid;
  gap: 0.3rem;
  min-width: 0;
}

.cart-lines__control :deep(.p-inputnumber),
.cart-lines__control :deep(.p-inputnumber-input) {
  width: 100%;
  min-width: 0;
}

.cart-lines__control-label {
  font-size: 0.78rem;
  color: var(--pv-text-muted);
}

.cart-lines__subtotal {
  text-align: right;
  align-self: center;
}

@media (max-width: 767px) {
  .cart-lines__item {
    padding: 0.65rem;
    gap: 0.45rem;
  }

  .cart-lines__item-controls {
    grid-template-columns: 1fr;
    gap: 0.4rem;
  }

  .cart-lines__control {
    grid-template-columns: auto 1fr;
    align-items: center;
    gap: 0.5rem;
  }

  .cart-lines__subtotal {
    grid-column: auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    text-align: left;
    padding-top: 0.35rem;
    border-top: 1px solid color-mix(in srgb, var(--pv-surface-border) 60%, transparent);
  }

  .cart-lines__control-label {
    font-size: 0.75rem;
    min-width: 2.5rem;
  }
}

.cart-lines__empty {
  display: grid;
  justify-items: center;
  gap: 0.75rem;
  padding: 2.5rem 1rem;
  color: var(--pv-text-muted);
  text-align: center;
}

.cart-lines__empty i {
  font-size: 2rem;
  color: var(--pv-accent);
}
</style>
