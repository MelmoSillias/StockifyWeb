import { defineStore } from 'pinia'

import { appConfig } from '@/config/app'

const cartStorageKey = appConfig.storage.commerceCartKey

const createEmptyAcheteur = () => ({
  type: 'anonymous',
  clientId: null,
  anonymousInfo: ''
})

const createInitialState = () => {
  const defaults = {
    lines: [],
    acheteur: createEmptyAcheteur(),
    linkedSaleId: null,
    linkedSaleReference: null,
    linkedOrderId: null,
    linkedOrderReference: null,
    linkedOrderStatus: null,
    updatedAt: null
  }

  try {
    const storedValue = localStorage.getItem(cartStorageKey)
    if (!storedValue) {
      return defaults
    }

    return {
      ...defaults,
      ...JSON.parse(storedValue)
    }
  } catch {
    return defaults
  }
}

const toMoney = (value) => {
  const numeric = Number(value)
  return Number.isFinite(numeric) ? numeric : 0
}

export const useCartStore = defineStore('commerce-cart', {
  state: () => createInitialState(),

  getters: {
    isEmpty: (state) => state.lines.length === 0,
    itemCount: (state) => state.lines.reduce((total, line) => total + Number(line.quantity || 0), 0),
    subtotal: (state) =>
      state.lines.reduce((total, line) => total + toMoney(line.quantity) * toMoney(line.unitPrice), 0),
    estimatedCost: (state) =>
      state.lines.reduce((total, line) => {
        if (line.unitCost === null || line.unitCost === undefined || line.unitCost === '') {
          return total
        }
        return total + toMoney(line.quantity) * toMoney(line.unitCost)
      }, 0),
    hasCostData: (state) =>
      state.lines.length > 0 &&
      state.lines.every((line) => line.unitCost !== null && line.unitCost !== undefined && line.unitCost !== ''),
    estimatedProfit() {
      return this.subtotal - this.estimatedCost
    },
    status(state) {
      if (state.linkedSaleId) {
        return 'vendu'
      }
      if (state.linkedOrderId) {
        return state.linkedOrderStatus === 'confirmee' ? 'commande_confirmee' : 'commande_initiee'
      }
      if (state.lines.length === 0) {
        return 'vide'
      }
      return 'non_enregistre'
    },
    isCheckedOut: (state) => Boolean(state.linkedSaleId || state.linkedOrderId),
    acheteurPayload: (state) => {
      if (state.acheteur.type === 'client' && state.acheteur.clientId) {
        return { client_id: state.acheteur.clientId, anonymous_info: null }
      }
      return { client_id: null, anonymous_info: state.acheteur.anonymousInfo || 'Client comptoir' }
    },
    linesPayload: (state) =>
      state.lines.map((line) => ({
        variant_id: line.variantId,
        quantity: String(line.quantity),
        unit_price: String(line.unitPrice)
      }))
  },

  actions: {
    persist() {
      this.updatedAt = new Date().toISOString()
      try {
        localStorage.setItem(
          cartStorageKey,
          JSON.stringify({
            lines: this.lines,
            acheteur: this.acheteur,
            linkedSaleId: this.linkedSaleId,
            linkedSaleReference: this.linkedSaleReference,
            linkedOrderId: this.linkedOrderId,
            linkedOrderReference: this.linkedOrderReference,
            linkedOrderStatus: this.linkedOrderStatus,
            updatedAt: this.updatedAt
          })
        )
      } catch {
        // Ignore storage failures (private mode, quota, etc.).
      }
    },
    addLine(line) {
      if (this.isCheckedOut) {
        this.reset()
      }

      const existing = this.lines.find((entry) => entry.variantId === line.variantId)
      if (existing) {
        existing.quantity = Number(existing.quantity) + Number(line.quantity)
        existing.unitPrice = line.unitPrice
      } else {
        this.lines.push({
          variantId: line.variantId,
          label: line.label,
          sku: line.sku ?? null,
          quantity: Number(line.quantity),
          unitPrice: line.unitPrice,
          unitCost: line.unitCost ?? null,
          available: line.available ?? null
        })
      }
      this.persist()
    },
    updateLineQuantity(variantId, quantity) {
      const line = this.lines.find((entry) => entry.variantId === variantId)
      if (line) {
        line.quantity = Number(quantity)
        this.persist()
      }
    },
    updateLinePrice(variantId, unitPrice) {
      const line = this.lines.find((entry) => entry.variantId === variantId)
      if (line) {
        line.unitPrice = unitPrice
        this.persist()
      }
    },
    removeLine(variantId) {
      this.lines = this.lines.filter((entry) => entry.variantId !== variantId)
      this.persist()
    },
    setAcheteur(acheteur) {
      this.acheteur = { ...createEmptyAcheteur(), ...acheteur }
      this.persist()
    },
    markAsSale(vente) {
      this.linkedSaleId = vente.id
      this.linkedSaleReference = vente.reference
      this.linkedOrderId = null
      this.linkedOrderReference = null
      this.persist()
    },
    markAsOrder(commande) {
      this.linkedOrderId = commande.id
      this.linkedOrderReference = commande.reference
      this.linkedOrderStatus = commande.status
      this.linkedSaleId = null
      this.linkedSaleReference = null
      this.persist()
    },
    reset() {
      this.lines = []
      this.acheteur = createEmptyAcheteur()
      this.linkedSaleId = null
      this.linkedSaleReference = null
      this.linkedOrderId = null
      this.linkedOrderReference = null
      this.linkedOrderStatus = null
      this.persist()
    }
  }
})
