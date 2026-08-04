import apiClient from '@/lib/axios'

export const shopService = {
  async fetchAccessibleShops() {
    const response = await apiClient.get('/me/shops')
    return response.data?.data || []
  },

  async fetchShops() {
    const response = await apiClient.get('/shops')
    return response.data?.data || []
  },

  async createShop(payload) {
    const response = await apiClient.post('/shops', payload)
    return response.data?.data
  },

  async updateShop(id, payload) {
    const response = await apiClient.put(`/shops/${id}`, payload)
    return response.data?.data
  },

  async deleteShop(id) {
    await apiClient.delete(`/shops/${id}`)
  },

  async fetchShopUsers(shopId) {
    const response = await apiClient.get(`/shops/${shopId}/users`)
    return response.data?.data || []
  },

  async createShopUser(shopId, payload) {
    const response = await apiClient.post(`/shops/${shopId}/users`, payload)
    return response.data
  }
}
