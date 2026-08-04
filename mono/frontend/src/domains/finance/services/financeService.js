import { apiClient } from '@/domains/shared/services/http'
import { emptyToNull, toIsoDateTime } from '@/domains/shared/services/createCrudService'

export const financeService = {
  async listComptes() {
    const response = await apiClient.get('/comptes')
    return Array.isArray(response.data) ? response.data : []
  },

  async getCompte(id) {
    const response = await apiClient.get(`/comptes/${id}`)
    return response.data
  },

  async createCompte(payload) {
    const response = await apiClient.post('/comptes', {
      name: emptyToNull(payload.name),
      type: payload.type || 'caisse',
      is_active: payload.is_active !== false
    })
    return response.data
  },

  async updateCompte(id, payload) {
    const response = await apiClient.put(`/comptes/${id}`, {
      name: emptyToNull(payload.name),
      type: payload.type,
      is_active: payload.is_active
    })
    return response.data
  },

  async listTransactions(filters = {}) {
    const params = {}
    if (filters.compte_id) params.compte_id = filters.compte_id
    if (filters.type) params.type = filters.type
    if (filters.from) params.from = filters.from
    if (filters.to) params.to = filters.to

    const response = await apiClient.get('/transactions', { params })
    return Array.isArray(response.data) ? response.data : []
  },

  async createTransaction(payload) {
    const response = await apiClient.post('/transactions', {
      compte_id: payload.compte_id,
      type: payload.type,
      amount: String(payload.amount),
      label: emptyToNull(payload.label),
      description: emptyToNull(payload.description),
      occurred_at: payload.occurred_at ? toIsoDateTime(payload.occurred_at) : undefined
    })
    return response.data
  },

  async cancelTransaction(id) {
    const response = await apiClient.post(`/transactions/${id}/cancel`)
    return response.data
  },

  async listPaymentMethods({ activeOnly = false } = {}) {
    const response = await apiClient.get('/modes-de-paiement', {
      params: activeOnly ? { active_only: true } : {}
    })
    return Array.isArray(response.data) ? response.data : []
  },

  async createPaymentMethod(payload) {
    const response = await apiClient.post('/modes-de-paiement', {
      code: payload.code,
      label: emptyToNull(payload.label),
      compte_id: payload.compte_id,
      is_active: payload.is_active !== false,
      generates_transaction: payload.generates_transaction !== false
    })
    return response.data
  },

  async updatePaymentMethod(id, payload) {
    const response = await apiClient.put(`/modes-de-paiement/${id}`, {
      label: emptyToNull(payload.label),
      compte_id: payload.compte_id,
      is_active: payload.is_active,
      generates_transaction: payload.generates_transaction
    })
    return response.data
  },

  async removePaymentMethod(id) {
    await apiClient.delete(`/modes-de-paiement/${id}`)
    return id
  }
}
