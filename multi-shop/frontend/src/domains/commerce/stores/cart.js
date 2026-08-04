import { defineStore } from 'pinia'

import { appConfig } from '@/config/app'

const cartStorageKey = appConfig.storage.commerceCartKey

const createLineId = () => {
  if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
    return crypto.randomUUID()
  }
  return `line-${Date.now()}-${Math.random().toString(36).slice(2, 9)}`
}

const createEmptyAcheteur = () => ({
  type: 'anonymous',
  clientId: null,
  anonymousInfo: ''
})

const normalizeStoredLine = (line) => ({
  lineId: line.lineId || createLineId(),
  variantId: line.variantId ?? null,
  lineType: line.lineType || (line.variantId ? 'product' : 'libre'),
  label: line.label,
  sku: line.sku ?? null,
  quantity: Number(line.quantity),
  unitPrice: line.unitPrice,
  unitCost: line.unitCost ?? null,
  available: line.available ?? null
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
    linkedQuoteId: null,
    linkedQuoteReference: null,
    updatedAt: null
  }

  try {
    const storedValue = localStorage.getItem(cartStorageKey)
    if (!storedValue) {
      return defaults
    }

    const parsed = JSON.parse(storedValue)

    return {
      ...defaults,
      ...parsed,
      lines: Array.isArray(parsed.lines) ? parsed.lines.map(normalizeStoredLine) : []
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
      if (state.linkedQuoteId) {
        return 'devis'
      }
      if (state.lines.length === 0) {
        return 'vide'
      }
      return 'non_enregistre'
    },
    isCheckedOut: (state) => Boolean(state.linkedSaleId || state.linkedOrderId || state.linkedQuoteId),
    acheteurPayload: (state) => {
      if (state.acheteur.type === 'client' && state.acheteur.clientId) {
        return { client_id: state.acheteur.clientId, anonymous_info: null }
      }
      return { client_id: null, anonymous_info: state.acheteur.anonymousInfo || 'Client comptoir' }
    },
    linesPayload: (state) =>
      state.lines.map((line) => {
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
            linkedQuoteId: this.linkedQuoteId,
            linkedQuoteReference: this.linkedQuoteReference,
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

      const existing = this.lines.find((entry) => entry.variantId && entry.variantId === line.variantId)
      if (existing) {
        existing.quantity = Number(existing.quantity) + Number(line.quantity)
        existing.unitPrice = line.unitPrice
      } else {
        this.lines.push({
          lineId: createLineId(),
          variantId: line.variantId,
          lineType: 'product',
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
    addFreeLine({ label, quantity, unitPrice }) {
      if (this.isCheckedOut) {
        this.reset()
      }

      this.lines.push({
        lineId: createLineId(),
        variantId: null,
        lineType: 'libre',
        label: String(label).trim(),
        sku: null,
        quantity: Number(quantity),
        unitPrice: unitPrice,
        unitCost: null,
        available: null
      })
      this.persist()
    },
    updateLineQuantity(lineId, quantity) {
      const line = this.lines.find((entry) => entry.lineId === lineId)
      if (line) {
        line.quantity = Number(quantity)
        this.persist()
      }
    },
    updateLinePrice(lineId, unitPrice) {
      const line = this.lines.find((entry) => entry.lineId === lineId)
      if (line) {
        line.unitPrice = unitPrice
        this.persist()
      }
    },
    removeLine(lineId) {
      this.lines = this.lines.filter((entry) => entry.lineId !== lineId)
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
      this.linkedQuoteId = null
      this.linkedQuoteReference = null
      this.persist()
    },
    markAsOrder(commande) {
      this.linkedOrderId = commande.id
      this.linkedOrderReference = commande.reference
      this.linkedOrderStatus = commande.status
      this.linkedSaleId = null
      this.linkedSaleReference = null
      this.linkedQuoteId = null
      this.linkedQuoteReference = null
      this.persist()
    },
    markAsQuote(devis) {
      this.linkedQuoteId = devis.id
      this.linkedQuoteReference = devis.reference
      this.linkedSaleId = null
      this.linkedSaleReference = null
      this.linkedOrderId = null
      this.linkedOrderReference = null
      this.linkedOrderStatus = null
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
      this.linkedQuoteId = null
      this.linkedQuoteReference = null
      this.persist()
    }
  }
})
