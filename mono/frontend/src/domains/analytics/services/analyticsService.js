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

const periodParams = ({ from, to, compare = true }) => ({
  from: toIsoDateStart(from),
  to: toIsoDateEnd(to),
  compare
})

export const analyticsService = {
  async getOverview(period) {
    const response = await apiClient.get('/analytics/overview', {
      params: periodParams(period)
    })
    return response.data
  },

  async getSales(period) {
    const response = await apiClient.get('/analytics/sales', {
      params: periodParams(period)
    })
    return response.data
  },

  async getPayments(period) {
    const response = await apiClient.get('/analytics/payments', {
      params: periodParams(period)
    })
    return response.data
  },

  async getInventory(period) {
    const response = await apiClient.get('/analytics/inventory', {
      params: periodParams(period)
    })
    return response.data
  },

  async getPurchases(period) {
    const response = await apiClient.get('/analytics/purchases', {
      params: periodParams(period)
    })
    return response.data
  },

  async getFinance(period) {
    const response = await apiClient.get('/analytics/finance', {
      params: periodParams(period)
    })
    return response.data
  },

  async getClients(period) {
    const response = await apiClient.get('/analytics/clients', {
      params: periodParams(period)
    })
    return response.data
  }
}
