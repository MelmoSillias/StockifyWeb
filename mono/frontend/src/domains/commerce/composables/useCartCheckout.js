import { ref } from 'vue'

import { commerceService } from '@/domains/commerce/services/commerceService'
import { useCartStore } from '@/domains/commerce/stores/cart'
import { useOrdersStore } from '@/domains/commerce/stores/orders'
import { useSalesStore } from '@/domains/commerce/stores/sales'
import { useEntityActions } from '@/domains/shared/composables/useEntityActions'
import { toIsoDateTime } from '@/domains/shared/services/createCrudService'

export const useCartCheckout = () => {
  const cart = useCartStore()
  const salesStore = useSalesStore()
  const ordersStore = useOrdersStore()
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

  const openCheckout = (mode) => {
    checkoutMode.value = mode
    checkoutVisible.value = true
  }

  const onCheckoutConfirm = async ({ payment, operationDate, paymentDate, deliveryDate, confirmOrder }) => {
    submitting.value = true
    try {
      if (checkoutMode.value === 'sale') {
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
