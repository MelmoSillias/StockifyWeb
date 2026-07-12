import { apiClient } from '@/domains/shared/services/http'

export const platformService = {
  async fetchHealth() {
    const response = await apiClient.get('/health')
    return response.data
  },
  async fetchStats() {
    const response = await apiClient.get('/admin/stats')
    return response.data
  },
  async listAccounts() {
    const response = await apiClient.get('/admin/accounts')
    return Array.isArray(response.data) ? response.data : []
  },
  async getAccount(id) {
    const response = await apiClient.get(`/admin/accounts/${id}`)
    return response.data
  },
  async listShops() {
    const response = await apiClient.get('/admin/shops')
    return Array.isArray(response.data) ? response.data : []
  }
}
