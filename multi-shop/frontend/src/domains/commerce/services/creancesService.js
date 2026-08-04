import { apiClient } from '@/domains/shared/services/http'

export const creancesService = {
  async list(params = {}) {
    const response = await apiClient.get('/creances', { params })
    return Array.isArray(response.data) ? response.data : []
  },

  async listByClient(clientId, params = {}) {
    const response = await apiClient.get(`/clients/${clientId}/creances`, { params })
    return Array.isArray(response.data) ? response.data : []
  }
}
