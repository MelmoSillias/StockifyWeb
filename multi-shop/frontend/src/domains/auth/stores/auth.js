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

const parseStoredList = (value) => {
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

const normalizeQuotas = (quotas) => {
  if (!quotas || typeof quotas !== 'object') {
    return null
  }

  return {
    max_shops: Number(quotas.max_shops),
    max_users: Number(quotas.max_users)
  }
}

const normalizeUsage = (usage) => {
  if (!usage || typeof usage !== 'object') {
    return null
  }

  return {
    shops: Number(usage.shops),
    users: Number(usage.users)
  }
}

const createInitialState = () => ({
  accessToken: localStorage.getItem(storage.authTokenKey) || null,
  user: parseStoredUser(localStorage.getItem(storage.authUserKey)),
  permissions: parseStoredList(localStorage.getItem(storage.authPermissionsKey)),
  features: parseStoredList(localStorage.getItem(storage.authFeaturesKey)),
  quotas: null,
  usage: null
})

const normalizeMePayload = (payload) => {
  if (!payload || typeof payload !== 'object') {
    return { user: null, permissions: [], features: [], quotas: null, usage: null, accessibleShops: [] }
  }

  if (payload.user && typeof payload.user === 'object') {
    return {
      user: payload.user,
      permissions: Array.isArray(payload.permissions) ? payload.permissions : [],
      features: Array.isArray(payload.features) ? payload.features : [],
      quotas: normalizeQuotas(payload.quotas),
      usage: normalizeUsage(payload.usage),
      accessibleShops: Array.isArray(payload.accessible_shops) ? payload.accessible_shops : []
    }
  }

  return {
    user: payload,
    permissions: Array.isArray(payload.permissions) ? payload.permissions : [],
    features: Array.isArray(payload.features) ? payload.features : [],
    quotas: normalizeQuotas(payload.quotas),
    usage: normalizeUsage(payload.usage),
    accessibleShops: Array.isArray(payload.accessible_shops) ? payload.accessible_shops : []
  }
}

export const useAuthStore = defineStore('auth', {
  state: () => createInitialState(),

  getters: {
    isAuthenticated: (state) => !!state.accessToken,

    isEmailVerified: (state) => {
      if (!state.user) {
        return false
      }

      if (!state.user.identity_id) {
        return true
      }

      if (typeof state.user.email_verified === 'boolean') {
        return state.user.email_verified
      }

      return !!state.user.email_verified_at
    },

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
    },

    /**
     * Prefer the active shop's features (same source as TenantFeatureGuard).
     * null features = shop without tenant → ungated.
     * Fallback: user tenant features; no tenant_account_id → ungated.
     */
    hasFeature: (state) => (feature) => {
      if (!feature) {
        return true
      }

      const shopStore = useShopStore()
      const activeShop = shopStore.activeShop

      if (activeShop && Object.prototype.hasOwnProperty.call(activeShop, 'features')) {
        if (activeShop.features === null) {
          return true
        }

        return Array.isArray(activeShop.features) && activeShop.features.includes(feature)
      }

      if (!state.user?.tenant_account_id) {
        return true
      }

      return state.features.includes(feature)
    },

    /** Quotas null = ungated tenant (no tenant_account_id). */
    canCreateShop: (state) => {
      if (!state.quotas || !state.usage) {
        return true
      }

      if (!Number.isFinite(state.quotas.max_shops) || !Number.isFinite(state.usage.shops)) {
        return true
      }

      return state.usage.shops < state.quotas.max_shops
    },

    canCreateUser: (state) => {
      if (!state.quotas || !state.usage) {
        return true
      }

      if (!Number.isFinite(state.quotas.max_users) || !Number.isFinite(state.usage.users)) {
        return true
      }

      return state.usage.users < state.quotas.max_users
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

    setFeatures(features) {
      this.features = Array.isArray(features) ? features : []
      localStorage.setItem(storage.authFeaturesKey, JSON.stringify(this.features))
    },

    setQuotas(quotas) {
      this.quotas = normalizeQuotas(quotas)
    },

    setUsage(usage) {
      this.usage = normalizeUsage(usage)
    },

    clearSession() {
      this.setAccessToken(null)
      this.setUser(null)
      this.setPermissions([])
      this.setFeatures([])
      this.setQuotas(null)
      this.setUsage(null)
      localStorage.removeItem(storage.authPermissionsKey)
      localStorage.removeItem(storage.authFeaturesKey)
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
      const { user, permissions, features, quotas, usage, accessibleShops } = normalizeMePayload(payload)
      this.setUser(user)
      this.setPermissions(permissions)
      this.setFeatures(features)
      this.setQuotas(quotas)
      this.setUsage(usage)
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
        this.setFeatures([])
        this.setQuotas(null)
        this.setUsage(null)
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
