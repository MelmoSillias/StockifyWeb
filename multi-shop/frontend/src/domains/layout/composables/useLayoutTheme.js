import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { palette, updatePrimaryPalette } from '@primeuix/themes'

import { layoutOptionSets } from '@/domains/layout/config/appLayout'
import { useLayoutStore } from '@/domains/layout/stores/layout'

const accentColorMap = {
  emerald: '#10b981',
  cyan: '#06b6d4',
  amber: '#f59e0b',
  rose: '#f43f5e',
  orange: '#f79e1b'
}

const resolveAccentHex = (accentName, accentColor) => {
  if (accentName === 'custom') {
    return normalizeHex(accentColor) || accentColorMap.emerald
  }

  return accentColorMap[accentName] || accentColorMap.emerald
}

const normalizeHex = (value) => {
  if (!value) {
    return null
  }

  const normalized = value.startsWith('#') ? value : `#${value}`

  if (!/^#[0-9a-fA-F]{6}$/.test(normalized)) {
    return null
  }

  return normalized.toLowerCase()
}

const hexToRgb = (hex) => {
  const normalized = normalizeHex(hex)

  if (!normalized) {
    return null
  }

  const numeric = parseInt(normalized.slice(1), 16)

  return {
    r: (numeric >> 16) & 255,
    g: (numeric >> 8) & 255,
    b: numeric & 255
  }
}

const rgbToHex = ({ r, g, b }) => {
  return `#${[r, g, b]
    .map((channel) => Math.max(0, Math.min(255, Math.round(channel))).toString(16).padStart(2, '0'))
    .join('')}`
}

const mixHex = (hex, mixWith, ratio) => {
  const base = hexToRgb(hex)
  const target = hexToRgb(mixWith)

  if (!base || !target) {
    return hex
  }

  return rgbToHex({
    r: base.r + (target.r - base.r) * ratio,
    g: base.g + (target.g - base.g) * ratio,
    b: base.b + (target.b - base.b) * ratio
  })
}

const toRgba = (hex, alpha) => {
  const rgb = hexToRgb(hex)

  if (!rgb) {
    return null
  }

  return `rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, ${alpha})`
}

const resolveQuery = () => {
  if (typeof window === 'undefined' || !window.matchMedia) {
    return null
  }

  return window.matchMedia('(prefers-color-scheme: dark)')
}

export function useLayoutTheme() {
  const layoutStore = useLayoutStore()
  const {
    themeName,
    accentName,
    accentColor,
    surfaceName,
    fontName,
    density,
    textScale,
    radius,
    darkMode,
    motionPreset,
    gradientMode
  } = storeToRefs(layoutStore)

  const mediaQuery = resolveQuery()
  const prefersDark = ref(mediaQuery?.matches ?? false)

  const syncDarkPreference = (event) => {
    prefersDark.value = event?.matches ?? mediaQuery?.matches ?? false
  }

  const isDarkModeActive = computed(() => {
    if (darkMode.value === 'dark') {
      return true
    }

    if (darkMode.value === 'light') {
      return false
    }

    return prefersDark.value
  })

  watch(
    [themeName, accentName, accentColor, surfaceName, fontName, density, textScale, radius, motionPreset, gradientMode, isDarkModeActive],
    () => {
      if (typeof document === 'undefined') {
        return
      }

      const root = document.documentElement

      root.dataset.layoutTheme = themeName.value
      root.dataset.layoutAccent = accentName.value
      root.dataset.layoutSurface = surfaceName.value
      root.dataset.layoutFont = fontName.value
      root.dataset.layoutDensity = density.value
      root.dataset.layoutTextScale = textScale.value
      root.dataset.layoutRadius = radius.value
      root.dataset.layoutMotion = motionPreset.value
      root.dataset.layoutGradient = gradientMode.value
      root.classList.toggle('app-dark', isDarkModeActive.value)

      const resolvedAccent = resolveAccentHex(accentName.value, accentColor.value)

      if (accentName.value === 'custom') {
        root.style.setProperty('--layout-accent', resolvedAccent)
        root.style.setProperty('--layout-accent-strong', mixHex(resolvedAccent, '#000000', 0.28))
        root.style.setProperty('--layout-accent-soft', toRgba(resolvedAccent, 0.16))
      } else {
        root.style.removeProperty('--layout-accent')
        root.style.removeProperty('--layout-accent-strong')
        root.style.removeProperty('--layout-accent-soft')
      }

      updatePrimaryPalette(palette(resolvedAccent))
    },
    { immediate: true }
  )

  onMounted(() => {
    mediaQuery?.addEventListener?.('change', syncDarkPreference)
  })

  onUnmounted(() => {
    mediaQuery?.removeEventListener?.('change', syncDarkPreference)
  })

  return {
    options: layoutOptionSets,
    isDarkModeActive,
    setThemeName: layoutStore.setThemeName,
    setAccentName: layoutStore.setAccentName,
    setAccentColor: layoutStore.setAccentColor,
    setSurfaceName: layoutStore.setSurfaceName,
    setFontName: layoutStore.setFontName,
    setDensity: layoutStore.setDensity,
    setTextScale: layoutStore.setTextScale,
    setRadius: layoutStore.setRadius,
    setDarkMode: layoutStore.setDarkMode,
    setSidebarMode: layoutStore.setSidebarMode,
    setMotionPreset: layoutStore.setMotionPreset,
    setGradientMode: layoutStore.setGradientMode,
    setSidebarLogoPosition: layoutStore.setSidebarLogoPosition,
    setSidebarProfilePosition: layoutStore.setSidebarProfilePosition,
    setSidebarSearchPosition: layoutStore.setSidebarSearchPosition,
    setTopbarLogoVisibility: layoutStore.setTopbarLogoVisibility,
    setTopbarProfilePosition: layoutStore.setTopbarProfilePosition,
    setTopbarSearchPosition: layoutStore.setTopbarSearchPosition,
    setLayoutStyle: layoutStore.setLayoutStyle
  }
}
