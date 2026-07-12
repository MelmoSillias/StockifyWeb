import apiClient from '@/lib/axios'
import { appConfig } from '@/config/app'

export const authService = {
  async login(credentials) {
    const response = await apiClient.post(appConfig.auth.loginEndpoint, credentials)
    return response.data
  },

  async me() {
    const response = await apiClient.get(appConfig.auth.meEndpoint)
    return response.data
  }
}
