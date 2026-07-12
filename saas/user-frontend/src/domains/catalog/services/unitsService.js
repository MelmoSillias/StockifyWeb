import { apiClient } from '@/domains/shared/services/http'

export const unitsService = {
  async list() {
    const response = await apiClient.get('/units-of-measure')
    return Array.isArray(response.data) ? response.data : []
  }
}
