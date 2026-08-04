import { defineStore } from 'pinia'

import { extractApiError } from '@/domains/shared/services/http'
import { financeService } from '@/domains/finance/services/financeService'

export const usePaymentMethodsStore = defineStore('finance-payment-methods', {
  state: () => ({
    items: [],
    loading: false,
    submitting: false,
    deletingIds: [],
    error: null
  }),
  getters: {
    activeItems: (state) => state.items.filter((item) => item.is_active),
    byCode: (state) => (code) => state.items.find((item) => item.code === code) || null,
    isDeleting: (state) => (id) => state.deletingIds.includes(id)
  },
  actions: {
    async fetchAll({ activeOnly = false } = {}) {
      this.loading = true
      this.error = null
      try {
        this.items = await financeService.listPaymentMethods({ activeOnly })
        return this.items
      } catch (error) {
        this.error = extractApiError(error).message
        throw error
      } finally {
        this.loading = false
      }
    },
    async saveItem(payload) {
      this.submitting = true
      this.error = null
      try {
        const item = payload.id
          ? await financeService.updatePaymentMethod(payload.id, payload)
          : await financeService.createPaymentMethod(payload)
        const index = this.items.findIndex((entry) => entry.id === item.id)
        if (index === -1) {
          this.items = [item, ...this.items]
        } else {
          this.items = this.items.map((entry) => (entry.id === item.id ? item : entry))
        }
        return item
      } catch (error) {
        this.error = extractApiError(error).message
        throw error
      } finally {
        this.submitting = false
      }
    },
    async removeItem(id) {
      this.deletingIds = [...this.deletingIds, id]
      try {
        await financeService.removePaymentMethod(id)
        this.items = this.items.filter((item) => item.id !== id)
      } finally {
        this.deletingIds = this.deletingIds.filter((entry) => entry !== id)
      }
    }
  }
})
