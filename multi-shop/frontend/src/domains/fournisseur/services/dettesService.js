import { apiClient } from '@/domains/shared/services/http'

export const dettesService = {
  async list(params = {}) {
    const response = await apiClient.get('/dettes-fournisseur', { params })
    return Array.isArray(response.data) ? response.data : []
  },

  async getById(id) {
    const response = await apiClient.get(`/dettes-fournisseur/${id}`)
    return response.data
  },

  async create(payload) {
    const response = await apiClient.post('/dettes-fournisseur', payload)
    return response.data
  },

  async createPaiement(payload) {
    const response = await apiClient.post('/paiements-fournisseur', payload)
    return response.data
  },

  async cancelPaiement(id) {
    const response = await apiClient.post(`/paiements-fournisseur/${id}/cancel`)
    return response.data
  }
}
