import { apiClient } from '@/domains/shared/services/http'
import { emptyToNull, createCrudService } from '@/domains/shared/services/createCrudService'

const serializeClient = (payload) => ({
  name: emptyToNull(payload.name),
  phone: emptyToNull(payload.phone),
  email: emptyToNull(payload.email),
  credit_limit: payload.credit_limit === '' || payload.credit_limit == null ? null : String(payload.credit_limit),
  status: payload.status || 'active'
})

const crudService = createCrudService({
  listPath: '/clients',
  serialize: serializeClient
})

export const clientsService = {
  ...crudService,

  async remove(id) {
    const response = await apiClient.delete(`/clients/${id}`)
    return response.data
  },

  async listVentes(clientId) {
    const response = await apiClient.get(`/clients/${clientId}/ventes`)
    return Array.isArray(response.data) ? response.data : []
  },

  async listCommandes(clientId) {
    const response = await apiClient.get(`/clients/${clientId}/commandes`)
    return Array.isArray(response.data) ? response.data : []
  },

  async listFactures(clientId) {
    const response = await apiClient.get(`/clients/${clientId}/factures`)
    return Array.isArray(response.data) ? response.data : []
  },

  async listPaiements(clientId) {
    const response = await apiClient.get(`/clients/${clientId}/paiements`)
    return Array.isArray(response.data) ? response.data : []
  }
}
