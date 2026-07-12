import { getShopBasePath } from '@/domains/shared/services/createShopScopedService'
import { apiClient } from '@/domains/shared/services/http'

export const inventoryService = {
  async listLots(variantId) {
    const response = await apiClient.get(`${getShopBasePath()}/variants/${variantId}/lots`)
    return Array.isArray(response.data) ? response.data : []
  },
  async receiveLot(variantId, payload) {
    const response = await apiClient.post(`${getShopBasePath()}/variants/${variantId}/lots`, payload)
    return response.data
  },
  async getStock(variantId) {
    const response = await apiClient.get(`${getShopBasePath()}/variants/${variantId}/stock`)
    return response.data
  },
  async stockOut(variantId, payload) {
    const response = await apiClient.post(`${getShopBasePath()}/variants/${variantId}/stock-out`, payload)
    return response.data
  },
  async adjust(variantId, payload) {
    const response = await apiClient.post(`${getShopBasePath()}/variants/${variantId}/adjustments`, payload)
    return response.data
  },
  async listMovements(variantId = null) {
    const params = variantId ? { variant_id: variantId } : {}
    const response = await apiClient.get(`${getShopBasePath()}/stock-movements`, { params })
    return Array.isArray(response.data) ? response.data : []
  },
  async listAlerts() {
    const response = await apiClient.get(`${getShopBasePath()}/stock-alerts`)
    return Array.isArray(response.data) ? response.data : []
  }
}
