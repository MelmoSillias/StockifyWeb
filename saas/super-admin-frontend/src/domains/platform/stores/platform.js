import { defineStore } from 'pinia'

import { extractApiError } from '@/domains/shared/services/http'
import { platformService } from '@/domains/platform/services/platformService'

export const usePlatformStore = defineStore('platform', {
  state: () => ({
    health: null,
    stats: null,
    accounts: [],
    shops: [],
    currentAccount: null,
    loading: false,
    detailLoading: false,
    error: null
  }),
  actions: {
    async fetchDashboard() {
      this.loading = true
      this.error = null

      try {
        const [health, stats] = await Promise.all([
          platformService.fetchHealth(),
          platformService.fetchStats()
        ])
        this.health = health
        this.stats = stats
        return { health, stats }
      } catch (error) {
        const normalizedError = extractApiError(error)
        this.error = normalizedError.message
        throw normalizedError
      } finally {
        this.loading = false
      }
    },
    async fetchAccounts() {
      this.loading = true
      this.error = null

      try {
        this.accounts = await platformService.listAccounts()
        return this.accounts
      } catch (error) {
        const normalizedError = extractApiError(error)
        this.error = normalizedError.message
        throw normalizedError
      } finally {
        this.loading = false
      }
    },
    async fetchAccount(id) {
      this.detailLoading = true
      this.error = null

      try {
        this.currentAccount = await platformService.getAccount(id)
        return this.currentAccount
      } catch (error) {
        const normalizedError = extractApiError(error)
        this.error = normalizedError.message
        throw normalizedError
      } finally {
        this.detailLoading = false
      }
    },
    async fetchShops() {
      this.loading = true
      this.error = null

      try {
        this.shops = await platformService.listShops()
        return this.shops
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
