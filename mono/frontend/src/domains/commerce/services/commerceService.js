import { apiClient } from '@/domains/shared/services/http'

export const commerceService = {
  async listVentes() {
    const response = await apiClient.get('/ventes')
    return Array.isArray(response.data) ? response.data : []
  },
  async getVente(id) {
    const response = await apiClient.get(`/ventes/${id}`)
    return response.data
  },
  async createVente(payload) {
    const response = await apiClient.post('/ventes', payload)
    return response.data
  },
  async cancelVente(id) {
    const response = await apiClient.post(`/ventes/${id}/cancel`)
    return response.data
  },

  async listCommandes() {
    const response = await apiClient.get('/commandes')
    return Array.isArray(response.data) ? response.data : []
  },
  async getCommande(id) {
    const response = await apiClient.get(`/commandes/${id}`)
    return response.data
  },
  async createCommande(payload) {
    const response = await apiClient.post('/commandes', payload)
    return response.data
  },
  async confirmCommande(id, payload = {}) {
    const response = await apiClient.post(`/commandes/${id}/confirm`, payload)
    return response.data
  },
  async cancelCommande(id) {
    const response = await apiClient.post(`/commandes/${id}/cancel`)
    return response.data
  },

  async getResteALivrer(commandeId) {
    const response = await apiClient.get(`/commandes/${commandeId}/reste-a-livrer`)
    return Array.isArray(response.data) ? response.data : []
  },
  async listBonsLivraison(commandeId) {
    const response = await apiClient.get(`/commandes/${commandeId}/bons-livraison`)
    return Array.isArray(response.data) ? response.data : []
  },
  async createBonLivraison(commandeId, payload) {
    const response = await apiClient.post(`/commandes/${commandeId}/bons-livraison`, payload)
    return response.data
  },
  async delivrerBonLivraison(id) {
    const response = await apiClient.post(`/bons-livraison/${id}/delivrer`)
    return response.data
  },

  async listFactures() {
    const response = await apiClient.get('/factures')
    return Array.isArray(response.data) ? response.data : []
  },
  async getFacture(id) {
    const response = await apiClient.get(`/factures/${id}`)
    return response.data
  },

  async listPaiements() {
    const response = await apiClient.get('/paiements')
    return Array.isArray(response.data) ? response.data : []
  },
  async createPaiement(payload) {
    const response = await apiClient.post('/paiements', payload)
    return response.data
  },
  async cancelPaiement(id) {
    const response = await apiClient.post(`/paiements/${id}/cancel`)
    return response.data
  },

  async getVariantStock(variantId) {
    const response = await apiClient.get(`/variants/${variantId}/stock`)
    return response.data
  }
}
