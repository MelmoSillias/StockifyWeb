import { computed, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useRoute, useRouter } from 'vue-router'

import { appNavigation, appShellBrand, appShellDefaults } from '@/domains/layout/config/appLayout'
import { useLayoutStore } from '@/domains/layout/stores/layout'

const normalizeMenuItems = (items, router, route) => {
  return items.map((item) => {
    const children = item.items ? normalizeMenuItems(item.items, router, route) : undefined
    const isActive = item.routeName
      ? route.name === item.routeName
      : Boolean(children?.some((child) => child._active))

    return {
      ...item,
      items: children,
      class: isActive ? 'layout-menu-item-active' : '',
      command: item.routeName
        ? () => {
            router.push({ name: item.routeName })
          }
        : undefined,
      _active: isActive
    }
  })
}

const findAncestorKeys = (items, routeName, trail = []) => {
  for (const item of items) {
    const nextTrail = [...trail, item.key]

    if (item.routeName === routeName) {
      return trail
    }

    if (item.items?.length) {
      const match = findAncestorKeys(item.items, routeName, nextTrail)

      if (match.length) {
        return match
      }
    }
  }

  return []
}

const toExpandedKeyMap = (keys) => Object.fromEntries(keys.map((key) => [key, true]))

export function useLayout() {
  const route = useRoute()
  const router = useRouter()
  const layoutStore = useLayoutStore()

  const {
    sidebarMode,
    sidebarCollapsed,
    mobileSidebarOpen,
    preferencesOpen,
    expandedMenuKeys,
    themeName,
    accentName,
    surfaceName,
    fontName,
    density,
    radius,
    darkMode,
    motionPreset
  } = storeToRefs(layoutStore)

  const menuModel = computed(() => normalizeMenuItems(appNavigation, router, route))
  const pageTitle = computed(() => route.meta.title || 'Application')
  const pageSection = computed(() => route.meta.section || appShellBrand.shortName)
  const homeBreadcrumb = computed(() => ({ icon: 'pi pi-home', route: { name: 'home' } }))
  const breadcrumbs = computed(() => {
    return route.matched
      .filter((record) => record.meta?.title)
      .map((record) => ({
        label: record.meta.title,
        route: record.name ? { name: record.name } : undefined
      }))
  })

  const shellConfig = computed(() => ({
    ...appShellDefaults.navigation,
    ...appShellDefaults.features
  }))

  const handlePrimaryNavigation = () => {
    if (layoutStore.isOverlayMode || window.innerWidth < 1024) {
      layoutStore.setMobileSidebarOpen(!mobileSidebarOpen.value)
      return
    }

    layoutStore.toggleSidebarCollapsed()
  }

  watch(
    () => route.name,
    (routeName) => {
      if (!routeName) {
        return
      }

      const keys = findAncestorKeys(appNavigation, routeName)

      if (keys.length) {
        layoutStore.mergeExpandedMenuKeys(toExpandedKeyMap(keys))
      }

      layoutStore.setMobileSidebarOpen(false)
    },
    { immediate: true }
  )

  return {
    brand: appShellBrand,
    shellConfig,
    menuModel,
    pageTitle,
    pageSection,
    homeBreadcrumb,
    breadcrumbs,
    sidebarMode,
    sidebarCollapsed,
    mobileSidebarOpen,
    preferencesOpen,
    expandedMenuKeys,
    themeName,
    accentName,
    surfaceName,
    fontName,
    density,
    radius,
    darkMode,
    motionPreset,
    handlePrimaryNavigation,
    setMobileSidebarOpen: layoutStore.setMobileSidebarOpen,
    setPreferencesOpen: layoutStore.setPreferencesOpen,
    setExpandedMenuKeys: layoutStore.setExpandedMenuKeys,
    setSidebarMode: layoutStore.setSidebarMode,
    setDarkMode: layoutStore.setDarkMode
  }
}
