import { defineStore } from 'pinia'

import { extractApiError } from '@/domains/shared/services/http'

export const createCrudStore = (storeId, service) => defineStore(storeId, {
  state: () => ({
    items: [],
    currentItem: null,
    loading: false,
    detailLoading: false,
    submitting: false,
    deletingIds: [],
    error: null
  }),
  getters: {
    byId: (state) => (id) => state.items.find((item) => item.id === id) || null,
    isDeleting: (state) => (id) => state.deletingIds.includes(id)
  },
  actions: {
    setItems(items) {
      this.items = Array.isArray(items) ? items : []
    },
    upsertItem(item) {
      const existingIndex = this.items.findIndex((entry) => entry.id === item.id)

      if (existingIndex === -1) {
        this.items = [item, ...this.items]
      } else {
        this.items = this.items.map((entry) => (entry.id === item.id ? item : entry))
      }

      if (this.currentItem?.id === item.id) {
        this.currentItem = item
      }
    },
    removeLocalItem(id) {
      this.items = this.items.filter((item) => item.id !== id)

      if (this.currentItem?.id === id) {
        this.currentItem = null
      }
    },
    async fetchAll() {
      this.loading = true
      this.error = null

      try {
        const items = await service.list()
        this.setItems(items)
        return items
      } catch (error) {
        const normalizedError = extractApiError(error)
        this.error = normalizedError.message
        throw normalizedError
      } finally {
        this.loading = false
      }
    },
    async fetchById(id, { force = false } = {}) {
      if (!force) {
        const cachedItem = this.byId(id)

        if (cachedItem) {
          this.currentItem = cachedItem
          return cachedItem
        }
      }

      this.detailLoading = true
      this.error = null

      try {
        const item = await service.get(id)
        this.currentItem = item
        this.upsertItem(item)
        return item
      } catch (error) {
        const normalizedError = extractApiError(error)
        this.error = normalizedError.message
        throw normalizedError
      } finally {
        this.detailLoading = false
      }
    },
    async createItem(payload) {
      this.submitting = true
      this.error = null

      try {
        const item = await service.create(payload)
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
    async updateItem(id, payload) {
      this.submitting = true
      this.error = null

      try {
        const item = await service.update(id, payload)
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
    async saveItem(payload) {
      return payload?.id ? this.updateItem(payload.id, payload) : this.createItem(payload)
    },
    async removeItem(id) {
      this.deletingIds = [...this.deletingIds, id]
      this.error = null

      try {
        await service.remove(id)
        this.removeLocalItem(id)
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