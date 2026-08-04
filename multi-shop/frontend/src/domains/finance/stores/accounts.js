import { defineStore } from 'pinia'

import { extractApiError } from '@/domains/shared/services/http'
import { financeService } from '@/domains/finance/services/financeService'

export const useAccountsStore = defineStore('finance-accounts', {
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
        this.items = await financeService.listComptes()
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
          ? await financeService.updateCompte(payload.id, payload)
          : await financeService.createCompte(payload)
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
