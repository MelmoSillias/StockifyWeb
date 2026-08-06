<template>
  <section class="dashboard-page">
    <div class="cart-layout">
      <Card class="dashboard-panel cart-layout__picker">
        <template #title>
          <div class="cart-layout__picker-title">
            <span>Ajouter un article</span>
            <Tag :value="statusMeta.label" :severity="statusMeta.severity" :icon="statusMeta.icon" rounded />
          </div>
        </template>
        <template #content>
          <CartVariantPicker :variants="variants" :loading="variantsLoading" @add="onAddLine" />
        </template>
      </Card>

      <Card class="dashboard-panel cart-layout__lines">
        <template #title>
          <div class="cart-layout__lines-title">
            <span>Panier</span>
            <Button
              v-if="cart.isCheckedOut"
              label="Nouveau panier"
              icon="pi pi-refresh"
              size="small"
              severity="secondary"
              outlined
              @click="cart.reset()"
            />
          </div>
        </template>
        <template #content>
          <CartLinesPanel
            :lines="cart.lines"
            :disabled="cart.isCheckedOut"
            @remove="cart.removeLine"
            @update-quantity="cart.updateLineQuantity"
            @update-price="cart.updateLinePrice"
          />

          <div class="cart-summary">
            <div class="cart-summary__row">
              <span>Sous-total</span>
              <strong>{{ formatMoney(cart.subtotal) }}</strong>
            </div>
            <div class="cart-summary__row">
              <span>Total</span>
              <strong class="cart-summary__total">{{ formatMoney(cart.subtotal) }}</strong>
            </div>
          </div>

          <Divider />

          <div class="cart-free-line">
            <label class="filter-label">Ligne libre</label>
            <div class="cart-free-line__form">
              <InputText
                v-model="freeLineLabel"
                placeholder="Nom / description"
                fluid
              />
              <InputNumber
                v-model="freeLineQuantity"
                :min="0"
                :min-fraction-digits="0"
                :max-fraction-digits="3"
                mode="decimal"
                placeholder="Qté"
                fluid
              />
              <InputNumber
                v-model="freeLinePrice"
                :min="0"
                :min-fraction-digits="0"
                :max-fraction-digits="2"
                mode="decimal"
                placeholder="Prix"
                fluid
              />
              <Button
                label="Ajouter"
                icon="pi pi-plus"
                severity="secondary"
                outlined
                :disabled="!canAddFreeLine || cart.isCheckedOut"
                @click="onAddFreeLine"
              />
            </div>
          </div>

          <Divider />

          <div class="cart-summary__buyer">
            <label class="filter-label">Acheteur</label>
            <AcheteurSelector
              :model-value="cart.acheteur"
              :clients="clientsStore.items"
              :clients-loading="clientsStore.loading"
              :compact="isMobile"
              @update:model-value="cart.setAcheteur"
            />
          </div>

          <div v-if="linkedMessage" class="cart-linked">
            <i class="pi pi-check-circle" />
            <span>{{ linkedMessage }}</span>
          </div>

          <div class="cart-actions">
            <Button
              v-if="canCreateOrder"
              label="Commande"
              icon="pi pi-list"
              :disabled="cart.isEmpty || cart.isCheckedOut"
              @click="openCheckout('order')"
            />
            <Button
              v-if="canCreateQuote"
              label="Devis"
              icon="pi pi-file-edit"
              severity="secondary"
              :disabled="cart.isEmpty || cart.isCheckedOut"
              @click="openCheckout('quote')"
            />
            <Button
              label="Vendre"
              icon="pi pi-shopping-bag"
              :disabled="cart.isEmpty || cart.isCheckedOut"
              @click="openCheckout('sale')"
            />
          </div>
        </template>
      </Card>
    </div>

    <CheckoutDialog
      :visible="checkoutVisible"
      :mode="checkoutMode"
      :payment-hint="checkoutMode === 'sale'
        ? 'Enregistrer le paiement de la vente immédiatement.'
        : checkoutMode === 'order'
          ? 'Enregistrer un acompte sur la commande.'
          : ''"
      :total="cart.subtotal"
      :loading="submitting"
      @update:visible="checkoutVisible = $event"
      @confirm="onCheckoutConfirm"
    />
  </section>
</template>

<script setup>
import Button from 'primevue/button'
import Card from 'primevue/card'
import Divider from 'primevue/divider'
import InputNumber from 'primevue/inputnumber'
import InputText from 'primevue/inputtext'
import Tag from 'primevue/tag'
import { computed, onMounted, ref } from 'vue'

import { usePermissions } from '@/domains/auth/composables/usePermissions'
import AcheteurSelector from '@/domains/commerce/components/AcheteurSelector.vue'
import CartLinesPanel from '@/domains/commerce/components/CartLinesPanel.vue'
import CartVariantPicker from '@/domains/commerce/components/CartVariantPicker.vue'
import CheckoutDialog from '@/domains/commerce/components/CheckoutDialog.vue'
import { useCartCheckout } from '@/domains/commerce/composables/useCartCheckout'
import { useCartStore } from '@/domains/commerce/stores/cart'
import { useClientsStore } from '@/domains/client/stores/clients'
import { variantsService } from '@/domains/catalog/services/variantsService'
import { useEntityActions } from '@/domains/shared/composables/useEntityActions'
import { useDisplayFormatters } from '@/domains/shared/composables/useDisplayFormatters'
import { useBreakpoint } from '@/domains/layout/composables/useBreakpoint'

const { isMobile } = useBreakpoint()
const cart = useCartStore()
const clientsStore = useClientsStore()
const { hasFeature } = usePermissions()
const { showError } = useEntityActions()
const { checkoutVisible, checkoutMode, submitting, openCheckout, onCheckoutConfirm } = useCartCheckout()
const { formatMoney } = useDisplayFormatters()

const canCreateOrder = computed(() => hasFeature('stockify.orders'))
const canCreateQuote = computed(() => hasFeature('stockify.quotes'))

const variants = ref([])
const variantsLoading = ref(false)
const freeLineLabel = ref('')
const freeLineQuantity = ref(1)
const freeLinePrice = ref(null)

const canAddFreeLine = computed(() =>
  String(freeLineLabel.value || '').trim().length > 0
  && Number(freeLineQuantity.value) > 0
  && Number(freeLinePrice.value) >= 0
  && freeLinePrice.value !== null
  && freeLinePrice.value !== ''
)

const statusMap = {
  vide: { label: 'Panier vide', severity: 'secondary', icon: 'pi pi-inbox' },
  non_enregistre: { label: 'Non enregistré', severity: 'warn', icon: 'pi pi-pencil' },
  commande_initiee: { label: 'Commande initiée', severity: 'info', icon: 'pi pi-list' },
  commande_confirmee: { label: 'Commande confirmée', severity: 'success', icon: 'pi pi-check' },
  devis: { label: 'Devis enregistré', severity: 'info', icon: 'pi pi-file-edit' },
  vendu: { label: 'Vendu', severity: 'success', icon: 'pi pi-check' }
}

const statusMeta = computed(() => statusMap[cart.status] || statusMap.vide)

const linkedMessage = computed(() => {
  if (cart.linkedSaleReference) {
    return `Vente ${cart.linkedSaleReference} enregistrée.`
  }
  if (cart.linkedOrderReference) {
    const suffix = cart.status === 'commande_confirmee' ? ' confirmée.' : ' créée.'
    return `Commande ${cart.linkedOrderReference}${suffix}`
  }
  if (cart.linkedQuoteReference) {
    return `Devis ${cart.linkedQuoteReference} enregistré.`
  }
  return null
})

const loadVariants = async () => {
  variantsLoading.value = true
  try {
    variants.value = await variantsService.listCatalog()
  } catch {
    showError('Impossible de charger les variantes du catalogue.')
  } finally {
    variantsLoading.value = false
  }
}

const onAddLine = (line) => {
  cart.addLine(line)
}

const onAddFreeLine = () => {
  if (!canAddFreeLine.value) return

  cart.addFreeLine({
    label: freeLineLabel.value,
    quantity: freeLineQuantity.value,
    unitPrice: freeLinePrice.value
  })

  freeLineLabel.value = ''
  freeLineQuantity.value = 1
  freeLinePrice.value = null
}

onMounted(() => {
  loadVariants()
  if (clientsStore.items.length === 0) {
    clientsStore.fetchAll().catch(() => {})
  }
})
</script>

<style scoped>
.cart-layout {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 1.2fr);
  gap: 1.5rem;
  align-items: start;
  min-width: 0;
}

.cart-layout__picker,
.cart-layout__lines {
  min-width: 0;
}

.cart-layout__lines-title,
.cart-layout__picker-title {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}

.cart-summary {
  display: grid;
  gap: 0.5rem;
  margin-top: 1.25rem;
}

.cart-summary__row {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.cart-summary__row--muted {
  color: var(--pv-text-muted);
}

.cart-summary__total {
  font-size: 1.15rem;
  color: var(--pv-accent);
}

.cart-summary__buyer {
  display: grid;
  gap: 0.5rem;
}

.cart-free-line {
  display: grid;
  gap: 0.5rem;
}

.cart-free-line__form {
  display: grid;
  grid-template-columns: 1.5fr 0.7fr 0.9fr auto;
  gap: 0.5rem;
  align-items: end;
}

.cart-linked {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-top: 1rem;
  padding: 0.65rem 0.9rem;
  border-radius: 0.75rem;
  background: color-mix(in srgb, var(--pv-accent-soft) 60%, transparent);
  color: var(--pv-text);
}

.cart-linked i {
  color: var(--pv-accent);
}

.cart-actions {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0.75rem;
  margin-top: 1.25rem;
}

@media (max-width: 960px) {
  .cart-layout {
    grid-template-columns: 1fr;
  }

  .cart-actions {
    grid-template-columns: 1fr;
  }

  .cart-free-line__form {
    grid-template-columns: 1fr;
  }
}
</style>
