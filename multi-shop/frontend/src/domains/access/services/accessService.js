import api from '@/lib/axios'

export const accessService = {
  async listUsers() {
    const { data } = await api.get('/users')
    return data.data ?? data
  },

  async getUser(id) {
    const { data } = await api.get(`/users/${id}`)
    return data.data ?? data
  },

  async createUser(payload) {
    const { data } = await api.post('/users', payload)
    return data.data ?? data
  },

  async updateUser(id, payload) {
    const { data } = await api.put(`/users/${id}`, payload)
    return data.data ?? data
  },

  async suspendUser(id) {
    const { data } = await api.post(`/users/${id}/suspend`)
    return data.data ?? data
  },

  async resetPassword(id, password) {
    const { data } = await api.post(`/users/${id}/reset-password`, { password })
    return data
  },

  async listRoles() {
    const { data } = await api.get('/roles')
    return data.data ?? data
  },

  async listPermissions() {
    const { data } = await api.get('/permissions')
    return data.data ?? data
  },

  async createRole(payload) {
    const { data } = await api.post('/roles', payload)
    return data.data ?? data
  },

  async updateRole(id, payload) {
    const { data } = await api.put(`/roles/${id}`, payload)
    return data.data ?? data
  },

  async deleteRole(id) {
    await api.delete(`/roles/${id}`)
  },

  async listAuditLogs(params = {}) {
    const { data } = await api.get('/audit-logs', { params })
    return data
  }
}
