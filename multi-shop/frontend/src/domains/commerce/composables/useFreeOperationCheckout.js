import { computed, ref } from 'vue'

import { commerceService } from '@/domains/commerce/services/commerceService'
import { useOrdersStore } from '@/domains/commerce/stores/orders'
import { useQuotesStore } from '@/domains/commerce/stores/quotes'
import { useSalesStore } from '@/domains/commerce/stores/sales'
import { useEntityActions } from '@/domains/shared/composables/useEntityActions'
import { toIsoDate, toIsoDateTime } from '@/domains/shared/services/createCrudService'

const createEmptyAcheteur = () => ({
  type: 'anonymous',
  clientId: null,
  anonymousInfo: ''
})

const toAcheteurPayload = (acheteur) => {
  if (acheteur.type === 'client' && acheteur.clientId) {
    return { client_id: acheteur.clientId, anonymous_info: null }
  }
  return { client_id: null, anonymous_info: acheteur.anonymousInfo || 'Client comptoir' }
}

const toLinesPayload = (lines) =>
  lines.map((line) => {
    const payload = {
      quantity: String(line.quantity),
      unit_price: String(line.unitPrice)
    }

    if (line.variantId) {
      payload.variant_id = line.variantId
    } else {
      payload.label = line.label
    }

    return payload
  })

export const useFreeOperationCheckout = () => {
  const salesStore = useSalesStore()
  const ordersStore = useOrdersStore()
  const quotesStore = useQuotesStore()
  const { showSuccess, showError } = useEntityActions()

  const checkoutVisible = ref(false)
  const checkoutMode = ref('sale')
  const submitting = ref(false)
  const freeLines = ref([])
  const acheteur = ref(createEmptyAcheteur())

  const total = computed(() =>
    freeLines.value.reduce((sum, line) => sum + Number(line.quantity || 0) * Number(line.unitPrice || 0), 0)
  )

  const resetForm = () => {
    freeLines.value = []
    acheteur.value = createEmptyAcheteur()
  }

  const openFreeOperation = (mode) => {
    checkoutMode.value = mode
    resetForm()
    checkoutVisible.value = true
  }

  const submitFreeOperation = async ({
    payment,
    operationDate,
    paymentDate,
    deliveryDate,
    confirmOrder,
    validUntil
  }) => {
    if (freeLines.value.length === 0) {
      showError('Ajoutez au moins une ligne libre.')
      return false
    }

    submitting.value = true
    try {
      const payload = {
        acheteur: toAcheteurPayload(acheteur.value),
        lines: toLinesPayload(freeLines.value),
        created_at: toIsoDateTime(operationDate)
      }

      if (checkoutMode.value === 'quote') {
        payload.valid_until = toIsoDate(validUntil)
        const devis = await quotesStore.createQuote(payload)
        showSuccess(`Devis ${devis.reference} enregistré.`)
      } else if (checkoutMode.value === 'sale') {
        if (payment && payment.amount > 0) {
          payload.initial_payment = {
            amount: String(payment.amount),
            mode_de_paiement_id: payment.mode_de_paiement_id,
            paid_at: toIsoDateTime(paymentDate)
          }
        }
        const vente = await salesStore.createSale(payload)
        showSuccess(`Vente ${vente.reference} enregistrée.`)
      } else {
        const commande = await ordersStore.createOrder(payload)

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

        if (confirmOrder) {
          const confirmed = await ordersStore.confirmOrder(commande.id, {
            delivery_date: toIsoDateTime(deliveryDate)
          })
          showSuccess(`Commande ${confirmed.reference} confirmée.`)
        } else {
          showSuccess(`Commande ${commande.reference} créée.`)
        }
      }

      checkoutVisible.value = false
      resetForm()
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
    freeLines,
    acheteur,
    total,
    openFreeOperation,
    submitFreeOperation,
    resetForm
  }
}
