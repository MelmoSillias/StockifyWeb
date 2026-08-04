import { defineStore } from 'pinia'

import { commerceService } from '@/domains/commerce/services/commerceService'
import { extractApiError } from '@/domains/shared/services/http'

export const usePaymentsStore = defineStore('commerce-payments', {
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
        this.items = await commerceService.listPaiements()
        return this.items
      } catch (error) {
        const normalizedError = extractApiError(error)
        this.error = normalizedError.message
        throw normalizedError
      } finally {
        this.loading = false
      }
    },
    async createPayment(payload) {
      this.submitting = true
      this.error = null
      try {
        const item = await commerceService.createPaiement(payload)
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
    async cancelPayment(id) {
      this.pendingIds = [...this.pendingIds, id]
      try {
        const item = await commerceService.cancelPaiement(id)
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
