import { defineStore } from 'pinia'

import { appConfig } from '@/config/app'
import { appShellDefaults, themeDefaultAccents } from '@/domains/layout/config/appLayout'

const presetAccentMap = {
  emerald: '#10b981',
  cyan: '#06b6d4',
  amber: '#f59e0b',
  rose: '#f43f5e'
}

const layoutStorageKey = appConfig.storage.layoutPreferencesKey

const createInitialState = () => {
  const defaults = {
    sidebarMode: appShellDefaults.navigation.sidebarMode,
    sidebarCollapsed: appShellDefaults.navigation.sidebarCollapsed,
    mobileSidebarOpen: false,
    quickPanelOpen: false,
    preferencesOpen: false,
    expandedMenuKeys: {},
    themeName: appShellDefaults.appearance.themeName,
    accentName: appShellDefaults.appearance.accentName,
    accentColor: appShellDefaults.appearance.accentColor,
    surfaceName: appShellDefaults.appearance.surfaceName,
    fontName: appShellDefaults.appearance.fontName,
    density: appShellDefaults.appearance.density,
    textScale: appShellDefaults.appearance.textScale,
    radius: appShellDefaults.appearance.radius,
    darkMode: appShellDefaults.appearance.darkMode,
    motionPreset: appShellDefaults.appearance.motionPreset,
    gradientMode: appShellDefaults.appearance.gradientMode,
    sidebarLogoPosition: appShellDefaults.layout.sidebarLogoPosition,
    sidebarProfilePosition: appShellDefaults.layout.sidebarProfilePosition,
    sidebarSearchPosition: appShellDefaults.layout.sidebarSearchPosition,
    topbarLogoVisibility: appShellDefaults.layout.topbarLogoVisibility,
    topbarProfilePosition: appShellDefaults.layout.topbarProfilePosition,
    topbarSearchPosition: appShellDefaults.layout.topbarSearchPosition
  }

  try {
    const storedValue = localStorage.getItem(layoutStorageKey)

    if (!storedValue) {
      return defaults
    }

    return {
      ...defaults,
      ...JSON.parse(storedValue)
    }
  } catch {
    return defaults
  }
}

export const useLayoutStore = defineStore('layout', {
  state: () => createInitialState(),

  getters: {
    isOverlayMode: (state) => state.sidebarMode === 'overlay'
  },

  actions: {
    persist() {
      localStorage.setItem(
        layoutStorageKey,
        JSON.stringify({
          sidebarMode: this.sidebarMode,
          sidebarCollapsed: this.sidebarCollapsed,
          expandedMenuKeys: this.expandedMenuKeys,
          themeName: this.themeName,
          accentName: this.accentName,
          accentColor: this.accentColor,
          surfaceName: this.surfaceName,
          fontName: this.fontName,
          density: this.density,
          textScale: this.textScale,
          radius: this.radius,
          darkMode: this.darkMode,
          motionPreset: this.motionPreset,
          gradientMode: this.gradientMode,
          sidebarLogoPosition: this.sidebarLogoPosition,
          sidebarProfilePosition: this.sidebarProfilePosition,
          sidebarSearchPosition: this.sidebarSearchPosition,
          topbarLogoVisibility: this.topbarLogoVisibility,
          topbarProfilePosition: this.topbarProfilePosition,
          topbarSearchPosition: this.topbarSearchPosition
        })
      )
    },

    setSidebarMode(mode) {
      this.sidebarMode = mode

      if (mode === 'overlay') {
        this.sidebarCollapsed = false
      }

      this.persist()
    },

    toggleSidebarCollapsed() {
      this.sidebarCollapsed = !this.sidebarCollapsed
      this.persist()
    },

    setMobileSidebarOpen(value) {
      this.mobileSidebarOpen = value
    },

    setQuickPanelOpen(value) {
      this.quickPanelOpen = value
    },

    setPreferencesOpen(value) {
      this.preferencesOpen = value
    },

    setExpandedMenuKeys(value) {
      this.expandedMenuKeys = value
      this.persist()
    },

    mergeExpandedMenuKeys(value) {
      this.expandedMenuKeys = {
        ...this.expandedMenuKeys,
        ...value
      }
      this.persist()
    },

    setThemeName(value) {
      this.themeName = value
      this.accentName = themeDefaultAccents[value] || appShellDefaults.appearance.accentName
      this.accentColor = null
      this.persist()
    },

    setAccentName(value) {
      this.accentName = value

      if (value !== 'custom') {
        this.accentColor = null
      } else if (!this.accentColor) {
        this.accentColor = presetAccentMap.emerald
      }

      this.persist()
    },

    setAccentColor(value) {
      this.accentName = 'custom'
      this.accentColor = value
      this.persist()
    },

    setSurfaceName(value) {
      this.surfaceName = value
      this.persist()
    },

    setFontName(value) {
      this.fontName = value
      this.persist()
    },

    setDensity(value) {
      this.density = value
      this.persist()
    },

    setTextScale(value) {
      this.textScale = value
      this.persist()
    },

    setRadius(value) {
      this.radius = value
      this.persist()
    },

    setDarkMode(value) {
      this.darkMode = value
      this.persist()
    },

    setMotionPreset(value) {
      this.motionPreset = value
      this.persist()
    },

    setSidebarLogoPosition(value) {
      this.sidebarLogoPosition = value
      this.persist()
    },

    setSidebarProfilePosition(value) {
      this.sidebarProfilePosition = value
      this.persist()
    },

    setSidebarSearchPosition(value) {
      this.sidebarSearchPosition = value
      this.persist()
    },

    setGradientMode(value) {
      this.gradientMode = value
      this.persist()
    },

    setTopbarProfilePosition(value) {
      this.topbarProfilePosition = value
      this.persist()
    },

    setTopbarLogoVisibility(value) {
      this.topbarLogoVisibility = value
      this.persist()
    },

    setTopbarSearchPosition(value) {
      this.topbarSearchPosition = value
      this.persist()
    }
  }
})
