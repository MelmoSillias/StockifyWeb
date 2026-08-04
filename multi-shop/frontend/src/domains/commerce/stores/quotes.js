import { defineStore } from 'pinia'

import { commerceService } from '@/domains/commerce/services/commerceService'
import { extractApiError } from '@/domains/shared/services/http'

export const useQuotesStore = defineStore('commerce-quotes', {
  state: () => ({
    items: [],
    loading: false,
    submitting: false,
    error: null
  }),
  actions: {
    upsertItem(item) {
      const index = this.items.findIndex((entry) => entry.id === item.id)
      if (index === -1) {
        this.items = [item, ...this.items]
        return
      }
      this.items = this.items.map((entry, i) => (i === index ? item : entry))
    },
    async fetchAll() {
      this.loading = true
      this.error = null
      try {
        this.items = await commerceService.listDevis()
        return this.items
      } catch (error) {
        const normalizedError = extractApiError(error)
        this.error = normalizedError.message
        throw normalizedError
      } finally {
        this.loading = false
      }
    },
    async createQuote(payload) {
      this.submitting = true
      this.error = null
      try {
        const item = await commerceService.createDevis(payload)
        this.upsertItem(item)
        return item
      } catch (error) {
        const normalizedError = extractApiError(error)
        this.error = normalizedError.message
        throw normalizedError
      } finally {
        this.submitting = false
      }
    },
    async cancelQuote(id) {
      try {
        const item = await commerceService.cancelDevis(id)
        this.upsertItem(item)
        return item
      } catch (error) {
        throw extractApiError(error)
      }
    }
  }
})
