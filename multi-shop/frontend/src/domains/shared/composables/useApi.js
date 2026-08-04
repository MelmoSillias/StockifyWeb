// src/composables/useApi.js
import apiClient from '@/lib/axios'

export function useApi() {
  return {
    api: apiClient
  }
}
