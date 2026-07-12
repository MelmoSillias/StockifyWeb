import { defineStore } from 'pinia'

import { extractApiError } from '@/domains/shared/services/http'
import { unitsService } from '@/domains/catalog/services/unitsService'

export const useUnitsStore = defineStore('catalog-units', {
  state: () => ({
    items: [],
    loading: false,
    error: null
  }),
  actions: {
    async fetchAll() {
      if (this.items.length) {
        return this.items
      }

      this.loading = true
      this.error = null

      try {
        this.items = await unitsService.list()
        return this.items
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
