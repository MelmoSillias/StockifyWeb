import { useTenantStore } from '@/domains/tenancy/stores/tenant'
import { apiClient } from './http'

export const getShopBasePath = () => {
  const shopId = useTenantStore().shopId
  if (!shopId) {
    throw new Error('Aucune boutique selectionnee.')
  }

  return `/shops/${shopId}`
}

export const createShopScopedCrudService = ({
  resource,
  detailPath = (id) => `${getShopBasePath()}/${resource}/${id}`,
  createPath = () => `${getShopBasePath()}/${resource}`,
  updatePath = (id) => `${getShopBasePath()}/${resource}/${id}`,
  deletePath = (id) => `${getShopBasePath()}/${resource}/${id}`,
  serialize = (value) => value
}) => ({
  async list() {
    const response = await apiClient.get(`${getShopBasePath()}/${resource}`)
    return Array.isArray(response.data) ? response.data : []
  },
  async get(id) {
    const response = await apiClient.get(detailPath(id))
    return response.data
  },
  async create(payload) {
    const response = await apiClient.post(createPath(), serialize(payload))
    return response.data
  },
  async update(id, payload) {
    const response = await apiClient.put(updatePath(id), serialize(payload))
    return response.data
  },
  async remove(id) {
    await apiClient.delete(deletePath(id))
    return id
  }
})
