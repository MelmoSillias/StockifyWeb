import { computed } from 'vue'

import { useAuthStore } from '@/domains/auth/stores/auth'

export function usePermissions() {
  const authStore = useAuthStore()

  const permissions = computed(() => authStore.permissions)

  const hasPermission = (permission) => authStore.hasPermission(permission)

  const hasAnyPermission = (...codes) => authStore.hasAnyPermission(...codes)

  const canAccessMenuItem = (item) => {
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
    hasPermission,
    hasAnyPermission,
    canAccessMenuItem,
    filterNavigationItems
  }
}
