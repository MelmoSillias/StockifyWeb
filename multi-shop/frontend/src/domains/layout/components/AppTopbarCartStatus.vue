<script setup>
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'

import Button from 'primevue/button'
import Popover from 'primevue/popover'

import { usePermissions } from '@/domains/auth/composables/usePermissions'
import AcheteurSelector from '@/domains/commerce/components/AcheteurSelector.vue'
import CheckoutDialog from '@/domains/commerce/components/CheckoutDialog.vue'
import { useCartCheckout } from '@/domains/commerce/composables/useCartCheckout'
import { useCartStore } from '@/domains/commerce/stores/cart'
import { useClientsStore } from '@/domains/client/stores/clients'
import { useDisplayFormatters } from '@/domains/shared/composables/useDisplayFormatters'

const props = defineProps({
  variant: {
    type: String,
    default: 'chip',
    validator: (value) => ['chip', 'icon', 'panel'].includes(value)
  },
  compact: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['open-panel'])

const cart = useCartStore()
const clientsStore = useClientsStore()
const router = useRouter()
const { hasFeature } = usePermissions()
const { checkoutVisible, checkoutMode, submitting, openCheckout, onCheckoutConfirm } = useCartCheckout()
const { formatMoney, formatCompactNumber } = useDisplayFormatters()

const canCreateOrder = computed(() => hasFeature('stockify.orders'))
const canCreateQuote = computed(() => hasFeature('stockify.quotes'))

const cartPopover = ref()

const statusMap = {
  vide: { label: 'Panier vide' },
  non_enregistre: { label: 'Non enregistré' },
  commande_initiee: { label: 'Commande initiée' },
  devis: { label: 'Devis enregistré' },
  vendu: { label: 'Vendu' }
}

const statusMeta = computed(() => statusMap[cart.status] || statusMap.vide)

const contentSummary = computed(() => {
  if (cart.isEmpty) {
    return 'Aucun article'
  }

  const articleLabel = cart.lines.length === 1 ? '1 article' : `${cart.lines.length} articles`
  return `${articleLabel} · ${formatMoney(cart.subtotal)}`
})

const linkedMessage = computed(() => {
  if (cart.linkedSaleReference) {
    return `Vente ${cart.linkedSaleReference} enregistrée.`
  }
  if (cart.linkedOrderReference) {
    return `Commande ${cart.linkedOrderReference} enregistrée.`
  }
  if (cart.linkedQuoteReference) {
    return `Devis ${cart.linkedQuoteReference} enregistré.`
  }
  return null
})

const chipClasses = computed(() => ({
  'app-topbar__chip--clickable': true,
  'app-topbar__chip--compact': props.compact
}))

const ensureClientsLoaded = () => {
  if (clientsStore.items.length === 0) {
    clientsStore.fetchAll().catch(() => {})
  }
}

const toggleCartPanel = (event) => {
  ensureClientsLoaded()
  cartPopover.value.toggle(event)
}

const openQuickPanel = () => {
  ensureClientsLoaded()
  emit('open-panel')
}

const goToCart = () => {
  cartPopover.value?.hide()
  router.push({ name: 'commerce-cart' })
}

const handleCheckout = (mode) => {
  openCheckout(mode)
}

const handleCheckoutConfirm = async (payload) => {
  const success = await onCheckoutConfirm(payload)
  if (success) {
    cartPopover.value?.hide()
  }
}

const resetCart = () => {
  cart.reset()
  cartPopover.value?.hide()
}
</script>

<template>
  <div v-if="variant === 'panel'" class="app-topbar-cart-panel app-topbar-cart-panel--drawer">
    <div class="app-topbar-cart-panel__header">
      <div>
        <strong>Panier</strong>
        <span>{{ statusMeta.label }}</span>
      </div>
      <Button
        icon="pi pi-external-link"
        text
        rounded
        severity="secondary"
        aria-label="Ouvrir la page panier"
        @click="goToCart"
      />
    </div>

    <div v-if="cart.isEmpty" class="app-topbar-cart-panel__empty">
      <i class="pi pi-shopping-cart" />
      <p>Le panier est vide.</p>
      <Button label="Ajouter des articles" size="small" outlined @click="goToCart" />
    </div>

    <template v-else>
      <ul class="app-topbar-cart-panel__lines">
        <li v-for="line in cart.lines" :key="line.variantId" class="app-topbar-cart-panel__line">
          <div class="app-topbar-cart-panel__line-row">
            <div class="app-topbar-cart-panel__line-info">
              <span class="app-topbar-cart-panel__line-label" :title="line.label">{{ line.label }}</span>
              <span class="app-topbar-cart-panel__line-qty">
                {{ formatCompactNumber(line.quantity) }} × {{ formatMoney(line.unitPrice) }}
              </span>
            </div>
            <div class="app-topbar-cart-panel__line-side">
              <strong class="app-topbar-cart-panel__line-total">{{ formatMoney(line.quantity * line.unitPrice) }}</strong>
              <Button
                icon="pi pi-times"
                text
                rounded
                severity="danger"
                size="small"
                class="app-topbar-cart-panel__line-remove"
                :disabled="cart.isCheckedOut"
                @click="cart.removeLine(line.variantId)"
              />
            </div>
          </div>
        </li>
      </ul>

      <div class="app-topbar-cart-panel__total">
        <span>Total</span>
        <strong>{{ formatMoney(cart.subtotal) }}</strong>
      </div>

      <div class="app-topbar-cart-panel__buyer">
        <label class="filter-label">Acheteur</label>
        <AcheteurSelector
          :model-value="cart.acheteur"
          :clients="clientsStore.items"
          :clients-loading="clientsStore.loading"
          compact
          @update:model-value="cart.setAcheteur"
        />
      </div>
    </template>

    <div v-if="linkedMessage" class="app-topbar-cart-panel__linked">
      <i class="pi pi-check-circle" />
      <span>{{ linkedMessage }}</span>
    </div>

    <div
      class="app-topbar-cart-panel__actions"
      :class="{ 'app-topbar-cart-panel__actions--single': cart.isCheckedOut }"
    >
      <Button
        v-if="cart.isCheckedOut"
        label="Nouveau panier"
        icon="pi pi-refresh"
        severity="secondary"
        outlined
        size="small"
        fluid
        @click="resetCart"
      />
      <template v-else>
        <Button
          v-if="canCreateOrder"
          label="Créer commande"
          icon="pi pi-list"
          severity="secondary"
          size="small"
          :disabled="cart.isEmpty"
          fluid
          @click="handleCheckout('order')"
        />
        <Button
          v-if="canCreateQuote"
          label="Créer devis"
          icon="pi pi-file-edit"
          severity="secondary"
          outlined
          size="small"
          :disabled="cart.isEmpty"
          fluid
          @click="handleCheckout('quote')"
        />
        <Button
          label="Vendre"
          icon="pi pi-shopping-bag"
          size="small"
          :disabled="cart.isEmpty"
          fluid
          @click="handleCheckout('sale')"
        />
      </template>
    </div>
  </div>

  <div v-else class="app-topbar__chip-host">
    <button
      v-if="variant === 'icon'"
      type="button"
      class="app-topbar__chip app-topbar__chip--icon app-topbar__chip--clickable"
      aria-label="Ouvrir le panier"
      @click="toggleCartPanel"
    >
      <span class="app-topbar__chip-icon app-topbar__chip-icon--accent">
        <i class="pi pi-shopping-cart" />
        <span v-if="!cart.isEmpty" class="app-topbar__chip-badge">{{ cart.lines.length }}</span>
      </span>
    </button>

    <button
      v-else
      type="button"
      class="app-topbar__chip"
      :class="chipClasses"
      aria-label="Ouvrir le panier"
      @click="toggleCartPanel"
    >
      <span class="app-topbar__chip-icon app-topbar__chip-icon--accent">
        <i class="pi pi-shopping-cart" />
      </span>
      <span class="app-topbar__chip-copy">
        <strong class="app-topbar__chip-primary">{{ statusMeta.label }}</strong>
        <span class="app-topbar__chip-secondary">{{ contentSummary }}</span>
      </span>
    </button>

    <Popover v-if="variant === 'chip' || variant === 'icon'" ref="cartPopover" class="app-topbar-cart-popover">
      <div class="app-topbar-cart-panel">
        <div class="app-topbar-cart-panel__header">
          <div>
            <strong>Panier</strong>
            <span>{{ statusMeta.label }}</span>
          </div>
          <Button
            icon="pi pi-external-link"
            text
            rounded
            severity="secondary"
            aria-label="Ouvrir la page panier"
            @click="goToCart"
          />
        </div>

        <div v-if="cart.isEmpty" class="app-topbar-cart-panel__empty">
          <i class="pi pi-shopping-cart" />
          <p>Le panier est vide.</p>
          <Button label="Ajouter des articles" size="small" outlined @click="goToCart" />
        </div>

        <template v-else>
          <ul class="app-topbar-cart-panel__lines">
            <li v-for="line in cart.lines" :key="line.variantId" class="app-topbar-cart-panel__line">
              <div class="app-topbar-cart-panel__line-row">
                <div class="app-topbar-cart-panel__line-info">
                  <span class="app-topbar-cart-panel__line-label" :title="line.label">{{ line.label }}</span>
                  <span class="app-topbar-cart-panel__line-qty">
                    {{ formatCompactNumber(line.quantity) }} × {{ formatMoney(line.unitPrice) }}
                  </span>
                </div>
                <div class="app-topbar-cart-panel__line-side">
                  <strong class="app-topbar-cart-panel__line-total">{{ formatMoney(line.quantity * line.unitPrice) }}</strong>
                  <Button
                    icon="pi pi-times"
                    text
                    rounded
                    severity="danger"
                    size="small"
                    class="app-topbar-cart-panel__line-remove"
                    :disabled="cart.isCheckedOut"
                    @click="cart.removeLine(line.variantId)"
                  />
                </div>
              </div>
            </li>
          </ul>

          <div class="app-topbar-cart-panel__total">
            <span>Total</span>
            <strong>{{ formatMoney(cart.subtotal) }}</strong>
          </div>

          <div class="app-topbar-cart-panel__buyer">
            <label class="filter-label">Acheteur</label>
            <AcheteurSelector
              :model-value="cart.acheteur"
              :clients="clientsStore.items"
              :clients-loading="clientsStore.loading"
              compact
              @update:model-value="cart.setAcheteur"
            />
          </div>
        </template>

        <div v-if="linkedMessage" class="app-topbar-cart-panel__linked">
          <i class="pi pi-check-circle" />
          <span>{{ linkedMessage }}</span>
        </div>

        <div
          class="app-topbar-cart-panel__actions"
          :class="{
            'app-topbar-cart-panel__actions--single': cart.isCheckedOut,
            'app-topbar-cart-panel__actions--stacked': variant === 'icon' || variant === 'chip'
          }"
        >
          <Button
            v-if="cart.isCheckedOut"
            label="Nouveau panier"
            icon="pi pi-refresh"
            severity="secondary"
            outlined
            size="small"
            fluid
            @click="resetCart"
          />
          <template v-else>
            <Button
              v-if="canCreateOrder"
              label="Créer commande"
              icon="pi pi-list"
              severity="secondary"
              size="small"
              :disabled="cart.isEmpty"
              fluid
              @click="handleCheckout('order')"
            />
            <Button
              v-if="canCreateQuote"
              label="Créer devis"
              icon="pi pi-file-edit"
              severity="secondary"
              outlined
              size="small"
              :disabled="cart.isEmpty"
              fluid
              @click="handleCheckout('quote')"
            />
            <Button
              label="Vendre"
              icon="pi pi-shopping-bag"
              size="small"
              :disabled="cart.isEmpty"
              fluid
              @click="handleCheckout('sale')"
            />
          </template>
        </div>
      </div>
    </Popover>
  </div>

  <CheckoutDialog
    :visible="checkoutVisible"
    :mode="checkoutMode"
    :title="checkoutMode === 'sale'
      ? 'Encaisser la vente'
      : checkoutMode === 'quote'
        ? 'Créer le devis'
        : 'Créer la commande'"
    :confirm-label="checkoutMode === 'sale'
      ? 'Valider la vente'
      : checkoutMode === 'quote'
        ? 'Enregistrer le devis'
        : 'Créer la commande'"
    :payment-hint="checkoutMode === 'sale'
      ? 'Enregistrer le paiement de la vente immédiatement.'
      : checkoutMode === 'order'
        ? 'Enregistrer un acompte sur la commande.'
        : ''"
    :total="cart.subtotal"
    :loading="submitting"
    @update:visible="checkoutVisible = $event"
    @confirm="handleCheckoutConfirm"
  />
</template>
