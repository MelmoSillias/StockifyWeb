<template>
  <div class="variant-picker">
    <div class="variant-picker__field">
      <label class="filter-label" for="variant-select">Produit</label>
      <Select
        id="variant-select"
        v-model="selectedVariantId"
        :options="variants"
        option-label="label"
        option-value="id"
        placeholder="Rechercher un produit..."
        filter
        show-clear
        fluid
        :loading="loading"
        @change="onVariantChange"
      >
        <template #value="{ value, placeholder }">
          <span v-if="value" class="variant-picker__value" :title="variantLabel(value)">
            {{ variantLabel(value) }}
          </span>
          <span v-else>{{ placeholder }}</span>
        </template>
        <template #option="{ option }">
          <div class="variant-picker__option">
            <span class="variant-picker__option-label">{{ option.label }}</span>
            <span
              class="variant-picker__stock"
              :class="{ 'variant-picker__stock--empty': Number(option.available) <= 0 }"
            >
              {{ formatCompactNumber(option.available) }}
            </span>
          </div>
        </template>
      </Select>
    </div>

    <div v-if="selectedVariant" class="variant-picker__meta">
      <span
        class="variant-picker__stock variant-picker__stock--badge"
        :class="{ 'variant-picker__stock--empty': Number(selectedVariant.available) <= 0 }"
      >
        <i class="pi pi-box" />
        Stock: {{ formatCompactNumber(selectedVariant.available) }}
      </span>
      <span class="variant-picker__price-badge">
        <i class="pi pi-tag" />
        Prix de vente: {{ formatMoney(selectedVariant.default_price) }}
      </span>
    </div>

    <div class="variant-picker__grid">
      <div class="variant-picker__field">
        <label class="filter-label" for="variant-qty">Quantité</label>
        <InputNumber id="variant-qty" v-model="quantity" :min="0" :min-fraction-digits="0" :max-fraction-digits="3" mode="decimal" fluid />
      </div>
      <div class="variant-picker__field">
        <label class="filter-label" for="variant-price">Prix de vente (XOF)</label>
        <InputNumber id="variant-price" v-model="unitPrice" :min="0" :min-fraction-digits="0" :max-fraction-digits="2" mode="decimal" fluid />
      </div>
    </div>

    <Button
      label="Ajouter au panier"
      icon="pi pi-plus"
      :disabled="!canAdd"
      fluid
      @click="onAdd"
    />
  </div>
</template>

<script setup>
import Button from 'primevue/button'
import InputNumber from 'primevue/inputnumber'
import Select from 'primevue/select'
import { computed, ref } from 'vue'

import { useDisplayFormatters } from '@/domains/shared/composables/useDisplayFormatters'

const props = defineProps({
  variants: {
    type: Array,
    default: () => []
  },
  loading: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['add'])

const { formatCompactNumber, formatMoney } = useDisplayFormatters()

const selectedVariantId = ref(null)
const quantity = ref(1)
const unitPrice = ref(0)

const selectedVariant = computed(() =>
  props.variants.find((variant) => variant.id === selectedVariantId.value) || null
)

const variantLabel = (variantId) =>
  props.variants.find((variant) => variant.id === variantId)?.label || ''

const canAdd = computed(() => Boolean(selectedVariant.value) && Number(quantity.value) > 0)

const onVariantChange = () => {
  if (selectedVariant.value) {
    unitPrice.value = Number(selectedVariant.value.default_price || 0)
    quantity.value = 1
  }
}

const onAdd = () => {
  if (!selectedVariant.value) {
    return
  }

  emit('add', {
    variantId: selectedVariant.value.id,
    label: selectedVariant.value.label,
    sku: selectedVariant.value.sku,
    quantity: Number(quantity.value),
    unitPrice: Number(unitPrice.value),
    available: selectedVariant.value.available
  })

  selectedVariantId.value = null
  quantity.value = 1
  unitPrice.value = 0
}
</script>

<style scoped>
.variant-picker {
  display: grid;
  gap: 1rem;
}

.variant-picker__field {
  display: grid;
  gap: 0.4rem;
  min-width: 0;
}

.variant-picker__value,
.variant-picker__option-label {
  display: block;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  min-width: 0;
}

.variant-picker__grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.75rem;
}

.variant-picker__meta {
  display: flex;
  gap: 0.35rem;
  flex-wrap: wrap;
}

.variant-picker__option {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  width: 100%;
  min-width: 0;
}

.variant-picker__stock {
  flex-shrink: 0;
  font-size: 0.72rem;
  font-weight: 600;
  color: var(--pv-accent);
}

.variant-picker__stock--badge {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  padding: 0.3rem 0.55rem;
  border-radius: 999px;
  font-size: 0.72rem;
  background: color-mix(in srgb, var(--pv-accent) 14%, transparent);
}

.variant-picker__stock--empty {
  color: var(--p-red-400, #f87171);
  background: color-mix(in srgb, var(--p-red-400, #f87171) 12%, transparent);
}

.variant-picker__price-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  padding: 0.3rem 0.55rem;
  border-radius: 999px;
  font-size: 0.72rem;
  font-weight: 600;
  color: var(--pv-text);
  background: color-mix(in srgb, var(--pv-surface-border) 35%, transparent);
}

@media (max-width: 767px) {
  .variant-picker__grid {
    grid-template-columns: 1fr;
  }

  .variant-picker__meta {
    flex-direction: column;
    align-items: stretch;
  }

  .variant-picker__stock--badge,
  .variant-picker__price-badge {
    justify-content: center;
    width: 100%;
    box-sizing: border-box;
  }
}
</style>
