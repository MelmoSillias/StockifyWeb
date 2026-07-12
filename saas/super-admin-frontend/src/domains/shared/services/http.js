import { apiClient } from '@/lib/axios'

const flattenFieldErrors = (errors) => {
  if (!errors || typeof errors !== 'object') {
    return []
  }

  return Object.values(errors)
    .flatMap((messages) => (Array.isArray(messages) ? messages : [messages]))
    .filter(Boolean)
}

export const extractApiError = (error, fallbackMessage = 'Une erreur inattendue est survenue.') => {
  const payload = error?.response?.data
  const fieldErrors = payload?.errors && typeof payload.errors === 'object' ? payload.errors : {}
  const flattenedFieldErrors = flattenFieldErrors(fieldErrors)

  return {
    message: flattenedFieldErrors[0] || payload?.error || payload?.message || error?.message || fallbackMessage,
    fieldErrors,
    status: error?.response?.status ?? null,
    payload
  }
}

export { apiClient }