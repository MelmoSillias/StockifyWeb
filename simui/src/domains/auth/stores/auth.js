import { defineStore } from 'pinia'
import { appConfig } from '@/config/app'
import { authService } from '@/domains/auth/services/authService'

const { storage, auth } = appConfig

const parseStoredUser = (value) => {
  if (!value) {
    return null
  }

  try {
    return JSON.parse(value)
  } catch {
    return null
  }
}

const createInitialState = () => ({
  accessToken: localStorage.getItem(storage.authTokenKey) || null,
  user: parseStoredUser(localStorage.getItem(storage.authUserKey))
})

export const useAuthStore = defineStore('auth', {
  state: () => createInitialState(),

  getters: {
    isAuthenticated: (state) => !!state.accessToken
  },

  actions: {
    setAccessToken(accessToken) {
      this.accessToken = accessToken

      if (accessToken) {
        localStorage.setItem(storage.authTokenKey, accessToken)
        return
      }

      localStorage.removeItem(storage.authTokenKey)
    },

    setUser(user) {
      this.user = user

      if (user) {
        localStorage.setItem(storage.authUserKey, JSON.stringify(user))
        return
      }

      localStorage.removeItem(storage.authUserKey)
    },

    clearSession() {
      this.setAccessToken(null)
      this.setUser(null)
    },

    async login(credentials) {
      if (!auth.enabled) {
        return null
      }

      const data = await authService.login(credentials)
      const accessToken = auth.tokenResolver(data)

      if (!accessToken) {
        throw new Error('Jeton de connexion manquant dans la reponse du serveur.')
      }

      this.setAccessToken(accessToken)
      await this.fetchCurrentUser()
      return this.user
    },

    async fetchCurrentUser() {
      const user = await authService.me()
      this.setUser(user)
      return user
    },

    async restoreSession() {
      if (!auth.enabled) {
        this.clearSession()
        return null
      }

      if (!this.accessToken) {
        this.setUser(null)
        return null
      }

      try {
        return await this.fetchCurrentUser()
      } catch {
        this.clearSession()
        return null
      }
    },

    logout() {
      this.clearSession()
    }
  }
})
