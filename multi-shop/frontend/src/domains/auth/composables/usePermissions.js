import { computed } from 'vue'
import { storeToRefs } from 'pinia'

import { useAuthStore } from '@/domains/auth/stores/auth'
import { useShopStore } from '@/domains/shop/stores/shop'

export function usePermissions() {
  const authStore = useAuthStore()
  const shopStore = useShopStore()
  const { activeShopId } = storeToRefs(shopStore)

  const permissions = computed(() => authStore.permissions)
  const features = computed(() => authStore.features)
  const quotas = computed(() => authStore.quotas)
  const usage = computed(() => authStore.usage)
  const canCreateShop = computed(() => authStore.canCreateShop)
  const canCreateUser = computed(() => authStore.canCreateUser)

  const hasPermission = (permission) => authStore.hasPermission(permission)

  const hasAnyPermission = (...codes) => authStore.hasAnyPermission(...codes)

  const hasFeature = (feature) => authStore.hasFeature(feature)

  const canAccessMenuItem = (item) => {
    // Touch activeShopId so nav recomputes when the shop (and its features) changes.
    void activeShopId.value

    if (item?.requiredFeature && !authStore.hasFeature(item.requiredFeature)) {
      return false
    }

    if (!item?.requiredPermission) {
      return true
    }

    if (Array.isArray(item.requiredPermission)) {
      return authStore.hasAnyPermission(...item.requiredPermission)
    }

    return authStore.hasPermission(item.requiredPermission)
  }

  const filterNavigationItems = (items = []) => {
    return items
      .map((item) => {
        if (item.items?.length) {
          const children = filterNavigationItems(item.items)
          if (!children.length) {
            return null
          }

          return { ...item, items: children }
        }

        return canAccessMenuItem(item) ? item : null
      })
      .filter(Boolean)
  }

  return {
    permissions,
    features,
    quotas,
    usage,
    canCreateShop,
    canCreateUser,
    hasPermission,
    hasAnyPermission,
    hasFeature,
    canAccessMenuItem,
    filterNavigationItems
  }
}
