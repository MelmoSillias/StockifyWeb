import apiClient from '@/lib/axios'
import { defaultApplicationSlug } from '@/domains/marketing/config/marketingContent'

export const quoteRequestsService = {
  async submit(payload) {
    const response = await apiClient.post('/public/quote-requests', {
      applicationSlug: defaultApplicationSlug,
      ...payload
    })
    return response.data
  }
}
