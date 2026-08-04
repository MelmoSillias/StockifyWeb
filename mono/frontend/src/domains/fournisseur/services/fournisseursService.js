import { apiClient } from '@/domains/shared/services/http'
import { emptyToNull, createCrudService } from '@/domains/shared/services/createCrudService'

const serializeFournisseur = (payload) => ({
  name: emptyToNull(payload.name),
  phone: emptyToNull(payload.phone),
  email: emptyToNull(payload.email),
  status: payload.status || 'active'
})

const crudService = createCrudService({
  listPath: '/fournisseurs',
  serialize: serializeFournisseur
})

export const fournisseursService = {
  ...crudService,

  async remove(id) {
    const response = await apiClient.delete(`/fournisseurs/${id}`)
    return response.data
  },

  async listCommandes(fournisseurId) {
    const response = await apiClient.get(`/fournisseurs/${fournisseurId}/commandes`)
    return Array.isArray(response.data) ? response.data : []
  },

  async listDettes(fournisseurId, params = {}) {
    const response = await apiClient.get(`/fournisseurs/${fournisseurId}/dettes`, { params })
    return Array.isArray(response.data) ? response.data : []
  },

  async listPaiements(fournisseurId) {
    const response = await apiClient.get(`/fournisseurs/${fournisseurId}/paiements`)
    return Array.isArray(response.data) ? response.data : []
  }
}
