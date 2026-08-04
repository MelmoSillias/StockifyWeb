import { defineStore } from 'pinia'

import { extractApiError } from '@/domains/shared/services/http'
import { variantsService } from '@/domains/catalog/services/variantsService'

export const useVariantsStore = defineStore('catalog-variants', {
  state: () => ({
    items: [],
    productId: null,
    loading: false,
    submitting: false,
    deletingIds: [],
    error: null
  }),
  getters: {
    isDeleting: (state) => (id) => state.deletingIds.includes(id)
  },
  actions: {
    async fetchForProduct(productId) {
      this.loading = true
      this.error = null
      this.productId = productId

      try {
        this.items = await variantsService.list(productId)
        return this.items
      } catch (error) {
        const normalizedError = extractApiError(error)
        this.error = normalizedError.message
        throw normalizedError
      } finally {
        this.loading = false
      }
    },
    async saveItem(payload) {
      this.submitting = true
      this.error = null

      try {
        const item = payload?.id
          ? await variantsService.update(payload.id, payload)
          : await variantsService.create(this.productId, payload)

        const existingIndex = this.items.findIndex((entry) => entry.id === item.id)
        if (existingIndex === -1) {
          this.items = [item, ...this.items]
        } else {
          this.items = this.items.map((entry) => (entry.id === item.id ? item : entry))
        }

        return item
      } catch (error) {
        const normalizedError = extractApiError(error)
        this.error = normalizedError.message
        throw normalizedError
      } finally {
        this.submitting = false
      }
    },
    async removeItem(id) {
      this.deletingIds = [...this.deletingIds, id]
      this.error = null

      try {
        await variantsService.remove(id)
        this.items = this.items.filter((item) => item.id !== id)
        return id
      } catch (error) {
        const normalizedError = extractApiError(error)
        this.error = normalizedError.message
        throw normalizedError
      } finally {
        this.deletingIds = this.deletingIds.filter((entry) => entry !== id)
      }
    }
  }
})
