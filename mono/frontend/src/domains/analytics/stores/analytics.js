import { defineStore } from 'pinia'

import { analyticsService } from '@/domains/analytics/services/analyticsService'
import { extractApiError } from '@/domains/shared/services/http'

export const useAnalyticsStore = defineStore('analytics', {
  state: () => ({
    overview: null,
    overviewLoading: false,
    sales: null,
    salesLoading: false,
    payments: null,
    paymentsLoading: false,
    inventory: null,
    inventoryLoading: false,
    purchases: null,
    purchasesLoading: false,
    finance: null,
    financeLoading: false,
    clients: null,
    clientsLoading: false,
    period: { from: null, to: null },
    compare: true,
    error: null,
    activeSection: 'sales'
  }),

  getters: {
    isLoading(state) {
      return state.overviewLoading
        || state.salesLoading
        || state.paymentsLoading
        || state.inventoryLoading
        || state.purchasesLoading
        || state.financeLoading
        || state.clientsLoading
    }
  },

  actions: {
    setPeriod(from, to, compare = true) {
      this.period = { from, to }
      this.compare = compare
    },

    setActiveSection(sectionId) {
      this.activeSection = sectionId
    },

    async fetchOverview() {
      if (!this.period.from || !this.period.to) return null
      this.overviewLoading = true
      this.error = null
      try {
        this.overview = await analyticsService.getOverview({
          ...this.period,
          compare: this.compare
        })
        return this.overview
      } catch (error) {
        const normalizedError = extractApiError(error)
        this.error = normalizedError.message
        throw normalizedError
      } finally {
        this.overviewLoading = false
      }
    },

    async fetchSales() {
      if (!this.period.from || !this.period.to) return null
      this.salesLoading = true
      try {
        this.sales = await analyticsService.getSales(this.period)
        return this.sales
      } catch (error) {
        const normalizedError = extractApiError(error)
        this.error = normalizedError.message
        throw normalizedError
      } finally {
        this.salesLoading = false
      }
    },

    async fetchPayments() {
      if (!this.period.from || !this.period.to) return null
      this.paymentsLoading = true
      try {
        this.payments = await analyticsService.getPayments(this.period)
        return this.payments
      } catch (error) {
        const normalizedError = extractApiError(error)
        this.error = normalizedError.message
        throw normalizedError
      } finally {
        this.paymentsLoading = false
      }
    },

    async fetchInventory() {
      if (!this.period.from || !this.period.to) return null
      this.inventoryLoading = true
      try {
        this.inventory = await analyticsService.getInventory(this.period)
        return this.inventory
      } catch (error) {
        const normalizedError = extractApiError(error)
        this.error = normalizedError.message
        throw normalizedError
      } finally {
        this.inventoryLoading = false
      }
    },

    async fetchPurchases() {
      if (!this.period.from || !this.period.to) return null
      this.purchasesLoading = true
      try {
        this.purchases = await analyticsService.getPurchases(this.period)
        return this.purchases
      } catch (error) {
        const normalizedError = extractApiError(error)
        this.error = normalizedError.message
        throw normalizedError
      } finally {
        this.purchasesLoading = false
      }
    },

    async fetchFinance() {
      if (!this.period.from || !this.period.to) return null
      this.financeLoading = true
      try {
        this.finance = await analyticsService.getFinance(this.period)
        return this.finance
      } catch (error) {
        const normalizedError = extractApiError(error)
        this.error = normalizedError.message
        throw normalizedError
      } finally {
        this.financeLoading = false
      }
    },

    async fetchClients() {
      if (!this.period.from || !this.period.to) return null
      this.clientsLoading = true
      try {
        this.clients = await analyticsService.getClients(this.period)
        return this.clients
      } catch (error) {
        const normalizedError = extractApiError(error)
        this.error = normalizedError.message
        throw normalizedError
      } finally {
        this.clientsLoading = false
      }
    },

    async refreshSection(sectionId) {
      const fetchers = {
        sales: () => this.fetchSales(),
        payments: () => this.fetchPayments(),
        inventory: () => this.fetchInventory(),
        purchases: () => this.fetchPurchases(),
        finance: () => this.fetchFinance(),
        clients: () => this.fetchClients()
      }
      if (fetchers[sectionId]) {
        await fetchers[sectionId]()
      }
    },

    async refreshAll(from, to, compare, sectionIds = []) {
      this.setPeriod(from, to, compare)
      const tasks = [this.fetchOverview()]
      const fetchMap = {
        sales: () => this.fetchSales(),
        payments: () => this.fetchPayments(),
        inventory: () => this.fetchInventory(),
        purchases: () => this.fetchPurchases(),
        finance: () => this.fetchFinance(),
        clients: () => this.fetchClients()
      }
      sectionIds.forEach((id) => {
        if (fetchMap[id]) tasks.push(fetchMap[id]())
      })
      await Promise.all(tasks)
    }
  }
})
