import { defineStore } from 'pinia'

import { extractApiError } from '@/domains/shared/services/http'
import { inventoryService } from '@/domains/inventory/services/inventoryService'

export const useInventoryStore = defineStore('inventory', {
  state: () => ({
    lots: [],
    movements: [],
    alerts: [],
    stock: null,
    loading: false,
    submitting: false,
    error: null
  }),
  actions: {
    async fetchLots(variantId) {
      this.loading = true
      this.error = null

      try {
        const [lots, stock] = await Promise.all([
          inventoryService.listLots(variantId),
          inventoryService.getStock(variantId)
        ])
        this.lots = lots
        this.stock = stock
        return { lots, stock }
      } catch (error) {
        const normalizedError = extractApiError(error)
        this.error = normalizedError.message
        throw normalizedError
      } finally {
        this.loading = false
      }
    },
    async receiveLot(variantId, payload) {
      this.submitting = true
      this.error = null

      try {
        const lot = await inventoryService.receiveLot(variantId, payload)
        await this.fetchLots(variantId)
        return lot
      } catch (error) {
        const normalizedError = extractApiError(error)
        this.error = normalizedError.message
        throw normalizedError
      } finally {
        this.submitting = false
      }
    },
    async stockOut(variantId, payload) {
      this.submitting = true
      this.error = null

      try {
        const movement = await inventoryService.stockOut(variantId, payload)
        await this.fetchLots(variantId)
        return movement
      } catch (error) {
        const normalizedError = extractApiError(error)
        this.error = normalizedError.message
        throw normalizedError
      } finally {
        this.submitting = false
      }
    },
    async adjust(variantId, payload) {
      this.submitting = true
      this.error = null

      try {
        const movement = await inventoryService.adjust(variantId, payload)
        await this.fetchLots(variantId)
        return movement
      } catch (error) {
        const normalizedError = extractApiError(error)
        this.error = normalizedError.message
        throw normalizedError
      } finally {
        this.submitting = false
      }
    },
    async fetchMovements(variantId = null) {
      this.loading = true
      this.error = null

      try {
        this.movements = await inventoryService.listMovements(variantId)
        return this.movements
      } catch (error) {
        const normalizedError = extractApiError(error)
        this.error = normalizedError.message
        throw normalizedError
      } finally {
        this.loading = false
      }
    },
    async fetchAlerts() {
      this.loading = true
      this.error = null

      try {
        this.alerts = await inventoryService.listAlerts()
        return this.alerts
      } catch (error) {
        const normalizedError = extractApiError(error)
        this.error = normalizedError.message
        throw normalizedError
      } finally {
        this.loading = false
      }
    }
  }
})
