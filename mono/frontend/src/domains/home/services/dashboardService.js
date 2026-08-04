import { apiClient } from '@/domains/shared/services/http'

const toIsoDateStart = (date) => {
  const normalized = new Date(date)
  normalized.setHours(0, 0, 0, 0)
  return normalized.toISOString()
}

const toIsoDateEnd = (date) => {
  const normalized = new Date(date)
  normalized.setHours(23, 59, 59, 999)
  return normalized.toISOString()
}

export const dashboardService = {
  async getSummary({ from, to }) {
    const response = await apiClient.get('/dashboard/summary', {
      params: {
        from: toIsoDateStart(from),
        to: toIsoDateEnd(to)
      }
    })
    return response.data
  },

  async getFeed({ from, to, limit = 5 }) {
    const response = await apiClient.get('/dashboard/feed', {
      params: {
        from: toIsoDateStart(from),
        to: toIsoDateEnd(to),
        limit
      }
    })
    return response.data
  },

  async getSalesTrend({ from, to }) {
    const response = await apiClient.get('/dashboard/sales-trend', {
      params: {
        from: toIsoDateStart(from),
        to: toIsoDateEnd(to)
      }
    })
    return response.data
  },

  async getPendingDeliveries({ limit = 10 } = {}) {
    const response = await apiClient.get('/dashboard/pending-deliveries', {
      params: { limit }
    })
    return response.data
  },

  async getPendingSupplierOrders({ limit = 10 } = {}) {
    const response = await apiClient.get('/dashboard/pending-supplier-orders', {
      params: { limit }
    })
    return response.data
  },

  async getFinanceSummary() {
    const response = await apiClient.get('/dashboard/finance-summary')
    return response.data
  },

  async getRecentAudit({ limit = 5 } = {}) {
    const response = await apiClient.get('/dashboard/recent-audit', {
      params: { limit }
    })
    return response.data
  }
}
