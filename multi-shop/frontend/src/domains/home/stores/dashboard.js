import { defineStore } from 'pinia'

import { dashboardService } from '@/domains/home/services/dashboardService'
import { extractApiError } from '@/domains/shared/services/http'

const defaultFetchOptions = {
  fetchSummary: true,
  fetchFeed: true,
  fetchSalesTrend: true,
  fetchPendingDeliveries: true,
  fetchPendingSupplierOrders: false,
  fetchFinanceSummary: false,
  fetchRecentAudit: false
}

export const useDashboardStore = defineStore('dashboard', {
  state: () => ({
    summary: null,
    summaryLoading: false,
    feed: null,
    feedLoading: false,
    salesTrend: null,
    salesTrendLoading: false,
    pendingDeliveries: null,
    pendingDeliveriesLoading: false,
    pendingSupplierOrders: null,
    pendingSupplierOrdersLoading: false,
    financeSummary: null,
    financeSummaryLoading: false,
    recentAudit: null,
    recentAuditLoading: false,
    period: {
      from: null,
      to: null
    },
    error: null
  }),

  getters: {
    isLoading(state) {
      return state.summaryLoading
        || state.feedLoading
        || state.salesTrendLoading
        || state.pendingDeliveriesLoading
        || state.pendingSupplierOrdersLoading
        || state.financeSummaryLoading
        || state.recentAuditLoading
    }
  },

  actions: {
    setPeriod(from, to) {
      this.period = { from, to }
    },

    async fetchSummary() {
      if (!this.period.from || !this.period.to) {
        return null
      }

      this.summaryLoading = true
      this.error = null
      try {
        this.summary = await dashboardService.getSummary(this.period)
        return this.summary
      } catch (error) {
        const normalizedError = extractApiError(error)
        this.error = normalizedError.message
        throw normalizedError
      } finally {
        this.summaryLoading = false
      }
    },

    async fetchFeed() {
      if (!this.period.from || !this.period.to) {
        return null
      }

      this.feedLoading = true
      this.error = null
      try {
        this.feed = await dashboardService.getFeed(this.period)
        return this.feed
      } catch (error) {
        const normalizedError = extractApiError(error)
        this.error = normalizedError.message
        throw normalizedError
      } finally {
        this.feedLoading = false
      }
    },

    async fetchSalesTrend() {
      if (!this.period.from || !this.period.to) {
        return null
      }

      this.salesTrendLoading = true
      this.error = null
      try {
        this.salesTrend = await dashboardService.getSalesTrend(this.period)
        return this.salesTrend
      } catch (error) {
        const normalizedError = extractApiError(error)
        this.error = normalizedError.message
        throw normalizedError
      } finally {
        this.salesTrendLoading = false
      }
    },

    async fetchPendingDeliveries() {
      this.pendingDeliveriesLoading = true
      this.error = null
      try {
        this.pendingDeliveries = await dashboardService.getPendingDeliveries()
        return this.pendingDeliveries
      } catch (error) {
        const normalizedError = extractApiError(error)
        this.error = normalizedError.message
        throw normalizedError
      } finally {
        this.pendingDeliveriesLoading = false
      }
    },

    async fetchPendingSupplierOrders() {
      this.pendingSupplierOrdersLoading = true
      this.error = null
      try {
        this.pendingSupplierOrders = await dashboardService.getPendingSupplierOrders()
        return this.pendingSupplierOrders
      } catch (error) {
        const normalizedError = extractApiError(error)
        this.error = normalizedError.message
        throw normalizedError
      } finally {
        this.pendingSupplierOrdersLoading = false
      }
    },

    async fetchFinanceSummary() {
      this.financeSummaryLoading = true
      this.error = null
      try {
        this.financeSummary = await dashboardService.getFinanceSummary()
        return this.financeSummary
      } catch (error) {
        const normalizedError = extractApiError(error)
        this.error = normalizedError.message
        throw normalizedError
      } finally {
        this.financeSummaryLoading = false
      }
    },

    async fetchRecentAudit() {
      this.recentAuditLoading = true
      this.error = null
      try {
        this.recentAudit = await dashboardService.getRecentAudit()
        return this.recentAudit
      } catch (error) {
        const normalizedError = extractApiError(error)
        this.error = normalizedError.message
        throw normalizedError
      } finally {
        this.recentAuditLoading = false
      }
    },

    async refreshPeriodData(from, to, options = defaultFetchOptions) {
      this.setPeriod(from, to)

      const tasks = []
      if (options.fetchSummary) {
        tasks.push(this.fetchSummary())
      }
      if (options.fetchFeed) {
        tasks.push(this.fetchFeed())
      }
      if (options.fetchSalesTrend) {
        tasks.push(this.fetchSalesTrend())
      }

      await Promise.all(tasks)
    },

    async refreshStaticData(options = defaultFetchOptions) {
      const tasks = []
      if (options.fetchPendingDeliveries) {
        tasks.push(this.fetchPendingDeliveries())
      }
      if (options.fetchPendingSupplierOrders) {
        tasks.push(this.fetchPendingSupplierOrders())
      }
      if (options.fetchFinanceSummary) {
        tasks.push(this.fetchFinanceSummary())
      }
      if (options.fetchRecentAudit) {
        tasks.push(this.fetchRecentAudit())
      }

      await Promise.all(tasks)
    },

    async refreshAll(from, to, options = defaultFetchOptions) {
      await Promise.all([
        this.refreshPeriodData(from, to, options),
        this.refreshStaticData(options)
      ])
    }
  }
})
