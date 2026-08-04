import { defineStore } from 'pinia'

import { commerceService } from '@/domains/commerce/services/commerceService'
import { extractApiError } from '@/domains/shared/services/http'

export const useSalesStore = defineStore('commerce-sales', {
  state: () => ({
    items: [],
    loading: false,
    submitting: false,
    error: null
  }),
  actions: {
    async fetchAll() {
      this.loading = true
      this.error = null
      try {
        this.items = await commerceService.listVentes()
        return this.items
      } catch (error) {
        const normalizedError = extractApiError(error)
        this.error = normalizedError.message
        throw normalizedError
      } finally {
        this.loading = false
      }
    },
    async createSale(payload) {
      this.submitting = true
      this.error = null
      try {
        const item = await commerceService.createVente(payload)
        this.items = [item, ...this.items]
        return item
      } catch (error) {
        const normalizedError = extractApiError(error)
        this.error = normalizedError.message
        throw normalizedError
      } finally {
        this.submitting = false
      }
    },
    upsertItem(item) {
      const index = this.items.findIndex((entry) => entry.id === item.id)
      if (index === -1) {
        this.items = [item, ...this.items]
        return
      }
      this.items = this.items.map((entry, i) => (i === index ? item : entry))
    }
  }
})
