import { ref } from 'vue'

import { useAuthStore } from '@/domains/auth/stores/auth'
import { commerceService } from '@/domains/commerce/services/commerceService'
import { useOrdersStore } from '@/domains/commerce/stores/orders'
import { useQuotesStore } from '@/domains/commerce/stores/quotes'
import { useSalesStore } from '@/domains/commerce/stores/sales'
import { useEntityActions } from '@/domains/shared/composables/useEntityActions'
import { toIsoDateTime } from '@/domains/shared/services/createCrudService'

export const useDevisConversion = () => {
  const authStore = useAuthStore()
  const quotesStore = useQuotesStore()
  const salesStore = useSalesStore()
  const ordersStore = useOrdersStore()
  const { showSuccess, showError } = useEntityActions()

  const checkoutVisible = ref(false)
  const checkoutMode = ref('sale')
  const submitting = ref(false)
  const selectedDevis = ref(null)

  const buildPaymentPayload = (payment, paymentDate) => {
    if (!payment || payment.amount <= 0) {
      return null
    }

    return {
      amount: String(payment.amount),
      mode_de_paiement_id: payment.mode_de_paiement_id,
      paid_at: toIsoDateTime(paymentDate)
    }
  }

  const completeSaleConversion = async ({ payment, operationDate, paymentDate }) => {
    const payload = {
      created_at: toIsoDateTime(operationDate)
    }
    const initialPayment = buildPaymentPayload(payment, paymentDate)
    if (initialPayment) {
      payload.initial_payment = initialPayment
    }

    const result = await commerceService.convertDevisToVente(selectedDevis.value.id, payload)
    quotesStore.upsertItem(result.devis)
    salesStore.upsertItem(result.vente)
    showSuccess(`Devis ${result.devis.reference} converti en vente ${result.vente.reference}.`)
    return result
  }

  const completeOrderConversion = async ({ payment, operationDate, paymentDate, deliveryDate, confirmOrder }) => {
    const payload = {
      created_at: toIsoDateTime(operationDate),
      confirm: Boolean(confirmOrder)
    }

    if (confirmOrder && deliveryDate) {
      payload.delivery_date = toIsoDateTime(deliveryDate)
    }

    const initialPayment = buildPaymentPayload(payment, paymentDate)
    if (initialPayment) {
      payload.initial_payment = initialPayment
    }

    const result = await commerceService.convertDevisToCommande(selectedDevis.value.id, payload)
    quotesStore.upsertItem(result.devis)
    ordersStore.upsert(result.commande)

    const suffix = confirmOrder ? ' confirmée' : ' créée'
    showSuccess(`Devis ${result.devis.reference} converti en commande ${result.commande.reference}${suffix}.`)
    return result
  }

  const openConversion = (devis, mode) => {
    if (mode === 'order' && !authStore.hasFeature('stockify.orders')) {
      showError('Les commandes ne sont pas incluses dans votre plan.')
      return
    }

    selectedDevis.value = devis
    checkoutMode.value = mode
    checkoutVisible.value = true
  }

  const onCheckoutConfirm = async ({ payment, operationDate, paymentDate, deliveryDate, confirmOrder }) => {
    if (!selectedDevis.value) {
      return false
    }

    if (submitting.value) return false

    if (checkoutMode.value === 'order' && !authStore.hasFeature('stockify.orders')) {
      showError('Les commandes ne sont pas incluses dans votre plan.')
      return false
    }

    submitting.value = true
    try {
      if (checkoutMode.value === 'sale') {
        await completeSaleConversion({ payment, operationDate, paymentDate })
      } else {
        await completeOrderConversion({ payment, operationDate, paymentDate, deliveryDate, confirmOrder })
      }
      checkoutVisible.value = false
      return true
    } catch (error) {
      showError(error?.message || 'La conversion a échoué.')
      return false
    } finally {
      submitting.value = false
    }
  }

  const conversionTotal = () => Number(selectedDevis.value?.total_amount || 0)

  return {
    checkoutVisible,
    checkoutMode,
    submitting,
    selectedDevis,
    openConversion,
    onCheckoutConfirm,
    conversionTotal
  }
}
