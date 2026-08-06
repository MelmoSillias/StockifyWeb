import { ref } from 'vue'

import { useAuthStore } from '@/domains/auth/stores/auth'
import { commerceService } from '@/domains/commerce/services/commerceService'
import { useCartStore } from '@/domains/commerce/stores/cart'
import { useOrdersStore } from '@/domains/commerce/stores/orders'
import { useQuotesStore } from '@/domains/commerce/stores/quotes'
import { useSalesStore } from '@/domains/commerce/stores/sales'
import { useEntityActions } from '@/domains/shared/composables/useEntityActions'
import { toIsoDate, toIsoDateTime } from '@/domains/shared/services/createCrudService'

export const useCartCheckout = () => {
  const authStore = useAuthStore()
  const cart = useCartStore()
  const salesStore = useSalesStore()
  const ordersStore = useOrdersStore()
  const quotesStore = useQuotesStore()
  const { showSuccess, showError } = useEntityActions()

  const checkoutVisible = ref(false)
  const checkoutMode = ref('sale')
  const submitting = ref(false)

  const completeSale = async ({ payment, operationDate, paymentDate }) => {
    const payload = {
      acheteur: cart.acheteurPayload,
      lines: cart.linesPayload,
      created_at: toIsoDateTime(operationDate)
    }

    if (payment && payment.amount > 0) {
      payload.initial_payment = {
        amount: String(payment.amount),
        mode_de_paiement_id: payment.mode_de_paiement_id,
        paid_at: toIsoDateTime(paymentDate)
      }
    }

    const vente = await salesStore.createSale(payload)

    cart.markAsSale(vente)
    showSuccess(`Vente ${vente.reference} enregistrée.`)
  }

  const completeOrder = async ({ payment, operationDate, paymentDate }) => {
    const commande = await ordersStore.createOrder({
      acheteur: cart.acheteurPayload,
      lines: cart.linesPayload,
      created_at: toIsoDateTime(operationDate)
    })

    if (payment && payment.amount > 0) {
      try {
        await commerceService.createPaiement({
          commande_id: commande.id,
          amount: String(payment.amount),
          mode_de_paiement_id: payment.mode_de_paiement_id,
          paid_at: toIsoDateTime(paymentDate)
        })
      } catch {
        showError('La commande est créée mais l\'acompte a échoué.')
      }
    }

    cart.markAsOrder(commande)
    showSuccess(`Commande ${commande.reference} créée.`)
  }

  const completeOrderAndConfirm = async ({ payment, operationDate, paymentDate, deliveryDate }) => {
    const commande = await ordersStore.createOrder({
      acheteur: cart.acheteurPayload,
      lines: cart.linesPayload,
      created_at: toIsoDateTime(operationDate)
    })

    if (payment && payment.amount > 0) {
      try {
        await commerceService.createPaiement({
          commande_id: commande.id,
          amount: String(payment.amount),
          mode_de_paiement_id: payment.mode_de_paiement_id,
          paid_at: toIsoDateTime(paymentDate)
        })
      } catch {
        showError('La commande est créée mais l\'acompte a échoué.')
      }
    }

    const confirmed = await ordersStore.confirmOrder(commande.id, {
      delivery_date: toIsoDateTime(deliveryDate)
    })

    cart.markAsOrder(confirmed)
    showSuccess(`Commande ${confirmed.reference} confirmée.`)
  }

  const completeQuote = async ({ operationDate, validUntil }) => {
    const devis = await quotesStore.createQuote({
      acheteur: cart.acheteurPayload,
      lines: cart.linesPayload,
      created_at: toIsoDateTime(operationDate),
      valid_until: toIsoDate(validUntil)
    })

    cart.markAsQuote(devis)
    showSuccess(`Devis ${devis.reference} enregistré.`)
  }

  const openCheckout = (mode) => {
    if (mode === 'order' && !authStore.hasFeature('stockify.orders')) {
      showError('Les commandes ne sont pas incluses dans votre plan.')
      return
    }

    if (mode === 'quote' && !authStore.hasFeature('stockify.quotes')) {
      showError('Les devis ne sont pas inclus dans votre plan.')
      return
    }

    checkoutMode.value = mode
    checkoutVisible.value = true
  }

  const onCheckoutConfirm = async ({ payment, operationDate, paymentDate, deliveryDate, confirmOrder, validUntil }) => {
    if (submitting.value) return false

    if (checkoutMode.value === 'quote' && !authStore.hasFeature('stockify.quotes')) {
      showError('Les devis ne sont pas inclus dans votre plan.')
      return false
    }

    if (checkoutMode.value === 'order' && !authStore.hasFeature('stockify.orders')) {
      showError('Les commandes ne sont pas incluses dans votre plan.')
      return false
    }

    submitting.value = true
    try {
      if (checkoutMode.value === 'quote') {
        await completeQuote({ operationDate, validUntil })
      } else if (checkoutMode.value === 'sale') {
        await completeSale({ payment, operationDate, paymentDate })
      } else if (confirmOrder) {
        await completeOrderAndConfirm({ payment, operationDate, paymentDate, deliveryDate })
      } else {
        await completeOrder({ payment, operationDate, paymentDate })
      }
      checkoutVisible.value = false
      return true
    } catch (error) {
      showError(error?.message || 'L\'opération a échoué.')
      return false
    } finally {
      submitting.value = false
    }
  }

  return {
    checkoutVisible,
    checkoutMode,
    submitting,
    openCheckout,
    onCheckoutConfirm
  }
}
