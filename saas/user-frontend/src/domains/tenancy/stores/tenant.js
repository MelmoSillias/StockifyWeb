import { defineStore } from 'pinia'
import { appConfig } from '@/config/app'

const { storage } = appConfig

const parseSelection = () => {
  try {
    const raw = localStorage.getItem(storage.tenantSelectionKey)
    if (!raw) {
      return { accountId: null, shopId: null }
    }

    const parsed = JSON.parse(raw)
    return {
      accountId: typeof parsed.accountId === 'string' ? parsed.accountId : null,
      shopId: typeof parsed.shopId === 'string' ? parsed.shopId : null
    }
  } catch {
    return { accountId: null, shopId: null }
  }
}

export const useTenantStore = defineStore('tenant', {
  state: () => ({
    accounts: [],
    ...parseSelection()
  }),
  getters: {
    selectedAccount: (state) => state.accounts.find((account) => account.id === state.accountId) || null,
    hasTenant: (state) => Boolean(state.accountId && state.shopId),
    selectedShop: (state) => {
      const account = state.accounts.find((entry) => entry.id === state.accountId)
      if (!account) {
        return null
      }

      return (account.shops || []).find((shop) => shop.id === state.shopId) || null
    }
  },
  actions: {
    persistSelection() {
      localStorage.setItem(
        storage.tenantSelectionKey,
        JSON.stringify({ accountId: this.accountId, shopId: this.shopId })
      )
    },
    clearSelection() {
      this.accounts = []
      this.accountId = null
      this.shopId = null
      localStorage.removeItem(storage.tenantSelectionKey)
    },
    setSelection(accountId, shopId) {
      this.accountId = accountId
      this.shopId = shopId
      this.persistSelection()
    },
    setAvailableAccounts(accounts) {
      this.accounts = Array.isArray(accounts) ? accounts : []

      if (!this.accounts.length) {
        this.accountId = null
        this.shopId = null
        this.persistSelection()
        return
      }

      const currentAccountValid = this.accounts.some((account) => account.id === this.accountId)
      if (!currentAccountValid) {
        this.accountId = this.accounts[0].id
      }

      const account = this.accounts.find((entry) => entry.id === this.accountId)
      const shops = account?.shops || []
      const currentShopValid = shops.some((shop) => shop.id === this.shopId)
      if (!currentShopValid) {
        this.shopId = shops[0]?.id || null
      }

      this.persistSelection()
    }
  }
})
