import { defineStore } from 'pinia'
import { shopService } from '@/domains/shop/services/shopService'

const STORAGE_KEY = 'stockify-active-shop'

const readStoredShopId = () => localStorage.getItem(STORAGE_KEY) || null

export const useShopStore = defineStore('shop', {
  state: () => ({
    activeShopId: readStoredShopId(),
    accessibleShops: []
  }),

  getters: {
    activeShop: (state) => state.accessibleShops.find((shop) => shop.id === state.activeShopId) || null,

    hasMultipleShops: (state) => state.accessibleShops.length > 1,

    showSelector: (state) => state.accessibleShops.length > 0
  },

  actions: {
    setAccessibleShops(shops) {
      this.accessibleShops = Array.isArray(shops) ? shops : []
      this.ensureActiveShop()
    },

    ensureActiveShop() {
      if (!this.accessibleShops.length) {
        this.setActiveShopId(null)
        return
      }

      const stillValid = this.accessibleShops.some((shop) => shop.id === this.activeShopId)
      if (!stillValid) {
        this.setActiveShopId(this.accessibleShops[0].id)
      }
    },

    resolveActiveShopId(user = null) {
      if (this.activeShopId && this.accessibleShops.some((shop) => shop.id === this.activeShopId)) {
        return this.activeShopId
      }

      if (user?.shop_id) {
        this.setActiveShopId(user.shop_id)
        return user.shop_id
      }

      if (this.accessibleShops.length > 0) {
        this.setActiveShopId(this.accessibleShops[0].id)
        return this.accessibleShops[0].id
      }

      return null
    },

    setActiveShopId(shopId) {
      this.activeShopId = shopId

      if (shopId) {
        localStorage.setItem(STORAGE_KEY, shopId)
        return
      }

      localStorage.removeItem(STORAGE_KEY)
    },

    async switchShop(shopId) {
      if (!this.accessibleShops.some((shop) => shop.id === shopId)) {
        throw new Error('Boutique inaccessible.')
      }

      this.setActiveShopId(shopId)
    },

    async refreshAccessibleShops() {
      const shops = await shopService.fetchAccessibleShops()
      this.setAccessibleShops(shops)
      return shops
    },

    clear() {
      this.setActiveShopId(null)
      this.accessibleShops = []
    }
  }
})
