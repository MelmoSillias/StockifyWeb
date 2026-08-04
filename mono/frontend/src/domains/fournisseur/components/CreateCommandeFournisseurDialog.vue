<template>
  <Dialog
    :visible="visible"
    header="Nouvelle commande achat"
    modal
    :style="{ width: '44rem' }"
    @update:visible="$emit('update:visible', $event)"
  >
    <div class="create-commande-dialog">
      <p v-if="fournisseurName" class="create-commande-dialog__supplier">
        Fournisseur : <strong>{{ fournisseurName }}</strong>
      </p>

      <div class="create-commande-dialog__picker">
        <div class="create-commande-dialog__field">
          <label for="achat-variant-select">Article</label>
          <Select
            id="achat-variant-select"
            v-model="selectedVariantId"
            :options="variantOptions"
            option-label="label"
            option-value="value"
            placeholder="Rechercher un article..."
            filter
            show-clear
            fluid
            :loading="variantsLoading"
            @change="onVariantChange"
          />
        </div>

        <div class="create-commande-dialog__grid">
          <div class="create-commande-dialog__field">
            <label for="achat-qty">Quantité</label>
            <InputNumber
              id="achat-qty"
              v-model="quantity"
              :min="0"
              :min-fraction-digits="0"
              :max-fraction-digits="3"
              mode="decimal"
              fluid
            />
          </div>
          <div class="create-commande-dialog__field">
            <label for="achat-cost">Coût unitaire (XOF)</label>
            <InputNumber
              id="achat-cost"
              v-model="unitCost"
              :min="0"
              :min-fraction-digits="0"
              :max-fraction-digits="2"
              mode="decimal"
              fluid
            />
          </div>
        </div>

        <Button
          label="Ajouter la ligne"
          icon="pi pi-plus"
          severity="secondary"
          :disabled="!canAddLine"
          @click="addLine"
        />
      </div>

      <DataTable
        :value="lines"
        data-key="variant_id"
        size="small"
        striped-rows
        class="create-commande-dialog__lines"
      >
        <template #empty>
          <p class="create-commande-dialog__empty">Ajoutez au moins une ligne à la commande.</p>
        </template>
        <Column field="label" header="Article" />
        <Column header="Quantité">
          <template #body="{ data }">{{ data.quantity }}</template>
        </Column>
        <Column header="Coût unitaire">
          <template #body="{ data }">{{ formatMoney(data.unit_cost) }}</template>
        </Column>
        <Column header="Total">
          <template #body="{ data }">{{ formatMoney(lineTotal(data)) }}</template>
        </Column>
        <Column header="" style="width: 3rem">
          <template #body="{ data }">
            <Button
              icon="pi pi-times"
              text
              rounded
              severity="danger"
              @click="removeLine(data.variant_id)"
            />
          </template>
        </Column>
      </DataTable>

      <p v-if="lines.length" class="create-commande-dialog__total">
        Total : <strong>{{ formatMoney(totalAmount) }}</strong>
      </p>
    </div>

    <template #footer>
      <Button label="Annuler" severity="secondary" text @click="$emit('update:visible', false)" />
      <Button
        label="Créer la commande"
        icon="pi pi-check"
        :loading="loading"
        :disabled="lines.length === 0"
        @click="onSubmit"
      />
    </template>
  </Dialog>
</template>

<script setup>
import Button from 'primevue/button'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import Dialog from 'primevue/dialog'
import InputNumber from 'primevue/inputnumber'
import Select from 'primevue/select'
import { computed, ref, watch } from 'vue'

import { useVariantOptions } from '@/domains/catalog/composables/useVariantOptions'
import { useDisplayFormatters } from '@/domains/shared/composables/useDisplayFormatters'

const props = defineProps({
  visible: { type: Boolean, default: false },
  fournisseurId: { type: String, default: null },
  fournisseurName: { type: String, default: null },
  loading: { type: Boolean, default: false }
})

const emit = defineEmits(['update:visible', 'submit'])

const { formatMoney } = useDisplayFormatters()
const { options: variantOptions, loading: variantsLoading, load: loadVariants } = useVariantOptions()

const selectedVariantId = ref(null)
const quantity = ref(1)
const unitCost = ref(0)
const lines = ref([])

const selectedVariant = computed(
  () => variantOptions.value.find((entry) => entry.value === selectedVariantId.value) || null
)

const canAddLine = computed(
  () => Boolean(selectedVariant.value) && Number(quantity.value) > 0 && Number(unitCost.value) >= 0
)

const lineTotal = (line) => Number(line.quantity) * Number(line.unit_cost)
const totalAmount = computed(() => lines.value.reduce((sum, line) => sum + lineTotal(line), 0))

const resetPicker = () => {
  selectedVariantId.value = null
  quantity.value = 1
  unitCost.value = 0
}

const onVariantChange = () => {
  quantity.value = 1
  unitCost.value = 0
}

const addLine = () => {
  if (!selectedVariant.value) {
    return
  }

  const variantId = selectedVariant.value.value
  const existing = lines.value.find((line) => line.variant_id === variantId)

  if (existing) {
    existing.quantity = String(Number(existing.quantity) + Number(quantity.value))
    existing.unit_cost = String(unitCost.value)
  } else {
    lines.value.push({
      variant_id: variantId,
      label: selectedVariant.value.label,
      quantity: String(quantity.value),
      unit_cost: String(unitCost.value)
    })
  }

  resetPicker()
}

const removeLine = (variantId) => {
  lines.value = lines.value.filter((line) => line.variant_id !== variantId)
}

const onSubmit = () => {
  if (!props.fournisseurId || lines.value.length === 0) {
    return
  }

  emit('submit', {
    fournisseur_id: props.fournisseurId,
    lines: lines.value.map(({ variant_id, quantity, unit_cost }) => ({
      variant_id,
      quantity,
      unit_cost
    }))
  })
}

watch(
  () => props.visible,
  async (isVisible) => {
    if (isVisible) {
      lines.value = []
      resetPicker()
      await loadVariants()
    }
  }
)
</script>

<style scoped>
.create-commande-dialog {
  display: grid;
  gap: 1rem;
}

.create-commande-dialog__supplier {
  margin: 0;
}

.create-commande-dialog__picker {
  display: grid;
  gap: 0.75rem;
  padding: 1rem;
  border: 1px solid color-mix(in srgb, var(--p-surface-border) 70%, transparent);
  border-radius: 0.75rem;
}

.create-commande-dialog__field {
  display: grid;
  gap: 0.35rem;
}

.create-commande-dialog__grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.75rem;
}

.create-commande-dialog__empty {
  margin: 0;
  padding: 1rem;
  text-align: center;
  color: var(--p-text-muted-color);
}

.create-commande-dialog__total {
  margin: 0;
  text-align: right;
  font-size: 1.05rem;
}
</style>
