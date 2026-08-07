import axios from 'axios'
import { appConfig } from '@/config/app'
import { useAuthStore } from '@/domains/auth/stores/auth'
import { useShopStore } from '@/domains/shop/stores/shop'
import { getAppRouter } from '@/lib/routerContext'

const apiClient = axios.create()

export const applyApiClientConfig = () => {
  const { axios: axiosConfig } = appConfig

  apiClient.defaults.baseURL = axiosConfig.baseURL
  apiClient.defaults.timeout = axiosConfig.timeout
  apiClient.defaults.withCredentials = axiosConfig.withCredentials
  apiClient.defaults.headers.common = {
    ...apiClient.defaults.headers.common,
    ...axiosConfig.defaultHeaders
  }
}

applyApiClientConfig()

const isAuthBypassRequest = (url = '') => {
  const { auth } = appConfig
  return [auth.loginEndpoint, auth.refreshEndpoint].some(
    (endpoint) => endpoint && url.includes(endpoint)
  )
}

let refreshPromise = null

apiClient.interceptors.request.use(
  (config) => {
    applyApiClientConfig()

    const authStore = useAuthStore()
    const shopStore = useShopStore()
    const token = authStore.accessToken
    const { auth } = appConfig

    config.headers = config.headers || {}

    if (token) {
      config.headers.Authorization = `${auth.tokenType || 'Bearer'} ${token}`
    }

    const shopId = shopStore.resolveActiveShopId(authStore.user)
    if (shopId && !config.headers['X-Shop-Id']) {
      config.headers['X-Shop-Id'] = shopId
    }

    return config
  },
  (error) => Promise.reject(error)
)

apiClient.interceptors.response.use(
  (response) => response,
  async (error) => {
    const originalRequest = error.config
    const authStore = useAuthStore()
    const router = getAppRouter()
    const { auth, routes } = appConfig

    if (
      error.response?.status === 401
      && auth.enabled
      && authStore.isAuthenticated
      && !originalRequest?._retry
      && !isAuthBypassRequest(originalRequest?.url)
    ) {
      originalRequest._retry = true

      try {
        if (!refreshPromise) {
          refreshPromise = authStore.refreshAccessToken().finally(() => {
            refreshPromise = null
          })
        }
        await refreshPromise
        originalRequest.headers = originalRequest.headers || {}
        originalRequest.headers.Authorization = `${auth.tokenType || 'Bearer'} ${authStore.accessToken}`
        return apiClient(originalRequest)
      } catch {
        authStore.clearSession()
        if (router && router.currentRoute.value.name !== routes.loginRouteName) {
          router.push({ name: routes.loginRouteName, query: { redirect: router.currentRoute.value.fullPath } })
        }
      }
    }

    if (
      error.response?.status === 401
      && auth.enabled
      && auth.redirectOn401
      && !isAuthBypassRequest(originalRequest?.url)
      && router
      && router.currentRoute.value.name !== routes.loginRouteName
    ) {
      authStore.clearSession()
      router.push({ name: routes.loginRouteName, query: { redirect: router.currentRoute.value.fullPath } })
    }

    if (error.response) {
      const message = error.response.data?.message || error.response.data?.error || 'Une erreur est survenue'
      console.error(`Erreur ${error.response.status}: ${message}`)
    }

    return Promise.reject(error)
  }
)

export { apiClient }
export default apiClient
