import { defineStore } from 'pinia'

import { extractApiError } from '@/domains/shared/services/http'
import { financeService } from '@/domains/finance/services/financeService'

export const useTransactionsStore = defineStore('finance-transactions', {
  state: () => ({
    items: [],
    loading: false,
    submitting: false,
    cancellingIds: [],
    error: null
  }),
  getters: {
    isCancelling: (state) => (id) => state.cancellingIds.includes(id)
  },
  actions: {
    async fetchAll(filters = {}) {
      this.loading = true
      this.error = null
      try {
        this.items = await financeService.listTransactions(filters)
        return this.items
      } catch (error) {
        this.error = extractApiError(error).message
        throw error
      } finally {
        this.loading = false
      }
    },
    async createItem(payload) {
      this.submitting = true
      this.error = null
      try {
        const item = await financeService.createTransaction(payload)
        this.items = [item, ...this.items]
        return item
      } catch (error) {
        this.error = extractApiError(error).message
        throw error
      } finally {
        this.submitting = false
      }
    },
    async cancelItem(id) {
      this.cancellingIds = [...this.cancellingIds, id]
      try {
        const item = await financeService.cancelTransaction(id)
        this.items = this.items.map((entry) => (entry.id === id ? item : entry))
        return item
      } finally {
        this.cancellingIds = this.cancellingIds.filter((entry) => entry !== id)
      }
    },
    upsertItem(item) {
      const index = this.items.findIndex((entry) => entry.id === item.id)
      if (index === -1) {
        this.items = [item, ...this.items]
      } else {
        this.items = this.items.map((entry) => (entry.id === item.id ? item : entry))
      }
    }
  }
})
