import { emptyToNull } from '@/domains/shared/services/createCrudService'
import { getShopBasePath } from '@/domains/shared/services/createShopScopedService'
import { apiClient } from '@/domains/shared/services/http'

const serializeVariant = (payload) => ({
  sku: emptyToNull(payload.sku),
  unit_of_measure_id: emptyToNull(payload.unit_of_measure_id),
  sale_mode: emptyToNull(payload.sale_mode),
  default_price: payload.default_price !== '' && payload.default_price != null ? String(payload.default_price) : null,
  alert_threshold: payload.alert_threshold !== '' && payload.alert_threshold != null ? String(payload.alert_threshold) : null
})

export const variantsService = {
  async list(productId) {
    const response = await apiClient.get(`${getShopBasePath()}/products/${productId}/variants`)
    return Array.isArray(response.data) ? response.data : []
  },
  async create(productId, payload) {
    const response = await apiClient.post(
      `${getShopBasePath()}/products/${productId}/variants`,
      serializeVariant(payload)
    )
    return response.data
  },
  async update(id, payload) {
    const response = await apiClient.put(`${getShopBasePath()}/variants/${id}`, serializeVariant(payload))
    return response.data
  },
  async remove(id) {
    await apiClient.delete(`${getShopBasePath()}/variants/${id}`)
    return id
  }
}
