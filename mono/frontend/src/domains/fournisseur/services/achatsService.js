import { apiClient } from '@/domains/shared/services/http'

export const achatsService = {
  async list() {
    const response = await apiClient.get('/commandes-fournisseur')
    return Array.isArray(response.data) ? response.data : []
  },

  async getById(id) {
    const response = await apiClient.get(`/commandes-fournisseur/${id}`)
    return response.data
  },

  async create(payload) {
    const response = await apiClient.post('/commandes-fournisseur', payload)
    return response.data
  },

  async confirm(id, payload = {}) {
    const response = await apiClient.post(`/commandes-fournisseur/${id}/confirm`, payload)
    return response.data
  },

  async recevoir(id, payload) {
    const response = await apiClient.post(`/commandes-fournisseur/${id}/recevoir`, payload)
    return response.data
  },

  async cancel(id) {
    const response = await apiClient.post(`/commandes-fournisseur/${id}/cancel`)
    return response.data
  }
}
