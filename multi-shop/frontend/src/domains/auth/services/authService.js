import apiClient from '@/lib/axios'
import { appConfig } from '@/config/app'

export const authService = {
  async login(credentials) {
    const response = await apiClient.post(appConfig.auth.loginEndpoint, credentials)
    return response.data
  },

  async refresh() {
    const response = await apiClient.post(appConfig.auth.refreshEndpoint)
    return response.data
  },

  async me() {
    const response = await apiClient.get(appConfig.auth.meEndpoint)
    return response.data
  },

  async resendVerificationEmail() {
    const response = await apiClient.post('/auth/verification/resend')
    return response.data
  },

  async syncVerificationStatus() {
    const response = await apiClient.post('/auth/verification/sync')
    return response.data
  }
}
