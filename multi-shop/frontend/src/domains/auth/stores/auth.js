import { defineStore } from 'pinia'
import { appConfig } from '@/config/app'
import { authService } from '@/domains/auth/services/authService'
import { useShopStore } from '@/domains/shop/stores/shop'

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

const parseStoredPermissions = (value) => {
  if (!value) {
    return []
  }

  try {
    const parsed = JSON.parse(value)
    return Array.isArray(parsed) ? parsed : []
  } catch {
    return []
  }
}

const createInitialState = () => ({
  accessToken: localStorage.getItem(storage.authTokenKey) || null,
  user: parseStoredUser(localStorage.getItem(storage.authUserKey)),
  permissions: parseStoredPermissions(localStorage.getItem(storage.authPermissionsKey))
})

const normalizeMePayload = (payload) => {
  if (!payload || typeof payload !== 'object') {
    return { user: null, permissions: [], accessibleShops: [] }
  }

  if (payload.user && typeof payload.user === 'object') {
    return {
      user: payload.user,
      permissions: Array.isArray(payload.permissions) ? payload.permissions : [],
      accessibleShops: Array.isArray(payload.accessible_shops) ? payload.accessible_shops : []
    }
  }

  return {
    user: payload,
    permissions: Array.isArray(payload.permissions) ? payload.permissions : [],
    accessibleShops: Array.isArray(payload.accessible_shops) ? payload.accessible_shops : []
  }
}

export const useAuthStore = defineStore('auth', {
  state: () => createInitialState(),

  getters: {
    isAuthenticated: (state) => !!state.accessToken,

    hasPermission: (state) => (permission) => {
      if (!permission) {
        return true
      }

      return state.permissions.includes(permission)
    },

    hasAnyPermission: (state) => (...permissions) => {
      if (!permissions.length) {
        return true
      }

      return permissions.some((permission) => state.permissions.includes(permission))
    }
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

    setPermissions(permissions) {
      this.permissions = Array.isArray(permissions) ? permissions : []
      localStorage.setItem(storage.authPermissionsKey, JSON.stringify(this.permissions))
    },

    clearSession() {
      this.setAccessToken(null)
      this.setUser(null)
      this.setPermissions([])
      localStorage.removeItem(storage.authPermissionsKey)
      useShopStore().clear()
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
      const payload = await authService.me()
      const { user, permissions, accessibleShops } = normalizeMePayload(payload)
      this.setUser(user)
      this.setPermissions(permissions)
      const shopStore = useShopStore()
      shopStore.setAccessibleShops(accessibleShops)
      shopStore.resolveActiveShopId(user)
      return user
    },

    async restoreSession() {
      if (!auth.enabled) {
        this.clearSession()
        return null
      }

      if (!this.accessToken) {
        this.setUser(null)
        this.setPermissions([])
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
