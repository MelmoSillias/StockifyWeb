import { apiClient } from '@/domains/shared/services/http'

const buildDocumentUrl = (type, id, params = {}) => {
  const search = new URLSearchParams()
  Object.entries(params).forEach(([key, value]) => {
    if (value != null && value !== '') {
      search.set(key, String(value))
    }
  })
  const query = search.toString()
  const base = apiClient.defaults.baseURL?.replace(/\/$/, '') || ''
  return `${base}/impressions/documents/${type}/${id}${query ? `?${query}` : ''}`
}

export const impressionService = {
  async getSettings() {
    const response = await apiClient.get('/impressions/settings')
    return response.data
  },

  async updateSettings(payload) {
    const response = await apiClient.put('/impressions/settings', payload)
    return response.data
  },

  buildDocumentUrl,

  async fetchDocument(type, id, { format = 'html', page, disposition = 'inline' } = {}) {
    const response = await apiClient.get(`/impressions/documents/${type}/${id}`, {
      params: { format, page, disposition },
      responseType: 'blob'
    })
    return response.data
  },

  async printTable(tableType, payload) {
    const response = await apiClient.post(`/impressions/tables/${tableType}/print`, payload, {
      responseType: 'blob'
    })
    return response.data
  },

  async exportTable(tableType, payload) {
    const response = await apiClient.post(`/impressions/tables/${tableType}/export`, payload, {
      responseType: 'blob'
    })
    return response.data
  }
}
