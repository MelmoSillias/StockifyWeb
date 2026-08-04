import { apiClient } from './http'

const identity = (value) => value

export const emptyToNull = (value) => {
  if (value === undefined || value === null) {
    return null
  }

  if (typeof value === 'string') {
    const trimmedValue = value.trim()
    return trimmedValue === '' ? null : trimmedValue
  }

  return value
}

export const toStringList = (value) => {
  if (Array.isArray(value)) {
    return value
      .map((entry) => String(entry).trim())
      .filter(Boolean)
  }

  if (typeof value === 'string') {
    return value
      .split(',')
      .map((entry) => entry.trim())
      .filter(Boolean)
  }

  return []
}

export const toNumberList = (value) => {
  if (!Array.isArray(value)) {
    return []
  }

  return value
    .map((entry) => Number(entry))
    .filter((entry) => Number.isFinite(entry) && entry > 0)
}

export const toIsoDateTime = (value) => {
  if (!value) {
    return null
  }

  if (value instanceof Date) {
    return value.toISOString()
  }

  const normalizedDate = new Date(value)
  return Number.isNaN(normalizedDate.getTime()) ? null : normalizedDate.toISOString()
}

export const toIsoDate = (value) => {
  if (!value) {
    return null
  }

  const date = value instanceof Date ? value : new Date(value)
  if (Number.isNaN(date.getTime())) {
    return null
  }

  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

export const toIsoDateList = (value) => {
  if (!Array.isArray(value)) {
    return []
  }

  return value
    .map((entry) => toIsoDateTime(entry))
    .filter(Boolean)
}

export const createCrudService = ({
  listPath,
  detailPath = (id) => `${listPath}/${id}`,
  createPath = listPath,
  updatePath = (id) => `${listPath}/${id}`,
  deletePath = (id) => `${listPath}/${id}`,
  serialize = identity
}) => ({
  async list() {
    const response = await apiClient.get(listPath)
    return Array.isArray(response.data) ? response.data : []
  },
  async get(id) {
    const response = await apiClient.get(detailPath(id))
    return response.data
  },
  async create(payload) {
    const response = await apiClient.post(createPath, serialize(payload))
    return response.data
  },
  async update(id, payload) {
    const response = await apiClient.put(updatePath(id), serialize(payload))
    return response.data
  },
  async remove(id) {
    await apiClient.delete(deletePath(id))
    return id
  }
})