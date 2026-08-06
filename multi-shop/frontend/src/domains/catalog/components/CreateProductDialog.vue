<template>
  <Dialog
    :visible="visible"
    modal
    class="create-product-dialog"
    :style="{ width: 'min(920px, 94vw)' }"
    dismissable-mask
    @update:visible="onVisibleChange"
  >
    <template #header>
      <div class="create-product-dialog__header">
        <span class="create-product-dialog__header-icon">
          <i class="pi pi-box"></i>
        </span>
        <div class="create-product-dialog__header-copy">
          <h2 class="create-product-dialog__title">Nouveau produit</h2>
          <p class="create-product-dialog__subtitle">
            Produit, variante optionnelle et lots à la volée.
          </p>
        </div>
      </div>
    </template>

    <div class="create-product-dialog__content">
      <Message v-if="generalError" severity="error" :closable="false">{{ generalError }}</Message>

      <Tabs v-model:value="activeTab">
        <TabList>
          <Tab value="product">
            <span class="tab-label">
              <i :class="productTabIcon" class="tab-indicator"></i>
              Produit
            </span>
          </Tab>
          <Tab value="variant">
            <span class="tab-label">
              <i :class="variantTabIcon" class="tab-indicator"></i>
              Variante
            </span>
          </Tab>
        </TabList>

        <TabPanels>
          <TabPanel value="product">
            <Fluid>
              <div class="form-grid">
                <div class="form-field">
                  <label for="cp-name" class="form-label">
                    <i class="pi pi-tag"></i>
                    <span>Nom</span>
                  </label>
                  <InputText
                    id="cp-name"
                    v-model="form.name"
                    placeholder="Ex: Eau minérale Cristal"
                    :disabled="loading"
                    :invalid="showValidation && !!productErrors.name"
                    fluid
                  />
                </div>

                <div class="form-field">
                  <label for="cp-reference" class="form-label">
                    <i class="pi pi-barcode"></i>
                    <span>Référence</span>
                  </label>
                  <InputText
                    id="cp-reference"
                    v-model="form.reference"
                    placeholder="Ex: EAU"
                    :disabled="loading"
                    fluid
                  />
                </div>

                <div class="form-field form-field--full">
                  <label for="cp-description" class="form-label">
                    <i class="pi pi-align-left"></i>
                    <span>Description</span>
                  </label>
                  <Textarea
                    id="cp-description"
                    v-model="form.description"
                    placeholder="Description du produit"
                    :rows="4"
                    :disabled="loading"
                    auto-resize
                    fluid
                  />
                </div>

                <div class="form-field form-field--full">
                  <label for="cp-category" class="form-label">
                    <i class="pi pi-sitemap"></i>
                    <span>Catégorie</span>
                  </label>
                  <Select
                    id="cp-category"
                    v-model="form.category_id"
                    :options="categoryOptions"
                    option-label="label"
                    option-value="value"
                    placeholder="Sélectionner"
                    :disabled="loading"
                    filter
                    fluid
                    show-clear
                  />
                </div>
              </div>
            </Fluid>

            <div class="tab-actions">
              <Button
                label="Continuer vers la variante"
                icon="pi pi-arrow-right"
                icon-pos="right"
                :disabled="loading"
                @click="activeTab = 'variant'"
              />
            </div>
          </TabPanel>

          <TabPanel value="variant">
            <Fluid>
              <div class="form-grid">
                <div class="form-field">
                  <label for="cp-unit" class="form-label">
                    <i class="pi pi-sliders-h"></i>
                    <span>Unité</span>
                  </label>
                  <Select
                    id="cp-unit"
                    v-model="form.unit_of_measure_id"
                    :options="unitOptions"
                    option-label="label"
                    option-value="value"
                    placeholder="Sélectionner"
                    :disabled="loading"
                    :invalid="showValidation && !!variantErrors.unit_of_measure_id"
                    filter
                    fluid
                    show-clear
                  />
                </div>

                <div class="form-field">
                  <label for="cp-sale-mode" class="form-label">
                    <i class="pi pi-shopping-cart"></i>
                    <span>Mode de vente</span>
                  </label>
                  <Select
                    id="cp-sale-mode"
                    v-model="form.sale_mode"
                    :options="saleModeOptions"
                    option-label="label"
                    option-value="value"
                    :disabled="loading"
                    :invalid="showValidation && !!variantErrors.sale_mode"
                    fluid
                  />
                </div>

                <div class="form-field">
                  <label for="cp-price" class="form-label">
                    <i class="pi pi-money-bill"></i>
                    <span>Prix par défaut</span>
                  </label>
                  <InputNumber
                    id="cp-price"
                    v-model="form.default_price"
                    :min="0"
                    :min-fraction-digits="0"
                    :max-fraction-digits="2"
                    placeholder="Ex: 500"
                    :disabled="loading"
                    :invalid="showValidation && !!variantErrors.default_price"
                    fluid
                  />
                </div>

                <div class="form-field">
                  <label for="cp-threshold" class="form-label">
                    <i class="pi pi-exclamation-triangle"></i>
                    <span>Seuil alerte</span>
                  </label>
                  <InputNumber
                    id="cp-threshold"
                    v-model="form.alert_threshold"
                    :min="0"
                    :min-fraction-digits="0"
                    :max-fraction-digits="2"
                    placeholder="Ex: 10"
                    :disabled="loading"
                    :invalid="showValidation && !!variantErrors.alert_threshold"
                    fluid
                  />
                </div>
              </div>
            </Fluid>

            <Divider />

            <div class="lots-section">
              <div class="lots-section__header">
                <h3 class="lots-section__title">Lots</h3>
                <p class="lots-section__hint">
                  Ajoutez des lots uniquement si la variante est correctement renseignée.
                </p>
              </div>

              <div
                v-for="(lot, index) in form.lots"
                :key="index"
                class="lot-block"
              >
                <div class="lot-block__header">
                  <span class="lot-block__title">Lot {{ index + 1 }}</span>
                  <Button
                    icon="pi pi-trash"
                    severity="danger"
                    text
                    rounded
                    :disabled="loading"
                    @click="removeLot(index)"
                  />
                </div>

                <Fluid>
                  <div class="form-grid">
                    <div class="form-field">
                      <label :for="`cp-lot-qty-${index}`" class="form-label">
                        <i class="pi pi-box"></i>
                        <span>Quantité</span>
                      </label>
                      <InputNumber
                        :id="`cp-lot-qty-${index}`"
                        v-model="lot.quantity"
                        :min="0"
                        :min-fraction-digits="0"
                        :max-fraction-digits="3"
                        placeholder="Ex: 100"
                        :disabled="loading"
                        :invalid="showValidation && !!lotErrors[index]?.quantity"
                        fluid
                      />
                    </div>

                    <div class="form-field">
                      <label :for="`cp-lot-cost-${index}`" class="form-label">
                        <i class="pi pi-money-bill"></i>
                        <span>Coût unitaire</span>
                      </label>
                      <InputNumber
                        :id="`cp-lot-cost-${index}`"
                        v-model="lot.unit_cost"
                        :min="0"
                        :min-fraction-digits="0"
                        :max-fraction-digits="2"
                        placeholder="Ex: 2.50"
                        :disabled="loading"
                        :invalid="showValidation && !!lotErrors[index]?.unit_cost"
                        fluid
                      />
                    </div>

                    <div class="form-field">
                      <label :for="`cp-lot-ref-${index}`" class="form-label">
                        <i class="pi pi-tag"></i>
                        <span>Référence lot</span>
                      </label>
                      <InputText
                        :id="`cp-lot-ref-${index}`"
                        v-model="lot.reference"
                        placeholder="Ex: LOT-2026-01"
                        :disabled="loading"
                        fluid
                      />
                    </div>

                    <div class="form-field">
                      <label :for="`cp-lot-supplier-${index}`" class="form-label">
                        <i class="pi pi-truck"></i>
                        <span>Réf. fournisseur</span>
                      </label>
                      <InputText
                        :id="`cp-lot-supplier-${index}`"
                        v-model="lot.supplier_ref"
                        placeholder="Optionnel"
                        :disabled="loading"
                        fluid
                      />
                    </div>

                    <div class="form-field form-field--full">
                      <label :for="`cp-lot-expiry-${index}`" class="form-label">
                        <i class="pi pi-calendar"></i>
                        <span>Date expiration</span>
                      </label>
                      <DatePicker
                        :id="`cp-lot-expiry-${index}`"
                        v-model="lot.expiry_date"
                        placeholder="Optionnel"
                        show-icon
                        :disabled="loading"
                        fluid
                      />
                    </div>
                  </div>
                </Fluid>
              </div>

              <div class="tab-actions">
                <Button
                  label="Ajouter un lot"
                  icon="pi pi-plus"
                  severity="secondary"
                  outlined
                  :disabled="loading || !isVariantComplete"
                  @click="addLot"
                />
              </div>
            </div>
          </TabPanel>
        </TabPanels>
      </Tabs>
    </div>

    <template #footer>
      <div class="create-product-dialog__footer">
        <Button
          label="Nettoyer"
          icon="pi pi-trash"
          severity="secondary"
          text
          :disabled="loading"
          @click="confirmClear"
        />
        <div class="create-product-dialog__footer-actions">
          <Button
            label="Annuler"
            icon="pi pi-times"
            severity="secondary"
            text
            :disabled="loading"
            @click="onVisibleChange(false)"
          />
          <Button
            label="Enregistrer"
            icon="pi pi-check"
            :loading="loading"
            :disabled="loading"
            @click="confirmSubmit"
          />
        </div>
      </div>
    </template>
  </Dialog>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useConfirm } from 'primevue/useconfirm'

import Button from 'primevue/button'
import DatePicker from 'primevue/datepicker'
import Dialog from 'primevue/dialog'
import Divider from 'primevue/divider'
import Fluid from 'primevue/fluid'
import InputNumber from 'primevue/inputnumber'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import Select from 'primevue/select'
import Tab from 'primevue/tab'
import TabList from 'primevue/tablist'
import TabPanel from 'primevue/tabpanel'
import TabPanels from 'primevue/tabpanels'
import Tabs from 'primevue/tabs'
import Textarea from 'primevue/textarea'

import {
  createEmptyLot,
  useCreateProductDraft
} from '@/domains/catalog/composables/useCreateProductDraft'
import { saleModeOptions } from '@/domains/catalog/utils/variantLabel'

const props = defineProps({
  visible: {
    type: Boolean,
    default: false
  },
  loading: {
    type: Boolean,
    default: false
  },
  generalError: {
    type: String,
    default: ''
  },
  categoryOptions: {
    type: Array,
    default: () => []
  },
  unitOptions: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['update:visible', 'submit'])

const confirm = useConfirm()
const { form, loadDraft, clearDraft } = useCreateProductDraft()
const activeTab = ref('product')
const draftLoaded = ref(false)
const showValidation = ref(false)

const isBlank = (value) => value === null || value === undefined || value === ''

const isVariantEmpty = computed(() => {
  return (
    !form.unit_of_measure_id &&
    (form.sale_mode === 'unit' || !form.sale_mode) &&
    isBlank(form.default_price) &&
    isBlank(form.alert_threshold) &&
    form.lots.length === 0
  )
})

const productErrors = computed(() => {
  const errors = {}
  if (!String(form.name || '').trim()) {
    errors.name = 'Le nom est requis.'
  }
  return errors
})

const isProductValid = computed(() => Object.keys(productErrors.value).length === 0)

const variantErrors = computed(() => {
  const errors = {}

  if (isVariantEmpty.value) {
    return errors
  }

  if (!form.unit_of_measure_id) {
    errors.unit_of_measure_id = 'Choisissez une unité.'
  }

  if (!form.sale_mode) {
    errors.sale_mode = 'Choisissez un mode de vente.'
  }

  if (!isBlank(form.default_price) && Number(form.default_price) < 0) {
    errors.default_price = 'Le prix doit être positif ou nul.'
  }

  if (!isBlank(form.alert_threshold) && Number(form.alert_threshold) < 0) {
    errors.alert_threshold = 'Le seuil doit être positif ou nul.'
  }

  return errors
})

const isVariantComplete = computed(() => {
  return Boolean(form.unit_of_measure_id && form.sale_mode && Object.keys(variantErrors.value).length === 0)
})

const isVariantTabOk = computed(() => {
  if (isVariantEmpty.value) {
    return true
  }
  return isVariantComplete.value
})

const lotErrors = computed(() =>
  form.lots.map((lot) => {
    const errors = {}
    if (isBlank(lot.quantity) || Number(lot.quantity) <= 0) {
      errors.quantity = 'La quantité doit être supérieure à 0.'
    }
    if (isBlank(lot.unit_cost)) {
      errors.unit_cost = 'Le coût unitaire est requis.'
    } else if (Number(lot.unit_cost) < 0) {
      errors.unit_cost = 'Le coût unitaire doit être positif ou nul.'
    }
    return errors
  })
)

const areLotsValid = computed(() => lotErrors.value.every((errors) => Object.keys(errors).length === 0))

const productTabIcon = computed(() =>
  isProductValid.value ? 'pi pi-check-circle tab-indicator--ok' : 'pi pi-circle tab-indicator--pending'
)

const variantTabIcon = computed(() => {
  if (isVariantEmpty.value) {
    return 'pi pi-minus-circle tab-indicator--optional'
  }
  if (isVariantTabOk.value && areLotsValid.value) {
    return 'pi pi-check-circle tab-indicator--ok'
  }
  return 'pi pi-exclamation-circle tab-indicator--error'
})

const canSubmit = computed(() => {
  if (!isProductValid.value) {
    return false
  }

  if (isVariantEmpty.value) {
    return form.lots.length === 0
  }

  if (!isVariantComplete.value) {
    return false
  }

  return areLotsValid.value
})

watch(
  () => props.visible,
  (visible) => {
    if (visible && !draftLoaded.value) {
      loadDraft()
      draftLoaded.value = true
      activeTab.value = 'product'
      showValidation.value = false
    }
    if (!visible) {
      draftLoaded.value = false
      showValidation.value = false
    }
  }
)

const onVisibleChange = (value) => {
  emit('update:visible', value)
}

const addLot = () => {
  if (!isVariantComplete.value) {
    return
  }
  form.lots.push(createEmptyLot())
}

const removeLot = (index) => {
  form.lots.splice(index, 1)
}

const confirmClear = (event) => {
  confirm.require({
    target: event.currentTarget,
    message: 'Effacer tous les champs du formulaire ?',
    icon: 'pi pi-exclamation-triangle',
    rejectProps: {
      label: 'Annuler',
      severity: 'secondary',
      outlined: true
    },
    acceptProps: {
      label: 'Nettoyer',
      severity: 'danger'
    },
    accept: () => {
      clearDraft()
      activeTab.value = 'product'
    }
  })
}

const confirmSubmit = (event) => {
  if (props.loading) return
  if (!canSubmit.value) {
    showValidation.value = true
    if (!isProductValid.value) {
      activeTab.value = 'product'
    } else {
      activeTab.value = 'variant'
    }
    return
  }

  showValidation.value = false

  confirm.require({
    target: event.currentTarget,
    message: 'Confirmer la création de ce produit ?',
    icon: 'pi pi-check-circle',
    rejectProps: {
      label: 'Annuler',
      severity: 'secondary',
      outlined: true
    },
    acceptProps: {
      label: 'Créer',
      severity: 'primary'
    },
    accept: () => {
      emit('submit', { ...form, lots: form.lots.map((lot) => ({ ...lot })) })
    }
  })
}

const resetAfterSuccess = () => {
  clearDraft()
  activeTab.value = 'product'
  showValidation.value = false
}

defineExpose({
  form,
  clearDraft: resetAfterSuccess,
  isVariantEmpty,
  isVariantComplete
})
</script>

<style scoped>
.create-product-dialog__content {
  display: grid;
  gap: 1rem;
}

.create-product-dialog__header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.create-product-dialog__header-icon {
  display: grid;
  place-items: center;
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 999px;
  background: color-mix(in srgb, var(--pv-accent-soft) 80%, white);
  color: var(--pv-accent-strong);
  flex: 0 0 auto;
}

.create-product-dialog__header-copy {
  display: grid;
  gap: 0.125rem;
}

.create-product-dialog__title {
  margin: 0;
  font-size: 1.05rem;
  font-weight: 700;
  color: var(--pv-accent-strong);
}

.create-product-dialog__subtitle {
  margin: 0;
  color: var(--pv-text-muted);
}

.tab-label {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
}

.tab-indicator--ok {
  color: var(--p-green-500, #22c55e);
}

.tab-indicator--error {
  color: var(--p-red-500, #ef4444);
}

.tab-indicator--optional,
.tab-indicator--pending {
  color: var(--layout-text-muted);
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1rem;
  margin-top: 1rem;
}

.form-field {
  display: grid;
  gap: 0.5rem;
}

.form-field--full {
  grid-column: 1 / -1;
}

.form-label {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  font-weight: 600;
}

.tab-actions {
  display: flex;
  justify-content: flex-end;
  margin-top: 1.25rem;
}

.lots-section {
  display: grid;
  gap: 1rem;
}

.lots-section__header {
  display: grid;
  gap: 0.25rem;
}

.lots-section__title {
  margin: 0;
  font-size: 1rem;
  font-weight: 700;
}

.lots-section__hint {
  margin: 0;
  color: var(--layout-text-muted);
  font-size: 0.875rem;
}

.lot-block {
  display: grid;
  gap: 0.75rem;
  padding: 0.85rem 1rem;
  border: 1px solid var(--p-content-border-color);
  border-radius: var(--p-border-radius-xl, 0.75rem);
  background: color-mix(in srgb, var(--p-content-background) 92%, var(--p-surface-100, #f4f4f5));
}

.lot-block__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.lot-block__title {
  font-weight: 650;
}

.create-product-dialog__footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  width: 100%;
  margin-top: 0.5rem;
}

.create-product-dialog__footer-actions {
  display: flex;
  gap: 0.5rem;
}

@media (max-width: 768px) {
  .form-grid {
    grid-template-columns: 1fr;
  }

  .create-product-dialog__footer {
    flex-direction: column;
    align-items: stretch;
  }

  .create-product-dialog__footer-actions {
    justify-content: flex-end;
  }
}
</style>
