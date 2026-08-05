import { apiClient } from '@/domains/shared/services/http'
import { defaultApplicationSlug } from '@/domains/marketing/config/marketingContent'

export const signupService = {
  async signup(payload) {
    const { data } = await apiClient.post('/public/signup', {
      accountName: payload.accountName,
      accountSlug: payload.accountSlug,
      billingEmail: payload.billingEmail,
      adminEmail: payload.adminEmail ?? payload.billingEmail,
      adminPassword: payload.adminPassword,
      applicationSlug: defaultApplicationSlug,
      planCode: payload.requestedPlanCode ?? undefined
    }, { timeout: 90000 })

    return data
  }
}
