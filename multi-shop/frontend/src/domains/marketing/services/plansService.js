import { apiClient } from '@/domains/shared/services/http'

export const plansService = {
  async list(applicationSlug = 'stockify') {
    const { data } = await apiClient.get('/public/plans', {
      params: { application: applicationSlug }
    })

    return data?.items ?? []
  }
}
