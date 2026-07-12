import axios from 'axios'
import { appConfig } from '@/config/app'
import { useAuthStore } from '@/domains/auth/stores/auth'
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

const isLoginRequest = (url = '') => {
  const loginEndpoint = appConfig.auth.loginEndpoint
  return Boolean(loginEndpoint && url.includes(loginEndpoint))
}

apiClient.interceptors.request.use(
  (config) => {
    applyApiClientConfig()

    const authStore = useAuthStore()
    const token = authStore.accessToken
    const { auth } = appConfig

    config.headers = config.headers || {}

    if (token) {
      config.headers.Authorization = `${auth.tokenType || 'Bearer'} ${token}`
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
      && auth.redirectOn401
      && authStore.isAuthenticated
      && !isLoginRequest(originalRequest?.url)
    ) {
      if (router && router.currentRoute.value.name !== routes.loginRouteName) {
        authStore.logout()
        router.push({ name: routes.loginRouteName, query: { redirect: router.currentRoute.value.fullPath } })
      }

      return Promise.reject(error)
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
