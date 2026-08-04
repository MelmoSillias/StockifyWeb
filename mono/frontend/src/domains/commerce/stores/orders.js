import { defineStore } from 'pinia'

import { commerceService } from '@/domains/commerce/services/commerceService'
import { extractApiError } from '@/domains/shared/services/http'

export const useOrdersStore = defineStore('commerce-orders', {
  state: () => ({
    items: [],
    loading: false,
    submitting: false,
    pendingIds: [],
    error: null
  }),
  getters: {
    isPending: (state) => (id) => state.pendingIds.includes(id)
  },
  actions: {
    upsert(item) {
      const index = this.items.findIndex((entry) => entry.id === item.id)
      if (index === -1) {
        this.items = [item, ...this.items]
      } else {
        this.items = this.items.map((entry) => (entry.id === item.id ? item : entry))
      }
    },
    async fetchAll() {
      this.loading = true
      this.error = null
      try {
        this.items = await commerceService.listCommandes()
        return this.items
      } catch (error) {
        const normalizedError = extractApiError(error)
        this.error = normalizedError.message
        throw normalizedError
      } finally {
        this.loading = false
      }
    },
    async createOrder(payload) {
      this.submitting = true
      this.error = null
      try {
        const item = await commerceService.createCommande(payload)
        this.upsert(item)
        return item
      } catch (error) {
        const normalizedError = extractApiError(error)
        this.error = normalizedError.message
        throw normalizedError
      } finally {
        this.submitting = false
      }
    },
    async confirmOrder(id, payload = {}) {
      this.pendingIds = [...this.pendingIds, id]
      try {
        const item = await commerceService.confirmCommande(id, payload)
        this.upsert(item)
        return item
      } catch (error) {
        throw extractApiError(error)
      } finally {
        this.pendingIds = this.pendingIds.filter((entry) => entry !== id)
      }
    },
    async cancelOrder(id) {
      this.pendingIds = [...this.pendingIds, id]
      try {
        const item = await commerceService.cancelCommande(id)
        this.upsert(item)
        return item
      } catch (error) {
        throw extractApiError(error)
      } finally {
        this.pendingIds = this.pendingIds.filter((entry) => entry !== id)
      }
    }
  }
})
