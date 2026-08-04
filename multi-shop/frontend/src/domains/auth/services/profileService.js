import apiClient from '@/lib/axios'

export const profileService = {
  async getLoginHistory(params = {}) {
    const { data } = await apiClient.get('/me/login-history', { params })
    return data
  },

  async changePassword(payload) {
    const { data } = await apiClient.post('/me/password', payload)
    return data
  }
}
